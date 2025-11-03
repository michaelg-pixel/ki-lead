<?php
/**
 * API: Update Company Information
 * Aktualisiere Firmendaten für Impressum in E-Mails
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $userId = $_SESSION['user_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $companyName = $input['company_name'] ?? null;
    $companyEmail = $input['company_email'] ?? null;
    $companyImprint = $input['company_imprint_html'] ?? null;
    
    // Validierung
    if ($companyEmail && !filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige E-Mail-Adresse');
    }
    
    // XSS-Schutz für Impressum (nur bestimmte Tags erlauben)
    if ($companyImprint) {
        $companyImprint = strip_tags(
            $companyImprint, 
            '<p><br><strong><b><em><i><u><a><ul><ol><li><span><div>'
        );
    }
    
    $stmt = $db->prepare("
        UPDATE customers 
        SET 
            company_name = ?,
            company_email = ?,
            company_imprint_html = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $companyName,
        $companyEmail,
        $companyImprint,
        $userId
    ]);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Firmendaten erfolgreich aktualisiert',
        'data' => [
            'company_name' => $companyName,
            'company_email' => $companyEmail,
            'company_imprint_html' => $companyImprint
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Company Update Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'invalid_request',
        'message' => $e->getMessage()
    ]);
}
