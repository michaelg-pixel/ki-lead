<?php
/**
 * Course Actions Handler - Videokurs Verwaltung
 * Behandelt alle CRUD-Operationen für Kurse, Module und Lektionen
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ==================== KURS ERSTELLEN ====================
        case 'create_course':
            $freebie_id = $_POST['freebie_id'] ?? 0;
            
            // Prüfen ob Freebie dem Kunden gehört
            $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ?");
            $stmt->execute([$freebie_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Freebie nicht gefunden');
            }
            
            // Kurs erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_courses (freebie_id, title, description, created_at)
                VALUES (?, 'Mein Videokurs', 'Beschreibung hier einfügen', NOW())
            ");
            $stmt->execute([$freebie_id]);
            
            // Freebie als "hat Kurs" markieren
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
            $stmt->execute([$freebie_id]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$freebie_id}&tab=course&success=course_created");
            exit;
            
        // ==================== MODUL ERSTELLEN ====================
        case 'create_module':
            $course_id = $_POST['course_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                throw new Exception('Titel erforderlich');
            }
            
            // Prüfen ob Kurs dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT fc.id FROM freebie_courses fc
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fc.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$course_id, $customer_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Kurs nicht gefunden');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $max_order = $stmt->fetchColumn() ?? 0;
            
            // Modul erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules (course_id, title, description, sort_order, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$course_id, $title, $description, $max_order + 1]);
            
            // Freebie ID ermitteln für Redirect
            $stmt = $pdo->prepare("SELECT freebie_id FROM freebie_courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $freebie_id = $stmt->fetchColumn();
            
            header("Location: custom-freebie-editor-tabs.php?id={$freebie_id}&tab=course&success=module_created");
            exit;
            
        // ==================== MODUL BEARBEITEN ====================
        case 'update_module':
            $module_id = $_POST['module_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($title)) {
                throw new Exception('Titel erforderlich');
            }
            
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT fcm.id, fc.freebie_id 
                FROM freebie_course_modules fcm
                JOIN freebie_courses fc ON fcm.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fcm.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$module) {
                throw new Exception('Modul nicht gefunden');
            }
            
            // Modul aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_modules 
                SET title = ?, description = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $module_id]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$module['freebie_id']}&tab=course&success=module_updated");
            exit;
            
        // ==================== MODUL LÖSCHEN ====================
        case 'delete_module':
            $module_id = $_GET['id'] ?? 0;
            
            // Prüfen ob Modul dem Kunden gehört
            $stmt = $pdo->prepare("
                SELECT fcm.id, fc.freebie_id 
                FROM freebie_course_modules fcm
                JOIN freebie_courses fc ON fcm.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fcm.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$module) {
                throw new Exception('Modul nicht gefunden');
            }
            
            // Erst alle Lektionen löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            
            // Dann Modul löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE id = ?");
            $stmt->execute([$module_id]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$module['freebie_id']}&tab=course&success=module_deleted");
            exit;
            
        // ==================== LEKTION ERSTELLEN ====================
        case 'create_lesson':
            $module_id = $_POST['module_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $video_url = trim($_POST['video_url'] ?? '');
            $pdf_url = trim($_POST['pdf_url'] ?? '');
            $button_text = trim($_POST['button_text'] ?? '');
            $button_url = trim($_POST['button_url'] ?? '');
            $unlock_after_days = intval($_POST['unlock_after_days'] ?? 0);
            
            if (empty($title)) {
                throw new Exception('Titel erforderlich');
            }
            
            // Prüfen ob Modul dem Kunden gehört + Freebie ID holen
            $stmt = $pdo->prepare("
                SELECT fcm.id, fc.freebie_id 
                FROM freebie_course_modules fcm
                JOIN freebie_courses fc ON fcm.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fcm.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$module_id, $customer_id]);
            $module = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$module) {
                throw new Exception('Modul nicht gefunden');
            }
            
            // Höchste sort_order ermitteln
            $stmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM freebie_course_lessons WHERE module_id = ?");
            $stmt->execute([$module_id]);
            $max_order = $stmt->fetchColumn() ?? 0;
            
            // Lektion erstellen
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (
                    module_id, title, description, video_url, pdf_url, 
                    button_text, button_url, unlock_after_days, sort_order, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $module_id, $title, $description, $video_url, $pdf_url,
                $button_text, $button_url, $unlock_after_days, $max_order + 1
            ]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$module['freebie_id']}&tab=course&success=lesson_created");
            exit;
            
        // ==================== LEKTION BEARBEITEN ====================
        case 'update_lesson':
            $lesson_id = $_POST['lesson_id'] ?? 0;
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $video_url = trim($_POST['video_url'] ?? '');
            $pdf_url = trim($_POST['pdf_url'] ?? '');
            $button_text = trim($_POST['button_text'] ?? '');
            $button_url = trim($_POST['button_url'] ?? '');
            $unlock_after_days = intval($_POST['unlock_after_days'] ?? 0);
            
            if (empty($title)) {
                throw new Exception('Titel erforderlich');
            }
            
            // Prüfen ob Lektion dem Kunden gehört + Freebie ID holen
            $stmt = $pdo->prepare("
                SELECT fcl.id, fc.freebie_id 
                FROM freebie_course_lessons fcl
                JOIN freebie_course_modules fcm ON fcl.module_id = fcm.id
                JOIN freebie_courses fc ON fcm.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fcl.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lesson) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Lektion aktualisieren
            $stmt = $pdo->prepare("
                UPDATE freebie_course_lessons 
                SET title = ?, description = ?, video_url = ?, pdf_url = ?,
                    button_text = ?, button_url = ?, unlock_after_days = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $title, $description, $video_url, $pdf_url,
                $button_text, $button_url, $unlock_after_days, $lesson_id
            ]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$lesson['freebie_id']}&tab=course&success=lesson_updated");
            exit;
            
        // ==================== LEKTION LÖSCHEN ====================
        case 'delete_lesson':
            $lesson_id = $_GET['id'] ?? 0;
            
            // Prüfen ob Lektion dem Kunden gehört + Freebie ID holen
            $stmt = $pdo->prepare("
                SELECT fcl.id, fc.freebie_id 
                FROM freebie_course_lessons fcl
                JOIN freebie_course_modules fcm ON fcl.module_id = fcm.id
                JOIN freebie_courses fc ON fcm.course_id = fc.id
                JOIN customer_freebies cf ON fc.freebie_id = cf.id
                WHERE fcl.id = ? AND cf.customer_id = ?
            ");
            $stmt->execute([$lesson_id, $customer_id]);
            $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lesson) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Lektion löschen
            $stmt = $pdo->prepare("DELETE FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            
            header("Location: custom-freebie-editor-tabs.php?id={$lesson['freebie_id']}&tab=course&success=lesson_deleted");
            exit;
            
        default:
            throw new Exception('Unbekannte Aktion');
    }
    
} catch (Exception $e) {
    // Fehlerbehandlung
    $error = urlencode($e->getMessage());
    
    // Versuche Freebie ID zu ermitteln für Redirect
    $freebie_id = $_POST['freebie_id'] ?? $_GET['freebie_id'] ?? 0;
    
    if ($freebie_id) {
        header("Location: custom-freebie-editor-tabs.php?id={$freebie_id}&tab=course&error={$error}");
    } else {
        header("Location: /customer/dashboard.php?page=freebies&error={$error}");
    }
    exit;
}
