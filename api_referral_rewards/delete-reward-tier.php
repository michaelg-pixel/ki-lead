<?php
/**
 * API: Belohnungsstufe löschen
 * DELETE /api_referral_rewards/delete-reward-tier.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE');

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
    
    // Optional: Bestimmte Stufe löschen
    $input = json_decode(file_get_contents('php://input'), true);
    $tier_id = $input['tier_id'] ?? null;
    
    if ($tier_id !== null) {
        // Bestimmte Stufe löschen
        $stmt = $db->prepare("
            DELETE FROM referral_reward_tiers 
            WHERE lead_id = ? AND tier_id = ?
        ");
        $stmt->execute([$lead_id, $tier_id]);
    } else {
        // Alle Stufen löschen
        $stmt = $db->prepare("
            DELETE FROM referral_reward_tiers 
            WHERE lead_id = ?
        ");
        $stmt->execute([$lead_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Belohnungsstufe(n) gelöscht',
        'deleted_rows' => $stmt->rowCount()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
