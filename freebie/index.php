<?php
/**
 * Öffentliche Freebie-Ansicht via unique_id
 * Diese Datei akzeptiert die unique_id und zeigt das entsprechende Freebie an
 */

require_once __DIR__ . '/../config/database.php';

// unique_id aus URL holen
$unique_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($unique_id)) {
    http_response_code(400);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Ungültige Freebie-ID</h1><p>Bitte überprüfen Sie den Link.</p></body></html>');
}

try {
    // Freebie aus customer_freebies laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE unique_id = ?");
    $stmt->execute([$unique_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        http_response_code(404);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht gefunden</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Freebie nicht gefunden</h1><p>Dieses Freebie existiert nicht oder wurde gelöscht.</p></body></html>');
    }
    
    // Customer-Daten laden für Footer-Links
    $customer_id = $freebie['customer_id'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    http_response_code(500);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Datenbankfehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>');
}

// Layout, Farben und Fonts
$layout = $freebie['layout'] ?? 'hybrid';
$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$background_color = $freebie['background_color'] ?? '#FFFFFF';

// Font-Einstellungen
$font_heading = $freebie['font_heading'] ?? 'Inter';
$font_body = $freebie['font_body'] ?? 'Inter';
$font_size = $freebie['font_size'] ?? 'medium';

// Font-Größen Mapping
$font_sizes = [
    'small' => ['headline' => '32px', 'subheadline' => '16px', 'body' => '14px', 'preheadline' => '12px'],
    'medium' => ['headline' => '48px', 'subheadline' => '20px', 'body' => '16px', 'preheadline' => '14px'],
    'large' => ['headline' => '56px', 'subheadline' => '24px', 'body' => '18px', 'preheadline' => '16px']
];
$sizes = $font_sizes[$font_size] ?? $font_sizes['medium'];

// Mockup und Video
$show_mockup = !empty($freebie['mockup_image_url']);
$mockup_url = $freebie['mockup_image_url'] ?? '';
$show_video = !empty($freebie['video_url']);
$video_url = $freebie['video_url'] ?? '';
$video_format = $freebie['video_format'] ?? 'widescreen';

// Footer-Links
$impressum_link = "/impressum.php?customer=" . $customer_id;
$datenschutz_link = "/datenschutz.php?customer=" . $customer_id;

// Video Embed URL ermitteln
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $match)) {
        return 'https://player.vimeo.com/video/' . $match[1];
    }
    
    return null;
}

$video_embed_url = getVideoEmbedUrl($video_url);

// Custom Code / Tracking extrahieren
$custom_tracking_code = '';
if (!empty($freebie['raw_code'])) {
    $parts = explode('<!-- CUSTOM_TRACKING_CODE -->', $freebie['raw_code']);
    if (isset($parts[1])) {
        $custom_tracking_code = trim($parts[1]);
    }
}

// Bullet Icon Style
$bullet_icon_style = $freebie['bullet_icon_style'] ?? 'standard';

// Email Optin Display Mode
$optin_display_mode = $freebie['optin_display_mode'] ?? 'direct';
$popup_message = $freebie['popup_message'] ?? 'Trage dich jetzt unverbindlich ein!';
$cta_animation = $freebie['cta_animation'] ?? 'none';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['headline'] ?? 'Kostenloses Angebot'); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@400;600;700;800&family=Montserrat:wght@400;600;700;800&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@400;700&family=Raleway:wght@400;600;700&family=Oswald:wght@400;600;700&display=swap" rel="stylesheet">
    
    <?php if (!empty($custom_tracking_code)): ?>
    <!-- Custom Tracking Code -->
    <?php echo $custom_tracking_code; ?>
    <?php endif; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '<?php echo $font_body; ?>', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?php echo $background_color; ?>;
            line-height: 1.6;
            color: #1F2937;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        /* Preheadline */
        .preheadline {
            color: <?php echo $primary_color; ?>;
            font-size: <?php echo $sizes['preheadline']; ?>;
            font-family: '<?php echo $font_body; ?>', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            text-align: center;
        }
        
        /* Headline */
        .headline {
            font-size: <?php echo $sizes['headline']; ?>;
            font-family: '<?php echo $font_heading; ?>', sans-serif;
            font-weight: 800;
            color: <?php echo $primary_color; ?>;
            line-height: 1.2;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Subheadline */
        .subheadline {
            font-size: <?php echo $sizes['subheadline']; ?>;
            font-family: '<?php echo $font_body; ?>', sans-serif;
            color: #6b7280;
            margin-bottom: 40px;
            line-height: 1.6;
            text-align: center;
        }
        
        /* Layouts */
        .layout-hybrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .layout-centered {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .layout-sidebar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        /* Media */
        .media-container {
            text-align: center;
        }
        
        .media-container img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            margin: 0 auto;
        }
        
        .video-container.widescreen {
            padding-bottom: 56.25%; /* 16:9 */
            max-width: 100%;
        }
        
        .video-container.portrait {
            padding-bottom: 177.78%; /* 9:16 */
            max-width: 400px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 12px;
        }
        
        /* Bulletpoints */
        .bulletpoints {
            margin-bottom: 40px;
        }
        
        .bulletpoint {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .bulletpoint-icon {
            font-size: 20px;
            flex-shrink: 0;
            color: <?php echo $primary_color; ?>;
        }
        
        .bulletpoint-text {
            color: #374151;
            font-size: <?php echo $sizes['body']; ?>;
            font-family: '<?php echo $font_body; ?>', sans-serif;
            line-height: 1.6;
        }
        
        /* CTA Button */
        .cta-button {
            background: <?php echo $primary_color; ?>;
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            font-family: '<?php echo $font_body; ?>', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        /* Button Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px <?php echo $primary_color; ?>; }
            50% { box-shadow: 0 0 20px <?php echo $primary_color; ?>; }
        }
        
        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        .animate-shake { animation: shake 0.5s ease-in-out infinite; }
        .animate-bounce { animation: bounce 1s ease-in-out infinite; }
        .animate-glow { animation: glow 2s ease-in-out infinite; }
        
        /* Footer */
        .footer {
            margin-top: 100px;
            padding-top: 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 16px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: <?php echo $primary_color; ?>;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }
            
            .layout-hybrid,
            .layout-sidebar {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .headline {
                font-size: 32px;
            }
            
            .subheadline {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($layout === 'centered'): ?>
            <!-- CENTERED LAYOUT -->
            <div class="layout-centered">
                <?php if (!empty($freebie['preheadline'])): ?>
                    <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
                <?php endif; ?>
                
                <h1 class="headline"><?php echo htmlspecialchars($freebie['headline']); ?></h1>
                
                <?php if (!empty($freebie['subheadline'])): ?>
                    <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
                <?php endif; ?>
                
                <!-- Video oder Mockup -->
                <?php if ($show_video && $video_embed_url): ?>
                    <div class="media-container" style="margin-bottom: 40px;">
                        <div class="video-container <?php echo $video_format; ?>">
                            <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    </div>
                <?php elseif ($show_mockup): ?>
                    <div class="media-container" style="margin-bottom: 40px;">
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup" style="max-width: 400px;">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($freebie['bullet_points'])): ?>
                    <div class="bulletpoints" style="text-align: left; display: inline-block;">
                        <?php
                        $bullets = explode("\n", $freebie['bullet_points']);
                        foreach ($bullets as $bullet):
                            $bullet = trim($bullet);
                            if (empty($bullet)) continue;
                            
                            // Icon extrahieren je nach Style
                            if ($bullet_icon_style === 'custom') {
                                // Emoji/Icon am Anfang erkennen
                                $icon = '✓';
                                $text = $bullet;
                                if (preg_match('/^([\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[^\w\s])/u', $bullet, $match)) {
                                    $icon = $match[1];
                                    $text = trim(substr($bullet, strlen($icon)));
                                }
                            } else {
                                // Standard: grüner Haken
                                $icon = '✓';
                                $text = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                            }
                        ?>
                            <div class="bulletpoint">
                                <span class="bulletpoint-icon"><?php echo $icon; ?></span>
                                <span class="bulletpoint-text"><?php echo htmlspecialchars($text); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <button class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>">
                    <?php echo htmlspecialchars($freebie['cta_text']); ?>
                </button>
            </div>
            
        <?php elseif ($layout === 'hybrid'): ?>
            <!-- HYBRID LAYOUT -->
            <?php if (!empty($freebie['preheadline'])): ?>
                <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
            <?php endif; ?>
            
            <h1 class="headline"><?php echo htmlspecialchars($freebie['headline']); ?></h1>
            
            <?php if (!empty($freebie['subheadline'])): ?>
                <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
            <?php endif; ?>
            
            <div class="layout-hybrid">
                <!-- Media Links -->
                <div class="media-container">
                    <?php if ($show_video && $video_embed_url): ?>
                        <div class="video-container <?php echo $video_format; ?>">
                            <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php elseif ($show_mockup): ?>
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup">
                    <?php else: ?>
                        <div style="font-size: 120px; color: <?php echo $primary_color; ?>;">🎁</div>
                    <?php endif; ?>
                </div>
                
                <!-- Content Rechts -->
                <div>
                    <?php if (!empty($freebie['bullet_points'])): ?>
                        <div class="bulletpoints">
                            <?php
                            $bullets = explode("\n", $freebie['bullet_points']);
                            foreach ($bullets as $bullet):
                                $bullet = trim($bullet);
                                if (empty($bullet)) continue;
                                
                                if ($bullet_icon_style === 'custom') {
                                    $icon = '✓';
                                    $text = $bullet;
                                    if (preg_match('/^([\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[^\w\s])/u', $bullet, $match)) {
                                        $icon = $match[1];
                                        $text = trim(substr($bullet, strlen($icon)));
                                    }
                                } else {
                                    $icon = '✓';
                                    $text = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                                }
                            ?>
                                <div class="bulletpoint">
                                    <span class="bulletpoint-icon"><?php echo $icon; ?></span>
                                    <span class="bulletpoint-text"><?php echo htmlspecialchars($text); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>">
                        <?php echo htmlspecialchars($freebie['cta_text']); ?>
                    </button>
                </div>
            </div>
            
        <?php else: ?>
            <!-- SIDEBAR LAYOUT -->
            <?php if (!empty($freebie['preheadline'])): ?>
                <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
            <?php endif; ?>
            
            <h1 class="headline"><?php echo htmlspecialchars($freebie['headline']); ?></h1>
            
            <?php if (!empty($freebie['subheadline'])): ?>
                <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
            <?php endif; ?>
            
            <div class="layout-sidebar">
                <!-- Content Links -->
                <div>
                    <?php if (!empty($freebie['bullet_points'])): ?>
                        <div class="bulletpoints">
                            <?php
                            $bullets = explode("\n", $freebie['bullet_points']);
                            foreach ($bullets as $bullet):
                                $bullet = trim($bullet);
                                if (empty($bullet)) continue;
                                
                                if ($bullet_icon_style === 'custom') {
                                    $icon = '✓';
                                    $text = $bullet;
                                    if (preg_match('/^([\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[^\w\s])/u', $bullet, $match)) {
                                        $icon = $match[1];
                                        $text = trim(substr($bullet, strlen($icon)));
                                    }
                                } else {
                                    $icon = '✓';
                                    $text = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                                }
                            ?>
                                <div class="bulletpoint">
                                    <span class="bulletpoint-icon"><?php echo $icon; ?></span>
                                    <span class="bulletpoint-text"><?php echo htmlspecialchars($text); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <button class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>">
                        <?php echo htmlspecialchars($freebie['cta_text']); ?>
                    </button>
                </div>
                
                <!-- Media Rechts -->
                <div class="media-container">
                    <?php if ($show_video && $video_embed_url): ?>
                        <div class="video-container <?php echo $video_format; ?>">
                            <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                    <?php elseif ($show_mockup): ?>
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup">
                    <?php else: ?>
                        <div style="font-size: 120px; color: <?php echo $primary_color; ?>;">🎁</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> - Alle Rechte vorbehalten</p>
        <div class="footer-links">
            <a href="<?php echo $impressum_link; ?>">Impressum</a>
            <a href="<?php echo $datenschutz_link; ?>">Datenschutz</a>
        </div>
    </div>
</body>
</html>