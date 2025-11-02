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
    
    if ($action === 'get') {
        // Präferenzen abrufen
        $stmt = $pdo->prepare("
            SELECT * FROM admin_preferences WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Wenn keine Präferenzen existieren, Standard-Werte erstellen
        if (!$preferences) {
            $stmt = $pdo->prepare("
                INSERT INTO admin_preferences (user_id) VALUES (?)
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Neu angelegte Präferenzen abrufen
            $stmt = $pdo->prepare("
                SELECT * FROM admin_preferences WHERE user_id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        echo json_encode(['success' => true, 'data' => $preferences]);
        
    } elseif ($action === 'update') {
        // Präferenzen aktualisieren
        $input = json_decode(file_get_contents('php://input'), true);
        
        $notifications_new_users = isset($input['notifications_new_users']) ? (bool)$input['notifications_new_users'] : true;
        $notifications_course_purchases = isset($input['notifications_course_purchases']) ? (bool)$input['notifications_course_purchases'] : true;
        $notifications_weekly_summary = isset($input['notifications_weekly_summary']) ? (bool)$input['notifications_weekly_summary'] : true;
        $theme = $input['theme'] ?? 'dark';
        $language = $input['language'] ?? 'de';
        
        // Zuerst prüfen ob Eintrag existiert
        $stmt = $pdo->prepare("SELECT id FROM admin_preferences WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
        if ($stmt->fetch()) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE admin_preferences 
                SET notifications_new_users = ?,
                    notifications_course_purchases = ?,
                    notifications_weekly_summary = ?,
                    theme = ?,
                    language = ?,
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([
                $notifications_new_users,
                $notifications_course_purchases,
                $notifications_weekly_summary,
                $theme,
                $language,
                $_SESSION['user_id']
            ]);
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO admin_preferences 
                (user_id, notifications_new_users, notifications_course_purchases, notifications_weekly_summary, theme, language)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $notifications_new_users,
                $notifications_course_purchases,
                $notifications_weekly_summary,
                $theme,
                $language
            ]);
        }
        
        // Aktivität loggen
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
            VALUES (?, 'preferences_updated', 'Admin-Präferenzen wurden aktualisiert', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
        
        echo json_encode(['success' => true, 'message' => 'Präferenzen erfolgreich gespeichert']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
    }
    
} catch (Exception $e) {
    error_log("Fehler beim Verwalten der Präferenzen: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
