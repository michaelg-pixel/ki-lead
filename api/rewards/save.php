<?php
/**
 * API: Belohnungsstufe erstellen/aktualisieren
 * POST /api/rewards/save.php
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

$required = ['tier_level', 'tier_name', 'required_referrals', 'reward_type', 'reward_title'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => "Feld '$field' ist erforderlich"
        ]);
        exit;
    }
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $id = $input['id'] ?? null;
    
    // Validierung
    if ($input['tier_level'] < 1 || $input['tier_level'] > 50) {
        throw new Exception('Tier-Level muss zwischen 1 und 50 liegen');
    }
    
    if ($input['required_referrals'] < 1) {
        throw new Exception('Mindestens 1 Empfehlung erforderlich');
    }
    
    if ($id) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE reward_definitions SET
                tier_level = ?,
                tier_name = ?,
                tier_description = ?,
                required_referrals = ?,
                reward_type = ?,
                reward_title = ?,
                reward_description = ?,
                reward_value = ?,
                reward_download_url = ?,
                reward_access_code = ?,
                reward_instructions = ?,
                reward_icon = ?,
                reward_color = ?,
                is_active = ?,
                is_featured = ?,
                auto_deliver = ?,
                notification_subject = ?,
                notification_body = ?,
                sort_order = ?,
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $result = $stmt->execute([
            $input['tier_level'],
            $input['tier_name'],
            $input['tier_description'] ?? null,
            $input['required_referrals'],
            $input['reward_type'],
            $input['reward_title'],
            $input['reward_description'] ?? null,
            $input['reward_value'] ?? null,
            $input['reward_download_url'] ?? null,
            $input['reward_access_code'] ?? null,
            $input['reward_instructions'] ?? null,
            $input['reward_icon'] ?? 'fa-gift',
            $input['reward_color'] ?? '#667eea',
            $input['is_active'] ?? true,
            $input['is_featured'] ?? false,
            $input['auto_deliver'] ?? false,
            $input['notification_subject'] ?? null,
            $input['notification_body'] ?? null,
            $input['sort_order'] ?? 0,
            $id,
            $user_id
        ]);
        
        $message = 'Belohnungsstufe aktualisiert';
        
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO reward_definitions (
                user_id, tier_level, tier_name, tier_description,
                required_referrals, reward_type, reward_title, reward_description,
                reward_value, reward_download_url, reward_access_code,
                reward_instructions, reward_icon, reward_color,
                is_active, is_featured, auto_deliver,
                notification_subject, notification_body, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $input['tier_level'],
            $input['tier_name'],
            $input['tier_description'] ?? null,
            $input['required_referrals'],
            $input['reward_type'],
            $input['reward_title'],
            $input['reward_description'] ?? null,
            $input['reward_value'] ?? null,
            $input['reward_download_url'] ?? null,
            $input['reward_access_code'] ?? null,
            $input['reward_instructions'] ?? null,
            $input['reward_icon'] ?? 'fa-gift',
            $input['reward_color'] ?? '#667eea',
            $input['is_active'] ?? true,
            $input['is_featured'] ?? false,
            $input['auto_deliver'] ?? false,
            $input['notification_subject'] ?? null,
            $input['notification_body'] ?? null,
            $input['sort_order'] ?? 0
        ]);
        
        $id = $pdo->lastInsertId();
        $message = 'Belohnungsstufe erstellt';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => ['id' => $id]
    ]);
    
} catch (PDOException $e) {
    error_log("Reward Save Error: " . $e->getMessage());
    
    // Duplicate key error
    if ($e->getCode() == 23000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Diese Tier-Level existiert bereits für diesen User'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Datenbankfehler'
        ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
