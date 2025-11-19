<?php
/**
 * FIX: Kopiert Videokurse für alle Marktplatz-Käufe die noch keinen Kurs haben
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Videokurs-Kopien</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#0f0f1e;color:#fff;font-size:14px;}
.success{background:#10b981;padding:15px;margin:10px 0;border-radius:8px;}
.error{background:#ef4444;padding:15px;margin:10px 0;border-radius:8px;}
.info{background:#3b82f6;padding:15px;margin:10px 0;border-radius:8px;}
h1{color:#667eea;}
pre{background:#1a1a2e;padding:10px;border-radius:5px;overflow-x:auto;}
</style></head><body>";

echo "<h1>🔧 Fix: Videokurs-Kopien für Marktplatz-Käufe</h1>";

try {
    $pdo = getDBConnection();
    
    // Finde alle kopierten Freebies ohne Videokurs
    $stmt = $pdo->query("
        SELECT 
            cf.id as copied_freebie_id,
            cf.customer_id as buyer_id,
            cf.headline,
            cf.copied_from_freebie_id as source_freebie_id
        FROM customer_freebies cf
        WHERE cf.copied_from_freebie_id IS NOT NULL
        AND NOT EXISTS (
            SELECT 1 FROM freebie_courses fc 
            WHERE fc.freebie_id = cf.id
        )
    ");
    
    $needsFix = $stmt->fetchAll();
    
    echo "<div class='info'>Gefunden: " . count($needsFix) . " Marktplatz-Käufe ohne Videokurs</div>";
    
    if (empty($needsFix)) {
        echo "<div class='success'>✅ Alle Marktplatz-Käufe haben bereits Videokurse!</div>";
        echo "</body></html>";
        exit;
    }
    
    // Für jeden Fix durchführen
    foreach ($needsFix as $freebie) {
        echo "<div class='info'>";
        echo "<strong>Freebie ID {$freebie['copied_freebie_id']}</strong>: {$freebie['headline']}<br>";
        echo "Käufer ID: {$freebie['buyer_id']}<br>";
        echo "Original ID: {$freebie['source_freebie_id']}";
        echo "</div>";
        
        // Prüfe ob Original einen Videokurs hat
        $stmt = $pdo->prepare("
            SELECT * FROM freebie_courses 
            WHERE freebie_id = ?
        ");
        $stmt->execute([$freebie['source_freebie_id']]);
        $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourceCourse) {
            echo "<div class='info'>→ Original hat keinen Videokurs. Überspringe...</div>";
            continue;
        }
        
        echo "<div class='info'>→ Original hat Videokurs: {$sourceCourse['title']}</div>";
        
        // Videokurs kopieren
        copyFreebieVideoCourse(
            $pdo, 
            $freebie['source_freebie_id'], 
            $freebie['copied_freebie_id'], 
            $freebie['buyer_id']
        );
        
        echo "<div class='success'>✅ Videokurs erfolgreich kopiert!</div>";
    }
    
    echo "<h2>🎉 Fertig!</h2>";
    echo "<div class='success'>Alle fehlenden Videokurse wurden kopiert!</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";

/**
 * Kopiert den kompletten Videokurs eines Freebies zum Käufer
 */
function copyFreebieVideoCourse($pdo, $sourceFreebieId, $targetFreebieId, $buyerId) {
    // 1. Source Course laden
    $stmt = $pdo->prepare("
        SELECT * FROM freebie_courses 
        WHERE freebie_id = ?
    ");
    $stmt->execute([$sourceFreebieId]);
    $sourceCourse = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sourceCourse) {
        echo "<div class='info'>Kein Source-Course gefunden</div>";
        return;
    }
    
    // 2. Videokurs-Eintrag für Käufer erstellen
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (
            freebie_id,
            customer_id,
            title,
            description,
            is_active,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $targetFreebieId,
        $buyerId,
        $sourceCourse['title'],
        $sourceCourse['description'],
        $sourceCourse['is_active']
    ]);
    
    $newCourseId = $pdo->lastInsertId();
    echo "<div class='info'>→ Kurs-Container erstellt (ID: $newCourseId)</div>";
    
    // 3. Alle Module kopieren
    $stmt = $pdo->prepare("
        SELECT * FROM freebie_course_modules 
        WHERE course_id = ?
        ORDER BY sort_order
    ");
    $stmt->execute([$sourceCourse['id']]);
    $sourceModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $moduleMapping = [];
    
    foreach ($sourceModules as $sourceModule) {
        $stmt = $pdo->prepare("
            INSERT INTO freebie_course_modules (
                course_id,
                title,
                description,
                sort_order,
                unlock_after_days,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $newCourseId,
            $sourceModule['title'],
            $sourceModule['description'],
            $sourceModule['sort_order'],
            $sourceModule['unlock_after_days'] ?? 0
        ]);
        
        $newModuleId = $pdo->lastInsertId();
        $moduleMapping[$sourceModule['id']] = $newModuleId;
        
        echo "<div class='info'>→ Modul kopiert: {$sourceModule['title']} (ID: $newModuleId)</div>";
    }
    
    // 4. Alle Lektionen kopieren
    $totalLessonsCopied = 0;
    
    foreach ($moduleMapping as $oldModuleId => $newModuleId) {
        $stmt = $pdo->prepare("
            SELECT * FROM freebie_course_lessons 
            WHERE module_id = ?
            ORDER BY sort_order
        ");
        $stmt->execute([$oldModuleId]);
        $sourceLessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sourceLessons as $sourceLesson) {
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lessons (
                    module_id,
                    title,
                    description,
                    video_url,
                    pdf_url,
                    sort_order,
                    unlock_after_days,
                    button_text,
                    button_url,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $newModuleId,
                $sourceLesson['title'],
                $sourceLesson['description'],
                $sourceLesson['video_url'],
                $sourceLesson['pdf_url'] ?? null,
                $sourceLesson['sort_order'],
                $sourceLesson['unlock_after_days'] ?? 0,
                $sourceLesson['button_text'] ?? null,
                $sourceLesson['button_url'] ?? null
            ]);
            
            $totalLessonsCopied++;
        }
    }
    
    echo "<div class='info'>→ Lektionen kopiert: $totalLessonsCopied</div>";
    
    echo "<div class='success'>";
    echo "✅ Videokurs komplett kopiert!<br>";
    echo "Module: " . count($moduleMapping) . "<br>";
    echo "Lektionen: $totalLessonsCopied";
    echo "</div>";
}
