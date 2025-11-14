<?php
/**
 * Vendor Image Upload API
 * Ermöglicht das Hochladen von Produktbildern
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    // Get PDO connection
    $pdo = getDBConnection();
    
    // Prüfe ob User Vendor ist
    $stmt = $pdo->prepare("SELECT is_vendor FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_vendor']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Kein Vendor']);
        exit;
    }
    
    // Prüfe ob Datei hochgeladen wurde
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Keine Datei hochgeladen');
    }
    
    $file = $_FILES['image'];
    
    // Validierung
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Ungültiger Dateityp. Erlaubt: JPG, PNG, WebP, GIF');
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception('Datei zu groß. Maximal 5MB erlaubt');
    }
    
    // Erstelle Upload-Verzeichnis falls nicht vorhanden
    $upload_dir = __DIR__ . '/../../uploads/vendor-products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generiere eindeutigen Dateinamen
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'vendor_' . $customer_id . '_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Verschiebe Datei
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Fehler beim Speichern der Datei');
    }
    
    // Generiere URL
    $url = '/uploads/vendor-products/' . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'filename' => $filename
    ]);
    
} catch (Exception $e) {
    error_log('Vendor Image Upload Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
