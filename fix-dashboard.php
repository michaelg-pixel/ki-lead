<?php
/**
 * Quick Fix Script: Repariert customer/dashboard.php
 * Rufen Sie diese Datei einmalig auf: https://app.mehr-infos-jetzt.de/fix-dashboard.php
 */

$source = __DIR__ . '/customer/dashboard-fixed.php';
$target = __DIR__ . '/customer/dashboard.php';
$backup = __DIR__ . '/customer/dashboard-backup-' . date('YmdHis') . '.php';

// Backup erstellen
if (file_exists($target)) {
    copy($target, $backup);
    echo "✅ Backup erstellt: " . basename($backup) . "<br>";
}

// Kopieren
if (file_exists($source)) {
    copy($source, $target);
    echo "✅ Dashboard repariert!<br>";
    echo "<br>Sie können jetzt: <a href='/customer/dashboard.php'>Zum Dashboard</a>";
} else {
    echo "❌ Fehler: dashboard-fixed.php nicht gefunden";
}
?>