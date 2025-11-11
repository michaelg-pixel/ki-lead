<?php
/**
 * API für Kurs-Module
 * CRUD Operations
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
            // Prüfen ob Kurs dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT cf.id FROM customer_freebies cf
                JOIN freebie_courses fc ON cf.id = fc.freebie_id
                WHERE fc.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['course_id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf diesen Kurs');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("
                SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order 
                FROM freebie_course_modules 
                WHERE course_id = ?
            ");
            $stmt->execute([$input['course_id']]);
            $sort_order = $stmt->fetchColumn();
            
            // Modul erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules 
                (course_id, title, description, sort_order, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $input['course_id'],
                $input['title'],
                $input['description'] ?? '',
                $sort_order
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Modul erstellt',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'update':
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses fc ON m.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE m.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf dieses Modul');
            }
            
            // Modul aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_modules 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $input['title'],
                $input['description'] ?? '',
                $input['id']
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Modul aktualisiert']);
            break;
            
        case 'delete':
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT m.id FROM freebie_course_modules m
                JOIN freebie_courses fc ON m.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE m.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$input['id'], $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kein Zugriff auf dieses Modul');
            }
            
            // Modul löschen (Lektionen werden via CASCADE gelöscht)
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Modul gelöscht']);
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