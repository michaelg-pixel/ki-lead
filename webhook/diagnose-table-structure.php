<?php
/**
 * Diagnose: Finde die echte Kurs-Tabellenstruktur
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

$pdo = getDBConnection();

echo "<h1>🔍 Tabellen-Struktur Diagnose</h1>";
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

// 1. Alle Tabellen auflisten die mit "course" zu tun haben
echo "<h2>1️⃣ Alle Tabellen die 'course' enthalten</h2>";

$stmt = $pdo->query("SHOW TABLES LIKE '%course%'");
$courseTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($courseTables)) {
    echo "<pre class='success'>✅ Gefundene Tabellen:</pre>";
    echo "<ul>";
    foreach ($courseTables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
} else {
    echo "<pre class='error'>❌ Keine Tabellen mit 'course' gefunden!</pre>";
}

// 2. Alle Tabellen auflisten
echo "<h2>2️⃣ ALLE Tabellen in der Datenbank</h2>";

$stmt = $pdo->query("SHOW TABLES");
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<pre class='success'>✅ Gefunden: " . count($allTables) . " Tabellen</pre>";
echo "<ul style='columns: 3;'>";
foreach ($allTables as $table) {
    $highlight = (stripos($table, 'course') !== false || stripos($table, 'lesson') !== false || stripos($table, 'module') !== false) ? " style='color:#ffaa00; font-weight:bold;'" : "";
    echo "<li$highlight>$table</li>";
}
echo "</ul>";

// 3. Struktur der relevanten Tabellen prüfen
echo "<h2>3️⃣ Struktur der Kurs-relevanten Tabellen</h2>";

foreach ($courseTables as $table) {
    echo "<h3>Tabelle: $table</h3>";
    
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Feld</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . $col['Field'] . "</td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "<td>" . $col['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Beispieldaten anzeigen
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
        $sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($sampleData)) {
            echo "<p class='success'>📊 Beispieldaten:</p>";
            echo "<pre>" . print_r($sampleData, true) . "</pre>";
        }
        
    } catch (Exception $e) {
        echo "<pre class='error'>❌ Fehler: " . $e->getMessage() . "</pre>";
    }
}

// 4. Prüfe customer_freebies Struktur (course_id Feld)
echo "<h2>4️⃣ customer_freebies Struktur (course_id Feld?)</h2>";

try {
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $courseRelatedFields = [];
    foreach ($columns as $col) {
        if (stripos($col['Field'], 'course') !== false || stripos($col['Field'], 'video') !== false) {
            $courseRelatedFields[] = $col;
        }
    }
    
    if (!empty($courseRelatedFields)) {
        echo "<pre class='success'>✅ Kurs-relevante Felder gefunden:</pre>";
        echo "<table>";
        echo "<tr><th>Feld</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($courseRelatedFields as $col) {
            echo "<tr>";
            echo "<td><strong>" . $col['Field'] . "</strong></td>";
            echo "<td>" . $col['Type'] . "</td>";
            echo "<td>" . $col['Null'] . "</td>";
            echo "<td>" . $col['Key'] . "</td>";
            echo "<td>" . $col['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<pre class='warning'>⚠️ Keine kurs-relevanten Felder in customer_freebies gefunden</pre>";
    }
    
} catch (Exception $e) {
    echo "<pre class='error'>❌ Fehler: " . $e->getMessage() . "</pre>";
}

// 5. Wie ist der Videokurs aktuell verknüpft?
echo "<h2>5️⃣ Wie sind Freebies mit Kursen verknüpft?</h2>";

// Verschiedene mögliche Verknüpfungen testen
$possibleLinks = [
    "SELECT cf.id as freebie_id, cf.headline, cf.course_id, cf.has_course 
     FROM customer_freebies cf 
     WHERE cf.has_course = 1 
     LIMIT 5" => "Via course_id in customer_freebies",
     
    "SELECT cf.id as freebie_id, cf.headline, c.id as course_id, c.title
     FROM customer_freebies cf
     JOIN courses c ON c.customer_freebie_id = cf.id
     WHERE cf.id = 7" => "Via courses.customer_freebie_id",
     
    "SELECT cf.id as freebie_id, cf.headline, c.id as course_id, c.course_name
     FROM customer_freebies cf
     JOIN courses c ON c.freebie_id = cf.id
     WHERE cf.id = 7" => "Via courses.freebie_id"
];

foreach ($possibleLinks as $query => $description) {
    echo "<h3>Test: $description</h3>";
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($results)) {
            echo "<pre class='success'>✅ TREFFER! Das ist wahrscheinlich die richtige Verknüpfung!</pre>";
            echo "<pre>" . print_r($results, true) . "</pre>";
        } else {
            echo "<pre class='warning'>⚠️ Keine Ergebnisse</pre>";
        }
    } catch (Exception $e) {
        echo "<pre class='error'>❌ Tabelle/Feld existiert nicht</pre>";
    }
}

echo "<hr>";
echo "<h2>🎯 Zusammenfassung</h2>";
echo "<p>Basierend auf den Daten oben können wir jetzt sehen:</p>";
echo "<ol>";
echo "<li>Welche Tabellen für Kurse verwendet werden</li>";
echo "<li>Wie die Verknüpfung zwischen Freebie und Kurs funktioniert</li>";
echo "<li>Ob course_id direkt in customer_freebies gespeichert wird oder über eine separate Tabelle</li>";
echo "</ol>";
?>