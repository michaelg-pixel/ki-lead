<?php
/**
 * Admin API: Update User
 * Bearbeiten von Benutzerdaten durch Admin (Kunden & Admins)
 * ANGEPASST AN BESTEHENDES SYSTEM
 */

header('Content-Type: application/json');
session_start();

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Keine Berechtigung']);
    exit;
}

require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    // Daten aus POST-Request
    $userId = $_POST['user_id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    
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
    
    if (!in_array($role, ['customer', 'admin'])) {
        throw new Exception('Ungültige Rolle');
    }
    
    // Prüfen, ob Benutzer existiert
    $checkStmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
    $checkStmt->execute([$userId]);
    $existingUser = $checkStmt->fetch();
    if (!$existingUser) {
        throw new Exception('Benutzer nicht gefunden');
    }
    
    // Prüfen, ob E-Mail bereits von anderem User verwendet wird
    $emailCheckStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $emailCheckStmt->execute([$email, $userId]);
    if ($emailCheckStmt->fetch()) {
        throw new Exception('Diese E-Mail-Adresse wird bereits verwendet');
    }
    
    // Prüfen welche Spalten existieren
    $tableColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Basis-Update (immer vorhanden)
    $updateFields = ['name = ?', 'email = ?', 'role = ?'];
    $updateValues = [$name, $email, $role];
    
    // Optionale Felder nur hinzufügen wenn Spalte existiert
    if (in_array('raw_code', $tableColumns)) {
        $rawCode = trim($_POST['raw_code'] ?? '');
        $updateFields[] = 'raw_code = ?';
        $updateValues[] = $rawCode ?: null;
    }
    
    if (in_array('company_name', $tableColumns)) {
        $companyName = trim($_POST['company_name'] ?? '');
        $updateFields[] = 'company_name = ?';
        $updateValues[] = $companyName ?: null;
    }
    
    if (in_array('company_email', $tableColumns)) {
        $companyEmail = trim($_POST['company_email'] ?? '');
        $updateFields[] = 'company_email = ?';
        $updateValues[] = $companyEmail ?: null;
    }
    
    // User ID für WHERE clause
    $updateValues[] = $userId;
    
    // Benutzerdaten aktualisieren
    $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute($updateValues);
    
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
    $tables = $pdo->query("SHOW TABLES LIKE 'user_activity_log'")->fetchAll();
    if (count($tables) > 0) {
        try {
            $logStmt = $pdo->prepare("
                INSERT INTO user_activity_log (user_id, action, details, ip_address) 
                VALUES (?, 'profile_updated_by_admin', ?, ?)
            ");
            $logStmt->execute([
                $userId,
                json_encode(['updated_by' => $_SESSION['user_id'], 'name' => $_SESSION['name'] ?? 'Admin']),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Ignorieren, falls Log-Tabelle Probleme hat
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Benutzerdaten erfolgreich aktualisiert'
    ]);
    
} catch (Exception $e) {
    error_log("User Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
