<?php
/**
 * Referral Conversion Webhook
 * Wird aufgerufen wenn ein empfohlener Lead konvertiert
 * Prüft automatisch ob Belohnungen erreicht wurden
 * 
 * Verwendung:
 * POST /webhook/referral_conversion.php
 * {
 *   "email": "lead@example.com",
 *   "freebie_id": 123,
 *   "status": "converted"
 * }
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../api/reward_delivery.php';

// Logging
function logConversion($data, $type = 'info') {
    $logFile = __DIR__ . '/conversion-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen
$rawInput = file_get_contents('php://input');
logConversion(['raw_input' => $rawInput], 'received');

$webhookData = json_decode($rawInput, true);

if (!$webhookData) {
    logConversion(['error' => 'Invalid JSON'], 'error');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $email = $webhookData['email'] ?? '';
    $freebie_id = $webhookData['freebie_id'] ?? null;
    $status = $webhookData['status'] ?? 'converted';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Lead finden
    $stmt = $pdo->prepare("
        SELECT id, user_id, referrer_id 
        FROM lead_users 
        WHERE email = ? 
        " . ($freebie_id ? "AND freebie_id = ?" : "") . "
        LIMIT 1
    ");
    
    $params = $freebie_id ? [$email, $freebie_id] : [$email];
    $stmt->execute($params);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        throw new Exception('Lead not found');
    }
    
    // Conversion-Status aktualisieren
    $stmt = $pdo->prepare("
        UPDATE lead_referrals 
        SET status = ?, converted_at = NOW()
        WHERE referred_email = ?
        " . ($freebie_id ? "AND freebie_id = ?" : "")
    );
    $stmt->execute($freebie_id ? [$status, $email, $freebie_id] : [$status, $email]);
    
    logConversion([
        'message' => 'Lead conversion recorded',
        'lead_id' => $lead['id'],
        'email' => $email,
        'status' => $status,
        'freebie_id' => $freebie_id
    ], 'success');
    
    // Wenn Lead durch Empfehlung kam: Belohnungen für Referrer prüfen
    if ($lead['referrer_id']) {
        logConversion([
            'message' => 'Checking rewards for referrer',
            'referrer_id' => $lead['referrer_id']
        ], 'info');
        
        // Belohnungen automatisch ausliefern
        $delivery_result = checkAndDeliverRewards($pdo, $lead['referrer_id']);
        
        logConversion([
            'message' => 'Reward check completed',
            'referrer_id' => $lead['referrer_id'],
            'result' => $delivery_result
        ], 'reward_check');
        
        // Response mit Belohnungs-Info
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Conversion recorded and rewards checked',
            'lead_id' => $lead['id'],
            'referrer_id' => $lead['referrer_id'],
            'rewards_delivered' => $delivery_result['rewards_delivered'] ?? 0,
            'reward_details' => $delivery_result['rewards'] ?? []
        ]);
    } else {
        // Kein Referrer, normale Response
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Conversion recorded (no referrer)',
            'lead_id' => $lead['id']
        ]);
    }
    
} catch (Exception $e) {
    logConversion(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'error');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
