<?php
/**
 * Admin API: Get User Details
 * Abrufen vollständiger Benutzerdaten für Admin-Ansicht (Kunden & Admins)
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
    
    // User ID aus GET-Parameter
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('Keine User-ID angegeben');
    }
    
    // Erst prüfen, welche Spalten existieren
    $columns = ['u.id', 'u.name', 'u.email', 'u.role', 'u.is_active', 'u.created_at'];
    
    // Optional columns - nur hinzufügen wenn sie existieren
    $optionalColumns = ['raw_code', 'referral_enabled', 'referral_code', 'company_name', 'company_email'];
    $tableColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($optionalColumns as $col) {
        if (in_array($col, $tableColumns)) {
            $columns[] = "u.$col";
        }
    }
    
    $columnsStr = implode(', ', $columns);
    
    // Benutzerdaten mit zugewiesenen Freebies abrufen (für Kunden)
    $query = "
        SELECT 
            $columnsStr,
            GROUP_CONCAT(
                DISTINCT CONCAT(f.id, ':', f.name) 
                SEPARATOR '||'
            ) as assigned_freebies,
            COUNT(DISTINCT uf.freebie_id) as freebie_count
        FROM users u
        LEFT JOIN user_freebies uf ON u.id = uf.user_id
        LEFT JOIN freebies f ON uf.freebie_id = f.id
        WHERE u.id = ?
        GROUP BY u.id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('Benutzer nicht gefunden');
    }
    
    // Defaults für fehlende Spalten setzen
    $user['raw_code'] = $user['raw_code'] ?? null;
    $user['referral_enabled'] = $user['referral_enabled'] ?? 0;
    $user['referral_code'] = $user['referral_code'] ?? null;
    $user['company_name'] = $user['company_name'] ?? null;
    $user['company_email'] = $user['company_email'] ?? null;
    $user['role'] = $user['role'] ?? 'customer';
    
    // Freebies aufbereiten (nur für Kunden relevant)
    $freebies = [];
    if (!empty($user['assigned_freebies'])) {
        $freebieData = explode('||', $user['assigned_freebies']);
        foreach ($freebieData as $item) {
            if (!empty($item) && strpos($item, ':') !== false) {
                list($id, $name) = explode(':', $item, 2);
                $freebies[] = [
                    'id' => $id,
                    'name' => $name
                ];
            }
        }
    }
    
    $user['freebies'] = $freebies;
    unset($user['assigned_freebies']);
    
    // Statistiken abrufen
    $stats = [
        'total_freebies' => (int)$user['freebie_count'],
        'last_login' => null,
        'total_downloads' => 0
    ];
    
    // Prüfen ob user_activity_log Tabelle existiert
    $tables = $pdo->query("SHOW TABLES LIKE 'user_activity_log'")->fetchAll();
    if (count($tables) > 0) {
        try {
            $loginStmt = $pdo->prepare("
                SELECT MAX(created_at) as last_login 
                FROM user_activity_log 
                WHERE user_id = ? AND action = 'login'
            ");
            $loginStmt->execute([$userId]);
            $loginData = $loginStmt->fetch(PDO::FETCH_ASSOC);
            if ($loginData && $loginData['last_login']) {
                $stats['last_login'] = $loginData['last_login'];
            }
        } catch (Exception $e) {
            // Ignorieren wenn Tabelle nicht existiert oder Fehler
        }
    }
    
    // Prüfen ob freebie_analytics Tabelle existiert (nur für Kunden)
    if ($user['role'] === 'customer') {
        $tables = $pdo->query("SHOW TABLES LIKE 'freebie_analytics'")->fetchAll();
        if (count($tables) > 0) {
            try {
                $downloadStmt = $pdo->prepare("
                    SELECT COUNT(*) as total 
                    FROM freebie_analytics 
                    WHERE user_id = ?
                ");
                $downloadStmt->execute([$userId]);
                $downloadData = $downloadStmt->fetch(PDO::FETCH_ASSOC);
                if ($downloadData) {
                    $stats['total_downloads'] = (int)$downloadData['total'];
                }
            } catch (Exception $e) {
                // Ignorieren wenn Tabelle nicht existiert oder Fehler
            }
        }
    }
    
    $user['stats'] = $stats;
    unset($user['freebie_count']);
    
    echo json_encode([
        'success' => true,
        'customer' => $user  // Backward compatibility - behalte den Key "customer"
    ]);
    
} catch (Exception $e) {
    error_log("User Get Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
