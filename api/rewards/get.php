<?php
/**
 * API: Einzelne Belohnungsstufe abrufen
 * GET /api/rewards/get.php?id=123
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Auth prüfen
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'ID erforderlich'
    ]);
    exit;
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $id = $_GET['id'];
    
    $stmt = $pdo->prepare("
        SELECT 
            id,
            freebie_id,
            reward_title,
            reward_description,
            required_referrals,
            reward_icon,
            reward_color,
            reward_delivery_type,
            email_subject,
            email_body,
            reward_download_url
        FROM reward_definitions 
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$id, $user_id]);
    $reward = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reward) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Belohnungsstufe nicht gefunden'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'reward' => $reward  // Changed from 'data' to 'reward'
    ]);
    
} catch (Exception $e) {
    error_log("Reward Get Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
