<?php
// 🔍 VOLLSTÄNDIGE WEBHOOK ANALYSE für testtest123@web.de
require_once 'config/database.php';

$test_email = 'testtest123@web.de';

echo "<h1>🔍 Webhook Analyse für: $test_email</h1>";
echo "<hr>";

// 1. User Check
echo "<h2>1️⃣ USER CHECK</h2>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ USER GEFUNDEN!<br>";
    echo "<pre>" . print_r($user, true) . "</pre>";
} else {
    echo "❌ USER NICHT GEFUNDEN!<br>";
}

echo "<hr>";

// 2. Freebies Check
echo "<h2>2️⃣ FREEBIES CHECK</h2>";
if ($user) {
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($freebies)) {
        echo "❌ KEINE FREEBIES GEFUNDEN!<br>";
    } else {
        echo "✅ FREEBIES GEFUNDEN: " . count($freebies) . "<br>";
        echo "<pre>" . print_r($freebies, true) . "</pre>";
    }
}

echo "<hr>";

// 3. Marketplace Freebies Check
echo "<h2>3️⃣ MARKTPLATZ-FREEBIES</h2>";
$stmt = $pdo->query("SELECT * FROM marketplace_freebies");
$mp_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mp_freebies)) {
    echo "❌ KEINE MARKTPLATZ-FREEBIES GEFUNDEN!<br>";
} else {
    echo "✅ MARKTPLATZ-FREEBIES GEFUNDEN: " . count($mp_freebies) . "<br>";
    foreach ($mp_freebies as $mf) {
        echo "<div style='border: 2px solid blue; margin: 10px; padding: 10px;'>";
        echo "<strong>ID:</strong> {$mf['id']}<br>";
        echo "<strong>Template ID:</strong> {$mf['template_id']}<br>";
        echo "<strong>Produkt-ID:</strong> {$mf['digistore_product_id']}<br>";
        echo "<strong>Preis:</strong> {$mf['price']}<br>";
        echo "</div>";
    }
}

echo "<hr>";

// 4. Webhook Logs
echo "<h2>4️⃣ WEBHOOK LOGS</h2>";
$log_file = 'webhooks/webhook.log';
if (file_exists($log_file)) {
    $logs = file($log_file);
    $relevant_logs = array_filter($logs, function($line) use ($test_email) {
        return stripos($line, $test_email) !== false;
    });
    
    if (empty($relevant_logs)) {
        echo "❌ KEINE LOGS MIT '$test_email' GEFUNDEN!<br>";
    } else {
        echo "✅ LOGS GEFUNDEN: " . count($relevant_logs) . " Zeilen<br><br>";
        echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto;'>";
        foreach ($relevant_logs as $log) {
            echo htmlspecialchars($log);
        }
        echo "</pre>";
    }
} else {
    echo "❌ WEBHOOK LOG NICHT GEFUNDEN: $log_file<br>";
}

echo "<hr>";

// 5. Raw Input aus Webhook Log extrahieren
echo "<h2>5️⃣ RAW INPUT ANALYSE</h2>";
if (!empty($relevant_logs)) {
    foreach ($relevant_logs as $log) {
        if (strpos($log, '"raw_input"') !== false) {
            echo "✅ RAW INPUT GEFUNDEN!<br><br>";
            
            // JSON extrahieren
            preg_match('/"raw_input":"([^"]+)"/', $log, $matches);
            if (isset($matches[1])) {
                $raw = urldecode($matches[1]);
                parse_str($raw, $params);
                
                echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 10px 0;'>";
                echo "<strong>📋 EMPFANGENE DATEN:</strong><br><br>";
                echo "<strong>Email:</strong> " . ($params['email'] ?? 'N/A') . "<br>";
                echo "<strong>Order ID:</strong> " . ($params['order_id'] ?? 'N/A') . "<br>";
                echo "<strong>Product ID:</strong> <span style='font-size: 20px; color: red; font-weight: bold;'>" . ($params['product_id'] ?? 'N/A') . "</span><br>";
                echo "<strong>Product Name:</strong> " . ($params['product_name'] ?? 'N/A') . "<br>";
                echo "</div>";
                
                // Vergleich mit Marktplatz
                $received_product_id = $params['product_id'] ?? null;
                echo "<br><h3>🔍 VERGLEICH MIT MARKTPLATZ:</h3>";
                
                $found_match = false;
                foreach ($mp_freebies as $mf) {
                    $match = ($mf['digistore_product_id'] == $received_product_id);
                    $color = $match ? 'green' : 'red';
                    $icon = $match ? '✅' : '❌';
                    
                    echo "<div style='border: 2px solid $color; margin: 10px; padding: 10px;'>";
                    echo "$icon <strong>Marktplatz-Freebie ID {$mf['id']}</strong><br>";
                    echo "Produkt-ID: {$mf['digistore_product_id']} ";
                    if ($match) {
                        echo "<span style='color: green; font-weight: bold;'>MATCH!</span>";
                        $found_match = true;
                    } else {
                        echo "<span style='color: red;'>NO MATCH</span>";
                    }
                    echo "</div>";
                }
                
                if (!$found_match) {
                    echo "<div style='background: #f8d7da; border: 2px solid #dc3545; padding: 15px; margin: 10px 0;'>";
                    echo "<strong>❌ PROBLEM GEFUNDEN!</strong><br><br>";
                    echo "Empfangene Produkt-ID: <strong>$received_product_id</strong><br>";
                    echo "Keine passende Marktplatz-Freebie gefunden!<br><br>";
                    echo "Das ist der Grund, warum das Freebie nicht kopiert wurde!";
                    echo "</div>";
                }
            }
        }
    }
}

echo "<hr>";

// 6. Webhook Code Check
echo "<h2>6️⃣ WEBHOOK CODE ANALYSE</h2>";
$webhook_file = 'webhooks/digistore-webhook.php';
if (file_exists($webhook_file)) {
    $code = file_get_contents($webhook_file);
    
    // Suche nach der Kopier-Logik
    if (strpos($code, 'marketplace_freebies') !== false) {
        echo "✅ Webhook enthält Marktplatz-Logik<br>";
    } else {
        echo "❌ Webhook enthält KEINE Marktplatz-Logik!<br>";
    }
    
    if (strpos($code, 'copyFreebie') !== false) {
        echo "✅ Webhook enthält copyFreebie-Funktion<br>";
    } else {
        echo "❌ Webhook enthält KEINE copyFreebie-Funktion!<br>";
    }
} else {
    echo "❌ WEBHOOK NICHT GEFUNDEN: $webhook_file<br>";
}
?>
