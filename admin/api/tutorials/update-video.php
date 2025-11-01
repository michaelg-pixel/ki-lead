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

try {
    $stmt = $pdo->prepare("
        UPDATE tutorials 
        SET category_id = ?, title = ?, description = ?, vimeo_url = ?, sort_order = ?, is_active = ?
        WHERE id = ?
    ");
    
    $stmt->execute([$category_id, $title, $description, $vimeo_url, $sort_order, $is_active, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Video erfolgreich aktualisiert'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
