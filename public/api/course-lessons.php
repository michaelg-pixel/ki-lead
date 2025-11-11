<?php
/**
 * API für Kurs-Lektionen
 * CRUD Operations mit allen Features
 */

session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check auth
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Parse JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses fc ON m.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE m.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['module_id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf dieses Modul');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                FROM freebie_course_lessons 
                WHERE module_id = ?
            ");
            $stmt->execute([$input['module_id']]);
            $sort_order = $stmt->fetchColumn();
            
            // Lektion erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons 
                (module_id, title, description, video_url, pdf_url, 
                 button_text, button_url, unlock_after_days, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $input['module_id'],
                $input['title'],
                $input['description'] ?? '',
                $input['video_url'] ?? '',
                $input['pdf_url'] ?? '',
                $input['button_text'] ?? '',
                $input['button_url'] ?? '',
                $input['unlock_after_days'] ?? 0,
                $sort_order
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Lektion erstellt',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'update':
            // Prüfen ob Lektion dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                JOIN freebie_course_modules m ON l.module_id = m.id
                JOIN freebie_courses fc ON m.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE l.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf diese Lektion');
            }
            
            // Lektion aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_lessons 
                SET title = ?, 
                    description = ?, 
                    video_url = ?, 
                    pdf_url = ?, 
                    button_text = ?, 
                    button_url = ?, 
                    unlock_after_days = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                $input['video_url'] ?? '',
                $input['pdf_url'] ?? '',
                $input['button_text'] ?? '',
                $input['button_url'] ?? '',
                $input['unlock_after_days'] ?? 0,
                $input['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Lektion aktualisiert']);
            break;
            
        case 'delete':
            // Prüfen ob Lektion dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT l.id FROM freebie_course_lessons l
                JOIN freebie_course_modules m ON l.module_id = m.id
                JOIN freebie_courses fc ON m.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE l.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf diese Lektion');
            }
            
            // Lektion löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Lektion gelöscht']);
            break;
            
        default:
            throw new Exception('Ungültige Aktion');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>