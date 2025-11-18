<?php
/**
 * Diagnose-Tool: Prüft warum der Videokurs nicht kopiert wird
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();

echo "<h1>🔍 Videokurs-Kopier Diagnose</h1>";
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

// 1. Original Freebie prüfen (ID 7 - das Marktplatz-Freebie)
echo "<h2>1️⃣ Original Freebie (ID 7) prüfen</h2>";

$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 7");
$stmt->execute();
$originalFreebie = $stmt->fetch(PDO::FETCH_ASSOC);

if ($originalFreebie) {
    echo "<pre class='success'>✅ Freebie ID 7 gefunden</pre>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    foreach ($originalFreebie as $key => $value) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
    }
    echo "</table>";
    
    // Wichtige Felder hervorheben
    echo "<h3>📊 Wichtige Felder:</h3>";
    echo "<ul>";
    echo "<li><strong>customer_id:</strong> " . ($originalFreebie['customer_id'] ?? 'NULL') . "</li>";
    echo "<li><strong>course_id:</strong> " . ($originalFreebie['course_id'] ?? 'NULL') . "</li>";
    echo "<li><strong>has_course:</strong> " . ($originalFreebie['has_course'] ?? 'NULL') . "</li>";
    echo "</ul>";
} else {
    echo "<pre class='error'>❌ Freebie ID 7 nicht gefunden!</pre>";
}

// 2. Kurs-Daten in customer_freebie_courses prüfen
echo "<h2>2️⃣ Kurs-Daten für Freebie ID 7 prüfen</h2>";

$stmt = $pdo->prepare("SELECT * FROM customer_freebie_courses WHERE freebie_id = 7");
$stmt->execute();
$coursesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($coursesData)) {
    echo "<pre class='success'>✅ Kurs-Daten gefunden: " . count($coursesData) . " Einträge</pre>";
    
    foreach ($coursesData as $course) {
        echo "<h3>Kurs ID: " . $course['id'] . "</h3>";
        echo "<table>";
        echo "<tr><th>Feld</th><th>Wert</th></tr>";
        foreach ($course as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
        }
        echo "</table>";
        
        // Module zählen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $moduleCount = $stmt->fetchColumn();
        
        // Lektionen zählen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $lessonCount = $stmt->fetchColumn();
        
        echo "<pre class='success'>";
        echo "📦 Module: $moduleCount\n";
        echo "📚 Lektionen: $lessonCount";
        echo "</pre>";
    }
} else {
    echo "<pre class='error'>❌ KEINE Kurs-Daten für Freebie ID 7 gefunden!</pre>";
    echo "<p class='warning'>⚠️ Das ist wahrscheinlich das Problem - wenn das Original-Freebie keine Kurs-Daten hat, kann auch nichts kopiert werden.</p>";
}

// 3. Prüfen welches Freebie den Kurs hat
echo "<h2>3️⃣ Welches Freebie hat den Videokurs?</h2>";

$stmt = $pdo->query("
    SELECT cf.id, cf.customer_id, cf.headline, cfc.id as course_id, 
           (SELECT COUNT(*) FROM course_modules WHERE course_id = cfc.id) as modules,
           (SELECT COUNT(*) FROM course_lessons WHERE course_id = cfc.id) as lessons
    FROM customer_freebies cf
    LEFT JOIN customer_freebie_courses cfc ON cf.id = cfc.freebie_id
    WHERE cfc.id IS NOT NULL
    ORDER BY cf.id DESC
    LIMIT 10
");

$freebiesWithCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($freebiesWithCourses)) {
    echo "<pre class='success'>✅ Freebies mit Videokursen gefunden:</pre>";
    echo "<table>";
    echo "<tr><th>Freebie ID</th><th>Customer ID</th><th>Headline</th><th>Course ID</th><th>Module</th><th>Lektionen</th></tr>";
    foreach ($freebiesWithCourses as $row) {
        $highlight = ($row['id'] == 7) ? "style='background:#2a4a2a;'" : "";
        echo "<tr $highlight>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['customer_id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['headline'], 0, 50)) . "</td>";
        echo "<td>" . $row['course_id'] . "</td>";
        echo "<td>" . $row['modules'] . "</td>";
        echo "<td>" . $row['lessons'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<pre class='error'>❌ Keine Freebies mit Videokursen gefunden!</pre>";
}

// 4. Dein gekauftes Freebie prüfen
echo "<h2>4️⃣ Dein gekauftes Freebie prüfen</h2>";

// Finde das zuletzt erstellte Freebie für den aktuellen User
$stmt = $pdo->prepare("
    SELECT cf.*, cfc.id as course_id
    FROM customer_freebies cf
    LEFT JOIN customer_freebie_courses cfc ON cf.id = cfc.freebie_id
    WHERE cf.copied_from_freebie_id = 7
    ORDER BY cf.created_at DESC
    LIMIT 1
");
$stmt->execute();
$purchasedFreebie = $stmt->fetch(PDO::FETCH_ASSOC);

if ($purchasedFreebie) {
    echo "<pre class='success'>✅ Dein gekauftes Freebie gefunden (ID: " . $purchasedFreebie['id'] . ")</pre>";
    
    if ($purchasedFreebie['course_id']) {
        echo "<pre class='success'>✅ Kurs wurde kopiert (Course ID: " . $purchasedFreebie['course_id'] . ")</pre>";
        
        // Module und Lektionen zählen
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_modules WHERE course_id = ?");
        $stmt->execute([$purchasedFreebie['course_id']]);
        $moduleCount = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_lessons WHERE course_id = ?");
        $stmt->execute([$purchasedFreebie['course_id']]);
        $lessonCount = $stmt->fetchColumn();
        
        echo "<pre class='success'>";
        echo "📦 Module kopiert: $moduleCount\n";
        echo "📚 Lektionen kopiert: $lessonCount";
        echo "</pre>";
    } else {
        echo "<pre class='error'>❌ KEIN Kurs kopiert!</pre>";
    }
    
    echo "<h3>Freebie-Details:</h3>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    foreach ($purchasedFreebie as $key => $value) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<pre class='warning'>⚠️ Noch kein gekauftes Freebie gefunden</pre>";
}

// 5. Webhook-Logs prüfen
echo "<h2>5️⃣ Letzte Webhook-Logs</h2>";

$logFile = __DIR__ . '/webhook-logs.txt';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $last50 = array_slice($lines, -50);
    
    echo "<pre style='max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars(implode("\n", $last50));
    echo "</pre>";
} else {
    echo "<pre class='error'>❌ Webhook-Logs nicht gefunden</pre>";
}

echo "<hr>";
echo "<h2>🎯 Zusammenfassung</h2>";
echo "<p>Basierend auf den Daten oben sollte klar sein:</p>";
echo "<ol>";
echo "<li>Hat Freebie ID 7 überhaupt einen Videokurs? (Prüfe Schritt 2)</li>";
echo "<li>Wenn ja: Wurde der Kurs kopiert? (Prüfe Schritt 4)</li>";
echo "<li>Wenn nein: Welches Freebie hat den Kurs? (Prüfe Schritt 3)</li>";
echo "</ol>";
?>