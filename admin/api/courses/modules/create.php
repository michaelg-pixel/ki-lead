<?php
/**
 * API: Modul erstellen
 * POST /admin/api/courses/modules/create.php
 */

session_start();
header('Content-Type: application/json');

// Logging für Debug
error_log("=== MODULE CREATE START ===");
error_log("POST Data: " . print_r($_POST, true));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once '../../../../config/database.php';

try {
    $pdo = getDBConnection();
    
    $course_id = $_POST['course_id'] ?? null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    
    error_log("Course ID: " . $course_id);
    error_log("Title: " . $title);
    error_log("Description: " . $description);
    
    if (!$course_id || !$title) {
        throw new Exception('Kurs-ID und Titel sind erforderlich');
    }
    
    // Get next sort order
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM course_modules WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $sort_order = $stmt->fetchColumn();
    
    error_log("Next Sort Order: " . $sort_order);
    
    // Insert Module
    $stmt = $pdo->prepare("
        INSERT INTO course_modules (course_id, title, description, sort_order)
        VALUES (:course_id, :title, :description, :sort_order)
    ");
    
    $result = $stmt->execute([
        'course_id' => $course_id,
        'title' => $title,
        'description' => $description,
        'sort_order' => $sort_order
    ]);
    
    $module_id = $pdo->lastInsertId();
    
    error_log("Insert Result: " . ($result ? 'SUCCESS' : 'FAILED'));
    error_log("New Module ID: " . $module_id);
    
    // Verify insertion
    $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    $verifyModule = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Verification: " . print_r($verifyModule, true));
    
    // Count total modules for this course
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $totalModules = $stmt->fetchColumn();
    error_log("Total Modules for Course: " . $totalModules);
    
    error_log("=== MODULE CREATE END ===");
    
    echo json_encode([
        'success' => true,
        'module_id' => $module_id,
        'sort_order' => $sort_order,
        'total_modules' => $totalModules,
        'message' => 'Modul erfolgreich erstellt'
    ]);
    
} catch (Exception $e) {
    error_log("ERROR: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>