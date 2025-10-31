<?php
/**
 * API: Kunden-Status ändern (Sperren/Aktivieren)
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
    
    // Daten aus JSON-Body lesen
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = intval($input['user_id'] ?? 0);
    
    if ($userId <= 0) {
        throw new Exception('Ungültige Benutzer-ID');
    }
    
    // Aktuellen Status abrufen
    $stmt = $pdo->prepare("SELECT id, name, email, is_active FROM users WHERE id = ? AND role = 'customer'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Status umkehren
    $newStatus = $user['is_active'] ? 0 : 1;
    
    // Status in Datenbank aktualisieren
    $stmt = $pdo->prepare("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $userId]);
    
    // E-Mail senden wenn gesperrt
    if ($newStatus === 0) {
        sendSuspensionEmail($user['email'], $user['name']);
    } else {
        sendReactivationEmail($user['email'], $user['name']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $newStatus ? 'Kunde aktiviert' : 'Kunde gesperrt',
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Sperrung-Benachrichtigung
 */
function sendSuspensionEmail($email, $name) {
    $subject = "Dein Account wurde gesperrt";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #ef4444;'>Hallo $name,</h2>
            <p>Dein Account wurde vorübergehend gesperrt.</p>
            <p>Bei Fragen kontaktiere uns bitte unter support@mehr-infos-jetzt.de</p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: KI Leadsystem <noreply@mehr-infos-jetzt.de>\r\n";
    
    mail($email, $subject, $message, $headers);
}

/**
 * Reaktivierung-Benachrichtigung
 */
function sendReactivationEmail($email, $name) {
    $subject = "Dein Account wurde aktiviert";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #4ade80;'>Hallo $name,</h2>
            <p>Dein Account wurde wieder aktiviert. Du kannst dich jetzt wieder einloggen!</p>
            <p>
                <a href='https://app.mehr-infos-jetzt.de/public/login.php' 
                   style='display: inline-block; background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                    Jetzt einloggen
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
