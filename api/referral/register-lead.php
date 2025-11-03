<?php
/**
 * API: Register Referral Lead
 * Lead meldet sich für Empfehlungsprogramm an
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
    $db = Database::getInstance()->getConnection();
    $referral = new ReferralHelper($db);
    
    // Input validieren
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['user_id'] ?? null;
    $refCode = $input['ref'] ?? null;
    $email = $input['email'] ?? null;
    $gdprConsent = $input['gdpr_consent'] ?? false;
    
    // Validierung
    if (!$userId || !$refCode || !$email) {
        throw new Exception('Pflichtfelder fehlen');
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
    
    // Lead registrieren
    $result = $referral->registerLead(
        $userId,
        $refCode,
        $email,
        $ipHash,
        $gdprConsent
    );
    
    if ($result['success']) {
        // Hole User-Daten für E-Mail
        $stmt = $db->prepare("
            SELECT email, company_name, company_email, company_imprint_html 
            FROM customers 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Sende Bestätigungs-E-Mail
        try {
            sendConfirmationEmail(
                $email,
                $result['confirmation_token'],
                $user
            );
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            // Fehler nicht an User weitergeben, Lead ist bereits gespeichert
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Erfolgreich registriert! Bitte bestätigen Sie Ihre E-Mail-Adresse.',
            'lead_id' => $result['lead_id']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'message' => $result['message']
        ]);
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
 * Sende Bestätigungs-E-Mail
 */
function sendConfirmationEmail($email, $token, $user) {
    $companyName = $user['company_name'] ?: 'Mehr-Infos-Jetzt.de';
    $companyEmail = $user['company_email'] ?: 'noreply@mehr-infos-jetzt.de';
    $imprint = $user['company_imprint_html'] ?: getDefaultImprint();
    
    $confirmUrl = "https://" . $_SERVER['HTTP_HOST'] . "/api/referral/confirm-lead.php?token=" . $token;
    
    $subject = "Bitte bestätigen Sie Ihre E-Mail-Adresse";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button { display: inline-block; padding: 12px 24px; background: #10b981; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Willkommen beim Empfehlungsprogramm!</h2>
            <p>Vielen Dank für Ihre Anmeldung beim Empfehlungsprogramm von <strong>{$companyName}</strong>.</p>
            <p>Bitte bestätigen Sie Ihre E-Mail-Adresse, um Ihre Teilnahme zu aktivieren:</p>
            <a href='{$confirmUrl}' class='button'>E-Mail bestätigen</a>
            <p>Oder kopieren Sie diesen Link in Ihren Browser:<br>
            <small>{$confirmUrl}</small></p>
            <div class='footer'>
                <hr>
                <small>
                Diese E-Mail wurde im Rahmen des Empfehlungsprogramms von {$companyName} versendet.<br><br>
                {$imprint}
                </small>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "From: {$companyName} <{$companyEmail}>\r\n";
    $headers .= "Reply-To: {$companyEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($email, $subject, $message, $headers);
}

/**
 * Fallback-Impressum
 */
function getDefaultImprint() {
    return "
        <strong>KI-Lead-System</strong><br>
        Technischer Dienstleister im Auftrag<br>
        E-Mail: support@mehr-infos-jetzt.de
    ";
}
