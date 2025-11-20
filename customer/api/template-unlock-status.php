<?php
/**
 * Template Unlock Status API - Funktioniert auch für neue Kunden ohne Produktkäufe
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

// Für Tests: Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nicht in JSON ausgeben

try {
    require_once '../../config/database.php';
    
    // Session Check
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Nicht eingeloggt'
        ]);
        exit;
    }
    
    $customer_id = $_SESSION['user_id'];
    $pdo = getDBConnection();
    
    // 1. Alle Templates laden
    $stmt = $pdo->query("SELECT id, name, course_id FROM freebies ORDER BY created_at DESC");
    $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Welche Produkte hat der Kunde gekauft?
    $stmt = $pdo->prepare("SELECT product_id FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customerProductIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 3. Für jedes Template prüfen ob freigeschaltet
    $statusMap = [];
    $unlockedCount = 0;
    
    foreach ($allTemplates as $template) {
        $templateId = $template['id'];
        $templateName = $template['name'];
        $courseId = $template['course_id'];
        
        // Kein Kurs? Dann no_course
        if (empty($courseId)) {
            $statusMap['template_' . $templateId] = [
                'unlock_status' => 'no_course',
                'name' => $templateName
            ];
            continue;
        }
        
        // ✅ KRITISCHER FIX: Wenn Kunde keine Produkte hat, sind alle Kurse gesperrt
        if (empty($customerProductIds)) {
            $statusMap['template_' . $templateId] = [
                'unlock_status' => 'locked',
                'name' => $templateName
            ];
            continue;
        }
        
        // Hat der Kunde Zugriff auf diesen Kurs?
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_access
            FROM webhook_course_access wca
            INNER JOIN webhook_configurations wc ON wca.webhook_id = wc.id AND wc.is_active = 1
            INNER JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
            WHERE wca.course_id = ?
            AND wpi.product_id IN (" . implode(',', array_fill(0, count($customerProductIds), '?')) . ")
        ");
        
        $params = array_merge([$courseId], $customerProductIds);
        $stmt->execute($params);
        $hasAccess = $stmt->fetch(PDO::FETCH_ASSOC)['has_access'] > 0;
        
        $statusMap['template_' . $templateId] = [
            'unlock_status' => $hasAccess ? 'unlocked' : 'locked',
            'name' => $templateName
        ];
        
        if ($hasAccess) {
            $unlockedCount++;
        }
    }
    
    // Erfolgreiche Response
    echo json_encode([
        'success' => true,
        'customer_id' => $customer_id,
        'total_templates' => count($allTemplates),
        'unlocked_count' => $unlockedCount,
        'customer_products' => $customerProductIds,
        'has_products' => !empty($customerProductIds),
        'statuses' => $statusMap
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ], JSON_PRETTY_PRINT);
}
?>