<?php
/**
 * DEBUG: ultimatetest@web.de - Nach v6.1 FINAL Fix
 */

require_once '../config/database.php';

echo "<h1>🔍 DEBUG: ultimatetest@web.de (v6.1 FINAL Test)</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    
    echo "=== 1. USER CHECK ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'ultimatetest@web.de'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User existiert!\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Erstellt: " . $user['created_at'] . "\n\n";
        
        $userId = $user['id'];
        
        echo "=== 2. FREEBIES CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($freebies) > 0) {
            echo "✅ Freebies gefunden: " . count($freebies) . "\n";
            foreach ($freebies as $f) {
                echo "  - ID: " . $f['id'] . " | " . $f['headline'] . " | Typ: " . $f['freebie_type'] . "\n";
            }
        } else {
            echo "❌ Keine Freebies!\n";
        }
        
        echo "\n=== 3. VIDEOKURSE CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo count($courses) > 0 ? "✅ Videokurse: " . count($courses) : "❌ Keine Videokurse";
        echo "\n";
        
    } else {
        echo "❌ User existiert NICHT!\n";
    }
    
    echo "\n=== 4. WEBHOOK VERSION CHECK ===\n";
    $webhookFile = __DIR__ . '/digistore24-v5-fixed.php';
    if (file_exists($webhookFile)) {
        $content = file_get_contents($webhookFile);
        if (strpos($content, 'VERSION 6.1 FINAL') !== false) {
            echo "✅ VERSION 6.1 FINAL ist aktiv!\n";
        } elseif (strpos($content, 'VERSION 6.0 DYNAMIC') !== false) {
            echo "⚠️ VERSION 6.0 noch aktiv (nicht v6.1)\n";
        } elseif (strpos($content, 'VERSION 5') !== false) {
            echo "❌ Alte VERSION 5 noch aktiv!\n";
        } else {
            echo "⚠️ Version unbekannt\n";
        }
        echo "Letzte Änderung: " . date('Y-m-d H:i:s', filemtime($webhookFile)) . "\n";
        
        // Prüfe ob getEnumValues Funktion existiert (neu in v6.1)
        if (strpos($content, 'function getEnumValues') !== false) {
            echo "✅ getEnumValues() Funktion existiert (v6.1 Feature)\n";
        } else {
            echo "❌ getEnumValues() fehlt - alte Version!\n";
        }
    }
    
    echo "\n=== 5. WEBHOOK LOG (SUCHE NACH ultimatetest) ===\n";
    $logFile = __DIR__ . '/webhook.log';
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        
        if (strpos($content, 'ultimatetest@web.de') !== false) {
            echo "✅ Webhook wurde für ultimatetest@web.de aufgerufen!\n\n";
            
            // Extrahiere ultimatetest-spezifische Logs
            $lines = explode("\n", $content);
            $relevantLines = [];
            $capturing = false;
            $captureCount = 0;
            
            foreach ($lines as $line) {
                if (strpos($line, 'ultimatetest@web.de') !== false) {
                    $capturing = true;
                    $captureCount = 30; // Zeige 30 Zeilen nach dem Fund
                }
                if ($capturing) {
                    $relevantLines[] = $line;
                    $captureCount--;
                    if ($captureCount <= 0) {
                        $capturing = false;
                    }
                }
            }
            
            echo "=== ULTIMATETEST LOGS ===\n";
            echo implode("\n", $relevantLines);
            
        } else {
            echo "❌ KEIN Log-Eintrag für ultimatetest@web.de!\n";
            echo "Webhook wurde NIE aufgerufen oder Log wurde gelöscht!\n";
        }
        
    } else {
        echo "❌ webhook.log existiert nicht!\n";
    }
    
    echo "\n\n=== 6. GITHUB ACTIONS CHECK ===\n";
    echo "GitHub Repository: michaelg-pixel/ki-lead\n";
    echo "Letzter Commit sollte sein: 'Webhook v6.1 FINAL: Korrekter ENUM-Wert'\n";
    echo "Prüfe ob GitHub Actions erfolgreich war!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";

echo "<hr><h2>📊 Mögliche Probleme:</h2>";
echo "<ul>";
echo "<li><strong>GitHub Deploy nicht durchgelaufen:</strong> v6.1 noch nicht auf Server</li>";
echo "<li><strong>Webhook-URL falsch:</strong> Digistore24 ruft falsche Datei auf</li>";
echo "<li><strong>Neuer Fehler in v6.1:</strong> Script hat Bug (siehe Logs)</li>";
echo "<li><strong>Cache-Problem:</strong> Alter Code wird noch ausgeführt</li>";
echo "</ul>";

echo "<h2>🔧 Nächste Schritte:</h2>";
echo "<ol>";
echo "<li>Prüfe ob v6.1 wirklich aktiv ist</li>";
echo "<li>Wenn nein: Warte noch 1-2 Minuten auf Deploy</li>";
echo "<li>Wenn ja aber Fehler: Logs analysieren</li>";
echo "<li>Wenn keine Logs: IPN-URL bei Digistore24 prüfen</li>";
echo "</ol>";
?>
