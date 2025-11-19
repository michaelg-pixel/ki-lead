<?php
/**
 * DEBUG SCRIPT - Check Webhook Purchase Status
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
        pre { background: #1e1e1e; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #569cd6; }
    </style>
</head>
<body>

<h1>🔍 Webhook Debug Report</h1>
<p>Email: <strong>12345t@abnehmen-fitness.com</strong></p>

<?php
try {
    $pdo = getDBConnection();
    $email = '12345t@abnehmen-fitness.com';
    
    echo '<div class="section">';
    echo '<h2>1️⃣ USER CHECK</h2>';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo '<p class="success">✅ USER GEFUNDEN!</p>';
        echo '<pre>';
        echo "ID:         " . $user['id'] . "\n";
        echo "Name:       " . ($user['name'] ?? 'N/A') . "\n";
        echo "Email:      " . $user['email'] . "\n";
        echo "Source:     " . ($user['source'] ?? 'N/A') . "\n";
        echo "Created:    " . ($user['created_at'] ?? 'N/A') . "\n";
        echo "RAW-Code:   " . ($user['raw_code'] ?? 'N/A') . "\n";
        echo "Order ID:   " . ($user['digistore_order_id'] ?? 'N/A') . "\n";
        echo '</pre>';
        
        $userId = $user['id'];
        
        echo '<h2>2️⃣ FREEBIE CHECK</h2>';
        
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll();
        
        if (empty($freebies)) {
            echo '<p class="error">❌ KEINE FREEBIES KOPIERT!</p>';
        } else {
            echo '<p class="success">✅ FREEBIES GEFUNDEN: ' . count($freebies) . '</p>';
            echo '<pre>';
            foreach ($freebies as $freebie) {
                echo "ID: " . $freebie['id'] . " | Headline: " . ($freebie['headline'] ?? 'N/A') . " | Type: " . ($freebie['freebie_type'] ?? 'N/A') . " | Copied From: " . ($freebie['copied_from_freebie_id'] ?? 'NULL') . "\n";
            }
            echo '</pre>';
        }
        
    } else {
        echo '<p class="error">❌ USER NICHT GEFUNDEN!</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>3️⃣ MARKTPLATZ-FREEBIE CHECK</h2>';
    
    $stmt = $pdo->prepare("SELECT id, customer_id, headline, digistore_product_id, marketplace_enabled, marketplace_sales_count FROM customer_freebies WHERE digistore_product_id = '613818' AND marketplace_enabled = 1");
    $stmt->execute();
    $marketplace = $stmt->fetch();
    
    if ($marketplace) {
        echo '<p class="success">✅ MARKTPLATZ-FREEBIE EXISTIERT</p>';
        echo '<pre>';
        echo "Freebie ID:       " . $marketplace['id'] . "\n";
        echo "Verkäufer ID:     " . $marketplace['customer_id'] . "\n";
        echo "Headline:         " . ($marketplace['headline'] ?? 'N/A') . "\n";
        echo "Product ID:       " . $marketplace['digistore_product_id'] . "\n";
        echo "Marketplace:      " . ($marketplace['marketplace_enabled'] ? 'Ja' : 'Nein') . "\n";
        echo "Sales Count:      " . ($marketplace['marketplace_sales_count'] ?? 0) . "\n";
        echo '</pre>';
    } else {
        echo '<p class="error">❌ MARKTPLATZ-FREEBIE NICHT GEFUNDEN (Product ID: 613818)</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>4️⃣ WEBHOOK LOGS (Last 100 Lines)</h2>';
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $lastLines = array_slice($lines, -100);
        
        echo '<pre style="max-height: 500px; overflow-y: auto;">';
        foreach ($lastLines as $line) {
            if (empty(trim($line))) continue;
            
            if (strpos($line, 'error') !== false || strpos($line, 'ERROR') !== false) {
                echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
            } elseif (strpos($line, 'success') !== false || strpos($line, 'SUCCESS') !== false) {
                echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
            } elseif (strpos($line, 'warning') !== false || strpos($line, 'WARNING') !== false) {
                echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
            } else {
                echo htmlspecialchars($line) . "\n";
            }
        }
        echo '</pre>';
    } else {
        echo '<p class="error">❌ Webhook-Log-Datei nicht gefunden!</p>';
        echo '<p>Pfad: ' . $logFile . '</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>5️⃣ WEBHOOK URL CHECK</h2>';
    echo '<p>Aktuelle Webhook-URLs:</p>';
    echo '<ul>';
    echo '<li><strong>v4 (NEU):</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</li>';
    echo '<li><strong>v3.4 (ALT):</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24.php</li>';
    echo '</ul>';
    echo '<p class="warning">⚠️ Welche URL ist bei Digistore24 eingetragen?</p>';
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">❌ FEHLER: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

</body>
</html>
