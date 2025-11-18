<?php
/**
 * Vendor Activation API
 * Aktiviert den Vendor-Modus für einen Customer
 * 
 * FIX 2025-11-18: vendor_paypal_email und vendor_bank_account entfernt (Spalten existieren nicht mehr)
 */

session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Datenbank-Verbindung
require_once __DIR__ . '/../../config/database.php';

// Auth-Prüfung - verwende user_id wie im Rest des Systems
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Nicht authentifiziert'
    ]);
    exit;
}

// Nur POST-Requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Nur POST-Requests erlaubt'
    ]);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    // Input parsen
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Ungültige JSON-Daten');
    }
    
    // Validierung
    $errors = [];
    
    if (empty($data['company_name'])) {
        $errors[] = 'Firmenname ist erforderlich';
    } elseif (strlen($data['company_name']) < 3) {
        $errors[] = 'Firmenname muss mindestens 3 Zeichen lang sein';
    }
    
    if (!empty($data['website'])) {
        // Simple URL-Validierung
        if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Ungültige Website-URL';
        }
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit;
    }
    
    // Prüfe ob User bereits Vendor ist
    $stmt = $pdo->prepare("SELECT is_vendor FROM users WHERE id = ?");
    $stmt->execute([$customer_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Benutzer nicht gefunden'
        ]);
        exit;
    }
    
    if ($user['is_vendor']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Sie sind bereits ein Vendor'
        ]);
        exit;
    }
    
    // Vendor-Modus aktivieren (OHNE vendor_paypal_email und vendor_bank_account)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET 
            is_vendor = 1,
            vendor_company_name = ?,
            vendor_website = ?,
            vendor_description = ?,
            vendor_activated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([
        $data['company_name'],
        $data['website'] ?? null,
        $data['description'] ?? null,
        $customer_id
    ]);
    
    if (!$result) {
        throw new Exception('Fehler beim Aktivieren des Vendor-Modus');
    }
    
    // Log erstellen (falls logging-System vorhanden)
    error_log("Vendor aktiviert: User ID {$customer_id}, Firma: {$data['company_name']}");
    
    // Erfolg
    echo json_encode([
        'success' => true,
        'message' => 'Vendor-Modus erfolgreich aktiviert',
        'vendor_id' => $customer_id,
        'activated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    error_log('Vendor Activation Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>