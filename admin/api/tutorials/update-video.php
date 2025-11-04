<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

$pdo = getDBConnection();

$id = intval($_POST['id'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$vimeo_url = trim($_POST['vimeo_url'] ?? '');
$sort_order = intval($_POST['sort_order'] ?? 0);
$is_active = isset($_POST['is_active']) ? 1 : 0;

if ($id <= 0 || empty($title) || empty($vimeo_url) || $category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

// Vimeo URL validieren
if (!preg_match('/vimeo\.com/i', $vimeo_url)) {
    echo json_encode(['success' => false, 'message' => 'Bitte eine gültige Vimeo-URL eingeben']);
    exit;
}

// Altes Mockup abrufen
$stmt = $pdo->prepare("SELECT mockup_image FROM tutorials WHERE id = ?");
$stmt->execute([$id]);
$old_video = $stmt->fetch(PDO::FETCH_ASSOC);
$old_mockup = $old_video['mockup_image'] ?? null;

// Mockup-Upload verarbeiten
$mockup_image = $old_mockup; // Behalte altes Mockup als Standard
$delete_old_mockup = false;

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
        $delete_old_mockup = true; // Altes Mockup kann gelöscht werden
    } else {
        echo json_encode(['success' => false, 'message' => 'Fehler beim Hochladen des Mockups']);
        exit;
    }
}

// Prüfen ob Mockup gelöscht werden soll (über POST-Parameter)
if (isset($_POST['delete_mockup']) && $_POST['delete_mockup'] === '1') {
    $mockup_image = null;
    $delete_old_mockup = true;
}

try {
    $stmt = $pdo->prepare("
        UPDATE tutorials 
        SET category_id = ?, title = ?, description = ?, vimeo_url = ?, mockup_image = ?, sort_order = ?, is_active = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$category_id, $title, $description, $vimeo_url, $mockup_image, $sort_order, $is_active, $id]);
    
    // Altes Mockup löschen falls ein neues hochgeladen wurde oder gelöscht werden soll
    if ($delete_old_mockup && $old_mockup && file_exists('../../../' . ltrim($old_mockup, '/'))) {
        unlink('../../../' . ltrim($old_mockup, '/'));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Video erfolgreich aktualisiert'
    ]);
} catch (PDOException $e) {
    // Bei Fehler neues hochgeladenes Bild wieder löschen
    if ($delete_old_mockup && $mockup_image && $mockup_image !== $old_mockup && file_exists('../../../' . ltrim($mockup_image, '/'))) {
        unlink('../../../' . ltrim($mockup_image, '/'));
    }
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
