<?php
/**
 * Template Delete API
 * Löscht ein Template
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
    
    // Prüfe Ownership
    $stmt = $pdo->prepare("
        SELECT vendor_id, template_name, times_imported 
        FROM vendor_reward_templates 
        WHERE id = ?
    ");
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
    
    // Warnung wenn bereits importiert
    $warning = null;
    if ($template['times_imported'] > 0) {
        $warning = "Dieses Template wurde bereits {$template['times_imported']}x importiert. Bereits importierte Instanzen bleiben bestehen.";
    }
    
    // Löschen (CASCADE löscht automatisch imports und claims)
    $stmt = $pdo->prepare("DELETE FROM vendor_reward_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Template erfolgreich gelöscht',
        'warning' => $warning
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>