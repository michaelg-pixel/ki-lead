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
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];

// JSON-Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Freebie-ID fehlt']);
    exit;
}

try {
    $id = $input['id'];
    
    // Prüfen ob Freebie dem Kunden gehört und vom Typ 'custom' ist
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$id, $customer_id]);
    $freebie = $stmt->fetch();
    
    if (!$freebie) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Freebie löschen
    $stmt = $pdo->prepare("
        DELETE FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$id, $customer_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Freebie erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
