<?php
/**
 * Admin API: Update Customer
 * Bearbeiten von Kundendaten durch Admin
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Daten aus POST-Request
    $userId = $_POST['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rawCode = trim($_POST['raw_code'] ?? '');
    $companyName = trim($_POST['company_name'] ?? '');
    $companyEmail = trim($_POST['company_email'] ?? '');
    
    // Validierung
    if (!$userId) {
        throw new Exception('Keine User-ID angegeben');
    }
    
    if (empty($name)) {
        throw new Exception('Name ist erforderlich');
    }
    
    if (empty($email)) {
        throw new Exception('E-Mail ist erforderlich');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    // Prüfen, ob Kunde existiert
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
    $checkStmt->execute([$userId]);
    if (!$checkStmt->fetch()) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Prüfen, ob E-Mail bereits von anderem User verwendet wird
    $emailCheckStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheckStmt->execute([$email, $userId]);
    if ($emailCheckStmt->fetch()) {
        throw new Exception('Diese E-Mail-Adresse wird bereits verwendet');
    }
    
    // Kundendaten aktualisieren
    $updateStmt = $pdo->prepare("
        UPDATE users 
        SET 
            name = ?,
            email = ?,
            raw_code = ?,
            company_name = ?,
            company_email = ?
        WHERE id = ? AND role = 'customer'
    ");
    
    $updateStmt->execute([
        $name,
        $email,
        $rawCode ?: null,
        $companyName ?: null,
        $companyEmail ?: null,
        $userId
    ]);
    
    // Optional: Passwort ändern (nur wenn angegeben)
    if (!empty($_POST['new_password'])) {
        $newPassword = $_POST['new_password'];
        
        if (strlen($newPassword) < 8) {
            throw new Exception('Passwort muss mindestens 8 Zeichen lang sein');
        }
        
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $passwordStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $passwordStmt->execute([$passwordHash, $userId]);
    }
    
    // Aktivitätslog (optional, falls Tabelle existiert)
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO user_activity_log (user_id, action, details, ip_address) 
            VALUES (?, 'profile_updated_by_admin', ?, ?)
        ");
        $logStmt->execute([
            $userId,
            json_encode(['updated_by' => $_SESSION['user_id'], 'name' => $_SESSION['name']]),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Ignorieren, falls Log-Tabelle nicht existiert
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Kundendaten erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    error_log("Customer Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
