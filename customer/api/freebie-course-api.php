<?php
/**
 * 🎓 FREEBIE COURSE API
 * 
 * API Endpoints für Videokurs-Management
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

$customer_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        
        case 'create_course':
            $freebie_id = $input['freebie_id'] ?? 0;
            $title = trim($input['title'] ?? 'Mein Videokurs');
            $description = trim($input['description'] ?? '');
            
            if (!$freebie_id) {
                throw new Exception('Freebie ID fehlt');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ?");
            $stmt->execute([$freebie_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Freebie nicht gefunden');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM freebie_courses WHERE freebie_id = ?");
            $stmt->execute([$freebie_id]);
            if ($stmt->fetch()) {
                throw new Exception('Kurs existiert bereits');
            }
            
            $stmt = $pdo->prepare("INSERT INTO freebie_courses (freebie_id, customer_id, title, description, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$freebie_id, $customer_id, $title, $description]);
            $course_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
            $stmt->execute([$freebie_id]);
            
            echo json_encode(['success' => true, 'course_id' => $course_id, 'message' => 'Kurs erstellt']);
            break;
        
        case 'create_module':
            $course_id = $input['course_id'] ?? 0;
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            
            if (!$course_id || empty($title)) {
                throw new Exception('Kurs ID und Titel erforderlich');
            }
            
            $stmt = $pdo->prepare("SELECT fc.id FROM freebie_courses fc JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE fc.id = ? AND cf.customer_id = ?");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_order = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $pdo->prepare("INSERT INTO freebie_course_modules (course_id, title, description, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$course_id, $title, $description, $next_order]);
            
            echo json_encode(['success' => true, 'module_id' => $pdo->lastInsertId(), 'message' => 'Modul erstellt']);
            break;
        
        case 'update_module':
            $module_id = $input['module_id'] ?? 0;
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            
            if (!$module_id || empty($title)) {
                throw new Exception('Modul ID und Titel erforderlich');
            }
            
            $stmt = $pdo->prepare("SELECT m.id FROM freebie_course_modules m JOIN freebie_courses fc ON m.course_id = fc.id JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE m.id = ? AND cf.customer_id = ?");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("UPDATE freebie_course_modules SET title = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $description, $module_id]);
            
            echo json_encode(['success' => true, 'message' => 'Modul aktualisiert']);
            break;
        
        case 'delete_module':
            $module_id = $input['module_id'] ?? 0;
            
            if (!$module_id) {
                throw new Exception('Modul ID fehlt');
            }
            
            $stmt = $pdo->prepare("SELECT m.id FROM freebie_course_modules m JOIN freebie_courses fc ON m.course_id = fc.id JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE m.id = ? AND cf.customer_id = ?");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE id = ?");
            $stmt->execute([$module_id]);
            
            echo json_encode(['success' => true, 'message' => 'Modul gelöscht']);
            break;
        
        case 'create_lesson':
            $module_id = $input['module_id'] ?? 0;
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            
            if (!$module_id || empty($title)) {
                throw new Exception('Modul ID und Titel erforderlich');
            }
            
            $stmt = $pdo->prepare("SELECT m.id FROM freebie_course_modules m JOIN freebie_courses fc ON m.course_id = fc.id JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE m.id = ? AND cf.customer_id = ?");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_order = ($result['max_order'] ?? 0) + 1;
            
            $stmt = $pdo->prepare("INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$module_id, $title, $description, $video_url, $pdf_url, $next_order]);
            
            echo json_encode(['success' => true, 'lesson_id' => $pdo->lastInsertId(), 'message' => 'Lektion erstellt']);
            break;
        
        case 'update_lesson':
            $lesson_id = $input['lesson_id'] ?? 0;
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            
            if (!$lesson_id || empty($title)) {
                throw new Exception('Lektion ID und Titel erforderlich');
            }
            
            $stmt = $pdo->prepare("SELECT l.id FROM freebie_course_lessons l JOIN freebie_course_modules m ON l.module_id = m.id JOIN freebie_courses fc ON m.course_id = fc.id JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE l.id = ? AND cf.customer_id = ?");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("UPDATE freebie_course_lessons SET title = ?, description = ?, video_url = ?, pdf_url = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$title, $description, $video_url, $pdf_url, $lesson_id]);
            
            echo json_encode(['success' => true, 'message' => 'Lektion aktualisiert']);
            break;
        
        case 'delete_lesson':
            $lesson_id = $input['lesson_id'] ?? 0;
            
            if (!$lesson_id) {
                throw new Exception('Lektion ID fehlt');
            }
            
            $stmt = $pdo->prepare("SELECT l.id FROM freebie_course_lessons l JOIN freebie_course_modules m ON l.module_id = m.id JOIN freebie_courses fc ON m.course_id = fc.id JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE l.id = ? AND cf.customer_id = ?");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            
            echo json_encode(['success' => true, 'message' => 'Lektion gelöscht']);
            break;
        
        case 'delete_course':
            $course_id = $input['course_id'] ?? 0;
            
            if (!$course_id) {
                throw new Exception('Kurs ID fehlt');
            }
            
            $stmt = $pdo->prepare("SELECT fc.id, fc.freebie_id FROM freebie_courses fc JOIN customer_freebies cf ON fc.freebie_id = cf.id WHERE fc.id = ? AND cf.customer_id = ?");
            $stmt->execute([$course_id, $customer_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$course) {
                throw new Exception('Keine Berechtigung');
            }
            
            $stmt = $pdo->prepare("SELECT id FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $module_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($module_ids)) {
                $placeholders = str_repeat('?,', count($module_ids) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE module_id IN ($placeholders)");
                $stmt->execute($module_ids);
            }
            
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course_id]);
            
            $stmt = $pdo->prepare("DELETE FROM freebie_courses WHERE id = ?");
            $stmt->execute([$course_id]);
            
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 0 WHERE id = ?");
            $stmt->execute([$course['freebie_id']]);
            
            echo json_encode(['success' => true, 'message' => 'Kurs gelöscht']);
            break;
        
        default:
            throw new Exception('Ungültige Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
