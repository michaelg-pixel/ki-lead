<?php
/**
 * Webhook Test-Tool
 * Simuliert einen Digistore24 Webhook-Call zum Testen
 */

// Test-Produkt-ID aus URL holen
$testProductId = $_GET['product_id'] ?? '';

if (empty($testProductId)) {
    die('Fehler: Keine product_id angegeben. Nutze: ?product_id=DEINE_PRODUKT_ID');
}

// Simuliere Webhook-Daten
$testData = [
    'event' => 'payment.success',
    'order_id' => 'TEST_' . time(),
    'product_id' => $testProductId,
    'product_name' => 'Test Produkt',
    'buyer' => [
        'email' => 'test_' . time() . '@example.com',
        'first_name' => 'Test',
        'last_name' => 'User'
    ],
    'payment' => [
        'amount' => 99.00,
        'currency' => 'EUR'
    ]
];

// Sende Request an Webhook
$ch = curl_init('https://app.mehr-infos-jetzt.de/webhook/digistore24.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Ausgabe
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Webhook Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: bold;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .info-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #e5e7eb;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #374151;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
        }
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Webhook Test-Ergebnis</h1>
        <p>Test für Produkt-ID: <code><?php echo htmlspecialchars($testProductId); ?></code></p>
        
        <div class="status <?php echo $httpCode === 200 ? 'success' : 'error'; ?>">
            <?php if ($httpCode === 200): ?>
                ✅ Webhook erfolgreich ausgeführt! (HTTP <?php echo $httpCode; ?>)
            <?php else: ?>
                ❌ Webhook fehlgeschlagen! (HTTP <?php echo $httpCode; ?>)
            <?php endif; ?>
        </div>
        
        <div class="info-box">
            <h3>📤 Gesendete Test-Daten:</h3>
            <pre><?php echo json_encode($testData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
        </div>
        
        <div class="info-box">
            <h3>📥 Webhook-Antwort:</h3>
            <pre><?php echo htmlspecialchars($response); ?></pre>
        </div>
        
        <div class="info-box">
            <h3>📝 Prüfe folgendes:</h3>
            <ol>
                <li>Wurde ein Test-User in der Datenbank erstellt?</li>
                <li>Wurde das richtige Freebie-Limit gesetzt?</li>
                <li>Wurden die Empfehlungs-Slots korrekt zugewiesen?</li>
                <li>Schaue in <code>/webhook/webhook-logs.txt</code> für Details</li>
            </ol>
        </div>
        
        <a href="/admin/dashboard.php?page=digistore" class="btn">← Zurück zum Dashboard</a>
        <a href="/admin/dashboard.php?page=users" class="btn">Zu den Kunden →</a>
    </div>
</body>
</html>
<?php
