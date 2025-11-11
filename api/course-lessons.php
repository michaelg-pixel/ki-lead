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
            // Neue Lektion erstellen
            if (empty($input['module_id']) || empty($input['title'])) {
                throw new Exception('Module-ID und Titel sind erforderlich');
            }
            
            $module_id = $input['module_id'];
            $title = trim($input['title']);
            $description = trim($input['description'] ?? '');
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            $button_text = trim($input['button_text'] ?? '');
            $button_url = trim($input['button_url'] ?? '');
            $unlock_after_days = intval($input['unlock_after_days'] ?? 0);
            
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
                (module_id, title, description, video_url, pdf_url, button_text, button_url, unlock_after_days, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$module_id, $title, $description, $video_url, $pdf_url, $button_text, $button_url, $unlock_after_days, $next_order]);
            
            $lesson_id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'Lektion erfolgreich erstellt',
                'lesson_id' => $lesson_id
            ]);
            break;
            
        case 'update':
            // Lektion aktualisieren
            if (empty($input['id']) || empty($input['title'])) {
                throw new Exception('ID und Titel sind erforderlich');
            }
            
            $lesson_id = $input['id'];
            $title = trim($input['title']);
            $description = trim($input['description'] ?? '');
            $video_url = trim($input['video_url'] ?? '');
            $pdf_url = trim($input['pdf_url'] ?? '');
            $button_text = trim($input['button_text'] ?? '');
            $button_url = trim($input['button_url'] ?? '');
            $unlock_after_days = intval($input['unlock_after_days'] ?? 0);
            
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
                SET title = ?, description = ?, video_url = ?, pdf_url = ?, 
                    button_text = ?, button_url = ?, unlock_after_days = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $video_url, $pdf_url, $button_text, $button_url, $unlock_after_days, $lesson_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Lektion erfolgreich aktualisiert'
            ]);
            break;
            
        case 'delete':
            // Lektion löschen
            if (empty($input['id'])) {
                throw new Exception('ID ist erforderlich');
            }
            
            $lesson_id = $input['id'];
            
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
