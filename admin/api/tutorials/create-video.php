<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

$pdo = getDBConnection();

$category_id = intval($_POST['category_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$vimeo_url = trim($_POST['vimeo_url'] ?? '');
$sort_order = intval($_POST['sort_order'] ?? 0);
$is_active = isset($_POST['is_active']) ? 1 : 0;

if (empty($title) || empty($vimeo_url) || $category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Titel, Video-URL und Kategorie sind erforderlich']);
    exit;
}

// Vimeo URL validieren
if (!preg_match('/vimeo\.com/i', $vimeo_url)) {
    echo json_encode(['success' => false, 'message' => 'Bitte eine gültige Vimeo-URL eingeben']);
    exit;
}

// Mockup-Upload verarbeiten
$mockup_image = null;
if (isset($_FILES['mockup_image']) && $_FILES['mockup_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../../uploads/mockups/';
    
    // Verzeichnis erstellen falls nicht vorhanden
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_info = pathinfo($_FILES['mockup_image']['name']);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Ungültiges Dateiformat. Erlaubt: JPG, PNG, GIF, WebP']);
        exit;
    }
    
    // Eindeutigen Dateinamen generieren
    $filename = 'mockup_' . time() . '_' . uniqid() . '.' . $extension;
    $target_path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['mockup_image']['tmp_name'], $target_path)) {
        $mockup_image = '/uploads/mockups/' . $filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Hochladen des Mockups']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO tutorials (category_id, title, description, vimeo_url, mockup_image, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$category_id, $title, $description, $vimeo_url, $mockup_image, $sort_order, $is_active]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Video erfolgreich erstellt',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    // Bei Fehler hochgeladenes Bild wieder löschen
    if ($mockup_image && file_exists('../../../' . $mockup_image)) {
        unlink('../../../' . $mockup_image);
    }
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
