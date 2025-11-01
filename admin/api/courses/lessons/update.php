<?php
/**
 * API: Lektion aktualisieren
 * POST /admin/api/courses/lessons/update.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../../config/database.php';

try {
    $lesson_id = $_POST['lesson_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (!$lesson_id || !$title) {
        throw new Exception('Lektions-ID und Titel sind erforderlich');
    }
    
    // Aktuelle PDF-Datei laden
    $stmt = $pdo->prepare("SELECT pdf_attachment FROM course_lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $current_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $pdf_attachment = $current_lesson['pdf_attachment'] ?? null;
    
    // File Upload: PDF Attachment (nur wenn neue Datei hochgeladen wird)
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
            // Alte PDF löschen, falls vorhanden
            if ($pdf_attachment && file_exists('../../../../' . $pdf_attachment)) {
                unlink('../../../../' . $pdf_attachment);
            }
            $pdf_attachment = '/uploads/courses/attachments/' . $file_name;
        }
    }
    
    // Update Lesson
    $stmt = $pdo->prepare("
        UPDATE course_lessons 
        SET title = :title, 
            video_url = :video_url, 
            description = :description,
            pdf_attachment = :pdf_attachment
        WHERE id = :lesson_id
    ");
    
    $stmt->execute([
        'lesson_id' => $lesson_id,
        'title' => $title,
        'video_url' => $video_url,
        'description' => $description,
        'pdf_attachment' => $pdf_attachment
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lektion erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>