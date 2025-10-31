<?php
/**
 * API: Lektion als abgeschlossen/nicht abgeschlossen markieren
 * POST /customer/api/mark-lesson-complete.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../config/database.php';

try {
    $pdo = getDBConnection();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'];
    $lesson_id = $data['lesson_id'] ?? null;
    $completed = $data['completed'] ?? true;
    
    if (!$lesson_id) {
        throw new Exception('Lektions-ID fehlt');
    }
    
    if ($completed) {
        // Als abgeschlossen markieren
        $stmt = $pdo->prepare("
            INSERT INTO course_progress (user_id, lesson_id, completed, completed_at)
            VALUES (?, ?, TRUE, NOW())
            ON DUPLICATE KEY UPDATE 
                completed = TRUE,
                completed_at = NOW()
        ");
        $stmt->execute([$user_id, $lesson_id]);
    } else {
        // Als nicht abgeschlossen markieren
        $stmt = $pdo->prepare("
            UPDATE course_progress 
            SET completed = FALSE, completed_at = NULL
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$user_id, $lesson_id]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $completed ? 'Lektion als abgeschlossen markiert' : 'Lektion als nicht abgeschlossen markiert'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>