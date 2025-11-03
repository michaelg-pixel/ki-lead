<?php
/**
 * Customer API: Get Profile
 * Abrufen der Customer-Profildaten inkl. Referral-Informationen
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

session_start();

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $customerId = $_SESSION['customer_id'];
    
    $stmt = $db->prepare("
        SELECT 
            id,
            email,
            status,
            referral_enabled,
            referral_code,
            company_name,
            company_email,
            company_imprint_html,
            created_at
        FROM customers
        WHERE id = ?
    ");
    
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        throw new Exception('Customer nicht gefunden');
    }
    
    // Entferne sensible Daten für API-Response
    unset($customer['password']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $customer
    ]);
    
} catch (Exception $e) {
    error_log("Customer Profile Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'server_error',
        'message' => 'Fehler beim Laden des Profils'
    ]);
}
