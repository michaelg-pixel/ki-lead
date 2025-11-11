<?php
/**
 * Freebie Public Page - Mit allen neuen Features
 * - 3 Layouts (Hybrid, Centered, Sidebar)
 * - Font-Größen & Schriftarten
 * - Bullet Icon Styles
 * - Button Animationen
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

$identifier = $_GET['id'] ?? null;
if (!$identifier) { 
    die('Keine Freebie-ID angegeben');
}

// Tracking
$ref_code = isset($_GET['ref']) ? trim($_GET['ref']) : null;
$customer_param = isset($_GET['customer']) ? intval($_GET['customer']) : null;

$customer_id = null;
$freebie_db_id = null;

// Google Fonts
$googleFonts = [
    'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap',
    'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap',
    'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap',
    'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap',
    'Playfair Display' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&display=swap'
];

try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.id as customer_id
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        WHERE cf.unique_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'] ?? null;
        $freebie_db_id = $freebie['id'] ?? null;
    }
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

if (!$freebie) { 
    die('Freebie nicht gefunden: ' . htmlspecialchars($identifier));
}

if ($customer_param) {
    $customer_id = $customer_param;
}

// Settings mit Defaults
$layout = $freebie['layout'] ?? 'hybrid';
$bulletIconStyle = $freebie['bullet_icon_style'] ?? 'checkmark';
$primaryColor = $freebie['primary_color'] ?? '#5B8DEF';
$backgroundColor = $freebie['background_color'] ?? '#F8F9FC';
$fontHeading = $freebie['font_heading'] ?? 'Inter';
$fontBody = $freebie['font_body'] ?? 'Inter';
$headingFontSize = $freebie['heading_font_size'] ?? 32;
$bodyFontSize = $freebie['body_font_size'] ?? 16;
$ctaAnimation = $freebie['cta_animation'] ?? 'none';
$optinDisplayMode = $freebie['optin_display_mode'] ?? 'direct';
$popupMessage = $freebie['popup_message'] ?? 'Trage dich jetzt ein!';

// Video
$videoUrl = $freebie['video_url'] ?? '';
$videoFormat = $freebie['video_format'] ?? 'widescreen';

function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    return null;
}

$videoEmbedUrl = getVideoEmbedUrl($videoUrl);

// Links
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($freebie['headline'] ?? 'Freebie') ?></title>
    
    <!-- Google Fonts -->
    <?php 
    $fontsToLoad = array_unique([$fontHeading, $fontBody]);
    foreach ($fontsToLoad as $font): 
        if (isset($googleFonts[$font])): ?>
            <link href="<?= $googleFonts[$font] ?>" rel="stylesheet">
        <?php endif; 
    endforeach; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '<?= htmlspecialchars($fontBody) ?>', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?= htmlspecialchars($backgroundColor) ?>;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Headlines - Immer zentriert */
        .headlines {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .preheadline {
            color: <?= htmlspecialchars($primaryColor) ?>;
            font-size: 14px;
            font-family: '<?= htmlspecialchars($fontHeading) ?>', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        
        .headline {
            color: #1a1a2e;
            font-size: <?= (int)$headingFontSize ?>px;
            font-family: '<?= htmlspecialchars($fontHeading) ?>', sans-serif;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        
        .subheadline {
            color: #666;
            font-size: <?= (int)$bodyFontSize ?>px;
            font-family: '<?= htmlspecialchars($fontBody) ?>', sans-serif;
            line-height: 1.6;
        }
        
        /* Video */
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 12px;
            margin-bottom: 40px;
        }
        
        .video-container.shorts {
            padding-bottom: 177.78%;
            max-width: 400px;
            margin: 0 auto 40px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        /* Mockup */
        .mockup img {
            max-width: 100%;
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 8px;
        }
        
        /* Bullets */
        .bullets {
            list-style: none;
        }
        
        .bullet {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: <?= (int)$bodyFontSize ?>px;
            font-family: '<?= htmlspecialchars($fontBody) ?>', sans-serif;
            line-height: 1.6;
            color: #374151;
        }
        
        .bullet-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: <?= htmlspecialchars($primaryColor) ?>;
            color: white;
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }
        
        /* CTA Button */
        .cta-button {
            display: inline-block;
            padding: 18px 40px;
            border-radius: 8px;
            background: <?= htmlspecialchars($primaryColor) ?>;
            color: white;
            font-weight: 700;
            font-size: <?= (int)$bodyFontSize ?>px;
            font-family: '<?= htmlspecialchars($fontBody) ?>', sans-serif;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s;
            cursor: pointer;
            border: none;
            margin-top: 24px;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        /* Button Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .btn-pulse { animation: pulse 2s infinite; }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px rgba(91, 141, 239, 0.5); }
            50% { box-shadow: 0 0 20px rgba(91, 141, 239, 0.8), 0 0 30px rgba(91, 141, 239, 0.6); }
        }
        .btn-glow { animation: glow 2s infinite; }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .btn-bounce { animation: bounce 2s infinite; }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .btn-shake { animation: shake 3s infinite; }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-rotate { animation: rotate 3s linear infinite; }
        
        /* Form */
        .optin-form {
            background: rgba(255, 255, 255, 0.8);
            border-radius: 12px;
            padding: 32px;
            margin-top: 24px;
        }
        
        .optin-form input[type="email"],
        .optin-form input[type="text"] {
            width: 100%;
            padding: 14px 18px;
            margin-bottom: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }
        
        .optin-form button,
        .optin-form input[type="submit"] {
            width: 100%;
            padding: 14px 18px;
            background: <?= htmlspecialchars($primaryColor) ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .optin-form button:hover,
        .optin-form input[type="submit"]:hover {
            transform: translateY(-2px);
        }
        
        /* Layout: Centered */
        .layout-centered {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }
        
        .layout-centered .mockup {
            margin: 40px auto;
        }
        
        .layout-centered .bullets {
            max-width: 600px;
            margin: 40px auto;
            text-align: left;
        }
        
        /* Layout: Hybrid */
        .layout-hybrid .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        /* Layout: Sidebar */
        .layout-sidebar .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        /* Footer */
        .footer {
            margin-top: 60px;
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .footer a {
            color: #666;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* Popup */
        .popup-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            padding: 20px;
            overflow-y: auto;
        }
        
        .popup-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .popup-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            position: relative;
        }
        
        .popup-header {
            padding: 32px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .popup-close {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 40px;
            height: 40px;
            border: none;
            background: none;
            font-size: 32px;
            color: #999;
            cursor: pointer;
            line-height: 1;
        }
        
        .popup-close:hover {
            color: #333;
        }
        
        .popup-body {
            padding: 32px;
        }
        
        body.popup-open {
            overflow: hidden;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .headline {
                font-size: <?= max(24, (int)$headingFontSize - 8) ?>px;
            }
            
            .layout-hybrid .grid,
            .layout-sidebar .grid {
                grid-template-columns: 1fr;
            }
            
            .optin-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // Build content parts
        $preheadline_html = !empty($freebie['preheadline']) ? 
            '<div class="preheadline">' . htmlspecialchars($freebie['preheadline']) . '</div>' : '';
        
        $headline_html = '<h1 class="headline">' . 
            htmlspecialchars($freebie['headline'] ?? 'Willkommen') . '</h1>';
        
        $subheadline_html = !empty($freebie['subheadline']) ? 
            '<p class="subheadline">' . htmlspecialchars($freebie['subheadline']) . '</p>' : '';
        
        // Video
        $video_html = '';
        if (!empty($videoEmbedUrl)) {
            $video_html = '<div class="video-container ' . ($videoFormat === 'shorts' ? 'shorts' : '') . '">
                <iframe src="' . htmlspecialchars($videoEmbedUrl) . '" frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen></iframe>
            </div>';
        }
        
        // Mockup
        $mockup_html = '';
        if (!empty($freebie['mockup_image_url'])) {
            $mockup_html = '<div class="mockup">
                <img src="' . htmlspecialchars($freebie['mockup_image_url']) . '" alt="Mockup">
            </div>';
        }
        
        // Bullets
        $bullets_html = '';
        if (!empty($freebie['bullet_points'])) {
            $bullets = array_filter(explode("\n", $freebie['bullet_points']), function($b) { 
                return trim($b) !== ''; 
            });
            
            if (!empty($bullets)) {
                $iconMap = [
                    'none' => '',
                    'checkmark' => '✓',
                    'standard' => '→',
                    'star' => '★'
                ];
                $icon = $iconMap[$bulletIconStyle] ?? '✓';
                
                $bullets_html = '<ul class="bullets">';
                foreach ($bullets as $bullet) {
                    $bullets_html .= '<li class="bullet">';
                    if ($bulletIconStyle !== 'none') {
                        $bullets_html .= '<span class="bullet-icon">' . $icon . '</span>';
                    }
                    $bullets_html .= '<span>' . htmlspecialchars(trim($bullet)) . '</span>';
                    $bullets_html .= '</li>';
                }
                $bullets_html .= '</ul>';
            }
        }
        
        // CTA/Form
        $cta_html = '';
        $animationClass = $ctaAnimation !== 'none' ? 'btn-' . $ctaAnimation : '';
        
        if ($optinDisplayMode === 'direct' && !empty($freebie['raw_code'])) {
            $cta_html = '<div class="optin-form">' . $freebie['raw_code'] . '</div>';
        } elseif ($optinDisplayMode === 'popup') {
            $ctaText = $freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN';
            $cta_html = '<button onclick="openPopup()" class="cta-button ' . $animationClass . '">' . 
                htmlspecialchars($ctaText) . '</button>';
        } else {
            $cta_html = '<div class="optin-form">';
            if (!empty($freebie['raw_code'])) {
                $cta_html .= $freebie['raw_code'];
            } else {
                $cta_html .= '<form>
                    <input type="email" name="email" placeholder="E-Mail-Adresse" required>
                    <button type="submit">Jetzt anmelden</button>
                </form>';
            }
            $cta_html .= '</div>';
        }
        
        // Render layout
        if ($layout === 'centered') {
            echo '<div class="layout-centered">';
            echo '<div class="headlines">';
            echo $preheadline_html . $headline_html . $subheadline_html;
            echo '</div>';
            echo $video_html;
            echo $mockup_html;
            echo $bullets_html;
            echo '<div style="text-align: center;">' . $cta_html . '</div>';
            echo '</div>';
            
        } elseif ($layout === 'hybrid') {
            echo '<div class="layout-hybrid">';
            echo '<div class="headlines">';
            echo $preheadline_html . $headline_html . $subheadline_html;
            echo '</div>';
            echo '<div class="grid">';
            echo '<div>' . $video_html . $mockup_html . '</div>';
            echo '<div>' . $bullets_html . $cta_html . '</div>';
            echo '</div>';
            echo '</div>';
            
        } else { // sidebar
            echo '<div class="layout-sidebar">';
            echo '<div class="headlines">';
            echo $preheadline_html . $headline_html . $subheadline_html;
            echo '</div>';
            echo '<div class="grid">';
            echo '<div>' . $bullets_html . $cta_html . '</div>';
            echo '<div>' . $video_html . $mockup_html . '</div>';
            echo '</div>';
            echo '</div>';
        }
        ?>
        
        <div class="footer">
            <a href="<?= $impressum_link ?>">Impressum</a>
            <span style="margin: 0 12px;">•</span>
            <a href="<?= $datenschutz_link ?>">Datenschutz</a>
        </div>
    </div>
    
    <?php if ($optinDisplayMode === 'popup'): ?>
    <!-- Popup -->
    <div id="popup" class="popup-overlay" onclick="if(event.target === this) closePopup()">
        <div class="popup-content" onclick="event.stopPropagation()">
            <div class="popup-header">
                <button onclick="closePopup()" class="popup-close">×</button>
                <div style="font-size: 64px; margin-bottom: 16px;">🎁</div>
                <h2 style="font-size: 28px; font-weight: 800; margin-bottom: 8px;">
                    <?= htmlspecialchars($freebie['headline'] ?? '') ?>
                </h2>
                <p style="color: #666;"><?= htmlspecialchars($popupMessage) ?></p>
            </div>
            <div class="popup-body">
                <?php if (!empty($freebie['raw_code'])): ?>
                    <?= $freebie['raw_code'] ?>
                <?php else: ?>
                    <form>
                        <input type="email" name="email" placeholder="E-Mail-Adresse" required
                               style="width: 100%; padding: 14px; margin-bottom: 12px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 16px;">
                        <button type="submit" 
                                style="width: 100%; padding: 14px; background: <?= htmlspecialchars($primaryColor) ?>; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                            <?= htmlspecialchars($freebie['cta_text'] ?? 'Jetzt anmelden') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
    function openPopup() {
        document.getElementById('popup').classList.add('active');
        document.body.classList.add('popup-open');
    }
    function closePopup() {
        document.getElementById('popup').classList.remove('active');
        document.body.classList.remove('popup-open');
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePopup();
    });
    </script>
    <?php endif; ?>
    
    <!-- Cookie Banner -->
    <?php 
    $cookieBannerPath = __DIR__ . '/../includes/cookie-banner.php';
    if (file_exists($cookieBannerPath)) {
        require_once $cookieBannerPath;
    }
    ?>
</body>
</html>
