<?php
/**
 * API: Track Referral Conversion
 * Wird von thankyou.php aufgerufen wenn ref-Parameter vorhanden
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
    $customerId = $input['customer_id'] ?? $_GET['customer_id'] ?? null;
    $source = $input['source'] ?? 'thankyou';
    
    if (!$refCode) {
        throw new Exception('Referral-Code fehlt');
    }
    
    // Validiere Referral-Code
    if (!$referral->validateRefCode($refCode)) {
        throw new Exception('Ungültiger oder inaktiver Referral-Code');
    }
    
    // Hole Customer-ID wenn nicht übergeben
    if (!$customerId) {
        $customerId = $referral->getCustomerIdFromRefCode($refCode);
        if (!$customerId) {
            throw new Exception('Customer nicht gefunden');
        }
    }
    
    // Hole IP und UserAgent
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // DSGVO-konform hashen
    $ipHash = $referral->hashIP($ip);
    $fingerprint = $referral->createFingerprint($ip, $userAgent);
    
    // Tracking durchführen
    $result = $referral->trackConversion(
        $customerId,
        $refCode,
        $ipHash,
        $userAgent,
        $fingerprint,
        $source
    );
    
    if ($result['success']) {
        $response = [
            'success' => true,
            'message' => 'Conversion erfolgreich getrackt',
            'conversion_id' => $result['conversion_id']
        ];
        
        // Warnung bei verdächtiger Conversion
        if ($result['suspicious']) {
            $response['warning'] = 'Conversion als verdächtig markiert (zu schnell)';
            $response['time_to_convert'] = $result['time_to_convert'];
        }
        
        http_response_code(200);
        echo json_encode($response);
    } else {
        http_response_code(200); // Trotzdem 200, um Frontend nicht zu brechen
        echo json_encode([
            'success' => false,
            'error' => $result['error'],
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Referral Conversion Tracking Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}
