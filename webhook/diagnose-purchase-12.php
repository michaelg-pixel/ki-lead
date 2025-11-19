<?php
/**
 * Diagnose: Wurde das Freebie für 12@abnehmen-fitness.com korrekt angelegt?
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();

echo "<h1>🔍 Marktplatz-Kauf Diagnose für 12@abnehmen-fitness.com</h1>";
echo "<style>
body { font-family: monospace; background: #1a1a2e; color: #eee; padding: 20px; }
h1, h2, h3 { color: #00ff88; }
pre { background: #0f0f1e; padding: 15px; border-radius: 8px; overflow-x: auto; }
.success { color: #00ff88; }
.error { color: #ff4444; }
.warning { color: #ffaa00; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #444; padding: 8px; text-align: left; }
th { background: #2a2a4e; }
</style>";

// 1. User prüfen
echo "<h2>1️⃣ User 12@abnehmen-fitness.com</h2>";

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = '12@abnehmen-fitness.com'");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "<pre class='success'>✅ User gefunden - ID: " . $user['id'] . "</pre>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    foreach ($user as $key => $value) {
        echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
    }
    echo "</table>";
    
    $userId = $user['id'];
} else {
    echo "<pre class='error'>❌ User NICHT gefunden! Webhook hat keinen User angelegt!</pre>";
    $userId = null;
}

// 2. Freebies des Users prüfen
if ($userId) {
    echo "<h2>2️⃣ Freebies von User ID $userId</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($freebies)) {
        echo "<pre class='success'>✅ Freebies gefunden: " . count($freebies) . "</pre>";
        
        foreach ($freebies as $freebie) {
            $isMarketplace = !empty($freebie['copied_from_freebie_id']);
            $bgColor = $isMarketplace ? " style='background:#2a4a2a;'" : "";
            
            echo "<h3$bgColor>Freebie ID: " . $freebie['id'] . ($isMarketplace ? " 🛒 MARKTPLATZ" : "") . "</h3>";
            echo "<table>";
            echo "<tr><th>Feld</th><th>Wert</th></tr>";
            foreach ($freebie as $key => $value) {
                $highlight = (in_array($key, ['copied_from_freebie_id', 'original_creator_id', 'marketplace_enabled'])) ? " style='background:#3a3a5a;'" : "";
                echo "<tr$highlight><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<pre class='error'>❌ KEINE Freebies gefunden! Webhook hat kein Freebie kopiert!</pre>";
    }
}

// 3. Kurse des Users prüfen
if ($userId) {
    echo "<h2>3️⃣ Kurse von User ID $userId</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE customer_id = ?");
    $stmt->execute([$userId]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($courses)) {
        echo "<pre class='success'>✅ Kurse gefunden: " . count($courses) . "</pre>";
        
        foreach ($courses as $course) {
            echo "<h3>Kurs ID: " . $course['id'] . "</h3>";
            echo "<table>";
            echo "<tr><th>Feld</th><th>Wert</th></tr>";
            foreach ($course as $key => $value) {
                echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
            }
            echo "</table>";
            
            // Module und Lektionen zählen
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
            $stmt->execute([$course['id']]);
            $moduleCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id = ?");
            $stmt->execute([$course['id']]);
            $lessonCount = $stmt->fetchColumn();
            
            echo "<pre class='success'>";
            echo "📦 Module: $moduleCount\n";
            echo "📚 Lektionen: $lessonCount";
            echo "</pre>";
        }
    } else {
        echo "<pre class='error'>❌ KEINE Kurse gefunden! Webhook hat keinen Kurs kopiert!</pre>";
    }
}

// 4. Webhook-Logs prüfen (letzte Einträge)
echo "<h2>4️⃣ Letzte Webhook-Logs (für 12@abnehmen-fitness.com)</h2>";

$logFile = __DIR__ . '/webhook-logs.txt';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    
    // Nach der E-Mail filtern
    $relevantLines = [];
    foreach ($lines as $line) {
        if (stripos($line, '12@abnehmen-fitness.com') !== false || 
            stripos($line, '12%40abnehmen-fitness.com') !== false) {
            $relevantLines[] = $line;
        }
    }
    
    if (!empty($relevantLines)) {
        echo "<pre class='success'>✅ Logs gefunden (" . count($relevantLines) . " Zeilen)</pre>";
        echo "<pre style='max-height: 500px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $relevantLines));
        echo "</pre>";
    } else {
        echo "<pre class='warning'>⚠️ Keine Logs für diese E-Mail gefunden</pre>";
        
        // Letzte 30 Zeilen anzeigen
        echo "<h3>Letzte 30 Zeilen aller Logs:</h3>";
        $last30 = array_slice($lines, -30);
        echo "<pre style='max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $last30));
        echo "</pre>";
    }
} else {
    echo "<pre class='error'>❌ Webhook-Logs nicht gefunden!</pre>";
}

// 5. Original Marktplatz-Freebie prüfen
echo "<h2>5️⃣ Original Marktplatz-Freebie (ID 7)</h2>";

$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 7");
$stmt->execute();
$originalFreebie = $stmt->fetch(PDO::FETCH_ASSOC);

if ($originalFreebie) {
    echo "<pre class='success'>✅ Original-Freebie gefunden</pre>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $originalFreebie['id'] . "</li>";
    echo "<li><strong>Headline:</strong> " . htmlspecialchars($originalFreebie['headline']) . "</li>";
    echo "<li><strong>digistore_product_id:</strong> " . ($originalFreebie['digistore_product_id'] ?? 'NULL') . "</li>";
    echo "<li><strong>marketplace_enabled:</strong> " . ($originalFreebie['marketplace_enabled'] ?? 'NULL') . "</li>";
    echo "<li><strong>customer_id (Owner):</strong> " . $originalFreebie['customer_id'] . "</li>";
    echo "</ul>";
    
    // Owner E-Mail holen
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$originalFreebie['customer_id']]);
    $ownerEmail = $stmt->fetchColumn();
    echo "<p><strong>Owner E-Mail:</strong> " . htmlspecialchars($ownerEmail) . "</p>";
    
} else {
    echo "<pre class='error'>❌ Original-Freebie nicht gefunden!</pre>";
}

echo "<hr>";
echo "<h2>🎯 Zusammenfassung</h2>";
echo "<ol>";
echo "<li>Hat Webhook einen User angelegt?</li>";
echo "<li>Hat Webhook ein Freebie kopiert?</li>";
echo "<li>Hat Webhook einen Kurs kopiert?</li>";
echo "<li>Was steht in den Webhook-Logs?</li>";
echo "</ol>";

echo "<h3>💡 Mögliche Probleme:</h3>";
echo "<ul>";
echo "<li>❌ Webhook wurde nicht ausgelöst (Digistore24 → Webhook URL)</li>";
echo "<li>❌ Webhook hat einen Fehler geworfen (siehe Logs)</li>";
echo "<li>❌ Product ID 613818 ist nicht richtig konfiguriert</li>";
echo "<li>❌ User zeigt falsches Dashboard an (alte vs. neue Version)</li>";
echo "</ul>";
?>