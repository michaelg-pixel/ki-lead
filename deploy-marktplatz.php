<?php
// Deployment Script für marktplatz.php
// Aufruf: https://app.mehr-infos-jetzt.de/deploy-marktplatz.php

$github_url = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/main/customer/marktplatz.php';
$target_file = __DIR__ . '/customer/marktplatz.php';
$backup_file = __DIR__ . '/customer/marktplatz.php.backup.' . date('YmdHis');

// Backup erstellen
if (file_exists($target_file)) {
    copy($target_file, $backup_file);
    echo "✅ Backup erstellt: " . basename($backup_file) . "<br>";
}

// Neue Datei von GitHub laden
$content = file_get_contents($github_url);

if ($content === false) {
    die("❌ Fehler: Konnte Datei nicht von GitHub laden<br>URL: " . $github_url);
}

// Datei speichern
if (file_put_contents($target_file, $content) !== false) {
    echo "✅ marktplatz.php erfolgreich aktualisiert!<br>";
    echo "✅ Dateigröße: " . strlen($content) . " Bytes<br>";
    echo "<br>";
    echo "✅ Neue Features:<br>";
    echo "   - NUR Custom Freebies werden angezeigt<br>";
    echo "   - Thank You Link zum Kopieren<br>";
    echo "   - Impressum & Datenschutz im Footer<br>";
    echo "<br>";
    echo "👉 <a href='/customer/dashboard.php?page=marktplatz'>Zum Marktplatz</a>";
} else {
    die("❌ Fehler: Konnte Datei nicht speichern");
}
