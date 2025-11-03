<?php
/**
 * Admin API: Get User Referral Details
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

session_start();

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$userId = $_GET['user_id'] ?? null;

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'user_id_required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Hole User-Daten
    $stmt = $db->prepare("
        SELECT email, company_name, referral_code
        FROM customers
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User nicht gefunden');
    }
    
    // Hole letzte Klicks
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as date,
            ref_code
        FROM referral_clicks
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentClicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hole letzte Conversions
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as date,
            ref_code,
            source,
            suspicious,
            time_to_convert
        FROM referral_conversions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentConversions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hole Leads
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as date,
            email,
            confirmed
        FROM referral_leads
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => array_merge($user, [
            'recent_clicks' => $recentClicks,
            'recent_conversions' => $recentConversions,
            'leads' => $leads
        ])
    ]);
    
} catch (Exception $e) {
    error_log("Admin User Details Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage()
    ]);
}
