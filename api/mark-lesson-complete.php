<?php
/**
 * API: Lektion als abgeschlossen markieren
 * POST /api/mark-lesson-complete.php
 * Body: { "lesson_id": 123 }
 */

session_start();
header('Content-Type: application/json');

// CORS für lokale Tests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet']);
    exit;
}

$user_id = $_SESSION['user_id'];

// POST-Daten lesen
$input = json_decode(file_get_contents('php://input'), true);
$lesson_id = isset($input['lesson_id']) ? (int)$input['lesson_id'] : 0;

if ($lesson_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Lektions-ID']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Prüfen ob Lektion existiert und Zugriff besteht
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.module_id, cm.course_id
        FROM course_lessons cl
        JOIN course_modules cm ON cl.module_id = cm.id
        WHERE cl.id = ?
    ");
    $stmt->execute([$lesson_id]);
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Lektion nicht gefunden']);
        exit;
    }
    
    // Prüfen ob User Zugriff auf den Kurs hat
    $stmt = $pdo->prepare("
        SELECT id FROM course_access 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$user_id, $lesson['course_id']]);
    $has_access = $stmt->fetch();
    
    if (!$has_access) {
        // Prüfen ob es ein Freebie-Kurs ist
        $stmt = $pdo->prepare("SELECT is_freebie FROM courses WHERE id = ?");
        $stmt->execute([$lesson['course_id']]);
        $course = $stmt->fetch();
        
        if (!$course || !$course['is_freebie']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Kein Zugriff auf diesen Kurs']);
            exit;
        }
    }
    
    // Fortschritt in DB speichern (INSERT oder UPDATE)
    $stmt = $pdo->prepare("
        INSERT INTO course_progress (user_id, lesson_id, completed, completed_at)
        VALUES (?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE 
            completed = 1,
            completed_at = NOW()
    ");
    $stmt->execute([$user_id, $lesson_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Lektion als abgeschlossen markiert',
        'lesson_id' => $lesson_id
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
