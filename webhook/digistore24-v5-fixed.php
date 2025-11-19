<?php
/**
 * Digistore24 Webhook Handler - VERSION 5.0 FIXED
 * KORRIGIERT für: URL-encoded Data, customer_id statt user_id, Marktplatz-Integration
 */

require_once '../config/database.php';

// Logging
function logWebhook($data, $type = 'info') {
    $logFile = __DIR__ . '/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Webhook-Daten empfangen
$rawInput = file_get_contents('php://input');
logWebhook(['raw_input' => $rawInput], 'received');

// WICHTIG: Digistore24 sendet URL-encoded Form-Data, NICHT JSON!
parse_str($rawInput, $webhookData);

if (empty($webhookData)) {
    // Fallback: Versuche JSON
    $webhookData = json_decode($rawInput, true);
    
    if (empty($webhookData)) {
        logWebhook(['error' => 'No data received'], 'error');
        http_response_code(200); // Trotzdem 200 zurück
        exit;
    }
}

logWebhook(['parsed_data' => $webhookData], 'parsed');

try {
    $pdo = getDBConnection();
    
    // Daten extrahieren
    $email = $webhookData['email'] ?? '';
    $firstName = $webhookData['first_name'] ?? '';
    $lastName = $webhookData['last_name'] ?? '';
    $name = trim("$firstName $lastName");
    $orderId = $webhookData['order_id'] ?? '';
    $productId = $webhookData['product_id'] ?? '';
    $productName = $webhookData['product_name'] ?? '';
    
    logWebhook([
        'step' => 'data_extracted',
        'email' => $email,
        'product_id' => $productId,
        'order_id' => $orderId
    ], 'info');
    
    if (empty($email) || empty($productId)) {
        logWebhook(['error' => 'Email or Product ID missing'], 'error');
        http_response_code(200);
        exit;
    }
    
    // MARKTPLATZ-CHECK
    logWebhook(['step' => 'checking_marketplace', 'product_id' => $productId], 'info');
    
    $stmt = $pdo->prepare("
        SELECT id, customer_id, headline, marketplace_price 
        FROM customer_freebies 
        WHERE digistore_product_id = ? 
        AND marketplace_enabled = 1
        LIMIT 1
    ");
    $stmt->execute([$productId]);
    $marketplaceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($marketplaceFreebie) {
        logWebhook([
            'step' => 'marketplace_freebie_found',
            'freebie_id' => $marketplaceFreebie['id'],
            'seller_customer_id' => $marketplaceFreebie['customer_id']
        ], 'marketplace');
        
        // KÄUFER FINDEN ODER ERSTELLEN
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $buyer = $stmt->fetch();
        
        if (!$buyer) {
            // Neuen User erstellen
            logWebhook(['step' => 'creating_new_buyer', 'email' => $email], 'info');
            
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
            $buyerId = $pdo->lastInsertId();
            
            // Limits setzen
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_name, source)
                VALUES (?, 2, 'Marktplatz Käufer', 'marketplace')
            ");
            $stmt->execute([$buyerId]);
            
            logWebhook([
                'step' => 'buyer_created',
                'buyer_id' => $buyerId,
                'raw_code' => $rawCode
            ], 'success');
            
            // Welcome Email senden
            sendWelcomeEmail($email, $name, $password, $rawCode);
            
        } else {
            $buyerId = $buyer['id'];
            logWebhook(['step' => 'buyer_exists', 'buyer_id' => $buyerId], 'info');
        }
        
        // DUPLIKAT-CHECK
        $stmt = $pdo->prepare("
            SELECT id FROM customer_freebies 
            WHERE customer_id = ? AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$buyerId, $marketplaceFreebie['id']]);
        
        if ($stmt->fetch()) {
            logWebhook(['step' => 'already_copied', 'buyer_id' => $buyerId], 'info');
            http_response_code(200);
            exit;
        }
        
        // FREEBIE KOPIEREN
        logWebhook(['step' => 'copying_freebie', 'source_id' => $marketplaceFreebie['id']], 'info');
        
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$marketplaceFreebie['id']]);
        $source = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $uniqueId = bin2hex(random_bytes(16));
        $urlSlug = ($source['url_slug'] ?? 'freebie') . '-' . substr($uniqueId, 0, 8);
        
        $stmt = $pdo->prepare("
            INSERT INTO customer_freebies (
                customer_id, template_id, freebie_type, headline, subheadline, preheadline,
                mockup_image_url, background_color, primary_color, cta_text, bullet_points,
                bullet_icon_style, layout, email_field_text, button_text, privacy_checkbox_text,
                thank_you_headline, thank_you_message, unique_id, url_slug, niche,
                original_creator_id, copied_from_freebie_id, marketplace_enabled, created_at
            ) VALUES (
                ?, ?, 'purchased', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW()
            )
        ");
        
        $stmt->execute([
            $buyerId, $source['template_id'], $source['headline'], $source['subheadline'],
            $source['preheadline'], $source['mockup_image_url'], $source['background_color'],
            $source['primary_color'], $source['cta_text'], $source['bullet_points'],
            $source['bullet_icon_style'] ?? 'standard', $source['layout'],
            $source['email_field_text'], $source['button_text'], $source['privacy_checkbox_text'],
            $source['thank_you_headline'], $source['thank_you_message'],
            $uniqueId, $urlSlug, $source['niche'] ?? 'sonstiges',
            $source['customer_id'], $marketplaceFreebie['id']
        ]);
        
        $copiedFreebieId = $pdo->lastInsertId();
        
        logWebhook([
            'step' => 'freebie_copied',
            'copied_freebie_id' => $copiedFreebieId,
            'buyer_id' => $buyerId
        ], 'success');
        
        // VIDEOKURS KOPIEREN
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
        $stmt->execute([$marketplaceFreebie['id']]);
        $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sourceCourse) {
            logWebhook(['step' => 'copying_videocourse', 'source_course_id' => $sourceCourse['id']], 'info');
            
            // Kurs kopieren
            $stmt = $pdo->prepare("
                INSERT INTO freebie_courses (freebie_id, customer_id, title, description, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$copiedFreebieId, $buyerId, $sourceCourse['title'], $sourceCourse['description'], $sourceCourse['is_active']]);
            $newCourseId = $pdo->lastInsertId();
            
            // Module kopieren
            $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE course_id = ? ORDER BY sort_order");
            $stmt->execute([$sourceCourse['id']]);
            $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $moduleMapping = [];
            foreach ($modules as $module) {
                $stmt = $pdo->prepare("
                    INSERT INTO freebie_course_modules (course_id, title, description, sort_order, unlock_after_days, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$newCourseId, $module['title'], $module['description'], $module['sort_order'], $module['unlock_after_days'] ?? 0]);
                $moduleMapping[$module['id']] = $pdo->lastInsertId();
            }
            
            // Lektionen kopieren
            $lessonCount = 0;
            foreach ($moduleMapping as $oldModuleId => $newModuleId) {
                $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY sort_order");
                $stmt->execute([$oldModuleId]);
                $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($lessons as $lesson) {
                    $stmt = $pdo->prepare("
                        INSERT INTO freebie_course_lessons (module_id, title, description, video_url, pdf_url, sort_order, unlock_after_days, button_text, button_url, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $newModuleId, $lesson['title'], $lesson['description'], $lesson['video_url'],
                        $lesson['pdf_url'] ?? null, $lesson['sort_order'], $lesson['unlock_after_days'] ?? 0,
                        $lesson['button_text'] ?? null, $lesson['button_url'] ?? null
                    ]);
                    $lessonCount++;
                }
            }
            
            logWebhook([
                'step' => 'videocourse_copied',
                'new_course_id' => $newCourseId,
                'modules' => count($moduleMapping),
                'lessons' => $lessonCount
            ], 'success');
        }
        
        // Verkaufszähler erhöhen
        $stmt = $pdo->prepare("UPDATE customer_freebies SET marketplace_sales_count = marketplace_sales_count + 1 WHERE id = ?");
        $stmt->execute([$marketplaceFreebie['id']]);
        
        // Purchase Email
        sendPurchaseEmail($email, $name, $source['headline']);
        
        logWebhook([
            'final_status' => 'COMPLETE_SUCCESS',
            'buyer_id' => $buyerId,
            'copied_freebie_id' => $copiedFreebieId,
            'videocourse_copied' => !empty($sourceCourse)
        ], 'success');
        
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Marketplace freebie copied']);
        exit;
        
    } else {
        // Kein Marktplatz-Freebie
        logWebhook([
            'step' => 'no_marketplace_freebie',
            'product_id' => $productId,
            'message' => 'Not a marketplace product'
        ], 'info');
    }
    
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Webhook received']);
    
} catch (Exception $e) {
    logWebhook(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'error');
    http_response_code(200); // Immer 200 zurückgeben!
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function sendWelcomeEmail($email, $name, $password, $rawCode) {
    $subject = "🎉 Willkommen - Dein Marktplatz-Kauf";
    $message = "Hallo $name,\n\nLogin: $email\nPasswort: $password\nRAW-Code: $rawCode\n\nhttps://app.mehr-infos-jetzt.de/public/login.php";
    mail($email, $subject, $message, "From: noreply@mehr-infos-jetzt.de");
}

function sendPurchaseEmail($email, $name, $freebieTitle) {
    $subject = "✅ Dein Freebie ist verfügbar!";
    $message = "Hallo $name,\n\nDein Freebie \"$freebieTitle\" wurde erfolgreich kopiert!\n\nhttps://app.mehr-infos-jetzt.de/customer/dashboard.php?page=freebies";
    mail($email, $subject, $message, "From: noreply@mehr-infos-jetzt.de");
}
?>
