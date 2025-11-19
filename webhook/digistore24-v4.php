<?php
/**
 * Enhanced Webhook Handler - VERSION 5.0 MASTER
 * UNIVERSELLER WEBHOOK: Admin-Dashboard + Marktplatz + Legacy
 * 
 * Unterstützt ALLE Systeme:
 * 1. Neues flexibles Webhook-System (Admin-Dashboard)
 * 2. Legacy digistore_products System
 * 3. Marktplatz-Freebies mit Videokurs-Kopie
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
    
    // User finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
        logWebhook(['info' => 'Existing user found', 'user_id' => $userId], 'info');
    } else {
        $userId = createUser($pdo, $email, $name, $orderId, $productId, $config['webhook_name']);
        logWebhook(['success' => 'New user created', 'user_id' => $userId], 'success');
    }
    
    // Freebie-Limits setzen (falls konfiguriert)
    if (!empty($config['freebie_limit'])) {
        setFreebieLimit_Flexible($pdo, $userId, $productId, $config['webhook_name'], $config['freebie_limit']);
    }
    
    // Kurse zuweisen
    assignWebhookCourses($pdo, $userId, $config['id']);
    
    // Fertige Freebies zuweisen
    assignWebhookFreebies($pdo, $userId, $config['id']);
    
    logWebhook(['success' => 'Flexible webhook processed successfully', 'user_id' => $userId], 'success');
}

/**
 * Legacy: Alte digistore_products Verarbeitung
 */
function processLegacyWebhook($pdo, $product, $email, $name, $orderId, $productId, $data) {
    logWebhook([
        'info' => 'Processing legacy webhook',
        'product_id' => $productId,
        'product_name' => $product['product_name']
    ], 'info');
    
    // JV-Daten extrahieren
    $partnerUsername = $data['partner_username'] ?? null;
    $affiliateUsername = $data['affiliate_username'] ?? null;
    
    // User finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $userId = $user['id'];
    } else {
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $product, $partnerUsername, $affiliateUsername, null);
    }
    
    // Kurs-Zugang gewähren
    grantCourseAccess($pdo, $userId, $productId, $email);
    
    // Freebie-Limits setzen
    setFreebieLimit($pdo, $userId, $productId, $product);
    
    // Empfehlungsprogramm-Slots
    setReferralSlots($pdo, $userId, $product);
    
    // Fertige Freebies zuweisen
    if ($product['ready_freebies_count'] > 0) {
        assignReadyFreebies($pdo, $userId, $product['ready_freebies_count']);
    }
    
    logWebhook(['success' => 'Legacy webhook processed successfully', 'user_id' => $userId], 'success');
}

/**
 * MARKTPLATZ: Verarbeitet Kauf eines Marktplatz-Freebies
 */
function handleMarketplacePurchase($pdo, $buyerEmail, $buyerName, $productId, $sourceFreebie, $orderId) {
    logWebhook([
        'info' => 'Starting marketplace purchase',
        'buyer_email' => $buyerEmail,
        'source_freebie_id' => $sourceFreebie['id']
    ], 'marketplace');
    
    // Käufer finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$buyerEmail]);
    $buyer = $stmt->fetch();
    
    if ($buyer) {
        $buyerId = $buyer['id'];
    } else {
        $buyerId = createMarketplaceBuyer($pdo, $buyerEmail, $buyerName, $orderId);
    }
    
    // Prüfen ob bereits gekauft
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE customer_id = ? AND copied_from_freebie_id = ?
    ");
    $stmt->execute([$buyerId, $sourceFreebie['id']]);
    
    if ($stmt->fetch()) {
        logWebhook(['warning' => 'Already purchased'], 'warning');
        return;
    }
    
    // Freebie kopieren
    $copiedFreebieId = copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebie['id']);
    
    // Videokurs kopieren
    copyFreebieVideoCourse($pdo, $sourceFreebie['id'], $copiedFreebieId, $buyerId);
    
    // Verkaufszähler erhöhen
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_sales_count = marketplace_sales_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$sourceFreebie['id']]);
    
    sendMarketplacePurchaseEmail($buyerEmail, $buyerName, $sourceFreebie['headline']);
    
    logWebhook(['success' => 'Marketplace purchase completed', 'buyer_id' => $buyerId, 'freebie_id' => $copiedFreebieId], 'marketplace_success');
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
    
    // Standard-Limits
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
        VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
    ");
    $stmt->execute([$userId]);
    
    sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode);
    
    return $userId;
}

function copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebieId) {
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Source freebie not found');
    }
    
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebies (
            customer_id, template_id, freebie_type, headline, subheadline, preheadline,
            mockup_image_url, background_color, primary_color, cta_text, bullet_points,
            bullet_icon_style, layout, unique_id, url_slug, niche, original_creator_id,
            copied_from_freebie_id, marketplace_enabled, created_at
        ) VALUES (?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");
    
    $stmt->execute([
        $buyerId,
        $source['template_id'],
        $source['headline'],
        $source['subheadline'],
        $source['preheadline'],
        $source['mockup_image_url'],
        $source['background_color'],
        $source['primary_color'],
        $source['cta_text'],
        $source['bullet_points'],
        $source['bullet_icon_style'] ?? 'standard',
        $source['layout'],
        $uniqueId,
        $urlSlug,
        $source['niche'] ?? 'sonstiges',
        $source['customer_id'],
        $sourceFreebieId
    ]);
    
    return $pdo->lastInsertId();
}

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
        }
    }
    
    logWebhook(['success' => 'Video course copied', 'modules' => count($moduleMapping)], 'success');
}

// Helper functions für Admin-Dashboard
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
        
        foreach ($freebies as $freebieId) {
            // Freebie kopieren
        }
    } catch (PDOException $e) {
        logWebhook(['warning' => 'Could not assign freebies', 'error' => $e->getMessage()], 'warning');
    }
}

// Legacy-Funktionen (aus digistore24.php)
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

// Email-Funktionen (vereinfacht)
function sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode) {}
function sendMarketplacePurchaseEmail($email, $name, $freebieTitle) {}

// Stub functions
function handleRefund($pdo, $data) {}
function handleSubscriptionEnd($pdo, $data) {}
