<?php
/**
 * API: Toggle Referral Program
 * Aktiviere/Deaktiviere Empfehlungsprogramm
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = $input['enabled'] ?? null;
    
    if ($enabled === null) {
        throw new Exception('Parameter "enabled" fehlt');
    }
    
    $stmt = $db->prepare("
        UPDATE customers 
        SET referral_enabled = ? 
        WHERE id = ?
    ");
    $stmt->execute([$enabled ? 1 : 0, $userId]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Empfehlungsprogramm aktiviert' : 'Empfehlungsprogramm deaktiviert',
        'enabled' => (bool)$enabled
    ]);
    
} catch (Exception $e) {
    error_log("Referral Toggle Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}
