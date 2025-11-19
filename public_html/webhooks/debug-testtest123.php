<?php
// 🔍 VOLLSTÄNDIGE WEBHOOK ANALYSE für testtest123@web.de
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
if (!file_exists($config_path)) {
    die("❌ Config nicht gefunden: $config_path");
}

require_once $config_path;

$test_email = 'testtest123@web.de';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Webhook Debug</title></head><body>";
echo "<h1>🔍 Webhook Analyse für: $test_email</h1>";
echo "<hr>";

// 1. User Check
echo "<h2>1️⃣ USER CHECK</h2>";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$test_email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✅ USER GEFUNDEN!<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
    foreach ($user as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
    }
    echo "</table>";
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
        echo "<strong>Produkt-ID:</strong> <span style='font-size: 18px; color: red; font-weight: bold;'>{$mf['digistore_product_id']}</span><br>";
        echo "<strong>Preis:</strong> {$mf['price']}<br>";
        echo "</div>";
    }
}

echo "<hr>";

// 4. Webhook Logs
echo "<h2>4️⃣ WEBHOOK LOGS</h2>";
$log_file = __DIR__ . '/webhook.log';
if (file_exists($log_file)) {
    $logs = file($log_file);
    $relevant_logs = array_filter($logs, function($line) use ($test_email) {
        return stripos($line, $test_email) !== false;
    });
    
    if (empty($relevant_logs)) {
        echo "❌ KEINE LOGS MIT '$test_email' GEFUNDEN!<br>";
        echo "Log-Datei existiert, aber keine passenden Einträge.<br>";
    } else {
        echo "✅ LOGS GEFUNDEN: " . count($relevant_logs) . " Zeilen<br><br>";
        
        // Raw Input extrahieren und analysieren
        foreach ($relevant_logs as $log) {
            if (strpos($log, '"raw_input"') !== false) {
                echo "<h3>📋 RAW INPUT GEFUNDEN!</h3>";
                
                // JSON extrahieren
                $json_start = strpos($log, '{');
                if ($json_start !== false) {
                    $json_str = substr($log, $json_start);
                    $data = json_decode($json_str, true);
                    
                    if ($data && isset($data['raw_input'])) {
                        $raw = $data['raw_input'];
                        parse_str($raw, $params);
                        
                        echo "<div style='background: #fff3cd; border: 2px solid #ffc107; padding: 15px; margin: 10px 0;'>";
                        echo "<strong>EMPFANGENE DATEN:</strong><br><br>";
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
                            
                            echo "<div style='border: 3px solid $color; margin: 10px; padding: 10px;'>";
                            echo "$icon <strong>Marktplatz-Freebie ID {$mf['id']}</strong><br>";
                            echo "Produkt-ID: <strong>{$mf['digistore_product_id']}</strong> ";
                            if ($match) {
                                echo "<span style='color: green; font-size: 18px; font-weight: bold;'>✅ MATCH!</span>";
                                $found_match = true;
                            } else {
                                echo "<span style='color: red; font-size: 18px;'>❌ NO MATCH (Empfangen: $received_product_id)</span>";
                            }
                            echo "</div>";
                        }
                        
                        if (!$found_match) {
                            echo "<div style='background: #f8d7da; border: 3px solid #dc3545; padding: 20px; margin: 20px 0;'>";
                            echo "<h2>❌ PROBLEM GEFUNDEN!</h2>";
                            echo "Empfangene Produkt-ID: <strong style='font-size: 20px;'>$received_product_id</strong><br><br>";
                            echo "Keine passende Marktplatz-Freebie gefunden!<br><br>";
                            echo "<strong style='font-size: 18px;'>Das ist der Grund, warum das Freebie nicht kopiert wurde!</strong>";
                            echo "</div>";
                        } else {
                            echo "<div style='background: #d4edda; border: 3px solid #28a745; padding: 20px; margin: 20px 0;'>";
                            echo "<h2>✅ PRODUKT-ID MATCHED!</h2>";
                            echo "Das Freebie SOLLTE kopiert worden sein.<br>";
                            echo "Wenn nicht, liegt das Problem woanders (z.B. in der Kopier-Logik)!";
                            echo "</div>";
                        }
                    }
                }
            }
        }
        
        echo "<br><h3>📄 KOMPLETTE LOG-EINTRÄGE:</h3>";
        echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto; font-size: 12px;'>";
        foreach ($relevant_logs as $log) {
            echo htmlspecialchars($log);
        }
        echo "</pre>";
    }
} else {
    echo "❌ WEBHOOK LOG NICHT GEFUNDEN: $log_file<br>";
}

echo "<hr>";

// 5. Diagnose-Zusammenfassung
echo "<h2>5️⃣ DIAGNOSE-ZUSAMMENFASSUNG</h2>";
echo "<div style='background: #e7f3ff; border: 2px solid #0066cc; padding: 15px;'>";

if (!$user) {
    echo "🔴 <strong>HAUPTPROBLEM:</strong> User wurde nicht angelegt!<br>";
} elseif (empty($freebies)) {
    echo "🟡 <strong>TEILPROBLEM:</strong> User existiert, aber Freebie wurde nicht kopiert!<br>";
    echo "Mögliche Ursachen:<br>";
    echo "1. Produkt-ID Mismatch (siehe Vergleich oben)<br>";
    echo "2. Fehler in der Kopier-Logik<br>";
    echo "3. Fehlende Berechtigungen<br>";
} else {
    echo "🟢 <strong>ALLES OK:</strong> User und Freebies existieren!<br>";
}

echo "</div>";

echo "</body></html>";
?>
