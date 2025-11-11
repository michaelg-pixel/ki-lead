<?php
session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../public/login.php');
    exit;
}

require_once '../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$freebie_id) {
    header('Location: dashboard.php?page=freebies');
    exit;
}

// Customer Freebie laden
try {
    $stmt = $pdo->prepare("
        SELECT cf.*, 
               f.name as template_name
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        WHERE cf.id = ? AND cf.customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        die('Freebie nicht gefunden oder keine Berechtigung');
    }
    
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// Video Embed URL Konvertierung
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return null;
}

// Defaults
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

$videoUrl = $freebie['video_url'] ?? '';
$videoFormat = $freebie['video_format'] ?? 'widescreen';
$videoEmbedUrl = getVideoEmbedUrl($videoUrl);

// Google Fonts
$googleFonts = [
    'Inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap',
    'Roboto' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap',
    'Montserrat' => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap',
    'Poppins' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap',
    'Playfair Display' => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&display=swap'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie Vorschau - <?php echo htmlspecialchars($freebie['headline'] ?? 'Freebie'); ?></title>
    
    <!-- Google Fonts -->
    <?php foreach ($googleFonts as $font => $url): ?>
    <link href="<?php echo $url; ?>" rel="stylesheet">
    <?php endforeach; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 16px;
            transition: gap 0.2s;
        }
        
        .back-button:hover {
            gap: 12px;
        }
        
        .header h1 {
            color: #1a1a2e;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .action-bar {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .action-button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
        }
        
        .action-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .preview-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .freebie-content {
            background: <?php echo htmlspecialchars($backgroundColor); ?>;
            border-radius: 12px;
            padding: 80px 60px;
            min-height: 600px;
        }
        
        /* Video Container */
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 16px;
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
        
        /* Headlines - Immer zentriert */
        .headlines {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .freebie-preheadline {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-size: 14px;
            font-family: '<?php echo htmlspecialchars($fontHeading); ?>', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        
        .freebie-headline {
            color: #1a1a2e;
            font-size: <?php echo (int)$headingFontSize; ?>px;
            font-family: '<?php echo htmlspecialchars($fontHeading); ?>', sans-serif;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }
        
        .freebie-subheadline {
            color: #666;
            font-size: <?php echo (int)$bodyFontSize; ?>px;
            font-family: '<?php echo htmlspecialchars($fontBody); ?>', sans-serif;
            line-height: 1.6;
        }
        
        /* Mockup */
        .freebie-mockup img {
            max-width: 100%;
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 8px;
        }
        
        /* Bullets */
        .freebie-bullets {
            list-style: none;
        }
        
        .freebie-bullet {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: <?php echo (int)$bodyFontSize; ?>px;
            font-family: '<?php echo htmlspecialchars($fontBody); ?>', sans-serif;
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
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }
        
        /* Form / CTA */
        .freebie-form {
            margin-top: 24px;
        }
        
        .freebie-cta {
            display: inline-block;
            padding: 18px 40px;
            border-radius: 8px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            font-weight: 700;
            font-size: <?php echo (int)$bodyFontSize; ?>px;
            font-family: '<?php echo htmlspecialchars($fontBody); ?>', sans-serif;
            text-decoration: none;
            text-align: center;
            transition: transform 0.2s;
            cursor: pointer;
        }
        
        .freebie-cta:hover {
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
        
        /* Layout: Hybrid */
        .layout-hybrid .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        /* Layout: Centered */
        .layout-centered {
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
        }
        
        .layout-centered .freebie-mockup {
            margin: 40px auto;
        }
        
        .layout-centered .freebie-bullets {
            max-width: 600px;
            margin: 40px auto;
        }
        
        .layout-centered .freebie-bullet {
            text-align: left;
        }
        
        /* Layout: Sidebar */
        .layout-sidebar .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        @media (max-width: 1024px) {
            .layout-hybrid .grid,
            .layout-sidebar .grid {
                grid-template-columns: 1fr;
            }
            
            .freebie-content {
                padding: 60px 40px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header,
            .preview-wrapper {
                padding: 20px;
            }
            
            .freebie-content {
                padding: 40px 20px;
            }
            
            .freebie-headline {
                font-size: <?php echo max(24, (int)$headingFontSize - 8); ?>px;
            }
            
            .action-bar {
                flex-direction: column;
            }
            
            .action-button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="dashboard.php?page=freebies" class="back-button">
                ← Zurück zur Übersicht
            </a>
            <h1>👁️ Freebie Vorschau</h1>
            <p>Template: <?php echo htmlspecialchars($freebie['template_name'] ?? 'Custom'); ?></p>
            
            <div class="action-bar">
                <a href="edit-freebie.php?id=<?php echo $freebie['id']; ?>" class="action-button action-primary">
                    ✏️ Bearbeiten
                </a>
            </div>
        </div>
        
        <div class="preview-wrapper">
            <div class="freebie-content">
                <?php
                // Build content parts
                $preheadline_html = !empty($freebie['preheadline']) ? 
                    '<div class="freebie-preheadline">' . htmlspecialchars($freebie['preheadline']) . '</div>' : '';
                
                $headline_html = '<div class="freebie-headline">' . 
                    htmlspecialchars($freebie['headline'] ?? 'Freebie Headline') . '</div>';
                
                $subheadline_html = !empty($freebie['subheadline']) ? 
                    '<div class="freebie-subheadline">' . htmlspecialchars($freebie['subheadline']) . '</div>' : '';
                
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
                    $mockup_html = '<div class="freebie-mockup">
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
                        
                        $bullets_html = '<ul class="freebie-bullets">';
                        foreach ($bullets as $bullet) {
                            $bullets_html .= '<li class="freebie-bullet">';
                            if ($bulletIconStyle !== 'none') {
                                $bullets_html .= '<span class="bullet-icon">' . $icon . '</span>';
                            }
                            $bullets_html .= '<span>' . htmlspecialchars(trim($bullet)) . '</span>';
                            $bullets_html .= '</li>';
                        }
                        $bullets_html .= '</ul>';
                    }
                }
                
                // Form/CTA
                $cta_html = '';
                $animationClass = $ctaAnimation !== 'none' ? 'btn-' . $ctaAnimation : '';
                
                if ($optinDisplayMode === 'direct' && !empty($freebie['raw_code'])) {
                    $cta_html = '<div class="freebie-form">' . $freebie['raw_code'] . '</div>';
                } else {
                    $ctaText = $freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN';
                    $cta_html = '<div style="margin-top: 24px;">
                        <a href="#" class="freebie-cta ' . $animationClass . '">' . 
                        htmlspecialchars($ctaText) . '</a>
                    </div>';
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
                    echo $cta_html;
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
            </div>
        </div>
    </div>
</body>
</html>
