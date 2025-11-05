<?php
/**
 * Globale Tarif-Synchronisation
 * Aktualisiert alle Kunden eines Tarifs, wenn Admin die Limits ändert
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
    
    // Finde alle Kunden mit diesem Produkt
    $sql = "
        SELECT DISTINCT u.id, u.name, u.email,
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
    $customers = $stmt->fetchAll();
    
    if (empty($customers)) {
        throw new Exception('Keine Kunden mit diesem Produkt gefunden');
    }
    
    $updated = [
        'freebies' => 0,
        'referrals' => 0,
        'skipped_manual' => 0
    ];
    
    $pdo->beginTransaction();
    
    foreach ($customers as $customer) {
        $customerId = $customer['id'];
        
        // Freebie-Limits aktualisieren
        if ($overwriteManual || $customer['freebie_source'] !== 'manual') {
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
        if ($overwriteManual || $customer['referral_source'] !== 'manual') {
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
    }
    
    $pdo->commit();
    
    // Log-Eintrag
    $logMessage = sprintf(
        "Admin '%s' hat Tarif '%s' synchronisiert: %d Kunden betroffen, %d Freebie-Limits, %d Referral-Slots aktualisiert, %d manuell übersprungen",
        $_SESSION['name'] ?? $_SESSION['email'],
        $product['product_name'],
        count($customers),
        $updated['freebies'],
        $updated['referrals'],
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
            'manual_skipped' => $updated['skipped_manual']
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
