<?php
/**
 * CACHE-BUSTER VERSION: course-view.php mit Versions-Parameter
 * Diese Version lädt CSS garantiert neu!
 */

$file = __DIR__ . '/course-view.php';

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden");
}

$content = file_get_contents($file);

// CSS Fixes anwenden
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

// CRITICAL: Füge Versions-Parameter zum <style> Tag hinzu
// Dadurch wird CSS garantiert neu geladen!
$version = time();
$content = str_replace(
    '<style>',
    "<style data-version=\"v{$version}\">",
    $content
);

// Füge auch einen Meta-Tag ein für Cache Control
$cache_meta = "\n    <meta http-equiv=\"Cache-Control\" content=\"no-cache, no-store, must-revalidate, max-age=0\">\n    <meta http-equiv=\"Pragma\" content=\"no-cache\">\n    <meta http-equiv=\"Expires\" content=\"0\">";

$content = str_replace(
    '<meta http-equiv="Cache-Control"',
    $cache_meta . "\n    <meta http-equiv=\"Cache-Control-Duplicate\"",
    $content
);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Cache-Buster Download</title>
<style>
body{font-family:sans-serif;background:#0a0a16;color:#e5e7eb;padding:40px;}
.container{max-width:800px;margin:0 auto;background:#1a1532;padding:40px;border-radius:16px;border:2px solid rgba(168,85,247,0.3);}
h1{color:#a855f7;margin-bottom:20px;}
.success{background:#d4edda;color:#155724;padding:20px;border-radius:8px;margin:20px 0;}
.critical{background:#fff3cd;color:#856404;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #ffc107;}
.btn{display:inline-block;background:#a855f7;color:white;padding:16px 32px;text-decoration:none;border-radius:8px;font-weight:700;margin:20px 10px 20px 0;}
.btn:hover{background:#8b40d1;}
.code{background:#2d2d2d;color:#f8f8f2;padding:15px;border-radius:8px;font-family:monospace;font-size:13px;overflow-x:auto;margin:10px 0;}
</style>
</head><body><div class='container'>";

echo "<h1>🚀 CACHE-BUSTER VERSION</h1>";

echo "<div class='success'>";
echo "<h3>✅ Diese Version hat einen eingebauten Cache-Buster!</h3>";
echo "<p><strong>Was das bedeutet:</strong> Der Browser MUSS die CSS neu laden.</p>";
echo "<p><strong>Versions-ID:</strong> v{$version}</p>";
echo "</div>";

echo "<div class='critical'>";
echo "<h3>⚡ WICHTIGE ANWEISUNGEN:</h3>";
echo "<ol>";
echo "<li><strong>Laden Sie diese Datei herunter</strong></li>";
echo "<li><strong>Laden Sie per FTP hoch nach:</strong><div class='code'>/app.mehr-infos-jetzt.de/customer/course-view.php</div></li>";
echo "<li><strong>DANN: Schließen Sie Ihren Browser KOMPLETT</strong></li>";
echo "<li><strong>Öffnen Sie einen NEUEN Browser</strong></li>";
echo "<li><strong>Gehen Sie zur Kurs-Seite</strong></li>";
echo "</ol>";
echo "</div>";

echo "<a href='?download=1' class='btn'>📥 Download mit Cache-Buster</a>";

echo "<div style='margin-top:30px;padding:20px;background:rgba(168,85,247,0.1);border-radius:8px;'>";
echo "<h3 style='color:#a855f7;'>🔍 Was wurde gefixt:</h3>";
echo "<div class='code'>";
echo "// VORHER:\n";
echo "overflow-x: auto;\n";
echo "overflow-y: hidden;\n";
echo "flex-wrap: nowrap;\n";
echo "scrollbar-width: none;\n\n";
echo "// NACHHER:\n";
echo "overflow: hidden;  ← Kein Scrollbalken!\n";
echo "flex-wrap: wrap;   ← Tabs wrappen automatisch\n";
echo "</div>";
echo "</div>";

echo "<div style='margin-top:30px;padding:20px;background:#2d2d2d;border-radius:8px;'>";
echo "<h3 style='color:#4ade80;'>✨ Cache-Buster Features:</h3>";
echo "<ul style='color:#9ca3af;'>";
echo "<li>🔸 Versions-Attribut im &lt;style&gt; Tag: <code>data-version=\"v{$version}\"</code></li>";
echo "<li>🔸 Extra Meta-Tags für Cache-Control</li>";
echo "<li>🔸 Timestamp-basierte Versionierung</li>";
echo "</ul>";
echo "</div>";

echo "</div></body></html>";

if (isset($_GET['download']) && $_GET['download'] == '1') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="course-view.php"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $content;
    exit;
}
?>