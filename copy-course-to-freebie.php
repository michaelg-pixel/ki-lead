<?php
/**
 * COPY COURSE FROM ORIGINAL FREEBIE
 * Kopiert den Videokurs vom Original-Marktplatz-Freebie
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$targetFreebieId = 53; // Das gekaufte Freebie
$sourceFreebieId = 7;  // Das Original-Freebie (copied_from_freebie_id)

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Copy Course to Freebie</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .btn-primary { background: #10b981; color: white; }
    </style>
</head>
<body>
<h1>🎓 Videokurs kopieren: Freebie $sourceFreebieId → $targetFreebieId</h1>";

try {
    // SCHRITT 1: Original-Freebie prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: Original-Freebie prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$sourceFreebieId]);
    $sourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceFreebie) {
        echo "<p class='error'>❌ Original-Freebie $sourceFreebieId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Original-Freebie gefunden: " . htmlspecialchars($sourceFreebie['headline']) . "</p>";
    echo "<p>Owner customer_id: {$sourceFreebie['customer_id']}</p>";
    echo "</div>";
    
    // SCHRITT 2: Ziel-Freebie prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Ziel-Freebie prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$targetFreebieId]);
    $targetFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$targetFreebie) {
        echo "<p class='error'>❌ Ziel-Freebie $targetFreebieId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ Ziel-Freebie gefunden: " . htmlspecialchars($targetFreebie['headline']) . "</p>";
    echo "<p>Käufer customer_id: {$targetFreebie['customer_id']}</p>";
    echo "</div>";
    
    // SCHRITT 3: Module des Original-Freebies finden
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Module des Original-Freebies suchen</h2>";
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebie_modules 
        WHERE customer_freebie_id = ? 
        ORDER BY module_order
    ");
    $stmt->execute([$sourceFreebieId]);
    $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$sourceModules) {
        echo "<p class='error'>❌ Keine Module gefunden für Freebie $sourceFreebieId</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ " . count($sourceModules) . " Modul(e) gefunden:</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Order</th></tr>";
    foreach ($sourceModules as $module) {
        echo "<tr>";
        echo "<td>{$module['id']}</td>";
        echo "<td>" . htmlspecialchars($module['module_name']) . "</td>";
        echo "<td>{$module['module_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Lektionen zählen
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM customer_freebie_lessons l
        JOIN customer_freebie_modules m ON l.module_id = m.id
        WHERE m.customer_freebie_id = ?
    ");
    $stmt->execute([$sourceFreebieId]);
    $lessonCount = $stmt->fetchColumn();
    echo "<p class='info'>📚 Gesamt: $lessonCount Lektion(en)</p>";
    
    echo "</div>";
    
    // SCHRITT 4: Prüfen ob Ziel bereits Module hat
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Ziel-Freebie auf existierende Module prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebie_modules WHERE customer_freebie_id = ?");
    $stmt->execute([$targetFreebieId]);
    $existingModules = $stmt->fetchColumn();
    
    if ($existingModules > 0) {
        echo "<p class='error'>⚠️ Ziel-Freebie hat bereits $existingModules Modul(e)!</p>";
        echo "<p>Soll ich die existierenden Module überschreiben?</p>";
    } else {
        echo "<p class='success'>✓ Ziel-Freebie hat noch keine Module</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 5: KOPIEREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 5: VIDEOKURS KOPIEREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // Existierende Module löschen
            if ($existingModules > 0) {
                $stmt = $pdo->prepare("DELETE FROM customer_freebie_modules WHERE customer_freebie_id = ?");
                $stmt->execute([$targetFreebieId]);
                echo "<p class='info'>🗑️ $existingModules alte Module gelöscht</p>";
            }
            
            $moduleMapping = []; // Alt-ID => Neu-ID
            
            // Module kopieren
            foreach ($sourceModules as $sourceModule) {
                $stmt = $pdo->prepare("
                    INSERT INTO customer_freebie_modules (
                        customer_freebie_id, module_name, module_order, 
                        is_drip, drip_days, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $targetFreebieId,
                    $sourceModule['module_name'],
                    $sourceModule['module_order'],
                    $sourceModule['is_drip'] ?? 0,
                    $sourceModule['drip_days'] ?? 0
                ]);
                
                $newModuleId = $pdo->lastInsertId();
                $moduleMapping[$sourceModule['id']] = $newModuleId;
                
                echo "<p class='success'>✓ Modul kopiert: {$sourceModule['module_name']} (ID {$sourceModule['id']} → $newModuleId)</p>";
            }
            
            // Lektionen kopieren
            $totalLessons = 0;
            foreach ($sourceModules as $sourceModule) {
                $stmt = $pdo->prepare("
                    SELECT * FROM customer_freebie_lessons 
                    WHERE module_id = ? 
                    ORDER BY lesson_order
                ");
                $stmt->execute([$sourceModule['id']]);
                $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($lessons as $lesson) {
                    $newModuleId = $moduleMapping[$sourceModule['id']];
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO customer_freebie_lessons (
                            module_id, lesson_title, lesson_order,
                            is_drip, drip_days, created_at
                        ) VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $newModuleId,
                        $lesson['lesson_title'],
                        $lesson['lesson_order'],
                        $lesson['is_drip'] ?? 0,
                        $lesson['drip_days'] ?? 0
                    ]);
                    
                    $newLessonId = $pdo->lastInsertId();
                    
                    // Videos kopieren
                    $stmt = $pdo->prepare("SELECT * FROM customer_freebie_lesson_videos WHERE lesson_id = ? ORDER BY video_order");
                    $stmt->execute([$lesson['id']]);
                    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($videos as $video) {
                        $stmt = $pdo->prepare("
                            INSERT INTO customer_freebie_lesson_videos (
                                lesson_id, video_url, video_title, video_order, created_at
                            ) VALUES (?, ?, ?, ?, NOW())
                        ");
                        
                        $stmt->execute([
                            $newLessonId,
                            $video['video_url'],
                            $video['video_title'] ?? '',
                            $video['video_order']
                        ]);
                    }
                    
                    $totalLessons++;
                }
            }
            
            // has_course Flag setzen
            $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
            $stmt->execute([$targetFreebieId]);
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH!</p>";
            echo "<p class='info'>📦 " . count($sourceModules) . " Module kopiert</p>";
            echo "<p class='info'>📚 $totalLessons Lektionen kopiert</p>";
            echo "<p><a href='/customer/edit-course.php?id=$targetFreebieId' class='btn btn-primary'>🎓 Zum Videokurs</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNG
        echo "<div class='box'>";
        echo "<h2>🚀 BEREIT ZUM KOPIEREN?</h2>";
        echo "<p><strong>Das wird passieren:</strong></p>";
        echo "<ul>";
        echo "<li>✅ " . count($sourceModules) . " Module kopieren</li>";
        echo "<li>✅ ~$lessonCount Lektionen kopieren</li>";
        echo "<li>✅ Alle Videos kopieren</li>";
        if ($existingModules > 0) {
            echo "<li>⚠️ $existingModules existierende Module werden überschrieben</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?confirm=yes' class='btn btn-primary'>🎓 JETZT KOPIEREN</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>