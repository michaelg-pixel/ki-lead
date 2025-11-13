<?php
/**
 * Update Vendor Payment Info API
 * Aktualisiert Auszahlungsinformationen (PayPal, Bank)
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Prüfe ob User Vendor ist
    $stmt = $pdo->prepare("SELECT is_vendor FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['is_vendor']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Kein Vendor']);
        exit;
    }
    
    // Validierung PayPal Email
    $paypal_email = $input['vendor_paypal_email'] ?? null;
    if ($paypal_email && !filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Ungültige PayPal E-Mail-Adresse');
    }
    
    // Update Auszahlungsinformationen
    $stmt = $pdo->prepare("
        UPDATE users SET
            vendor_paypal_email = ?,
            vendor_bank_account = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $paypal_email,
        $input['vendor_bank_account'] ?? null,
        $user_id
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Auszahlungsinformationen erfolgreich gespeichert'
    ]);
    
} catch (PDOException $e) {
    error_log('Update Payment Info Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>