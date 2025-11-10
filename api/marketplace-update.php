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
    
    // POST-Daten empfangen
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['freebie_id'])) {
        throw new Exception('Freebie-ID fehlt');
    }
    
    $freebie_id = (int)$input['freebie_id'];
    
    // Prüfen, ob das Freebie dem Customer gehört
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Marktplatz-Daten vorbereiten und konvertieren
    // WICHTIG: Boolean zu Integer konvertieren!
    $marketplace_enabled = isset($input['marketplace_enabled']) && $input['marketplace_enabled'] ? 1 : 0;
    
    // Preis: Leerer String wird zu NULL
    $marketplace_price = null;
    if (isset($input['marketplace_price']) && $input['marketplace_price'] !== '' && $input['marketplace_price'] !== null) {
        $marketplace_price = (float)$input['marketplace_price'];
    }
    
    // DigiStore24 Produkt-ID: Leerer String wird zu NULL
    $digistore_product_id = null;
    if (isset($input['digistore_product_id']) && trim($input['digistore_product_id']) !== '') {
        $digistore_product_id = trim($input['digistore_product_id']);
    }
    
    // Marktplatz-Beschreibung: Leerer String wird zu NULL
    $marketplace_description = null;
    if (isset($input['marketplace_description']) && trim($input['marketplace_description']) !== '') {
        $marketplace_description = trim($input['marketplace_description']);
    }
    
    // Lektionen-Anzahl: Leerer String wird zu NULL
    $course_lessons_count = null;
    if (isset($input['course_lessons_count']) && $input['course_lessons_count'] !== '' && $input['course_lessons_count'] !== null) {
        $course_lessons_count = (int)$input['course_lessons_count'];
    }
    
    // Kursdauer: Leerer String wird zu NULL
    $course_duration = null;
    if (isset($input['course_duration']) && trim($input['course_duration']) !== '') {
        $course_duration = trim($input['course_duration']);
    }
    
    // Update durchführen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET 
            marketplace_enabled = ?,
            marketplace_price = ?,
            digistore_product_id = ?,
            marketplace_description = ?,
            course_lessons_count = ?,
            course_duration = ?,
            marketplace_updated_at = NOW()
        WHERE id = ? AND customer_id = ?
    ");
    
    $stmt->execute([
        $marketplace_enabled,
        $marketplace_price,
        $digistore_product_id,
        $marketplace_description,
        $course_lessons_count,
        $course_duration,
        $freebie_id,
        $customer_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Marktplatz-Einstellungen gespeichert',
        'data' => [
            'marketplace_enabled' => $marketplace_enabled,
            'marketplace_price' => $marketplace_price,
            'digistore_product_id' => $digistore_product_id
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>