<?php
/**
 * DIAGNOSE: Marktplatz-Kauf analysieren
 * 
 * Prüft was beim Testkauf schief gelaufen ist:
 * - Wurde das Freebie korrekt kopiert?
 * - Welche Felder sind leer?
 * - Was steht im Webhook-Log?
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .error { background: #fee; border-left: 4px solid #f00; }
        .success { background: #efe; border-left: 4px solid #0f0; }
        .warning { background: #ffe; border-left: 4px solid #fa0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
        .empty { color: #f00; font-weight: bold; }
        .filled { color: #0a0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>";
    
    echo "<h1>🔍 Marktplatz-Kauf Diagnose</h1>";
    
    // SCHRITT 1: Käufer finden
    echo "<div class='box'>";
    echo "<h2>1️⃣ Käufer-Account</h2>";
    
    $buyerEmail = '12@abnehmen-fitness.com';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$buyerEmail]);
    $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$buyer) {
        echo "<div class='error'><strong>❌ Käufer nicht gefunden!</strong><br>Email: $buyerEmail</div>";
        echo "</div>";
        exit;
    }
    
    echo "<p><strong>✅ Käufer gefunden:</strong></p>";
    echo "<table>";
    echo "<tr><th>ID</th><td>" . $buyer['id'] . "</td></tr>";
    echo "<tr><th>Name</th><td>" . htmlspecialchars($buyer['name']) . "</td></tr>";
    echo "<tr><th>Email</th><td>" . htmlspecialchars($buyer['email']) . "</td></tr>";
    echo "<tr><th>Source</th><td>" . htmlspecialchars($buyer['source'] ?? 'N/A') . "</td></tr>";
    echo "<tr><th>Created</th><td>" . $buyer['created_at'] . "</td></tr>";
    echo "</table>";
    echo "</div>";
    
    $buyerId = $buyer['id'];
    
    // SCHRITT 2: Gekaufte Freebies finden
    echo "<div class='box'>";
    echo "<h2>2️⃣ Gekaufte Freebies</h2>";
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE customer_id = ? 
        AND freebie_type = 'purchased'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$buyerId]);
    $purchasedFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($purchasedFreebies)) {
        echo "<div class='error'><strong>❌ Keine gekauften Freebies gefunden!</strong></div>";
        echo "</div>";
        exit;
    }
    
    echo "<p><strong>✅ Gefunden: " . count($purchasedFreebies) . " gekaufte(s) Freebie(s)</strong></p>";
    
    foreach ($purchasedFreebies as $purchased) {
        echo "<div style='border: 2px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<h3>📦 Freebie ID: " . $purchased['id'] . "</h3>";
        
        echo "<table>";
        echo "<tr><th style='width: 200px;'>Feld</th><th>Wert</th><th>Status</th></tr>";
        
        // Wichtige Felder prüfen
        $fieldsToCheck = [
            'headline' => 'Headline',
            'subheadline' => 'Subheadline',
            'preheadline' => 'Preheadline',
            'bullet_points' => 'Bullet Points',
            'cta_text' => 'CTA Text',
            'layout' => 'Layout',
            'background_color' => 'Background Color',
            'primary_color' => 'Primary Color',
            'mockup_image_url' => 'Mockup URL',
            'video_url' => 'Video URL',
            'course_id' => 'Course ID',
            'raw_code' => 'Raw Code',
            'copied_from_freebie_id' => 'Original Freebie ID',
            'original_creator_id' => 'Original Creator ID',
            'unique_id' => 'Unique ID',
            'url_slug' => 'URL Slug',
            'niche' => 'Nische',
            'font_heading' => 'Font Heading',
            'font_body' => 'Font Body',
            'font_size' => 'Font Size (JSON)'
        ];
        
        foreach ($fieldsToCheck as $field => $label) {
            $value = $purchased[$field] ?? null;
            $isEmpty = empty($value);
            $statusClass = $isEmpty ? 'empty' : 'filled';
            $statusIcon = $isEmpty ? '❌' : '✅';
            
            $displayValue = $isEmpty ? '<span class="empty">LEER</span>' : '<span class="filled">' . htmlspecialchars(substr($value, 0, 100)) . ($value && strlen($value) > 100 ? '...' : '') . '</span>';
            
            echo "<tr>";
            echo "<td><strong>$label</strong></td>";
            echo "<td>$displayValue</td>";
            echo "<td class='$statusClass'>$statusIcon</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // SCHRITT 3: Original-Freebie prüfen
        if ($purchased['copied_from_freebie_id']) {
            echo "<div class='box'>";
            echo "<h2>3️⃣ Original-Freebie (ID: " . $purchased['copied_from_freebie_id'] . ")</h2>";
            
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
            $stmt->execute([$purchased['copied_from_freebie_id']]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$original) {
                echo "<div class='error'><strong>❌ Original-Freebie nicht gefunden!</strong></div>";
            } else {
                echo "<table>";
                echo "<tr><th style='width: 200px;'>Feld</th><th>Original-Wert</th><th>Gekauft-Wert</th><th>Status</th></tr>";
                
                foreach ($fieldsToCheck as $field => $label) {
                    if ($field === 'copied_from_freebie_id' || $field === 'original_creator_id' || $field === 'unique_id' || $field === 'url_slug') {
                        continue; // Diese Felder sind unterschiedlich
                    }
                    
                    $originalValue = $original[$field] ?? null;
                    $purchasedValue = $purchased[$field] ?? null;
                    
                    $match = $originalValue === $purchasedValue;
                    $statusIcon = $match ? '✅' : '❌';
                    $statusClass = $match ? 'filled' : 'empty';
                    
                    $origDisplay = empty($originalValue) ? '<span class="empty">LEER</span>' : htmlspecialchars(substr($originalValue, 0, 50)) . (strlen($originalValue) > 50 ? '...' : '');
                    $purchDisplay = empty($purchasedValue) ? '<span class="empty">LEER</span>' : htmlspecialchars(substr($purchasedValue, 0, 50)) . (strlen($purchasedValue) > 50 ? '...' : '');
                    
                    echo "<tr>";
                    echo "<td><strong>$label</strong></td>";
                    echo "<td>$origDisplay</td>";
                    echo "<td>$purchDisplay</td>";
                    echo "<td class='$statusClass'>$statusIcon</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Zusammenfassung
                echo "<h3>📊 Zusammenfassung</h3>";
                
                $emptyInOriginal = 0;
                $emptyInPurchased = 0;
                $notCopied = 0;
                
                foreach ($fieldsToCheck as $field => $label) {
                    if ($field === 'copied_from_freebie_id' || $field === 'original_creator_id' || $field === 'unique_id' || $field === 'url_slug') {
                        continue;
                    }
                    
                    if (empty($original[$field])) $emptyInOriginal++;
                    if (empty($purchased[$field])) $emptyInPurchased++;
                    if ($original[$field] !== $purchased[$field]) $notCopied++;
                }
                
                echo "<ul>";
                echo "<li>Original hat <strong>$emptyInOriginal</strong> leere Felder</li>";
                echo "<li>Gekauftes Freebie hat <strong>$emptyInPurchased</strong> leere Felder</li>";
                echo "<li><strong class='" . ($notCopied > 0 ? 'empty' : 'filled') . "'>$notCopied Felder wurden nicht korrekt kopiert!</strong></li>";
                echo "</ul>";
                
                if ($notCopied > 0) {
                    echo "<div class='error'>";
                    echo "<h4>❌ Problem gefunden!</h4>";
                    echo "<p>Es wurden <strong>$notCopied Felder</strong> nicht korrekt vom Original kopiert!</p>";
                    echo "<p><strong>Mögliche Ursachen:</strong></p>";
                    echo "<ul>";
                    echo "<li>Webhook kopiert nicht alle Felder</li>";
                    echo "<li>Datenbankfehler beim INSERT</li>";
                    echo "<li>Felder existieren nicht in der Tabelle</li>";
                    echo "</ul>";
                    echo "</div>";
                }
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // SCHRITT 4: Webhook-Logs prüfen
    echo "<div class='box'>";
    echo "<h2>4️⃣ Webhook-Logs (letzte 50 Zeilen)</h2>";
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    
    if (file_exists($logFile)) {
        $logs = file($logFile);
        $recentLogs = array_slice($logs, -50);
        
        echo "<pre style='max-height: 400px; overflow-y: auto;'>";
        foreach ($recentLogs as $log) {
            // Highlight wichtige Keywords
            $log = str_replace('marketplace', '<strong style="color: blue;">marketplace</strong>', $log);
            $log = str_replace('ERROR', '<strong style="color: red;">ERROR</strong>', $log);
            $log = str_replace('SUCCESS', '<strong style="color: green;">SUCCESS</strong>', $log);
            $log = str_replace($buyerEmail, '<strong style="background: yellow;">' . $buyerEmail . '</strong>', $log);
            
            echo $log;
        }
        echo "</pre>";
    } else {
        echo "<div class='warning'><strong>⚠️ Keine Webhook-Logs gefunden!</strong><br>Pfad: $logFile</div>";
    }
    
    echo "</div>";
    
    // SCHRITT 5: Marktplatz-Button-Status prüfen
    echo "<div class='box'>";
    echo "<h2>5️⃣ Marktplatz-Button-Status</h2>";
    
    echo "<p>Der Button zeigt noch 'Jetzt kaufen' statt 'Bereits gekauft'. Das liegt daran, dass die Marktplatz-Seite nicht prüft, ob der Käufer das Freebie bereits gekauft hat.</p>";
    
    // Prüfe ob Käufer das Freebie hat
    if (!empty($purchasedFreebies)) {
        $originalId = $purchasedFreebies[0]['copied_from_freebie_id'];
        
        echo "<div class='success'>";
        echo "<p><strong>✅ Käufer hat das Freebie gekauft!</strong></p>";
        echo "<p>Original Freebie ID: $originalId</p>";
        echo "<p>Die Marktplatz-Seite muss aktualisiert werden, um diesen Status zu zeigen.</p>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // SCHRITT 6: Empfohlene Fixes
    echo "<div class='box error'>";
    echo "<h2>🔧 Empfohlene Fixes</h2>";
    
    echo "<ol>";
    echo "<li><strong>Webhook aktualisieren:</strong> Stelle sicher, dass die copyMarketplaceFreebie() Funktion ALLE Felder kopiert</li>";
    echo "<li><strong>Datenbank-Struktur prüfen:</strong> Alle Felder müssen in customer_freebies existieren</li>";
    echo "<li><strong>Marktplatz-Button-Check hinzufügen:</strong> Prüfen ob Käufer Freebie bereits besitzt</li>";
    echo "<li><strong>Dieses gekaufte Freebie manuell reparieren:</strong> Felder vom Original kopieren</li>";
    echo "</ol>";
    
    echo "</div>";
    
    // SCHRITT 7: Quick Fix anbieten
    if (!empty($purchasedFreebies) && !empty($original)) {
        echo "<div class='box warning'>";
        echo "<h2>⚡ Quick Fix verfügbar</h2>";
        
        echo "<p>Möchtest du das gekaufte Freebie automatisch reparieren?</p>";
        
        echo "<form method='POST'>";
        echo "<input type='hidden' name='fix_freebie_id' value='" . $purchasedFreebies[0]['id'] . "'>";
        echo "<input type='hidden' name='original_freebie_id' value='" . $purchasedFreebies[0]['copied_from_freebie_id'] . "'>";
        echo "<button type='submit' name='do_fix' style='padding: 15px 30px; background: #4CAF50; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold;'>";
        echo "🔧 Jetzt reparieren";
        echo "</button>";
        echo "</form>";
        
        echo "</div>";
    }
    
    // Quick Fix ausführen
    if (isset($_POST['do_fix']) && isset($_POST['fix_freebie_id']) && isset($_POST['original_freebie_id'])) {
        $fixId = intval($_POST['fix_freebie_id']);
        $origId = intval($_POST['original_freebie_id']);
        
        echo "<div class='box success'>";
        echo "<h2>⚡ Reparatur wird durchgeführt...</h2>";
        
        // Original laden
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$origId]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($orig) {
            // Update durchführen
            $stmt = $pdo->prepare("
                UPDATE customer_freebies SET
                    headline = ?,
                    subheadline = ?,
                    preheadline = ?,
                    bullet_points = ?,
                    bullet_icon_style = ?,
                    cta_text = ?,
                    layout = ?,
                    background_color = ?,
                    primary_color = ?,
                    mockup_image_url = ?,
                    video_url = ?,
                    video_format = ?,
                    course_id = ?,
                    raw_code = ?,
                    niche = ?,
                    font_heading = ?,
                    font_body = ?,
                    font_size = ?,
                    optin_display_mode = ?,
                    popup_message = ?,
                    cta_animation = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $orig['headline'],
                $orig['subheadline'],
                $orig['preheadline'],
                $orig['bullet_points'],
                $orig['bullet_icon_style'],
                $orig['cta_text'],
                $orig['layout'],
                $orig['background_color'],
                $orig['primary_color'],
                $orig['mockup_image_url'],
                $orig['video_url'],
                $orig['video_format'],
                $orig['course_id'],
                $orig['raw_code'],
                $orig['niche'],
                $orig['font_heading'],
                $orig['font_body'],
                $orig['font_size'],
                $orig['optin_display_mode'],
                $orig['popup_message'],
                $orig['cta_animation'],
                $fixId
            ]);
            
            echo "<p><strong>✅ Freebie erfolgreich repariert!</strong></p>";
            echo "<p><a href='?refresh=1' style='color: blue; text-decoration: underline;'>🔄 Seite neu laden</a> um das Ergebnis zu sehen.</p>";
        } else {
            echo "<p><strong>❌ Fehler: Original-Freebie nicht gefunden!</strong></p>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box error'>";
    echo "<h2>❌ Fehler</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>
