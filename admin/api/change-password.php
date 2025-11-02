<?php
session_start();

// Admin-Zugriff prüfen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

require_once '../../config/database.php';

// JSON-Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

// Validierung
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'Alle Felder sind erforderlich']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'Die neuen Passwörter stimmen nicht überein']);
    exit;
}

if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Das neue Passwort muss mindestens 8 Zeichen lang sein']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Aktuelles Passwort überprüfen
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Das aktuelle Passwort ist falsch']);
        exit;
    }
    
    // Neues Passwort hashen und speichern
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Passwort erfolgreich geändert']);
    
} catch (Exception $e) {
    error_log("Fehler beim Ändern des Passworts: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
