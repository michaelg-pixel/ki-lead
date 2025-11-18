<?php
/**
 * PATCH: Webhook course_id Fix
 * 
 * Dieses Patch stellt sicher, dass die course_id beim Kopieren
 * von Marktplatz-Freebies immer korrekt übertragen wird.
 * 
 * ANWENDUNG:
 * 1. Backup des aktuellen webhook/digistore24.php erstellen
 * 2. Diesen Code in die bestehende Datei einarbeiten
 * 3. Oder direkt die Funktion copyMarketplaceFreebie() ersetzen
 */

/**
 * VERBESSERTE VERSION: MARKTPLATZ: Kopiert ein Freebie komplett in Käufer-Account
 * 
 * NEU: Prüft explizit auf course_id und loggt, wenn sie fehlt
 */
function copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebieId) {
    // Original-Freebie laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $source = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$source) {
        throw new Exception('Source freebie not found');
    }
    
    // DEBUG: course_id prüfen
    $courseId = $source['course_id'] ?? null;
    logWebhook([
        'info' => 'Copying marketplace freebie',
        'source_freebie_id' => $sourceFreebieId,
        'source_course_id' => $courseId,
        'buyer_id' => $buyerId
    ], 'debug');
    
    // Neues unique_id generieren
    $uniqueId = bin2hex(random_bytes(16));
    
    // WICHTIG: Alle relevanten Felder des Original-Freebies kopieren
    // Insbesondere course_id für die Kurs-Verknüpfung!
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
        $buyerId,                                  // customer_id
        $source['template_id'],                    // template_id
        $source['headline'],                       // headline
        $source['subheadline'],                    // subheadline
        $source['preheadline'],                    // preheadline
        $source['mockup_image_url'],               // mockup_image_url
        $source['background_color'],               // background_color
        $source['primary_color'],                  // primary_color
        $source['cta_text'],                       // cta_text
        $source['bullet_points'],                  // bullet_points
        $source['bullet_icon_style'] ?? 'standard', // bullet_icon_style
        $source['layout'],                         // layout
        $source['email_field_text'],               // email_field_text
        $source['button_text'],                    // button_text
        $source['privacy_checkbox_text'],          // privacy_checkbox_text
        $source['thank_you_headline'],             // thank_you_headline
        $source['thank_you_message'],              // thank_you_message
        $courseId,                                 // course_id - KRITISCH!
        $uniqueId,                                 // unique_id
        $source['url_slug'] ?? '',                 // url_slug
        $source['niche'] ?? 'sonstiges',           // niche
        $source['raw_code'] ?? '',                 // raw_code
        $source['video_url'] ?? '',                // video_url
        $source['video_format'] ?? 'widescreen',   // video_format
        $source['optin_display_mode'] ?? 'direct', // optin_display_mode
        $source['popup_message'] ?? '',            // popup_message
        $source['cta_animation'] ?? 'none',        // cta_animation
        $source['font_heading'] ?? 'Inter',        // font_heading
        $source['font_body'] ?? 'Inter',           // font_body
        $source['font_size'] ?? null,              // font_size (JSON)
        $source['customer_id'],                    // original_creator_id
        $sourceFreebieId                           // copied_from_freebie_id
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
            'warning' => 'Source freebie has no course_id - buyer will not have course access',
            'source_freebie_id' => $sourceFreebieId,
            'copied_freebie_id' => $copiedId
        ], 'warning');
    }
    
    return $copiedId;
}

/**
 * ZUSÄTZLICHE HELPER-FUNKTION: Prüft ob ein Freebie einen Kurs hat
 */
function verifyFreebieHasCourse($pdo, $freebieId) {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id as freebie_id,
            cf.headline,
            cf.course_id,
            c.title as course_title
        FROM customer_freebies cf
        LEFT JOIN courses c ON c.id = cf.course_id
        WHERE cf.id = ?
    ");
    $stmt->execute([$freebieId]);
    $result = $stmt->fetch();
    
    return [
        'has_course' => !empty($result['course_id']),
        'course_id' => $result['course_id'] ?? null,
        'course_title' => $result['course_title'] ?? null,
        'freebie_headline' => $result['headline'] ?? null
    ];
}

/**
 * VERWENDUNG IM WEBHOOK:
 * 
 * Ersetze die bestehende copyMarketplaceFreebie-Funktion in webhook/digistore24.php
 * mit dieser verbesserten Version.
 * 
 * Optional: Nach dem Kopieren die Verifikation aufrufen:
 * 
 * $copiedFreebieId = copyMarketplaceFreebie($pdo, $buyerId, $sourceFreebie['id']);
 * $verification = verifyFreebieHasCourse($pdo, $copiedFreebieId);
 * 
 * logWebhook([
 *     'verification' => $verification
 * ], 'info');
 */

/**
 * TEST-FUNKTION: Simuliert einen Marktplatz-Kauf
 */
function testMarketplacePurchase($pdo) {
    echo "<h2>🧪 Test: Marktplatz-Kauf simulieren</h2>";
    
    // Finde ein Marktplatz-Freebie mit course_id
    $stmt = $pdo->query("
        SELECT id, headline, course_id 
        FROM customer_freebies 
        WHERE marketplace_enabled = 1 
        AND course_id IS NOT NULL 
        LIMIT 1
    ");
    
    $testFreebie = $stmt->fetch();
    
    if (!$testFreebie) {
        echo "<p style='color: orange;'>⚠️ Kein Marktplatz-Freebie mit Kurs gefunden für Test</p>";
        return;
    }
    
    echo "<p><strong>Test-Freebie:</strong> " . htmlspecialchars($testFreebie['headline']) . "</p>";
    echo "<p><strong>Course ID:</strong> " . $testFreebie['course_id'] . "</p>";
    
    // Simuliere Kauf für Test-User
    $testBuyerEmail = 'test-buyer-' . time() . '@example.com';
    
    echo "<p>Simuliere Kauf für: $testBuyerEmail</p>";
    
    // Erstelle Test-Buyer
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, role, is_active, source, created_at)
        VALUES ('Test Buyer', ?, ?, 'customer', 1, 'test', NOW())
    ");
    $stmt->execute([$testBuyerEmail, password_hash('test123', PASSWORD_DEFAULT)]);
    $testBuyerId = $pdo->lastInsertId();
    
    echo "<p>✅ Test-Buyer erstellt (ID: $testBuyerId)</p>";
    
    // Kopiere Freebie
    $copiedId = copyMarketplaceFreebie($pdo, $testBuyerId, $testFreebie['id']);
    
    echo "<p>✅ Freebie kopiert (ID: $copiedId)</p>";
    
    // Verifiziere
    $verification = verifyFreebieHasCourse($pdo, $copiedId);
    
    echo "<h3>Verifikation:</h3>";
    echo "<ul>";
    echo "<li><strong>Hat Kurs:</strong> " . ($verification['has_course'] ? '✅ JA' : '❌ NEIN') . "</li>";
    echo "<li><strong>Kurs-ID:</strong> " . ($verification['course_id'] ?? 'NULL') . "</li>";
    echo "<li><strong>Kurs-Titel:</strong> " . ($verification['course_title'] ?? 'N/A') . "</li>";
    echo "</ul>";
    
    // Cleanup
    $pdo->prepare("DELETE FROM customer_freebies WHERE id = ?")->execute([$copiedId]);
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$testBuyerId]);
    
    echo "<p style='color: green;'>✅ Test abgeschlossen und aufgeräumt</p>";
}

// Wenn direkt aufgerufen, zeige Anweisungen
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Webhook Course ID Patch</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                max-width: 900px;
                margin: 40px auto;
                padding: 20px;
                background: #f5f5f5;
            }
            .code-block {
                background: #1e1e1e;
                color: #d4d4d4;
                padding: 20px;
                border-radius: 8px;
                overflow-x: auto;
                margin: 20px 0;
            }
            .highlight {
                background: #fef3c7;
                padding: 2px 6px;
                border-radius: 3px;
            }
            .success {
                background: #d1fae5;
                border-left: 4px solid #10b981;
                padding: 16px;
                border-radius: 6px;
                margin: 20px 0;
            }
            .warning {
                background: #fef3c7;
                border-left: 4px solid #f59e0b;
                padding: 16px;
                border-radius: 6px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <h1>🔧 Webhook Course ID Patch</h1>
        
        <div class="warning">
            <strong>⚠️ Wichtig:</strong> Dieser Patch behebt das Problem, dass beim Kauf von Marktplatz-Freebies die Kurs-Verknüpfung nicht übertragen wird.
        </div>
        
        <h2>📋 Installationsschritte:</h2>
        
        <ol>
            <li>
                <strong>Backup erstellen:</strong>
                <div class="code-block">
                    cp webhook/digistore24.php webhook/digistore24.php.backup
                </div>
            </li>
            
            <li>
                <strong>Funktion ersetzen:</strong>
                <p>Öffne <code>webhook/digistore24.php</code> und ersetze die Funktion <code>copyMarketplaceFreebie()</code> mit der verbesserten Version aus diesem Patch.</p>
            </li>
            
            <li>
                <strong>Bestehende Käufe reparieren:</strong>
                <p>Führe das Fix-Script aus:</p>
                <div class="code-block">
                    php webhook/fix-marketplace-course-link.php
                </div>
            </li>
        </ol>
        
        <h2>✨ Was wird verbessert:</h2>
        
        <ul>
            <li>✅ <code>course_id</code> wird explizit aus dem Source-Freebie übernommen</li>
            <li>✅ Zusätzliches Logging für Debugging</li>
            <li>✅ Warnung wenn Source-Freebie keine course_id hat</li>
            <li>✅ Alle modernen Freebie-Felder werden korrekt kopiert (Fonts, Videos, etc.)</li>
        </ul>
        
        <div class="success">
            <strong>✅ Nach der Installation:</strong><br>
            Alle zukünftigen Marktplatz-Käufe werden die Kurs-Verknüpfung korrekt übertragen.
        </div>
        
        <h2>🔍 Verifikation:</h2>
        
        <p>Nach der Installation kannst du prüfen ob es funktioniert:</p>
        
        <div class="code-block">
            # Webhook-Logs prüfen
            tail -f webhook/webhook-logs.txt
            
            # Oder über Datenbank:
            SELECT 
                cf.id, 
                cf.headline, 
                cf.course_id, 
                c.title as course_title
            FROM customer_freebies cf
            LEFT JOIN courses c ON c.id = cf.course_id
            WHERE cf.copied_from_freebie_id IS NOT NULL
            AND cf.freebie_type = 'purchased'
            ORDER BY cf.id DESC
            LIMIT 10;
        </div>
        
    </body>
    </html>
    <?php
}
?>
