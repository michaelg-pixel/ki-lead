<?php
/**
 * DEBUG: supertest@web.de - Nach v6.0 Dynamic Fix
 */

require_once '../config/database.php';

echo "<h1>🔍 DEBUG: supertest@web.de (v6.0 Test)</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    
    echo "=== 1. USER CHECK ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'supertest@web.de'");
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
                echo "  - ID: " . $f['id'] . " | " . $f['headline'] . "\n";
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
    
    echo "\n=== 4. WEBHOOK-LOG (NEUESTE EINTRÄGE FÜR supertest) ===\n";
    $logFile = __DIR__ . '/webhook.log';
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        
        // Suche nach supertest-spezifischen Logs
        if (strpos($content, 'supertest@web.de') !== false) {
            echo "✅ Webhook wurde für supertest@web.de aufgerufen!\n\n";
            
            // Zeige alle Zeilen die supertest enthalten + Kontext
            $lines = explode("\n", $content);
            $showNext = 0;
            
            foreach ($lines as $line) {
                if (strpos($line, 'supertest@web.de') !== false) {
                    $showNext = 20; // Zeige 20 Zeilen nach dem Fund
                }
                if ($showNext > 0) {
                    echo $line . "\n";
                    $showNext--;
                }
            }
        } else {
            echo "❌ KEIN Log-Eintrag für supertest@web.de gefunden!\n";
            echo "Das bedeutet: Webhook wurde NICHT aufgerufen!\n\n";
        }
        
        echo "\n=== KOMPLETTES LOG (LETZTE 150 ZEILEN) ===\n";
        $lines = explode("\n", $content);
        $lastLines = array_slice($lines, -150);
        echo implode("\n", $lastLines);
        
    } else {
        echo "❌ webhook.log existiert nicht!\n";
    }
    
    echo "\n\n=== 5. DATEI-CHECK: Welche Version ist aktiv? ===\n";
    $webhookFile = __DIR__ . '/digistore24-v5-fixed.php';
    if (file_exists($webhookFile)) {
        $firstLines = file($webhookFile, FILE_IGNORE_NEW_LINES);
        echo "✅ Datei existiert: digistore24-v5-fixed.php\n";
        echo "Erste 5 Zeilen:\n";
        for ($i = 0; $i < min(5, count($firstLines)); $i++) {
            echo $firstLines[$i] . "\n";
        }
        echo "\nLetzte Änderung: " . date('Y-m-d H:i:s', filemtime($webhookFile)) . "\n";
        
        // Prüfe ob v6.0 Code drin ist
        $content = file_get_contents($webhookFile);
        if (strpos($content, 'VERSION 6.0 DYNAMIC') !== false) {
            echo "✅ VERSION 6.0 DYNAMIC ist aktiv!\n";
        } elseif (strpos($content, 'VERSION 5') !== false) {
            echo "❌ Alte Version 5 noch aktiv - Deploy hat nicht funktioniert!\n";
        }
    } else {
        echo "❌ Webhook-Datei nicht gefunden!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";

echo "<hr><h2>📊 Diagnose:</h2>";
echo "<ul>";
echo "<li><strong>Wenn User NICHT existiert:</strong> Webhook wird nicht aufgerufen (IPN-Problem)</li>";
echo "<li><strong>Wenn User existiert + KEINE Freebies + KEIN Log:</strong> GitHub Deploy hat nicht funktioniert</li>";
echo "<li><strong>Wenn User existiert + Log vorhanden + Error:</strong> Script-Fehler (siehe Log)</li>";
echo "</ul>";
?>
