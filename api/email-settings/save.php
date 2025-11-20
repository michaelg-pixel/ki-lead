<?php
/**
 * API Endpoint: Email-Marketing API-Einstellungen speichern
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
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

// POST-Daten lesen
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ungültige Eingabedaten']);
    exit;
}

// Pflichtfelder prüfen
if (empty($input['provider']) || empty($input['api_key'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Provider und API-Key sind erforderlich']);
    exit;
}

try {
    // DB-Verbindung
    $pdo = getDBConnection();
    
    // Provider validieren
    $supportedProviders = EmailProviderFactory::getSupportedProviders();
    if (!isset($supportedProviders[$input['provider']])) {
        throw new Exception('Ungültiger Provider');
    }
    
    // Custom Settings als JSON vorbereiten
    $customSettings = [];
    
    // WICHTIG: api_url muss in custom_settings gespeichert werden!
    if (isset($input['api_url']) && !empty($input['api_url'])) {
        $customSettings['api_url'] = $input['api_url'];
    }
    
    // Weitere optionale Felder
    $optionalFields = ['username', 'password', 'account_url', 'base_url', 'sender_email', 'sender_name'];
    foreach ($optionalFields as $field) {
        if (isset($input[$field])) {
            $customSettings[$field] = $input[$field];
        }
    }
    
    // Prüfen ob bereits eine Konfiguration existiert
    $stmt = $pdo->prepare("
        SELECT id FROM customer_email_api_settings 
        WHERE customer_id = ? AND provider = ?
    ");
    $stmt->execute([$customer_id, $input['provider']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE customer_email_api_settings SET
                api_key = ?,
                api_secret = ?,
                start_tag = ?,
                list_id = ?,
                campaign_id = ?,
                double_optin_enabled = ?,
                double_optin_form_id = ?,
                custom_settings = ?,
                is_active = TRUE,
                is_verified = FALSE,
                updated_at = NOW()
            WHERE customer_id = ? AND provider = ?
        ");
        
        $stmt->execute([
            $input['api_key'],
            $input['api_secret'] ?? null,
            $input['start_tag'] ?? null,
            $input['list_id'] ?? null,
            $input['campaign_id'] ?? null,
            $input['double_optin_enabled'] ? 1 : 0,
            $input['double_optin_form_id'] ?? null,
            json_encode($customSettings),
            $customer_id,
            $input['provider']
        ]);
        
        $message = 'API-Einstellungen aktualisiert';
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO customer_email_api_settings (
                customer_id,
                provider,
                api_key,
                api_secret,
                start_tag,
                list_id,
                campaign_id,
                double_optin_enabled,
                double_optin_form_id,
                custom_settings,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
        ");
        
        $stmt->execute([
            $customer_id,
            $input['provider'],
            $input['api_key'],
            $input['api_secret'] ?? null,
            $input['start_tag'] ?? null,
            $input['list_id'] ?? null,
            $input['campaign_id'] ?? null,
            $input['double_optin_enabled'] ? 1 : 0,
            $input['double_optin_form_id'] ?? null,
            json_encode($customSettings)
        ]);
        
        $message = 'API-Einstellungen gespeichert';
    }
    
    // Log
    error_log("Email API Settings saved for customer {$customer_id}, provider: {$input['provider']}");
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (PDOException $e) {
    error_log("Email API Settings Error: " . $e->getMessage());
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
