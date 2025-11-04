<?php
/**
 * Admin API: Get Customer Details
 * Abrufen vollständiger Kundendaten für Admin-Ansicht
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
    
    // Kundendaten mit zugewiesenen Freebies abrufen
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.name,
            u.email,
            u.raw_code,
            u.is_active,
            u.created_at,
            u.referral_enabled,
            u.referral_code,
            u.company_name,
            u.company_email,
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
    ");
    
    $stmt->execute([$userId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Kunde nicht gefunden');
    }
    
    // Freebies aufbereiten
    $freebies = [];
    if (!empty($customer['assigned_freebies'])) {
        $freebieData = explode('||', $customer['assigned_freebies']);
        foreach ($freebieData as $item) {
            if (!empty($item)) {
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
    
    // Letzten Login abrufen (falls tracking existiert)
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
    
    // Download-Statistiken (falls Tracking vorhanden)
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
