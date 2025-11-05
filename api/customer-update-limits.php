<?php
/**
 * Admin API: Freebie-Limits und Empfehlungs-Slots manuell anpassen
 * VERSION 2.0 - Mit Source-Tracking
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
    
    $userId = $_POST['user_id'] ?? null;
    $freebieLimit = isset($_POST['freebie_limit']) ? (int)$_POST['freebie_limit'] : null;
    $referralSlots = isset($_POST['referral_slots']) ? (int)$_POST['referral_slots'] : null;
    
    if (!$userId) {
        throw new Exception('User-ID fehlt');
    }
    
    // Prüfen ob User existiert
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    $updated = [];
    
    // Freebie-Limit aktualisieren
    if ($freebieLimit !== null) {
        // Prüfen ob Limit existiert
        $stmt = $pdo->prepare("SELECT id FROM customer_freebie_limits WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update - WICHTIG: source = 'manual' setzen!
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, 
                    source = 'manual',
                    product_id = 'manual',
                    product_name = 'Manuell vom Admin gesetzt',
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$freebieLimit, $userId]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (
                    customer_id, freebie_limit, product_id, product_name, source
                ) VALUES (?, ?, 'manual', 'Manuell vom Admin gesetzt', 'manual')
            ");
            $stmt->execute([$userId, $freebieLimit]);
        }
        
        $updated[] = "Freebie-Limit: $freebieLimit (manuell)";
    }
    
    // Empfehlungs-Slots aktualisieren
    if ($referralSlots !== null) {
        // Prüfen ob Slots existieren
        $stmt = $pdo->prepare("SELECT id FROM customer_referral_slots WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update - WICHTIG: source = 'manual' setzen!
            $stmt = $pdo->prepare("
                UPDATE customer_referral_slots 
                SET total_slots = ?, 
                    source = 'manual',
                    product_id = 'manual',
                    product_name = 'Manuell vom Admin gesetzt',
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$referralSlots, $userId]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (
                    customer_id, total_slots, used_slots, 
                    product_id, product_name, source
                ) VALUES (?, ?, 0, 'manual', 'Manuell vom Admin gesetzt', 'manual')
            ");
            $stmt->execute([$userId, $referralSlots]);
        }
        
        $updated[] = "Empfehlungs-Slots: $referralSlots (manuell)";
    }
    
    if (empty($updated)) {
        throw new Exception('Keine Daten zum Aktualisieren angegeben');
    }
    
    // Log-Eintrag
    $logMessage = sprintf(
        "Admin '%s' hat Limits für '%s' (%s) manuell aktualisiert: %s",
        $_SESSION['name'] ?? $_SESSION['email'],
        $user['name'],
        $user['email'],
        implode(', ', $updated)
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at) 
        VALUES (?, 'customer_limits_manual_update', ?, NOW())
    ");
    
    try {
        $stmt->execute([$_SESSION['user_id'], $logMessage]);
    } catch (PDOException $e) {
        // Logging optional
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Limits erfolgreich manuell aktualisiert',
        'updated' => $updated,
        'warning' => 'Diese Limits sind jetzt als "manuell" markiert und werden vom Webhook nicht überschrieben'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
