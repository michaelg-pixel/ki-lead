<?php
/**
 * Quick Test: Drip-Content für Modul 1, Video 1 aktivieren
 * Setzt unlock_after_days = 1 (Freischaltung nach 1 Tag)
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Finde erste Lektion von Modul 1 in Kurs 10
    $stmt = $pdo->prepare("
        SELECT cl.*, cm.title as module_title
        FROM course_lessons cl
        JOIN course_modules cm ON cl.module_id = cm.id
        WHERE cm.course_id = 10
        AND cm.sort_order = 1
        AND cl.sort_order = 1
        LIMIT 1
    ");
    $stmt->execute();
    $lesson = $stmt->fetch();
    
    if (!$lesson) {
        die("<p style='color: red;'>❌ Erste Lektion von Modul 1 nicht gefunden</p>");
    }
    
    echo "<h2>Lektion gefunden:</h2>";
    echo "<p><strong>Modul:</strong> " . htmlspecialchars($lesson['module_title']) . "</p>";
    echo "<p><strong>Lektion:</strong> " . htmlspecialchars($lesson['title']) . "</p>";
    echo "<p><strong>Lektion ID:</strong> " . $lesson['id'] . "</p>";
    
    // Prüfen ob unlock_after_days Feld existiert
    try {
        $pdo->query("SELECT unlock_after_days FROM course_lessons LIMIT 1");
        echo "<p style='color: green;'>✅ unlock_after_days Feld existiert</p>";
    } catch (Exception $e) {
        die("<p style='color: red;'>❌ unlock_after_days Feld existiert nicht. Bitte führe erst die Migration aus!</p>");
    }
    
    // unlock_after_days auf 1 setzen
    $stmt = $pdo->prepare("
        UPDATE course_lessons 
        SET unlock_after_days = 1 
        WHERE id = ?
    ");
    $stmt->execute([$lesson['id']]);
    
    echo "<p style='color: green; font-weight: bold;'>✅ Drip-Content aktiviert!</p>";
    echo "<p>Diese Lektion wird jetzt erst nach <strong>1 Tag</strong> nach Kurs-Einschreibung freigeschaltet.</p>";
    
    echo "<hr>";
    echo "<h3>🔒 Wie es funktioniert:</h3>";
    echo "<ul>";
    echo "<li>Wenn ein Kunde erstmalig einen Kurs öffnet, wird das Datum in <code>course_enrollments</code> gespeichert</li>";
    echo "<li>Lektionen mit <code>unlock_after_days = 1</code> werden erst 1 Tag später freigeschaltet</li>";
    echo "<li>Lektionen mit <code>unlock_after_days = NULL</code> sind sofort verfügbar</li>";
    echo "</ul>";
    
    echo "<h3>🧪 Zum Testen:</h3>";
    echo "<p>1. Lösche dein enrollment: <code>DELETE FROM course_enrollments WHERE user_id = DEINE_USER_ID AND course_id = 10</code></p>";
    echo "<p>2. Öffne den Kurs: <a href='/customer/course-player.php?id=10'>Kurs ansehen</a></p>";
    echo "<p>3. Modul 1, Video 1 sollte jetzt gesperrt sein mit Countdown</p>";
    
    echo "<h3>⚡ Zum Sofort-Freischalten:</h3>";
    echo "<p>Setze <code>enrolled_at</code> auf gestern: <code>UPDATE course_enrollments SET enrolled_at = DATE_SUB(NOW(), INTERVAL 2 DAY) WHERE user_id = DEINE_USER_ID AND course_id = 10</code></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>