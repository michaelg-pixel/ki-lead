<?php
/**
 * Freebie Public Page mit Cookie-Banner + REFERRAL-TRACKING + VIDEO-SUPPORT (inkl. YouTube Shorts)
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
    showError('Datenbankverbindung fehlgeschlagen', 'Bitte versuchen Sie es später erneut.');
}

// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../config/fonts.php';

$identifier = $_GET['id'] ?? null;
if (!$identifier) { 
    showError('Keine Freebie-ID angegeben', 'Der Link ist unvollständig. Bitte verwenden Sie den korrekten Link.');
}

// REFERRAL TRACKING
$ref_code = isset($_GET['ref']) ? trim($_GET['ref']) : null;
$customer_param = isset($_GET['customer']) ? intval($_GET['customer']) : null;

$customer_id = null;
$freebie_db_id = null;
$template = null;

try {
    // FIX: mockup_image_url bevorzugt aus customer_freebies, fallback zu freebies Template
    // VIDEO-SUPPORT: video_url und video_format hinzugefügt
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.id as customer_id,
            f.preheadline_font as template_preheadline_font,
            f.preheadline_size as template_preheadline_size,
            f.headline_font as template_headline_font,
            f.headline_size as template_headline_size,
            f.subheadline_font as template_subheadline_font,
            f.subheadline_size as template_subheadline_size,
            f.bulletpoints_font as template_bulletpoints_font,
            f.bulletpoints_size as template_bulletpoints_size,
            COALESCE(cf.mockup_image_url, f.mockup_image_url) as mockup_image_url,
            COALESCE(cf.video_url, f.video_url) as video_url,
            COALESCE(cf.video_format, f.video_format, 'widescreen') as video_format,
            COALESCE(cf.preheadline_font, f.preheadline_font) as preheadline_font,
            COALESCE(cf.preheadline_size, f.preheadline_size) as preheadline_size,
            COALESCE(cf.headline_font, f.headline_font) as headline_font,
            COALESCE(cf.headline_size, f.headline_size) as headline_size,
            COALESCE(cf.subheadline_font, f.subheadline_font) as subheadline_font,
            COALESCE(cf.subheadline_size, f.subheadline_size) as subheadline_size,
            COALESCE(cf.bulletpoints_font, f.bulletpoints_font) as bulletpoints_font,
            COALESCE(cf.bulletpoints_size, f.bulletpoints_size) as bulletpoints_size
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        LEFT JOIN freebies f ON cf.template_id = f.id 
        WHERE cf.unique_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        $template = $freebie; // Bei Master Templates ist das Freebie selbst das Template
    } else {
        $customer_id = $freebie['customer_id'] ?? null;
        $freebie_db_id = $freebie['id'] ?? null;
    }
} catch (PDOException $e) {
    showError('Datenbankfehler', 'Beim Laden des Freebies ist ein Fehler aufgetreten.');
}

if (!$freebie) { 
    showError(
        'Freebie nicht gefunden', 
        'Das angeforderte Freebie existiert nicht oder wurde entfernt.',
        'Verwendete ID: ' . htmlspecialchars($identifier)
    );
}

// Wenn customer-Parameter übergeben wurde, verwende diesen
if ($customer_param) {
    $customer_id = $customer_param;
}

$primaryColor = $freebie['primary_color'] ?? '#5b8def';
$backgroundColor = $freebie['background_color'] ?? '#f8f9fc';
$preheadline = $freebie['preheadline'] ?? '';
$headline = $freebie['headline'] ?? 'Willkommen';
$subheadline = $freebie['subheadline'] ?? '';
$ctaText = $freebie['cta_text'] ?? 'JETZT KOSTENLOS DOWNLOADEN';
$mockupUrl = $freebie['mockup_image_url'] ?? '';
$videoUrl = $freebie['video_url'] ?? '';
$videoFormat = $freebie['video_format'] ?? 'widescreen';
$layout = $freebie['layout'] ?? 'hybrid';

// Font-Einstellungen aus DB mit Fallback auf Defaults
$preheadlineFont = $freebie['preheadline_font'] ?? $fontConfig['defaults']['preheadline_font'];
$preheadlineSize = $freebie['preheadline_size'] ?? $fontConfig['defaults']['preheadline_size'];
$headlineFont = $freebie['headline_font'] ?? $fontConfig['defaults']['headline_font'];
$headlineSize = $freebie['headline_size'] ?? $fontConfig['defaults']['headline_size'];
$subheadlineFont = $freebie['subheadline_font'] ?? $fontConfig['defaults']['subheadline_font'];
$subheadlineSize = $freebie['subheadline_size'] ?? $fontConfig['defaults']['subheadline_size'];
$bulletpointsFont = $freebie['bulletpoints_font'] ?? $fontConfig['defaults']['bulletpoints_font'];
$bulletpointsSize = $freebie['bulletpoints_size'] ?? $fontConfig['defaults']['bulletpoints_size'];

$bulletPoints = [];
if (!empty($freebie['bullet_points'])) {
    $bulletPoints = array_filter(explode("\n", $freebie['bullet_points']));
}

$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

// Speichere Referral-Code für Later Use
$referral_code_to_pass = $ref_code;

// Video Embed URL Konvertierung (PHP-Funktion) - inkl. YouTube Shorts Support
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube (watch, youtu.be, shorts)
    if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    return null;
}

$videoEmbedUrl = getVideoEmbedUrl($videoUrl);

// Hilfsfunktion für Fehleranzeige
function showError($title, $message, $details = '') {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fehler - Freebie</title>
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
            .error-container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 24px;
                color: #1a202c;
                margin-bottom: 12px;
            }
            p {
                color: #4a5568;
                line-height: 1.6;
                margin-bottom: 8px;
            }
            .details {
                background: #f7fafc;
                padding: 12px;
                border-radius: 8px;
                font-size: 13px;
                color: #718096;
                margin-top: 20px;
                word-break: break-all;
            }
            .back-btn {
                display: inline-block;
                margin-top: 24px;
                padding: 12px 24px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: all 0.3s;
            }
            .back-btn:hover {
                background: #5568d3;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <?php if ($details): ?>
                <div class="details"><?php echo $details; ?></div>
            <?php endif; ?>
            <a href="javascript:history.back()" class="back-btn">← Zurück</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>