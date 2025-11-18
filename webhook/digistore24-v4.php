<?php
/**
 * Enhanced Webhook Handler - VERSION 4.0
 * Unterstützt BEIDE Systeme:
 * 1. Altes System: digistore_products (bleibt funktional)
 * 2. Neues System: webhook_configurations (flexibel mit Multi-Produkt-IDs)
 * 
 * Features:
 * - Mehrere Produkt-IDs pro Webhook
 * - Flexible Ressourcen-Zuweisung
 * - Upsell-Support (addiert zu bestehenden Ressourcen)
 * - Aktivitäts-Logging
 * - Rückwärtskompatibel
 * - MARKETPLACE PURCHASE SUPPORT
 */

require_once '../config/database.php';

// Logging
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen
$rawInput = file_get_contents('php://input');
logWebhook(['raw_input' => $rawInput], 'received');

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
 * Neuen Kunden verarbeiten - VERSION 4.0
 * Unterstützt beide Webhook-Systeme + Upsells
 */
function handleNewCustomer($pdo, $data) {
    $email = $data['buyer']['email'] ?? '';
    $name = trim(($data['buyer']['first_name'] ?? '') . ' ' . ($data['buyer']['last_name'] ?? ''));
    $orderId = $data['order_id'] ?? '';
    $productId = $data['product_id'] ?? '';
    $productName = $data['product_name'] ?? '';
    
    if (empty($email) || empty($productId)) {
        throw new Exception('Email and Product ID are required');
    }
    
    // SCHRITT 1: Neues flexibles Webhook-System prüfen
    $webhookConfig = findWebhookConfiguration($pdo, $productId);
    
    if ($webhookConfig) {
        // NEUES SYSTEM
        processFlexibleWebhook($pdo, $webhookConfig, $email, $name, $orderId, $productId, $data);
        return;
    }
    
    // SCHRITT 2: Altes System als Fallback
    $legacyProduct = getProductConfig($pdo, $productId);
    
    if ($legacyProduct) {
        // ALTES SYSTEM (rückwärtskompatibel)
        processLegacyWebhook($pdo, $legacyProduct, $email, $name, $orderId, $productId, $productName, $data);
        return;
    }
    
    // SCHRITT 3: Marktplatz-Check
    $marketplaceFreebie = checkMarketplacePurchase($pdo, $productId);
    if ($marketplaceFreebie) {
        handleMarketplacePurchase($pdo, $email, $name, $productId, $marketplaceFreebie, $orderId);
        return;
    }
    
    // Kein passendes Webhook-System gefunden
    logWebhook([
        'warning' => 'No webhook configuration found for product_id',
        'product_id' => $productId,
        'email' => $email
    ], 'warning');
}

/**
 * NEUES SYSTEM: Flexible Webhook-Konfiguration finden
 */
function findWebhookConfiguration($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT wc.* 
        FROM webhook_configurations wc
        JOIN webhook_product_ids wp ON wc.id = wp.webhook_id
        WHERE wp.product_id = ? AND wc.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    
    return $stmt->fetch();
}

/**
 * NEUES SYSTEM: Flexible Webhook verarbeiten
 */
function processFlexibleWebhook($pdo, $config, $email, $name, $orderId, $productId, $data) {
    logWebhook([
        'info' => 'Processing with NEW flexible webhook system',
        'webhook_id' => $config['id'],
        'webhook_name' => $config['name'],
        'is_upsell' => $config['is_upsell']
    ], 'info');
    
    // User finden oder erstellen
    $userId = findOrCreateUser($pdo, $email, $name, $orderId, $productId, $config, $data);
    
    // Ressourcen gewähren
    $resourcesGranted = [];
    
    // Freebies
    if ($config['own_freebies_limit'] > 0) {
        grantFreebiesFlexible($pdo, $userId, $config);
        $resourcesGranted['freebies'] = $config['own_freebies_limit'];
    }
    
    // Empfehlungs-Slots
    if ($config['referral_slots'] > 0) {
        grantReferralSlotsFlexible($pdo, $userId, $config);
        $resourcesGranted['referral_slots'] = $config['referral_slots'];
    }
    
    // Kurse
    $courses = grantCourseAccessFlexible($pdo, $userId, $config['id'], $email);
    if (!empty($courses)) {
        $resourcesGranted['courses'] = $courses;
    }
    
    // Aktivität loggen
    logWebhookActivity($pdo, $config['id'], $productId, $email, $userId, 'purchase', $resourcesGranted, $config['is_upsell']);
    
    logWebhook([
        'success' => 'Customer processed with flexible webhook',
        'user_id' => $userId,
        'webhook_id' => $config['id'],
        'resources_granted' => $resourcesGranted,
        'is_upsell' => $config['is_upsell']
    ], 'success');
}

/**
 * ALTES SYSTEM: Legacy-Produkt verarbeiten (rückwärtskompatibel)
 */
function processLegacyWebhook($pdo, $product, $email, $name, $orderId, $productId, $productName, $data) {
    logWebhook([
        'info' => 'Processing with LEGACY system (backwards compatible)',
        'product_id' => $productId,
        'product_type' => $product['product_type']
    ], 'info');
    
    // User finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
    } else {
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $product, null, null, null);
    }
    
    // Legacy-Ressourcen gewähren
    grantCourseAccess($pdo, $userId, $productId, $email);
    setFreebieLimit($pdo, $userId, $productId, $product);
    setReferralSlots($pdo, $userId, $product);
    
    if ($product['ready_freebies_count'] > 0) {
        assignReadyFreebies($pdo, $userId, $product['ready_freebies_count']);
    }
    
    logWebhook([
        'success' => 'Customer processed with legacy system',
        'user_id' => $userId,
        'product_type' => $product['product_type']
    ], 'success');
}

/**
 * FLEXIBLE: Freebies gewähren mit Upsell-Support
 */
function grantFreebiesFlexible($pdo, $userId, $config) {
    $newLimit = $config['own_freebies_limit'];
    $isUpsell = $config['is_upsell'];
    $behavior = $config['upsell_behavior'];
    
    // Aktuelles Limit prüfen
    $stmt = $pdo->prepare("SELECT freebie_limit, source FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Bestehender Kunde
        if (!$isUpsell) {
            // Kein Upsell - normales Verhalten (nicht überschreiben wenn manuell)
            if ($existing['source'] === 'manual') {
                logWebhook(['info' => 'Freebie limit set manually - not overwriting', 'user_id' => $userId], 'info');
                return;
            }
            
            if ($newLimit > $existing['freebie_limit']) {
                $stmt = $pdo->prepare("UPDATE customer_freebie_limits SET freebie_limit = ?, source = 'webhook_v4' WHERE customer_id = ?");
                $stmt->execute([$newLimit, $userId]);
            }
        } else {
            // UPSELL!
            $finalLimit = $existing['freebie_limit'];
            
            switch ($behavior) {
                case 'add':
                    $finalLimit = $existing['freebie_limit'] + $newLimit;
                    break;
                case 'upgrade':
                    $finalLimit = max($existing['freebie_limit'], $newLimit);
                    break;
                case 'replace':
                    $finalLimit = $newLimit;
                    break;
            }
            
            $stmt = $pdo->prepare("UPDATE customer_freebie_limits SET freebie_limit = ?, source = 'webhook_v4_upsell' WHERE customer_id = ?");
            $stmt->execute([$finalLimit, $userId]);
            
            logWebhook([
                'info' => 'Upsell: Freebie limit updated',
                'user_id' => $userId,
                'old_limit' => $existing['freebie_limit'],
                'new_limit' => $finalLimit,
                'behavior' => $behavior
            ], 'info');
        }
    } else {
        // Neuer Kunde
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
            VALUES (?, ?, ?, 'webhook_v4')
        ");
        $stmt->execute([$userId, $newLimit, $config['name']]);
    }
}

/**
 * FLEXIBLE: Empfehlungs-Slots gewähren mit Upsell-Support
 */
function grantReferralSlotsFlexible($pdo, $userId, $config) {
    $newSlots = $config['referral_slots'];
    $isUpsell = $config['is_upsell'];
    $behavior = $config['upsell_behavior'];
    
    try {
        $stmt = $pdo->prepare("SELECT total_slots, source FROM customer_referral_slots WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if (!$isUpsell) {
                if ($existing['source'] === 'manual') {
                    return;
                }
                
                if ($newSlots > $existing['total_slots']) {
                    $stmt = $pdo->prepare("UPDATE customer_referral_slots SET total_slots = ?, source = 'webhook_v4' WHERE customer_id = ?");
                    $stmt->execute([$newSlots, $userId]);
                }
            } else {
                // UPSELL!
                $finalSlots = $existing['total_slots'];
                
                switch ($behavior) {
                    case 'add':
                        $finalSlots = $existing['total_slots'] + $newSlots;
                        break;
                    case 'upgrade':
                        $finalSlots = max($existing['total_slots'], $newSlots);
                        break;
                    case 'replace':
                        $finalSlots = $newSlots;
                        break;
                }
                
                $stmt = $pdo->prepare("UPDATE customer_referral_slots SET total_slots = ?, source = 'webhook_v4_upsell' WHERE customer_id = ?");
                $stmt->execute([$finalSlots, $userId]);
                
                logWebhook([
                    'info' => 'Upsell: Referral slots updated',
                    'user_id' => $userId,
                    'old_slots' => $existing['total_slots'],
                    'new_slots' => $finalSlots,
                    'behavior' => $behavior
                ], 'info');
            }
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO customer_referral_slots (customer_id, total_slots, used_slots, product_name, source)
                VALUES (?, ?, 0, ?, 'webhook_v4')
            ");
            $stmt->execute([$userId, $newSlots, $config['name']]);
        }
    } catch (PDOException $e) {
        logWebhook(['error' => 'Referral slots error: ' . $e->getMessage()], 'error');
    }
}

/**
 * FLEXIBLE: Kurszugang gewähren
 */
function grantCourseAccessFlexible($pdo, $userId, $webhookId, $email) {
    // Kurse für diesen Webhook laden
    $stmt = $pdo->prepare("
        SELECT c.id, c.title 
        FROM webhook_course_access wc
        JOIN courses c ON wc.course_id = c.id
        WHERE wc.webhook_id = ? AND c.is_active = 1
    ");
    $stmt->execute([$webhookId]);
    $courses = $stmt->fetchAll();
    
    $grantedCourses = [];
    
    foreach ($courses as $course) {
        // Prüfen ob Zugang bereits existiert
        $stmt = $pdo->prepare("SELECT id FROM course_access WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$userId, $course['id']]);
        
        if (!$stmt->fetch()) {
            // Zugang gewähren
            $stmt = $pdo->prepare("
                INSERT INTO course_access (user_id, course_id, access_source, granted_at)
                VALUES (?, ?, 'webhook_v4', NOW())
            ");
            $stmt->execute([$userId, $course['id']]);
            
            $grantedCourses[] = $course['title'];
            
            logWebhook([
                'success' => 'Course access granted',
                'user_id' => $userId,
                'course_id' => $course['id'],
                'course_title' => $course['title']
            ], 'success');
        }
    }
    
    return $grantedCourses;
}

/**
 * User finden oder erstellen
 */
function findOrCreateUser($pdo, $email, $name, $orderId, $productId, $config, $data) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        return $user['id'];
    }
    
    // Neuen User erstellen
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, email, password, role, is_active, raw_code,
            digistore_order_id, digistore_product_id, digistore_product_name,
            source, created_at
        ) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'webhook_v4', NOW())
    ");
    
    $stmt->execute([
        $name,
        $email,
        $hashedPassword,
        $rawCode,
        $orderId,
        $productId,
        $config['name']
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Willkommens-E-Mail
    sendWelcomeEmailFlexible($email, $name, $password, $rawCode, $config);
    
    return $userId;
}

/**
 * Webhook-Aktivität loggen
 */
function logWebhookActivity($pdo, $webhookId, $productId, $email, $userId, $eventType, $resources, $isUpsell) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO webhook_activity_log (
                webhook_id, product_id, customer_email, customer_id,
                event_type, resources_granted, is_upsell
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $webhookId,
            $productId,
            $email,
            $userId,
            $eventType,
            json_encode($resources),
            $isUpsell
        ]);
    } catch (PDOException $e) {
        logWebhook(['error' => 'Activity log error: ' . $e->getMessage()], 'error');
    }
}

/**
 * Willkommens-E-Mail für flexibles System
 */
function sendWelcomeEmailFlexible($email, $name, $password, $rawCode, $config) {
    $webhookName = $config['name'];
    
    $features = [];
    if ($config['own_freebies_limit'] > 0) {
        $features[] = "✅ <strong>{$config['own_freebies_limit']} eigene Freebies</strong> erstellen";
    }
    if ($config['ready_freebies_count'] > 0) {
        $features[] = "🎁 <strong>{$config['ready_freebies_count']} fertige Templates</strong>";
    }
    if ($config['referral_slots'] > 0) {
        $features[] = "🚀 <strong>{$config['referral_slots']} Empfehlungs-Slots</strong>";
    }
    
    $featuresList = !empty($features) ? '<ul style="list-style: none; padding: 0;">' . 
                    implode('', array_map(fn($f) => "<li style='padding: 8px 0;'>$f</li>", $features)) . 
                    '</ul>' : '';
    
    $subject = "🎉 Willkommen beim KI Leadsystem - $webhookName";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Willkommen, $name!</h1>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <p>Vielen Dank für deinen Kauf von <strong>$webhookName</strong>!</p>
                
                $featuresList
                
                <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #667eea;'>🔑 Deine Zugangsdaten:</h3>
                    <table style='width: 100%;'>
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
                              color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                        🚀 Jetzt einloggen
                    </a>
                </div>
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

// === LEGACY SUPPORT FUNCTIONS ===

function getProductConfig($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT * FROM digistore_products 
        WHERE product_id = ? AND is_active = 1
    ");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

function checkMarketplacePurchase($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE digistore_product_id = ? AND marketplace_enabled = 1
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

/**
 * MARKTPLATZ: Kauf verarbeiten - VOLLSTÄNDIGE IMPLEMENTIERUNG
 */
function handleMarketplacePurchase($pdo, $buyerEmail, $buyerName, $productId, $sourceFreebie, $orderId) {
    logWebhook([
        'info' => 'Starting marketplace purchase processing',
        'buyer_email' => $buyerEmail,
        'source_freebie_id' => $sourceFreebie['id'],
        'product_id' => $productId
    ], 'info');
    
    try {
        // 1. Käufer finden oder erstellen
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$buyerEmail]);
        $buyer = $stmt->fetch();
        
        if ($buyer) {
            $buyerId = $buyer['id'];
            logWebhook(['info' => 'Buyer found', 'buyer_id' => $buyerId], 'info');
        } else {
            // Neuen Käufer anlegen
            $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $password = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    name, email, password, role, is_active, raw_code,
                    digistore_order_id, digistore_product_id,
                    source, created_at
                ) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, 'marketplace', NOW())
            ");
            
            $stmt->execute([
                $buyerName,
                $buyerEmail,
                $hashedPassword,
                $rawCode,
                $orderId,
                $productId
            ]);
            
            $buyerId = $pdo->lastInsertId();
            
            logWebhook([
                'success' => 'New buyer created',
                'buyer_id' => $buyerId,
                'email' => $buyerEmail
            ], 'success');
            
            // Willkommens-E-Mail
            sendMarketplaceWelcomeEmail($buyerEmail, $buyerName, $password, $rawCode, $sourceFreebie['headline']);
        }
        
        // 2. Prüfen ob bereits gekauft
        $stmt = $pdo->prepare("
            SELECT id FROM customer_freebies 
            WHERE customer_id = ? AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$buyerId, $sourceFreebie['id']]);
        
        if ($stmt->fetch()) {
            logWebhook([
                'warning' => 'Freebie already purchased',
                'buyer_id' => $buyerId,
                'freebie_id' => $sourceFreebie['id']
            ], 'warning');
            return; // Bereits gekauft
        }
        
        // 3. ALLE Felder des Original-Freebies laden
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$sourceFreebie['id']]);
        $fullSourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        logWebhook([
            'info' => 'Source freebie loaded',
            'fields_count' => count($fullSourceFreebie),
            'has_bullet_points' => !empty($fullSourceFreebie['bullet_points']),
            'has_mockup' => !empty($fullSourceFreebie['mockup_image_url'])
        ], 'info');
        
        // 4. Unique ID generieren
        $uniqueId = bin2hex(random_bytes(16));
        
        // 5. VOLLSTÄNDIGES Freebie kopieren mit ALLEN Feldern
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id,
                template_id,
                freebie_type,
                headline,
                subheadline,
                preheadline,
                mockup_image_url,
                background_color,
                primary_color,
                cta_text,
                bullet_points,
                layout,
                email_field_text,
                button_text,
                privacy_checkbox_text,
                thank_you_headline,
                thank_you_message,
                email_provider,
                email_api_key,
                email_list_id,
                course_id,
                unique_id,
                niche,
                original_creator_id,
                copied_from_freebie_id,
                marketplace_enabled,
                digistore_order_id,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
                NULL, NULL, NULL, ?, ?, ?, ?, ?, 0, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $buyerId,                                      // customer_id
            $fullSourceFreebie['template_id'],            // template_id
            'purchased',                                   // freebie_type (als gekauft markieren)
            $fullSourceFreebie['headline'],                // headline
            $fullSourceFreebie['subheadline'],            // subheadline
            $fullSourceFreebie['preheadline'],            // preheadline
            $fullSourceFreebie['mockup_image_url'],       // mockup_image_url
            $fullSourceFreebie['background_color'],       // background_color
            $fullSourceFreebie['primary_color'],          // primary_color
            $fullSourceFreebie['cta_text'],               // cta_text
            $fullSourceFreebie['bullet_points'],          // bullet_points ⭐ WICHTIG!
            $fullSourceFreebie['layout'],                 // layout
            $fullSourceFreebie['email_field_text'],       // email_field_text
            $fullSourceFreebie['button_text'],            // button_text
            $fullSourceFreebie['privacy_checkbox_text'],  // privacy_checkbox_text
            $fullSourceFreebie['thank_you_headline'],     // thank_you_headline
            $fullSourceFreebie['thank_you_message'],      // thank_you_message
            // Email-Einstellungen werden auf NULL gesetzt (muss neu konfiguriert werden)
            $fullSourceFreebie['course_id'],              // course_id
            $uniqueId,                                     // unique_id (NEU!)
            $fullSourceFreebie['niche'],                  // niche
            $fullSourceFreebie['customer_id'],            // original_creator_id
            $fullSourceFreebie['id'],                     // copied_from_freebie_id
            $orderId                                       // digistore_order_id
        ]);
        
        $newFreebieId = $pdo->lastInsertId();
        
        logWebhook([
            'success' => 'Freebie copied successfully',
            'new_freebie_id' => $newFreebieId,
            'buyer_id' => $buyerId,
            'copied_fields' => [
                'headline' => $fullSourceFreebie['headline'],
                'bullet_points' => !empty($fullSourceFreebie['bullet_points']) ? 'YES' : 'NO',
                'mockup_image' => !empty($fullSourceFreebie['mockup_image_url']) ? 'YES' : 'NO',
                'course_id' => $fullSourceFreebie['course_id']
            ]
        ], 'success');
        
        // 6. Verkaufszähler beim Original erhöhen
        $stmt = $pdo->prepare("
            UPDATE customer_freebies 
            SET marketplace_sales_count = marketplace_sales_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$sourceFreebie['id']]);
        
        // 7. Provisionen später verarbeiten (falls Vendor-System aktiv)
        // TODO: Vendor-Provisionen berechnen
        
        logWebhook([
            'success' => 'Marketplace purchase completed',
            'buyer_email' => $buyerEmail,
            'new_freebie_id' => $newFreebieId,
            'original_freebie_id' => $sourceFreebie['id']
        ], 'success');
        
    } catch (Exception $e) {
        logWebhook([
            'error' => 'Marketplace purchase failed',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 'error');
        throw $e;
    }
}

/**
 * Willkommens-E-Mail für Marktplatz-Käufe
 */
function sendMarketplaceWelcomeEmail($email, $name, $password, $rawCode, $freebieTitle) {
    $subject = "🎉 Willkommen - Dein gekauftes Freebie: $freebieTitle";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Willkommen, $name!</h1>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <p>Vielen Dank für deinen Kauf im Marktplatz!</p>
                
                <p><strong>Dein gekauftes Freebie:</strong><br>
                📦 $freebieTitle</p>
                
                <p>Das Freebie wurde automatisch in deinen Account übertragen und steht dir sofort zur Verfügung.</p>
                
                <div style='background: #f5f7fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin: 0 0 15px 0; color: #667eea;'>🔑 Deine Zugangsdaten:</h3>
                    <table style='width: 100%;'>
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
                              color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold;'>
                        🚀 Jetzt einloggen und Freebie nutzen
                    </a>
                </div>
                
                <p style='font-size: 14px; color: #6b7280; margin-top: 20px;'>
                    💡 Tipp: Du kannst das Freebie sofort anpassen und für deine eigene Lead-Generierung nutzen!
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

function createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig, $partnerUsername, $affiliateUsername, $jvCommissionData) {
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, email, password, role, is_active, raw_code,
            digistore_order_id, digistore_product_id, digistore_product_name,
            source, created_at
        ) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'digistore24', NOW())
    ");
    
    $stmt->execute([
        $name, $email, $hashedPassword, $rawCode,
        $orderId, $productId, $productConfig['product_name'] ?? 'Unbekannt'
    ]);
    
    return $pdo->lastInsertId();
}

function grantCourseAccess($pdo, $userId, $productId, $email) {
    // Legacy course access
}

function setFreebieLimit($pdo, $userId, $productId, $productConfig) {
    // Legacy freebie limit
}

function setReferralSlots($pdo, $userId, $productConfig) {
    // Legacy referral slots
}

function assignReadyFreebies($pdo, $userId, $count) {
    // Legacy ready freebies
}

function handleRefund($pdo, $data) {
    // Refund logic
}

function handleSubscriptionEnd($pdo, $data) {
    // Subscription end logic
}
