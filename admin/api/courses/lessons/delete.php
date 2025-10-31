<?php
/**
 * API: Lektion löschen
 * POST /admin/api/courses/lessons/delete.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $lesson_id = $data['lesson_id'] ?? null;
    
    if (!$lesson_id) {
        throw new Exception('Lektions-ID fehlt');
    }
    
    // Get lesson files before deletion
    $stmt = $pdo->prepare("SELECT pdf_attachment FROM course_lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();
    
    // Delete lesson
    $stmt = $pdo->prepare("DELETE FROM course_lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    
    // Delete attachment file
    if ($lesson && $lesson['pdf_attachment']) {
        $file_path = '../../../../' . ltrim($lesson['pdf_attachment'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Lektion erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>