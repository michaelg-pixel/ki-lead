<?php
/**
 * Template Details Statistics API
 * Liefert detaillierte Statistiken für ein einzelnes Template
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

$vendor_id = $_SESSION['user_id'];
$template_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$template_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Template-ID fehlt']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Prüfe Ownership
    $stmt = $pdo->prepare("SELECT vendor_id FROM vendor_reward_templates WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template || $template['vendor_id'] != $vendor_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Keine Berechtigung']);
        exit;
    }
    
    // Template-Grunddaten
    $stmt = $pdo->prepare("
        SELECT 
            vrt.*,
            u.name as vendor_name,
            u.vendor_company_name
        FROM vendor_reward_templates vrt
        JOIN users u ON vrt.vendor_id = u.id
        WHERE vrt.id = ?
    ");
    $stmt->execute([$template_id]);
    $template_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Import-Statistiken
    $stmt = $pdo->prepare("
        SELECT 
            rti.*,
            u.name as customer_name,
            u.email as customer_email,
            rd.tier_name,
            rd.tier_level,
            (SELECT COUNT(*) FROM referral_claimed_rewards rcr 
             WHERE rcr.reward_id = rti.reward_definition_id) as claims_count
        FROM reward_template_imports rti
        JOIN users u ON rti.customer_id = u.id
        LEFT JOIN reward_definitions rd ON rti.reward_definition_id = rd.id
        WHERE rti.template_id = ?
        ORDER BY rti.import_date DESC
        LIMIT 50
    ");
    $stmt->execute([$template_id]);
    $imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Claim-Statistiken
    $stmt = $pdo->prepare("
        SELECT 
            rtc.*,
            u.name as customer_name,
            rd.tier_name,
            rd.reward_title
        FROM reward_template_claims rtc
        JOIN users u ON rtc.customer_id = u.id
        LEFT JOIN reward_definitions rd ON rtc.reward_definition_id = rd.id
        WHERE rtc.template_id = ?
        ORDER BY rtc.claimed_at DESC
        LIMIT 50
    ");
    $stmt->execute([$template_id]);
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Zeitreihen-Daten (Imports über Zeit - letzte 30 Tage)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(import_date) as date,
            COUNT(*) as count
        FROM reward_template_imports
        WHERE template_id = ?
        AND import_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(import_date)
        ORDER BY date ASC
    ");
    $stmt->execute([$template_id]);
    $imports_over_time = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Claims über Zeit (letzte 30 Tage)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(claimed_at) as date,
            COUNT(*) as count
        FROM reward_template_claims
        WHERE template_id = ?
        AND claimed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(claimed_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$template_id]);
    $claims_over_time = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top-Customer (meiste Claims)
    $stmt = $pdo->prepare("
        SELECT 
            u.name as customer_name,
            u.email as customer_email,
            COUNT(*) as total_claims
        FROM reward_template_claims rtc
        JOIN users u ON rtc.customer_id = u.id
        WHERE rtc.template_id = ?
        GROUP BY rtc.customer_id
        ORDER BY total_claims DESC
        LIMIT 10
    ");
    $stmt->execute([$template_id]);
    $top_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Aggregierte Stats
    $stats = [
        'total_imports' => (int)$template_data['times_imported'],
        'total_claims' => (int)$template_data['times_claimed'],
        'total_revenue' => (float)$template_data['total_revenue'],
        'unique_customers' => count($imports),
        'avg_claims_per_customer' => count($imports) > 0 ? round(count($claims) / count($imports), 2) : 0,
        'conversion_rate' => count($imports) > 0 ? round((count($claims) / count($imports)) * 100, 2) : 0
    ];
    
    echo json_encode([
        'success' => true,
        'template' => $template_data,
        'stats' => $stats,
        'imports' => $imports,
        'claims' => $claims,
        'imports_over_time' => $imports_over_time,
        'claims_over_time' => $claims_over_time,
        'top_customers' => $top_customers
    ]);
    
} catch (PDOException $e) {
    error_log('Template Details Stats Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>