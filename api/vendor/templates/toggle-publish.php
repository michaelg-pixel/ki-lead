<?php
/**
 * Template Toggle Publish API
 * Veröffentlicht/Entveröffentlicht ein Template im Marktplatz
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('Template-ID fehlt');
    }
    
    $template_id = (int)$input['id'];
    $is_published = isset($input['is_published']) ? (bool)$input['is_published'] : false;
    
    // Prüfe Ownership
    $stmt = $pdo->prepare("SELECT vendor_id FROM vendor_reward_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Template nicht gefunden']);
        exit;
    }
    
    if ($template['vendor_id'] != $customer_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        exit;
    }
    
    // Update
    $stmt = $pdo->prepare("UPDATE vendor_reward_templates SET is_published = ? WHERE id = ?");
    $stmt->execute([$is_published ? 1 : 0, $template_id]);
    
    echo json_encode([
        'success' => true,
        'message' => $is_published ? 'Template veröffentlicht' : 'Template entveröffentlicht',
        'is_published' => $is_published
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>