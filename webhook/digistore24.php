<?php
/**
 * Digistore24 Webhook Handler - MIT KURS-FREISCHALTUNG & FREEBIE-LIMITS
 * Empfängt Kunden von Digistore24 und erstellt automatisch Accounts + Kurs-Zugang + Freebie-Limits
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
        case 'purchase':
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
 * Neuen Kunden anlegen + Kurs-Zugang gewähren + Freebie-Limits setzen
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
    
    $userId = null;
    $isNewUser = false;
    
    if ($existingUser) {
        $userId = $existingUser['id'];
        logWebhook(['message' => 'User already exists, granting access', 'email' => $email, 'user_id' => $userId], 'info');
    } else {
        // Neuen User erstellen
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $productName);
        $isNewUser = true;
    }
    
    // KURS-ZUGANG GEWÄHREN
    grantCourseAccess($pdo, $userId, $productId, $email);
    
    // FREEBIE-LIMITS SETZEN (NEU!)
    setFreebieLimit($pdo, $userId, $productId, $productName);
    
    logWebhook([
        'message' => 'Customer processed successfully',
        'user_id' => $userId,
        'email' => $email,
        'new_user' => $isNewUser,
        'product_id' => $productId
    ], 'success');
}

/**
 * Neuen User erstellen
 */
function createNewUser($pdo, $email, $name, $orderId, $productId, $productName) {
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
    
    // Willkommens-E-Mail senden
    sendWelcomeEmail($email, $name, $password, $rawCode);
    
    logWebhook([
        'message' => 'New customer created',
        'user_id' => $userId,
        'email' => $email,
        'raw_code' => $rawCode
    ], 'success');
    
    return $userId;
}

/**
 * Kurs-Zugang gewähren basierend auf Produkt-ID
 */
function grantCourseAccess($pdo, $userId, $productId, $email) {
    if (empty($productId)) {
        logWebhook(['warning' => 'No product_id provided, cannot grant course access'], 'warning');
        return;
    }
    
    // Kurs anhand der Digistore-Produkt-ID finden
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE digistore_product_id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $course = $stmt->fetch();
    
    if (!$course) {
        logWebhook([
            'warning' => 'No course found for this product_id',
            'product_id' => $productId,
            'user_id' => $userId
        ], 'warning');
        return;
    }
    
    // Prüfen ob Zugang bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM course_access WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $course['id']]);
    $existingAccess = $stmt->fetch();
    
    if ($existingAccess) {
        logWebhook([
            'info' => 'Course access already exists',
            'user_id' => $userId,
            'course_id' => $course['id'],
            'course_title' => $course['title']
        ], 'info');
        return;
    }
    
    // Zugang gewähren
    $stmt = $pdo->prepare("
        INSERT INTO course_access (user_id, course_id, access_source, granted_at)
        VALUES (?, ?, 'digistore24', NOW())
    ");
    $stmt->execute([$userId, $course['id']]);
    
    logWebhook([
        'success' => 'Course access granted',
        'user_id' => $userId,
        'course_id' => $course['id'],
        'course_title' => $course['title'],
        'product_id' => $productId
    ], 'success');
    
    // E-Mail mit Kurs-Zugang senden
    sendCourseAccessEmail($email, $course['title']);
}

/**
 * NEUE FUNKTION: Freebie-Limit für Kunde setzen
 */
function setFreebieLimit($pdo, $userId, $productId, $productName) {
    if (empty($productId)) {
        logWebhook(['warning' => 'No product_id provided, cannot set freebie limit'], 'warning');
        return;
    }
    
    // Freebie-Limit aus Konfiguration holen
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM product_freebie_config 
        WHERE product_id = ? AND is_active = 1
    ");
    $stmt->execute([$productId]);
    $config = $stmt->fetch();
    
    $freebieLimit = 5; // Default
    
    if ($config) {
        $freebieLimit = $config['freebie_limit'];
        $productName = $config['product_name'] ?: $productName;
    } else {
        logWebhook([
            'info' => 'No freebie config found, using default limit',
            'product_id' => $productId,
            'default_limit' => $freebieLimit
        ], 'info');
    }
    
    // Prüfen ob bereits ein Limit existiert
    $stmt = $pdo->prepare("SELECT id, freebie_limit FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update nur wenn neues Limit höher ist
        if ($freebieLimit > $existing['freebie_limit']) {
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, product_id = ?, product_name = ?, updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$freebieLimit, $productId, $productName, $userId]);
            
            logWebhook([
                'success' => 'Freebie limit upgraded',
                'user_id' => $userId,
                'old_limit' => $existing['freebie_limit'],
                'new_limit' => $freebieLimit,
                'product_id' => $productId
            ], 'success');
        } else {
            logWebhook([
                'info' => 'Existing limit is higher or equal, not downgrading',
                'user_id' => $userId,
                'current_limit' => $existing['freebie_limit'],
                'new_limit' => $freebieLimit
            ], 'info');
        }
    } else {
        // Neues Limit erstellen
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $freebieLimit, $productId, $productName]);
        
        logWebhook([
            'success' => 'Freebie limit created',
            'user_id' => $userId,
            'limit' => $freebieLimit,
            'product_id' => $productId
        ], 'success');
    }
}

/**
 * Rückerstattung behandeln
 */
function handleRefund($pdo, $data) {
    $email = $data['buyer']['email'] ?? '';
    $productId = $data['product_id'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // User-ID finden
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        logWebhook(['warning' => 'User not found for refund', 'email' => $email], 'warning');
        return;
    }
    
    $userId = $user['id'];
    
    // Kunden deaktivieren
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0, refund_date = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Freebie-Limit auf 0 setzen
    $stmt = $pdo->prepare("UPDATE customer_freebie_limits SET freebie_limit = 0 WHERE customer_id = ?");
    $stmt->execute([$userId]);
    
    // Kurs-Zugang entfernen (falls Product-ID vorhanden)
    if (!empty($productId)) {
        $stmt = $pdo->prepare("
            DELETE ca FROM course_access ca
            JOIN courses c ON ca.course_id = c.id
            WHERE ca.user_id = ? AND c.digistore_product_id = ?
        ");
        $stmt->execute([$userId, $productId]);
        
        logWebhook([
            'message' => 'Course access revoked due to refund',
            'user_id' => $userId,
            'email' => $email,
            'product_id' => $productId
        ], 'info');
    }
    
    logWebhook([
        'message' => 'User deactivated and freebie limit reset due to refund',
        'user_id' => $userId,
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

/**
 * E-Mail mit Kurs-Zugang senden
 */
function sendCourseAccessEmail($email, $courseTitle) {
    $subject = "Dein Kurs ist jetzt freigeschaltet!";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <h2 style='color: #667eea;'>🎉 Dein Kurs wurde freigeschaltet!</h2>
            <p>Gute Neuigkeiten! Du hast jetzt Zugang zu:</p>
            
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                        padding: 20px; border-radius: 8px; margin: 20px 0; color: white;'>
                <h3 style='margin: 0;'>📚 $courseTitle</h3>
            </div>
            
            <p>Du kannst jetzt sofort mit dem Kurs beginnen!</p>
            
            <p>
                <a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=kurse' 
                   style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                    Zum Kurs
                </a>
            </p>
            
            <p style='color: #888; font-size: 14px; margin-top: 20px;'>
                Viel Erfolg beim Lernen! 🚀
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
