<?php
/**
 * DEBUG SCRIPT - Check Webhook Purchase Status
 * Speziell für: final-test@web.de
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Debug - Purchase Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .section { margin: 20px 0; padding: 15px; background: #2d2d30; border-radius: 5px; }
        pre { background: #1e1e1e; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
        h2 { color: #569cd6; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; border-bottom: 1px solid #3e3e42; }
        td:first-child { color: #9cdcfe; width: 200px; }
    </style>
</head>
<body>

<h1>🔍 Webhook Debug Report</h1>
<p>Email: <strong>final-test@web.de</strong></p>

<?php
try {
    $pdo = getDBConnection();
    $email = 'final-test@web.de';
    
    echo '<div class="section">';
    echo '<h2>1️⃣ USER CHECK</h2>';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo '<p class="success">✅ USER GEFUNDEN!</p>';
        echo '<table>';
        echo '<tr><td>ID</td><td>' . $user['id'] . '</td></tr>';
        echo '<tr><td>Name</td><td>' . ($user['name'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Email</td><td>' . $user['email'] . '</td></tr>';
        echo '<tr><td>Source</td><td>' . ($user['source'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Created</td><td>' . ($user['created_at'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>RAW-Code</td><td>' . ($user['raw_code'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Order ID</td><td>' . ($user['digistore_order_id'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Product ID</td><td>' . ($user['digistore_product_id'] ?? 'N/A') . '</td></tr>';
        echo '</table>';
        
        $userId = $user['id'];
        
        echo '</div><div class="section">';
        echo '<h2>2️⃣ FREEBIE CHECK</h2>';
        
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll();
        
        if (empty($freebies)) {
            echo '<p class="error">❌ KEINE FREEBIES KOPIERT!</p>';
            echo '<p class="warning">Das Freebie wurde NICHT automatisch kopiert!</p>';
        } else {
            echo '<p class="success">✅ FREEBIES GEFUNDEN: ' . count($freebies) . '</p>';
            echo '<table>';
            echo '<tr><td><strong>ID</strong></td><td><strong>Headline</strong></td><td><strong>Kopiert von</strong></td><td><strong>Erstellt</strong></td></tr>';
            foreach ($freebies as $freebie) {
                echo '<tr>';
                echo '<td>' . $freebie['id'] . '</td>';
                echo '<td>' . ($freebie['headline'] ?? 'N/A') . '</td>';
                echo '<td>' . ($freebie['copied_from_freebie_id'] ?? 'Eigenes') . '</td>';
                echo '<td>' . ($freebie['created_at'] ?? 'N/A') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
    } else {
        echo '<p class="error">❌ USER NICHT GEFUNDEN!</p>';
        echo '<p class="warning">Der User wurde NICHT angelegt! Der Webhook wurde wahrscheinlich nicht getriggert.</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>3️⃣ MARKTPLATZ-FREEBIE CHECK</h2>';
    
    $stmt = $pdo->prepare("SELECT id, customer_id, headline, digistore_product_id, marketplace_enabled, marketplace_sales_count FROM customer_freebies WHERE digistore_product_id = '613818' AND marketplace_enabled = 1");
    $stmt->execute();
    $marketplace = $stmt->fetch();
    
    if ($marketplace) {
        echo '<p class="success">✅ MARKTPLATZ-FREEBIE EXISTIERT</p>';
        echo '<table>';
        echo '<tr><td>Freebie ID</td><td>' . $marketplace['id'] . '</td></tr>';
        echo '<tr><td>Verkäufer ID</td><td>' . $marketplace['customer_id'] . '</td></tr>';
        echo '<tr><td>Headline</td><td>' . ($marketplace['headline'] ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Product ID</td><td>' . $marketplace['digistore_product_id'] . '</td></tr>';
        echo '<tr><td>Sales Count</td><td>' . ($marketplace['marketplace_sales_count'] ?? 0) . '</td></tr>';
        echo '</table>';
    } else {
        echo '<p class="error">❌ MARKTPLATZ-FREEBIE NICHT GEFUNDEN</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>4️⃣ WEBHOOK LOGS (Letzte 50 Zeilen mit "final-test")</h2>';
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        
        // Nur Zeilen mit final-test anzeigen
        $relevantLines = [];
        foreach ($lines as $line) {
            if (stripos($line, 'final-test') !== false) {
                $relevantLines[] = $line;
            }
        }
        
        if (empty($relevantLines)) {
            echo '<p class="error">❌ KEINE LOGS MIT "final-test" GEFUNDEN!</p>';
            echo '<p class="warning">Der Webhook wurde wahrscheinlich NICHT für diesen Kauf getriggert!</p>';
            
            // Zeige die letzten 30 Zeilen generell
            echo '<h3>Letzte 30 Webhook-Einträge (allgemein):</h3>';
            $lastLines = array_slice($lines, -30);
            echo '<pre>';
            foreach ($lastLines as $line) {
                if (empty(trim($line))) continue;
                
                if (strpos($line, 'error') !== false || strpos($line, 'ERROR') !== false) {
                    echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
                } elseif (strpos($line, 'success') !== false) {
                    echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
                } elseif (strpos($line, 'marketplace') !== false) {
                    echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre>';
            
        } else {
            echo '<p class="success">✅ LOGS MIT "final-test" GEFUNDEN: ' . count($relevantLines) . ' Zeilen</p>';
            echo '<pre>';
            foreach ($relevantLines as $line) {
                if (strpos($line, 'error') !== false) {
                    echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
                } elseif (strpos($line, 'success') !== false) {
                    echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre>';
        }
    } else {
        echo '<p class="error">❌ Webhook-Log-Datei nicht gefunden!</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>5️⃣ DIAGNOSE</h2>';
    
    if (!isset($user)) {
        echo '<p class="error">🚨 <strong>HAUPTPROBLEM:</strong> Der User wurde NICHT angelegt!</p>';
        echo '<p>Das bedeutet: <strong>Digistore24 hat den Webhook NICHT aufgerufen!</strong></p>';
        echo '<ul>';
        echo '<li>✅ Ist die Webhook-URL korrekt eingetragen? <code>https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</code></li>';
        echo '<li>✅ Ist der Webhook für Produkt 613818 aktiviert?</li>';
        echo '<li>✅ War es ein echter Kauf oder nur ein Test?</li>';
        echo '</ul>';
    } elseif (isset($user) && empty($freebies)) {
        echo '<p class="warning">⚠️ <strong>TEILPROBLEM:</strong> User wurde angelegt, aber Freebie nicht kopiert!</p>';
        echo '<p>Das bedeutet: Der Webhook wurde aufgerufen, aber die Kopier-Logik hat nicht funktioniert.</p>';
        echo '<p>Prüfe die Webhook-Logs oben für Fehlermeldungen!</p>';
    } else {
        echo '<p class="success">✅ <strong>ALLES FUNKTIONIERT!</strong> User und Freebie wurden korrekt angelegt.</p>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

</body>
</html>
