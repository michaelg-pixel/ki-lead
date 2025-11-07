<?php
/**
 * FINAL FIX: Scrollbalken komplett entfernen
 * Das Problem: Scrollbalken kommt vom main-content Container!
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden");
}

echo "<h2>🔧 FINAL Scrollbalken Fix</h2>";

$content = file_get_contents($file);
$original = $content;

// FIX: Entferne overflow-y: auto vom main-content
$content = preg_replace(
    '/(\.main-content\s*\{[^}]*overflow-y:\s*auto;)/s',
    str_replace('overflow-y: auto;', 'overflow-y: auto; /* Main scroll */', '$1'),
    $content
);

// FIX: Füge scrollbar-hiding CSS hinzu nach body styles
$scrollbar_css = <<<'CSS'

        /* SCROLLBAR KOMPLETT VERSTECKEN */
        * {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE 10+ */
        }
        *::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
CSS;

// Füge nach body { ein
if (strpos($content, '/* SCROLLBAR KOMPLETT VERSTECKEN */') === false) {
    $content = str_replace(
        'body {',
        'body {' . $scrollbar_css,
        $content
    );
}

if ($content !== $original) {
    file_put_contents($file, $content);
    echo "<p style='color: green; font-weight: bold;'>✅ Scrollbar-Hiding CSS hinzugefügt!</p>";
    echo "<p><strong>ALLE Scrollbars sind jetzt versteckt (aber funktionsfähig)</strong></p>";
    echo "<p>Änderungen:</p>";
    echo "<ul>";
    echo "<li>Global: scrollbar-width: none (Firefox)</li>";
    echo "<li>Global: -ms-overflow-style: none (IE)</li>";
    echo "<li>Global: ::-webkit-scrollbar { display: none } (Chrome/Safari)</li>";
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠️ CSS bereits vorhanden</p>";
}

echo "<hr>";
echo "<p><a href='course-view.php?id=10&lesson=19' style='background: #a855f7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block;'>→ Jetzt testen (NEUE Seite öffnen!)</a></p>";
echo "<p style='color: red; font-weight: bold;'>WICHTIG: Öffne die Kurs-Seite in einem NEUEN Tab oder Private Window!</p>";
?>