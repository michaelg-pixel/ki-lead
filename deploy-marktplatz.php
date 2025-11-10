<?php
// Deployment Script für marktplatz.php
// Aufruf: https://app.mehr-infos-jetzt.de/deploy-marktplatz.php

$source_file = __DIR__ . '/customer/marktplatz.php';
$target_file = __DIR__ . '/customer/sections/marktplatz.php';
$backup_file = __DIR__ . '/customer/sections/marktplatz.php.backup.' . date('YmdHis');

echo "<h2>🚀 Marktplatz Deployment</h2>";

// 1. Prüfe ob Quell-Datei existiert
if (!file_exists($source_file)) {
    die("❌ Quell-Datei nicht gefunden: " . $source_file);
}

echo "✅ Quell-Datei gefunden: " . filesize($source_file) . " Bytes<br>";

// 2. Erstelle sections Verzeichnis falls nicht vorhanden
$sections_dir = __DIR__ . '/customer/sections';
if (!is_dir($sections_dir)) {
    mkdir($sections_dir, 0755, true);
    echo "✅ Verzeichnis erstellt: /customer/sections<br>";
}

// 3. Backup der alten Datei falls vorhanden
if (file_exists($target_file)) {
    copy($target_file, $backup_file);
    echo "✅ Backup erstellt: " . basename($backup_file) . "<br>";
}

// 4. Datei kopieren
if (copy($source_file, $target_file)) {
    echo "✅ Datei erfolgreich kopiert nach: /customer/sections/marktplatz.php<br>";
    echo "✅ Neue Dateigröße: " . filesize($target_file) . " Bytes<br>";
    
    // 5. Alte Datei löschen
    unlink($source_file);
    echo "✅ Alte Datei gelöscht: /customer/marktplatz.php<br>";
    
    echo "<br>";
    echo "<h3>🎉 Deployment erfolgreich!</h3>";
    echo "<p><a href='/customer/dashboard.php?page=marktplatz' style='display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Zum Marktplatz</a></p>";
} else {
    die("❌ Fehler beim Kopieren der Datei");
}
?>
