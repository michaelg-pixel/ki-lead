<?php
/**
 * API: Belohnungsstufe speichern
 * POST /api_referral_rewards/save-reward-tier.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../config/database.php';

// Input validieren
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['tier_id']) || !isset($input['rewards_earned']) || !isset($input['current_referrals'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Fehlende Parameter: tier_id, rewards_earned, current_referrals erforderlich'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Lead ID aus Session oder Token
    session_start();
    $lead_id = $_SESSION['lead_id'] ?? null;
    
    if (!$lead_id && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        // Token-basierte Auth für externe Anfragen
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
    
    // Belohnungsstufe speichern
    $stmt = $db->prepare("
        INSERT INTO referral_reward_tiers 
        (lead_id, tier_id, rewards_earned, current_referrals, achieved_at) 
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            rewards_earned = VALUES(rewards_earned),
            current_referrals = VALUES(current_referrals),
            achieved_at = NOW()
    ");
    
    $stmt->execute([
        $lead_id,
        $input['tier_id'],
        $input['rewards_earned'],
        $input['current_referrals']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Belohnungsstufe gespeichert',
        'data' => [
            'tier_id' => $input['tier_id'],
            'rewards_earned' => $input['rewards_earned'],
            'current_referrals' => $input['current_referrals']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
