<?php
/**
 * 🎓 FREEBIE COURSE PUBLIC API
 * 
 * Public API Endpoints für Lead-Zugriff auf Videokurse
 * MIT DRIP-CONTENT UNTERSTÜTZUNG (unlock_after_days)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        
        /**
         * Fortschritt markieren (Lead ohne Login)
         */
        case 'mark_complete':
            $lesson_id = $input['lesson_id'] ?? 0;
            $email = trim($input['email'] ?? '');
            $completed = isset($input['completed']) ? (bool)$input['completed'] : true;
            
            if (!$lesson_id || !$email) {
                throw new Exception('Lektion ID und E-Mail erforderlich');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail-Adresse');
            }
            
            // Prüfe ob Lektion existiert
            $stmt = $pdo->prepare("SELECT id FROM freebie_course_lessons WHERE id = ?");
            $stmt->execute([$lesson_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Lektion nicht gefunden');
            }
            
            // Upsert Fortschritt
            if ($completed) {
                $stmt = $pdo->prepare("
                    INSERT INTO freebie_course_progress (lesson_id, lead_email, completed, completed_at, updated_at) 
                    VALUES (?, ?, TRUE, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE completed = TRUE, completed_at = NOW(), updated_at = NOW()
                ");
                $stmt->execute([$lesson_id, $email]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO freebie_course_progress (lesson_id, lead_email, completed, completed_at, updated_at) 
                    VALUES (?, ?, FALSE, NULL, NOW())
                    ON DUPLICATE KEY UPDATE completed = FALSE, completed_at = NULL, updated_at = NOW()
                ");
                $stmt->execute([$lesson_id, $email]);
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $completed ? 'Lektion abgeschlossen' : 'Lektion als unerledigt markiert'
            ]);
            break;
        
        default:
            throw new Exception('Ungültige Aktion: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
