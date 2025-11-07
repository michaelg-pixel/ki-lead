<?php
/**
 * DEBUG: Zeigt was course-view.php lädt
 */

session_start();
require_once '../config/database.php';

$pdo = getDBConnection();
$course_id = 5;

// Simuliere das, was course-view.php macht
$stmt = $pdo->prepare("
    SELECT * FROM course_modules 
    WHERE course_id = ? 
    ORDER BY sort_order ASC
");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

echo "<h1>Debug: Was wird geladen?</h1>";
echo "<pre>";

foreach ($modules as $module) {
    echo "\n📦 MODUL: " . $module['title'] . "\n";
    echo "   ID: " . $module['id'] . "\n\n";
    
    // Lektionen laden
    $stmt = $pdo->prepare("
        SELECT * FROM course_lessons 
        WHERE module_id = ?
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$module['id']]);
    $lessons = $stmt->fetchAll();
    
    foreach ($lessons as $lesson) {
        echo "   📝 LEKTION: " . $lesson['title'] . "\n";
        echo "      ID: " . $lesson['id'] . "\n";
        echo "      Video URL: " . ($lesson['video_url'] ?: 'KEIN HAUPTVIDEO') . "\n";
        
        // Zusätzliche Videos laden
        $stmt = $pdo->prepare("
            SELECT * FROM lesson_videos 
            WHERE lesson_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$lesson['id']]);
        $videos = $stmt->fetchAll();
        
        echo "      Zusätzliche Videos: " . count($videos) . "\n";
        
        foreach ($videos as $video) {
            echo "         🎬 " . ($video['video_title'] ?: 'Kein Titel') . "\n";
            echo "            URL: " . $video['video_url'] . "\n";
        }
        
        echo "\n";
    }
}

echo "</pre>";

echo "<h2>Test-Link</h2>";
echo "<a href='course-view.php?id=5' style='font-size: 20px; color: #a855f7;'>→ Zur Course-View</a>";
?>
