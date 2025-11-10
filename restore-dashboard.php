<?php
// Dashboard Wiederherstellung
// Aufruf: https://app.mehr-infos-jetzt.de/restore-dashboard.php

echo "<h2>🔧 Dashboard Wiederherstellung</h2>";

$dashboard_file = __DIR__ . '/customer/dashboard.php';
$backup_pattern = __DIR__ . '/customer/dashboard.php.backup.*';

// 1. Suche nach Backups
echo "<h3>1. Suche nach Backups</h3>";
$backups = glob($backup_pattern);

if (empty($backups)) {
    echo "❌ Keine Backups gefunden<br>";
} else {
    rsort($backups); // Neueste zuerst
    echo "✅ " . count($backups) . " Backup(s) gefunden:<br>";
    
    foreach ($backups as $backup) {
        $size = filesize($backup);
        $date = basename($backup);
        echo "- " . basename($backup) . " (" . number_format($size) . " Bytes)<br>";
    }
    
    // Neuestes Backup wiederherstellen
    $latest_backup = $backups[0];
    echo "<br><strong>Neuestes Backup:</strong> " . basename($latest_backup) . "<br>";
    
    if ($size < 1000) {
        echo "⚠️ Backup ist sehr klein (" . $size . " Bytes) - wahrscheinlich auch beschädigt<br>";
    } else {
        echo "<form method='POST'>";
        echo "<input type='hidden' name='restore_from' value='" . htmlspecialchars($latest_backup) . "'>";
        echo "<button type='submit' style='padding: 12px 24px; background: #10b981; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>✅ Backup wiederherstellen</button>";
        echo "</form>";
    }
}

// 2. POST Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_from'])) {
    $backup_file = $_POST['restore_from'];
    
    if (!file_exists($backup_file)) {
        die("<br>❌ Backup-Datei nicht gefunden!");
    }
    
    // Aktuelles File als "kaputt" sichern
    $broken_file = $dashboard_file . '.broken.' . date('YmdHis');
    copy($dashboard_file, $broken_file);
    echo "<br>✅ Kaputte Datei gesichert: " . basename($broken_file) . "<br>";
    
    // Backup wiederherstellen
    if (copy($backup_file, $dashboard_file)) {
        echo "✅ Dashboard erfolgreich wiederhergestellt!<br>";
        echo "✅ Neue Größe: " . filesize($dashboard_file) . " Bytes<br>";
        
        // Syntax Check
        exec("php -l " . escapeshellarg($dashboard_file) . " 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "<br>✅ Syntax Check: OK<br>";
            echo "<br><strong>🎉 Dashboard wurde erfolgreich wiederhergestellt!</strong><br>";
            echo "<a href='/customer/dashboard.php' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Zum Dashboard</a>";
        } else {
            echo "<br>❌ Syntax Check fehlgeschlagen:<br>";
            echo "<pre style='background: #fee; padding: 10px;'>" . implode("\n", $output) . "</pre>";
        }
    } else {
        echo "❌ Fehler beim Kopieren der Backup-Datei<br>";
    }
}

// 3. Manuelle Menü-Korrektur anbieten
echo "<hr>";
echo "<h3>2. Manuelle Korrektur</h3>";
echo "<p>Falls kein Backup funktioniert, kannst du das Dashboard manuell korrigieren:</p>";
echo "<ol>";
echo "<li>Die dashboard.php wurde beim letzten Update beschädigt</li>";
echo "<li>Es fehlt der Anfang der Datei (PHP-Code, HTML-Struktur)</li>";
echo "<li>Nur die Menü-Einträge für Marktplatz müssen hinzugefügt werden</li>";
echo "</ol>";

echo "<form method='POST' action='fix-dashboard-menu.php'>";
echo "<button type='submit' style='padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>🔧 Menü automatisch korrigieren</button>";
echo "</form>";
?>
