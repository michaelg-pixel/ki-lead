<?php
/**
 * Quick Test: 3 Videos zu Lektion 19 hinzufügen
 * Zum Testen des Multi-Video Features
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Lektion 19 Details abrufen
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE id = 19");
    $stmt->execute();
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        die(json_encode(['error' => 'Lektion 19 nicht gefunden']));
    }
    
    echo "<h2>Lektion 19 gefunden: " . htmlspecialchars($lesson['title']) . "</h2>";
    echo "<p>Modul ID: " . $lesson['module_id'] . "</p>";
    
    // Prüfen ob lesson_videos Tabelle existiert
    try {
        $pdo->query("SELECT 1 FROM lesson_videos LIMIT 1");
        echo "<p style='color: green;'>✅ lesson_videos Tabelle existiert</p>";
    } catch (Exception $e) {
        die("<p style='color: red;'>❌ lesson_videos Tabelle existiert nicht. Bitte führe erst die Migration aus!</p>");
    }
    
    // Bestehende Videos für Lektion 19 löschen
    $stmt = $pdo->prepare("DELETE FROM lesson_videos WHERE lesson_id = 19");
    $stmt->execute();
    
    // 3 Test-Videos einfügen
    $videos = [
        [
            'lesson_id' => 19,
            'video_title' => 'Video 1 - Einführung',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Beispiel-URL
            'sort_order' => 1
        ],
        [
            'lesson_id' => 19,
            'video_title' => 'Video 2 - Hauptteil',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Beispiel-URL
            'sort_order' => 2
        ],
        [
            'lesson_id' => 19,
            'video_title' => 'Video 3 - Zusammenfassung',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ', // Beispiel-URL
            'sort_order' => 3
        ]
    ];
    
    foreach ($videos as $video) {
        $stmt = $pdo->prepare("
            INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order)
            VALUES (:lesson_id, :video_title, :video_url, :sort_order)
        ");
        $stmt->execute($video);
    }
    
    echo "<p style='color: green; font-weight: bold;'>✅ 3 Videos erfolgreich zu Lektion 19 hinzugefügt!</p>";
    
    // Videos anzeigen
    $stmt = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = 19 ORDER BY sort_order");
    $stmt->execute();
    $inserted_videos = $stmt->fetchAll();
    
    echo "<h3>Eingefügte Videos:</h3>";
    echo "<ul>";
    foreach ($inserted_videos as $v) {
        echo "<li><strong>" . htmlspecialchars($v['video_title']) . "</strong> - " . htmlspecialchars($v['video_url']) . "</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<h3>🎯 Nächster Schritt:</h3>";
    echo "<p>Ändere die video_url zu deinen echten Video-URLs (Vimeo oder YouTube)</p>";
    echo "<p>Gehe dann zu: <a href='/customer/course-player.php?id=10&lesson=19'>Lektion 19 ansehen</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>