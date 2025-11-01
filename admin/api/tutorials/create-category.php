<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

$pdo = getDBConnection();

$name = trim($_POST['name'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$description = trim($_POST['description'] ?? '');
$icon = trim($_POST['icon'] ?? 'video');
$sort_order = intval($_POST['sort_order'] ?? 0);

if (empty($name) || empty($slug)) {
    echo json_encode(['success' => false, 'message' => 'Name und Slug sind erforderlich']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO tutorial_categories (name, slug, description, icon, sort_order)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $slug, $description, $icon, $sort_order]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategorie erfolgreich erstellt',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'Slug existiert bereits']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
}
