<?php
/**
 * FIX: Füge fehlende Kurse zur Webhook ID 1 hinzu
 */

require_once '../config/database.php';

$pdo = getDBConnection();

// Die Kurse die für "Launch Angebot" (Webhook 1) freigeschaltet werden sollen
$webhook_id = 1;
$missing_courses = [
    11, // Abnehmen mit Schokolade
    12, // Abnehmen mit Ballaststoffen
    13, // KI Marktplatz Business
    14, // Kinderbuch KI Business
    23, // Affirmationsworkbook
    28  // 7 Tage Morgen Ritual
];

echo "<h1>🔧 Webhook Course Access Fix</h1>";
echo "<p>Füge fehlende Kurse zu Webhook ID {$webhook_id} hinzu...</p>";

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO webhook_course_access (webhook_id, course_id)
        VALUES (?, ?)
    ");
    
    $added = 0;
    foreach ($missing_courses as $course_id) {
        $stmt->execute([$webhook_id, $course_id]);
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: #22c55e;'>✓ Course {$course_id} hinzugefügt</p>";
            $added++;
        } else {
            echo "<p style='color: #888;'>- Course {$course_id} existiert bereits</p>";
        }
    }
    
    $pdo->commit();
    
    echo "<h2 style='color: #22c55e;'>✅ Fertig!</h2>";
    echo "<p>{$added} neue Course-Access Einträge hinzugefügt</p>";
    
    // Zeige alle Course Access für Webhook 1
    $stmt = $pdo->prepare("
        SELECT wca.course_id, c.title
        FROM webhook_course_access wca
        LEFT JOIN courses c ON wca.course_id = c.id
        WHERE wca.webhook_id = ?
        ORDER BY wca.course_id
    ");
    $stmt->execute([$webhook_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Alle Kurse für Webhook ID {$webhook_id}:</h3>";
    echo "<ul>";
    foreach ($courses as $c) {
        echo "<li>Course {$c['course_id']}: {$c['title']}</li>";
    }
    echo "</ul>";
    
    echo "<p style='margin-top: 40px;'><a href='test-webhook-config.php'>← Zurück zur Webhook Config</a></p>";
    echo "<p><a href='test-freebies-debug.php'>🧪 Debug Test öffnen</a></p>";
    echo "<p><a href='dashboard.php?page=freebies'>🎁 Zu den Freebies</a></p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: #ef4444;'>❌ Fehler: " . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        background: #1a1a2e;
        color: white;
        font-family: Arial, sans-serif;
        padding: 40px;
        max-width: 800px;
        margin: 0 auto;
    }
    a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
