<?php
/**
 * API: Belohnungsstufe löschen
 * POST /api/rewards/delete.php
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

// Input validieren
$input = json_decode(file_get_contents('php://input'), true);

// Akzeptiere sowohl 'id' als auch 'reward_id'
$id = $input['reward_id'] ?? $input['id'] ?? null;

if (!$id) {
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
    
    // Prüfen ob Belohnung bereits vergeben wurde (optional)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM referral_claimed_rewards 
        WHERE reward_id = ?
    ");
    $stmt->execute([$id]);
    $claimed_count = $stmt->fetchColumn();
    
    if ($claimed_count > 0) {
        // Nur deaktivieren, nicht löschen
        $stmt = $pdo->prepare("
            UPDATE reward_definitions 
            SET is_active = 0, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Belohnungsstufe wurde deaktiviert (bereits vergeben)',
            'deactivated' => true
        ]);
    } else {
        // Kann sicher gelöscht werden
        $stmt = $pdo->prepare("
            DELETE FROM reward_definitions 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Belohnungsstufe gelöscht'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Belohnungsstufe nicht gefunden oder kein Zugriff'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Reward Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
