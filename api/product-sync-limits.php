<?php
/**
 * Globale Tarif-Synchronisation
 * Aktualisiert alle Kunden eines Tarifs, wenn Admin die Limits ändert
 * KORRIGIERT: Bezieht sich immer auf das spezifische Produkt
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
    $includeUnlinked = isset($_POST['include_all']) && $_POST['include_all'] === '1';
    
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
    
    // Finde Kunden basierend auf Modus
    if ($includeUnlinked) {
        // ALLE Kunden MIT DIESEM PRODUKT (auch manuell angelegte ohne Limits-Einträge)
        $sql = "
            SELECT DISTINCT u.id, u.name, u.email,
                   u.digistore_product_id,
                   cfl.freebie_limit as current_freebie_limit,
                   cfl.source as freebie_source,
                   crs.total_slots as current_referral_slots,
                   crs.source as referral_source
            FROM users u
            LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
            LEFT JOIN customer_referral_slots crs ON u.id = crs.customer_id
            WHERE u.role = 'customer'
            AND (
                u.digistore_product_id = ?
                OR cfl.product_id = ?
                OR crs.product_id = ?
            )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $productId, $productId]);
    } else {
        // Nur Kunden die bereits in den Limits-Tabellen mit diesem Produkt verknüpft sind
        $sql = "
            SELECT DISTINCT u.id, u.name, u.email,
                   u.digistore_product_id,
                   cfl.freebie_limit as current_freebie_limit,
                   cfl.source as freebie_source,
                   crs.total_slots as current_referral_slots,
                   crs.source as referral_source
            FROM users u
            LEFT JOIN customer_freebie_limits cfl ON u.id = cfl.customer_id
            LEFT JOIN customer_referral_slots crs ON u.id = crs.customer_id
            WHERE u.role = 'customer'
            AND (cfl.product_id = ? OR crs.product_id = ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId, $productId]);
    }
    
    $customers = $stmt->fetchAll();
    
    if (empty($customers)) {
        throw new Exception('Keine Kunden mit diesem Produkt gefunden');
    }
    
    $updated = [
        'freebies' => 0,
        'referrals' => 0,
        'skipped_manual' => 0,
        'new_entries' => 0
    ];
    
    $pdo->beginTransaction();
    
    foreach ($customers as $customer) {
        $customerId = $customer['id'];
        $hasFreebieLimit = !is_null($customer['current_freebie_limit']);
        $hasReferralSlots = !is_null($customer['current_referral_slots']);
        
        // Freebie-Limits aktualisieren/erstellen
        if (!$hasFreebieLimit) {
            // Neuer Eintrag für Kunden ohne Limits (z.B. manuell angelegt)
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (
                    customer_id, freebie_limit, product_id, product_name, source
                ) VALUES (?, ?, ?, ?, 'webhook')
            ");
            $stmt->execute([
                $customerId,
                $product['own_freebies_limit'],
                $productId,
                $product['product_name']
            ]);
            $updated['new_entries']++;
            $updated['freebies']++;
            
            // Auch digistore_product_id in users setzen falls leer
            $stmt = $pdo->prepare("
                UPDATE users 
                SET digistore_product_id = ?, digistore_product_name = ?
                WHERE id = ? AND (digistore_product_id IS NULL OR digistore_product_id = '')
            ");
            $stmt->execute([$productId, $product['product_name'], $customerId]);
            
        } elseif ($overwriteManual || $customer['freebie_source'] !== 'manual') {
            // Update bestehender Eintrag (wenn nicht manuell oder wenn überschreiben erlaubt)
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits
                SET freebie_limit = ?,
                    product_id = ?,
                    product_name = ?,
                    source = IF(source = 'manual' AND ? = 0, 'manual', 'webhook'),
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([
                $product['own_freebies_limit'],
                $productId,
                $product['product_name'],
                $overwriteManual ? 1 : 0,
                $customerId
            ]);
            $updated['freebies']++;
        } else {
            // Manuell gesetztes Limit wird übersprungen
            $updated['skipped_manual']++;
        }
        
        // Referral-Slots aktualisieren/erstellen
        if (!$hasReferralSlots) {
            // Neuer Eintrag für Kunden ohne Slots
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (
                    customer_id, total_slots, used_slots, product_id, product_name, source
                ) VALUES (?, ?, 0, ?, ?, 'webhook')
            ");
            $stmt->execute([
                $customerId,
                $product['referral_program_slots'],
                $productId,
                $product['product_name']
            ]);
            $updated['referrals']++;
            
        } elseif ($overwriteManual || $customer['referral_source'] !== 'manual') {
            // Update bestehender Eintrag (wenn nicht manuell oder wenn überschreiben erlaubt)
            $stmt = $pdo->prepare("
                UPDATE customer_referral_slots
                SET total_slots = ?,
                    product_id = ?,
                    product_name = ?,
                    source = IF(source = 'manual' AND ? = 0, 'manual', 'webhook'),
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([
                $product['referral_program_slots'],
                $productId,
                $product['product_name'],
                $overwriteManual ? 1 : 0,
                $customerId
            ]);
            $updated['referrals']++;
        }
    }
    
    $pdo->commit();
    
    // Log-Eintrag
    $modeText = $includeUnlinked ? 'inkl. manuell angelegter Kunden' : 'nur verknüpfte Kunden';
    $logMessage = sprintf(
        "Admin '%s' hat Tarif '%s' synchronisiert (%s): %d Kunden betroffen, %d Freebie-Limits, %d Referral-Slots aktualisiert, %d neu erstellt, %d manuell übersprungen",
        $_SESSION['name'] ?? $_SESSION['email'],
        $product['product_name'],
        $modeText,
        count($customers),
        $updated['freebies'],
        $updated['referrals'],
        $updated['new_entries'],
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
            'new_entries_created' => $updated['new_entries'],
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
