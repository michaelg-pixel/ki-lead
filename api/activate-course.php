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

// Freebie ID aus URL extrahieren
$uri = $_SERVER['REQUEST_URI'];
preg_match('/\/api\/freebies\/(\d+)\/activate-course/', $uri, $matches);
$freebie_id = $matches[1] ?? null;

if (!$freebie_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Freebie-ID fehlt']);
    exit;
}

try {
    // Prüfen ob Freebie dem Kunden gehört
    $stmt = $pdo->prepare("
        SELECT id, headline FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        throw new Exception('Freebie nicht gefunden oder keine Berechtigung');
    }
    
    // Prüfen ob bereits ein Kurs existiert
    $stmt = $pdo->prepare("SELECT id FROM freebie_courses WHERE freebie_id = ?");
    $stmt->execute([$freebie_id]);
    $existingCourse = $stmt->fetch();
    
    if ($existingCourse) {
        throw new Exception('Videokurs bereits aktiviert');
    }
    
    // Transaktion starten
    $pdo->beginTransaction();
    
    // has_course Flag setzen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET has_course = 1, updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$freebie_id]);
    
    // Kurs erstellen
    $courseTitle = $freebie['headline'] . ' - Videokurs';
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (freebie_id, title, description, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $freebie_id, 
        $courseTitle,
        'Willkommen zu deinem exklusiven Videokurs!'
    ]);
    
    $course_id = $pdo->lastInsertId();
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Videokurs erfolgreich aktiviert',
        'course_id' => $course_id
    ]);
    
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
