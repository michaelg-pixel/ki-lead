<?php
/**
 * Template Get API
 * Lädt ein einzelnes Template für Bearbeitung
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur GET-Requests erlaubt']);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Template-ID fehlt');
    }
    
    $template_id = (int)$_GET['id'];
    
    // Template laden
    $stmt = $pdo->prepare("SELECT * FROM vendor_reward_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Template nicht gefunden']);
        exit;
    }
    
    // Prüfe Ownership
    if ($template['vendor_id'] != $customer_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'template' => $template
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>