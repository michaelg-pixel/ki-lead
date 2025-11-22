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

// Debug logging
error_log("Reward Save Input: " . print_r($input, true));

$required = ['freebie_id', 'reward_title', 'required_referrals'];
foreach ($required as $field) {
    if (!isset($input[$field]) || $input[$field] === '') {
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
    $reward_id = $input['reward_id'] ?? null;
    $freebie_id = (int)$input['freebie_id'];
    
    // Validierung
    if ($input['required_referrals'] < 0) {
        throw new Exception('Anzahl Empfehlungen muss mindestens 0 sein');
    }
    
    // Falls Freebie-ID angegeben, prüfen ob User Zugriff hat
    $stmt = $pdo->prepare("
        SELECT cf.id
        FROM customer_freebies cf
        WHERE cf.id = ?
        AND cf.customer_id = ?
    ");
    $stmt->execute([$freebie_id, $user_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Kein Zugriff auf dieses Freebie');
    }
    
    // Delivery Type ermitteln
    $delivery_type = $input['reward_delivery_type'] ?? 'manual';
    
    if ($reward_id) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE reward_definitions SET
                freebie_id = ?,
                reward_title = ?,
                reward_description = ?,
                required_referrals = ?,
                reward_icon = ?,
                reward_color = ?,
                reward_delivery_type = ?,
                email_subject = ?,
                email_body = ?,
                reward_download_url = ?,
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $result = $stmt->execute([
            $freebie_id,
            $input['reward_title'],
            $input['reward_description'] ?? '',
            $input['required_referrals'],
            $input['reward_icon'] ?? '🎁',
            $input['reward_color'] ?? '#667eea',
            $delivery_type,
            $input['email_subject'] ?? '',
            $input['email_body'] ?? '',
            $input['reward_download_url'] ?? '',
            $reward_id,
            $user_id
        ]);
        
        if (!$result || $stmt->rowCount() === 0) {
            throw new Exception('Belohnung nicht gefunden oder keine Änderung');
        }
        
        $message = 'Belohnungsstufe aktualisiert';
        $id = $reward_id;
        
    } else {
        // INSERT - Tier Level automatisch ermitteln
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(tier_level), 0) + 1 as next_level
            FROM reward_definitions
            WHERE user_id = ? AND freebie_id = ?
        ");
        $stmt->execute([$user_id, $freebie_id]);
        $next_level = $stmt->fetch(PDO::FETCH_ASSOC)['next_level'];
        
        $stmt = $pdo->prepare("
            INSERT INTO reward_definitions (
                user_id, freebie_id, tier_level, tier_name,
                reward_title, reward_description,
                required_referrals, reward_type,
                reward_icon, reward_color,
                reward_delivery_type,
                email_subject, email_body,
                reward_download_url,
                is_active, auto_deliver,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, NOW(), NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $freebie_id,
            $next_level,
            $input['reward_title'], // tier_name = reward_title
            $input['reward_title'],
            $input['reward_description'] ?? '',
            $input['required_referrals'],
            'digital', // reward_type default
            $input['reward_icon'] ?? '🎁',
            $input['reward_color'] ?? '#667eea',
            $delivery_type,
            $input['email_subject'] ?? '',
            $input['email_body'] ?? '',
            $input['reward_download_url'] ?? ''
        ]);
        
        $id = $pdo->lastInsertId();
        $message = 'Belohnungsstufe erstellt';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'reward_id' => $id
    ]);
    
} catch (PDOException $e) {
    error_log("Reward Save Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
