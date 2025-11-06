<?php
session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// Lesson ID aus URL extrahieren (falls vorhanden)
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/api\/course-lessons\/(\d+)/', $uri, $matches);
$lesson_id = $matches[1] ?? null;

try {
    switch ($method) {
        case 'GET':
            // Liste aller Lektionen eines Moduls
            $module_id = $_GET['module_id'] ?? null;
            if (!$module_id) {
                throw new Exception('Module-ID fehlt');
            }
            
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                INNER JOIN freebie_courses fc ON m.course_id = fc.id
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE m.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Modul nicht gefunden oder keine Berechtigung');
            }
            
            // Lektionen abrufen
            $stmt = $pdo->prepare("
                SELECT * FROM freebie_course_lessons
                WHERE module_id = ?
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$module_id]);
            $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'lessons' => $lessons
            ]);
            break;
            
        case 'POST':
            // Neue Lektion erstellen
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['module_id']) || empty($input['title'])) {
                throw new Exception('Module-ID und Titel sind erforderlich');
            }
            
            $module_id = $input['module_id'];
            $title = trim($input['title']);
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            
            // Mindestens Video oder PDF muss vorhanden sein
            if (empty($video_url) && empty($pdf_url)) {
                throw new Exception('Mindestens Video-URL oder PDF-URL ist erforderlich');
            }
            
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                INNER JOIN freebie_courses fc ON m.course_id = fc.id
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE m.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Modul nicht gefunden oder keine Berechtigung');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                FROM freebie_course_lessons 
                WHERE module_id = ?
            ");
            $stmt->execute([$module_id]);
            $next_order = $stmt->fetchColumn();
            
            // Lektion erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons 
                (module_id, title, video_url, pdf_url, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$module_id, $title, $video_url, $pdf_url, $next_order]);
            
            $lesson_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Lektion erfolgreich erstellt',
                'lesson_id' => $lesson_id
            ]);
            break;
            
        case 'PUT':
            // Lektion aktualisieren
            if (!$lesson_id) {
                throw new Exception('Lektion-ID fehlt');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['title'])) {
                throw new Exception('Titel ist erforderlich');
            }
            
            $title = trim($input['title']);
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            
            // Mindestens Video oder PDF muss vorhanden sein
            if (empty($video_url) && empty($pdf_url)) {
                throw new Exception('Mindestens Video-URL oder PDF-URL ist erforderlich');
            }
            
            // Prüfen ob Lektion dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                INNER JOIN freebie_course_modules m ON l.module_id = m.id
                INNER JOIN freebie_courses fc ON m.course_id = fc.id
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE l.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden oder keine Berechtigung');
            }
            
            // Lektion aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_lessons 
                SET title = ?, video_url = ?, pdf_url = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $video_url, $pdf_url, $lesson_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lektion erfolgreich aktualisiert'
            ]);
            break;
            
        case 'DELETE':
            // Lektion löschen
            if (!$lesson_id) {
                throw new Exception('Lektion-ID fehlt');
            }
            
            // Prüfen ob Lektion dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                INNER JOIN freebie_course_modules m ON l.module_id = m.id
                INNER JOIN freebie_courses fc ON m.course_id = fc.id
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE l.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden oder keine Berechtigung');
            }
            
            // Lektion löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lektion erfolgreich gelöscht'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Methode nicht erlaubt']);
            break;
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
