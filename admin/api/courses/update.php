<?php
/**
 * API: Kurs aktualisieren
 * POST /admin/api/courses/update.php
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../config/database.php';

try {
    $course_id = $_POST['course_id'] ?? null;
    
    if (!$course_id) {
        throw new Exception('Kurs-ID fehlt');
    }
    
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $additional_info = $_POST['additional_info'] ?? '';
    $mockup_url = $_POST['mockup_url'] ?? '';
    $is_freebie = isset($_POST['is_freebie']) ? 1 : 0;
    $digistore_product_id = $_POST['digistore_product_id'] ?? '';
    
    // File Upload: Mockup
    if (isset($_FILES['mockup_file']) && $_FILES['mockup_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/courses/mockups/';
        $file_extension = pathinfo($_FILES['mockup_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('mockup_') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['mockup_file']['tmp_name'], $file_path)) {
            $mockup_url = '/uploads/courses/mockups/' . $file_name;
        }
    }
    
    // File Upload: PDF
    $pdf_file = null;
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/courses/pdfs/';
        $file_extension = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('course_') . '.pdf';
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $file_path)) {
            $pdf_file = '/uploads/courses/pdfs/' . $file_name;
        }
    }
    
    // Update Course
    $sql = "UPDATE courses SET 
            title = :title,
            description = :description,
            additional_info = :additional_info,
            mockup_url = :mockup_url,
            is_freebie = :is_freebie,
            digistore_product_id = :digistore_product_id";
    
    if ($pdf_file) {
        $sql .= ", pdf_file = :pdf_file";
    }
    
    $sql .= " WHERE id = :course_id";
    
    $stmt = $pdo->prepare($sql);
    $params = [
        'title' => $title,
        'description' => $description,
        'additional_info' => $additional_info,
        'mockup_url' => $mockup_url,
        'is_freebie' => $is_freebie,
        'digistore_product_id' => $digistore_product_id,
        'course_id' => $course_id
    ];
    
    if ($pdf_file) {
        $params['pdf_file'] = $pdf_file;
    }
    
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kurs erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>