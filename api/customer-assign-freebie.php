<?php
/**
 * API: Freebie Template einem Kunden zuweisen
 */

session_start();
header('Content-Type: application/json');

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Daten validieren
    $userId = intval($_POST['user_id'] ?? 0);
    $freebieId = intval($_POST['freebie_id'] ?? 0);
    
    if ($userId <= 0 || $freebieId <= 0) {
        throw new Exception('Ungültige Daten');
    }
    
    // Prüfen ob User existiert
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Prüfen ob Freebie existiert
    $stmt = $pdo->prepare("SELECT id, title FROM freebie_templates WHERE id = ?");
    $stmt->execute([$freebieId]);
    $freebie = $stmt->fetch();
    
    if (!$freebie) {
        throw new Exception('Freebie Template nicht gefunden');
    }
    
    // Prüfen ob bereits zugewiesen
    $stmt = $pdo->prepare("SELECT id FROM user_freebies WHERE user_id = ? AND freebie_id = ?");
    $stmt->execute([$userId, $freebieId]);
    
    if ($stmt->fetch()) {
        throw new Exception('Freebie wurde diesem Kunden bereits zugewiesen');
    }
    
    // Zuweisung erstellen
    $stmt = $pdo->prepare("
        INSERT INTO user_freebies (user_id, freebie_id, assigned_at, assigned_by)
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$userId, $freebieId, $_SESSION['user_id']]);
    
    // Benachrichtigungs-E-Mail senden
    sendAssignmentEmail($user['email'], $user['name'], $freebie['title']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Freebie erfolgreich zugewiesen'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Benachrichtigungs-E-Mail senden
 */
function sendAssignmentEmail($email, $name, $freebieTitle) {
    $subject = "Neues Freebie Template verfügbar!";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #a855f7;'>Hallo $name!</h2>
            <p>Dir wurde ein neues Freebie Template zugewiesen:</p>
            
            <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3>📄 " . htmlspecialchars($freebieTitle) . "</h3>
            </div>
            
            <p>
                <a href='https://app.mehr-infos-jetzt.de/customer/freebies.php' 
                   style='display: inline-block; background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                    Jetzt ansehen
                </a>
            </p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: KI Leadsystem <noreply@mehr-infos-jetzt.de>\r\n";
    
    mail($email, $subject, $message, $headers);
}
