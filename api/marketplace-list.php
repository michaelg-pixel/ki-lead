<?php
session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $customer_id = $_SESSION['user_id'];
    
    // Optionaler Nischen-Filter
    $niche = isset($_GET['niche']) ? $_GET['niche'] : null;
    
    // Alle für den Marktplatz freigegebenen Freebies laden
    $sql = "
        SELECT 
            cf.id,
            cf.customer_id,
            cf.headline,
            cf.subheadline,
            cf.mockup_image_url,
            cf.background_color,
            cf.primary_color,
            cf.niche,
            cf.marketplace_enabled,
            cf.marketplace_price,
            cf.digistore_product_id,
            cf.marketplace_description,
            cf.course_lessons_count,
            cf.course_duration,
            cf.marketplace_sales_count,
            cf.created_at,
            c.name as creator_name,
            c.email as creator_email
        FROM customer_freebies cf
        INNER JOIN customers c ON cf.customer_id = c.id
        WHERE cf.marketplace_enabled = 1
    ";
    
    $params = [];
    
    if ($niche) {
        $sql .= " AND cf.niche = ?";
        $params[] = $niche;
    }
    
    $sql .= " ORDER BY cf.marketplace_updated_at DESC, cf.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Markieren, welche Freebies der aktuelle Customer bereits gekauft hat
    foreach ($freebies as &$freebie) {
        $freebie['is_own'] = ($freebie['customer_id'] == $customer_id);
        
        // Prüfen, ob bereits gekauft/kopiert
        $stmt = $pdo->prepare("
            SELECT id FROM customer_freebies 
            WHERE customer_id = ? 
            AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$customer_id, $freebie['id']]);
        $freebie['already_purchased'] = ($stmt->fetch() !== false);
    }
    
    echo json_encode([
        'success' => true,
        'freebies' => $freebies,
        'total' => count($freebies)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>