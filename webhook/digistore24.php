<?php
/**
 * Digistore24 Webhook Handler - VERSION 3.0
 * Mit Source-Tracking und Konflikt-Schutz
 * 
 * Empfängt Kunden von Digistore24 und erstellt automatisch Accounts + Kurs-Zugang + Freebie-Limits
 * Respektiert manuelle Admin-Änderungen und verhindert ungewollte Überschreibungen
 */

require_once '../config/database.php';

// Logging-Funktion
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
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
            
        case 'subscription.cancelled':
        case 'subscription.expired':
            handleSubscriptionEnd($pdo, $webhookData);
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
    
    if (empty($productId)) {
        throw new Exception('Product ID is required');
    }
    
    // Produkt-Konfiguration laden
    $productConfig = getProductConfig($pdo, $productId);
    
    if (!$productConfig) {
        logWebhook([
            'warning' => 'Product not configured in admin system',
            'product_id' => $productId,
            'email' => $email
        ], 'warning');
        
        // Erstelle User trotzdem mit Default-Werten
        $productConfig = [
            'product_id' => $productId,
            'product_name' => $productName,
            'product_type' => 'custom',
            'own_freebies_limit' => 5,
            'ready_freebies_count' => 0,
            'referral_program_slots' => 1
        ];
    }
    
    // Prüfen ob Kunde bereits existiert
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    $userId = null;
    $isNewUser = false;
    
    if ($existingUser) {
        $userId = $existingUser['id'];
        logWebhook([
            'message' => 'User already exists, granting access',
            'email' => $email,
            'user_id' => $userId
        ], 'info');
    } else {
        // Neuen User erstellen
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig);
        $isNewUser = true;
    }
    
    // KURS-ZUGANG GEWÄHREN (falls konfiguriert)
    grantCourseAccess($pdo, $userId, $productId, $email);
    
    // FREEBIE-LIMITS SETZEN (mit Source-Tracking)
    setFreebieLimit($pdo, $userId, $productId, $productConfig);
    
    // EMPFEHLUNGSPROGRAMM-SLOTS SETZEN (mit Source-Tracking)
    setReferralSlots($pdo, $userId, $productConfig);
    
    // FERTIGE FREEBIES ZUWEISEN (nur bei Launch)
    if ($productConfig['ready_freebies_count'] > 0) {
        assignReadyFreebies($pdo, $userId, $productConfig['ready_freebies_count']);
    }
    
    logWebhook([
        'message' => 'Customer processed successfully',
        'user_id' => $userId,
        'email' => $email,
        'new_user' => $isNewUser,
        'product_id' => $productId,
        'product_type' => $productConfig['product_type'],
        'freebies_limit' => $productConfig['own_freebies_limit'],
        'referral_slots' => $productConfig['referral_program_slots']
    ], 'success');
}

/**
 * Produkt-Konfiguration aus Admin-System laden
 */
function getProductConfig($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT 
            product_id,
            product_name,
            product_type,
            price,
            billing_type,
            own_freebies_limit,
            ready_freebies_count,
            referral_program_slots
        FROM digistore_products 
        WHERE product_id = ? AND is_active = 1
    ");
    $stmt->execute([$productId]);
    
    return $stmt->fetch();
}

/**
 * Neuen User erstellen
 */
function createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig) {
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
        $productConfig['product_name'] ?? 'Unbekannt'
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Willkommens-E-Mail senden
    sendWelcomeEmail($email, $name, $password, $rawCode, $productConfig);
    
    logWebhook([
        'message' => 'New customer created',
        'user_id' => $userId,
        'email' => $email,
        'raw_code' => $rawCode,
        'product_type' => $productConfig['product_type'] ?? 'custom'
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
            'info' => 'No course linked to this product_id',
            'product_id' => $productId,
            'user_id' => $userId
        ], 'info');
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
 * Freebie-Limit für Kunde setzen - VERSION 3.0 mit Source-Tracking
 * Respektiert manuelle Admin-Änderungen!
 */
function setFreebieLimit($pdo, $userId, $productId, $productConfig) {
    $freebieLimit = $productConfig['own_freebies_limit'] ?? 5;
    $productName = $productConfig['product_name'] ?? 'Unbekannt';
    
    // Prüfen ob bereits ein Limit existiert
    $stmt = $pdo->prepare("
        SELECT id, freebie_limit, source, product_id 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // KRITISCH: Manuelle Änderungen NICHT überschreiben!
        if ($existing['source'] === 'manual') {
            logWebhook([
                'info' => 'Freebie limit set manually by admin - not overwriting',
                'user_id' => $userId,
                'manual_limit' => $existing['freebie_limit'],
                'webhook_would_set' => $freebieLimit
            ], 'info');
            return;
        }
        
        // Bei webhook/upgrade: Nur upgraden, nie downgraden
        if ($freebieLimit > $existing['freebie_limit']) {
            $stmt = $pdo->prepare("
                UPDATE customer_freebie_limits 
                SET freebie_limit = ?, 
                    product_id = ?, 
                    product_name = ?, 
                    source = 'webhook',
                    updated_at = NOW()
                WHERE customer_id = ?
            ");
            $stmt->execute([$freebieLimit, $productId, $productName, $userId]);
            
            logWebhook([
                'success' => 'Freebie limit upgraded via webhook',
                'user_id' => $userId,
                'old_limit' => $existing['freebie_limit'],
                'new_limit' => $freebieLimit,
                'product_id' => $productId
            ], 'success');
        } else {
            logWebhook([
                'info' => 'Webhook limit not higher - keeping existing',
                'user_id' => $userId,
                'current_limit' => $existing['freebie_limit'],
                'webhook_limit' => $freebieLimit
            ], 'info');
        }
    } else {
        // Neues Limit erstellen (immer via webhook)
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebie_limits (
                customer_id, freebie_limit, product_id, product_name, source
            ) VALUES (?, ?, ?, ?, 'webhook')
        ");
        $stmt->execute([$userId, $freebieLimit, $productId, $productName]);
        
        logWebhook([
            'success' => 'Freebie limit created via webhook',
            'user_id' => $userId,
            'limit' => $freebieLimit,
            'product_id' => $productId
        ], 'success');
    }
}

/**
 * Empfehlungsprogramm-Slots setzen - VERSION 3.0 mit Source-Tracking
 * Respektiert manuelle Admin-Änderungen und speichert Produkt-Referenz
 */
function setReferralSlots($pdo, $userId, $productConfig) {
    $slots = $productConfig['referral_program_slots'] ?? 1;
    $productId = $productConfig['product_id'] ?? '';
    $productName = $productConfig['product_name'] ?? '';
    
    try {
        // Prüfen ob bereits Slots existieren
        $stmt = $pdo->prepare("
            SELECT id, total_slots, source 
            FROM customer_referral_slots 
            WHERE customer_id = ?
        ");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // KRITISCH: Manuelle Änderungen NICHT überschreiben!
            if ($existing['source'] === 'manual') {
                logWebhook([
                    'info' => 'Referral slots set manually by admin - not overwriting',
                    'user_id' => $userId,
                    'manual_slots' => $existing['total_slots'],
                    'webhook_would_set' => $slots
                ], 'info');
                return;
            }
            
            // Bei webhook: Nur upgraden
            if ($slots > $existing['total_slots']) {
                $stmt = $pdo->prepare("
                    UPDATE customer_referral_slots 
                    SET total_slots = ?, 
                        product_id = ?,
                        product_name = ?,
                        source = 'webhook',
                        updated_at = NOW()
                    WHERE customer_id = ?
                ");
                $stmt->execute([$slots, $productId, $productName, $userId]);
                
                logWebhook([
                    'success' => 'Referral slots upgraded via webhook',
                    'user_id' => $userId,
                    'old_slots' => $existing['total_slots'],
                    'new_slots' => $slots
                ], 'success');
            }
        } else {
            // Neue Slots erstellen
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (
                    customer_id, total_slots, used_slots, 
                    product_id, product_name, source, created_at
                ) VALUES (?, ?, 0, ?, ?, 'webhook', NOW())
            ");
            $stmt->execute([$userId, $slots, $productId, $productName]);
            
            logWebhook([
                'success' => 'Referral slots created via webhook',
                'user_id' => $userId,
                'slots' => $slots,
                'product_id' => $productId
            ], 'success');
        }
    } catch (PDOException $e) {
        logWebhook([
            'error' => 'Failed to set referral slots',
            'user_id' => $userId,
            'message' => $e->getMessage()
        ], 'error');
    }
}

/**
 * Fertige Freebies zuweisen (nur für Launch-Paket)
 */
function assignReadyFreebies($pdo, $userId, $count) {
    try {
        // Hole die ersten X "fertigen" Freebies (markiert mit is_template=1)
        $stmt = $pdo->prepare("
            SELECT id, title, subtitle 
            FROM freebies 
            WHERE is_template = 1 AND is_active = 1
            LIMIT ?
        ");
        $stmt->execute([$count]);
        $templates = $stmt->fetchAll();
        
        if (empty($templates)) {
            logWebhook([
                'warning' => 'No template freebies available to assign',
                'user_id' => $userId,
                'requested_count' => $count
            ], 'warning');
            return;
        }
        
        // Weise jedes Template dem User zu
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO customer_freebies (customer_id, freebie_id, assigned_at)
            VALUES (?, ?, NOW())
        ");
        
        foreach ($templates as $template) {
            $stmt->execute([$userId, $template['id']]);
        }
        
        logWebhook([
            'success' => 'Ready freebies assigned',
            'user_id' => $userId,
            'count' => count($templates),
            'freebies' => array_column($templates, 'title')
        ], 'success');
        
    } catch (PDOException $e) {
        logWebhook([
            'error' => 'Failed to assign ready freebies',
            'user_id' => $userId,
            'message' => $e->getMessage()
        ], 'error');
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
    
    // Empfehlungs-Slots auf 0
    try {
        $stmt = $pdo->prepare("UPDATE customer_referral_slots SET total_slots = 0 WHERE customer_id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        // Tabelle existiert möglicherweise nicht
    }
    
    // Kurs-Zugang entfernen (falls Product-ID vorhanden)
    if (!empty($productId)) {
        $stmt = $pdo->prepare("
            DELETE ca FROM course_access ca
            JOIN courses c ON ca.course_id = c.id
            WHERE ca.user_id = ? AND c.digistore_product_id = ?
        ");
        $stmt->execute([$userId, $productId]);
    }
    
    logWebhook([
        'message' => 'Refund processed - access revoked',
        'user_id' => $userId,
        'email' => $email,
        'product_id' => $productId
    ], 'info');
}

/**
 * Abo-Ende behandeln (gekündigt oder abgelaufen)
 */
function handleSubscriptionEnd($pdo, $data) {
    $email = $data['buyer']['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    // User-ID finden
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        logWebhook(['warning' => 'User not found for subscription end', 'email' => $email], 'warning');
        return;
    }
    
    $userId = $user['id'];
    
    // Auf Freemium downgraden (statt komplett zu deaktivieren)
    $stmt = $pdo->prepare("UPDATE customer_freebie_limits SET freebie_limit = 2 WHERE customer_id = ?");
    $stmt->execute([$userId]);
    
    try {
        $stmt = $pdo->prepare("UPDATE customer_referral_slots SET total_slots = 0 WHERE customer_id = ?");
        $stmt->execute([$userId]);
    } catch (PDOException $e) {
        // Tabelle existiert möglicherweise nicht
    }
    
    logWebhook([
        'message' => 'Subscription ended - downgraded to freemium',
        'user_id' => $userId,
        'email' => $email
    ], 'info');
}

/**
 * Willkommens-E-Mail senden
 */
function sendWelcomeEmail($email, $name, $password, $rawCode, $productConfig) {
    $productName = $productConfig['product_name'] ?? 'KI Leadsystem';
    $productType = $productConfig['product_type'] ?? 'custom';
    
    // Features basierend auf Produkt-Typ
    $features = [];
    if (isset($productConfig['own_freebies_limit'])) {
        $features[] = "✅ <strong>{$productConfig['own_freebies_limit']} eigene Freebies</strong> erstellen";
    }
    if (isset($productConfig['ready_freebies_count']) && $productConfig['ready_freebies_count'] > 0) {
        $features[] = "🎁 <strong>{$productConfig['ready_freebies_count']} fertige Freebie-Templates</strong> sofort verfügbar";
    }
    if (isset($productConfig['referral_program_slots'])) {
        $features[] = "🚀 <strong>{$productConfig['referral_program_slots']} Empfehlungsprogramm-Slots</strong>";
    }
    
    $featuresList = !empty($features) ? '<ul style="list-style: none; padding: 0;">' . 
                    implode('', array_map(fn($f) => "<li style='padding: 8px 0;'>$f</li>", $features)) . 
                    '</ul>' : '';
    
    $subject = "🎉 Willkommen beim KI Leadsystem - $productName";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Willkommen, $name!</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0;'>Dein Account wurde erfolgreich erstellt</p>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <p>Vielen Dank für deinen Kauf von <strong>$productName</strong>!</p>
                
                $featuresList
                
                <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #667eea;'>🔑 Deine Zugangsdaten:</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280;'>E-Mail:</td>
                            <td style='padding: 8px 0; font-weight: bold;'>$email</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280;'>Passwort:</td>
                            <td style='padding: 8px 0; font-family: monospace; font-weight: bold;'>$password</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #6b7280;'>RAW-Code:</td>
                            <td style='padding: 8px 0; font-family: monospace; font-weight: bold;'>$rawCode</td>
                        </tr>
                    </table>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://app.mehr-infos-jetzt.de/public/login.php' 
                       style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                              color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                        🚀 Jetzt einloggen und loslegen
                    </a>
                </div>
                
                <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 6px; margin-top: 20px;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>⚠️ Wichtig:</strong> Bitte ändere dein Passwort nach dem ersten Login für maximale Sicherheit!
                    </p>
                </div>
                
                <p style='color: #888; font-size: 14px; margin-top: 30px; text-align: center;'>
                    Bei Fragen stehen wir dir gerne zur Verfügung!<br>
                    Viel Erfolg mit deinem KI Leadsystem! 🎯
                </p>
            </div>
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
    $subject = "🎓 Dein Kurs ist jetzt freigeschaltet!";
    
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
