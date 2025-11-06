<?php
/**
 * API für Customer Freebie Kurse
 * Verwaltet Module, Lektionen und Fortschritt
 */

session_start();
require_once __DIR__ . '/../../config/database.php';

// JSON Response Header
header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        
        // ==================== KURS ====================
        case 'create_course':
            $freebie_id = $input['freebie_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            
            // Prüfen ob Freebie dem Customer gehört
            $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ?");
            $stmt->execute([$freebie_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Freebie nicht gefunden');
            }
            
            // Kurs erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_courses (freebie_id, customer_id, title, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$freebie_id, $customer_id, $title, $description]);
            $course_id = $pdo->lastInsertId();
            
            // has_course Flag setzen
            $pdo->prepare("UPDATE customer_freebies SET has_course = TRUE WHERE id = ?")
                ->execute([$freebie_id]);
            
            echo json_encode(['success' => true, 'course_id' => $course_id]);
            break;
            
        case 'update_course':
            $course_id = $input['course_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            
            // Prüfen ob Kurs dem Customer gehört
            $stmt = $pdo->prepare("SELECT id FROM freebie_courses WHERE id = ? AND customer_id = ?");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kurs nicht gefunden');
            }
            
            $stmt = $pdo->prepare("
                UPDATE freebie_courses 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$title, $description, $course_id, $customer_id]);
            
            echo json_encode(['success' => true]);
            break;
        
        // ==================== MODULE ====================
        case 'create_module':
            $course_id = $input['course_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            
            // Prüfen ob Kurs dem Customer gehört
            $stmt = $pdo->prepare("SELECT id FROM freebie_courses WHERE id = ? AND customer_id = ?");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kurs nicht gefunden');
            }
            
            // Höchste sort_order finden
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $max_order = $stmt->fetchColumn() ?? 0;
            
            // Modul erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules (course_id, title, description, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$course_id, $title, $description, $max_order + 1]);
            $module_id = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'module_id' => $module_id]);
            break;
            
        case 'update_module':
            $module_id = $input['module_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            
            // Prüfen ob Modul dem Customer gehört (via course)
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses c ON m.course_id = c.id
                WHERE m.id = ? AND c.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Modul nicht gefunden');
            }
            
            $stmt = $pdo->prepare("
                UPDATE freebie_course_modules 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $module_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_module':
            $module_id = $input['module_id'];
            
            // Prüfen ob Modul dem Customer gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses c ON m.course_id = c.id
                WHERE m.id = ? AND c.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Modul nicht gefunden');
            }
            
            // Modul löschen (CASCADE löscht auch Lektionen)
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE id = ?");
            $stmt->execute([$module_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'reorder_modules':
            $modules = $input['modules']; // Array: [{id: 1, order: 0}, {id: 2, order: 1}, ...]
            
            foreach ($modules as $module) {
                // Prüfen ob Modul dem Customer gehört
                $stmt = $pdo->prepare("
                    SELECT m.id FROM freebie_course_modules m
                    JOIN freebie_courses c ON m.course_id = c.id
                    WHERE m.id = ? AND c.customer_id = ?
                ");
                $stmt->execute([$module['id'], $customer_id]);
                if (!$stmt->fetch()) {
                    continue;
                }
                
                $stmt = $pdo->prepare("UPDATE freebie_course_modules SET sort_order = ? WHERE id = ?");
                $stmt->execute([$module['order'], $module['id']]);
            }
            
            echo json_encode(['success' => true]);
            break;
        
        // ==================== LEKTIONEN ====================
        case 'create_lesson':
            $module_id = $input['module_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            $video_url = $input['video_url'] ?? '';
            $pdf_url = $input['pdf_url'] ?? '';
            
            // Prüfen ob Modul dem Customer gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses c ON m.course_id = c.id
                WHERE m.id = ? AND c.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Modul nicht gefunden');
            }
            
            // Video-URL normalisieren
            $video_url = normalizeVideoUrl($video_url);
            
            // Höchste sort_order finden
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            $max_order = $stmt->fetchColumn() ?? 0;
            
            // Lektion erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$module_id, $title, $description, $video_url, $pdf_url, $max_order + 1]);
            $lesson_id = $pdo->lastInsertId();
            
            echo json_encode(['success' => true, 'lesson_id' => $lesson_id]);
            break;
            
        case 'update_lesson':
            $lesson_id = $input['lesson_id'];
            $title = $input['title'];
            $description = $input['description'] ?? '';
            $video_url = $input['video_url'] ?? '';
            $pdf_url = $input['pdf_url'] ?? '';
            
            // Prüfen ob Lektion dem Customer gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                JOIN freebie_course_modules m ON l.module_id = m.id
                JOIN freebie_courses c ON m.course_id = c.id
                WHERE l.id = ? AND c.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Video-URL normalisieren
            $video_url = normalizeVideoUrl($video_url);
            
            $stmt = $pdo->prepare("
                UPDATE freebie_course_lessons 
                SET title = ?, description = ?, video_url = ?, pdf_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $video_url, $pdf_url, $lesson_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_lesson':
            $lesson_id = $input['lesson_id'];
            
            // Prüfen ob Lektion dem Customer gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                JOIN freebie_course_modules m ON l.module_id = m.id
                JOIN freebie_courses c ON m.course_id = c.id
                WHERE l.id = ? AND c.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Lektion löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'reorder_lessons':
            $lessons = $input['lessons']; // Array: [{id: 1, order: 0}, {id: 2, order: 1}, ...]
            
            foreach ($lessons as $lesson) {
                // Prüfen ob Lektion dem Customer gehört
                $stmt = $pdo->prepare("
                    SELECT l.id FROM freebie_course_lessons l
                    JOIN freebie_course_modules m ON l.module_id = m.id
                    JOIN freebie_courses c ON m.course_id = c.id
                    WHERE l.id = ? AND c.customer_id = ?
                ");
                $stmt->execute([$lesson['id'], $customer_id]);
                if (!$stmt->fetch()) {
                    continue;
                }
                
                $stmt = $pdo->prepare("UPDATE freebie_course_lessons SET sort_order = ? WHERE id = ?");
                $stmt->execute([$lesson['order'], $lesson['id']]);
            }
            
            echo json_encode(['success' => true]);
            break;
        
        // ==================== FORTSCHRITT (für Leads) ====================
        case 'mark_complete':
            // Dieser Endpoint kann OHNE Login verwendet werden (nur mit E-Mail)
            $lesson_id = $input['lesson_id'];
            $lead_email = $input['email'];
            $completed = $input['completed'] ?? true;
            
            if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail');
            }
            
            // Lektion existiert?
            $stmt = $pdo->prepare("SELECT id FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Fortschritt speichern oder aktualisieren
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_progress (lead_email, lesson_id, completed, completed_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    completed = VALUES(completed),
                    completed_at = IF(VALUES(completed) = TRUE, NOW(), completed_at),
                    updated_at = NOW()
            ");
            $stmt->execute([$lead_email, $lesson_id, $completed]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_progress':
            // Fortschritt für einen Lead abrufen
            $lead_email = $input['email'];
            $course_id = $input['course_id'];
            
            if (!filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail');
            }
            
            // Alle abgeschlossenen Lektionen für diesen Lead in diesem Kurs
            $stmt = $pdo->prepare("
                SELECT 
                    p.lesson_id,
                    p.completed,
                    p.completed_at
                FROM freebie_course_progress p
                JOIN freebie_course_lessons l ON p.lesson_id = l.id
                JOIN freebie_course_modules m ON l.module_id = m.id
                WHERE p.lead_email = ? AND m.course_id = ?
            ");
            $stmt->execute([$lead_email, $course_id]);
            $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'progress' => $progress]);
            break;
            
        // ==================== MOCKUP ====================
        case 'update_mockup':
            $freebie_id = $input['freebie_id'];
            $mockup_url = $input['mockup_url'] ?? '';
            
            // Prüfen ob Freebie dem Customer gehört
            $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ?");
            $stmt->execute([$freebie_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Freebie nicht gefunden');
            }
            
            $stmt = $pdo->prepare("
                UPDATE customer_freebies 
                SET course_mockup_url = ?, updated_at = NOW()
                WHERE id = ? AND customer_id = ?
            ");
            $stmt->execute([$mockup_url, $freebie_id, $customer_id]);
            
            echo json_encode(['success' => true]);
            break;
        
        default:
            throw new Exception('Unbekannte Aktion');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Normalisiert Video-URLs zu Embed-URLs
 */
function normalizeVideoUrl($url) {
    if (empty($url)) return '';
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    // Bereits Embed-URL? Zurückgeben
    if (strpos($url, 'youtube.com/embed/') !== false || strpos($url, 'player.vimeo.com/video/') !== false) {
        return $url;
    }
    
    return $url;
}
