<?php
/**
 * Freebies Display Fix
 * Ersetzt die komplexe freebies.php mit der vereinfachten funktionierenden Version
 */

$oldFile = __DIR__ . '/../customer/sections/freebies.php';
$newFile = __DIR__ . '/../customer/sections/freebies-simple.php';
$backupFile = __DIR__ . '/../customer/sections/freebies-backup-' . date('Y-m-d-His') . '.php';

echo "<h2>🔧 Freebies Display Fix</h2>";
echo "<hr><br>";

// 1. Prüfe ob Dateien existieren
if (!file_exists($oldFile)) {
    die("❌ Alte Datei nicht gefunden: $oldFile");
}

if (!file_exists($newFile)) {
    die("❌ Neue Datei nicht gefunden: $newFile");
}

echo "✅ Beide Dateien gefunden<br><br>";

// 2. Backup erstellen
if (!copy($oldFile, $backupFile)) {
    die("❌ Konnte kein Backup erstellen");
}

echo "✅ <strong>Backup erstellt:</strong> " . basename($backupFile) . "<br>";
echo "Größe: " . filesize($backupFile) . " bytes<br><br>";

// 3. Neue Datei kopieren
if (!copy($newFile, $oldFile)) {
    die("❌ Konnte neue Datei nicht kopieren");
}

echo "✅ <strong>Neue Version installiert!</strong><br>";
echo "Größe: " . filesize($oldFile) . " bytes<br><br>";

// 4. Vergleich
$oldSize = filesize($backupFile);
$newSize = filesize($oldFile);

echo "<hr><h3>📊 Vergleich:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Version</th><th>Größe</th></tr>";
echo "<tr><td>Alte Version (Backup)</td><td>" . number_format($oldSize) . " bytes</td></tr>";
echo "<tr><td>Neue Version (Aktiv)</td><td>" . number_format($newSize) . " bytes</td></tr>";
echo "</table><br>";

echo "<hr><h3>✅ Fix abgeschlossen!</h3>";
echo "<p>Die vereinfachte Freebies-Ansicht ist jetzt aktiv.</p>";
echo "<p><strong>Was wurde geändert:</strong></p>";
echo "<ul>";
echo "<li>✅ Vereinfachtes Design ohne komplexe Filter</li>";
echo "<li>✅ Zeigt garantiert alle Templates an</li>";
echo "<li>✅ Zeigt alle Custom Freebies an</li>";
echo "<li>✅ Tab-Navigation zwischen Templates und Custom Freebies</li>";
echo "<li>✅ Direkte Links zu Editor und Vorschau</li>";
echo "<li>✅ Freebie-Links zum Kopieren</li>";
echo "</ul>";

echo "<br><h3>🎯 Jetzt testen:</h3>";
echo "<p><a href='/customer/dashboard.php?page=freebies' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>→ Zu den Freebies</a></p>";

echo "<br><p style='color: #888; font-size: 14px;'><strong>Hinweis:</strong> Falls etwas nicht funktioniert, kannst du das Backup wiederherstellen: <code>" . basename($backupFile) . "</code></p>";
?>