<?php
/**
 * Template Create API
 * Erstellt ein neues Template
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
    // Prüfe ob User Vendor ist
    $stmt = $pdo->prepare("SELECT is_vendor FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_vendor']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Kein Vendor']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validierung
    $errors = [];
    
    if (empty($input['template_name']) || strlen($input['template_name']) < 3) {
        $errors[] = 'Template-Name zu kurz (min. 3 Zeichen)';
    }
    
    if (empty($input['reward_type'])) {
        $errors[] = 'Belohnungs-Typ ist erforderlich';
    }
    
    if (empty($input['reward_title'])) {
        $errors[] = 'Belohnungs-Titel ist erforderlich';
    }
    
    $valid_categories = ['ebook', 'consultation', 'discount', 'course', 'voucher', 'software', 'template', 'other'];
    if (!empty($input['category']) && !in_array($input['category'], $valid_categories)) {
        $errors[] = 'Ungültige Kategorie';
    }
    
    $valid_delivery_types = ['automatic', 'manual', 'code', 'url'];
    if (!empty($input['reward_delivery_type']) && !in_array($input['reward_delivery_type'], $valid_delivery_types)) {
        $errors[] = 'Ungültiger Lieferungs-Typ';
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    
    // Template erstellen
    $stmt = $pdo->prepare("
        INSERT INTO vendor_reward_templates (
            vendor_id,
            template_name,
            template_description,
            category,
            niche,
            reward_type,
            reward_title,
            reward_description,
            reward_value,
            reward_delivery_type,
            reward_instructions,
            reward_access_code_template,
            reward_download_url,
            reward_icon,
            reward_color,
            reward_badge_image,
            preview_image,
            suggested_tier_level,
            suggested_referrals_required,
            marketplace_price,
            digistore_product_id,
            is_published
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $result = $stmt->execute([
        $customer_id,
        $input['template_name'],
        $input['template_description'] ?? null,
        $input['category'] ?? null,
        $input['niche'] ?? null,
        $input['reward_type'],
        $input['reward_title'],
        $input['reward_description'] ?? null,
        $input['reward_value'] ?? null,
        $input['reward_delivery_type'] ?? 'manual',
        $input['reward_instructions'] ?? null,
        $input['reward_access_code_template'] ?? null,
        $input['reward_download_url'] ?? null,
        $input['reward_icon'] ?? 'fa-gift',
        $input['reward_color'] ?? '#667eea',
        $input['reward_badge_image'] ?? null,
        $input['preview_image'] ?? null,
        $input['suggested_tier_level'] ?? 1,
        $input['suggested_referrals_required'] ?? 3,
        $input['marketplace_price'] ?? 0.00,
        $input['digistore_product_id'] ?? null,
        $input['is_published'] ?? false
    ]);
    
    if (!$result) {
        throw new Exception('Fehler beim Erstellen des Templates');
    }
    
    $template_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Template erfolgreich erstellt',
        'template_id' => $template_id
    ]);
    
} catch (PDOException $e) {
    error_log('Template Create Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>