<?php
/**
 * API: Modul löschen
 * POST /admin/api/courses/modules/delete.php
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
    
    $data = json_decode(file_get_contents('php://input'), true);
    $module_id = $data['module_id'] ?? null;
    
    if (!$module_id) {
        throw new Exception('Modul-ID fehlt');
    }
    
    // Delete module (cascade will delete lessons)
    $stmt = $pdo->prepare("DELETE FROM course_modules WHERE id = ?");
    $stmt->execute([$module_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Modul erfolgreich gelöscht'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>