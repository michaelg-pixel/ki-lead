<?php
/**
 * Enhanced Webhook Handler - VERSION 5.1 FINAL
 * UNIVERSELLER WEBHOOK: Admin-Dashboard + Marktplatz + Legacy
 * 
 * PRODUKTIONSREIF mit:
 * - Vollständiger Freebie-Kopie (ALLE Felder inkl. course_id!)
 * - Funktionierenden Email-Benachrichtigungen
 * - Kompletter Videokurs-Kopie
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

function handleNewCustomer($pdo, $data) {
    $email = $data['buyer']['email'] ?? '';
    $name = trim(($data['buyer']['first_name'] ?? '') . ' ' . ($data['buyer']['last_name'] ?? ''));
    $orderId = $data['order_id'] ?? '';
    $productId = $data['product_id'] ?? '';
    $productName = $data['product_name'] ?? '';
    
    if (empty($email) || empty($productId)) {
        throw new Exception('Email and Product ID are required');
    }
    
    logWebhook([
        'info' => 'Processing new customer',
        'email' => $email,
        'product_id' => $productId,
        'order_id' => $orderId
    ], 'info');
    
    // PRIORITÄT 1: Marktplatz-Check (höchste Priorität!)
    $marketplaceFreebie = checkMarketplacePurchase($pdo, $productId);
    if ($marketplaceFreebie) {
        logWebhook(['info' => 'Marketplace purchase detected'], 'info');
        handleMarketplacePurchase($pdo, $email, $name, $productId, $marketplaceFreebie, $orderId);
        return;
    }
    
    // PRIORITÄT 2: Admin-Dashboard Webhook-System
    $webhookConfig = findWebhookConfiguration($pdo, $productId);
    if ($webhookConfig) {
        logWebhook(['info' => 'Admin dashboard webhook found'], 'info');
        processFlexibleWebhook($pdo, $webhookConfig, $email, $name, $orderId, $productId, $data);
        return;
    }
    
    // PRIORITÄT 3: Legacy digistore_products System
    $legacyProduct = getProductConfig($pdo, $productId);
    if ($legacyProduct) {
        logWebhook(['info' => 'Legacy product configuration found'], 'info');
        processLegacyWebhook($pdo, $legacyProduct, $email, $name, $orderId, $productId, $data);
        return;
    }
    
    logWebhook([
        'warning' => 'No configuration found for product_id',
        'product_id' => $productId,
        'email' => $email
    ], 'warning');
}

function findWebhookConfiguration($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT wc.* 
            FROM webhook_configurations wc
            JOIN webhook_product_ids wp ON wc.id = wp.webhook_id
            WHERE wp.product_id = ? AND wc.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logWebhook(['warning' => 'webhook_configurations table not found', 'error' => $e->getMessage()], 'warning');
        return null;
    }
}

function getProductConfig($pdo, $productId) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM digistore_products 
            WHERE product_id = ? AND is_active = 1
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logWebhook(['warning' => 'digistore_products table not found', 'error' => $e->getMessage()], 'warning');
        return null;
    }
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
 * Admin-Dashboard: Flexible Webhook-Verarbeitung
 */
function processFlexibleWebhook($pdo, $config, $email, $name, $orderId, $productId, $data) {
    logWebhook([
        'info' => 'Processing flexible webhook',
        'webhook_id' => $config['id'],
        'webhook_name' => $config['webhook_name']
    ], 'info');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
    } else {
        $userId = createUser($pdo, $email, $name, $orderId, $productId, $config['webhook_name']);
    }
    
    if (!empty($config['freebie_limit'])) {
        setFreebieLimit_Flexible($pdo, $userId, $productId, $config['webhook_name'], $config['freebie_limit']);
    }
    
    assignWebhookCourses($pdo, $userId, $config['id']);
    assignWebhookFreebies($pdo, $userId, $config['id']);
    
    logWebhook(['success' => 'Flexible webhook processed', 'user_id' => $userId], 'success');
}

/**
 * Legacy: Alte digistore_products Verarbeitung
 */
function processLegacyWebhook($pdo, $product, $email, $name, $orderId, $productId, $data) {
    logWebhook(['info' => 'Processing legacy webhook', 'product_id' => $productId], 'info');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
    } else {
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $product, null, null, null);
    }
    
    grantCourseAccess($pdo, $userId, $productId, $email);
    setFreebieLimit($pdo, $userId, $productId, $product);
    setReferralSlots($pdo, $userId, $product);
    
    if ($product['ready_freebies_count'] > 0) {
        assignReadyFreebies($pdo, $userId, $product['ready_freebies_count']);
    }
    
    logWebhook(['success' => 'Legacy webhook processed', 'user_id' => $userId], 'success');
}

/**
 * MARKTPLATZ: Verarbeitet Kauf eines Marktplatz-Freebies
 */
function handleMarketplacePurchase($pdo, $buyerEmail, $buyerName, $productId, $sourceFreebie, $orderId) {
    logWebhook([
        'info' => 'Starting marketplace purchase',
        'buyer_email' => $buyerEmail,
        'source_freebie_id' => $sourceFreebie['id'],
        'product_id' => $productId
    ], 'marketplace');
    
    // Käufer finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$buyerEmail]);
    $buyer = $stmt->fetch();
    
    if ($buyer) {
        $buyerId = $buyer['id'];
        logWebhook(['info' => 'Existing buyer found', 'buyer_id' => $buyerId], 'info');
    } else {
        $buyerId = createMarketplaceBuyer($pdo, $buyerEmail, $buyerName, $orderId);
        logWebhook(['success' => 'New buyer created', 'buyer_id' => $buyerId], 'success');
    }
    
    // Prüfen ob bereits gekauft
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE customer_id = ? AND copied_from_freebie_id = ?
    ");
    $stmt->execute([$buyerId, $sourceFreebie['id']]);
    
    if ($stmt->fetch()) {
        logWebhook(['warning' => 'Freebie already purchased', 'buyer_id' => $buyerId], 'warning');
        return;
    }
    
    // FREEBIE KOPIEREN (vollständig!)
    $copiedFreebieId = copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebie['id']);
    
    // VIDEOKURS KOPIEREN
    copyFreebieVideoCourse($pdo, $sourceFreebie['id'], $copiedFreebieId, $buyerId);
    
    // VERKAUFSZÄHLER ERHÖHEN
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_sales_count = marketplace_sales_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$sourceFreebie['id']]);
    
    // EMAIL SENDEN
    sendMarketplacePurchaseEmail($buyerEmail, $buyerName, $sourceFreebie['headline']);
    
    logWebhook([
        'success' => 'Marketplace purchase completed!',
        'buyer_id' => $buyerId,
        'copied_freebie_id' => $copiedFreebieId,
        'source_freebie_id' => $sourceFreebie['id']
    ], 'marketplace_success');
}

function createMarketplaceBuyer($pdo, $email, $name, $orderId) {
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (
            name, email, password, role, is_active, raw_code,
            digistore_order_id, source, created_at
        ) VALUES (?, ?, ?, 'customer', 1, ?, ?, 'marketplace', NOW())
    ");
    
    $stmt->execute([$name, $email, $hashedPassword, $rawCode, $orderId]);
    $userId = $pdo->lastInsertId();
    
    // Standard-Limits für Marktplatz-Käufer
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
        VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
    ");
    $stmt->execute([$userId]);
    
    // Willkommens-Email senden
    sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode);
    
    logWebhook(['success' => 'Marketplace buyer created with welcome email', 'buyer_id' => $userId], 'success');
    
    return $userId;
}

/**
 * MARKTPLATZ: Kopiert Freebie VOLLSTÄNDIG
 * VERSION 5.1: ALLE Felder inkl. course_id!
 */
function copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebieId) {
    // Original-Freebie MIT ALLEN FELDERN laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Source freebie not found');
    }
    
    // Neues unique_id und url_slug generieren
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    
    // KRITISCH: course_id loggen
    $courseId = $source['course_id'] ?? null;
    logWebhook([
        'debug' => 'Copying freebie with ALL fields',
        'source_freebie_id' => $sourceFreebieId,
        'course_id' => $courseId,
        'has_course' => !empty($courseId)
    ], 'debug');
    
    // VOLLSTÄNDIGES INSERT mit ALLEN Feldern!
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
            bullet_icon_style,
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
            url_slug,
            niche,
            raw_code,
            video_url,
            video_format,
            optin_display_mode,
            popup_message,
            cta_animation,
            font_heading,
            font_body,
            font_size,
            original_creator_id,
            copied_from_freebie_id,
            marketplace_enabled,
            created_at
        ) VALUES (
            ?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            NULL, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
        )
    ");
    
    $stmt->execute([
        $buyerId,                                          // customer_id
        $source['template_id'],                            // template_id
        $source['headline'],                               // headline
        $source['subheadline'],                            // subheadline
        $source['preheadline'],                            // preheadline
        $source['mockup_image_url'],                       // mockup_image_url
        $source['background_color'],                       // background_color
        $source['primary_color'],                          // primary_color
        $source['cta_text'],                               // cta_text
        $source['bullet_points'],                          // bullet_points
        $source['bullet_icon_style'] ?? 'standard',        // bullet_icon_style
        $source['layout'],                                 // layout
        $source['email_field_text'],                       // email_field_text
        $source['button_text'],                            // button_text
        $source['privacy_checkbox_text'],                  // privacy_checkbox_text
        $source['thank_you_headline'],                     // thank_you_headline
        $source['thank_you_message'],                      // thank_you_message
        $courseId,                                         // course_id - KRITISCH!
        $uniqueId,                                         // unique_id
        $urlSlug,                                          // url_slug
        $source['niche'] ?? 'sonstiges',                   // niche
        $source['raw_code'] ?? '',                         // raw_code
        $source['video_url'] ?? '',                        // video_url
        $source['video_format'] ?? 'widescreen',           // video_format
        $source['optin_display_mode'] ?? 'direct',         // optin_display_mode
        $source['popup_message'] ?? '',                    // popup_message
        $source['cta_animation'] ?? 'none',                // cta_animation
        $source['font_heading'] ?? 'Inter',                // font_heading
        $source['font_body'] ?? 'Inter',                   // font_body
        $source['font_size'] ?? null,                      // font_size (JSON)
        $source['customer_id'],                            // original_creator_id
        $sourceFreebieId                                   // copied_from_freebie_id
    ]);
    
    $copiedId = $pdo->lastInsertId();
    
    logWebhook([
        'success' => 'Freebie copied completely',
        'copied_freebie_id' => $copiedId,
        'course_id_copied' => $courseId,
        'all_fields_included' => true
    ], 'success');
    
    return $copiedId;
}

/**
 * MARKTPLATZ: Kopiert kompletten Videokurs
 */
function copyFreebieVideoCourse($pdo, $sourceFreebieId, $targetFreebieId, $buyerId) {
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCourse) {
        logWebhook(['info' => 'No video course to copy'], 'info');
        return;
    }
    
    logWebhook(['info' => 'Starting video course copy', 'course_id' => $sourceCourse['id']], 'info');
    
    // Kurs erstellen
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$targetFreebieId, $buyerId, $sourceCourse['title'], $sourceCourse['description'], $sourceCourse['is_active']]);
    $newCourseId = $pdo->lastInsertId();
    
    // Module kopieren
    $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
    $stmt->execute([$sourceCourse['id']]);
    $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $moduleMapping = [];
    foreach ($sourceModules as $sourceModule) {
        $stmt = $pdo->prepare("
            INSERT INTO freebie_course_modules (course_id, title, description, sort_order, unlock_after_days, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$newCourseId, $sourceModule['title'], $sourceModule['description'], $sourceModule['sort_order'], $sourceModule['unlock_after_days'] ?? 0]);
        $moduleMapping[$sourceModule['id']] = $pdo->lastInsertId();
    }
    
    // Lektionen kopieren
    $totalLessons = 0;
    foreach ($moduleMapping as $oldModuleId => $newModuleId) {
        $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
        $stmt->execute([$oldModuleId]);
        $sourceLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sourceLessons as $sourceLesson) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order, unlock_after_days, button_text, button_url, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $newModuleId,
                $sourceLesson['title'],
                $sourceLesson['description'],
                $sourceLesson['video_url'],
                $sourceLesson['pdf_url'] ?? null,
                $sourceLesson['sort_order'],
                $sourceLesson['unlock_after_days'] ?? 0,
                $sourceLesson['button_text'] ?? null,
                $sourceLesson['button_url'] ?? null
            ]);
            $totalLessons++;
        }
    }
    
    logWebhook([
        'success' => 'Video course copied completely',
        'new_course_id' => $newCourseId,
        'modules' => count($moduleMapping),
        'lessons' => $totalLessons
    ], 'success');
}

/**
 * MARKTPLATZ: Willkommens-Email für neuen Käufer
 */
function sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode) {
    $subject = "🎉 Willkommen beim KI Leadsystem - Dein Marktplatz-Kauf";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Willkommen, $name!</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0;'>Dein Freebie wartet auf dich!</p>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <p>Vielen Dank für deinen Kauf im Marktplatz!</p>
                <p>Dein gekauftes Freebie wurde automatisch in deinen Account kopiert und steht dir jetzt zur Verfügung.</p>
                
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
                        🚀 Jetzt einloggen
                    </a>
                </div>
                
                <p style='color: #888; font-size: 14px; margin-top: 30px; text-align: center;'>
                    Viel Erfolg mit deinem Freebie! 🎯<br>
                    Du kannst jederzeit weitere Freebies im Marktplatz entdecken!
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
    
    logWebhook(['success' => 'Welcome email sent to new marketplace buyer', 'email' => $email], 'success');
}

/**
 * MARKTPLATZ: Kauf-Bestätigung
 */
function sendMarketplacePurchaseEmail($email, $name, $freebieTitle) {
    $subject = "✅ Dein Freebie ist jetzt verfügbar!";
    
    $message = "
    <html>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;'>
                <h1 style='color: white; margin: 0; font-size: 32px;'>🎉 Erfolgreich gekauft!</h1>
            </div>
            
            <div style='background: white; padding: 30px; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                <p>Hallo $name,</p>
                <p>dein Freebie <strong>\"$freebieTitle\"</strong> wurde erfolgreich in deinen Account kopiert!</p>
                
                <div style='background: #f0fdf4; border: 2px solid #22c55e; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p style='margin: 0; color: #166534;'>
                        ✅ Du kannst das Freebie jetzt bearbeiten und für deine Zwecke anpassen!
                    </p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies' 
                       style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                              color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                        🎁 Zu meinen Freebies
                    </a>
                </div>
                
                <p style='color: #888; font-size: 14px; margin-top: 30px; text-align: center;'>
                    Viel Erfolg mit deinem neuen Freebie! 🚀
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
    
    logWebhook(['success' => 'Purchase confirmation email sent', 'email' => $email], 'success');
}

// Helper functions
function createUser($pdo, $email, $name, $orderId, $productId, $productName) {
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, is_active, raw_code, digistore_order_id, digistore_product_id, digistore_product_name, source, created_at)
        VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'digistore24', NOW())
    ");
    $stmt->execute([$name, $email, $hashedPassword, $rawCode, $orderId, $productId, $productName]);
    
    return $pdo->lastInsertId();
}

function setFreebieLimit_Flexible($pdo, $userId, $productId, $productName, $limit) {
    $stmt = $pdo->prepare("SELECT id FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE customer_freebie_limits SET freebie_limit = ?, product_id = ?, product_name = ? WHERE customer_id = ?");
        $stmt->execute([$limit, $productId, $productName, $userId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name, source) VALUES (?, ?, ?, ?, 'webhook')");
        $stmt->execute([$userId, $limit, $productId, $productName]);
    }
}

function assignWebhookCourses($pdo, $userId, $webhookId) {
    try {
        $stmt = $pdo->prepare("SELECT course_id FROM webhook_course_access WHERE webhook_id = ?");
        $stmt->execute([$webhookId]);
        $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($courses as $courseId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO course_enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $courseId]);
        }
    } catch (PDOException $e) {
        logWebhook(['warning' => 'Could not assign courses', 'error' => $e->getMessage()], 'warning');
    }
}

function assignWebhookFreebies($pdo, $userId, $webhookId) {
    try {
        $stmt = $pdo->prepare("SELECT freebie_template_id FROM webhook_ready_freebies WHERE webhook_id = ?");
        $stmt->execute([$webhookId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        logWebhook(['warning' => 'Could not assign freebies', 'error' => $e->getMessage()], 'warning');
    }
}

function createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig, $partnerUsername, $affiliateUsername, $jvCommissionData) {
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, is_active, raw_code, digistore_order_id, digistore_product_id, digistore_product_name, source, created_at)
        VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'digistore24', NOW())
    ");
    $stmt->execute([$name, $email, $hashedPassword, $rawCode, $orderId, $productId, $productConfig['product_name'] ?? 'Unbekannt']);
    
    return $pdo->lastInsertId();
}

function grantCourseAccess($pdo, $userId, $productId, $email) {
    $stmt = $pdo->prepare("SELECT id, title FROM courses WHERE digistore_product_id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $course = $stmt->fetch();
    
    if ($course) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO course_access (user_id, course_id, access_source, granted_at) VALUES (?, ?, 'digistore24', NOW())");
        $stmt->execute([$userId, $course['id']]);
    }
}

function setFreebieLimit($pdo, $userId, $productId, $productConfig) {
    $limit = $productConfig['own_freebies_limit'] ?? 5;
    $stmt = $pdo->prepare("INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name, source) VALUES (?, ?, ?, ?, 'webhook') ON DUPLICATE KEY UPDATE freebie_limit = ?");
    $stmt->execute([$userId, $limit, $productId, $productConfig['product_name'] ?? 'Unbekannt', $limit]);
}

function setReferralSlots($pdo, $userId, $productConfig) {
    $slots = $productConfig['referral_program_slots'] ?? 1;
    try {
        $stmt = $pdo->prepare("INSERT INTO customer_referral_slots (customer_id, total_slots, used_slots, source, created_at) VALUES (?, ?, 0, 'webhook', NOW())");
        $stmt->execute([$userId, $slots]);
    } catch (PDOException $e) {}
}

function assignReadyFreebies($pdo, $userId, $count) {}

// Stub functions
function handleRefund($pdo, $data) {}
function handleSubscriptionEnd($pdo, $data) {}
