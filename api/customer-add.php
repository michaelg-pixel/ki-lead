<?php
/**
 * API: Neuen Kunden manuell hinzufügen
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
    
    // Formulardaten validieren
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception('Alle Felder sind erforderlich');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
    }
    
    // Prüfen ob E-Mail bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception('Diese E-Mail-Adresse wird bereits verwendet');
    }
    
    // RAW-Code generieren
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Passwort hashen
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kunden in Datenbank anlegen
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, 
            email, 
            password, 
            role, 
            is_active,
            raw_code,
            source,
            created_at
        ) VALUES (?, ?, ?, 'customer', 1, ?, 'manual', NOW())
    ");
    
    $stmt->execute([$name, $email, $hashedPassword, $rawCode]);
    $userId = $pdo->lastInsertId();
    
    // Willkommens-E-Mail senden
    sendWelcomeEmail($email, $name, $password, $rawCode);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kunde erfolgreich hinzugefügt',
        'user_id' => $userId,
        'raw_code' => $rawCode
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Willkommens-E-Mail senden
 */
function sendWelcomeEmail($email, $name, $password, $rawCode) {
    $subject = "Willkommen beim KI Leadsystem!";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #a855f7;'>Willkommen, $name!</h2>
            <p>Dein Account wurde erfolgreich erstellt.</p>
            
            <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3>Deine Zugangsdaten:</h3>
                <p><strong>E-Mail:</strong> $email</p>
                <p><strong>Passwort:</strong> $password</p>
                <p><strong>RAW-Code:</strong> $rawCode</p>
            </div>
            
            <p>
                <a href='https://app.mehr-infos-jetzt.de/public/login.php' 
                   style='display: inline-block; background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                    Jetzt einloggen
                </a>
            </p>
            
            <p style='color: #888; font-size: 14px; margin-top: 20px;'>
                Bitte ändere dein Passwort nach dem ersten Login!
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
