<?php
/**
 * Vendor Overview Statistics API
 * Liefert Gesamtstatistiken für alle Templates eines Vendors
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

try {
    $pdo = getDBConnection();
    
    // Gesamtstatistiken
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_templates,
            COUNT(CASE WHEN is_published = 1 THEN 1 END) as published_templates,
            SUM(times_imported) as total_imports,
            SUM(times_claimed) as total_claims,
            SUM(total_revenue) as total_revenue
        FROM vendor_reward_templates
        WHERE vendor_id = ?
    ");
    $stmt->execute([$vendor_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Konvertiere NULL zu 0
    $stats['total_templates'] = (int)($stats['total_templates'] ?? 0);
    $stats['published_templates'] = (int)($stats['published_templates'] ?? 0);
    $stats['total_imports'] = (int)($stats['total_imports'] ?? 0);
    $stats['total_claims'] = (int)($stats['total_claims'] ?? 0);
    $stats['total_revenue'] = (float)($stats['total_revenue'] ?? 0);
    
    // Timeline-Daten (letzte 30 Tage)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(date) as date,
            SUM(imports) as imports,
            SUM(claims) as claims
        FROM (
            SELECT 
                import_date as date,
                1 as imports,
                0 as claims
            FROM reward_template_imports rti
            JOIN vendor_reward_templates vrt ON rti.template_id = vrt.id
            WHERE vrt.vendor_id = ?
            AND rti.import_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            
            UNION ALL
            
            SELECT 
                claimed_at as date,
                0 as imports,
                1 as claims
            FROM reward_template_claims rtc
            JOIN vendor_reward_templates vrt ON rtc.template_id = vrt.id
            WHERE vrt.vendor_id = ?
            AND rtc.claimed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ) combined
        GROUP BY DATE(date)
        ORDER BY date ASC
    ");
    $stmt->execute([$vendor_id, $vendor_id]);
    $timeline_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fülle Lücken in Timeline auf (für Chart.js)
    $timeline = [];
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
    
    for ($date = $start_date; $date <= $end_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
        $found = false;
        foreach ($timeline_data as $data) {
            if ($data['date'] === $date) {
                $timeline[] = [
                    'date' => date('d.m', strtotime($date)),
                    'imports' => (int)$data['imports'],
                    'claims' => (int)$data['claims']
                ];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $timeline[] = [
                'date' => date('d.m', strtotime($date)),
                'imports' => 0,
                'claims' => 0
            ];
        }
    }
    
    // Top Templates
    $stmt = $pdo->prepare("
        SELECT 
            id,
            template_name,
            category,
            niche,
            times_imported,
            times_claimed,
            total_revenue,
            is_published,
            is_featured
        FROM vendor_reward_templates
        WHERE vendor_id = ?
        ORDER BY times_imported DESC, times_claimed DESC
        LIMIT 10
    ");
    $stmt->execute([$vendor_id]);
    $top_templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent Imports (letzte 10)
    $stmt = $pdo->prepare("
        SELECT 
            rti.import_date,
            rti.purchase_price,
            vrt.template_name,
            u.name as customer_name
        FROM reward_template_imports rti
        JOIN vendor_reward_templates vrt ON rti.template_id = vrt.id
        JOIN users u ON rti.customer_id = u.id
        WHERE vrt.vendor_id = ?
        ORDER BY rti.import_date DESC
        LIMIT 10
    ");
    $stmt->execute([$vendor_id]);
    $recent_imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent Claims (letzte 10)
    $stmt = $pdo->prepare("
        SELECT 
            rtc.claimed_at,
            rtc.claim_status,
            vrt.template_name,
            u.name as customer_name
        FROM reward_template_claims rtc
        JOIN vendor_reward_templates vrt ON rtc.template_id = vrt.id
        JOIN users u ON rtc.customer_id = u.id
        WHERE vrt.vendor_id = ?
        ORDER BY rtc.claimed_at DESC
        LIMIT 10
    ");
    $stmt->execute([$vendor_id]);
    $recent_claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timeline' => $timeline,
        'top_templates' => $top_templates,
        'recent_imports' => $recent_imports,
        'recent_claims' => $recent_claims
    ]);
    
} catch (PDOException $e) {
    error_log('Vendor Overview Stats Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>