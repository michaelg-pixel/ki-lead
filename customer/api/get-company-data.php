<?php
/**
 * API: Firmendaten für AV-Vertrag abrufen
 */

session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Firmendaten abrufen
    $stmt = $pdo->prepare("
        SELECT company_name, company_address, company_zip, company_city, 
               company_country, contact_person, contact_email, contact_phone,
               created_at, updated_at
        FROM user_company_data 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => 'Noch keine Firmendaten hinterlegt'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Abrufen der Daten: ' . $e->getMessage()
    ]);
}
