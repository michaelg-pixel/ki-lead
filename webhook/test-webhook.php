<?php
/**
 * Webhook-Test Tool
 * Simuliert einen Digistore24 Webhook-Call
 */

header('Content-Type: text/html; charset=utf-8');

$testUrl = 'https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php';

// Test-Daten für einen Marktplatz-Kauf
$testData = [
    'event' => 'payment.success',
    'order_id' => 'TEST-' . time(),
    'product_id' => '613818', // Die Produkt-ID vom Kinderbuch-Freebie
    'product_name' => 'Test Marketplace Produkt',
    'buyer' => [
        'email' => '12@abnehmen-fitness.com',
        'first_name' => 'Micha',
        'last_name' => 'Test2'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Test</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        pre {
            background: #000;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <h1>🧪 Webhook Test Tool</h1>

    <div class="section">
        <h2>1️⃣ Test-Daten</h2>
        <p>Diese Daten werden an den Webhook gesendet:</p>
        <pre><?php echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
    </div>

<?php
if (isset($_POST['run_test'])) {
    echo '<div class="section">';
    echo '<h2>2️⃣ Test läuft...</h2>';
    
    try {
        // Webhook aufrufen
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: DigiStore24-Webhook-Test'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Für Test
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo '<p class="error">❌ CURL Fehler: ' . htmlspecialchars($error) . '</p>';
        } else {
            echo '<p class="success">✅ Webhook aufgerufen!</p>';
            echo '<p><strong>HTTP Status:</strong> ' . $httpCode . '</p>';
            
            echo '<h3>Webhook Response:</h3>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
            
            if ($httpCode == 200) {
                echo '<p class="success">✅ Webhook hat erfolgreich geantwortet!</p>';
                
                // Prüfe ob Freebie erstellt wurde
                require_once __DIR__ . '/../config/database.php';
                $pdo = getDBConnection();
                
                $stmt = $pdo->prepare("
                    SELECT id, headline, copied_from_freebie_id, created_at 
                    FROM customer_freebies 
                    WHERE customer_id = 17 
                    AND copied_from_freebie_id = 7
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute();
                $newFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($newFreebie) {
                    echo '<div style="background: rgba(16, 185, 129, 0.2); padding: 15px; border-radius: 8px; margin: 20px 0;">';
                    echo '<h3 class="success">🎉 ERFOLG!</h3>';
                    echo '<p>Ein neues Freebie wurde erstellt:</p>';
                    echo '<ul>';
                    echo '<li><strong>ID:</strong> ' . $newFreebie['id'] . '</li>';
                    echo '<li><strong>Headline:</strong> ' . htmlspecialchars($newFreebie['headline']) . '</li>';
                    echo '<li><strong>Kopiert von:</strong> Freebie ' . $newFreebie['copied_from_freebie_id'] . '</li>';
                    echo '<li><strong>Erstellt:</strong> ' . $newFreebie['created_at'] . '</li>';
                    echo '</ul>';
                    echo '<p><a href="/public/login.php" class="btn">🎯 Zum Dashboard</a></p>';
                    echo '</div>';
                } else {
                    echo '<p class="error">⚠️ Webhook hat geantwortet, aber KEIN Freebie wurde erstellt!</p>';
                    echo '<p>Prüfe die Webhook-Logs für Details.</p>';
                }
            } else {
                echo '<p class="error">❌ Webhook hat mit Fehler geantwortet (HTTP ' . $httpCode . ')</p>';
            }
        }
        
        // Webhook-Logs anzeigen
        echo '<h3>Webhook-Logs (letzte 50 Zeilen):</h3>';
        $logFile = __DIR__ . '/webhook-logs.txt';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $logLines = explode("\n", $logs);
            $recentLogs = array_slice($logLines, -50);
            
            echo '<pre style="max-height: 400px; overflow-y: auto;">';
            foreach ($recentLogs as $line) {
                if (stripos($line, 'error') !== false) {
                    echo '<span style="color: #ef4444;">' . htmlspecialchars($line) . '</span>' . "\n";
                } elseif (stripos($line, 'success') !== false) {
                    echo '<span style="color: #10b981;">' . htmlspecialchars($line) . '</span>' . "\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '</div>';
}
?>

    <div class="section">
        <h2>🚀 Test starten</h2>
        <p>Dieser Test simuliert einen echten Digistore24-Webhook-Call.</p>
        <p><strong>Was passiert:</strong></p>
        <ol>
            <li>Sendet Test-Daten an den Webhook</li>
            <li>Webhook sollte User finden/erstellen</li>
            <li>Webhook sollte Freebie ID 7 zum User ID 17 kopieren</li>
            <li>Du solltest das Freebie dann in deinem Account sehen</li>
        </ol>
        
        <form method="POST">
            <button type="submit" name="run_test" class="btn">🧪 TEST STARTEN</button>
        </form>
        
        <p style="font-size: 14px; color: #9ca3af; margin-top: 20px;">
            💡 Tipp: Wenn dieser Test funktioniert, bedeutet es, dass der Webhook grundsätzlich funktioniert. 
            Dann liegt das Problem bei der Digistore24-Konfiguration.
        </p>
    </div>

    <div class="section">
        <h2>📋 Checkliste</h2>
        <p>Nach dem Test prüfe:</p>
        <ol>
            <li>Hat der Webhook mit HTTP 200 geantwortet?</li>
            <li>Wurde ein Freebie erstellt?</li>
            <li>Gibt es Fehler in den Webhook-Logs?</li>
            <li>Siehst du das Freebie in deinem Dashboard?</li>
        </ol>
        
        <p><strong>Andere Tools:</strong></p>
        <ul>
            <li><a href="check-purchase-location.php" style="color: #667eea;">Kauf-Übersicht</a></li>
            <li><a href="check-marketplace-product-ids.php" style="color: #667eea;">Produkt-IDs Check</a></li>
            <li><a href="diagnose-marketplace-purchase-v2.php?email=12@abnehmen-fitness.com&freebie_id=7" style="color: #667eea;">Diagnose-Tool</a></li>
        </ul>
    </div>

</body>
</html>