<?php
/**
 * API: Toggle Referral Program
 * Aktiviere/Deaktiviere Empfehlungsprogramm
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Nicht direkt ausgeben, sondern loggen

require_once __DIR__ . '/../../config/database.php';

session_start();

// Debug-Logging aktivieren
function logDebug($message) {
    error_log("[Referral Toggle] " . $message);
}

if (!isset($_SESSION['user_id'])) {
    logDebug("Unauthorized access attempt");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'Nicht angemeldet']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

try {
    $pdo = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    logDebug("User ID: " . $userId);
    
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = $input['enabled'] ?? null;
    
    logDebug("Enabled value: " . ($enabled ? 'true' : 'false'));
    
    if ($enabled === null) {
        throw new Exception('Parameter "enabled" fehlt');
    }
    
    // Prüfe ob User existiert und welche Spalten vorhanden sind
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User mit ID $userId nicht gefunden");
    }
    
    logDebug("User gefunden: " . $user['email']);
    
    // Prüfe ob referral_enabled Spalte existiert
    if (!array_key_exists('referral_enabled', $user)) {
        throw new Exception("Spalte 'referral_enabled' existiert nicht in users Tabelle");
    }
    
    // Prüfe ob ref_code Spalte existiert
    if (!array_key_exists('ref_code', $user)) {
        throw new Exception("Spalte 'ref_code' existiert nicht in users Tabelle");
    }
    
    $refCode = $user['ref_code'];
    
    // Wenn aktiviert wird, generiere ref_code falls noch nicht vorhanden
    if ($enabled) {
        if (empty($refCode)) {
            // Generiere unique ref_code
            $refCode = 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT) . strtoupper(substr(md5(uniqid($userId, true)), 0, 6));
            logDebug("Generiere neuen ref_code: " . $refCode);
            
            // Update mit ref_code
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = 1, ref_code = ?
                WHERE id = ?
            ");
            $stmt->execute([$refCode, $userId]);
        } else {
            logDebug("Verwende existierenden ref_code: " . $refCode);
            // Nur enabled Status updaten
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = 1
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        }
        
        // Prüfe ob referral_stats Tabelle existiert
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'referral_stats'");
            $tableExists = $stmt->fetch();
            
            if ($tableExists) {
                logDebug("referral_stats Tabelle existiert");
                
                // Prüfe welche Spalte verwendet wird (customer_id oder user_id)
                $stmt = $pdo->query("SHOW COLUMNS FROM referral_stats");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                logDebug("Spalten in referral_stats: " . implode(', ', $columns));
                
                $idColumn = in_array('user_id', $columns) ? 'user_id' : 'customer_id';
                logDebug("Verwende Spalte: " . $idColumn);
                
                // Initialisiere referral_stats falls noch nicht vorhanden
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO referral_stats 
                    ($idColumn, total_clicks, total_conversions, total_leads) 
                    VALUES (?, 0, 0, 0)
                ");
                $stmt->execute([$userId]);
                logDebug("referral_stats initialisiert");
            } else {
                logDebug("WARNUNG: referral_stats Tabelle existiert nicht!");
            }
        } catch (PDOException $e) {
            logDebug("Fehler bei referral_stats: " . $e->getMessage());
            // Nicht kritisch, fahre fort
        }
        
    } else {
        logDebug("Deaktiviere Empfehlungsprogramm");
        // Deaktivieren
        $stmt = $pdo->prepare("
            UPDATE users 
            SET referral_enabled = 0
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
    }
    
    logDebug("Toggle erfolgreich abgeschlossen");
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Empfehlungsprogramm aktiviert' : 'Empfehlungsprogramm deaktiviert',
        'enabled' => (bool)$enabled,
        'ref_code' => $refCode
    ]);
    
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    logDebug("PDO Error: " . $errorMsg);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'database_error',
        'message' => 'Datenbankfehler: ' . $errorMsg
    ]);
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    logDebug("General Error: " . $errorMsg);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $errorMsg
    ]);
}
