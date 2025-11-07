<?php
/**
 * DOWNLOAD: Fertige course-view.php mit gefixter CSS
 * Aufruf: https://app.mehr-infos-jetzt.de/customer/download-fixed-course-view.php
 */

$source_file = __DIR__ . '/course-view.php';

if (!file_exists($source_file)) {
    die("❌ Source-Datei nicht gefunden!");
}

// Datei laden
$content = file_get_contents($source_file);

// FIX 1: overflow-x → overflow: hidden
$content = str_replace(
    'overflow-x: auto;      /* Horizontal scrollen wenn nötig */',
    'overflow: hidden;      /* KEIN Scrollbalken */',
    $content
);

// FIX 2: overflow-y entfernen
$content = str_replace(
    "            overflow-y: hidden;     /* KEIN vertikaler Scrollbalken */\n",
    '',
    $content
);

// FIX 3: flex-wrap: nowrap → wrap
$content = str_replace(
    'flex-wrap: nowrap;      /* EINE Reihe - horizontal scrollen statt wrappen */',
    'flex-wrap: wrap;',
    $content
);

// FIX 4: Entferne -webkit-overflow-scrolling
$content = str_replace(
    "            -webkit-overflow-scrolling: touch;\n",
    '',
    $content
);

// FIX 5: Entferne scrollbar-width
$content = str_replace(
    "            scrollbar-width: none;  /* Firefox */\n",
    '',
    $content
);

// FIX 6: Entferne min-height
$content = str_replace(
    "            min-height: 80px;\n",
    '',
    $content
);

// FIX 7: Entferne ::-webkit-scrollbar Block
$content = preg_replace(
    '/\s*\/\* Scrollbar komplett verstecken \*\/\s*\.video-tabs::-webkit-scrollbar\s*\{[^}]+\}\s*\n\s*\n/s',
    "\n",
    $content
);

// HTML Output mit Download-Button
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download: Gefixt course-view.php</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 32px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .info-box p {
            color: #555;
            line-height: 1.6;
        }
        .changes {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .changes h3 {
            color: #667eea;
            margin-bottom: 15px;
        }
        .changes ul {
            list-style: none;
            padding: 0;
        }
        .changes li {
            padding: 8px 0;
            color: #555;
            border-bottom: 1px solid #e0e0e0;
        }
        .changes li:last-child {
            border-bottom: none;
        }
        .btn-download {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            margin: 20px 0;
            transition: transform 0.2s;
        }
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        .path-box {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin: 15px 0;
            overflow-x: auto;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .warning strong {
            color: #ff9800;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📥 Download: Gefixt course-view.php</h1>
        
        <div class="info-box">
            <h3>✅ Datei bereit zum Download!</h3>
            <p>Diese Datei enthält die korrigierte CSS ohne Scrollbalken-Problem.</p>
        </div>
        
        <div class="changes">
            <h3>🔧 Was wurde geändert:</h3>
            <ul>
                <li>✓ <strong>overflow-x: auto</strong> → <strong>overflow: hidden</strong></li>
                <li>✓ <strong>overflow-y: hidden</strong> → entfernt</li>
                <li>✓ <strong>flex-wrap: nowrap</strong> → <strong>flex-wrap: wrap</strong></li>
                <li>✓ <strong>-webkit-overflow-scrolling</strong> → entfernt</li>
                <li>✓ <strong>scrollbar-width</strong> → entfernt</li>
                <li>✓ <strong>::-webkit-scrollbar</strong> Block → entfernt</li>
                <li>✓ <strong>min-height</strong> → entfernt</li>
            </ul>
        </div>
        
        <a href="?download=1" class="btn-download">📥 course-view.php herunterladen</a>
        
        <div class="info-box">
            <h3>📤 FTP Upload-Pfad:</h3>
            <div class="path-box">/app.mehr-infos-jetzt.de/customer/course-view.php</div>
            <p style="margin-top: 10px;">Oder vollständiger Pfad:</p>
            <div class="path-box">/home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/customer/course-view.php</div>
        </div>
        
        <div class="warning">
            <strong>⚠️ WICHTIG: Backup erstellen!</strong><br>
            Laden Sie zuerst die aktuelle course-view.php herunter und benennen Sie sie um in <code>course-view.php.backup</code>
        </div>
        
        <div class="info-box">
            <h3>📋 Upload-Anleitung:</h3>
            <ol style="padding-left: 20px; color: #555;">
                <li style="margin: 8px 0;">Laden Sie die aktuelle Datei als Backup herunter</li>
                <li style="margin: 8px 0;">Klicken Sie oben auf "Download"</li>
                <li style="margin: 8px 0;">Verbinden Sie sich per FTP mit Ihrem Server</li>
                <li style="margin: 8px 0;">Navigieren Sie zu: <code>/app.mehr-infos-jetzt.de/customer/</code></li>
                <li style="margin: 8px 0;">Laden Sie die neue course-view.php hoch (überschreiben)</li>
                <li style="margin: 8px 0;">Öffnen Sie die Kurs-Seite im <strong>privaten Fenster</strong> (Strg+Shift+N)</li>
            </ol>
        </div>
    </div>
</body>
</html>
<?php
// Download-Funktionalität
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