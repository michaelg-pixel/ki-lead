<?php
/**
 * DIAGNOSE VIDEOKURS-STRUKTUR
 * Findet die richtigen Tabellen für Videokurse
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Videokurs-Struktur Diagnose</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; font-size: 12px; }
        th { color: #667eea; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 11px; }
    </style>
</head>
<body>
<h1>🔍 Videokurs-Struktur Diagnose</h1>";

try {
    // SCHRITT 1: Alle Tabellen auflisten
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: Alle Datenbank-Tabellen</h2>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p class='info'>📋 " . count($allTables) . " Tabellen gefunden</p>";
    
    // Tabellen filtern die mit Kurs/Video/Lesson/Module zu tun haben
    $relevantTables = [];
    $keywords = ['course', 'module', 'lesson', 'video', 'freebie'];
    
    foreach ($allTables as $table) {
        foreach ($keywords as $keyword) {
            if (stripos($table, $keyword) !== false) {
                $relevantTables[] = $table;
                break;
            }
        }
    }
    
    echo "<p class='success'>✓ " . count($relevantTables) . " relevante Tabellen:</p>";
    echo "<pre>" . implode("\n", $relevantTables) . "</pre>";
    
    echo "</div>";
    
    // SCHRITT 2: Struktur jeder relevanten Tabelle anzeigen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Tabellenstrukturen</h2>";
    
    foreach ($relevantTables as $table) {
        echo "<h3 style='color: #667eea; margin-top: 20px;'>📋 $table</h3>";
        
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Anzahl Einträge
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p class='info'>Einträge: $count</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 3: Freebie 7 analysieren (Original mit Videokurs)
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Freebie 7 analysieren (Original mit Videokurs)</h2>";
    
    $sourceFreebieId = 7;
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sourceFreebie) {
        echo "<p class='success'>✓ Freebie 7 gefunden: " . htmlspecialchars($sourceFreebie['headline']) . "</p>";
        echo "<p>has_course: " . ($sourceFreebie['has_course'] ?? '0') . "</p>";
        
        // In jeder relevanten Tabelle nach Freebie 7 suchen
        foreach ($relevantTables as $table) {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Mögliche Spalten für Freebie-Verknüpfung
            $possibleColumns = ['customer_freebie_id', 'freebie_id', 'course_id'];
            
            foreach ($possibleColumns as $col) {
                if (in_array($col, $columns)) {
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $col = ?");
                        $stmt->execute([$sourceFreebieId]);
                        $count = $stmt->fetchColumn();
                        
                        if ($count > 0) {
                            echo "<p class='success'>✓ $table.$col: $count Einträge gefunden!</p>";
                            
                            // Beispiel-Daten laden
                            $stmt = $pdo->prepare("SELECT * FROM $table WHERE $col = ? LIMIT 3");
                            $stmt->execute([$sourceFreebieId]);
                            $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            echo "<table>";
                            if ($examples) {
                                // Header
                                echo "<tr>";
                                foreach (array_keys($examples[0]) as $key) {
                                    echo "<th>$key</th>";
                                }
                                echo "</tr>";
                                
                                // Daten
                                foreach ($examples as $row) {
                                    echo "<tr>";
                                    foreach ($row as $value) {
                                        echo "<td>" . htmlspecialchars(substr($value ?? '', 0, 50)) . "</td>";
                                    }
                                    echo "</tr>";
                                }
                            }
                            echo "</table>";
                        }
                    } catch (Exception $e) {
                        // Spalte existiert nicht in dieser Tabelle
                    }
                }
            }
        }
    } else {
        echo "<p class='error'>❌ Freebie 7 nicht gefunden!</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: Courses-Tabelle prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Courses-Tabelle prüfen</h2>";
    
    if (in_array('courses', $allTables)) {
        $stmt = $pdo->query("DESCRIBE courses");
        $courseColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p class='success'>✓ Courses-Tabelle gefunden</p>";
        echo "<p>Spalten: " . implode(', ', $courseColumns) . "</p>";
        
        // Prüfen ob es eine Verknüpfung zu customer_freebies gibt
        if (in_array('customer_id', $courseColumns)) {
            // Kurs vom Original-Owner suchen (customer_id = 4)
            $stmt = $pdo->prepare("SELECT * FROM courses WHERE customer_id = 4 LIMIT 1");
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                echo "<p class='success'>✓ Kurs gefunden für customer_id 4:</p>";
                echo "<pre>" . print_r($course, true) . "</pre>";
                
                // Module des Kurses
                if (in_array('course_modules', $relevantTables)) {
                    $stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ?");
                    $stmt->execute([$course['id']]);
                    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($modules) {
                        echo "<p class='success'>✓ " . count($modules) . " Module gefunden:</p>";
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Name</th></tr>";
                        foreach ($modules as $module) {
                            echo "<tr><td>{$module['id']}</td><td>" . htmlspecialchars($module['module_name'] ?? $module['title'] ?? 'N/A') . "</td></tr>";
                        }
                        echo "</table>";
                    }
                }
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