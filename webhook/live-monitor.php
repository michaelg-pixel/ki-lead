<?php
/**
 * Live-Diagnose: Zeigt die letzten Webhook-Aktivitäten in Echtzeit
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Webhook Live-Monitor</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#0f0f1e;color:#fff;font-size:14px;}
.box{background:#1a1a2e;padding:20px;margin:15px 0;border-radius:8px;border:2px solid #667eea;}
.success{border-color:#10b981;background:#10b98120;}
.error{border-color:#ef4444;background:#ef444420;}
.warning{border-color:#f59e0b;background:#f59e0b20;}
h1{color:#667eea;}
h3{color:#667eea;margin-top:0;}
pre{background:#000;padding:15px;border-radius:5px;overflow-x:auto;font-size:11px;max-height:600px;overflow-y:auto;line-height:1.4;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
th,td{padding:8px;text-align:left;border-bottom:1px solid #333;}
th{color:#667eea;}
.timestamp{color:#888;font-size:12px;}
</style></head><body>";

echo "<h1>🔴 Webhook Live-Monitor</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Letzte Webhook-Logs analysieren
    echo "<div class='box'>";
    echo "<h3>📝 Letzte Webhook-Aufrufe (letzte 100 Zeilen)</h3>";
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $lastLines = array_slice($lines, -100);
        
        // Suche nach test@web.de oder 613818
        $testLogs = array_filter($lastLines, function($line) {
            return stripos($line, 'test@web.de') !== false || 
                   stripos($line, '613818') !== false ||
                   stripos($line, 'marketplace') !== false;
        });
        
        if ($testLogs) {
            echo "<div class='success'>✅ Relevante Einträge gefunden: " . count($testLogs) . "</div>";
            echo "<pre>" . implode("\n", array_slice($testLogs, -20)) . "</pre>";
        } else {
            echo "<div class='error'>❌ KEINE Einträge für test@web.de oder Produkt 613818!</div>";
            echo "<p>Das bedeutet: Digistore24 hat den Webhook NICHT aufgerufen!</p>";
        }
        
        echo "<h4>Alle letzten Logs:</h4>";
        echo "<pre>" . implode("\n", $lastLines) . "</pre>";
    } else {
        echo "<div class='error'>❌ Webhook-Log-Datei nicht gefunden!</div>";
    }
    echo "</div>";
    
    // 2. Prüfe User test@web.de
    echo "<div class='box'>";
    echo "<h3>👤 User test@web.de Status</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['test@web.de']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='success'>✅ User existiert (ID: {$user['id']})</div>";
        echo "<table>";
        echo "<tr><th>Feld</th><th>Wert</th></tr>";
        foreach ($user as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        
        $userId = $user['id'];
        
        // 3. Alle Freebies für diesen User
        echo "<h4>📦 Freebies für diesen User:</h4>";
        
        $stmt = $pdo->prepare("
            SELECT id, headline, copied_from_freebie_id, created_at 
            FROM customer_freebies 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($freebies) {
            echo "<div class='success'>Gefunden: " . count($freebies) . " Freebies</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Headline</th><th>Kopiert von</th><th>Erstellt</th></tr>";
            foreach ($freebies as $f) {
                echo "<tr>";
                echo "<td>{$f['id']}</td>";
                echo "<td>{$f['headline']}</td>";
                echo "<td>" . ($f['copied_from_freebie_id'] ?? 'Eigenes') . "</td>";
                echo "<td>{$f['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Keine Freebies gefunden!</div>";
        }
        
    } else {
        echo "<div class='error'>❌ User existiert nicht!</div>";
    }
    echo "</div>";
    
    // 4. Prüfe Marktplatz-Freebie
    echo "<div class='box'>";
    echo "<h3>🏪 Marktplatz-Freebie (Produkt-ID: 613818)</h3>";
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE digistore_product_id = '613818' 
        AND marketplace_enabled = 1
    ");
    $stmt->execute();
    $marketplace = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($marketplace) {
        echo "<div class='success'>✅ Marktplatz-Freebie gefunden (ID: {$marketplace['id']})</div>";
        echo "<table>";
        echo "<tr><th>Feld</th><th>Wert</th></tr>";
        echo "<tr><td>ID</td><td>{$marketplace['id']}</td></tr>";
        echo "<tr><td>Headline</td><td>{$marketplace['headline']}</td></tr>";
        echo "<tr><td>Verkäufer ID</td><td>{$marketplace['customer_id']}</td></tr>";
        echo "<tr><td>Preis</td><td>{$marketplace['marketplace_price']} €</td></tr>";
        echo "<tr><td>Verkäufe</td><td>{$marketplace['marketplace_sales_count']}</td></tr>";
        echo "<tr><td>Produkt-ID</td><td>{$marketplace['digistore_product_id']}</td></tr>";
        echo "</table>";
        
        // Hat dieses Freebie einen Videokurs?
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
        $stmt->execute([$marketplace['id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            echo "<div class='success'>✅ Videokurs vorhanden (ID: {$course['id']})</div>";
            
            // Module zählen
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM freebie_course_modules WHERE course_id = ?");
            $stmt->execute([$course['id']]);
            $moduleCount = $stmt->fetchColumn();
            
            // Lektionen zählen
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM freebie_course_lessons 
                WHERE module_id IN (SELECT id FROM freebie_course_modules WHERE course_id = ?)
            ");
            $stmt->execute([$course['id']]);
            $lessonCount = $stmt->fetchColumn();
            
            echo "<p>Module: $moduleCount | Lektionen: $lessonCount</p>";
        } else {
            echo "<div class='error'>❌ Kein Videokurs für dieses Freebie!</div>";
        }
        
    } else {
        echo "<div class='error'>❌ Kein Marktplatz-Freebie mit Produkt-ID 613818 gefunden!</div>";
        echo "<p>Prüfe, ob die Produkt-ID im Freebie richtig eingetragen ist!</p>";
    }
    echo "</div>";
    
    // 5. Welche Webhook-Datei wird benutzt?
    echo "<div class='box'>";
    echo "<h3>⚙️ Webhook-Konfiguration</h3>";
    echo "<p><strong>Aktive Webhook-Datei:</strong> digistore24-v4.php (VERSION 4.8)</p>";
    echo "<p><strong>URL:</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</p>";
    
    // Datei-Datum prüfen
    $webhookFile = __DIR__ . '/digistore24-v4.php';
    if (file_exists($webhookFile)) {
        $modTime = filemtime($webhookFile);
        echo "<p><strong>Letzte Änderung:</strong> " . date('Y-m-d H:i:s', $modTime) . "</p>";
    }
    
    echo "<div class='warning'>";
    echo "<p>⚠️ <strong>WICHTIG:</strong> Diese URL muss in Digistore24 eingetragen sein!</p>";
    echo "<p>Stelle sicher, dass in den Produkt-Einstellungen bei IPN/Webhook diese URL eingetragen ist.</p>";
    echo "</div>";
    echo "</div>";
    
    // 6. Test-Button für manuellen Webhook-Call
    echo "<div class='box'>";
    echo "<h3>🧪 Manueller Test</h3>";
    echo "<p>Wenn Digistore24 den Webhook nicht aufruft, kannst du einen Test-Kauf manuell simulieren:</p>";
    echo "<form method='post' action='simulate-marketplace-purchase.php'>";
    echo "<input type='hidden' name='email' value='test@web.de'>";
    echo "<input type='hidden' name='product_id' value='613818'>";
    echo "<button type='submit' style='background:#667eea;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>✨ Kauf manuell simulieren</button>";
    echo "</form>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
