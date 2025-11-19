<?php
/**
 * DEBUG: finaltest@web.de - Nach Webhook v5.1 Fix
 */

require_once '../config/database.php';

echo "<h1>🔍 DEBUG: finaltest@web.de</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    
    echo "=== 1. USER CHECK ===\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'finaltest@web.de'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ User existiert!\n";
        echo "User ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "RAW-Code: " . $user['raw_code'] . "\n";
        echo "Erstellt: " . $user['created_at'] . "\n";
        
        $userId = $user['id'];
        
        echo "\n=== 2. FREEBIES CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($freebies) > 0) {
            echo "✅ Freebies gefunden: " . count($freebies) . "\n\n";
            foreach ($freebies as $freebie) {
                echo "Freebie ID: " . $freebie['id'] . "\n";
                echo "  - Headline: " . $freebie['headline'] . "\n";
                echo "  - Typ: " . $freebie['freebie_type'] . "\n";
                echo "  - Kopiert von: " . ($freebie['copied_from_freebie_id'] ?? 'Nein') . "\n";
                echo "  - URL-Slug: " . $freebie['url_slug'] . "\n\n";
            }
        } else {
            echo "❌ Keine Freebies gefunden für User $userId\n";
        }
        
        echo "\n=== 3. VIDEOKURS CHECK ===\n";
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($courses) > 0) {
            echo "✅ Videokurse gefunden: " . count($courses) . "\n\n";
            foreach ($courses as $course) {
                echo "Kurs ID: " . $course['id'] . "\n";
                echo "  - Title: " . $course['title'] . "\n";
                echo "  - Freebie ID: " . $course['freebie_id'] . "\n";
                
                // Module zählen
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM freebie_course_modules WHERE course_id = ?");
                $stmt->execute([$course['id']]);
                $moduleCount = $stmt->fetch()['count'];
                echo "  - Module: $moduleCount\n";
                
                // Lektionen zählen
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM freebie_course_lessons 
                    WHERE module_id IN (SELECT id FROM freebie_course_modules WHERE course_id = ?)
                ");
                $stmt->execute([$course['id']]);
                $lessonCount = $stmt->fetch()['count'];
                echo "  - Lektionen: $lessonCount\n\n";
            }
        } else {
            echo "❌ Keine Videokurse gefunden für User $userId\n";
        }
        
    } else {
        echo "❌ User wurde NICHT angelegt!\n";
    }
    
    echo "\n=== 4. WEBHOOK LOG (LETZTE 100 ZEILEN) ===\n";
    $logFile = __DIR__ . '/webhook.log';
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $lastLines = array_slice($lines, -100);
        echo implode("\n", $lastLines);
    } else {
        echo "❌ Log-Datei existiert NICHT!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";
?>
