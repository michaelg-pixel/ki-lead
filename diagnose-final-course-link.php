<?php
/**
 * FINALE DIAGNOSE - Finde die EXAKTE Videokurs-Verknüpfung
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Finale Videokurs-Diagnose</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; font-size: 13px; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; font-size: 18px; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 11px; max-height: 400px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
    </style>
</head>
<body>
<h1>🔍 FINALE Videokurs-Diagnose</h1>";

try {
    // SCHRITT 1: courses Tabelle genau analysieren
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: courses Tabellenstruktur</h2>";
    
    $stmt = $pdo->query("DESCRIBE courses");
    $courseColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
    foreach ($courseColumns as $col) {
        $highlight = (stripos($col['Field'], 'customer') !== false || stripos($col['Field'], 'user') !== false || stripos($col['Field'], 'freebie') !== false) ? 'style="color: #10b981; font-weight: bold;"' : '';
        echo "<tr>";
        echo "<td $highlight>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $columnNames = array_column($courseColumns, 'Field');
    echo "<p class='info'>Spalten: " . implode(', ', $columnNames) . "</p>";
    
    if (!in_array('customer_id', $columnNames) && !in_array('user_id', $columnNames)) {
        echo "<p class='error'>❌ Keine customer_id oder user_id in courses!</p>";
        echo "<p class='info'>→ Kurse sind Admin-Templates, keine kundenspezifischen Kurse</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 2: Freebie 7 komplett analysieren (hat Videokurs)
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Freebie 7 komplett analysieren</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 7");
    $stmt->execute();
    $freebie7 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie7) {
        echo "<p class='success'>✓ Freebie 7 gefunden</p>";
        echo "<pre>" . print_r($freebie7, true) . "</pre>";
        
        // Alle Spalten mit "course" im Namen prüfen
        foreach ($freebie7 as $key => $value) {
            if (stripos($key, 'course') !== false && !empty($value)) {
                echo "<p class='success'>✓ Wichtig: $key = $value</p>";
            }
        }
    }
    
    echo "</div>";
    
    // SCHRITT 3: Freebie 53 analysieren (soll Kurs bekommen)
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Freebie 53 analysieren</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = 53");
    $stmt->execute();
    $freebie53 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie53) {
        echo "<p class='success'>✓ Freebie 53 gefunden</p>";
        echo "<pre>" . print_r($freebie53, true) . "</pre>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: Alle Tabellen mit "freebie" UND ("module" ODER "lesson" ODER "video" ODER "course")
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Freebie-Videokurs-Tabellen suchen</h2>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $freebieCourseTables = [];
    foreach ($allTables as $table) {
        if (stripos($table, 'freebie') !== false) {
            if (stripos($table, 'module') !== false || 
                stripos($table, 'lesson') !== false || 
                stripos($table, 'video') !== false ||
                stripos($table, 'course') !== false) {
                $freebieCourseTables[] = $table;
            }
        }
    }
    
    if ($freebieCourseTables) {
        echo "<p class='success'>✓ " . count($freebieCourseTables) . " Freebie-Kurs-Tabellen gefunden:</p>";
        echo "<pre>" . implode("\n", $freebieCourseTables) . "</pre>";
        
        // Jede Tabelle analysieren
        foreach ($freebieCourseTables as $table) {
            echo "<h3 style='color: #667eea;'>$table</h3>";
            
            // Struktur
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p class='info'>Spalten: " . implode(', ', $columns) . "</p>";
            
            // Versuche Daten für Freebie 7 zu finden
            foreach ($columns as $col) {
                if (stripos($col, 'freebie') !== false) {
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $col = 7");
                        $stmt->execute();
                        $count = $stmt->fetchColumn();
                        
                        if ($count > 0) {
                            echo "<p class='success'>✓ $table.$col = 7: $count Einträge!</p>";
                            
                            // Erste 2 Einträge laden
                            $stmt = $pdo->prepare("SELECT * FROM $table WHERE $col = 7 LIMIT 2");
                            $stmt->execute();
                            $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            echo "<pre>" . print_r($examples, true) . "</pre>";
                        }
                    } catch (Exception $e) {
                        // Fehler ignorieren
                    }
                }
            }
        }
    } else {
        echo "<p class='error'>❌ Keine Freebie-Kurs-Tabellen gefunden!</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 5: Direkte Verbindung courses ↔ customer_freebies
    echo "<div class='box'>";
    echo "<h2>SCHRITT 5: Gibt es eine direkte Verknüpfungstabelle?</h2>";
    
    $linkTables = ['customer_freebie_courses', 'freebie_courses', 'course_access', 'freebie_course_access'];
    
    foreach ($linkTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetch()) {
            echo "<p class='success'>✓ Tabelle $table gefunden!</p>";
            
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "<p class='info'>Spalten: " . implode(', ', $columns) . "</p>";
            
            // Einträge anzeigen
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($entries) {
                echo "<pre>" . print_r($entries, true) . "</pre>";
            }
        }
    }
    
    echo "</div>";
    
    // SCHRITT 6: FINALE LÖSUNG
    echo "<div class='box'>";
    echo "<h2>🎯 FINALE LÖSUNG</h2>";
    
    if ($freebie7 && isset($freebie7['has_course']) && $freebie7['has_course'] == 1) {
        echo "<p class='success'>✓ Freebie 7 hat has_course = 1</p>";
        
        // Schauen ob es eine course_id oder ähnliches gibt
        foreach ($freebie7 as $key => $value) {
            if ((stripos($key, 'course') !== false || $key === 'course_id') && !empty($value) && $key !== 'has_course') {
                echo "<p class='success'>🔑 GEFUNDEN: customer_freebies.$key = $value</p>";
                echo "<p class='info'>→ Dies ist wahrscheinlich die Verknüpfung zum Kurs!</p>";
                
                // Kurs laden
                try {
                    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
                    $stmt->execute([$value]);
                    $course = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($course) {
                        echo "<p class='success'>✓ Kurs gefunden:</p>";
                        echo "<pre>" . print_r($course, true) . "</pre>";
                    }
                } catch (Exception $e) {}
            }
        }
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>