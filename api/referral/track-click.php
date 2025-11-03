<?php
/**
 * API: Track Referral Click
 * Wird von freebie.php aufgerufen wenn ref-Parameter vorhanden
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
    
    $refCode = $input['ref'] ?? $_GET['ref'] ?? null;
    $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
    
    if (!$refCode) {
        throw new Exception('Referral-Code fehlt');
    }
    
    // Validiere Referral-Code
    if (!$referral->validateRefCode($refCode)) {
        throw new Exception('Ungültiger oder inaktiver Referral-Code');
    }
    
    // Hole User-ID wenn nicht übergeben
    if (!$userId) {
        $userId = $referral->getUserIdFromRefCode($refCode);
        if (!$userId) {
            throw new Exception('User nicht gefunden');
        }
    }
    
    // Hole IP und UserAgent
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    
    // DSGVO-konform hashen
    $ipHash = $referral->hashIP($ip);
    $fingerprint = $referral->createFingerprint($ip, $userAgent);
    
    // Tracking durchführen
    $result = $referral->trackClick(
        $userId,
        $refCode,
        $ipHash,
        $userAgent,
        $fingerprint,
        $referer
    );
    
    if ($result['success']) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Klick erfolgreich getrackt',
            'click_id' => $result['click_id'],
            'session_id' => $result['session_id']
        ]);
    } else {
        http_response_code(200); // Trotzdem 200, um Frontend nicht zu brechen
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Referral Click Tracking Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}
