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

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');

// Validierung
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name ist erforderlich']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Gültige E-Mail-Adresse erforderlich']);
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Prüfen ob E-Mail bereits von anderem Benutzer verwendet wird
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Diese E-Mail-Adresse wird bereits verwendet']);
        exit;
    }
    
    // Profil aktualisieren
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$name, $email, $_SESSION['user_id']]);
    
    // Session-Daten aktualisieren
    $_SESSION['name'] = $name;
    $_SESSION['email'] = $email;
    
    // Aktivität loggen
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
        VALUES (?, 'profile_updated', 'Admin-Profil wurde aktualisiert', ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR'] ?? '']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profil erfolgreich aktualisiert',
        'data' => [
            'name' => $name,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Fehler beim Aktualisieren des Profils: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten']);
}
