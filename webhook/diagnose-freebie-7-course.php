<?php
/**
 * Diagnose: Wie ist Freebie 7 mit dem Videokurs verknüpft?
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();

echo "<h1>🔍 Freebie 7 - Videokurs Verknüpfung</h1>";
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

// 1. Freebie 7 Details
echo "<h2>1️⃣ Freebie ID 7 - Alle Felder</h2>";

$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 7");
$stmt->execute();
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if ($freebie) {
    echo "<pre class='success'>✅ Freebie gefunden</pre>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    foreach ($freebie as $key => $value) {
        $highlight = (stripos($key, 'course') !== false) ? " style='background:#2a4a2a;'" : "";
        echo "<tr$highlight><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>🔑 Wichtige Felder:</h3>";
    echo "<ul>";
    echo "<li><strong>id:</strong> " . $freebie['id'] . "</li>";
    echo "<li><strong>customer_id:</strong> " . $freebie['customer_id'] . "</li>";
    echo "<li><strong>course_id:</strong> " . ($freebie['course_id'] ?? 'NULL') . " " . (empty($freebie['course_id']) ? "❌ LEER!" : "✅") . "</li>";
    echo "<li><strong>has_course:</strong> " . ($freebie['has_course'] ?? 'NULL') . "</li>";
    echo "</ul>";
}

// 2. Suche Kurs in courses Tabelle - ALLE möglichen Verknüpfungen
echo "<h2>2️⃣ Suche Videokurs in 'courses' Tabelle</h2>";

// Zuerst: Struktur der courses Tabelle prüfen
echo "<h3>Struktur der 'courses' Tabelle:</h3>";
$stmt = $pdo->query("DESCRIBE courses");
$courseColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table>";
echo "<tr><th>Feld</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
foreach ($courseColumns as $col) {
    $highlight = (stripos($col['Field'], 'freebie') !== false) ? " style='background:#2a4a2a;'" : "";
    echo "<tr$highlight>";
    echo "<td><strong>" . $col['Field'] . "</strong></td>";
    echo "<td>" . $col['Type'] . "</td>";
    echo "<td>" . $col['Null'] . "</td>";
    echo "<td>" . $col['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Jetzt: Verschiedene Verknüpfungen testen
$queries = [
    "Via customer_freebie_id" => "SELECT * FROM courses WHERE customer_freebie_id = 7",
    "Via freebie_id" => "SELECT * FROM courses WHERE freebie_id = 7",
    "Via customer_id (User von Freebie 7)" => "SELECT * FROM courses WHERE customer_id = (SELECT customer_id FROM customer_freebies WHERE id = 7)",
];

foreach ($queries as $label => $query) {
    echo "<h3>Test: $label</h3>";
    echo "<pre style='font-size:11px;'>$query</pre>";
    
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "<pre class='success'>✅ TREFFER! Das ist die Verknüpfung!</pre>";
            echo "<p><strong>Gefundene Kurse: " . count($results) . "</strong></p>";
            
            foreach ($results as $course) {
                echo "<h4>Kurs ID: " . $course['id'] . "</h4>";
                echo "<table>";
                echo "<tr><th>Feld</th><th>Wert</th></tr>";
                foreach ($course as $key => $value) {
                    echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars(substr($value, 0, 100)) . "</td></tr>";
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
            echo "<pre class='warning'>⚠️ Keine Ergebnisse</pre>";
        }
    } catch (Exception $e) {
        echo "<pre class='error'>❌ Fehler: " . $e->getMessage() . "</pre>";
    }
}

// 3. Alle Kurse anzeigen (zur Kontrolle)
echo "<h2>3️⃣ Alle Kurse in der Datenbank (Top 10)</h2>";

try {
    $stmt = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as modules,
               (SELECT COUNT(*) FROM course_lessons WHERE course_id = c.id) as lessons
        FROM courses c
        ORDER BY c.id DESC
        LIMIT 10
    ");
    $allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($allCourses)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>course_name</th><th>customer_id</th><th>customer_freebie_id / freebie_id</th><th>Module</th><th>Lektionen</th></tr>";
        foreach ($allCourses as $course) {
            $freebieIdField = isset($course['customer_freebie_id']) ? 'customer_freebie_id' : 'freebie_id';
            $freebieIdValue = $course[$freebieIdField] ?? 'NULL';
            
            $highlight = ($freebieIdValue == 7) ? " style='background:#2a4a2a;'" : "";
            
            echo "<tr$highlight>";
            echo "<td>" . $course['id'] . "</td>";
            echo "<td>" . htmlspecialchars(substr($course['course_name'] ?? 'N/A', 0, 30)) . "</td>";
            echo "<td>" . ($course['customer_id'] ?? 'NULL') . "</td>";
            echo "<td><strong>$freebieIdField = $freebieIdValue</strong></td>";
            echo "<td>" . $course['modules'] . "</td>";
            echo "<td>" . $course['lessons'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<pre class='error'>❌ Fehler: " . $e->getMessage() . "</pre>";
}

echo "<hr>";
echo "<h2>🎯 Zusammenfassung</h2>";
echo "<p>Diese Diagnose zeigt:</p>";
echo "<ol>";
echo "<li>Welches Feld in 'courses' Tabelle auf Freebie 7 verweist (customer_freebie_id oder freebie_id?)</li>";
echo "<li>Ob customer_freebies.course_id gesetzt ist (wahrscheinlich nicht)</li>";
echo "<li>Die komplette Struktur der Verknüpfung</li>";
echo "</ol>";
echo "<p><strong>Danach kann ich den Webhook-Code anpassen!</strong></p>";
?>