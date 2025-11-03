<?php
/**
 * API: Register Referral Lead
 * Lead meldet sich für Empfehlungsprogramm an
 * UPDATED: Erstellt auch Eintrag in lead_users mit user_id Verknüpfung
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';

// Nur POST erlaubt
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

try {
    $db = getDBConnection();
    $referral = new ReferralHelper($db);
    
    // Input validieren
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Support both user_id and customer_id
    $customerId = $input['customer_id'] ?? $input['user_id'] ?? null;
    $refCode = $input['ref_code'] ?? $input['ref'] ?? null;
    $email = $input['email'] ?? null;
    $name = $input['name'] ?? null; // Optional: Name des Leads
    $gdprConsent = $input['gdpr_consent'] ?? false;
    
    // Validierung
    if (!$customerId || !$refCode || !$email) {
        throw new Exception('Pflichtfelder fehlen: customer_id, ref_code und email sind erforderlich');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    if (!$gdprConsent) {
        throw new Exception('Datenschutzerklärung muss akzeptiert werden');
    }
    
    // Validiere Referral-Code
    if (!$referral->validateRefCode($refCode)) {
        throw new Exception('Ungültiger oder inaktiver Referral-Code');
    }
    
    // Hole IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = $referral->hashIP($ip);
    
    // 1. Lead in referral_leads registrieren (für Tracking)
    $result = $referral->registerLead(
        $customerId,
        $refCode,
        $email,
        $ipHash,
        $gdprConsent
    );
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    // 2. Lead in lead_users registrieren (für Dashboard-Zugriff)
    $leadUserId = createLeadUser($db, $email, $name, $customerId, $refCode);
    
    if ($leadUserId) {
        // 3. Hole User-Daten für E-Mail
        $stmt = $db->prepare("
            SELECT email, company_name, company_email 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$customerId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 4. Sende Bestätigungs-E-Mail mit Login-Link
        try {
            sendConfirmationEmail(
                $email,
                $result['confirmation_token'],
                $user,
                $leadUserId
            );
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            // Fehler nicht an User weitergeben, Lead ist bereits gespeichert
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Erfolgreich registriert! Bitte bestätigen Sie Ihre E-Mail-Adresse.',
            'lead_id' => $result['lead_id'],
            'lead_user_id' => $leadUserId
        ]);
    } else {
        throw new Exception('Fehler beim Erstellen des Lead-Accounts');
    }
    
} catch (Exception $e) {
    error_log("Referral Lead Registration Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}

/**
 * Erstelle Lead User Account (für Dashboard-Zugriff)
 */
function createLeadUser($db, $email, $name, $userId, $refCode) {
    try {
        // Prüfe ob Lead bereits existiert
        $stmt = $db->prepare("SELECT id FROM lead_users WHERE email = ? AND user_id = ?");
        $stmt->execute([$email, $userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            return $existing['id'];
        }
        
        // Extrahiere Namen aus E-Mail wenn nicht vorhanden
        if (!$name) {
            $name = explode('@', $email)[0];
            $name = ucfirst(str_replace(['.', '_', '-'], ' ', $name));
        }
        
        // Generiere eindeutigen Referral-Code für den Lead
        $leadReferralCode = generateUniqueLeadReferralCode($db);
        
        // Generiere temporäres Passwort (wird per E-Mail gesendet)
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
        
        // Erstelle Lead User
        $stmt = $db->prepare("
            INSERT INTO lead_users 
            (name, email, password_hash, user_id, referral_code, referred_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            $name,
            $email,
            $hashedPassword,
            $userId,              // user_id - Verknüpfung zum Customer!
            $leadReferralCode,
            $refCode              // referred_by - Wer hat diesen Lead geworben
        ]);
        
        $leadUserId = $db->lastInsertId();
        
        // Speichere Passwort temporär für E-Mail (in Session oder DB)
        $_SESSION['lead_temp_password_' . $leadUserId] = $tempPassword;
        
        return $leadUserId;
        
    } catch (Exception $e) {
        error_log("Create Lead User Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generiere eindeutigen Referral-Code für Lead
 */
function generateUniqueLeadReferralCode($db) {
    $maxAttempts = 10;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $code = 'LEAD' . strtoupper(bin2hex(random_bytes(6)));
        
        // Prüfe ob Code bereits existiert
        $stmt = $db->prepare("SELECT id FROM lead_users WHERE referral_code = ?");
        $stmt->execute([$code]);
        
        if (!$stmt->fetch()) {
            return $code;
        }
    }
    
    // Fallback mit Timestamp
    return 'LEAD' . strtoupper(bin2hex(random_bytes(4))) . time();
}

/**
 * Sende Bestätigungs-E-Mail mit Login-Daten
 */
function sendConfirmationEmail($email, $token, $user, $leadUserId) {
    $companyName = $user['company_name'] ?? 'Mehr-Infos-Jetzt.de';
    $companyEmail = $user['company_email'] ?? 'noreply@mehr-infos-jetzt.de';
    
    $confirmUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/referral/confirm-lead.php?token=" . $token;
    $dashboardUrl = "https://" . $_SERVER['HTTP_HOST'] . "/lead_login.php";
    
    // Hole temporäres Passwort
    $tempPassword = $_SESSION['lead_temp_password_' . $leadUserId] ?? '(siehe separate E-Mail)';
    
    $subject = "Willkommen beim Empfehlungsprogramm - Bestätigen Sie Ihre E-Mail";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 14px 28px; background: #10b981; color: white !important; text-decoration: none; border-radius: 8px; margin: 20px 0; font-weight: bold; }
            .credentials { background: #f0f7ff; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin:0;font-size:28px;'>🎉 Willkommen!</h1>
                <p style='margin:10px 0 0 0;opacity:0.9;'>Empfehlungsprogramm von {$companyName}</p>
            </div>
            
            <div class='content'>
                <h2>Vielen Dank für Ihre Anmeldung!</h2>
                <p>Sie sind jetzt Teil unseres exklusiven Empfehlungsprogramms. Empfehlen Sie uns weiter und sichern Sie sich attraktive Belohnungen!</p>
                
                <h3>🔐 Ihre Login-Daten:</h3>
                <div class='credentials'>
                    <strong>Dashboard-URL:</strong><br>
                    <a href='{$dashboardUrl}'>{$dashboardUrl}</a><br><br>
                    <strong>E-Mail:</strong> {$email}<br>
                    <strong>Temporäres Passwort:</strong> {$tempPassword}
                </div>
                
                <p><strong>⚠️ Wichtig:</strong> Bitte ändern Sie Ihr Passwort nach dem ersten Login!</p>
                
                <h3>✅ Bestätigen Sie Ihre E-Mail-Adresse:</h3>
                <p>Um Ihre Teilnahme zu aktivieren, bestätigen Sie bitte Ihre E-Mail-Adresse:</p>
                <center>
                    <a href='{$confirmUrl}' class='button'>E-Mail jetzt bestätigen</a>
                </center>
                
                <p style='font-size:13px;color:#666;'>Oder kopieren Sie diesen Link in Ihren Browser:<br>
                <code style='background:#f5f5f5;padding:5px;display:block;word-break:break-all;'>{$confirmUrl}</code></p>
                
                <h3>🎁 So funktioniert's:</h3>
                <ol>
                    <li>Loggen Sie sich in Ihr Dashboard ein</li>
                    <li>Wählen Sie ein Freebie zum Teilen</li>
                    <li>Teilen Sie Ihren persönlichen Empfehlungslink</li>
                    <li>Sammeln Sie erfolgreiche Empfehlungen</li>
                    <li>Erhalten Sie exklusive Belohnungen!</li>
                </ol>
            </div>
            
            <div class='footer'>
                Diese E-Mail wurde im Rahmen des Empfehlungsprogramms von {$companyName} versendet.<br>
                Bei Fragen kontaktieren Sie uns unter: {$companyEmail}
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "From: {$companyName} <{$companyEmail}>\r\n";
    $headers .= "Reply-To: {$companyEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Cleanup Session
    unset($_SESSION['lead_temp_password_' . $leadUserId]);
    
    return mail($email, $subject, $message, $headers);
}
