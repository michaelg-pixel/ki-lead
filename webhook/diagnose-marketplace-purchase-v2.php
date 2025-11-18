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
    
    // 2. Gekaufte Freebies
    if ($buyerId) {
        echo '<div class="section">';
        echo '<h2>2️⃣ Gekaufte Freebies</h2>';
        $stmt = $pdo->prepare("
            SELECT 
                id,
                headline,
                freebie_type,
                copied_from_freebie_id,
                original_creator_id,
                mockup_image_url,
                background_color,
                bullet_points,
                email_provider,
                course_id,
                unique_id,
                digistore_order_id,
                created_at
            FROM customer_freebies 
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
                }
                
                echo '<p><strong>Freebie ID: ' . $f['id'] . '</strong></p>';
                
                // Problem-Check
                $emptyFields = [];
                $filledFields = [];
                
                $importantFields = ['headline', 'bullet_points', 'mockup_image_url', 'background_color', 'unique_id'];
                
                foreach ($importantFields as $field) {
                    if (empty($f[$field])) {
                        $emptyFields[] = $field;
                    } else {
                        $filledFields[] = $field;
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
    
    // 4. Webhook Logs prüfen
    echo '<div class="section">';
    echo '<h2>4️⃣ Webhook Logs</h2>';
    $logFile = __DIR__ . '/webhook-logs.txt';
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $logLines = explode("\n", $logs);
        
        // Nur Logs mit diesem Käufer oder Freebie
        $relevantLogs = array_filter($logLines, function($line) use ($buyerEmail, $sourceFreebieId) {
            return stripos($line, $buyerEmail) !== false || 
                   stripos($line, (string)$sourceFreebieId) !== false ||
                   stripos($line, 'marketplace') !== false;
        });
        
        if (count($relevantLogs) > 0) {
            echo '<p class="success">✅ ' . count($relevantLogs) . ' relevante Log-Einträge gefunden</p>';
            echo '<div style="background: #000; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto;">';
            echo '<pre style="margin: 0; font-size: 12px;">';
            foreach ($relevantLogs as $line) {
                if (stripos($line, 'error') !== false) {
                    echo '<span class="error">' . safeHtml($line) . '</span>' . "\n";
                } elseif (stripos($line, 'warning') !== false) {
                    echo '<span class="warning">' . safeHtml($line) . '</span>' . "\n";
                } elseif (stripos($line, 'success') !== false) {
                    echo '<span class="success">' . safeHtml($line) . '</span>' . "\n";
                } else {
                    echo safeHtml($line) . "\n";
                }
            }
            echo '</pre>';
            echo '</div>';
        } else {
            echo '<p class="warning">⚠️ Keine relevanten Webhook-Logs gefunden für diesen Kauf</p>';
        }
    } else {
        echo '<p class="warning">⚠️ Keine Webhook-Logs gefunden</p>';
    }
    echo '</div>';
    
    // 5. Marketplace Status
    if ($buyerId) {
        echo '<div class="section">';
        echo '<h2>5️⃣ Marketplace-Status Check</h2>';
        
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
    
    // 6. Zusammenfassung & Lösungen
    echo '<div class="section">';
    echo '<h2>📊 Zusammenfassung & Lösungen</h2>';
    
    $issues = [];
    $hasEmptyFreebie = false;
    
    if (!$buyer) {
        $issues[] = '❌ Käufer-Account fehlt';
    }
    
    if ($buyerId && count($freebies) === 0) {
        $issues[] = '❌ Keine Freebies im Käufer-Account';
    }
    
    if ($buyerId && count($freebies) > 0) {
        foreach ($freebies as $f) {
            if ($f['copied_from_freebie_id'] == $sourceFreebieId) {
                if (empty($f['headline']) || empty($f['bullet_points'])) {
                    $hasEmptyFreebie = true;
                    $emptyFreebieId = $f['id'];
                }
            }
        }
    }
    
    if ($hasEmptyFreebie) {
        echo '<div style="background: rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444; margin: 20px 0;">';
        echo '<h3 class="error">🔴 HAUPTPROBLEM IDENTIFIZIERT</h3>';
        echo '<p>Das gekaufte Freebie wurde <strong>OHNE Inhalt</strong> kopiert!</p>';
        echo '<p><strong>Grund:</strong> Der alte Webhook hatte eine leere handleMarketplacePurchase() Funktion.</p>';
        
        echo '<h4>✅ LÖSUNG 1: Repariere diesen Kauf</h4>';
        echo '<p>Kopiere die Daten manuell vom Original:</p>';
        echo '<pre style="background: #000; padding: 10px; border-radius: 4px; overflow-x: auto;">';
        echo 'UPDATE customer_freebies 
SET 
    headline = (SELECT headline FROM customer_freebies WHERE id = ' . $sourceFreebieId . '),
    bullet_points = (SELECT bullet_points FROM customer_freebies WHERE id = ' . $sourceFreebieId . '),
    mockup_image_url = (SELECT mockup_image_url FROM customer_freebies WHERE id = ' . $sourceFreebieId . '),
    background_color = (SELECT background_color FROM customer_freebies WHERE id = ' . $sourceFreebieId . '),
    primary_color = (SELECT primary_color FROM customer_freebies WHERE id = ' . $sourceFreebieId . ')
WHERE id = ' . ($emptyFreebieId ?? 'XXX') . ' AND customer_id = ' . $buyerId . ';';
        echo '</pre>';
        
        echo '<h4>✅ LÖSUNG 2: Für zukünftige Käufe</h4>';
        echo '<p class="success">Der Webhook wurde bereits REPARIERT!</p>';
        echo '<p>Alle <strong>NEUEN</strong> Marktplatz-Käufe werden jetzt vollständig kopiert.</p>';
        echo '<p><strong>Webhook-URL:</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</p>';
        
        echo '</div>';
    } else if (count($issues) === 0 && $buyerId) {
        echo '<p class="success">✅ Keine kritischen Probleme gefunden!</p>';
        echo '<p>Der Kauf wurde erfolgreich verarbeitet und alle Daten wurden kopiert.</p>';
    }
    
    if (count($issues) > 0) {
        echo '<p class="error"><strong>Weitere Probleme:</strong></p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . $issue . '</li>';
        }
        echo '</ul>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h2 class="error">💥 Fehler</h2>';
    echo '<p class="error">' . safeHtml($e->getMessage()) . '</p>';
    echo '<pre>' . safeHtml($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>

<div class="section">
    <h2>🔧 Nächste Schritte</h2>
    <ol>
        <li>✅ Webhook-Handler wurde repariert (handleMarketplacePurchase vollständig implementiert)</li>
        <li>🔧 Für diesen Test-Kauf: Nutze das UPDATE-SQL oben um die Daten nachträglich zu kopieren</li>
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