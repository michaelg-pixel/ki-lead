<?php
/**
 * API: Toggle Referral Program
 * Aktiviere/Deaktiviere Empfehlungsprogramm
 * 
 * UPDATED: Nutzt sichere Session-Konfiguration + prüft registration ODER mailgun_consent
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Sichere Session-Konfiguration laden
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../config/database.php';

// Starte sichere Session
startSecureSession();

function logDebug($message) {
    error_log("[Referral Toggle] " . $message);
}

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    logDebug("Unauthorized access attempt - User ID: " . ($_SESSION['user_id'] ?? 'none') . ", Role: " . ($_SESSION['role'] ?? 'none'));
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized', 'message' => 'Nicht angemeldet oder keine Berechtigung']);
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
    
    // Prüfe ob User existiert und ob AVV-Zustimmung vorhanden ist
    // WICHTIG: Prüfe auf 'registration' ODER 'mailgun_consent'
    $stmt = $pdo->prepare("
        SELECT 
            u.*,
            (
                SELECT COUNT(*) 
                FROM av_contract_acceptances 
                WHERE user_id = u.id 
                AND acceptance_type IN ('registration', 'mailgun_consent')
            ) as av_consent_given
        FROM users u
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User mit ID $userId nicht gefunden");
    }
    
    logDebug("User gefunden: " . $user['email'] . " - AVV Consent: " . $user['av_consent_given']);
    
    // Prüfe AVV-Zustimmung nur beim Aktivieren
    if ($enabled && $user['av_consent_given'] == 0) {
        logDebug("AVV-Zustimmung fehlt für User " . $userId);
        throw new Exception('AVV-Zustimmung erforderlich. Bitte akzeptiere erst die Nutzungsbedingungen auf der Empfehlungsprogramm-Seite.');
    }
    
    $refCode = $user['ref_code'];
    
    // Wenn aktiviert wird, generiere ref_code falls noch nicht vorhanden
    if ($enabled) {
        if (empty($refCode)) {
            // Generiere unique ref_code
            $refCode = 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT) . strtoupper(substr(md5(uniqid($userId, true)), 0, 6));
            logDebug("Generiere neuen ref_code: " . $refCode);
            
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = 1, ref_code = ?
                WHERE id = ?
            ");
            $stmt->execute([$refCode, $userId]);
        } else {
            logDebug("Verwende existierenden ref_code: " . $refCode);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = 1
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
        }
        
        // Initialisiere referral_stats
        try {
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO referral_stats 
                (user_id, total_clicks, total_conversions, total_leads) 
                VALUES (?, 0, 0, 0)
            ");
            $stmt->execute([$userId]);
            logDebug("referral_stats initialisiert");
        } catch (PDOException $e) {
            logDebug("WARNUNG bei referral_stats: " . $e->getMessage());
        }
        
    } else {
        logDebug("Deaktiviere Empfehlungsprogramm");
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