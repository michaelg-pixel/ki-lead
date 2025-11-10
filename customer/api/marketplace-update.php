<?php
session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];

// JSON Content-Type
header('Content-Type: application/json');

// POST-Request verarbeiten
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST erlaubt']);
    exit;
}

try {
    $freebie_id = (int)($_POST['freebie_id'] ?? 0);
    $enabled = (int)($_POST['enabled'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $digistore_id = (int)($_POST['digistore_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    if ($freebie_id <= 0) {
        throw new Exception('Ungültige Freebie-ID');
    }
    
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'");
    $stmt->execute([$freebie_id, $customer_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Update
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_enabled = ?,
            marketplace_price = ?,
            digistore_product_id = ?,
            marketplace_description = ?,
            updated_at = NOW()
        WHERE id = ? AND customer_id = ?
    ");
    
    $stmt->execute([
        $enabled,
        $price,
        $digistore_id,
        $description,
        $freebie_id,
        $customer_id
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Marktplatz-Einstellungen erfolgreich gespeichert!'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
