<?php
/**
 * Marketplace List API
 * Lädt alle veröffentlichten Templates für den Marktplatz
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
    $pdo = getDBConnection();
    
    // Filter-Parameter
    $category = $_GET['category'] ?? '';
    $niche = $_GET['niche'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // SQL Query
    $sql = "
        SELECT 
            vrt.id,
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
            vrt.preview_image,
            vrt.marketplace_price,
            vrt.times_imported,
            vrt.suggested_tier_level,
            vrt.suggested_referrals_required,
            vrt.created_at,
            u.name as vendor_name,
            u.vendor_company_name,
            CASE WHEN rti.id IS NOT NULL THEN 1 ELSE 0 END as is_imported_by_me
        FROM vendor_reward_templates vrt
        JOIN users u ON vrt.vendor_id = u.id
        LEFT JOIN reward_template_imports rti ON rti.template_id = vrt.id AND rti.customer_id = ?
        WHERE vrt.is_published = 1
    ";
    
    $params = [$customer_id];
    
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
        $sql .= " AND (vrt.template_name LIKE ? OR vrt.template_description LIKE ? OR vrt.reward_title LIKE ?)";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Sortierung: Featured zuerst, dann nach Imports
    $sql .= " ORDER BY vrt.is_featured DESC, vrt.times_imported DESC, vrt.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kategorien für Filter laden
    $stmt = $pdo->query("
        SELECT DISTINCT category 
        FROM vendor_reward_templates 
        WHERE is_published = 1 AND category IS NOT NULL
        ORDER BY category
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Nischen für Filter laden
    $stmt = $pdo->query("
        SELECT DISTINCT niche 
        FROM vendor_reward_templates 
        WHERE is_published = 1 AND niche IS NOT NULL
        ORDER BY niche
    ");
    $niches = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'templates' => $templates,
        'total' => count($templates),
        'filters' => [
            'categories' => $categories,
            'niches' => $niches
        ]
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