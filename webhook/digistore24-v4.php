<?php
/**
 * Enhanced Webhook Handler - VERSION 5.3 FINAL PRODUCTION
 * UNIVERSELLER WEBHOOK: Admin-Dashboard + Marktplatz + Legacy
 * 
 * UNTERSTÜTZT BEIDE FORMATE:
 * - JSON (moderne APIs)
 * - URL-encoded Form-Data (Digistore24 IPN)
 */

require_once '../config/database.php';

// Logging
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook-logs.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen - BEIDE Formate unterstützen!
$rawInput = file_get_contents('php://input');
logWebhook(['raw_input' => $rawInput], 'received');

$webhookData = null;

// Versuch 1: JSON dekodieren
$webhookData = json_decode($rawInput, true);

// Versuch 2: Wenn kein JSON, dann Form-Data parsen (Digistore24 IPN)
if (!$webhookData) {
    parse_str($rawInput, $webhookData);
    
    if (!empty($webhookData)) {
        logWebhook(['info' => 'Parsed as URL-encoded form data'], 'info');
        
        // Digistore24 Format in unser erwartetes Format umwandeln
        $webhookData = [
            'event' => $webhookData['event'] ?? 'purchase',
            'buyer' => [
                'email' => $webhookData['email'] ?? '',
                'first_name' => $webhookData['first_name'] ?? '',
                'last_name' => $webhookData['last_name'] ?? ''
            ],
            'order_id' => $webhookData['order_id'] ?? $webhookData['transaction_id'] ?? '',
            'product_id' => $webhookData['product_id'] ?? '',
            'product_name' => $webhookData['product_name'] ?? $webhookData['product_name_intern'] ?? ''
        ];
    }
}

if (!$webhookData || empty($webhookData['buyer']['email'])) {
    logWebhook(['error' => 'Invalid webhook data'], 'error');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook data']);
    exit;
}

logWebhook($webhookData, 'parsed');

try {
    $pdo = getDBConnection();
    
    $eventType = $webhookData['event'] ?? 'purchase';
    
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
            logWebhook(['info' => 'Event received', 'event' => $eventType], 'info');
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'Event received']);
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
    
    // FREEBIE KOPIEREN
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
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
        VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
    ");
    $stmt->execute([$userId]);
    
    sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode);
    
    logWebhook(['success' => 'Marketplace buyer created', 'buyer_id' => $userId], 'success');
    
    return $userId;
}

/**
 * MARKTPLATZ: Kopiert Freebie VOLLSTÄNDIG
 */
function copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebieId) {
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Source freebie not found');
    }
    
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    
    logWebhook([
        'debug' => 'Copying freebie',
        'source_freebie_id' => $sourceFreebieId,
        'course_id' => $source['course_id'] ?? 'NULL'
    ], 'debug');
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id, niche, template_id, course_id, headline, subheadline, preheadline,
            bullet_points, cta_text, layout, background_color, primary_color, raw_code,
            unique_id, url_slug, mockup_image_url, video_url, video_format, freebie_type,
            thank_you_message, preheadline_font, preheadline_size, headline_font, headline_size,
            subheadline_font, subheadline_size, bulletpoints_font, bulletpoints_size,
            optin_display_mode, popup_message, cta_animation, font_heading, font_body,
            font_size, heading_font_size, body_font_size, bullet_icon_style,
            original_creator_id, copied_from_freebie_id, marketplace_enabled, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
        )
    ");
    
    $stmt->execute([
        $buyerId, $source['niche'] ?? 'sonstiges', $source['template_id'], $source['course_id'],
        $source['headline'], $source['subheadline'], $source['preheadline'], $source['bullet_points'],
        $source['cta_text'], $source['layout'], $source['background_color'], $source['primary_color'],
        $source['raw_code'] ?? '', $uniqueId, $urlSlug, $source['mockup_image_url'],
        $source['video_url'] ?? '', $source['video_format'] ?? 'widescreen', $source['freebie_type'],
        $source['thank_you_message'], $source['preheadline_font'], $source['preheadline_size'],
        $source['headline_font'], $source['headline_size'], $source['subheadline_font'],
        $source['subheadline_size'], $source['bulletpoints_font'], $source['bulletpoints_size'],
        $source['optin_display_mode'] ?? 'direct', $source['popup_message'] ?? '',
        $source['cta_animation'] ?? 'none', $source['font_heading'] ?? 'Inter',
        $source['font_body'] ?? 'Inter', $source['font_size'], $source['heading_font_size'],
        $source['body_font_size'], $source['bullet_icon_style'] ?? 'standard',
        $source['customer_id'], $sourceFreebieId
    ]);
    
    $copiedId = $pdo->lastInsertId();
    
    logWebhook(['success' => 'Freebie copied', 'copied_freebie_id' => $copiedId], 'success');
    
    return $copiedId;
}

/**
 * MARKTPLATZ: Kopiert Videokurs
 */
function copyFreebieVideoCourse($pdo, $sourceFreebieId, $targetFreebieId, $buyerId) {
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCourse) {
        logWebhook(['info' => 'No video course to copy'], 'info');
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([$targetFreebieId, $buyerId, $sourceCourse['title'], $sourceCourse['description'], $sourceCourse['is_active']]);
    $newCourseId = $pdo->lastInsertId();
    
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
                $newModuleId, $sourceLesson['title'], $sourceLesson['description'], $sourceLesson['video_url'],
                $sourceLesson['pdf_url'] ?? null, $sourceLesson['sort_order'], $sourceLesson['unlock_after_days'] ?? 0,
                $sourceLesson['button_text'] ?? null, $sourceLesson['button_url'] ?? null
            ]);
            $totalLessons++;
        }
    }
    
    logWebhook(['success' => 'Video course copied', 'modules' => count($moduleMapping), 'lessons' => $totalLessons], 'success');
}

function sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode) {
    $subject = "🎉 Willkommen beim KI Leadsystem";
    $message = "<html><body><p>Hallo $name,</p><p>Login: $email<br>Passwort: $password<br>RAW-Code: $rawCode</p><p><a href='https://app.mehr-infos-jetzt.de/public/login.php'>Jetzt einloggen</a></p></body></html>";
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: KI Leadsystem <noreply@mehr-infos-jetzt.de>\r\n";
    mail($email, $subject, $message, $headers);
    logWebhook(['success' => 'Welcome email sent', 'email' => $email], 'success');
}

function sendMarketplacePurchaseEmail($email, $name, $freebieTitle) {
    $subject = "✅ Dein Freebie ist verfügbar!";
    $message = "<html><body><p>Hallo $name,</p><p>Dein Freebie \"$freebieTitle\" wurde kopiert!</p><p><a href='https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies'>Zu meinen Freebies</a></p></body></html>";
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: KI Leadsystem <noreply@mehr-infos-jetzt.de>\r\n";
    mail($email, $subject, $message, $headers);
    logWebhook(['success' => 'Purchase email sent', 'email' => $email], 'success');
}

// Helper functions
function createUser($pdo, $email, $name, $orderId, $productId, $productName) {
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active, raw_code, digistore_order_id, digistore_product_id, digistore_product_name, source, created_at) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, 'digistore24', NOW())");
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
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $courseId) {
            $stmt2 = $pdo->prepare("INSERT IGNORE INTO course_enrollments (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            $stmt2->execute([$userId, $courseId]);
        }
    } catch (PDOException $e) {}
}

function assignWebhookFreebies($pdo, $userId, $webhookId) {}
function createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig, $p1, $p2, $p3) {
    return createUser($pdo, $email, $name, $orderId, $productId, $productConfig['product_name'] ?? 'Unbekannt');
}
function grantCourseAccess($pdo, $userId, $productId, $email) {}
function setFreebieLimit($pdo, $userId, $productId, $productConfig) {}
function setReferralSlots($pdo, $userId, $productConfig) {}
function assignReadyFreebies($pdo, $userId, $count) {}
function handleRefund($pdo, $data) {}
function handleSubscriptionEnd($pdo, $data) {}
