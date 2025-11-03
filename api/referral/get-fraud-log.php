<?php
/**
 * Admin API: Get Fraud Log
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

try {
    $db = Database::getInstance()->getConnection();
    
    $query = "
        SELECT 
            DATE_FORMAT(created_at, '%d.%m.%Y %H:%i:%s') as date,
            fraud_type,
            ref_code,
            additional_data
        FROM referral_fraud_log
    ";
    
    if ($userId) {
        $query .= " WHERE user_id = ?";
    }
    
    $query .= " ORDER BY created_at DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    
    if ($userId) {
        $stmt->execute([$userId]);
    } else {
        $stmt->execute();
    }
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse additional_data JSON
    foreach ($logs as &$log) {
        if ($log['additional_data']) {
            $log['additional_data'] = json_decode($log['additional_data'], true);
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $logs
    ]);
    
} catch (Exception $e) {
    error_log("Admin Fraud Log Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => $e->getMessage()
    ]);
}
