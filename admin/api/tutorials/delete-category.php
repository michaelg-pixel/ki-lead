<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Ungültige ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Prüfen ob Videos existieren
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tutorials WHERE category_id = ?");
    $stmt->execute([$id]);
    $videoCount = $stmt->fetchColumn();
    
    if ($videoCount > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Kategorie kann nicht gelöscht werden. Es existieren noch $videoCount Video(s) in dieser Kategorie."
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM tutorial_categories WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kategorie erfolgreich gelöscht'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
}
