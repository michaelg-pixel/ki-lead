<?php
/**
 * DIREKTER DOWNLOAD - Kein HTML, nur die Datei!
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("ERROR: Datei nicht gefunden!");
}

// Datei laden
$content = file_get_contents($file);

// Alle Fixes anwenden
$content = str_replace(
    'overflow-x: auto;      /* Horizontal scrollen wenn nötig */',
    'overflow: hidden;      /* KEIN Scrollbalken */',
    $content
);

$content = str_replace(
    "            overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */\n",
    '',
    $content
);

$content = str_replace(
    'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */',
    'flex-wrap: wrap;',
    $content
);

$content = str_replace(
    "            -webkit-overflow-scrolling: touch;\n",
    '',
    $content
);

$content = str_replace(
    "            scrollbar-width: none;  /* Firefox */\n",
    '',
    $content
);

$content = str_replace(
    "            min-height: 80px;\n",
    '',
    $content
);

$content = preg_replace(
    '/\s*\/\* Scrollbar komplett verstecken \*\/\s*\.video-tabs::-webkit-scrollbar\s*\{[^}]+\}\s*\n\s*\n/s',
    "\n",
    $content
);

// SOFORT Download starten - kein HTML!
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="course-view.php"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Datei ausgeben
echo $content;
exit;
?>