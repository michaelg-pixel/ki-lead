<?php
/**
 * API: Lead registrieren
 * POST /api_referral_rewards/register-lead.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validierung
if (!isset($input['email']) || !isset($input['name'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Email und Name erforderlich'
    ]);
    exit;
}

$email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
if (!$email) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ungültige E-Mail-Adresse'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Prüfen ob Lead bereits existiert
    $stmt = $db->prepare("SELECT id, referral_code FROM referral_leads WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => 'Lead bereits registriert',
            'data' => [
                'lead_id' => $existing['id'],
                'referral_code' => $existing['referral_code'],
                'referral_link' => 'https://app.mehr-infos-jetzt.de/lead_login.php?ref=' . $existing['referral_code']
            ]
        ]);
        exit;
    }
    
    // Neuen Lead erstellen
    $referral_code = substr(md5($email . time()), 0, 10);
    $api_token = bin2hex(random_bytes(32));
    $referrer_code = $input['referrer_code'] ?? null;
    
    $stmt = $db->prepare("
        INSERT INTO referral_leads 
        (name, email, referral_code, api_token, referrer_code, registered_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $input['name'],
        $email,
        $referral_code,
        $api_token,
        $referrer_code
    ]);
    
    $lead_id = $db->lastInsertId();
    
    // Wenn Referrer Code vorhanden, Empfehlung tracken
    if ($referrer_code) {
        $stmt = $db->prepare("
            UPDATE referral_leads 
            SET total_referrals = total_referrals + 1 
            WHERE referral_code = ?
        ");
        $stmt->execute([$referrer_code]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Lead erfolgreich registriert',
        'data' => [
            'lead_id' => $lead_id,
            'referral_code' => $referral_code,
            'api_token' => $api_token,
            'referral_link' => 'https://app.mehr-infos-jetzt.de/lead_login.php?ref=' . $referral_code
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
