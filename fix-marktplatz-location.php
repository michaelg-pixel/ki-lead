<?php
// Script zum Verschieben der marktplatz.php ins sections Verzeichnis
// Aufruf: https://app.mehr-infos-jetzt.de/fix-marktplatz-location.php

echo "<h2>🔧 Marktplatz Dateien Fix</h2>";

$source = __DIR__ . '/customer/marktplatz.php';
$target = __DIR__ . '/customer/sections/marktplatz.php';
$sections_dir = __DIR__ . '/customer/sections';

// 1. Prüfe Quell-Datei
echo "<h3>1. Quell-Datei</h3>";
if (file_exists($source)) {
    echo "✅ Gefunden: /customer/marktplatz.php (" . filesize($source) . " Bytes)<br>";
} else {
    echo "❌ Nicht gefunden: /customer/marktplatz.php<br>";
}

// 2. Prüfe Ziel-Datei
echo "<h3>2. Ziel-Datei</h3>";
if (file_exists($target)) {
    echo "✅ Existiert bereits: /customer/sections/marktplatz.php (" . filesize($target) . " Bytes)<br>";
} else {
    echo "❌ Existiert nicht: /customer/sections/marktplatz.php<br>";
}

// 3. Sections Verzeichnis prüfen
echo "<h3>3. Sections Verzeichnis</h3>";
if (is_dir($sections_dir)) {
    echo "✅ Verzeichnis existiert: /customer/sections<br>";
} else {
    echo "❌ Verzeichnis fehlt - wird erstellt...<br>";
    mkdir($sections_dir, 0755, true);
    echo "✅ Verzeichnis erstellt<br>";
}

// 4. Verschieben
echo "<h3>4. Datei verschieben</h3>";

if (file_exists($source)) {
    if (copy($source, $target)) {
        echo "✅ Datei kopiert nach /customer/sections/marktplatz.php<br>";
        
        // Lösche Original
        unlink($source);
        echo "✅ Original gelöscht: /customer/marktplatz.php<br>";
        
        // Syntax Check
        exec("php -l " . escapeshellarg($target) . " 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "<br>✅ <strong>Syntax Check: OK</strong><br>";
        } else {
            echo "<br>❌ Syntax Check fehlgeschlagen:<br>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
        }
        
    } else {
        echo "❌ Fehler beim Kopieren<br>";
    }
} else {
    echo "⚠️ Quell-Datei nicht gefunden - lade von GitHub...<br>";
    
    // Von GitHub laden
    $github_url = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/main/customer/marktplatz.php';
    $content = file_get_contents($github_url);
    
    if ($content !== false) {
        if (file_put_contents($target, $content)) {
            echo "✅ Datei von GitHub geladen und gespeichert<br>";
            echo "✅ Größe: " . strlen($content) . " Bytes<br>";
        } else {
            echo "❌ Fehler beim Speichern<br>";
        }
    } else {
        echo "❌ Fehler beim Laden von GitHub<br>";
    }
}

// 5. Finale Prüfung
echo "<h3>5. Finale Prüfung</h3>";
if (file_exists($target)) {
    echo "✅ /customer/sections/marktplatz.php existiert (" . filesize($target) . " Bytes)<br>";
    echo "<br><strong>🎉 Erfolgreich!</strong><br>";
    echo "<a href='/customer/dashboard.php?page=marktplatz' style='display: inline-block; margin-top: 20px; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Zu Meine Marktplatz-Angebote</a>";
} else {
    echo "❌ Datei konnte nicht erstellt werden<br>";
}

// 6. Auch marktplatz-browse.php prüfen
echo "<h3>6. Marktplatz Browse Datei</h3>";
$browse_file = __DIR__ . '/customer/sections/marktplatz-browse.php';
if (file_exists($browse_file)) {
    echo "✅ /customer/sections/marktplatz-browse.php existiert (" . filesize($browse_file) . " Bytes)<br>";
} else {
    echo "❌ /customer/sections/marktplatz-browse.php fehlt<br>";
    echo "Lade von GitHub...<br>";
    
    $github_url = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/main/customer/sections/marktplatz-browse.php';
    $content = file_get_contents($github_url);
    
    if ($content !== false && file_put_contents($browse_file, $content)) {
        echo "✅ marktplatz-browse.php von GitHub geladen<br>";
    }
}
?>
