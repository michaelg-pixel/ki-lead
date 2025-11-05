<?php
/**
 * UPDATE Teil 2: Ersetze Video-HTML durch Click-to-Play
 */

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🎬 UPDATE Teil 2: Video-HTML für Click-to-Play\n";
echo str_repeat("=", 80) . "\n\n";

$targetFile = __DIR__ . '/freebie/index.php';

if (!file_exists($targetFile)) {
    die("❌ Datei nicht gefunden: $targetFile\n");
}

$content = file_get_contents($targetFile);
echo "✓ Datei geladen\n\n";

// Backup
$backupFile = $targetFile . '.backup-html.' . date('Y-m-d_H-i-s');
file_put_contents($backupFile, $content);
echo "💾 Backup: " . basename($backupFile) . "\n\n";

echo "🔄 Ersetze Video-Display-Logik...\n\n";

// Neuer Video-Display-Code mit Click-to-Play
$newVideoDisplayCode = <<<'PHP'
<?php if ($videoEmbedUrl): ?>
                        <!-- VIDEO mit Click-to-Play -->
                        <?php
                        $videoInfo = getVideoId($videoUrl);
                        $thumbnailUrl = '';
                        
                        if ($videoInfo) {
                            if ($videoInfo['platform'] === 'vimeo') {
                                $thumbnailUrl = getVimeoThumbnail($videoInfo['id']);
                            } else if ($videoInfo['platform'] === 'youtube') {
                                $thumbnailUrl = "https://img.youtube.com/vi/{$videoInfo['id']}/maxresdefault.jpg";
                            }
                        }
                        ?>
                        
                        <div class="video-container <?php echo $videoFormat === 'portrait' ? 'portrait' : 'widescreen'; ?>">
                            <!-- Video Preview (Thumbnail + Play Button) -->
                            <div class="video-wrapper <?php echo $videoFormat === 'portrait' ? 'portrait' : ''; ?>">
                                <div class="video-preview" 
                                     data-video-id="<?php echo htmlspecialchars($videoInfo['id'] ?? ''); ?>"
                                     data-platform="<?php echo htmlspecialchars($videoInfo['platform'] ?? ''); ?>"
                                     data-format="<?php echo htmlspecialchars($videoFormat); ?>">
                                    
                                    <?php if ($thumbnailUrl): ?>
                                        <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                                             alt="Video Vorschau" 
                                             class="video-preview-thumbnail">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;background:linear-gradient(135deg,<?php echo htmlspecialchars($primaryColor); ?> 0%,#667eea 100%);"></div>
                                    <?php endif; ?>
                                    
                                    <div class="video-preview-overlay">
                                        <div class="video-play-button">
                                            <div class="video-play-icon"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Iframe Container (wird beim Klick gefüllt) -->
                                <div class="video-iframe-container"></div>
                            </div>
                        </div>
PHP;

// Alter Code der ersetzt werden soll (nur das VIDEO-Teil)
$oldVideoCode = <<<'PHP'
<?php if ($videoEmbedUrl): ?>
                        <!-- VIDEO hat Priorität -->
                        <div class="video-container <?php echo $videoFormat === 'portrait' ? 'portrait' : 'widescreen'; ?>">
                            <div class="video-wrapper <?php echo $videoFormat === 'portrait' ? 'portrait' : ''; ?>">
                                <iframe 
                                    src="<?php echo htmlspecialchars($videoEmbedUrl); ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
PHP;

// Ersetze alle 3 Vorkommen (centered, sidebar, hybrid layouts)
$replacements = 0;
$tempContent = $content;

while (strpos($tempContent, $oldVideoCode) !== false) {
    $tempContent = preg_replace(
        '/' . preg_quote($oldVideoCode, '/') . '/',
        $newVideoDisplayCode,
        $tempContent,
        1
    );
    $replacements++;
    
    // Sicherheitscheck: Maximal 3 Ersetzungen
    if ($replacements >= 3) break;
}

if ($replacements > 0) {
    $content = $tempContent;
    echo "✅ Video-Display-Code ersetzt ($replacements Vorkommen)\n\n";
} else {
    echo "⚠️ Alter Code nicht gefunden - eventuell bereits aktualisiert\n\n";
}

// Speichern
file_put_contents($targetFile, $content);

echo "✅ HTML-UPDATE ABGESCHLOSSEN!\n\n";

echo str_repeat("=", 80) . "\n";
echo "🎉 CLICK-TO-PLAY IST JETZT AKTIV!\n\n";

echo "📋 WAS PASSIERT JETZT:\n";
echo "1. Video-Thumbnail wird als Vorschau gezeigt\n";
echo "2. Großer Play-Button wird angezeigt\n";
echo "3. Beim Klick: Video lädt und spielt automatisch MIT TON\n";
echo "4. DSGVO-konform: Video lädt erst nach User-Interaktion\n\n";

echo "🧪 TESTE JETZT:\n";
echo "https://app.mehr-infos-jetzt.de/freebie/index.php?id=04828493b017248c0db10bb82d48754e\n\n";

echo "✨ Features:\n";
echo "• Hover-Effekt auf Play-Button\n";
echo "• Smooth Transition zum Video\n";
echo "• Autoplay mit Ton (nach Klick)\n";
echo "• Funktioniert mit YouTube & Vimeo\n";

echo "</pre>";
