<?php
/**
 * Marketplace List API
 * Listet alle veröffentlichten Vendor-Templates auf
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

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

$user_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Filter-Parameter
    $category = $_GET['category'] ?? null;
    $niche = $_GET['niche'] ?? null;
    $search = $_GET['search'] ?? null;
    
    // Query bauen
    $sql = "
        SELECT 
            vrt.id,
            vrt.vendor_id,
            vrt.template_name,
            vrt.template_description,
            vrt.category,
            vrt.niche,
            vrt.reward_type,
            vrt.reward_title,
            vrt.reward_description,
            vrt.reward_value,
            vrt.reward_icon,
            vrt.reward_color,
            vrt.suggested_tier_level,
            vrt.suggested_referrals_required,
            vrt.times_imported,
            vrt.times_claimed,
            vrt.preview_image,
            vrt.product_mockup_url,
            vrt.original_product_link,
            vrt.course_duration,
            vrt.is_featured,
            u.vendor_company_name as vendor_name,
            u.vendor_website,
            u.vendor_description as vendor_description,
            (SELECT COUNT(*) FROM reward_template_imports 
             WHERE template_id = vrt.id AND customer_id = ?) as is_imported_by_me
        FROM vendor_reward_templates vrt
        JOIN users u ON vrt.vendor_id = u.id
        WHERE vrt.is_published = 1
    ";
    
    $params = [$user_id];
    
    // Filter anwenden
    if ($category) {
        $sql .= " AND vrt.category = ?";
        $params[] = $category;
    }
    
    if ($niche) {
        $sql .= " AND vrt.niche = ?";
        $params[] = $niche;
    }
    
    if ($search) {
        $sql .= " AND (vrt.template_name LIKE ? OR vrt.template_description LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Sortierung: Featured zuerst, dann nach Imports
    $sql .= " ORDER BY vrt.is_featured DESC, vrt.times_imported DESC, vrt.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Konvertiere is_imported_by_me zu boolean
    foreach ($templates as &$template) {
        $template['is_imported_by_me'] = (bool)$template['is_imported_by_me'];
    }
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'total' => count($templates)
    ]);
    
} catch (PDOException $e) {
    error_log('Marketplace List Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>