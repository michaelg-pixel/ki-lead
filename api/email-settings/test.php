<?php
/**
 * API Endpoint: Email-Marketing API-Verbindung testen
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/security.php';
require_once __DIR__ . '/../../customer/includes/EmailProviders.php';

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
    
    // Aktive API-Einstellungen laden
    $stmt = $pdo->prepare("
        SELECT * FROM customer_email_api_settings 
        WHERE customer_id = ? AND is_active = TRUE
        LIMIT 1
    ");
    $stmt->execute([$customer_id]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        throw new Exception('Keine API-Einstellungen gefunden');
    }
    
    // Custom Settings parsen
    $customSettings = json_decode($settings['custom_settings'] ?? '{}', true);
    
    // Provider-Instanz erstellen
    $provider = EmailProviderFactory::create(
        $settings['provider'],
        $settings['api_key'],
        $customSettings
    );
    
    // Verbindung testen
    $result = $provider->testConnection();
    
    if ($result['success']) {
        // Verifizierung in DB speichern
        $stmt = $pdo->prepare("
            UPDATE customer_email_api_settings SET
                is_verified = TRUE,
                last_verified_at = NOW(),
                verification_error = NULL
            WHERE id = ?
        ");
        $stmt->execute([$settings['id']]);
        
        // API-Log erstellen
        $stmt = $pdo->prepare("
            INSERT INTO email_api_logs (
                customer_id,
                provider,
                endpoint,
                method,
                response_code,
                success,
                duration_ms
            ) VALUES (?, ?, ?, ?, ?, TRUE, ?)
        ");
        $stmt->execute([
            $customer_id,
            $settings['provider'],
            'test-connection',
            'GET',
            200,
            50
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'details' => $result['details'] ?? null
        ]);
    } else {
        // Fehler in DB speichern
        $stmt = $pdo->prepare("
            UPDATE customer_email_api_settings SET
                is_verified = FALSE,
                verification_error = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $result['message'],
            $settings['id']
        ]);
        
        // API-Log erstellen
        $stmt = $pdo->prepare("
            INSERT INTO email_api_logs (
                customer_id,
                provider,
                endpoint,
                method,
                response_code,
                success,
                error_message
            ) VALUES (?, ?, ?, ?, ?, FALSE, ?)
        ");
        $stmt->execute([
            $customer_id,
            $settings['provider'],
            'test-connection',
            'GET',
            500,
            $result['message']
        ]);
        
        throw new Exception($result['message']);
    }
    
} catch (Exception $e) {
    error_log("Email API Test Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
