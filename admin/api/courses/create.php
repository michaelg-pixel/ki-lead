<?php
/**
 * API: Kurs erstellen
 * POST /admin/api/courses/create.php
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
    $pdo->beginTransaction();
    
    // Form Data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? 'video';
    $additional_info = $_POST['additional_info'] ?? '';
    $mockup_url = $_POST['mockup_url'] ?? '';
    $is_freebie = isset($_POST['is_freebie']) ? 1 : 0;
    $digistore_product_id = $_POST['digistore_product_id'] ?? '';
    $niche = 'other'; // Default niche
    
    // Validation
    if (empty($title)) {
        throw new Exception('Titel ist erforderlich');
    }
    
    // File Upload: Mockup
    if (isset($_FILES['mockup_file']) && $_FILES['mockup_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/courses/mockups/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['mockup_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('mockup_') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['mockup_file']['tmp_name'], $file_path)) {
            $mockup_url = '/uploads/courses/mockups/' . $file_name;
        }
    }
    
    // File Upload: PDF (für PDF-Kurse)
    $pdf_file = null;
    if ($type === 'pdf' && isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../../uploads/courses/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if ($file_extension !== 'pdf') {
            throw new Exception('Nur PDF-Dateien sind erlaubt');
        }
        
        $file_name = uniqid('course_') . '.pdf';
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $file_path)) {
            $pdf_file = '/uploads/courses/pdfs/' . $file_name;
        }
    }
    
    // Prüfe ob niche Spalte existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'niche'");
    $has_niche = $stmt->rowCount() > 0;
    
    // Insert Course
    if ($has_niche) {
        $stmt = $pdo->prepare("
            INSERT INTO courses (
                title, description, type, additional_info, 
                mockup_url, pdf_file, is_freebie, digistore_product_id, niche
            ) VALUES (
                :title, :description, :type, :additional_info,
                :mockup_url, :pdf_file, :is_freebie, :digistore_product_id, :niche
            )
        ");
        
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'additional_info' => $additional_info,
            'mockup_url' => $mockup_url,
            'pdf_file' => $pdf_file,
            'is_freebie' => $is_freebie,
            'digistore_product_id' => $digistore_product_id,
            'niche' => $niche
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO courses (
                title, description, type, additional_info, 
                mockup_url, pdf_file, is_freebie, digistore_product_id
            ) VALUES (
                :title, :description, :type, :additional_info,
                :mockup_url, :pdf_file, :is_freebie, :digistore_product_id
            )
        ");
        
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'type' => $type,
            'additional_info' => $additional_info,
            'mockup_url' => $mockup_url,
            'pdf_file' => $pdf_file,
            'is_freebie' => $is_freebie,
            'digistore_product_id' => $digistore_product_id
        ]);
    }
    
    $course_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'course_id' => $course_id,
        'message' => 'Kurs erfolgreich erstellt'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>