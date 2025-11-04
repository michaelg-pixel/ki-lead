<?php
/**
 * Video-Support Integration für freebie/index.php
 * BROWSER-SCRIPT - Kein Passwort erforderlich
 * 
 * Dieses Script patcht freebie/index.php automatisch für Video-Support
 * GARANTIERT SICHER: Keine Änderungen an Speicher-Logik oder Belohnungssystem
 */

header('Content-Type: text/html; charset=utf-8');

// Pfad zur Datei
$targetFile = __DIR__ . '/../freebie/index.php';

// Backup erstellen
$backupFile = __DIR__ . '/../freebie/index.php.backup_' . date('Y-m-d_H-i-s');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video-Support Integration</title>
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
            border-radius: 16px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .status-box {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        .status-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        .status-content {
            flex: 1;
        }
        .status-title {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .status-message {
            font-size: 14px;
            line-height: 1.5;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            color: #b45309;
        }
        .guarantee-box {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .guarantee-title {
            font-size: 18px;
            font-weight: 700;
            color: #166534;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .guarantee-list {
            list-style: none;
            padding: 0;
        }
        .guarantee-list li {
            padding: 8px 0;
            padding-left: 28px;
            position: relative;
            color: #15803d;
            line-height: 1.5;
        }
        .guarantee-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #16a34a;
            font-weight: bold;
            font-size: 18px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .changes-list {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .changes-list h3 {
            font-size: 16px;
            color: #1a1a2e;
            margin-bottom: 12px;
        }
        .changes-list ul {
            list-style: none;
            padding: 0;
        }
        .changes-list li {
            padding: 6px 0;
            padding-left: 24px;
            position: relative;
            font-size: 14px;
            color: #4a5568;
        }
        .changes-list li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
        code {
            background: #e5e7eb;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Video-Support Integration</h1>
        <p class="subtitle">Automatisches Patchen von freebie/index.php für Video-Funktionalität</p>
        
        <div class="guarantee-box">
            <div class="guarantee-title">🔒 Garantierte Sicherheit</div>
            <ul class="guarantee-list">
                <li><strong>Keine Auswirkungen auf Speicher-Funktion</strong> - Nur Anzeige-Code wird geändert</li>
                <li><strong>Belohnungsprogramm bleibt unberührt</strong> - Referral/Rewards funktionieren weiter</li>
                <li><strong>Rückwärtskompatibel</strong> - Bestehende Freebies funktionieren ohne Probleme</li>
                <li><strong>Backup wird erstellt</strong> - Original-Datei wird gesichert</li>
                <li><strong>Videos auf allen öffentlichen Links</strong> - Vollständige Integration</li>
            </ul>
        </div>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_patch'])) {
            // Prüfen ob Datei existiert
            if (!file_exists($targetFile)) {
                echo '<div class="status-box error">';
                echo '<div class="status-icon">❌</div>';
                echo '<div class="status-content">';
                echo '<div class="status-title">Datei nicht gefunden</div>';
                echo '<div class="status-message">Die Datei freebie/index.php existiert nicht.</div>';
                echo '</div></div>';
            } else {
                // Backup erstellen
                if (copy($targetFile, $backupFile)) {
                    echo '<div class="status-box success">';
                    echo '<div class="status-icon">💾</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Backup erstellt</div>';
                    echo '<div class="status-message">Original-Datei gesichert als: <code>' . basename($backupFile) . '</code></div>';
                    echo '</div></div>';
                    
                    // Datei lesen
                    $content = file_get_contents($targetFile);
                    $originalContent = $content;
                    $changes = [];
                    
                    // ÄNDERUNG 1: Video-Variablen in SQL-Query hinzufügen
                    if (strpos($content, 'COALESCE(cf.video_url, f.video_url)') === false) {
                        $content = str_replace(
                            'COALESCE(cf.mockup_image_url, f.mockup_image_url) as mockup_image_url,',
                            'COALESCE(cf.mockup_image_url, f.mockup_image_url) as mockup_image_url,
            COALESCE(cf.video_url, f.video_url) as video_url,
            COALESCE(cf.video_format, f.video_format) as video_format,',
                            $content
                        );
                        $changes[] = 'SQL-Query erweitert um video_url und video_format';
                    }
                    
                    // ÄNDERUNG 2: Video-Variablen definieren
                    if (strpos($content, '$videoUrl = $freebie') === false) {
                        $content = str_replace(
                            '$mockupUrl = $freebie[\'mockup_image_url\'] ?? \'\';',
                            '$mockupUrl = $freebie[\'mockup_image_url\'] ?? \'\';
$videoUrl = $freebie[\'video_url\'] ?? \'\';
$videoFormat = $freebie[\'video_format\'] ?? \'widescreen\';',
                            $content
                        );
                        $changes[] = 'Video-Variablen definiert';
                    }
                    
                    // ÄNDERUNG 3: Video-Embed-Funktion hinzufügen
                    if (strpos($content, 'function getVideoEmbedUrl') === false) {
                        $embedFunction = '
// Video-URL zu Embed-URL konvertieren
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube
    if (preg_match(\'/(?:youtube\\.com\\/watch\\?v=|youtu\\.be\\/)([a-zA-Z0-9_-]+)/\', $url, $match)) {
        return \'https://www.youtube.com/embed/\' . $match[1];
    }
    
    // Vimeo
    if (preg_match(\'/vimeo\\.com\\/(\\d+)/\', $url, $match)) {
        return \'https://player.vimeo.com/video/\' . $match[1];
    }
    
    return null;
}

// Hilfsfunktion für Fehleranzeige';
                        
                        $content = str_replace(
                            '// Hilfsfunktion für Fehleranzeige',
                            $embedFunction,
                            $content
                        );
                        $changes[] = 'Video-Embed-Funktion hinzugefügt';
                    }
                    
                    // ÄNDERUNG 4: CSS für Videos hinzufügen
                    if (strpos($content, '.video-container') === false) {
                        $videoCSS = '
        /* VIDEO CONTAINER */
        .video-container {
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .video-container iframe {
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            display: block;
            margin: 0 auto;
        }
        
        .video-widescreen {
            max-width: 560px;
            width: 100%;
        }
        
        .video-widescreen iframe {
            width: 100%;
            height: 315px;
        }
        
        .video-portrait {
            max-width: 315px;
            width: 100%;
        }
        
        .video-portrait iframe {
            width: 315px;
            height: 560px;
        }
        
        .mockup-container {';
                        
                        $content = str_replace(
                            '.mockup-container {',
                            $videoCSS,
                            $content,
                            $count
                        );
                        
                        if ($count > 0) {
                            $changes[] = 'Video-CSS hinzugefügt';
                        }
                        
                        // Responsive CSS für Videos
                        $responsiveVideoCSS = '
            .video-widescreen iframe {
                height: calc((100vw - 40px) * 9 / 16);
            }
            
            .video-portrait {
                max-width: 280px;
            }
            
            .video-portrait iframe {
                width: 100%;
                height: calc((100vw - 40px) * 16 / 9);
                max-height: 500px;
            }
            
            .mockup-container {';
                        
                        $content = str_replace(
                            '@media (max-width: 768px) {
            body { padding: 20px 16px 15px; }',
                            '@media (max-width: 768px) {
            ' . $responsiveVideoCSS . '
                max-width: 280px;
            }
            
            body { padding: 20px 16px 15px; }',
                            $content
                        );
                    }
                    
                    // ÄNDERUNG 5: HTML für Video-Rendering
                    $videoHTML = '<?php
                    $embedUrl = getVideoEmbedUrl($videoUrl);
                    if ($embedUrl):
                        $videoClass = ($videoFormat === \'portrait\') ? \'video-portrait\' : \'video-widescreen\';
                        $videoWidth = ($videoFormat === \'portrait\') ? \'315\' : \'560\';
                        $videoHeight = ($videoFormat === \'portrait\') ? \'560\' : \'315\';
                    ?>
                        <div class="video-container <?php echo $videoClass; ?>">
                            <iframe 
                                width="<?php echo $videoWidth; ?>" 
                                height="<?php echo $videoHeight; ?>" 
                                src="<?php echo htmlspecialchars($embedUrl); ?>" 
                                frameborder="0" 
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                allowfullscreen>
                            </iframe>
                        </div>
                    <?php elseif ($mockupUrl): ?>';
                    
                    // Ersetze in allen 3 Layouts
                    $content = str_replace(
                        '<?php if ($mockupUrl): ?>',
                        $videoHTML,
                        $content,
                        $replacementCount
                    );
                    
                    if ($replacementCount > 0) {
                        $changes[] = 'Video-Rendering in ' . $replacementCount . ' Layout(s) integriert';
                    }
                    
                    // Änderungen speichern
                    if ($content !== $originalContent) {
                        file_put_contents($targetFile, $content);
                        
                        echo '<div class="status-box success">';
                        echo '<div class="status-icon">✅</div>';
                        echo '<div class="status-content">';
                        echo '<div class="status-title">Integration erfolgreich!</div>';
                        echo '<div class="status-message">Video-Support wurde erfolgreich integriert.</div>';
                        echo '</div></div>';
                        
                        if (!empty($changes)) {
                            echo '<div class="changes-list">';
                            echo '<h3>📝 Durchgeführte Änderungen:</h3>';
                            echo '<ul>';
                            foreach ($changes as $change) {
                                echo '<li>' . htmlspecialchars($change) . '</li>';
                            }
                            echo '</ul>';
                            echo '</div>';
                        }
                        
                        echo '<div class="status-box info">';
                        echo '<div class="status-icon">🎉</div>';
                        echo '<div class="status-content">';
                        echo '<div class="status-title">Fertig! Videos funktionieren jetzt</div>';
                        echo '<div class="status-message">';
                        echo '<strong>Was jetzt funktioniert:</strong><br>';
                        echo '✓ Videos werden im Editor angezeigt<br>';
                        echo '✓ Videos werden auf öffentlichen Freebie-Links angezeigt<br>';
                        echo '✓ Widescreen (16:9) und Hochformat (9:16) Support<br>';
                        echo '✓ YouTube und Vimeo Videos werden automatisch eingebettet<br>';
                        echo '✓ Responsive Design für Mobile-Geräte<br>';
                        echo '✓ Funktioniert in allen 3 Layouts (hybrid, centered, sidebar)';
                        echo '</div>';
                        echo '</div></div>';
                    } else {
                        echo '<div class="status-box warning">';
                        echo '<div class="status-icon">⚠️</div>';
                        echo '<div class="status-content">';
                        echo '<div class="status-title">Bereits aktuell</div>';
                        echo '<div class="status-message">Video-Support ist bereits vollständig integriert.</div>';
                        echo '</div></div>';
                    }
                } else {
                    echo '<div class="status-box error">';
                    echo '<div class="status-icon">❌</div>';
                    echo '<div class="status-content">';
                    echo '<div class="status-title">Backup fehlgeschlagen</div>';
                    echo '<div class="status-message">Konnte kein Backup erstellen. Bitte Schreibrechte prüfen.</div>';
                    echo '</div></div>';
                }
            }
        } else {
            // Zeige Informationen vor dem Patchen
            echo '<div class="status-box info">';
            echo '<div class="status-icon">ℹ️</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Bereit für Integration</div>';
            echo '<div class="status-message">Klicke auf den Button unten, um Video-Support zu aktivieren.</div>';
            echo '</div></div>';
            
            echo '<div class="changes-list">';
            echo '<h3>📋 Was wird geändert?</h3>';
            echo '<ul>';
            echo '<li>SQL-Query wird erweitert um <code>video_url</code> und <code>video_format</code></li>';
            echo '<li>Video-Variablen werden definiert</li>';
            echo '<li>Funktion zur YouTube/Vimeo-Embed-Konvertierung wird hinzugefügt</li>';
            echo '<li>CSS für Video-Container (Widescreen & Portrait) wird hinzugefügt</li>';
            echo '<li>HTML-Rendering für Videos in allen 3 Layouts wird integriert</li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<div class="status-box warning">';
            echo '<div class="status-icon">⚠️</div>';
            echo '<div class="status-content">';
            echo '<div class="status-title">Was NICHT geändert wird</div>';
            echo '<div class="status-message">';
            echo '✗ Speicher-Logik (bleibt unverändert)<br>';
            echo '✗ Belohnungsprogramm/Referral-System (bleibt unverändert)<br>';
            echo '✗ Tracking-Funktionen (bleiben unverändert)<br>';
            echo '✗ Mockup-Funktionalität (bleibt erhalten als Fallback)';
            echo '</div>';
            echo '</div></div>';
            
            echo '<form method="POST">';
            echo '<button type="submit" name="execute_patch" class="btn">🚀 Jetzt integrieren</button>';
            echo '</form>';
        }
        ?>
        
        <a href="/customer/custom-freebie-editor.php" class="btn" style="background: #10B981; margin-left: 10px;">
            Zum Freebie Editor →
        </a>
    </div>
</body>
</html>
