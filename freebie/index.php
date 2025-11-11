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
    
    // Customer-ID für Footer-Links
    $customer_id = $freebie['customer_id'];
    
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

// 🆕 PIXEL-BASIERTE SCHRIFTGRÖSSEN AUS JSON LADEN
$sizes = [
    'headline' => '48px',
    'subheadline' => '20px',
    'body' => '16px',
    'preheadline' => '14px'
];

// Versuche font_size JSON zu dekodieren
if (!empty($freebie['font_size'])) {
    $decoded = json_decode($freebie['font_size'], true);
    if ($decoded && is_array($decoded)) {
        // Pixel-Werte mit "px" Suffix
        if (isset($decoded['headline'])) {
            $sizes['headline'] = $decoded['headline'] . 'px';
        }
        if (isset($decoded['subheadline'])) {
            $sizes['subheadline'] = $decoded['subheadline'] . 'px';
        }
        if (isset($decoded['bullet'])) {
            $sizes['body'] = $decoded['bullet'] . 'px';
        }
        if (isset($decoded['preheadline'])) {
            $sizes['preheadline'] = $decoded['preheadline'] . 'px';
        }
    }
}

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

// Email Optin Felder
$optin_headline = $freebie['optin_headline'] ?? 'Sichere dir jetzt deinen kostenlosen Zugang';
$optin_button_text = $freebie['optin_button_text'] ?? 'JETZT KOSTENLOS SICHERN!!!';
$optin_email_placeholder = $freebie['optin_email_placeholder'] ?? 'Deine E-Mail-Adresse';
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
        
        /* Mockup - deutlich kleiner */
        .mockup-image {
            max-width: 280px !important;
            height: auto;
            margin: 0 auto;
            display: block;
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
        
        /* Centered Layout Bulletpoints */
        .layout-centered .bulletpoints {
            text-align: left;
            display: inline-block;
            margin: 0 auto 40px;
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
        
        /* CTA Button Container - zentriert für centered layout */
        .cta-container {
            text-align: center;
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
        
        /* Email Optin Popup */
        .popup-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9998;
            animation: fadeIn 0.3s ease;
        }
        
        .popup-container {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            z-index: 9999;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }
        
        .popup-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #9ca3af;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }
        
        .popup-close:hover {
            color: #374151;
        }
        
        .popup-headline {
            font-size: 24px;
            font-weight: 700;
            color: <?php echo $primary_color; ?>;
            margin-bottom: 24px;
            font-family: '<?php echo $font_heading; ?>', sans-serif;
        }
        
        .popup-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .popup-input {
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            font-family: '<?php echo $font_body; ?>', sans-serif;
            transition: border-color 0.2s;
        }
        
        .popup-input:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
        }
        
        .popup-button {
            background: <?php echo $primary_color; ?>;
            color: white;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: '<?php echo $font_body; ?>', sans-serif;
        }
        
        .popup-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        /* Cookie Banner */
        .cookie-banner {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            width: 90%;
            z-index: 9997;
            animation: slideUpCookie 0.5s ease;
        }
        
        @keyframes slideUpCookie {
            from {
                opacity: 0;
                transform: translate(-50%, 100px);
            }
            to {
                opacity: 1;
                transform: translate(-50%, 0);
            }
        }
        
        .cookie-content {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .cookie-text {
            flex: 1;
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
            min-width: 200px;
        }
        
        .cookie-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .cookie-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .cookie-accept {
            background: <?php echo $primary_color; ?>;
            color: white;
        }
        
        .cookie-accept:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .cookie-decline {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .cookie-decline:hover {
            background: #e5e7eb;
        }
        
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
            
            .mockup-image {
                max-width: 200px !important;
            }
            
            .popup-container {
                padding: 30px 20px;
            }
            
            .cookie-content {
                flex-direction: column;
                text-align: center;
            }
            
            .cookie-buttons {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Cookie Banner -->
    <div id="cookieBanner" class="cookie-banner">
        <div class="cookie-content">
            <div class="cookie-text">
                <strong>🍪 Cookies</strong><br>
                Wir nutzen Cookies, um deine Erfahrung zu verbessern. Mit der Nutzung unserer Seite stimmst du dem zu.
            </div>
            <div class="cookie-buttons">
                <button class="cookie-btn cookie-accept" onclick="acceptCookies()">Akzeptieren</button>
                <button class="cookie-btn cookie-decline" onclick="declineCookies()">Ablehnen</button>
            </div>
        </div>
    </div>

    <!-- Email Optin Popup -->
    <?php if ($optin_display_mode === 'popup'): ?>
    <div id="popupOverlay" class="popup-overlay" onclick="closePopup()"></div>
    <div id="popupContainer" class="popup-container">
        <button class="popup-close" onclick="closePopup()">&times;</button>
        <h2 class="popup-headline"><?php echo htmlspecialchars($optin_headline); ?></h2>
        <form class="popup-form" id="optinForm" method="POST" action="/freebie/thankyou.php">
            <input type="hidden" name="freebie_id" value="<?php echo htmlspecialchars($unique_id); ?>">
            <input 
                type="email" 
                name="email" 
                class="popup-input" 
                placeholder="<?php echo htmlspecialchars($optin_email_placeholder); ?>" 
                required
            >
            <button type="submit" class="popup-button">
                <?php echo htmlspecialchars($optin_button_text); ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

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
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup" class="mockup-image">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($freebie['bullet_points'])): ?>
                    <div class="bulletpoints">
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
                
                <div class="cta-container">
                    <button 
                        class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>"
                        onclick="handleCTAClick()"
                    >
                        <?php echo htmlspecialchars($freebie['cta_text']); ?>
                    </button>
                </div>
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
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup" class="mockup-image">
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
                    
                    <button 
                        class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>"
                        onclick="handleCTAClick()"
                    >
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
                    
                    <button 
                        class="cta-button <?php echo $cta_animation !== 'none' ? 'animate-' . $cta_animation : ''; ?>"
                        onclick="handleCTAClick()"
                    >
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
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup" class="mockup-image">
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

    <script>
        // Cookie Banner Logic
        function showCookieBanner() {
            const consent = localStorage.getItem('cookieConsent');
            if (!consent) {
                document.getElementById('cookieBanner').style.display = 'block';
            }
        }

        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            document.getElementById('cookieBanner').style.display = 'none';
        }

        function declineCookies() {
            localStorage.setItem('cookieConsent', 'declined');
            document.getElementById('cookieBanner').style.display = 'none';
        }

        // Email Optin Popup Logic
        function openPopup() {
            const overlay = document.getElementById('popupOverlay');
            const container = document.getElementById('popupContainer');
            if (overlay && container) {
                overlay.style.display = 'block';
                container.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }

        function closePopup() {
            const overlay = document.getElementById('popupOverlay');
            const container = document.getElementById('popupContainer');
            if (overlay && container) {
                overlay.style.display = 'none';
                container.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        function handleCTAClick() {
            const displayMode = '<?php echo $optin_display_mode; ?>';
            
            if (displayMode === 'popup') {
                openPopup();
            } else {
                // Direct Mode - scroll to form or redirect
                window.location.href = '/freebie/thankyou.php?id=<?php echo $unique_id; ?>';
            }
        }

        // Show cookie banner on load
        window.addEventListener('load', function() {
            showCookieBanner();
        });

        // Close popup with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePopup();
            }
        });
    </script>
</body>
</html>
