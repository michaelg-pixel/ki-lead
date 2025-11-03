<?php
/**
 * API: Belohnung einlösen
 * POST /api_referral_rewards/claim-reward.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['reward_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'reward_id erforderlich'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Lead ID aus Session oder Token
    session_start();
    $lead_id = $_SESSION['lead_id'] ?? null;
    
    if (!$lead_id && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        $stmt = $db->prepare("SELECT id FROM referral_leads WHERE api_token = ?");
        $stmt->execute([$token]);
        $lead_id = $stmt->fetchColumn();
    }
    
    if (!$lead_id) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Nicht authentifiziert'
        ]);
        exit;
    }
    
    // Prüfen ob Belohnung bereits eingelöst wurde
    $stmt = $db->prepare("
        SELECT id FROM referral_claimed_rewards 
        WHERE lead_id = ? AND reward_id = ?
    ");
    $stmt->execute([$lead_id, $input['reward_id']]);
    
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Belohnung bereits eingelöst'
        ]);
        exit;
    }
    
    // Prüfen ob Lead genug Empfehlungen hat
    $stmt = $db->prepare("
        SELECT successful_referrals 
        FROM referral_leads 
        WHERE id = ?
    ");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Belohnungs-Anforderungen (Beispiel)
    $reward_requirements = [
        1 => 3,   // Belohnung 1: 3 Empfehlungen
        2 => 5,   // Belohnung 2: 5 Empfehlungen
        3 => 10,  // Belohnung 3: 10 Empfehlungen
        4 => 20   // Belohnung 4: 20 Empfehlungen
    ];
    
    $required = $reward_requirements[$input['reward_id']] ?? 0;
    
    if ($lead['successful_referrals'] < $required) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Nicht genug Empfehlungen. Benötigt: {$required}, Vorhanden: {$lead['successful_referrals']}"
        ]);
        exit;
    }
    
    // Belohnung einlösen
    $stmt = $db->prepare("
        INSERT INTO referral_claimed_rewards 
        (lead_id, reward_id, claimed_at) 
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$lead_id, $input['reward_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Belohnung erfolgreich eingelöst',
        'data' => [
            'reward_id' => $input['reward_id'],
            'claimed_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
