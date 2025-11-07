<?php
/**
 * QUICK FIX: Entferne Scrollbalken von Video-Tabs
 * Aufruf: https://app.mehr-infos-jetzt.de/customer/fix-video-tabs-scrollbar.php
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden: $file");
}

echo "<h2>🔧 Video-Tabs Scrollbalken Fix</h2>";

$content = file_get_contents($file);
$original_content = $content;

// FIX 1: Ersetze overflow-x: auto mit overflow: hidden
$content = str_replace(
    'overflow-x: auto;      /* Horizontal scrollen wenn nötig */',
    'overflow: hidden;      /* KEIN Scrollbalken */',
    $content
);

// FIX 2: Entferne overflow-y: hidden (nicht mehr nötig)
$content = str_replace(
    'overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */',
    '',
    $content
);

// FIX 3: Ändere flex-wrap zurück auf wrap
$content = str_replace(
    'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */',
    'flex-wrap: wrap;',
    $content
);

// FIX 4: Entferne -webkit-overflow-scrolling
$content = str_replace(
    '-webkit-overflow-scrolling: touch;',
    '',
    $content
);

// FIX 5: Entferne scrollbar-width
$content = str_replace(
    'scrollbar-width: none;  /* Firefox */',
    '',
    $content
);

// FIX 6: Entferne ::-webkit-scrollbar Block
$content = preg_replace(
    '/\/\* Scrollbar komplett verstecken \*\/\s*\.video-tabs::-webkit-scrollbar\s*\{[^}]+\}/s',
    '',
    $content
);

if ($content !== $original_content) {
    file_put_contents($file, $content);
    echo "<p style='color: green; font-weight: bold;'>✅ Datei erfolgreich aktualisiert!</p>";
    echo "<p>Änderungen:</p>";
    echo "<ul>";
    echo "<li>overflow-x: auto → overflow: hidden</li>";
    echo "<li>overflow-y: hidden → entfernt</li>";
    echo "<li>flex-wrap: nowrap → flex-wrap: wrap</li>";
    echo "<li>Scrollbar-Styling entfernt</li>";
    echo "</ul>";
    echo "<p><strong>Bitte jetzt die Seite neu laden (Strg+Shift+R)</strong></p>";
    echo "<p><a href='course-view.php?id=10&lesson=19'>→ Zur Kurs-Ansicht</a></p>";
} else {
    echo "<p style='color: orange;'>⚠️ Keine Änderungen vorgenommen (bereits gefixt oder Pattern nicht gefunden)</p>";
}

// Debug: Zeige den video-tabs CSS Block
echo "<hr><h3>Debug: Aktueller CSS Code</h3>";
echo "<pre style='background: #f5f5f5; padding: 15px; overflow-x: auto;'>";
$start = strpos($content, '/* VIDEO TABS');
$end = strpos($content, '.video-tab-icon', $start);
if ($start !== false && $end !== false) {
    echo htmlspecialchars(substr($content, $start, $end - $start));
} else {
    echo "Pattern nicht gefunden";
}
echo "</pre>";

// Lösche dieses Script nach Ausführung (optional)
// unlink(__FILE__);
?>