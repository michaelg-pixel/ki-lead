<?php
/**
 * Migration Script: Video-Support für Customer Freebies
 * 
 * Dieses Script fügt die Felder video_url und video_format zur customer_freebies Tabelle hinzu.
 * 
 * WICHTIG: Dieses Script EINMALIG ausführen!
 * Aufruf: https://app.mehr-infos-jetzt.de/scripts/migrate_video_support.php
 * 
 * Nach erfolgreicher Ausführung kann diese Datei gelöscht werden.
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Video-Support Migration</h1>";
echo "<p>Start: " . date('Y-m-d H:i:s') . "</p>";

try {
    $pdo = getDBConnection();
    
    // Prüfen ob die Spalten bereits existieren
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'video_url'");
    $video_url_exists = $stmt->fetch();
    
    if ($video_url_exists) {
        echo "<p style='color: orange;'>⚠️ Spalte 'video_url' existiert bereits. Migration wurde bereits durchgeführt.</p>";
    } else {
        echo "<p>✅ Füge Spalten hinzu...</p>";
        
        // Spalten hinzufügen
        $sql = "
            ALTER TABLE customer_freebies 
            ADD COLUMN video_url VARCHAR(500) DEFAULT NULL COMMENT 'YouTube/Vimeo/Direct Video URL' AFTER mockup_image_url,
            ADD COLUMN video_format ENUM('16:9', '9:16') DEFAULT '16:9' COMMENT 'Video aspect ratio' AFTER video_url
        ";
        
        $pdo->exec($sql);
        
        echo "<p style='color: green;'>✅ Spalten erfolgreich hinzugefügt!</p>";
        echo "<pre>";
        echo "- video_url VARCHAR(500)\n";
        echo "- video_format ENUM('16:9', '9:16')\n";
        echo "</pre>";
    }
    
    // Tabellenstruktur anzeigen
    echo "<h2>Aktuelle Tabellenstruktur (customer_freebies):</h2>";
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Migration erfolgreich abgeschlossen!</p>";
    echo "<p>Ende: " . date('Y-m-d H:i:s') . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
