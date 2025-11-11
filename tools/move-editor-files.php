<?php
/**
 * Verschiebt Editor-Dateien von /public/customer/ nach /customer/
 * Korrigiert automatisch alle Pfade
 */

echo "<h2>📦 Editor Dateien verschieben</h2>";
echo "<hr><br>";

$files_to_move = [
    'edit-freebie.php',
    'edit-course.php'
];

$moved = 0;
$errors = 0;

foreach ($files_to_move as $filename) {
    $source = __DIR__ . '/../public/customer/' . $filename;
    $destination = __DIR__ . '/../customer/' . $filename;
    $backup = __DIR__ . '/../customer/' . $filename . '.backup-' . date('Y-m-d-His');
    
    echo "<h3>📄 Verschiebe: $filename</h3>";
    
    // 1. Prüfe ob Quelle existiert
    if (!file_exists($source)) {
        echo "⚠️ Quelle existiert nicht: <code>/public/customer/$filename</code><br>";
        continue;
    }
    
    echo "✅ Quelle gefunden: " . filesize($source) . " bytes<br>";
    
    // 2. Backup erstellen falls Ziel schon existiert
    if (file_exists($destination)) {
        echo "ℹ️ Ziel existiert bereits, erstelle Backup...<br>";
        if (!copy($destination, $backup)) {
            echo "❌ Backup fehlgeschlagen!<br>";
            $errors++;
            continue;
        }
        echo "✅ Backup: " . basename($backup) . "<br>";
    }
    
    // 3. Datei kopieren
    if (!copy($source, $destination)) {
        echo "❌ Kopieren fehlgeschlagen!<br>";
        $errors++;
        continue;
    }
    
    echo "✅ Datei kopiert nach: <code>/customer/$filename</code><br>";
    
    // 4. Original löschen
    if (unlink($source)) {
        echo "✅ Original in /public/customer/ gelöscht<br>";
    } else {
        echo "⚠️ Konnte Original nicht löschen<br>";
    }
    
    $moved++;
    echo "<br>";
}

echo "<hr><h3>📊 Zusammenfassung:</h3>";
echo "<p>✅ Erfolgreich verschoben: <strong>$moved</strong> Datei(en)</p>";
if ($errors > 0) {
    echo "<p>❌ Fehler: <strong>$errors</strong></p>";
}

echo "<br><h3>🔧 Jetzt Links in freebies.php korrigieren</h3>";
echo "<p>Die Links müssen jetzt von <code>/public/customer/</code> zu <code>/customer/</code> geändert werden.</p>";

// Automatisch Links korrigieren
$freebiesFile = __DIR__ . '/../customer/sections/freebies-simple.php';

if (file_exists($freebiesFile)) {
    $content = file_get_contents($freebiesFile);
    $originalContent = $content;
    
    // Ersetze Pfade
    $content = str_replace('/public/customer/edit-freebie.php', '/customer/edit-freebie.php', $content);
    $content = str_replace('/public/customer/edit-course.php', '/customer/edit-course.php', $content);
    
    if ($content !== $originalContent) {
        // Backup erstellen
        $backupFile = $freebiesFile . '.backup-' . date('Y-m-d-His');
        copy($freebiesFile, $backupFile);
        
        // Speichern
        file_put_contents($freebiesFile, $content);
        
        echo "<p>✅ Links in freebies-simple.php automatisch korrigiert!</p>";
        echo "<p>Backup: " . basename($backupFile) . "</p>";
    } else {
        echo "<p>ℹ️ Keine Änderungen nötig in freebies-simple.php</p>";
    }
}

echo "<br><hr>";
echo "<h3>✅ Setup abgeschlossen!</h3>";
echo "<p>Die Editor-Dateien sind jetzt hier:</p>";
echo "<ul>";
echo "<li>✅ <code>/customer/edit-freebie.php</code></li>";
echo "<li>✅ <code>/customer/edit-course.php</code></li>";
echo "</ul>";

echo "<br><h3>🎯 Jetzt testen:</h3>";
echo "<p><a href='/customer/edit-freebie.php?id=7' target='_blank' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin-right: 10px;'>Test: Freebie Editor</a>";
echo "<a href='/customer/edit-course.php?id=8' target='_blank' style='display: inline-block; padding: 12px 24px; background: #fb923c; color: white; text-decoration: none; border-radius: 8px;'>Test: Kurs Editor</a></p>";

echo "<br><p style='color: #888;'>Dann zurück zu: <a href='/customer/dashboard.php?page=freebies'>Freebies</a></p>";
?>