<?php
/**
 * Template Update API
 * Aktualisiert ein bestehendes Template
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
    
    // Validierung
    $errors = [];
    
    if (isset($input['template_name']) && strlen($input['template_name']) < 3) {
        $errors[] = 'Template-Name zu kurz (min. 3 Zeichen)';
    }
    
    $valid_categories = ['ebook', 'consultation', 'discount', 'course', 'voucher', 'software', 'template', 'other'];
    if (isset($input['category']) && !empty($input['category']) && !in_array($input['category'], $valid_categories)) {
        $errors[] = 'Ungültige Kategorie';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    // Build UPDATE query dynamisch
    $updates = [];
    $params = [];
    
    $allowed_fields = [
        'template_name', 'template_description', 'category', 'niche',
        'reward_type', 'reward_title', 'reward_description', 'reward_value',
        'reward_delivery_type', 'reward_instructions', 'reward_access_code_template',
        'reward_download_url', 'reward_icon', 'reward_color', 'reward_badge_image',
        'preview_image', 'suggested_tier_level', 'suggested_referrals_required',
        'marketplace_price', 'digistore_product_id', 'is_published'
    ];
    
    foreach ($allowed_fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    if (empty($updates)) {
        throw new Exception('Keine Änderungen zum Speichern');
    }
    
    $params[] = $template_id;
    
    $sql = "UPDATE vendor_reward_templates SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Template erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>