<?php
/**
 * Cache-Clear Script für PHP OpCache
 * Leert alle Server-Caches
 */

// Nur für Admins zugänglich machen
session_start();
require_once '../config/database.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache leeren</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a0a16;
            color: #e5e7eb;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #1a1532;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        h1 {
            color: #a855f7;
            margin-bottom: 30px;
        }
        .result {
            background: rgba(168, 85, 247, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid rgba(168, 85, 247, 0.3);
        }
        .success {
            background: rgba(74, 222, 128, 0.1);
            border-color: rgba(74, 222, 128, 0.3);
            color: #86efac;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
        }
        pre {
            background: #0f0f1e;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗑️ Cache leeren</h1>
        
        <?php if ($is_admin): ?>
            
            <h2>PHP OpCache Status:</h2>
            <div class="result">
                <?php
                if (function_exists('opcache_reset')):
                    $before = opcache_get_status();
                    opcache_reset();
                    $after = opcache_get_status();
                    echo '<div class="success">✅ OpCache erfolgreich geleert!</div>';
                    echo '<pre>Vor dem Leeren: ' . print_r($before, true) . '</pre>';
                    echo '<pre>Nach dem Leeren: ' . print_r($after, true) . '</pre>';
                else:
                    echo '<div class="error">⚠️ OpCache ist nicht verfügbar oder aktiviert</div>';
                endif;
                ?>
            </div>
            
            <h2>Session Cache:</h2>
            <div class="result success">
                <?php
                // Session-Variablen anzeigen
                echo '<pre>Session Daten: ' . print_r($_SESSION, true) . '</pre>';
                ?>
            </div>
            
            <h2>Browser Cache Headers:</h2>
            <div class="result">
                <p>✅ Die folgenden Headers wurden gesetzt:</p>
                <pre>
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
Pragma: no-cache
Expires: Thu, 01 Jan 1970 00:00:00 GMT
                </pre>
            </div>
            
            <h2>Was jetzt tun?</h2>
            <div class="result">
                <ol style="margin: 0; padding-left: 20px;">
                    <li>Drücken Sie <strong>Strg+F5</strong> (Windows) oder <strong>Cmd+Shift+R</strong> (Mac) auf der Vorschau-Seite</li>
                    <li>Oder öffnen Sie ein <strong>Inkognito-Fenster</strong></li>
                    <li>Oder <strong>löschen Sie die Browser-Daten</strong> komplett</li>
                    <li>Wenn nichts hilft: <strong>Warten Sie 5 Minuten</strong> (CDN-Cache-Ablauf)</li>
                </ol>
            </div>
            
            <a href="preview_course.php?id=10&nocache=<?php echo time(); ?>" class="btn">
                🔍 Zur Vorschau (mit Cache-Buster)
            </a>
            
            <a href="dashboard.php?page=templates" class="btn" style="background: linear-gradient(135deg, #3b82f6, #2563eb); margin-left: 10px;">
                ← Zurück zur Verwaltung
            </a>
            
        <?php else: ?>
            <div class="result error">
                ⚠️ Sie müssen als Admin eingeloggt sein, um diesen Cache zu leeren.
            </div>
            <a href="../public/admin-login.php" class="btn">Zum Admin-Login</a>
        <?php endif; ?>
    </div>
</body>
</html>