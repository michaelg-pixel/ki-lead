<?php
/**
 * UPDATE: Click-to-Play Feature für Videos
 * Fügt schönes Video-Preview mit Play-Button hinzu
 */

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🎬 UPDATE: Click-to-Play Video Feature\n";
echo str_repeat("=", 80) . "\n\n";

$targetFile = __DIR__ . '/freebie/index.php';

if (!file_exists($targetFile)) {
    die("❌ Datei nicht gefunden: $targetFile\n");
}

echo "📂 Lade freebie/index.php...\n";
$content = file_get_contents($targetFile);
$originalSize = strlen($content);
echo "✓ Geladen (" . number_format($originalSize) . " bytes)\n\n";

// Backup erstellen
$backupFile = $targetFile . '.backup.' . date('Y-m-d_H-i-s');
file_put_contents($backupFile, $content);
echo "💾 Backup erstellt: " . basename($backupFile) . "\n\n";

echo "🔧 Füge Click-to-Play Features hinzu...\n\n";

// 1. Füge Vimeo-Thumbnail-Funktion hinzu (nach getVideoEmbedUrl)
$videoHelperCode = <<<'PHP'

// Vimeo Thumbnail Helper
function getVimeoThumbnail($videoId) {
    $url = "https://vimeo.com/api/oembed.json?url=https://vimeo.com/" . $videoId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['thumbnail_url'])) {
            $thumbnail = $data['thumbnail_url'];
            $thumbnail = preg_replace('/_\d+x\d+/', '_1280x720', $thumbnail);
            return $thumbnail;
        }
    }
    
    return "https://i.vimeocdn.com/video/{$videoId}_1280x720.jpg";
}

// Extrahiere Video-ID aus URL
function getVideoId($url) {
    // YouTube
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return ['platform' => 'youtube', 'id' => $matches[1]];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
        return ['platform' => 'vimeo', 'id' => $matches[1]];
    }
    
    return null;
}
PHP;

// Suche Position nach getVideoEmbedUrl Funktion
$searchPattern = '$videoEmbedUrl = getVideoEmbedUrl($videoUrl);';
$position = strpos($content, $searchPattern);

if ($position !== false) {
    $insertPosition = $position + strlen($searchPattern);
    $content = substr_replace($content, "\n" . $videoHelperCode, $insertPosition, 0);
    echo "✓ Video-Helper-Funktionen hinzugefügt\n";
} else {
    echo "⚠️ Konnte Einfügeposition nicht finden\n";
}

// 2. Füge CSS für Click-to-Play hinzu
$clickToPlayCSS = <<<'CSS'

        /* CLICK-TO-PLAY VIDEO */
        .video-preview {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .video-preview-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .video-preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .video-preview:hover .video-preview-overlay {
            background: rgba(0, 0, 0, 0.5);
        }
        
        .video-play-button {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        
        .video-preview:hover .video-play-button {
            transform: scale(1.1);
            background: white;
        }
        
        .video-play-icon {
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 15px 0 15px 25px;
            border-color: transparent transparent transparent #8B5CF6;
            margin-left: 5px;
        }
        
        .video-iframe-container {
            display: none;
        }
        
        .video-iframe-container.active {
            display: block;
        }
CSS;

// Füge CSS vor dem schließenden </style> Tag ein
$content = str_replace('</style>', $clickToPlayCSS . "\n    </style>", $content);
echo "✓ Click-to-Play CSS hinzugefügt\n";

// 3. Füge JavaScript hinzu (vor </body>)
$clickToPlayJS = <<<'JS'

    <script>
    // Click-to-Play Video Handler
    document.addEventListener('DOMContentLoaded', function() {
        const videoPreviews = document.querySelectorAll('.video-preview');
        
        videoPreviews.forEach(preview => {
            preview.addEventListener('click', function() {
                const videoId = this.dataset.videoId;
                const platform = this.dataset.platform;
                const format = this.dataset.format;
                const container = this.parentElement;
                
                let embedUrl = '';
                if (platform === 'youtube') {
                    embedUrl = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
                } else if (platform === 'vimeo') {
                    embedUrl = `https://player.vimeo.com/video/${videoId}?autoplay=1&title=0&byline=0&portrait=0`;
                }
                
                // Erstelle iframe
                const iframe = document.createElement('iframe');
                iframe.src = embedUrl;
                iframe.frameBorder = '0';
                iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
                iframe.allowFullscreen = true;
                iframe.style.position = 'absolute';
                iframe.style.top = '0';
                iframe.style.left = '0';
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                
                // Verstecke Preview, zeige iframe
                this.style.display = 'none';
                const iframeContainer = container.querySelector('.video-iframe-container');
                iframeContainer.appendChild(iframe);
                iframeContainer.classList.add('active');
            });
        });
    });
    </script>
JS;

$content = str_replace('</body>', $clickToPlayJS . "\n</body>", $content);
echo "✓ Click-to-Play JavaScript hinzugefügt\n\n";

// Speichere aktualisierte Datei
file_put_contents($targetFile, $content);
$newSize = strlen($content);

echo "✅ UPDATE ERFOLGREICH!\n";
echo "   Original: " . number_format($originalSize) . " bytes\n";
echo "   Neu: " . number_format($newSize) . " bytes\n";
echo "   Differenz: +" . number_format($newSize - $originalSize) . " bytes\n\n";

echo str_repeat("=", 80) . "\n";
echo "🎉 CLICK-TO-PLAY FEATURE AKTIVIERT!\n\n";

echo "📋 WAS WURDE HINZUGEFÜGT:\n";
echo "• Vimeo-Thumbnail-Funktion\n";
echo "• Video-ID-Extraktor\n";
echo "• Schönes Preview-Design mit Play-Button\n";
echo "• Hover-Effekte\n";
echo "• Click-Handler für Autoplay\n\n";

echo "⚠️ HINWEIS:\n";
echo "Die HTML-Struktur muss noch angepasst werden!\n";
echo "Führe als nächstes aus: update-video-html.php\n";

echo "</pre>";
