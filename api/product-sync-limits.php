<?php
/**
 * Globale Tarif-Synchronisation - VERSION 2.0
 * Aktualisiert Kunden eines Tarifs ODER alle Kunden (auch manuell angelegte)
 */

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Zugriff verweigert']));
}

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = getDBConnection();
    
    // POST-Daten validieren
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Nur POST-Requests erlaubt');
    }
    
    $productId = $_POST['product_id'] ?? null;
    $overwriteManual = isset($_POST['overwrite_manual']) && $_POST['overwrite_manual'] === '1';
    $allCustomers = isset($_POST['all_customers']) && $_POST['all_customers'] === '1'; // NEUE OPTION
    
    if (!$productId) {
        throw new Exception('Produkt-ID fehlt');
    }
    
    // Produkt-Konfiguration laden
    $stmt = $pdo->prepare("
        SELECT 
            product_id,
            product_name,
            product_type,
            own_freebies_limit,
            ready_freebies_count,
            referral_program_slots,
            is_active
        FROM digistore_products 
        WHERE product_id = ?
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Produkt nicht gefunden');
    }
    
    if (!$product['is_active']) {
        throw new Exception('Produkt ist nicht aktiv');
    }
    
    // Finde Kunden basierend auf Option
    if ($allCustomers) {
        // ALLE Kunden des Systems (auch manuell angelegte)
        $sql = "
            SELECT DISTINCT u.id, u.name, u.email, u.digistore_product_id,
                   cfl.freebie_limit as current_freebie_limit,
                   cfl.source as freebie_source,
                   crs.total_slots as current_referral_slots,
                   crs.source as referral_source
            FROM users u
            LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
            LEFT JOIN customer_referral_slots crs ON u.id = crs.customer_id
            WHERE u.role = 'customer'
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Nur Kunden mit diesem Produkt (wie bisher)
        $sql = "
            SELECT DISTINCT u.id, u.name, u.email, u.digistore_product_id,
                   cfl.freebie_limit as current_freebie_limit,
                   cfl.source as freebie_source,
                   crs.total_slots as current_referral_slots,
                   crs.source as referral_source
            FROM users u
            LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
            LEFT JOIN customer_referral_slots crs ON u.id = crs.customer_id
            WHERE u.role = 'customer'
            AND (u.digistore_product_id = ? 
                 OR cfl.product_id = ?
                 OR crs.product_id = ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $productId, $productId]);
    }
    
    $customers = $stmt->fetchAll();
    
    if (empty($customers)) {
        throw new Exception('Keine Kunden gefunden');
    }
    
    $updated = [
        'freebies' => 0,
        'referrals' => 0,
        'skipped_manual' => 0,
        'newly_initialized' => 0
    ];
    
    $pdo->beginTransaction();
    
    foreach ($customers as $customer) {
        $customerId = $customer['id'];
        $isNewCustomer = empty($customer['current_freebie_limit']) && empty($customer['current_referral_slots']);
        
        // Freebie-Limits aktualisieren
        $shouldUpdateFreebies = $overwriteManual || 
                                $customer['freebie_source'] !== 'manual' || 
                                $customer['freebie_source'] === null;
        
        if ($shouldUpdateFreebies) {
            // Update oder Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (
                    customer_id, freebie_limit, product_id, product_name, source
                ) VALUES (?, ?, ?, ?, 'webhook')
                ON DUPLICATE KEY UPDATE
                    freebie_limit = VALUES(freebie_limit),
                    product_id = VALUES(product_id),
                    product_name = VALUES(product_name),
                    source = IF(source = 'manual' AND ? = 0, 'manual', 'webhook'),
                    updated_at = NOW()
            ");
            $stmt->execute([
                $customerId,
                $product['own_freebies_limit'],
                $productId,
                $product['product_name'],
                $overwriteManual ? 1 : 0
            ]);
            $updated['freebies']++;
        } else {
            $updated['skipped_manual']++;
        }
        
        // Referral-Slots aktualisieren
        $shouldUpdateReferrals = $overwriteManual || 
                                 $customer['referral_source'] !== 'manual' || 
                                 $customer['referral_source'] === null;
        
        if ($shouldUpdateReferrals) {
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (
                    customer_id, total_slots, used_slots, product_id, product_name, source
                ) VALUES (?, ?, 0, ?, ?, 'webhook')
                ON DUPLICATE KEY UPDATE
                    total_slots = VALUES(total_slots),
                    product_id = VALUES(product_id),
                    product_name = VALUES(product_name),
                    source = IF(source = 'manual' AND ? = 0, 'manual', 'webhook'),
                    updated_at = NOW()
            ");
            $stmt->execute([
                $customerId,
                $product['referral_program_slots'],
                $productId,
                $product['product_name'],
                $overwriteManual ? 1 : 0
            ]);
            $updated['referrals']++;
        }
        
        // Digistore-Produkt-ID in User-Tabelle setzen falls nicht vorhanden
        if (empty($customer['digistore_product_id']) || $allCustomers) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET digistore_product_id = ?,
                    digistore_product_name = ?
                WHERE id = ?
            ");
            $stmt->execute([$productId, $product['product_name'], $customerId]);
        }
        
        if ($isNewCustomer) {
            $updated['newly_initialized']++;
        }
    }
    
    $pdo->commit();
    
    // Log-Eintrag
    $scope = $allCustomers ? 'ALLE Kunden' : "Kunden mit Tarif '{$product['product_name']}'";
    $logMessage = sprintf(
        "Admin '%s' hat %s synchronisiert: %d Kunden betroffen, %d Freebie-Limits, %d Referral-Slots aktualisiert, %d neu initialisiert, %d manuell übersprungen",
        $_SESSION['name'] ?? $_SESSION['email'],
        $scope,
        count($customers),
        $updated['freebies'],
        $updated['referrals'],
        $updated['newly_initialized'],
        $updated['skipped_manual']
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at) 
        VALUES (?, 'product_sync', ?, NOW())
    ");
    
    try {
        $stmt->execute([$_SESSION['user_id'], $logMessage]);
    } catch (PDOException $e) {
        // Logging optional
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tarif erfolgreich synchronisiert',
        'stats' => [
            'total_customers' => count($customers),
            'freebies_updated' => $updated['freebies'],
            'referrals_updated' => $updated['referrals'],
            'newly_initialized' => $updated['newly_initialized'],
            'manual_skipped' => $updated['skipped_manual'],
            'scope' => $allCustomers ? 'all' : 'product'
        ],
        'product' => [
            'name' => $product['product_name'],
            'freebies' => $product['own_freebies_limit'],
            'referral_slots' => $product['referral_program_slots']
        ]
    ]);
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
