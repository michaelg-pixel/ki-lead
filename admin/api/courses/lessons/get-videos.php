<?php
/**
 * API Endpoint: Zusätzliche Videos einer Lektion laden
 */

session_start();
require_once '../../../../config/database.php';

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$lesson_id = $_GET['lesson_id'] ?? null;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'error' => 'Lesson ID fehlt']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order");
    $stmt->execute([$lesson_id]);
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'videos' => $videos
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
