<?php
session_start();
header('Content-Type: application/json');

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Neues Modul erstellen
            if (empty($input['course_id']) || empty($input['title'])) {
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
            
        case 'update':
            // Modul aktualisieren
            if (empty($input['id']) || empty($input['title'])) {
                throw new Exception('ID und Titel sind erforderlich');
            }
            
            $module_id = $input['id'];
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
            
        case 'delete':
            // Modul löschen
            if (empty($input['id'])) {
                throw new Exception('ID ist erforderlich');
            }
            
            $module_id = $input['id'];
            
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
            throw new Exception('Ungültige Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
