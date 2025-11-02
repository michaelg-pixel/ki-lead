<?php
session_start();

// Admin-Zugriff prüfen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../config/database.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    if ($action === 'get_sessions') {
        // Aktive Sessions abrufen
        $stmt = $pdo->prepare("
            SELECT id, ip_address, browser, device, location, last_activity, created_at
            FROM login_sessions 
            WHERE user_id = ? AND is_active = TRUE
            ORDER BY last_activity DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $sessions]);
        
    } elseif ($action === 'get_last_logins') {
        // Letzte Login-Aktivitäten
        $stmt = $pdo->prepare("
            SELECT ip_address, browser, device, location, created_at
            FROM login_sessions 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $logins]);
        
    } elseif ($action === 'terminate_session') {
        // Session beenden
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? 0;
        
        $stmt = $pdo->prepare("
            UPDATE login_sessions 
            SET is_active = FALSE 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$sessionId, $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'Session beendet']);
        
    } elseif ($action === 'terminate_all_sessions') {
        // Alle anderen Sessions beenden
        $currentSessionToken = session_id();
        
        $stmt = $pdo->prepare("
            UPDATE login_sessions 
            SET is_active = FALSE 
            WHERE user_id = ? AND session_token != ?
        ");
        $stmt->execute([$_SESSION['user_id'], $currentSessionToken]);
        
        // Aktivität loggen
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
            VALUES (?, 'sessions_terminated', 'Alle anderen Sessions wurden beendet', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true, 'message' => 'Alle anderen Sessions wurden beendet']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
    }
    
} catch (Exception $e) {
    error_log("Fehler im Session-Management: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
