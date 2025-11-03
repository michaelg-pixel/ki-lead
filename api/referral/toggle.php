<?php
/**
 * API: Toggle Referral Program
 * Aktiviere/Deaktiviere Empfehlungsprogramm
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $enabled = $input['enabled'] ?? null;
    
    if ($enabled === null) {
        throw new Exception('Parameter "enabled" fehlt');
    }
    
    // Wenn aktiviert wird, generiere ref_code falls noch nicht vorhanden
    if ($enabled) {
        // Prüfe ob ref_code bereits existiert
        $stmt = $pdo->prepare("SELECT ref_code FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (empty($user['ref_code'])) {
            // Generiere unique ref_code
            $refCode = 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT) . strtoupper(substr(md5(uniqid($userId, true)), 0, 6));
            
            // Update mit ref_code
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = ?, ref_code = ?
                WHERE id = ?
            ");
            $stmt->execute([1, $refCode, $userId]);
        } else {
            // Nur enabled Status updaten
            $stmt = $pdo->prepare("
                UPDATE users 
                SET referral_enabled = ?
                WHERE id = ?
            ");
            $stmt->execute([1, $userId]);
            $refCode = $user['ref_code'];
        }
        
        // Initialisiere referral_stats falls noch nicht vorhanden
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO referral_stats (customer_id, total_clicks, total_conversions, total_leads) 
            VALUES (?, 0, 0, 0)
        ");
        $stmt->execute([$userId]);
        
    } else {
        // Deaktivieren
        $stmt = $pdo->prepare("
            UPDATE users 
            SET referral_enabled = ?
            WHERE id = ?
        ");
        $stmt->execute([0, $userId]);
        $refCode = null;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $enabled ? 'Empfehlungsprogramm aktiviert' : 'Empfehlungsprogramm deaktiviert',
        'enabled' => (bool)$enabled,
        'ref_code' => $refCode
    ]);
    
} catch (Exception $e) {
    error_log("Referral Toggle Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}
