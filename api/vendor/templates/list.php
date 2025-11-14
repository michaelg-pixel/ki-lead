<?php
/**
 * Template List API
 * Gibt alle Templates des Vendors zurück (Kostenlose Belohnungen)
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
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
    
    // Hole alle Templates des Vendors
    $stmt = $pdo->prepare("
        SELECT 
            id,
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
            reward_download_url,
            reward_instructions,
            reward_icon,
            reward_color,
            product_mockup_url,
            course_duration,
            original_product_link,
            suggested_tier_level,
            suggested_referrals_required,
            is_published,
            COALESCE(times_imported, 0) as times_imported,
            COALESCE(times_claimed, 0) as times_claimed,
            created_at,
            updated_at
        FROM vendor_reward_templates
        WHERE vendor_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$customer_id]);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Konvertiere Werte zu korrekten Typen
    foreach ($templates as &$template) {
        $template['times_imported'] = (int)$template['times_imported'];
        $template['times_claimed'] = (int)$template['times_claimed'];
        $template['is_published'] = (bool)$template['is_published'];
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (PDOException $e) {
    error_log('Template List Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Datenbankfehler',
        'details' => $e->getMessage()
    ]);
}
?>
