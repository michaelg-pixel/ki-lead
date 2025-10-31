<?php
/**
 * Digistore24 Webhook Handler
 * Empfängt Kunden von Digistore24 und erstellt automatisch Accounts
 */

require_once '../config/database.php';

// Logging-Funktion
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen
$rawInput = file_get_contents('php://input');
logWebhook(['raw_input' => $rawInput], 'received');

// JSON dekodieren
$webhookData = json_decode($rawInput, true);

if (!$webhookData) {
    logWebhook(['error' => 'Invalid JSON'], 'error');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

logWebhook($webhookData, 'parsed');

try {
    $pdo = getDBConnection();
    
    // Event-Type bestimmen
    $eventType = $webhookData['event'] ?? '';
    
    switch ($eventType) {
        case 'payment.success':
        case 'subscription.created':
            handleNewCustomer($pdo, $webhookData);
            break;
            
        case 'refund.created':
            handleRefund($pdo, $webhookData);
            break;
            
        default:
            logWebhook(['warning' => 'Unknown event type', 'event' => $eventType], 'warning');
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'Event received but not processed']);
            exit;
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
    
} catch (Exception $e) {
    logWebhook(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'error');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Neuen Kunden anlegen
 */
function handleNewCustomer($pdo, $data) {
    // Digistore24 Daten extrahieren
    $email = $data['buyer']['email'] ?? '';
    $name = trim(($data['buyer']['first_name'] ?? '') . ' ' . ($data['buyer']['last_name'] ?? ''));
    $orderId = $data['order_id'] ?? '';
    $productId = $data['product_id'] ?? '';
    $productName = $data['product_name'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Prüfen ob Kunde bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        logWebhook(['message' => 'User already exists', 'email' => $email], 'info');
        return;
    }
    
    // RAW-Code generieren
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Zufälliges Passwort generieren
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kunden in Datenbank anlegen
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, 
            email, 
            password, 
            role, 
            is_active,
            raw_code,
            digistore_order_id,
            digistore_product_id,
            digistore_product_name,
            source,
            created_at
        ) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'digistore24', NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $hashedPassword,
        $rawCode,
        $orderId,
        $productId,
        $productName
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Willkommens-E-Mail senden (optional)
    sendWelcomeEmail($email, $name, $password, $rawCode);
    
    logWebhook([
        'message' => 'New customer created',
        'user_id' => $userId,
        'email' => $email,
        'raw_code' => $rawCode
    ], 'success');
}

/**
 * Rückerstattung behandeln
 */
function handleRefund($pdo, $data) {
    $email = $data['buyer']['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // Kunden deaktivieren
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0, refund_date = NOW() WHERE email = ?");
    $stmt->execute([$email]);
    
    logWebhook([
        'message' => 'User deactivated due to refund',
        'email' => $email
    ], 'info');
}

/**
 * Willkommens-E-Mail senden
 */
function sendWelcomeEmail($email, $name, $password, $rawCode) {
    $subject = "Willkommen beim KI Leadsystem!";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>Willkommen, $name!</h2>
            <p>Vielen Dank für deinen Kauf! Dein Account wurde erfolgreich erstellt.</p>
            
            <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3>Deine Zugangsdaten:</h3>
                <p><strong>E-Mail:</strong> $email</p>
                <p><strong>Passwort:</strong> $password</p>
                <p><strong>RAW-Code:</strong> $rawCode</p>
            </div>
            
            <p>
                <a href='https://app.mehr-infos-jetzt.de/public/login.php' 
                   style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                    Jetzt einloggen
                </a>
            </p>
            
            <p style='color: #888; font-size: 14px; margin-top: 20px;'>
                Bitte ändere dein Passwort nach dem ersten Login!
            </p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: KI Leadsystem <noreply@mehr-infos-jetzt.de>\r\n";
    
    mail($email, $subject, $message, $headers);
}
