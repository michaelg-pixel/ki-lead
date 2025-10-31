<?php
/**
 * API: Modul erstellen
 * POST /admin/api/courses/modules/create.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../../config/database.php';

try {
    $course_id = $_POST['course_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!$course_id || !$title) {
        throw new Exception('Kurs-ID und Titel sind erforderlich');
    }
    
    // Get next sort order
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM course_modules WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $sort_order = $stmt->fetchColumn();
    
    // Insert Module
    $stmt = $pdo->prepare("
        INSERT INTO course_modules (course_id, title, description, sort_order)
        VALUES (:course_id, :title, :description, :sort_order)
    ");
    
    $stmt->execute([
        'course_id' => $course_id,
        'title' => $title,
        'description' => $description,
        'sort_order' => $sort_order
    ]);
    
    echo json_encode([
        'success' => true,
        'module_id' => $pdo->lastInsertId(),
        'message' => 'Modul erfolgreich erstellt'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>