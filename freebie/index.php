<?php
/**
 * Freebie Public Page - Optimiertes Layout mit Cookie-Banner
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DIREKTE DB-VERBINDUNG
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
    die('DB Connection Error');
}

$identifier = $_GET['id'] ?? null;

if (!$identifier) {
    http_response_code(404);
    die('No Freebie ID provided');
}

// Find the freebie
$customer_id = null;
try {
    $stmt = $pdo->prepare("SELECT cf.*, u.id as customer_id FROM customer_freebies cf LEFT JOIN users u ON cf.customer_id = u.id WHERE cf.unique_id = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'] ?? null;
    } else {
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database Error');
}

if (!$freebie) {
    http_response_code(404);
    die('Freebie not found');
}

// Get values with defaults
$primaryColor = $freebie['primary_color'] ?? '#5b8def';
$backgroundColor = $freebie['background_color'] ?? '#f8f9fc';
$preheadline = $freebie['preheadline'] ?? '';
$headline = $freebie['headline'] ?? 'Willkommen';
$subheadline = $freebie['subheadline'] ?? '';
$ctaText = $freebie['cta_text'] ?? 'JETZT KOSTENLOS DOWNLOADEN';
$mockupUrl = $freebie['mockup_image_url'] ?? '';

// Parse bullet points
$bulletPoints = [];
if (!empty($freebie['bullet_points'])) {
    $bulletPoints = array_filter(explode("\n", $freebie['bullet_points']));
}

// Footer links
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($headline); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?php echo htmlspecialchars($backgroundColor); ?>;
            min-height: 100vh;
            padding: 60px 20px;
        }
        
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        
        /* HEADER - ZENTRIERT */
        .header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .preheadline {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 20px;
            font-weight: 700;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        h1 {
            font-size: 48px;
            margin-bottom: 24px;
            font-weight: 800;
            line-height: 1.2;
            color: #1a202c;
        }
        
        .subheadline {
            font-size: 22px;
            line-height: 1.6;
            color: #4a5568;
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* MAIN CONTENT - MOCKUP + BULLETS */
        .main-content {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 60px;
            align-items: start;
            margin-bottom: 60px;
        }
        
        .mockup-container {
            text-align: center;
        }
        
        .mockup-image {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .content-container {
            display: flex;
            flex-direction: column;
        }
        
        .bullet-points {
            list-style: none;
            margin-bottom: 40px;
        }
        
        .bullet-points li {
            padding: 16px 0;
            position: relative;
            padding-left: 40px;
            font-size: 18px;
            line-height: 1.6;
            color: #2d3748;
        }
        
        .bullet-points li:before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 16px;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-weight: bold;
            font-size: 24px;
        }
        
        /* E-MAIL OPTIN + CTA */
        .optin-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .raw-code-container {
            margin-bottom: 20px;
        }
        
        .cta-button {
            display: block;
            width: 100%;
            padding: 20px 40px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 20px rgba(91, 141, 239, 0.3);
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(91, 141, 239, 0.4);
        }
        
        /* FOOTER */
        .footer {
            margin-top: 80px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .footer a {
            color: #718096;
            text-decoration: none;
            font-size: 14px;
            margin: 0 15px;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        /* COOKIE BANNER */
        .cookie-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(26, 32, 44, 0.98);
            backdrop-filter: blur(20px);
            padding: 24px;
            box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            transform: translateY(100%);
            transition: transform 0.4s ease-out;
            border-top: 3px solid <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        .cookie-banner.show {
            transform: translateY(0);
        }
        
        .cookie-banner.hidden {
            display: none;
        }
        
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .cookie-text {
            flex: 1;
            min-width: 280px;
        }
        
        .cookie-text h3 {
            color: white;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .cookie-text p {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .cookie-text a {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            text-decoration: underline;
        }
        
        .cookie-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .cookie-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .cookie-btn-accept {
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
        }
        
        .cookie-btn-accept:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(91, 141, 239, 0.6);
        }
        
        .cookie-btn-reject {
            background: transparent;
            color: #94a3b8;
            border: 1px solid #475569;
        }
        
        .cookie-btn-reject:hover {
            background: rgba(71, 85, 105, 0.2);
            color: white;
        }
        
        .cookie-btn-settings {
            background: transparent;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            border: 1px solid <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        .cookie-btn-settings:hover {
            background: rgba(91, 141, 239, 0.15);
        }
        
        /* RESPONSIVE */
        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .mockup-container {
                max-width: 400px;
                margin: 0 auto;
            }
        }
        
        @media (max-width: 768px) {
            body { padding: 40px 16px; }
            
            h1 { font-size: 36px; }
            
            .subheadline { font-size: 18px; }
            
            .header { margin-bottom: 40px; }
            
            .bullet-points li { font-size: 16px; }
            
            .optin-section { padding: 20px; }
            
            .cookie-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .cookie-actions {
                flex-direction: column;
            }
            
            .cookie-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <!-- HEADER - ZENTRIERT -->
        <div class="header">
            <?php if ($preheadline): ?>
                <div class="preheadline"><?php echo htmlspecialchars($preheadline); ?></div>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($headline); ?></h1>
            
            <?php if ($subheadline): ?>
                <p class="subheadline"><?php echo htmlspecialchars($subheadline); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- MAIN CONTENT - MOCKUP LINKS + BULLETS RECHTS -->
        <div class="main-content">
            
            <!-- MOCKUP -->
            <div class="mockup-container">
                <?php if ($mockupUrl): ?>
                    <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                <?php else: ?>
                    <div style="width: 100%; height: 400px; background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #667eea 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 80px; color: white;">
                        🎁
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- BULLETS + OPTIN -->
            <div class="content-container">
                
                <?php if (!empty($bulletPoints)): ?>
                    <ul class="bullet-points">
                        <?php foreach ($bulletPoints as $point): ?>
                            <?php $cleanPoint = preg_replace('/^[✓✔︎•-]\s*/', '', trim($point)); ?>
                            <li><?php echo htmlspecialchars($cleanPoint); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <!-- E-MAIL OPTIN / CTA -->
                <div class="optin-section">
                    <?php if (!empty($freebie['raw_code'])): ?>
                        <div class="raw-code-container">
                            <?php echo $freebie['raw_code']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <a href="#" class="cta-button">
                        <?php echo htmlspecialchars($ctaText); ?>
                    </a>
                </div>
                
            </div>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
            <span style="color: #cbd5e0;">•</span>
            <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
        </div>
        
    </div>
    
    <!-- COOKIE BANNER -->
    <div id="cookie-banner" class="cookie-banner">
        <div class="cookie-content">
            <div class="cookie-text">
                <h3>🍪 Wir respektieren deine Privatsphäre</h3>
                <p>
                    Wir verwenden Cookies, um dein Erlebnis zu verbessern und Inhalte zu personalisieren. 
                    <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Mehr erfahren</a>
                </p>
            </div>
            <div class="cookie-actions">
                <button onclick="rejectCookies()" class="cookie-btn cookie-btn-reject">
                    Nur notwendige
                </button>
                <button onclick="showCookieSettings()" class="cookie-btn cookie-btn-settings">
                    Einstellungen
                </button>
                <button onclick="acceptCookies()" class="cookie-btn cookie-btn-accept">
                    Alle akzeptieren
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Cookie Banner Management
        function acceptCookies() {
            localStorage.setItem('cookieConsent', 'accepted');
            hideCookieBanner();
            enableTracking();
        }
        
        function rejectCookies() {
            localStorage.setItem('cookieConsent', 'rejected');
            hideCookieBanner();
            disableTracking();
        }
        
        function showCookieSettings() {
            window.location.href = '<?php echo htmlspecialchars($datenschutz_link); ?>#cookie-einstellungen';
        }
        
        function hideCookieBanner() {
            const banner = document.getElementById('cookie-banner');
            if (banner) {
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.classList.add('hidden');
                }, 400);
            }
        }
        
        function enableTracking() {
            console.log('Tracking enabled');
            // Hier Tracking-Code aktivieren (Google Analytics, Facebook Pixel, etc.)
        }
        
        function disableTracking() {
            console.log('Tracking disabled');
            // Hier Tracking-Code deaktivieren
        }
        
        // Cookie-Banner bei Seite-Load prüfen
        document.addEventListener('DOMContentLoaded', function() {
            const consent = localStorage.getItem('cookieConsent');
            const banner = document.getElementById('cookie-banner');
            
            if (!consent && banner) {
                // Kurze Verzögerung für bessere UX
                setTimeout(() => {
                    banner.classList.add('show');
                }, 1000);
            } else if (consent === 'accepted') {
                enableTracking();
            }
        });
    </script>
</body>
</html>