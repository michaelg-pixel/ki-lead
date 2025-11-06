<?php
/**
 * API: Modul aktualisieren
 * POST /admin/api/courses/modules/update.php
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
    $description = $_POST['description'] ?? '';
    
    if (!$module_id || !$title) {
        throw new Exception('Modul-ID und Titel sind erforderlich');
    }
    
    // Update Module
    $stmt = $pdo->prepare("
        UPDATE course_modules 
        SET title = :title, 
            description = :description
        WHERE id = :module_id
    ");
    
    $stmt->execute([
        'module_id' => $module_id,
        'title' => $title,
        'description' => $description
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Modul erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>