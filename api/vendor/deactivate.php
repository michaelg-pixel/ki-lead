<?php
/**
 * Deactivate Vendor API
 * Deaktiviert den Vendor-Modus und entfernt Templates aus dem Marktplatz
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht authentifiziert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Prüfe ob User Vendor ist
    $stmt = $pdo->prepare("SELECT is_vendor FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_vendor']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Kein Vendor']);
        exit;
    }
    
    // Transaction starten
    $pdo->beginTransaction();
    
    try {
        // 1. Entferne alle Templates aus dem Marktplatz (unpublish)
        $stmt = $pdo->prepare("
            UPDATE vendor_reward_templates 
            SET is_published = 0, is_featured = 0
            WHERE vendor_id = ?
        ");
        $stmt->execute([$user_id]);
        
        // 2. Deaktiviere Vendor-Status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET is_vendor = 0
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Vendor-Modus wurde deaktiviert'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log('Deactivate Vendor Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>