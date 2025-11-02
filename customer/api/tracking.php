<?php
/**
 * Tracking API - Erfasst echte Nutzeraktivitäten
 * KORRIGIERT: Verwendet user_id statt customer_id
 */

header('Content-Type: application/json');
session_start();

// CORS Headers für AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JSON Input lesen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$pdo = getDBConnection();

// User ID aus Session (verwendet user_id wie die anderen Tabellen)
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Tracking-Typ
$type = $input['type'];
$data = $input['data'] ?? [];

try {
    switch ($type) {
        case 'page_view':
            // Seitenaufruf tracken
            $page = $data['page'] ?? 'unknown';
            $referrer = $data['referrer'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO customer_tracking 
                (user_id, type, page, referrer, user_agent, ip_address, created_at) 
                VALUES (?, 'page_view', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $page, $referrer, $user_agent, $ip_address]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Page view tracked'
            ]);
            break;
            
        case 'click':
            // Klick tracken
            $element = $data['element'] ?? 'unknown';
            $target = $data['target'] ?? '';
            $page = $data['page'] ?? 'unknown';
            
            $stmt = $pdo->prepare("
                INSERT INTO customer_tracking 
                (user_id, type, page, element, target, created_at) 
                VALUES (?, 'click', ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $page, $element, $target]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Click tracked'
            ]);
            break;
            
        case 'event':
            // Custom Event tracken
            $event_name = $data['event_name'] ?? 'custom';
            $event_data = json_encode($data['event_data'] ?? []);
            $page = $data['page'] ?? 'unknown';
            
            $stmt = $pdo->prepare("
                INSERT INTO customer_tracking 
                (user_id, type, page, event_name, event_data, created_at) 
                VALUES (?, 'event', ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $page, $event_name, $event_data]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event tracked'
            ]);
            break;
            
        case 'time_spent':
            // Verbrachte Zeit tracken
            $page = $data['page'] ?? 'unknown';
            $duration = intval($data['duration'] ?? 0);
            
            $stmt = $pdo->prepare("
                INSERT INTO customer_tracking 
                (user_id, type, page, duration, created_at) 
                VALUES (?, 'time_spent', ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $page, $duration]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Time spent tracked'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid tracking type']);
            break;
    }
} catch (PDOException $e) {
    error_log("Tracking Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
