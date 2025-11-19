<?php
/**
 * COPY FREEBIE COURSE - ULTRA-SAFE VERSION
 * Mit dynamischer Spalten-Erkennung
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$sourceFreebieId = 7;   // Original mit Videokurs
$targetFreebieId = 53;  // Gekauftes Freebie

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Copy Freebie Course</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .warning { background: #f59e0b; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; color: #000; }
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; color: white; }
        .btn-primary { background: #10b981; }
        .btn-secondary { background: #667eea; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th, td { padding: 6px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
    </style>
</head>
<body>
<h1>🎓 Videokurs-Verknüpfung kopieren (SAFE MODE)</h1>";

try {
    // SCHRITT 0: Tabellenstrukturen dynamisch prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 0: Tabellenstrukturen prüfen</h2>";
    
    // freebie_courses Spalten
    $stmt = $pdo->query("DESCRIBE freebie_courses");
    $freebieCoursesCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='info'>freebie_courses Spalten: " . implode(', ', $freebieCoursesCols) . "</p>";
    
    // Richtige Spalten identifizieren
    $freebieIdCol = null;
    $courseIdCol = null;
    
    foreach ($freebieCoursesCols as $col) {
        if (stripos($col, 'freebie') !== false && stripos($col, 'id') !== false && $col !== 'id') {
            $freebieIdCol = $col;
        }
        if (stripos($col, 'course') !== false && stripos($col, 'id') !== false && $col !== 'id') {
            $courseIdCol = $col;
        }
    }
    
    if (!$freebieIdCol || !$courseIdCol) {
        echo "<p class='error'>❌ Konnte Spalten nicht identifizieren!</p>";
        echo "<p>Gefunden: freebieIdCol=$freebieIdCol, courseIdCol=$courseIdCol</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Freebie-Spalte: <strong>$freebieIdCol</strong></p>";
    echo "<p class='success'>✓ Course-Spalte: <strong>$courseIdCol</strong></p>";
    
    // Module-Tabelle prüfen
    $moduleTable = null;
    $lessonTable = null;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_course_modules'");
    if ($stmt->fetch()) {
        $moduleTable = 'freebie_course_modules';
        echo "<p class='success'>✓ Module-Tabelle: $moduleTable</p>";
        
        $stmt = $pdo->query("DESCRIBE $moduleTable");
        $moduleCols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p class='info'>Module-Spalten: " . implode(', ', $moduleCols) . "</p>";
        
        // Freebie-Spalte in Modulen finden
        $moduleFreebieCol = null;
        foreach ($moduleCols as $col) {
            if (stripos($col, 'freebie') !== false && stripos($col, 'id') !== false) {
                $moduleFreebieCol = $col;
                break;
            }
        }
        echo "<p class='success'>✓ Module Freebie-Spalte: <strong>$moduleFreebieCol</strong></p>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_course_lessons'");
    if ($stmt->fetch()) {
        $lessonTable = 'freebie_course_lessons';
        echo "<p class='success'>✓ Lektionen-Tabelle: $lessonTable</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 1: Original-Freebie prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: Original-Freebie $sourceFreebieId</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceFreebie) {
        echo "<p class='error'>❌ Freebie $sourceFreebieId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Original-Freebie: " . htmlspecialchars($sourceFreebie['headline']) . "</p>";
    echo "</div>";
    
    // SCHRITT 2: Ziel-Freebie prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Ziel-Freebie $targetFreebieId</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$targetFreebieId]);
    $targetFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetFreebie) {
        echo "<p class='error'>❌ Freebie $targetFreebieId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Ziel-Freebie: " . htmlspecialchars($targetFreebie['headline']) . "</p>";
    echo "</div>";
    
    // SCHRITT 3: Kurs-Verknüpfung des Originals finden
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Kurs-Verknüpfung suchen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE $freebieIdCol = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourseLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCourseLink) {
        echo "<p class='error'>❌ Keine Kurs-Verknüpfung für Freebie $sourceFreebieId gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Kurs-Verknüpfung gefunden:</p>";
    echo "<table>";
    foreach ($sourceCourseLink as $key => $value) {
        echo "<tr><th>$key</th><td>$value</td></tr>";
    }
    echo "</table>";
    
    $courseId = $sourceCourseLink[$courseIdCol];
    echo "<p class='info'>🎓 Course ID: $courseId</p>";
    
    echo "</div>";
    
    // SCHRITT 4: Module/Lektionen zählen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Module & Lektionen</h2>";
    
    if ($moduleTable && $moduleFreebieCol) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $moduleTable WHERE $moduleFreebieCol = ?");
        $stmt->execute([$sourceFreebieId]);
        $moduleCount = $stmt->fetchColumn();
        echo "<p class='info'>📦 $moduleCount Module</p>";
        
        if ($lessonTable) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM $lessonTable l
                JOIN $moduleTable m ON l.module_id = m.id
                WHERE m.$moduleFreebieCol = ?
            ");
            $stmt->execute([$sourceFreebieId]);
            $lessonCount = $stmt->fetchColumn();
            echo "<p class='info'>📚 $lessonCount Lektionen</p>";
        }
    }
    
    echo "</div>";
    
    // SCHRITT 5: KOPIEREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 5: KOPIEREN DURCHFÜHREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. Kurs-Verknüpfung
            $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE $freebieIdCol = ?");
            $stmt->execute([$targetFreebieId]);
            $existingLink = $stmt->fetch();
            
            if ($existingLink) {
                $stmt = $pdo->prepare("UPDATE freebie_courses SET $courseIdCol = ?, updated_at = NOW() WHERE $freebieIdCol = ?");
                $stmt->execute([$courseId, $targetFreebieId]);
                echo "<p class='success'>✓ Kurs-Verknüpfung aktualisiert</p>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO freebie_courses ($freebieIdCol, $courseIdCol, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$targetFreebieId, $courseId]);
                echo "<p class='success'>✓ Kurs-Verknüpfung erstellt</p>";
            }
            
            // 2. Module kopieren
            if ($moduleTable && $moduleFreebieCol) {
                // Alte Module löschen
                $stmt = $pdo->prepare("DELETE FROM $moduleTable WHERE $moduleFreebieCol = ?");
                $stmt->execute([$targetFreebieId]);
                
                // Neue Module kopieren
                $stmt = $pdo->prepare("SELECT * FROM $moduleTable WHERE $moduleFreebieCol = ? ORDER BY module_order");
                $stmt->execute([$sourceFreebieId]);
                $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $moduleMapping = [];
                
                foreach ($sourceModules as $module) {
                    $fields = [];
                    $values = [];
                    
                    foreach ($module as $field => $value) {
                        if (!in_array($field, ['id', 'created_at', 'updated_at'])) {
                            if ($field === $moduleFreebieCol) {
                                $fields[] = $field;
                                $values[] = $targetFreebieId;
                            } else {
                                $fields[] = $field;
                                $values[] = $value;
                            }
                        }
                    }
                    
                    $fields[] = 'created_at';
                    $values[] = date('Y-m-d H:i:s');
                    
                    $placeholders = array_fill(0, count($values), '?');
                    $sql = "INSERT INTO $moduleTable (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                    
                    $moduleMapping[$module['id']] = $pdo->lastInsertId();
                }
                
                echo "<p class='success'>✓ " . count($sourceModules) . " Module kopiert</p>";
                
                // 3. Lektionen kopieren
                if ($lessonTable) {
                    $totalLessons = 0;
                    foreach ($sourceModules as $module) {
                        $stmt = $pdo->prepare("SELECT * FROM $lessonTable WHERE module_id = ? ORDER BY lesson_order");
                        $stmt->execute([$module['id']]);
                        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($lessons as $lesson) {
                            $fields = [];
                            $values = [];
                            
                            foreach ($lesson as $field => $value) {
                                if (!in_array($field, ['id', 'created_at', 'updated_at'])) {
                                    if ($field === 'module_id') {
                                        $fields[] = $field;
                                        $values[] = $moduleMapping[$module['id']];
                                    } else {
                                        $fields[] = $field;
                                        $values[] = $value;
                                    }
                                }
                            }
                            
                            $fields[] = 'created_at';
                            $values[] = date('Y-m-d H:i:s');
                            
                            $placeholders = array_fill(0, count($values), '?');
                            $sql = "INSERT INTO $lessonTable (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                            
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($values);
                            $totalLessons++;
                        }
                    }
                    
                    echo "<p class='success'>✓ $totalLessons Lektionen kopiert</p>";
                }
            }
            
            // 4. has_course Flag
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
            $stmt->execute([$targetFreebieId]);
            echo "<p class='success'>✓ has_course Flag gesetzt</p>";
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH!</p>";
            echo "<p><a href='/customer/dashboard.php?page=freebies' class='btn btn-secondary'>→ Zu Meine Freebies</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNG
        echo "<div class='box'>";
        echo "<h2>🚀 BEREIT ZUM KOPIEREN?</h2>";
        echo "<p><strong>Das wird passieren:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Kurs-Verknüpfung erstellen (course_id: $courseId)</li>";
        if (isset($moduleCount)) {
            echo "<li>✅ $moduleCount Module kopieren</li>";
        }
        if (isset($lessonCount)) {
            echo "<li>✅ $lessonCount Lektionen kopieren</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?confirm=yes' class='btn btn-primary'>🎓 JETZT KOPIEREN</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>