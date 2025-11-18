<?php
/**
 * Marktplatz-Kauf Diagnose-Tool
 * Prüft alle Aspekte eines Marktplatz-Kaufs
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

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
    </style>
</head>
<body>
    <h1>🔍 Marktplatz-Kauf Diagnose</h1>
    <p>Käufer: <strong><?php echo htmlspecialchars($buyerEmail); ?></strong></p>
    <p>Original-Freebie ID: <strong><?php echo htmlspecialchars($sourceFreebieId); ?></strong></p>

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
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value) . '</td></tr>';
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
                $class = $isPurchased ? 'success' : '';
                
                echo '<div style="margin: 20px 0; padding: 15px; background: rgba(0,0,0,0.3); border-left: 3px solid ' . 
                     ($isPurchased ? '#10b981' : '#667eea') . ';">';
                
                if ($isPurchased) {
                    echo '<p class="success"><strong>🎯 DAS IST DER GEKAUFTE!</strong></p>';
                }
                
                echo '<table>';
                echo '<tr><th>Feld</th><th>Wert</th><th>Status</th></tr>';
                
                foreach ($f as $key => $value) {
                    $status = '';
                    
                    if ($key === 'headline' && empty($value)) {
                        $status = '<span class="error">⚠️ LEER!</span>';
                    } elseif ($key === 'bullet_points' && empty($value)) {
                        $status = '<span class="error">⚠️ LEER!</span>';
                    } elseif ($key === 'mockup_image_url' && empty($value)) {
                        $status = '<span class="warning">⚠️ LEER</span>';
                    } elseif (!empty($value)) {
                        $status = '<span class="success">✓</span>';
                    }
                    
                    $displayValue = $value;
                    if (strlen($displayValue) > 100) {
                        $displayValue = substr($displayValue, 0, 100) . '...';
                    }
                    
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($key) . '</td>';
                    echo '<td>' . htmlspecialchars($displayValue) . '</td>';
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
        echo '<table>';
        echo '<tr><th>Feld</th><th>Wert</th><th>Länge</th></tr>';
        foreach ($original as $key => $value) {
            if (!in_array($key, ['email_api_key'])) {
                $displayValue = $value;
                if (strlen($displayValue) > 100) {
                    $displayValue = substr($displayValue, 0, 100) . '...';
                }
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($key) . '</td>';
                echo '<td>' . htmlspecialchars($displayValue) . '</td>';
                echo '<td>' . strlen($value) . ' chars</td>';
                echo '</tr>';
            }
        }
        echo '</table>';
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
        
        // Nur die letzten 50 Zeilen
        $recentLogs = array_slice($logLines, -50);
        
        echo '<div style="background: #000; padding: 15px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto;">';
        echo '<pre style="margin: 0; font-size: 12px;">';
        foreach ($recentLogs as $line) {
            if (stripos($line, 'error') !== false) {
                echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
            } elseif (stripos($line, 'warning') !== false) {
                echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
            } elseif (stripos($line, 'success') !== false) {
                echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo '</pre>';
        echo '</div>';
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
            SELECT id FROM customer_freebies 
            WHERE customer_id = ? 
            AND copied_from_freebie_id = ?
        ");
        $stmt->execute([$buyerId, $sourceFreebieId]);
        $isPurchased = ($stmt->fetch() !== false);
        
        if ($isPurchased) {
            echo '<p class="success">✅ System erkennt: Freebie wurde gekauft</p>';
            echo '<p class="success">✅ Button sollte anzeigen: "Bereits gekauft"</p>';
        } else {
            echo '<p class="error">❌ System erkennt NICHT, dass Freebie gekauft wurde!</p>';
            echo '<p class="error">❌ Button zeigt weiterhin "Jetzt kaufen"</p>';
        }
        echo '</div>';
    }
    
    // 6. Zusammenfassung
    echo '<div class="section">';
    echo '<h2>📊 Zusammenfassung</h2>';
    
    $issues = [];
    
    if (!$buyer) {
        $issues[] = '❌ Käufer-Account fehlt';
    }
    
    if ($buyerId && count($freebies) === 0) {
        $issues[] = '❌ Keine Freebies im Käufer-Account';
    }
    
    if ($buyerId && count($freebies) > 0) {
        foreach ($freebies as $f) {
            if ($f['copied_from_freebie_id'] == $sourceFreebieId) {
                if (empty($f['headline'])) {
                    $issues[] = '⚠️ Gekauftes Freebie hat keine Headline';
                }
                if (empty($f['bullet_points'])) {
                    $issues[] = '⚠️ Gekauftes Freebie hat keine Bullet Points';
                }
                if (empty($f['mockup_image_url'])) {
                    $issues[] = '⚠️ Gekauftes Freebie hat kein Mockup-Bild';
                }
            }
        }
    }
    
    if (count($issues) > 0) {
        echo '<p class="error"><strong>Gefundene Probleme:</strong></p>';
        echo '<ul>';
        foreach ($issues as $issue) {
            echo '<li>' . $issue . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="success">✅ Keine kritischen Probleme gefunden!</p>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h2 class="error">💥 Fehler</h2>';
    echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}
?>

<div class="section">
    <h2>🔧 Nächste Schritte</h2>
    <ol>
        <li>Webhook-Handler wurde aktualisiert mit vollständiger Marketplace-Logik</li>
        <li>Teste einen neuen Kauf über Digistore24</li>
        <li>Prüfe, ob ALLE Felder kopiert werden</li>
        <li>Prüfe, ob "bereits gekauft" Button erscheint</li>
    </ol>
    
    <p><strong>Webhook-URL:</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</p>
    <p><strong>Test mit anderem Käufer:</strong> <a href="?email=test@example.com&freebie_id=<?php echo $sourceFreebieId; ?>">Hier klicken</a></p>
</div>

</body>
</html>