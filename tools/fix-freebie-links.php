<?php
/**
 * AUTO-FIX Script für Freebie Links
 * Dieses Script ersetzt automatisch die alten Links mit den neuen
 * 
 * Ausführen: https://app.mehr-infos-jetzt.de/tools/fix-freebie-links.php
 */

$file = __DIR__ . '/../customer/sections/freebies.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden: $file");
}

// Backup erstellen
$backup = $file . '.backup-' . date('Y-m-d-His');
if (!copy($file, $backup)) {
    die("❌ Konnte kein Backup erstellen");
}

echo "✅ Backup erstellt: $backup<br><br>";

// Datei lesen
$content = file_get_contents($file);
$original_content = $content;

// Alle Links ersetzen
$replacements = [
    '/customer/custom-freebie-editor-tabs.php' => '/public/customer/edit-freebie.php',
];

$count = 0;
foreach ($replacements as $old => $new) {
    $replaced = substr_count($content, $old);
    $content = str_replace($old, $new, $content);
    $count += $replaced;
    echo "🔄 Ersetzt: <code>$old</code> → <code>$new</code> ($replaced Vorkommen)<br>";
}

if ($count === 0) {
    echo "<br>ℹ️ Keine Änderungen nötig - Links sind bereits korrekt!<br>";
    unlink($backup);
    exit;
}

// Datei speichern
if (file_put_contents($file, $content) === false) {
    die("<br>❌ Fehler beim Speichern der Datei");
}

echo "<br>✅ Datei erfolgreich aktualisiert!<br>";
echo "📊 Insgesamt $count Link(s) ersetzt<br><br>";

// Änderungen anzeigen
echo "<h3>📝 Änderungen:</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; border-radius: 8px; max-height: 400px; overflow-y: auto;'>";

$original_lines = explode("\n", $original_content);
$new_lines = explode("\n", $content);

for ($i = 0; $i < count($original_lines); $i++) {
    if ($original_lines[$i] !== $new_lines[$i]) {
        echo "Zeile " . ($i + 1) . ":\n";
        echo "- " . htmlspecialchars($original_lines[$i]) . "\n";
        echo "+ " . htmlspecialchars($new_lines[$i]) . "\n\n";
    }
}

echo "</pre>";

echo "<br><h3>✅ Fix abgeschlossen!</h3>";
echo "<p>Die Freebie-Links wurden erfolgreich aktualisiert.</p>";
echo "<p><a href='/customer/dashboard.php?page=freebies'>Zurück zu Freebies</a></p>";
?>