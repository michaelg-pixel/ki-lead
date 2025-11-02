<?php
/**
 * Freebie Click Tracking API
 * Speichert jeden Seitenaufruf für historische Analytics
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

// Funktion für eindeutige Session-ID (Cookie-basiert für Unique-Tracking)
function getSessionId() {
    if (!isset($_COOKIE['tracking_session'])) {
        $session_id = bin2hex(random_bytes(16));
        setcookie('tracking_session', $session_id, time() + (86400 * 30), '/'); // 30 Tage
        return $session_id;
    }
    return $_COOKIE['tracking_session'];
}

// Funktion für Unique Check (basierend auf Session + Freebie)
function isUniqueClick($pdo, $freebie_id, $session_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM freebie_click_logs 
        WHERE freebie_id = ? AND session_id = ? 
        AND click_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$freebie_id, $session_id]);
    return $stmt->fetchColumn() == 0;
}

try {
    $pdo = getDBConnection();
    
    // Parameter aus Request
    $freebie_id = $_POST['freebie_id'] ?? $_GET['freebie_id'] ?? null;
    $customer_id = $_POST['customer_id'] ?? $_GET['customer_id'] ?? null;
    
    if (!$freebie_id || !$customer_id) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
        exit;
    }
    
    // Session & Unique Check
    $session_id = getSessionId();
    $is_unique = isUniqueClick($pdo, $freebie_id, $session_id);
    
    // Tracking-Daten sammeln
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    
    // IP-Anonymisierung (DSGVO)
    $ip_parts = explode('.', $ip_address);
    if (count($ip_parts) === 4) {
        $ip_parts[3] = '0'; // Letztes Oktett anonymisieren
        $ip_address = implode('.', $ip_parts);
    }
    
    // User-Agent kürzen
    if (strlen($user_agent) > 255) {
        $user_agent = substr($user_agent, 0, 255);
    }
    
    // Stored Procedure aufrufen
    $stmt = $pdo->prepare("CALL sp_track_freebie_click(?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $freebie_id,
        $customer_id,
        $is_unique ? 1 : 0,
        $ip_address,
        $user_agent,
        $referrer,
        $session_id
    ]);
    
    echo json_encode([
        'success' => true,
        'tracked' => true,
        'unique' => $is_unique,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log("Tracking Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Tracking failed',
        'message' => $e->getMessage()
    ]);
}
