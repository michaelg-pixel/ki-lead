<?php
/**
 * Admin API: Update Customer
 * Bearbeiten von Kundendaten durch Admin
 * SICHERE VERSION - funktioniert auch ohne optionale Spalten
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
    
    // Prüfen welche Spalten existieren
    $tableColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    // Basis-Update (immer vorhanden)
    $updateFields = ['name = ?', 'email = ?'];
    $updateValues = [$name, $email];
    
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
    
    // Kundendaten aktualisieren
    $updateQuery = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ? AND role = 'customer'";
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
