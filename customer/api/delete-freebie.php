<?php
/**
 * API zum Löschen von Custom Freebies
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];

// POST-Daten holen
$input = json_decode(file_get_contents('php://input'), true);
$freebie_id = $input['freebie_id'] ?? null;

if (!$freebie_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Freebie-ID fehlt']);
    exit;
}

try {
    // Prüfen ob das Freebie dem Customer gehört und ein Custom-Freebie ist
    $stmt = $pdo->prepare("
        SELECT id, freebie_type 
        FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Freebie nicht gefunden oder keine Berechtigung']);
        exit;
    }
    
    // Freebie löschen
    $stmt = $pdo->prepare("DELETE FROM customer_freebies WHERE id = ? AND customer_id = ?");
    $stmt->execute([$freebie_id, $customer_id]);
    
    echo json_encode(['success' => true, 'message' => 'Freebie erfolgreich gelöscht']);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
