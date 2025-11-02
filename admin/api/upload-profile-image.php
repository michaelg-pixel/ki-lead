<?php
session_start();

// Admin-Zugriff prüfen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../config/database.php';

// Prüfen ob Datei hochgeladen wurde
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Keine Datei hochgeladen']);
    exit;
}

$file = $_FILES['profile_image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validierung
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Nur Bilddateien (JPG, PNG, GIF, WEBP) erlaubt']);
    exit;
}

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Datei zu groß (max. 5MB)']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Upload-Verzeichnis erstellen falls nicht vorhanden
    $uploadDir = '../../uploads/profile-images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Eindeutigen Dateinamen generieren
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Altes Profilbild löschen
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $oldImage = $stmt->fetchColumn();
    
    if ($oldImage && file_exists('../../' . $oldImage)) {
        unlink('../../' . $oldImage);
    }
    
    // Neue Datei hochladen
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Fehler beim Hochladen der Datei');
    }
    
    // Relativen Pfad speichern
    $relativePath = 'uploads/profile-images/' . $filename;
    
    // Datenbank aktualisieren
    $stmt = $pdo->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$relativePath, $_SESSION['user_id']]);
    
    // Aktivität loggen
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
        VALUES (?, 'profile_image_updated', 'Profilbild wurde aktualisiert', ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profilbild erfolgreich hochgeladen',
        'data' => [
            'profile_image_url' => '/' . $relativePath
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Fehler beim Hochladen des Profilbilds: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
