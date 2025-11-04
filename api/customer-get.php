<?php
/**
 * Admin API: Get Customer Details
 * Abrufen vollständiger Kundendaten für Admin-Ansicht
 * SICHERE VERSION - funktioniert auch ohne optionale Tabellen/Spalten
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
    
    // User ID aus GET-Parameter
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        throw new Exception('Keine User-ID angegeben');
    }
    
    // Erst prüfen, welche Spalten existieren
    $columns = ['u.id', 'u.name', 'u.email', 'u.is_active', 'u.created_at'];
    
    // Optional columns - nur hinzufügen wenn sie existieren
    $optionalColumns = ['raw_code', 'referral_enabled', 'referral_code', 'company_name', 'company_email'];
    $tableColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($optionalColumns as $col) {
        if (in_array($col, $tableColumns)) {
            $columns[] = "u.$col";
        }
    }
    
    $columnsStr = implode(', ', $columns);
    
    // Kundendaten mit zugewiesenen Freebies abrufen
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
        WHERE u.id = ? AND u.role = 'customer'
        GROUP BY u.id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Defaults für fehlende Spalten setzen
    $customer['raw_code'] = $customer['raw_code'] ?? null;
    $customer['referral_enabled'] = $customer['referral_enabled'] ?? 0;
    $customer['referral_code'] = $customer['referral_code'] ?? null;
    $customer['company_name'] = $customer['company_name'] ?? null;
    $customer['company_email'] = $customer['company_email'] ?? null;
    
    // Freebies aufbereiten
    $freebies = [];
    if (!empty($customer['assigned_freebies'])) {
        $freebieData = explode('||', $customer['assigned_freebies']);
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
    
    $customer['freebies'] = $freebies;
    unset($customer['assigned_freebies']);
    
    // Statistiken abrufen
    $stats = [
        'total_freebies' => (int)$customer['freebie_count'],
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
    
    // Prüfen ob freebie_analytics Tabelle existiert
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
    
    $customer['stats'] = $stats;
    unset($customer['freebie_count']);
    
    echo json_encode([
        'success' => true,
        'customer' => $customer
    ]);
    
} catch (Exception $e) {
    error_log("Customer Get Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
