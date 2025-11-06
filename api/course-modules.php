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

// Module ID aus URL extrahieren (falls vorhanden)
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/api\/course-modules\/(\d+)/', $uri, $matches);
$module_id = $matches[1] ?? null;

try {
    switch ($method) {
        case 'GET':
            // Liste aller Module eines Kurses
            $course_id = $_GET['course_id'] ?? null;
            if (!$course_id) {
                throw new Exception('Course-ID fehlt');
            }
            
            // Prüfen ob Kurs dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT fc.id FROM freebie_courses fc
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fc.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kurs nicht gefunden oder keine Berechtigung');
            }
            
            // Module abrufen
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       COUNT(l.id) as lesson_count
                FROM freebie_course_modules m
                LEFT JOIN freebie_course_lessons l ON m.id = l.module_id
                WHERE m.course_id = ?
                GROUP BY m.id
                ORDER BY m.sort_order ASC
            ");
            $stmt->execute([$course_id]);
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'modules' => $modules
            ]);
            break;
            
        case 'POST':
            // Neues Modul erstellen
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['course_id']) || empty($input['title'])) {
                throw new Exception('Course-ID und Titel sind erforderlich');
            }
            
            $course_id = $input['course_id'];
            $title = trim($input['title']);
            $description = trim($input['description'] ?? '');
            
            // Prüfen ob Kurs dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT fc.id FROM freebie_courses fc
                INNER JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fc.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kurs nicht gefunden oder keine Berechtigung');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                FROM freebie_course_modules 
                WHERE course_id = ?
            ");
            $stmt->execute([$course_id]);
            $next_order = $stmt->fetchColumn();
            
            // Modul erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules 
                (course_id, title, description, sort_order, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$course_id, $title, $description, $next_order]);
            
            $module_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Modul erfolgreich erstellt',
                'module_id' => $module_id
            ]);
            break;
            
        case 'PUT':
            // Modul aktualisieren
            if (!$module_id) {
                throw new Exception('Modul-ID fehlt');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['title'])) {
                throw new Exception('Titel ist erforderlich');
            }
            
            $title = trim($input['title']);
            $description = trim($input['description'] ?? '');
            
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
            
            // Modul aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_modules 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $module_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Modul erfolgreich aktualisiert'
            ]);
            break;
            
        case 'DELETE':
            // Modul löschen
            if (!$module_id) {
                throw new Exception('Modul-ID fehlt');
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
            
            $pdo->beginTransaction();
            
            // Erst alle Lektionen löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            
            // Dann Modul löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE id = ?");
            $stmt->execute([$module_id]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Modul erfolgreich gelöscht'
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
