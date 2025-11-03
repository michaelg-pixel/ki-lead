<?php
/**
 * API: Get Referral Statistics
 * Für User-Dashboard
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ReferralHelper.php';
require_once __DIR__ . '/../../includes/auth.php';

// Session starten und Auth prüfen
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $referral = new ReferralHelper($db);
    $userId = $_SESSION['user_id'];
    
    // Hole Statistiken
    $stats = $referral->getStats($userId);
    
    // Hole User-Daten
    $stmt = $db->prepare("
        SELECT referral_enabled, referral_code 
        FROM customers 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats) {
        // Initialisiere Stats wenn nicht vorhanden
        $stmt = $db->prepare("
            INSERT INTO referral_stats (user_id) VALUES (?)
        ");
        $stmt->execute([$userId]);
        $stats = $referral->getStats($userId);
    }
    
    // Hole letzte Klicks
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%d.%m.%Y %H:%i') as date,
            ref_code,
            fingerprint
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
            ref_code,
            email,
            confirmed,
            reward_notified
        FROM referral_leads
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$userId]);
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hole Chart-Daten (letzte 30 Tage)
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM referral_clicks
        WHERE user_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$userId]);
    $clicksChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM referral_conversions
        WHERE user_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$userId]);
    $conversionsChart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'enabled' => (bool)$user['referral_enabled'],
            'ref_code' => $user['referral_code'],
            'stats' => [
                'total_clicks' => (int)$stats['total_clicks'],
                'unique_clicks' => (int)$stats['unique_clicks'],
                'total_conversions' => (int)$stats['total_conversions'],
                'suspicious_conversions' => (int)$stats['suspicious_conversions'],
                'total_leads' => (int)$stats['total_leads'],
                'confirmed_leads' => (int)$stats['confirmed_leads'],
                'conversion_rate' => (float)$stats['conversion_rate'],
                'last_click_at' => $stats['last_click_at'],
                'last_conversion_at' => $stats['last_conversion_at']
            ],
            'recent_clicks' => $recentClicks,
            'recent_conversions' => $recentConversions,
            'leads' => $leads,
            'charts' => [
                'clicks' => $clicksChart,
                'conversions' => $conversionsChart
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Referral Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => 'Fehler beim Laden der Statistiken'
    ]);
}
