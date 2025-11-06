<?php
/**
 * API: Lektion erstellen
 * POST /admin/api/courses/lessons/create.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../../config/database.php';

try {
    $pdo = getDBConnection(); // Korrekte Verwendung der DB-Verbindung
    
    $module_id = $_POST['module_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!$module_id || !$title) {
        throw new Exception('Modul-ID und Titel sind erforderlich');
    }
    
    // File Upload: PDF Attachment
    $pdf_attachment = null;
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../../uploads/courses/attachments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['pdf_attachment']['name'], PATHINFO_EXTENSION);
        if ($file_extension !== 'pdf') {
            throw new Exception('Nur PDF-Dateien sind erlaubt');
        }
        
        $file_name = uniqid('attachment_') . '.pdf';
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['pdf_attachment']['tmp_name'], $file_path)) {
            $pdf_attachment = '/uploads/courses/attachments/' . $file_name;
        }
    }
    
    // Get next sort order
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM course_lessons WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $sort_order = $stmt->fetchColumn();
    
    // Insert Lesson
    $stmt = $pdo->prepare("
        INSERT INTO course_lessons (module_id, title, video_url, description, pdf_attachment, sort_order)
        VALUES (:module_id, :title, :video_url, :description, :pdf_attachment, :sort_order)
    ");
    
    $stmt->execute([
        'module_id' => $module_id,
        'title' => $title,
        'video_url' => $video_url,
        'description' => $description,
        'pdf_attachment' => $pdf_attachment,
        'sort_order' => $sort_order
    ]);
    
    $lesson_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'lesson_id' => $lesson_id,
        'message' => 'Lektion erfolgreich erstellt'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>