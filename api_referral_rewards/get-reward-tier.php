<?php
/**
 * API: Belohnungsstufe abrufen
 * GET /api_referral_rewards/get-reward-tier.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

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
    
    // Aktuelle Belohnungsstufe abrufen
    $stmt = $db->prepare("
        SELECT 
            rt.*,
            l.total_referrals,
            l.successful_referrals
        FROM referral_reward_tiers rt
        JOIN referral_leads l ON rt.lead_id = l.id
        WHERE rt.lead_id = ?
        ORDER BY rt.achieved_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$lead_id]);
    $tier = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tier) {
        // Keine Stufe vorhanden - Standard zurückgeben
        $stmt = $db->prepare("
            SELECT total_referrals, successful_referrals 
            FROM referral_leads 
            WHERE id = ?
        ");
        $stmt->execute([$lead_id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'tier_id' => 0,
                'rewards_earned' => 0,
                'current_referrals' => $lead['successful_referrals'] ?? 0,
                'total_referrals' => $lead['total_referrals'] ?? 0
            ]
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'tier_id' => (int)$tier['tier_id'],
            'rewards_earned' => (int)$tier['rewards_earned'],
            'current_referrals' => (int)$tier['current_referrals'],
            'total_referrals' => (int)$tier['total_referrals'],
            'successful_referrals' => (int)$tier['successful_referrals'],
            'achieved_at' => $tier['achieved_at']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
