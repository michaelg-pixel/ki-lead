<?php
/**
 * Enhanced Webhook Handler - VERSION 4.7
 * FIX: Course linked via customer_id, not freebie_id
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
 * Kopiert Videokurs-Daten - KORRIGIERTE VERSION
 * Kurs ist mit customer_id verknüpft, NICHT mit freebie_id!
 */
function copyCourseData($pdo, $sourceFreebieId, $newFreebieId, $newCustomerId, $sourceCustomerId) {
    logWebhook([
        'info' => 'Starting course copy',
        'source_freebie_id' => $sourceFreebieId,
        'new_freebie_id' => $newFreebieId,
        'source_customer_id' => $sourceCustomerId,
        'new_customer_id' => $newCustomerId
    ], 'info');
    
    try {
        // 1. Kurs des Original-Owners finden (via customer_id)
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE customer_id = ? LIMIT 1");
        $stmt->execute([$sourceCustomerId]);
        $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourceCourse) {
            logWebhook(['info' => 'No course found for source customer', 'customer_id' => $sourceCustomerId], 'info');
            return;
        }
        
        logWebhook(['success' => 'Course found', 'course_id' => $sourceCourse['id'], 'course_name' => $sourceCourse['course_name'] ?? 'N/A'], 'success');
        
        // 2. Kurs kopieren
        $courseColumns = getTableColumns($pdo, 'courses');
        $courseFieldsToCopy = [];
        $courseValues = [];
        
        // Blacklist für Kurs-Felder
        $excludedCourseFields = ['id', 'customer_id', 'created_at', 'updated_at'];
        
        foreach ($sourceCourse as $field => $value) {
            if (in_array($field, $courseColumns) && 
                !in_array($field, $excludedCourseFields) && 
                !in_array($field, $courseFieldsToCopy)) {
                
                $courseFieldsToCopy[] = $field;
                $courseValues[] = $value;
            }
        }
        
        // customer_id setzen (neuer Owner)
        $courseFieldsToCopy[] = 'customer_id';
        $courseValues[] = $newCustomerId;
        
        // created_at setzen
        $courseFieldsToCopy[] = 'created_at';
        $courseValues[] = date('Y-m-d H:i:s');
        
        // Kurs einfügen
        $placeholders = array_fill(0, count($courseValues), '?');
        $sql = "INSERT INTO courses (" . implode(', ', $courseFieldsToCopy) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($courseValues);
        $newCourseId = $pdo->lastInsertId();
        
        logWebhook([
            'success' => 'Course copied',
            'new_course_id' => $newCourseId,
            'fields_copied' => count($courseFieldsToCopy)
        ], 'success');
        
        // 3. Module kopieren
        $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY module_order");
        $stmt->execute([$sourceCourse['id']]);
        $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $moduleMapping = []; // Alt-ID => Neu-ID
        
        foreach ($sourceModules as $sourceModule) {
            $moduleColumns = getTableColumns($pdo, 'course_modules');
            $moduleFieldsToCopy = [];
            $moduleValues = [];
            
            $excludedModuleFields = ['id', 'created_at', 'updated_at'];
            
            foreach ($sourceModule as $field => $value) {
                if (in_array($field, $moduleColumns) && 
                    !in_array($field, $excludedModuleFields) && 
                    !in_array($field, $moduleFieldsToCopy)) {
                    
                    $moduleFieldsToCopy[] = $field;
                    $moduleValues[] = $value;
                }
            }
            
            // course_id auf neuen Kurs setzen
            $courseIdIndex = array_search('course_id', $moduleFieldsToCopy);
            if ($courseIdIndex !== false) {
                $moduleValues[$courseIdIndex] = $newCourseId;
            } else {
                $moduleFieldsToCopy[] = 'course_id';
                $moduleValues[] = $newCourseId;
            }
            
            $moduleFieldsToCopy[] = 'created_at';
            $moduleValues[] = date('Y-m-d H:i:s');
            
            $placeholders = array_fill(0, count($moduleValues), '?');
            $sql = "INSERT INTO course_modules (" . implode(', ', $moduleFieldsToCopy) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($moduleValues);
            $newModuleId = $pdo->lastInsertId();
            
            $moduleMapping[$sourceModule['id']] = $newModuleId;
            
            logWebhook([
                'info' => 'Module copied',
                'old_module_id' => $sourceModule['id'],
                'new_module_id' => $newModuleId,
                'module_name' => $sourceModule['module_name'] ?? 'N/A'
            ], 'info');
        }
        
        // 4. Lektionen kopieren
        $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE course_id = ? ORDER BY lesson_order");
        $stmt->execute([$sourceCourse['id']]);
        $sourceLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sourceLessons as $sourceLesson) {
            $lessonColumns = getTableColumns($pdo, 'course_lessons');
            $lessonFieldsToCopy = [];
            $lessonValues = [];
            
            $excludedLessonFields = ['id', 'created_at', 'updated_at'];
            
            foreach ($sourceLesson as $field => $value) {
                if (in_array($field, $lessonColumns) && 
                    !in_array($field, $excludedLessonFields) && 
                    !in_array($field, $lessonFieldsToCopy)) {
                    
                    $lessonFieldsToCopy[] = $field;
                    $lessonValues[] = $value;
                }
            }
            
            // course_id auf neuen Kurs setzen
            $courseIdIndex = array_search('course_id', $lessonFieldsToCopy);
            if ($courseIdIndex !== false) {
                $lessonValues[$courseIdIndex] = $newCourseId;
            } else {
                $lessonFieldsToCopy[] = 'course_id';
                $lessonValues[] = $newCourseId;
            }
            
            // Module-ID umschreiben (Alt-ID => Neu-ID)
            if (!empty($sourceLesson['module_id']) && isset($moduleMapping[$sourceLesson['module_id']])) {
                $moduleIdIndex = array_search('module_id', $lessonFieldsToCopy);
                if ($moduleIdIndex !== false) {
                    $lessonValues[$moduleIdIndex] = $moduleMapping[$sourceLesson['module_id']];
                } else {
                    $lessonFieldsToCopy[] = 'module_id';
                    $lessonValues[] = $moduleMapping[$sourceLesson['module_id']];
                }
            }
            
            $lessonFieldsToCopy[] = 'created_at';
            $lessonValues[] = date('Y-m-d H:i:s');
            
            $placeholders = array_fill(0, count($lessonValues), '?');
            $sql = "INSERT INTO course_lessons (" . implode(', ', $lessonFieldsToCopy) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($lessonValues);
            $newLessonId = $pdo->lastInsertId();
            
            logWebhook([
                'info' => 'Lesson copied',
                'old_lesson_id' => $sourceLesson['id'],
                'new_lesson_id' => $newLessonId,
                'lesson_title' => $sourceLesson['lesson_title'] ?? 'N/A'
            ], 'info');
        }
        
        logWebhook([
            'success' => 'Course data copied completely',
            'new_course_id' => $newCourseId,
            'modules_copied' => count($sourceModules),
            'lessons_copied' => count($sourceLessons)
        ], 'success');
        
    } catch (Exception $e) {
        logWebhook([
            'error' => 'Course copy failed',
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
        
        // 9. VIDEOKURS KOPIEREN - KORRIGIERT: Mit source_customer_id Parameter
        copyCourseData($pdo, $sourceFreebie['id'], $newFreebieId, $buyerId, $sourceCustomerId);
        
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