<?php
/**
 * Admin API: Freebie-Limits und Empfehlungs-Slots manuell anpassen
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
            // Update
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$freebieLimit, $userId]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name)
                VALUES (?, ?, 'manual', 'Manuell gesetzt')
            ");
            $stmt->execute([$userId, $freebieLimit]);
        }
        
        $updated[] = "Freebie-Limit: $freebieLimit";
    }
    
    // Empfehlungs-Slots aktualisieren
    if ($referralSlots !== null) {
        // Prüfen ob Slots existieren
        $stmt = $pdo->prepare("SELECT id FROM customer_referral_slots WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE customer_referral_slots 
                SET total_slots = ?, updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$referralSlots, $userId]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (customer_id, total_slots, used_slots)
                VALUES (?, ?, 0)
            ");
            $stmt->execute([$userId, $referralSlots]);
        }
        
        $updated[] = "Empfehlungs-Slots: $referralSlots";
    }
    
    if (empty($updated)) {
        throw new Exception('Keine Daten zum Aktualisieren angegeben');
    }
    
    // Log-Eintrag
    $logMessage = sprintf(
        "Admin '%s' hat Limits für '%s' (%s) aktualisiert: %s",
        $_SESSION['name'] ?? $_SESSION['email'],
        $user['name'],
        $user['email'],
        implode(', ', $updated)
    );
    
    $stmt = $pdo->prepare("
        INSERT INTO admin_logs (admin_id, action, details, created_at) 
        VALUES (?, 'customer_limits_update', ?, NOW())
    ");
    
    try {
        $stmt->execute([$_SESSION['user_id'], $logMessage]);
    } catch (PDOException $e) {
        // Logging optional
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Limits erfolgreich aktualisiert',
        'updated' => $updated
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
