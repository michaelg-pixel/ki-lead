<?php
/**
 * API: Kurs löschen
 * POST /admin/api/courses/delete.php
 */

session_start();
header('Content-Type: application/json');

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = $data['course_id'] ?? null;
    
    if (!$course_id) {
        throw new Exception('Kurs-ID fehlt');
    }
    
    // Get course files before deletion
    $stmt = $pdo->prepare("SELECT mockup_url, pdf_file FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        throw new Exception('Kurs nicht gefunden');
    }
    
    // Delete course (cascade will delete modules, lessons, access, progress)
    $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    
    // Delete files
    if ($course['mockup_url']) {
        $file_path = '../../../' . ltrim($course['mockup_url'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    if ($course['pdf_file']) {
        $file_path = '../../../' . ltrim($course['pdf_file'], '/');
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Kurs erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>