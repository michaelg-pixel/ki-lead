<?php
/**
 * Cleanup Script - Löscht Setup-Dateien nach erfolgreicher Installation
 * Aufruf: https://app.mehr-infos-jetzt.de/setup/cleanup-setup-files.php
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Cleanup Setup Files</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;background:#f5f5f5}";
echo ".success{color:#22c55e;font-weight:bold}.error{color:#ef4444;font-weight:bold}";
echo ".box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1)}</style>";
echo "</head><body>";

echo "<h1>🧹 Setup-Dateien aufräumen</h1>";

$files_to_delete = [
    __DIR__ . '/setup-checklist-system.php',
    __DIR__ . '/check-db-structure.php',
    __DIR__ . '/cleanup-setup-files.php' // Diese Datei selbst
];

$deleted = 0;
$failed = 0;
$not_found = 0;

echo "<div class='box'>";
echo "<h2>📋 Dateien zum Löschen:</h2>";
echo "<ul>";

foreach ($files_to_delete as $file) {
    $filename = basename($file);
    
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<li class='success'>✅ Gelöscht: $filename</li>";
            $deleted++;
        } else {
            echo "<li class='error'>❌ Fehler beim Löschen: $filename</li>";
            $failed++;
        }
    } else {
        echo "<li style='color:#6b7280'>⏭️  Nicht gefunden: $filename</li>";
        $not_found++;
    }
}

echo "</ul>";
echo "</div>";

echo "<div class='box'>";
echo "<h2>📊 Zusammenfassung:</h2>";
echo "<ul>";
echo "<li>✅ Gelöscht: <strong>$deleted</strong></li>";
echo "<li>❌ Fehler: <strong>$failed</strong></li>";
echo "<li>⏭️  Nicht gefunden: <strong>$not_found</strong></li>";
echo "</ul>";

if ($failed === 0 && $deleted > 0) {
    echo "<p class='success' style='margin-top:20px'>🎉 Cleanup erfolgreich abgeschlossen!</p>";
    echo "<p>Die Setup-Dateien wurden sicher entfernt.</p>";
} elseif ($deleted === 0 && $not_found > 0) {
    echo "<p style='color:#6b7280;margin-top:20px'>ℹ️ Alle Dateien waren bereits gelöscht.</p>";
} else {
    echo "<p class='error' style='margin-top:20px'>⚠️ Einige Dateien konnten nicht gelöscht werden.</p>";
    echo "<p>Mögliche Gründe:</p>";
    echo "<ul>";
    echo "<li>Fehlende Datei-Berechtigungen</li>";
    echo "<li>Dateien werden gerade verwendet</li>";
    echo "</ul>";
    echo "<p>Bitte lösche die Dateien manuell per FTP oder SSH.</p>";
}

echo "</div>";

echo "<div class='box' style='background:#fef3c7'>";
echo "<h2>⚠️ Hinweis</h2>";
echo "<p>Nach dem Löschen dieser Dateien wird diese Seite nicht mehr erreichbar sein.</p>";
echo "<p>Du kannst dieses Fenster jetzt schließen.</p>";
echo "</div>";

echo "</body></html>";
?>