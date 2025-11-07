<?php
/**
 * ANTI-CACHE VERSION: Fügt Timestamp direkt ins HTML ein
 * Dadurch wird CSS GARANTIERT neu geladen
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("ERROR: Datei nicht gefunden");
}

$content = file_get_contents($file);

// CSS Fixes
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

// CRITICAL: Füge einen eindeutigen Kommentar mit Timestamp am Anfang des <style> ein
$timestamp = time();
$cache_comment = "\n        /* CACHE-BUSTER: VERSION-{$timestamp} - LOADED */";

$content = str_replace(
    '    <style>',
    "    <style>{$cache_comment}",
    $content
);

// CRITICAL: Ändere auch den Cache-Bust Parameter
$content = str_replace(
    '$cache_bust = time();',
    '$cache_bust = ' . $timestamp . ';',
    $content
);

// Download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="course-view-FINAL.php"');
header('Content-Length: ' . strlen($content));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $content;
exit;
?>