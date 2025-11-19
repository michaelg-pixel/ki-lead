<?php
/**
 * Enhanced Webhook Handler - VERSION 4.8
 * FIX: Kopiert Videokurse aus freebie_courses (nicht aus courses!)
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
    
    if (empty($email) || empty($productId)) {
        throw new Exception('Email and Product ID are required');
    }
    
    // SCHRITT 1: Neues flexibles Webhook-System prüfen
    $webhookConfig = findWebhookConfiguration($pdo, $productId);
    
    if ($webhookConfig) {
        processFlexibleWebhook($pdo, $webhookConfig, $email, $name, $orderId, $productId, $data);
        return;
    }
    
    // SCHRITT 2: Altes System als Fallback
    $legacyProduct = getProductConfig($pdo, $productId);
    
    if ($legacyProduct) {
        processLegacyWebhook($pdo, $legacyProduct, $email, $name, $orderId, $productId, $data);
        return;
    }
    
    // SCHRITT 3: Marktplatz-Check
    $marketplaceFreebie = checkMarketplacePurchase($pdo, $productId);
    if ($marketplaceFreebie) {
        handleMarketplacePurchase($pdo, $email, $name, $productId, $marketplaceFreebie, $orderId);
        return;
    }
    
    logWebhook([
        'warning' => 'No webhook configuration found for product_id',
        'product_id' => $productId,
        'email' => $email
    ], 'warning');
}

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
 * Holt alle existierenden Spalten der Tabelle
 */
function getTableColumns($pdo, $table) {
    $stmt = $pdo->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return $columns;
}

/**
 * NEU: Kopiert Videokurs-Daten aus freebie_courses System
 * VERSION 4.8: Komplett neu geschrieben für freebie_courses!
 */
function copyFreebieVideoCourse($pdo, $sourceFreebieId, $targetFreebieId, $buyerId) {
    logWebhook([
        'info' => 'Starting freebie video course copy',
        'source_freebie_id' => $sourceFreebieId,
        'target_freebie_id' => $targetFreebieId,
        'buyer_id' => $buyerId
    ], 'info');
    
    try {
        // 1. Prüfen ob Source-Freebie einen Videokurs hat
        $stmt = $pdo->prepare("
            SELECT * FROM freebie_courses 
            WHERE freebie_id = ?
        ");
        $stmt->execute([$sourceFreebieId]);
        $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourceCourse) {
            logWebhook([
                'info' => 'Source freebie has no video course',
                'source_freebie_id' => $sourceFreebieId
            ], 'info');
            return;
        }
        
        logWebhook([
            'success' => 'Source video course found',
            'course_id' => $sourceCourse['id'],
            'course_title' => $sourceCourse['title']
        ], 'success');
        
        // 2. Videokurs-Eintrag für Käufer erstellen
        $stmt = $pdo->prepare("
            INSERT INTO freebie_courses (
                freebie_id,
                customer_id,
                title,
                description,
                is_active,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $targetFreebieId,
            $buyerId,
            $sourceCourse['title'],
            $sourceCourse['description'],
            $sourceCourse['is_active']
        ]);
        
        $newCourseId = $pdo->lastInsertId();
        
        logWebhook([
            'success' => 'Video course container created',
            'new_course_id' => $newCourseId,
            'title' => $sourceCourse['title']
        ], 'success');
        
        // 3. Alle Module des Kurses kopieren
        $stmt = $pdo->prepare("
            SELECT * FROM freebie_course_modules 
            WHERE course_id = ?
            ORDER BY sort_order
        ");
        $stmt->execute([$sourceCourse['id']]);
        $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $moduleMapping = []; // Original-ID => Neue-ID Mapping
        
        foreach ($sourceModules as $sourceModule) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_modules (
                    course_id,
                    title,
                    description,
                    sort_order,
                    unlock_after_days,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $newCourseId,
                $sourceModule['title'],
                $sourceModule['description'],
                $sourceModule['sort_order'],
                $sourceModule['unlock_after_days'] ?? 0
            ]);
            
            $newModuleId = $pdo->lastInsertId();
            $moduleMapping[$sourceModule['id']] = $newModuleId;
            
            logWebhook([
                'success' => 'Module copied',
                'original_module_id' => $sourceModule['id'],
                'new_module_id' => $newModuleId,
                'title' => $sourceModule['title']
            ], 'success');
        }
        
        // 4. Alle Lektionen kopieren
        $totalLessonsCopied = 0;
        
        foreach ($moduleMapping as $oldModuleId => $newModuleId) {
            $stmt = $pdo->prepare("
                SELECT * FROM freebie_course_lessons 
                WHERE module_id = ?
                ORDER BY sort_order
            ");
            $stmt->execute([$oldModuleId]);
            $sourceLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sourceLessons as $sourceLesson) {
                $stmt = $pdo->prepare("
                    INSERT INTO freebie_course_lessons (
                        module_id,
                        title,
                        description,
                        video_url,
                        pdf_url,
                        sort_order,
                        unlock_after_days,
                        button_text,
                        button_url,
                        created_at,
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
                
                $totalLessonsCopied++;
                
                logWebhook([
                    'success' => 'Lesson copied',
                    'lesson_title' => $sourceLesson['title'],
                    'video_url' => $sourceLesson['video_url']
                ], 'success');
            }
        }
        
        logWebhook([
            'success' => 'Complete video course copied successfully!',
            'source_freebie_id' => $sourceFreebieId,
            'target_freebie_id' => $targetFreebieId,
            'new_course_id' => $newCourseId,
            'modules_copied' => count($moduleMapping),
            'lessons_copied' => $totalLessonsCopied,
            'buyer_id' => $buyerId
        ], 'marketplace_course_copy');
        
    } catch (Exception $e) {
        logWebhook([
            'error' => 'Video course copy failed',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 'error');
        // Nicht werfen - Freebie soll trotzdem funktionieren
    }
}

/**
 * MARKTPLATZ: SMART - Kopiert ALLE existierenden Felder automatisch + VIDEOKURS
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
            return;
        }
        
        // 3. Original-Freebie ALLE Felder laden
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$sourceFreebie['id']]);
        $fullSourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Original Owner ID merken (für Kurs-Copy)
        $sourceCustomerId = $fullSourceFreebie['customer_id'];
        
        logWebhook([
            'info' => 'Source freebie loaded',
            'fields_count' => count($fullSourceFreebie),
            'has_bullet_points' => isset($fullSourceFreebie['bullet_points']),
            'has_mockup' => isset($fullSourceFreebie['mockup_image_url']),
            'source_customer_id' => $sourceCustomerId
        ], 'info');
        
        // 4. Tabellenstruktur prüfen - welche Spalten existieren?
        $tableColumns = getTableColumns($pdo, 'customer_freebies');
        
        // 5. Felder die NICHT kopiert werden sollen (Blacklist)
        $excludedFields = [
            'id',
            'customer_id',  // wird durch $buyerId ersetzt
            'created_at',   // wird neu gesetzt
            'updated_at',   // wird automatisch gesetzt
            'marketplace_enabled',  // wird auf 0 gesetzt für Kopie
            'marketplace_sales_count',  // startet bei 0
            'digistore_product_id',  // wird entfernt bei Kopie
            'digistore_order_id',  // wird mit aktuellem Order-ID ersetzt
            'freebie_type',  // WICHTIG: Nicht kopieren, da Feld möglicherweise ENUM oder zu kurz ist
            'course_id'  // WICHTIG: Wird NICHT kopiert (ist eh NULL beim Original)
        ];
        
        // 6. ALLE Felder kopieren die existieren UND nicht ausgeschlossen sind
        $fieldsToCopy = [];
        $values = [];
        
        // customer_id setzen
        $fieldsToCopy[] = 'customer_id';
        $values[] = $buyerId;
        
        // ALLE Felder aus Source-Freebie durchgehen
        foreach ($fullSourceFreebie as $field => $value) {
            if (in_array($field, $tableColumns) && 
                !in_array($field, $excludedFields) && 
                !in_array($field, $fieldsToCopy)) {
                
                $fieldsToCopy[] = $field;
                $values[] = $value;
            }
        }
        
        // 7. Spezielle Felder überschreiben/setzen
        
        // unique_id generieren
        if (in_array('unique_id', $tableColumns)) {
            $uniqueIdIndex = array_search('unique_id', $fieldsToCopy);
            if ($uniqueIdIndex !== false) {
                $values[$uniqueIdIndex] = bin2hex(random_bytes(16));
            } else {
                $fieldsToCopy[] = 'unique_id';
                $values[] = bin2hex(random_bytes(16));
            }
        }
        
        // Marketplace-spezifische Felder
        if (in_array('original_creator_id', $tableColumns)) {
            $originalCreatorIndex = array_search('original_creator_id', $fieldsToCopy);
            if ($originalCreatorIndex !== false) {
                $values[$originalCreatorIndex] = $sourceCustomerId;
            } else {
                $fieldsToCopy[] = 'original_creator_id';
                $values[] = $sourceCustomerId;
            }
        }
        
        if (in_array('copied_from_freebie_id', $tableColumns)) {
            $copiedFromIndex = array_search('copied_from_freebie_id', $fieldsToCopy);
            if ($copiedFromIndex !== false) {
                $values[$copiedFromIndex] = $fullSourceFreebie['id'];
            } else {
                $fieldsToCopy[] = 'copied_from_freebie_id';
                $values[] = $fullSourceFreebie['id'];
            }
        }
        
        // marketplace_enabled auf 0 setzen
        if (in_array('marketplace_enabled', $tableColumns)) {
            $marketplaceIndex = array_search('marketplace_enabled', $fieldsToCopy);
            if ($marketplaceIndex !== false) {
                $values[$marketplaceIndex] = 0;
            } else {
                $fieldsToCopy[] = 'marketplace_enabled';
                $values[] = 0;
            }
        }
        
        // marketplace_sales_count auf 0 setzen
        if (in_array('marketplace_sales_count', $tableColumns)) {
            $salesCountIndex = array_search('marketplace_sales_count', $fieldsToCopy);
            if ($salesCountIndex !== false) {
                $values[$salesCountIndex] = 0;
            } else {
                $fieldsToCopy[] = 'marketplace_sales_count';
                $values[] = 0;
            }
        }
        
        // digistore_order_id
        if (in_array('digistore_order_id', $tableColumns)) {
            $orderIdIndex = array_search('digistore_order_id', $fieldsToCopy);
            if ($orderIdIndex !== false) {
                $values[$orderIdIndex] = $orderId;
            } else {
                $fieldsToCopy[] = 'digistore_order_id';
                $values[] = $orderId;
            }
        }
        
        // digistore_product_id NICHT setzen
        $productIdIndex = array_search('digistore_product_id', $fieldsToCopy);
        if ($productIdIndex !== false) {
            unset($fieldsToCopy[$productIdIndex]);
            unset($values[$productIdIndex]);
            $fieldsToCopy = array_values($fieldsToCopy);
            $values = array_values($values);
        }
        
        // created_at
        $createdAtIndex = array_search('created_at', $fieldsToCopy);
        if ($createdAtIndex !== false) {
            $values[$createdAtIndex] = date('Y-m-d H:i:s');
        } else {
            $fieldsToCopy[] = 'created_at';
            $values[] = date('Y-m-d H:i:s');
        }
        
        // 8. SQL Query dynamisch bauen
        $placeholders = array_fill(0, count($values), '?');
        $sql = "INSERT INTO customer_freebies (" . implode(', ', $fieldsToCopy) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        logWebhook([
            'info' => 'Dynamic INSERT prepared',
            'fields_count' => count($fieldsToCopy),
            'fields' => $fieldsToCopy
        ], 'info');
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        $newFreebieId = $pdo->lastInsertId();
        
        logWebhook([
            'success' => 'Freebie copied successfully',
            'new_freebie_id' => $newFreebieId,
            'buyer_id' => $buyerId,
            'fields_copied' => count($fieldsToCopy)
        ], 'success');
        
        // 9. NEU: VIDEOKURS KOPIEREN aus freebie_courses!
        copyFreebieVideoCourse($pdo, $sourceFreebie['id'], $newFreebieId, $buyerId);
        
        // 10. Verkaufszähler beim Original erhöhen
        if (in_array('marketplace_sales_count', $tableColumns)) {
            $stmt = $pdo->prepare("
                UPDATE customer_freebies 
                SET marketplace_sales_count = COALESCE(marketplace_sales_count, 0) + 1
                WHERE id = ?
            ");
            $stmt->execute([$sourceFreebie['id']]);
            
            logWebhook([
                'info' => 'Sales counter updated',
                'source_freebie_id' => $sourceFreebie['id']
            ], 'info');
        }
        
        logWebhook([
            'success' => 'Marketplace purchase completed',
            'buyer_email' => $buyerEmail,
            'new_freebie_id' => $newFreebieId
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
                
                <p style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;'>
                    Nach dem Login findest du dein gekauftes Freebie unter:<br>
                    <strong>Meine Freebies</strong>
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

// Stub functions
function processFlexibleWebhook($pdo, $config, $email, $name, $orderId, $productId, $data) {}
function processLegacyWebhook($pdo, $product, $email, $name, $orderId, $productId, $data) {}
function handleRefund($pdo, $data) {}
function handleSubscriptionEnd($pdo, $data) {}
