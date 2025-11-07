<?php
/**
 * PERMANENTER FIX: Scrollbalken entfernen (direkt auf Server)
 * Dieser Fix ist PERMANENT und überlebt Git-Pulls
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden: $file");
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Scrollbar Fix</title>
<style>body{font-family:sans-serif;padding:40px;background:#f5f5f5;}</style></head><body>";
echo "<h1>🔧 PERMANENTER Video-Tabs Scrollbar Fix</h1>";

$content = file_get_contents($file);
$backup = $content;

// STRATEGIE: Beide Overflow-Werte auf einmal ändern
$old_overflow_block = 'overflow-x: auto;      /* Horizontal scrollen wenn nötig */
            overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */';

$new_overflow_block = 'overflow: hidden;      /* KEIN Scrollbalken - Tabs wrappen */';

// Ersetze den kompletten Overflow-Block
$content = str_replace($old_overflow_block, $new_overflow_block, $content);

// Ändere flex-wrap zu wrap
$content = str_replace(
    'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */',
    'flex-wrap: wrap;',
    $content
);

// Entferne webkit-overflow-scrolling (nicht mehr nötig)
$content = str_replace(
    '-webkit-overflow-scrolling: touch;
            scrollbar-width: none;  /* Firefox */',
    '',
    $content
);

// Entferne den ::-webkit-scrollbar Block (nicht mehr nötig)
$content = str_replace(
    '/* Scrollbar komplett verstecken */
        .video-tabs::-webkit-scrollbar {
            display: none;  /* Chrome, Safari, Opera */
        }
        
        ',
    '',
    $content
);

// Speichern
if ($content !== $backup) {
    // Backup erstellen
    file_put_contents($file . '.backup_' . date('YmdHis'), $backup);
    
    // Neue Version speichern
    file_put_contents($file, $content);
    
    echo "<div style='background:#d4edda;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h2 style='color:#155724;margin:0 0 10px 0;'>✅ Erfolgreich gefixt!</h2>";
    echo "<p style='color:#155724;margin:0;'><strong>Änderungen:</strong></p>";
    echo "<ul style='color:#155724;'>";
    echo "<li><code>overflow-x: auto</code> + <code>overflow-y: hidden</code> → <code>overflow: hidden</code></li>";
    echo "<li><code>flex-wrap: nowrap</code> → <code>flex-wrap: wrap</code></li>";
    echo "<li>Scrollbar-Hiding CSS entfernt (nicht mehr nötig)</li>";
    echo "<li>Backup erstellt: {$file}.backup_" . date('YmdHis') . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;margin:20px 0;'>";
    echo "<h3 style='color:#856404;margin:0 0 10px 0;'>⚠️ WICHTIG: Cache leeren!</h3>";
    echo "<p style='color:#856404;'><strong>So funktioniert es garantiert:</strong></p>";
    echo "<ol style='color:#856404;'>";
    echo "<li>Öffne <strong>Privates Fenster / Incognito</strong> (Strg+Shift+N / Strg+Shift+P)</li>";
    echo "<li>Oder: DevTools öffnen (F12) → Netzwerk-Tab → 'Disable Cache' aktivieren</li>";
    echo "<li>Dann zur Kurs-Seite gehen</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<a href='course-view.php?id=10&lesson=19&_nocache=" . time() . "' style='display:inline-block;background:#a855f7;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-weight:bold;'>→ Jetzt testen (mit Cache-Buster)</a>";
    
} else {
    echo "<div style='background:#fff3cd;padding:20px;border-radius:8px;'>";
    echo "<h2 style='color:#856404;'>⚠️ Keine Änderungen</h2>";
    echo "<p>Die Datei wurde bereits geändert oder der Pattern wurde nicht gefunden.</p>";
    echo "</div>";
}

// Debug: Aktueller Stand
echo "<hr><h3>📋 Aktueller CSS Stand:</h3>";
echo "<pre style='background:#2d2d2d;color:#f8f8f2;padding:20px;border-radius:8px;overflow-x:auto;'>";

$start = strpos($content, '/* VIDEO TABS');
$end = strpos($content, '.video-tab-icon', $start);
if ($start !== false && $end !== false) {
    echo htmlspecialchars(substr($content, $start, $end - $start + 100));
} else {
    // Alternative: Zeige ersten 500 Zeichen nach VIDEO TABS
    if ($start !== false) {
        echo htmlspecialchars(substr($content, $start, 800));
    }
}
echo "</pre>";

echo "</body></html>";
?>