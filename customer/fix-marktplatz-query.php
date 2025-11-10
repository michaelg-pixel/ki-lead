<?php
/**
 * Quick Fix: Marktplatz Query korrigieren
 * Ändert die Query so, dass nur freebie_type = 'custom' geladen wird
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/customer/fix-marktplatz-query.php
 * Nach Ausführung LÖSCHEN!
 */

$file_path = __DIR__ . '/sections/marktplatz.php';

if (!file_exists($file_path)) {
    die("Datei nicht gefunden: $file_path");
}

$content = file_get_contents($file_path);

// Alte Query
$old_query = "WHERE customer_id = ? 
        AND freebie_type IN ('custom', 'template')
        AND copied_from_freebie_id IS NULL";

// Neue Query
$new_query = "WHERE customer_id = ? 
        AND freebie_type = 'custom'";

// Ersetzen
$new_content = str_replace($old_query, $new_query, $content);

if ($new_content === $content) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    echo "<h1 style='color: orange;'>⚠️ Keine Änderungen</h1>";
    echo "<p>Die Query wurde entweder bereits geändert oder konnte nicht gefunden werden.</p>";
    echo "<p><strong>Erwarteter Text:</strong></p>";
    echo "<pre>" . htmlspecialchars($old_query) . "</pre>";
    echo "</body></html>";
    exit;
}

// Backup erstellen
$backup_path = $file_path . '.backup.' . date('Y-m-d-H-i-s');
if (!copy($file_path, $backup_path)) {
    die("Fehler beim Erstellen des Backups!");
}

// Neue Datei schreiben
if (file_put_contents($file_path, $new_content) === false) {
    die("Fehler beim Schreiben der Datei!");
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>";
echo "body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }";
echo ".success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; }";
echo "</style></head><body>";

echo "<h1 style='color: #28a745;'>✅ Query erfolgreich korrigiert!</h1>";

echo "<div class='success'>";
echo "<h3>Änderungen:</h3>";
echo "<p><strong>ALT:</strong></p>";
echo "<pre>" . htmlspecialchars($old_query) . "</pre>";
echo "<p><strong>NEU:</strong></p>";
echo "<pre>" . htmlspecialchars($new_query) . "</pre>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ Backup erstellt:</h3>";
echo "<p>" . htmlspecialchars($backup_path) . "</p>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🎉 Fertig!</h3>";
echo "<p>Die Marktplatz-Seite zeigt jetzt nur noch deine eigenen Custom Freebies an.</p>";
echo "<p><a href='/customer/dashboard.php?page=marktplatz' style='color: #667eea; font-weight: bold;'>→ Zum Marktplatz</a></p>";
echo "</div>";

echo "<div class='warning'>";
echo "<h3>⚠️ WICHTIG:</h3>";
echo "<p>Bitte lösche diese Datei jetzt:</p>";
echo "<pre>customer/fix-marktplatz-query.php</pre>";
echo "</div>";

echo "</body></html>";
