<?php
/**
 * Template Create API
 * Erstellt ein neues Template
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // Get PDO connection
    $pdo = getDBConnection();
    
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
    
    if (!$input) {
        throw new Exception('Ungültige JSON-Daten');
    }
    
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
    
    // Helper function für sichere Wert-Konvertierung
    function getIntValue($input, $key, $default = 0) {
        if (!isset($input[$key]) || $input[$key] === '' || $input[$key] === null) {
            return $default;
        }
        return (int)$input[$key];
    }
    
    function getFloatValue($input, $key, $default = 0.00) {
        if (!isset($input[$key]) || $input[$key] === '' || $input[$key] === null) {
            return $default;
        }
        return (float)$input[$key];
    }
    
    function getBoolValue($input, $key, $default = false) {
        if (!isset($input[$key]) || $input[$key] === '' || $input[$key] === null) {
            return $default ? 1 : 0;
        }
        return $input[$key] ? 1 : 0;
    }
    
    function getStringValue($input, $key, $default = null) {
        if (!isset($input[$key]) || $input[$key] === '') {
            return $default;
        }
        return $input[$key];
    }
    
    // Template erstellen - OHNE digistore_product_id
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
            product_mockup_url,
            course_duration,
            original_product_link,
            suggested_tier_level,
            suggested_referrals_required,
            marketplace_price,
            is_published
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $result = $stmt->execute([
        $customer_id,
        $input['template_name'],
        getStringValue($input, 'template_description'),
        getStringValue($input, 'category'),
        getStringValue($input, 'niche'),
        $input['reward_type'],
        $input['reward_title'],
        getStringValue($input, 'reward_description'),
        getStringValue($input, 'reward_value'),
        getStringValue($input, 'reward_delivery_type', 'manual'),
        getStringValue($input, 'reward_instructions'),
        getStringValue($input, 'reward_access_code_template'),
        getStringValue($input, 'reward_download_url'),
        getStringValue($input, 'reward_icon', 'fa-gift'),
        getStringValue($input, 'reward_color', '#667eea'),
        getStringValue($input, 'product_mockup_url'),
        getStringValue($input, 'course_duration'),
        getStringValue($input, 'original_product_link'),
        getIntValue($input, 'suggested_tier_level', 1),
        getIntValue($input, 'suggested_referrals_required', 3),
        getFloatValue($input, 'marketplace_price', 0.00),
        getBoolValue($input, 'is_published', false)
    ]);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        throw new Exception('Fehler beim Erstellen des Templates: ' . $errorInfo[2]);
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
    echo json_encode([
        'success' => false, 
        'error' => 'Datenbankfehler: ' . $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    error_log('Template Create Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>