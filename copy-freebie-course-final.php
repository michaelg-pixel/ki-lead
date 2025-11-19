<?php
/**
 * COPY FREEBIE COURSE - FINALE VERSION
 * Kopiert die Videokurs-Verknüpfung vom Original-Freebie
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
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; color: white; }
        .btn-primary { background: #10b981; }
        .btn-secondary { background: #667eea; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 11px; }
    </style>
</head>
<body>
<h1>🎓 Videokurs-Verknüpfung kopieren</h1>";

try {
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
    echo "<p>has_course: " . ($sourceFreebie['has_course'] ?? '0') . "</p>";
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
    echo "<p>Käufer customer_id: {$targetFreebie['customer_id']}</p>";
    echo "</div>";
    
    // SCHRITT 3: Kurs-Verknüpfung des Originals finden
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Kurs-Verknüpfung suchen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE customer_freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourseLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCourseLink) {
        echo "<p class='error'>❌ Keine Kurs-Verknüpfung für Freebie $sourceFreebieId gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Kurs-Verknüpfung gefunden:</p>";
    echo "<pre>" . print_r($sourceCourseLink, true) . "</pre>";
    
    $courseId = $sourceCourseLink['course_id'];
    
    // Admin-Kurs laden
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($course) {
        $courseName = $course['course_name'] ?? $course['title'] ?? $course['name'] ?? 'Kurs';
        echo "<p class='success'>✓ Admin-Template-Kurs: " . htmlspecialchars($courseName) . " (ID: $courseId)</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: Module/Lektionen des Originals
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Module & Lektionen</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM freebie_course_modules WHERE customer_freebie_id = ?");
    $stmt->execute([$sourceFreebieId]);
    $moduleCount = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM freebie_course_lessons l
        JOIN freebie_course_modules m ON l.module_id = m.id
        WHERE m.customer_freebie_id = ?
    ");
    $stmt->execute([$sourceFreebieId]);
    $lessonCount = $stmt->fetchColumn();
    
    echo "<p class='info'>📦 $moduleCount Module</p>";
    echo "<p class='info'>📚 $lessonCount Lektionen</p>";
    
    echo "</div>";
    
    // SCHRITT 5: Prüfen ob Ziel bereits Verknüpfung hat
    echo "<div class='box'>";
    echo "<h2>SCHRITT 5: Ziel-Status prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE customer_freebie_id = ?");
    $stmt->execute([$targetFreebieId]);
    $existingLink = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingLink) {
        echo "<p class='error'>⚠️ Freebie $targetFreebieId hat bereits eine Kurs-Verknüpfung!</p>";
        echo "<pre>" . print_r($existingLink, true) . "</pre>";
    } else {
        echo "<p class='success'>✓ Keine existierende Verknüpfung</p>";
    }
    
    // Module/Lektionen prüfen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM freebie_course_modules WHERE customer_freebie_id = ?");
    $stmt->execute([$targetFreebieId]);
    $existingModules = $stmt->fetchColumn();
    
    if ($existingModules > 0) {
        echo "<p class='error'>⚠️ Freebie $targetFreebieId hat bereits $existingModules Module!</p>";
    } else {
        echo "<p class='success'>✓ Keine existierenden Module</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 6: KOPIEREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 6: KOPIEREN DURCHFÜHREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // 1. Kurs-Verknüpfung erstellen
            if ($existingLink) {
                $stmt = $pdo->prepare("UPDATE freebie_courses SET course_id = ?, updated_at = NOW() WHERE customer_freebie_id = ?");
                $stmt->execute([$courseId, $targetFreebieId]);
                echo "<p class='success'>✓ Kurs-Verknüpfung aktualisiert</p>";
            } else {
                $stmt = $pdo->prepare("INSERT INTO freebie_courses (customer_freebie_id, course_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$targetFreebieId, $courseId]);
                echo "<p class='success'>✓ Kurs-Verknüpfung erstellt</p>";
            }
            
            // 2. Module kopieren
            if ($existingModules > 0) {
                $stmt = $pdo->prepare("DELETE FROM freebie_course_modules WHERE customer_freebie_id = ?");
                $stmt->execute([$targetFreebieId]);
                echo "<p class='info'>🗑️ $existingModules alte Module gelöscht</p>";
            }
            
            $stmt = $pdo->prepare("SELECT * FROM freebie_course_modules WHERE customer_freebie_id = ? ORDER BY module_order");
            $stmt->execute([$sourceFreebieId]);
            $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $moduleMapping = [];
            
            foreach ($sourceModules as $module) {
                $excludedFields = ['id', 'created_at', 'updated_at'];
                $fields = [];
                $values = [];
                
                foreach ($module as $field => $value) {
                    if (!in_array($field, $excludedFields)) {
                        if ($field === 'customer_freebie_id') {
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
                $sql = "INSERT INTO freebie_course_modules (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
                
                $newModuleId = $pdo->lastInsertId();
                $moduleMapping[$module['id']] = $newModuleId;
            }
            
            echo "<p class='success'>✓ " . count($sourceModules) . " Module kopiert</p>";
            
            // 3. Lektionen kopieren
            $totalLessons = 0;
            foreach ($sourceModules as $module) {
                $stmt = $pdo->prepare("SELECT * FROM freebie_course_lessons WHERE module_id = ? ORDER BY lesson_order");
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
                    $sql = "INSERT INTO freebie_course_lessons (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values);
                    $totalLessons++;
                }
            }
            
            echo "<p class='success'>✓ $totalLessons Lektionen kopiert</p>";
            
            // 4. has_course Flag setzen
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
            $stmt->execute([$targetFreebieId]);
            echo "<p class='success'>✓ has_course Flag gesetzt</p>";
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH!</p>";
            echo "<p><a href='/customer/dashboard.php?page=freebies' class='btn btn-secondary'>→ Zu Meine Freebies</a></p>";
            echo "<p><small>Der Videokurs-Button sollte jetzt beim Freebie sichtbar sein!</small></p>";
            
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
        echo "<p><strong>Das wird passiert:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Kurs-Verknüpfung zu course_id $courseId erstellen</li>";
        echo "<li>✅ $moduleCount Module kopieren</li>";
        echo "<li>✅ $lessonCount Lektionen kopieren</li>";
        echo "<li>✅ has_course Flag setzen</li>";
        echo "</ul>";
        
        if ($existingLink || $existingModules > 0) {
            echo "<p style='color: #f59e0b;'>⚠️ Existierende Daten werden überschrieben!</p>";
        }
        
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