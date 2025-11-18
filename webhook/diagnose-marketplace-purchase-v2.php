<?php
/**
 * Marktplatz-Kauf Diagnose-Tool
 * Prüft alle Aspekte eines Marktplatz-Kaufs
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

// Safe HTML escape function that handles NULL values
function safeHtml($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$buyerEmail = $_GET['email'] ?? '12@abnehmen-fitness.com';
$sourceFreebieId = $_GET['freebie_id'] ?? 613818;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Marktplatz-Kauf Diagnose</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        h2 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid rgba(255,255,255,0.1);
        }
        th {
            background: rgba(102, 126, 234, 0.2);
        }
        .null-value {
            color: #ef4444;
            font-style: italic;
        }
        .has-value {
            color: #10b981;
        }
        .repair-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 10px 0;
        }
        .repair-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>
    <h1>🔍 Marktplatz-Kauf Diagnose</h1>
    <p>Käufer: <strong><?php echo safeHtml($buyerEmail); ?></strong></p>
    <p>Original-Freebie ID: <strong><?php echo safeHtml($sourceFreebieId); ?></strong></p>

<?php
try {
    $pdo = getDBConnection();
    
    // 1. Käufer suchen
    echo '<div class="section">';
    echo '<h2>1️⃣ Käufer-Account</h2>';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$buyerEmail]);
    $buyer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($buyer) {
        echo '<p class="success">✅ Käufer gefunden: ID ' . $buyer['id'] . '</p>';
        echo '<table>';
        echo '<tr><th>Feld</th><th>Wert</th></tr>';
        foreach ($buyer as $key => $value) {
            if (!in_array($key, ['password'])) {
                echo '<tr><td>' . safeHtml($key) . '</td><td>' . safeHtml($value) . '</td></tr>';
            }
        }
        echo '</table>';
        $buyerId = $buyer['id'];
    } else {
        echo '<p class="error">❌ Käufer nicht gefunden!</p>';
        $buyerId = null;
    }
    echo '</div>';
    
    // 2. Gekaufte Freebies - NUR ALLE FELDER LADEN
    if ($buyerId) {
        echo '<div class="section">';
        echo '<h2>2️⃣ Gekaufte Freebies</h2>';
        
        // Einfach alle Spalten laden ohne spezifische Namen
        $stmt = $pdo->prepare("
            SELECT * FROM customer_freebies 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$buyerId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($freebies) > 0) {
            echo '<p class="success">✅ ' . count($freebies) . ' Freebie(s) gefunden</p>';
            
            foreach ($freebies as $f) {
                $isPurchased = ($f['copied_from_freebie_id'] == $sourceFreebieId);
                
                echo '<div style="margin: 20px 0; padding: 15px; background: rgba(0,0,0,0.3); border-left: 3px solid ' . 
                     ($isPurchased ? '#10b981' : '#667eea') . ';">';
                
                if ($isPurchased) {
                    echo '<p class="success"><strong>🎯 DAS IST DER MARKTPLATZ-KAUF!</strong></p>';
                    
                    // Speichere die ID für die Reparatur
                    $purchasedFreebieId = $f['id'];
                }
                
                echo '<p><strong>Freebie ID: ' . $f['id'] . '</strong></p>';
                
                // Problem-Check
                $emptyFields = [];
                $filledFields = [];
                
                $importantFields = ['headline', 'bullet_points', 'mockup_image_url', 'background_color', 'unique_id'];
                
                foreach ($importantFields as $field) {
                    if (isset($f[$field])) {
                        if (empty($f[$field])) {
                            $emptyFields[] = $field;
                        } else {
                            $filledFields[] = $field;
                        }
                    }
                }
                
                if (count($emptyFields) > 0) {
                    echo '<p class="error">⚠️ LEERE WICHTIGE FELDER: ' . implode(', ', $emptyFields) . '</p>';
                }
                if (count($filledFields) > 0) {
                    echo '<p class="success">✅ GEFÜLLTE FELDER: ' . implode(', ', $filledFields) . '</p>';
                }
                
                echo '<table>';
                echo '<tr><th>Feld</th><th>Wert</th><th>Status</th></tr>';
                
                foreach ($f as $key => $value) {
                    $status = '';
                    $cssClass = '';
                    
                    if (is_null($value) || $value === '') {
                        $status = '<span class="error">⚠️ LEER/NULL!</span>';
                        $cssClass = 'null-value';
                        $displayValue = '(NULL)';
                    } else {
                        $status = '<span class="success">✓</span>';
                        $cssClass = 'has-value';
                        $displayValue = $value;
                        
                        if (strlen($displayValue) > 80) {
                            $displayValue = substr($displayValue, 0, 80) . '... [' . strlen($value) . ' chars]';
                        }
                    }
                    
                    echo '<tr>';
                    echo '<td>' . safeHtml($key) . '</td>';
                    echo '<td class="' . $cssClass . '">' . safeHtml($displayValue) . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }
        } else {
            echo '<p class="error">❌ Keine Freebies gefunden!</p>';
        }
        echo '</div>';
    }
    
    // 3. Original-Freebie
    echo '<div class="section">';
    echo '<h2>3️⃣ Original-Freebie (ID ' . $sourceFreebieId . ')</h2>';
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($original) {
        echo '<p class="success">✅ Original-Freebie gefunden</p>';
        echo '<p><strong>Headline:</strong> ' . safeHtml($original['headline']) . '</p>';
        echo '<p><strong>Verkäufer:</strong> Customer ID ' . $original['customer_id'] . '</p>';
        
        // Wichtige Felder hervorheben
        $hasContent = !empty($original['headline']) && !empty($original['bullet_points']);
        if ($hasContent) {
            echo '<p class="success">✅ Original hat vollständigen Inhalt</p>';
        } else {
            echo '<p class="error">⚠️ Original fehlen Daten!</p>';
        }
        
        echo '<details>';
        echo '<summary style="cursor: pointer; color: #667eea; margin: 10px 0;">📋 Alle Felder anzeigen</summary>';
        echo '<table>';
        echo '<tr><th>Feld</th><th>Wert</th><th>Länge</th></tr>';
        foreach ($original as $key => $value) {
            if (!in_array($key, ['email_api_key'])) {
                $displayValue = $value ?? '(NULL)';
                $length = $value ? strlen($value) : 0;
                
                if (strlen($displayValue) > 100) {
                    $displayValue = substr($displayValue, 0, 100) . '...';
                }
                
                echo '<tr>';
                echo '<td>' . safeHtml($key) . '</td>';
                echo '<td>' . safeHtml($displayValue) . '</td>';
                echo '<td>' . $length . ' chars</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
        echo '</details>';
    } else {
        echo '<p class="error">❌ Original-Freebie nicht gefunden!</p>';
    }
    echo '</div>';
    
    // 4. Marketplace Status
    if ($buyerId) {
        echo '<div class="section">';
        echo '<h2>4️⃣ Marketplace-Status Check</h2>';
        
        // Prüfen, ob das Freebie als "already_purchased" markiert wird
        $stmt = $pdo->prepare("
            SELECT id, headline FROM customer_freebies 
            WHERE customer_id = ? 
            AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$buyerId, $sourceFreebieId]);
        $purchasedFreebie = $stmt->fetch();
        
        if ($purchasedFreebie) {
            echo '<p class="success">✅ System erkennt: Freebie wurde gekauft (ID: ' . $purchasedFreebie['id'] . ')</p>';
            echo '<p class="success">✅ Button sollte anzeigen: "Bereits gekauft"</p>';
            
            // Prüfen ob das gekaufte Freebie Inhalt hat
            if (empty($purchasedFreebie['headline'])) {
                echo '<p class="error">❌ ABER: Das gekaufte Freebie ist LEER!</p>';
            }
        } else {
            echo '<p class="error">❌ System erkennt NICHT, dass Freebie gekauft wurde!</p>';
            echo '<p class="error">❌ Button zeigt weiterhin "Jetzt kaufen"</p>';
        }
        echo '</div>';
    }
    
    // 5. Zusammenfassung & Repair-Button
    echo '<div class="section">';
    echo '<h2>📊 Zusammenfassung & Repair-Tool</h2>';
    
    $hasEmptyFreebie = false;
    $emptyFreebieId = null;
    
    if ($buyerId && isset($freebies)) {
        foreach ($freebies as $f) {
            if ($f['copied_from_freebie_id'] == $sourceFreebieId) {
                if (empty($f['headline']) || empty($f['bullet_points'])) {
                    $hasEmptyFreebie = true;
                    $emptyFreebieId = $f['id'];
                    break;
                }
            }
        }
    }
    
    if ($hasEmptyFreebie && $emptyFreebieId && isset($original)) {
        echo '<div style="background: rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;">';
        echo '<h3 class="error">🔴 HAUPTPROBLEM IDENTIFIZIERT</h3>';
        echo '<p>Das gekaufte Freebie (ID: ' . $emptyFreebieId . ') wurde <strong>OHNE Inhalt</strong> kopiert!</p>';
        echo '<p><strong>Grund:</strong> Der alte Webhook hatte eine leere handleMarketplacePurchase() Funktion.</p>';
        echo '</div>';
        
        echo '<div style="background: rgba(16, 185, 129, 0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #10b981; margin: 20px 0;">';
        echo '<h3 class="success">✅ 1-KLICK REPARATUR</h3>';
        echo '<p>Kopiere alle Daten vom Original-Freebie (ID: ' . $sourceFreebieId . ') zum gekauften Freebie (ID: ' . $emptyFreebieId . '):</p>';
        
        echo '<form method="POST" action="repair-marketplace-purchase.php" style="margin: 20px 0;">';
        echo '<input type="hidden" name="source_id" value="' . $sourceFreebieId . '">';
        echo '<input type="hidden" name="target_id" value="' . $emptyFreebieId . '">';
        echo '<input type="hidden" name="buyer_id" value="' . $buyerId . '">';
        echo '<button type="submit" class="repair-button">🔧 JETZT REPARIEREN</button>';
        echo '</form>';
        
        echo '<p style="font-size: 14px; color: #9ca3af;">Dies kopiert: headline, subheadline, preheadline, bullet_points, mockup_image_url, background_color, primary_color, cta_text, layout, email_field_text, button_text, privacy_checkbox_text, thank_you_headline, thank_you_message, course_id</p>';
        echo '</div>';
        
        echo '<h4>✅ Für zukünftige Käufe</h4>';
        echo '<p class="success">Der Webhook wurde bereits REPARIERT!</p>';
        echo '<p>Alle <strong>NEUEN</strong> Marktplatz-Käufe werden jetzt vollständig kopiert.</p>';
        
    } else if ($buyerId && !$hasEmptyFreebie) {
        echo '<p class="success">✅ Keine Reparatur nötig - Freebie hat Inhalt!</p>';
    } else {
        echo '<p class="warning">⚠️ Keine Reparatur möglich - Daten fehlen</p>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h2 class="error">💥 Fehler</h2>';
    echo '<p class="error">' . safeHtml($e->getMessage()) . '</p>';
    echo '<pre style="font-size: 12px; overflow-x: auto;">' . safeHtml($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>

<div class="section">
    <h2>🔧 Nächste Schritte</h2>
    <ol>
        <li>✅ Webhook-Handler wurde repariert (handleMarketplacePurchase vollständig implementiert)</li>
        <li>🔧 Nutze den "JETZT REPARIEREN" Button oben um diesen Kauf zu fixen</li>
        <li>🧪 Teste einen NEUEN Kauf - der sollte jetzt vollständig funktionieren</li>
        <li>📊 Prüfe, ob "bereits gekauft" Button im Marktplatz erscheint</li>
    </ol>
    
    <p><strong>Test-Links:</strong></p>
    <ul>
        <li><a href="?email=<?php echo urlencode($buyerEmail); ?>&freebie_id=<?php echo $sourceFreebieId; ?>">Diese Diagnose neu laden</a></li>
        <li><a href="/customer/dashboard.php?page=marktplatz">Zum Marktplatz</a></li>
    </ul>
</div>

</body>
</html>