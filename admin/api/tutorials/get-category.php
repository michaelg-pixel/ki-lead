<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM tutorial_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        echo json_encode([
            'success' => true,
            'category' => $category
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kategorie nicht gefunden']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
