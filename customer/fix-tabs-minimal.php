<?php
/**
 * MINIMAL FIX: Numbered Pills - Nur CSS ändern
 * Einfachster Ansatz: Nur overflow und wrapping fixen
 */

$file = __DIR__ . '/course-view.php';
echo "<pre style='background:#0a0a16;color:#e5e7eb;padding:20px;font-family:monospace;'>";

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden: $file\n");
}

$content = file_get_contents($file);
$changed = false;

// FIX 1: overflow-x: auto -> overflow: hidden
if (strpos($content, 'overflow-x: auto;      /* Horizontal scrollen wenn nötig */') !== false) {
    $content = str_replace(
        'overflow-x: auto;      /* Horizontal scrollen wenn nötig */',
        'overflow: hidden;      /* KEIN Scrollbalken */',
        $content
    );
    echo "✓ FIX 1: overflow-x: auto → overflow: hidden\n";
    $changed = true;
}

// FIX 2: overflow-y: hidden -> löschen (nicht mehr nötig)
if (strpos($content, 'overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */') !== false) {
    $content = str_replace(
        "            overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */\n",
        '',
        $content
    );
    echo "✓ FIX 2: overflow-y: hidden entfernt\n";
    $changed = true;
}

// FIX 3: flex-wrap: nowrap -> flex-wrap: wrap
if (strpos($content, 'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */') !== false) {
    $content = str_replace(
        'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */',
        'flex-wrap: wrap;',
        $content
    );
    echo "✓ FIX 3: flex-wrap: nowrap → flex-wrap: wrap\n";
    $changed = true;
}

// FIX 4: -webkit-overflow-scrolling entfernen
if (strpos($content, '-webkit-overflow-scrolling: touch;') !== false) {
    $content = str_replace(
        "            -webkit-overflow-scrolling: touch;\n",
        '',
        $content
    );
    echo "✓ FIX 4: -webkit-overflow-scrolling entfernt\n";
    $changed = true;
}

// FIX 5: scrollbar-width entfernen
if (strpos($content, 'scrollbar-width: none;  /* Firefox */') !== false) {
    $content = str_replace(
        "            scrollbar-width: none;  /* Firefox */\n",
        '',
        $content
    );
    echo "✓ FIX 5: scrollbar-width entfernt\n";
    $changed = true;
}

// FIX 6: ::-webkit-scrollbar Block entfernen
if (strpos($content, '/* Scrollbar komplett verstecken */') !== false) {
    $content = preg_replace(
        '/\s*\/\* Scrollbar komplett verstecken \*\/\s*\.video-tabs::-webkit-scrollbar\s*\{[^}]+\}\s*\n\s*\n/s',
        "\n",
        $content
    );
    echo "✓ FIX 6: ::-webkit-scrollbar Block entfernt\n";
    $changed = true;
}

// FIX 7: min-height entfernen (nicht mehr nötig)
if (strpos($content, 'min-height: 80px;') !== false) {
    $content = str_replace(
        "            min-height: 80px;\n",
        '',
        $content
    );
    echo "✓ FIX 7: min-height entfernt\n";
    $changed = true;
}

if ($changed) {
    file_put_contents($file, $content);
    echo "\n🎉 ERFOLG! Datei aktualisiert.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "NÄCHSTER SCHRITT:\n";
    echo "1. Strg+Shift+R (Hard Refresh)\n";
    echo "2. Oder: Privates Fenster öffnen\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "<a href='course-view.php?id=10&lesson=19&_v=" . time() . "' style='display:inline-block;background:#a855f7;color:white;padding:12px 24px;text-decoration:none;border-radius:8px;font-weight:bold;'>→ Jetzt testen</a>\n";
} else {
    echo "\n⚠️  Keine Änderungen nötig - bereits gefixt!\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "DEBUG: Aktueller .video-tabs CSS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$start = strpos($content, '.video-tabs {');
if ($start !== false) {
    $end = strpos($content, '}', $start);
    echo htmlspecialchars(substr($content, $start, $end - $start + 1));
}

echo "\n\n</pre>";
?>