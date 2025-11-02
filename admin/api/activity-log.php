<?php
session_start();

// Admin-Zugriff prüfen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../config/database.php';

try {
    $pdo = getDBConnection();
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    
    // Aktivitäten abrufen
    $stmt = $pdo->prepare("
        SELECT 
            action_type,
            action_description,
            ip_address,
            created_at
        FROM admin_activity_log 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$_SESSION['user_id'], $limit]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Action-Type für bessere Darstellung übersetzen
    $actionTypeLabels = [
        'system_login' => 'System-Anmeldung',
        'profile_updated' => 'Profil aktualisiert',
        'profile_image_updated' => 'Profilbild aktualisiert',
        'password_changed' => 'Passwort geändert',
        'preferences_updated' => 'Einstellungen aktualisiert',
        'sessions_terminated' => 'Sessions beendet',
        'user_created' => 'Benutzer erstellt',
        'user_updated' => 'Benutzer aktualisiert',
        'user_deleted' => 'Benutzer gelöscht',
        'course_created' => 'Kurs erstellt',
        'course_updated' => 'Kurs aktualisiert',
        'course_deleted' => 'Kurs gelöscht',
        'freebie_created' => 'Freebie erstellt',
        'freebie_updated' => 'Freebie aktualisiert',
        'freebie_deleted' => 'Freebie gelöscht'
    ];
    
    // Icons für verschiedene Action-Types
    $actionTypeIcons = [
        'system_login' => '🔐',
        'profile_updated' => '👤',
        'profile_image_updated' => '🖼️',
        'password_changed' => '🔒',
        'preferences_updated' => '⚙️',
        'sessions_terminated' => '🚪',
        'user_created' => '➕',
        'user_updated' => '✏️',
        'user_deleted' => '🗑️',
        'course_created' => '📚',
        'course_updated' => '📝',
        'course_deleted' => '❌',
        'freebie_created' => '🎁',
        'freebie_updated' => '✨',
        'freebie_deleted' => '🗑️'
    ];
    
    // Aktivitäten anreichern
    foreach ($activities as &$activity) {
        $activity['label'] = $actionTypeLabels[$activity['action_type']] ?? $activity['action_type'];
        $activity['icon'] = $actionTypeIcons[$activity['action_type']] ?? '📌';
    }
    
    echo json_encode(['success' => true, 'data' => $activities]);
    
} catch (Exception $e) {
    error_log("Fehler beim Abrufen der Aktivitäten: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
