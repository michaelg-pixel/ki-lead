<?php
/**
 * API Endpoint: Email-Marketing API-Einstellungen löschen
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';

// Session starten
startSecureSession();

// Auth Check
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$customer_id = $_SESSION['user_id'] ?? null;
if (!$customer_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Keine User ID']);
    exit;
}

try {
    // DB-Verbindung
    $pdo = getDBConnection();
    
    // Alle API-Einstellungen des Kunden löschen
    $stmt = $pdo->prepare("
        DELETE FROM customer_email_api_settings 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    
    $deletedRows = $stmt->rowCount();
    
    if ($deletedRows > 0) {
        error_log("Email API Settings deleted for customer {$customer_id}");
        
        echo json_encode([
            'success' => true,
            'message' => 'API-Einstellungen erfolgreich gelöscht'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Keine API-Einstellungen zum Löschen gefunden'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Email API Settings Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler beim Löschen'
    ]);
}
