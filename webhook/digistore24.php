<?php
/**
 * Digistore24 Webhook Handler - VERSION 3.3
 * Mit Source-Tracking, Konflikt-Schutz, MARKTPLATZ-INTEGRATION, JV-TRACKING und VERBESSERTE COURSE-ID ÜBERTRAGUNG
 * 
 * Empfängt Kunden von Digistore24 und:
 * - Erstellt automatisch Accounts + Kurs-Zugang + Freebie-Limits
 * - Kopiert Marktplatz-Freebies automatisch in Käufer-Account
 * - Überträgt ALLE Freebie-Felder inkl. course_id
 * - Respektiert manuelle Admin-Änderungen
 * - Speichert JV-Partner und Affiliate-Daten für Tracking
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
    
    // JV-Partner und Affiliate-Daten extrahieren
    $partnerUsername = $data['partner_username'] ?? null;
    $affiliateUsername = $data['affiliate_username'] ?? null;
    $jvCommissionData = null;
    
    // JV-Kommissions-Daten sammeln (falls vorhanden)
    if (isset($data['partner_commission_amount']) || isset($data['affiliate_commission_amount'])) {
        $jvCommissionData = [
            'partner' => [
                'username' => $partnerUsername,
                'commission_amount' => $data['partner_commission_amount'] ?? null,
                'commission_percent' => $data['partner_commission_percent'] ?? null
            ],
            'affiliate' => [
                'username' => $affiliateUsername,
                'commission_amount' => $data['affiliate_commission_amount'] ?? null,
                'commission_percent' => $data['affiliate_commission_percent'] ?? null
            ],
            'recorded_at' => date('Y-m-d H:i:s')
        ];
    }
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (empty($productId)) {
        throw new Exception('Product ID is required');
    }
    
    // MARKTPLATZ-CHECK: Ist dies ein Marktplatz-Freebie-Kauf?
    $marketplaceFreebie = checkMarketplacePurchase($pdo, $productId);
    
    if ($marketplaceFreebie) {
        // Dies ist ein Marktplatz-Kauf!
        handleMarketplacePurchase($pdo, $email, $name, $productId, $marketplaceFreebie, $orderId);
        return;
    }
    
    // NORMAL: Regulärer Produkt-Kauf
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
        
        // JV-Daten beim existierenden User aktualisieren
        updateUserJVData($pdo, $userId, $partnerUsername, $affiliateUsername, $jvCommissionData);
        
        logWebhook([
            'message' => 'User already exists, granting access and updating JV data',
            'email' => $email,
            'user_id' => $userId,
            'partner_username' => $partnerUsername,
            'affiliate_username' => $affiliateUsername
        ], 'info');
    } else {
        // Neuen User erstellen (inkl. JV-Daten)
        $userId = createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig, 
                                 $partnerUsername, $affiliateUsername, $jvCommissionData);
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
        'referral_slots' => $productConfig['referral_program_slots'],
        'jv_partner' => $partnerUsername,
        'affiliate' => $affiliateUsername
    ], 'success');
}

/**
 * JV-Daten beim existierenden User aktualisieren
 */
function updateUserJVData($pdo, $userId, $partnerUsername, $affiliateUsername, $jvCommissionData) {
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET jv_partner_username = ?,
                affiliate_username = ?,
                jv_commission_data = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $partnerUsername,
            $affiliateUsername,
            $jvCommissionData ? json_encode($jvCommissionData) : null,
            $userId
        ]);
        
        logWebhook([
            'success' => 'JV data updated for existing user',
            'user_id' => $userId,
            'partner_username' => $partnerUsername,
            'affiliate_username' => $affiliateUsername
        ], 'success');
    } catch (PDOException $e) {
        logWebhook([
            'warning' => 'Could not update JV data - columns may not exist yet',
            'user_id' => $userId,
            'error' => $e->getMessage()
        ], 'warning');
    }
}

/**
 * MARKTPLATZ: Prüft ob Produkt-ID ein Marktplatz-Freebie ist
 */
function checkMarketplacePurchase($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT id, customer_id, headline, marketplace_price, course_id
        FROM customer_freebies 
        WHERE digistore_product_id = ? 
        AND marketplace_enabled = 1
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    
    return $stmt->fetch();
}

/**
 * MARKTPLATZ: Verarbeitet Kauf eines Marktplatz-Freebies
 */
function handleMarketplacePurchase($pdo, $buyerEmail, $buyerName, $productId, $sourceFreebie, $orderId) {
    logWebhook([
        'message' => 'Marketplace purchase detected',
        'buyer_email' => $buyerEmail,
        'product_id' => $productId,
        'freebie_id' => $sourceFreebie['id'],
        'seller_id' => $sourceFreebie['customer_id'],
        'source_course_id' => $sourceFreebie['course_id'] ?? null
    ], 'marketplace');
    
    // Käufer-Account finden oder erstellen
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$buyerEmail]);
    $buyer = $stmt->fetch();
    
    $buyerId = null;
    
    if (!$buyer) {
        // Neuen Käufer-Account erstellen
        $buyerId = createMarketplaceBuyer($pdo, $buyerEmail, $buyerName, $orderId);
    } else {
        $buyerId = $buyer['id'];
    }
    
    // Prüfen ob Freebie bereits gekauft wurde
    $stmt = $pdo->prepare("
        SELECT id FROM customer_freebies 
        WHERE customer_id = ? AND copied_from_freebie_id = ?
    ");
    $stmt->execute([$buyerId, $sourceFreebie['id']]);
    
    if ($stmt->fetch()) {
        logWebhook([
            'info' => 'Freebie already purchased by this user',
            'buyer_id' => $buyerId,
            'freebie_id' => $sourceFreebie['id']
        ], 'info');
        return;
    }
    
    // FREEBIE KOPIEREN mit allen Daten (inkl. course_id)
    $copiedFreebieId = copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebie['id']);
    
    // VERKAUFSZÄHLER ERHÖHEN beim Original
    $stmt = $pdo->prepare("
        UPDATE customer_freebies 
        SET marketplace_sales_count = marketplace_sales_count + 1
        WHERE id = ?
    ");
    $stmt->execute([$sourceFreebie['id']]);
    
    // E-Mail an Käufer senden
    sendMarketplacePurchaseEmail($buyerEmail, $buyerName, $sourceFreebie['headline']);
    
    logWebhook([
        'success' => 'Marketplace freebie copied to buyer',
        'buyer_id' => $buyerId,
        'buyer_email' => $buyerEmail,
        'source_freebie_id' => $sourceFreebie['id'],
        'copied_freebie_id' => $copiedFreebieId,
        'seller_id' => $sourceFreebie['customer_id']
    ], 'marketplace_success');
}

/**
 * MARKTPLATZ: Erstellt Account für Marktplatz-Käufer
 */
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
    
    // Standard-Limits für Marktplatz-Käufer (können später upgraden)
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
        VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
    ");
    $stmt->execute([$userId]);
    
    // Willkommens-E-Mail senden
    sendMarketplaceBuyerWelcomeEmail($email, $name, $password, $rawCode);
    
    return $userId;
}

/**
 * MARKTPLATZ: Kopiert ein Freebie komplett in Käufer-Account
 * VERSION 3.3: Verbessert - überträgt ALLE Felder inkl. course_id mit Logging
 */
function copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebieId) {
    // Original-Freebie laden MIT ALLEN FELDERN
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Source freebie not found');
    }
    
    // DEBUG: course_id prüfen und loggen
    $courseId = $source['course_id'] ?? null;
    logWebhook([
        'debug' => 'Copying marketplace freebie',
        'source_freebie_id' => $sourceFreebieId,
        'source_course_id' => $courseId,
        'has_course' => !empty($courseId),
        'buyer_id' => $buyerId
    ], 'debug');
    
    // Neues unique_id und url_slug generieren
    $uniqueId = bin2hex(random_bytes(16));
    $urlSlug = ($source['url_slug'] ?? '') . '-' . substr($uniqueId, 0, 8);
    
    // WICHTIG: ALLE Freebie-Felder kopieren inkl. course_id!
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
    
    // Erfolg loggen MIT course_id Info
    logWebhook([
        'success' => 'Marketplace freebie copied successfully',
        'copied_freebie_id' => $copiedId,
        'buyer_id' => $buyerId,
        'source_freebie_id' => $sourceFreebieId,
        'course_id_copied' => $courseId,
        'has_course' => !empty($courseId)
    ], 'success');
    
    // Wenn course_id fehlt, explizit warnen
    if (empty($courseId)) {
        logWebhook([
            'warning' => 'Source freebie has no course_id - buyer will not have course access via freebie',
            'source_freebie_id' => $sourceFreebieId,
            'copied_freebie_id' => $copiedId
        ], 'warning');
    }
    
    return $copiedId;
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
 * Neuen User erstellen - VERSION 3.2 mit JV-Tracking
 */
function createNewUser($pdo, $email, $name, $orderId, $productId, $productConfig, 
                       $partnerUsername = null, $affiliateUsername = null, $jvCommissionData = null) {
    // RAW-Code generieren
    $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Zufälliges Passwort generieren
    $password = bin2hex(random_bytes(8));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Kunden in Datenbank anlegen (mit JV-Daten falls vorhanden)
    try {
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
                jv_partner_username,
                affiliate_username,
                jv_commission_data,
                source,
                created_at
            ) VALUES (?, ?, ?, 'customer', 1, ?, ?, ?, ?, ?, ?, ?, 'digistore24', NOW())
        ");
        
        $stmt->execute([
            $name,
            $email,
            $hashedPassword,
            $rawCode,
            $orderId,
            $productId,
            $productConfig['product_name'] ?? 'Unbekannt',
            $partnerUsername,
            $affiliateUsername,
            $jvCommissionData ? json_encode($jvCommissionData) : null
        ]);
    } catch (PDOException $e) {
        // Falls JV-Spalten noch nicht existieren, ohne JV-Daten erstellen
        logWebhook([
            'warning' => 'JV columns not available, creating user without JV data',
            'error' => $e->getMessage()
        ], 'warning');
        
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
    }
    
    $userId = $pdo->lastInsertId();
    
    // Willkommens-E-Mail senden
    sendWelcomeEmail($email, $name, $password, $rawCode, $productConfig);
    
    logWebhook([
        'message' => 'New customer created',
        'user_id' => $userId,
        'email' => $email,
        'raw_code' => $rawCode,
        'product_type' => $productConfig['product_type'] ?? 'custom',
        'jv_partner' => $partnerUsername,
        'affiliate' => $affiliateUsername
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
 * MARKTPLATZ: Willkommens-E-Mail für Marktplatz-Käufer
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
}

/**
 * MARKTPLATZ: Kauf-Bestätigung an Käufer
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
