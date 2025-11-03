<?php
/**
 * Freebie Public Page mit Cookie-Banner + REFERRAL-TRACKING
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

$identifier = $_GET['id'] ?? null;
if (!$identifier) { 
    showError('Keine Freebie-ID angegeben', 'Der Link ist unvollständig. Bitte verwenden Sie den korrekten Link.');
}

// REFERRAL TRACKING
$ref_code = isset($_GET['ref']) ? trim($_GET['ref']) : null;
$customer_param = isset($_GET['customer']) ? intval($_GET['customer']) : null;

$customer_id = null;
$freebie_db_id = null;

try {
    // FIX: mockup_image_url bevorzugt aus customer_freebies, fallback zu freebies Template
    $stmt = $pdo->prepare("
        SELECT cf.*, u.id as customer_id, COALESCE(cf.mockup_image_url, f.mockup_image_url) as mockup_image_url 
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
$layout = $freebie['layout'] ?? 'hybrid';

$bulletPoints = [];
if (!empty($freebie['bullet_points'])) {
    $bulletPoints = array_filter(explode("\n", $freebie['bullet_points']));
}

$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

// Speichere Referral-Code für Later Use
$referral_code_to_pass = $ref_code;

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
            padding: 20px 20px 15px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 25px; }
        .preheadline {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
            font-weight: 700;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 800;
            line-height: 1.2;
            color: #1a202c;
        }
        .subheadline {
            font-size: 15px;
            line-height: 1.4;
            color: #4a5568;
            max-width: 650px;
            margin: 0 auto;
        }
        
        /* HYBRID & SIDEBAR LAYOUTS */
        .main-content {
            display: grid;
            gap: 35px;
            align-items: start;
            margin-bottom: 25px;
        }
        .main-content.layout-hybrid {
            grid-template-columns: 40% 60%;
        }
        .main-content.layout-sidebar {
            grid-template-columns: 60% 40%;
        }
        
        /* CENTERED LAYOUT */
        .main-content.layout-centered {
            display: flex;
            flex-direction: column;
            align-items: center;
            max-width: 700px;
            margin: 0 auto 25px;
        }
        .layout-centered .mockup-container {
            margin-bottom: 30px;
        }
        .layout-centered .bullet-points {
            text-align: left;
            width: 100%;
        }
        .layout-centered .optin-section {
            width: 100%;
            max-width: 500px;
        }
        
        .mockup-container { 
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mockup-image {
            max-width: 260px;
            height: auto;
            display: block;
        }
        .content-container { display: flex; flex-direction: column; }
        .bullet-points { list-style: none; margin-bottom: 20px; }
        .bullet-points li {
            padding: 6px 0;
            position: relative;
            padding-left: 28px;
            font-size: 15px;
            line-height: 1.4;
            color: #2d3748;
        }
        .bullet-points li:before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 6px;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-weight: bold;
            font-size: 19px;
        }
        .optin-section {
            background: white;
            padding: 22px;
            border-radius: 8px;
            max-width: 70%;
        }
        
        /* Styled Form Elemente */
        .optin-section form { display: flex; flex-direction: column; gap: 11px; }
        .optin-section form > div > div { display: flex; flex-direction: column; gap: 11px; }
        .optin-section label { display: none; }
        .optin-section input[type="text"],
        .optin-section input[type="email"] {
            width: 100%;
            padding: 11px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s;
            background: white;
        }
        .optin-section input:focus {
            outline: none;
            border-color: <?php echo htmlspecialchars($primaryColor); ?>;
            box-shadow: 0 0 0 3px rgba(91, 141, 239, 0.1);
        }
        .optin-section button[type="submit"] {
            width: 100%;
            padding: 13px 32px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Inter', sans-serif;
        }
        .optin-section button[type="submit"]:hover {
            transform: translateY(-2px);
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            filter: brightness(1.1);
        }
        
        .cta-button {
            display: block;
            width: 100%;
            padding: 13px 32px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cta-button:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        .footer {
            margin-top: 30px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .footer a {
            color: #718096;
            text-decoration: none;
            font-size: 12px;
            margin: 0 10px;
            transition: color 0.3s;
        }
        .footer a:hover { color: <?php echo htmlspecialchars($primaryColor); ?>; }
        
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
        .cookie-banner.show { transform: translateY(0); }
        .cookie-banner.hidden { display: none; }
        .cookie-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: wrap;
        }
        .cookie-text { flex: 1; min-width: 280px; }
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
        .cookie-actions { display: flex; gap: 12px; flex-wrap: wrap; }
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
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .cookie-btn-settings:hover { 
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        /* COOKIE SETTINGS MODAL */
        .cookie-settings-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .cookie-settings-modal.show { display: flex; }
        .cookie-settings-content {
            background: white;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .cookie-settings-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cookie-settings-header h2 {
            font-size: 22px;
            color: #1a202c;
            font-weight: 700;
        }
        .cookie-close-btn {
            background: none;
            border: none;
            font-size: 24px;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .cookie-close-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }
        .cookie-settings-body {
            padding: 24px;
        }
        .cookie-category {
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .cookie-category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .cookie-category-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a202c;
        }
        .cookie-category-desc {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: 0.3s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(24px);
        }
        input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .cookie-settings-footer {
            padding: 20px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        .btn-settings-save {
            padding: 12px 32px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-settings-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(91, 141, 239, 0.4);
        }
        
        /* ========================================
           MOBILE RESPONSIVE - STANDARDISIERTES LAYOUT
           Reihenfolge für ALLE Freebies in mobiler Ansicht:
           1. Preheadline
           2. Headline
           3. Subheadline
           4. Mockup
           5. Bulletpoints (OHNE Haken-Icons)
           6. Email Optin + Button
           7. Footer
        ======================================== */
        @media (max-width: 968px) {
            /* Alle Layout-Typen werden zu einer einheitlichen Spalte */
            .main-content.layout-hybrid,
            .main-content.layout-sidebar,
            .main-content.layout-centered { 
                display: flex !important;
                flex-direction: column !important;
                grid-template-columns: none !important;
                gap: 25px;
                align-items: center;
                max-width: 100%;
                margin: 0 auto 25px;
            }
            
            /* Mockup erscheint nach Header (Order 1) */
            .mockup-container {
                order: 1;
                width: 100%;
                max-width: 320px;
                margin: 0 auto 10px;
            }
            
            .mockup-container .mockup-image { 
                max-width: 100%;
                height: auto;
                margin: 0 auto;
                display: block;
            }
            
            /* Content Container erscheint nach Mockup (Order 2) */
            .content-container {
                order: 2;
                width: 100%;
                max-width: 100%;
            }
            
            /* Bulletpoints OHNE Haken-Icons - einfache Liste */
            .bullet-points {
                list-style: disc inside;
                text-align: left;
                width: 100%;
                margin-bottom: 20px;
                padding-left: 0;
            }
            
            .bullet-points li {
                padding: 8px 0 8px 0 !important;
                padding-left: 0 !important;
                font-size: 14px;
                line-height: 1.6;
                color: #2d3748;
                position: relative;
                margin-left: 20px;
            }
            
            /* Haken-Icons komplett entfernen in mobiler Ansicht */
            .bullet-points li:before {
                display: none !important;
                content: "" !important;
            }
            
            /* Optin Section volle Breite in Mobile */
            .optin-section { 
                max-width: 100% !important;
                width: 100%;
                padding: 20px;
            }
            
            .optin-section input[type="text"],
            .optin-section input[type="email"] {
                font-size: 16px;
                padding: 12px 16px;
            }
            
            .optin-section button[type="submit"],
            .cta-button {
                padding: 14px 24px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 768px) {
            body { padding: 20px 16px 15px; }
            
            h1 { 
                font-size: 26px;
                line-height: 1.3;
            }
            
            .preheadline {
                font-size: 10px;
                letter-spacing: 1.2px;
            }
            
            .subheadline { 
                font-size: 14px;
                line-height: 1.5;
            }
            
            .header { margin-bottom: 20px; }
            
            .mockup-container {
                max-width: 280px;
            }
            
            .bullet-points li {
                font-size: 13px;
                padding: 7px 0 7px 0 !important;
            }
            
            .optin-section { 
                padding: 18px;
            }
            
            .footer {
                margin-top: 25px;
                padding-top: 15px;
            }
            
            .footer a {
                font-size: 11px;
                margin: 0 8px;
            }
            
            /* Cookie Banner Mobile */
            .cookie-content { 
                flex-direction: column; 
                align-items: stretch;
                gap: 16px;
            }
            
            .cookie-text h3 {
                font-size: 16px;
            }
            
            .cookie-text p {
                font-size: 13px;
            }
            
            .cookie-actions { 
                flex-direction: column;
                gap: 10px;
            }
            
            .cookie-btn { 
                width: 100%;
                justify-content: center;
                padding: 11px 20px;
            }
            
            .cookie-settings-footer { 
                flex-direction: column;
            }
            
            .btn-settings-save { 
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            body { padding: 16px 12px; }
            
            h1 { 
                font-size: 22px;
            }
            
            .subheadline {
                font-size: 13px;
            }
            
            .mockup-container {
                max-width: 240px;
            }
            
            .bullet-points {
                margin-bottom: 16px;
            }
            
            .bullet-points li {
                font-size: 13px;
                padding: 6px 0 6px 0 !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if ($preheadline): ?>
                <div class="preheadline"><?php echo htmlspecialchars($preheadline); ?></div>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($headline); ?></h1>
            <?php if ($subheadline): ?>
                <p class="subheadline"><?php echo htmlspecialchars($subheadline); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if ($layout === 'centered'): ?>
            <!-- ZENTRIERTES LAYOUT -->
            <div class="main-content layout-centered">
                <div class="mockup-container">
                    <?php if ($mockupUrl): ?>
                        <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                    <?php else: ?>
                        <div style="width:260px;height:300px;background:linear-gradient(135deg,<?php echo htmlspecialchars($primaryColor); ?> 0%,#667eea 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:60px;color:white;">🎁</div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($bulletPoints)): ?>
                    <ul class="bullet-points">
                        <?php foreach ($bulletPoints as $point): ?>
                            <?php $cleanPoint = preg_replace('/^[✓✔︎•-]\s*/', '', trim($point)); ?>
                            <li><?php echo htmlspecialchars($cleanPoint); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="optin-section" style="max-width: 500px;">
                    <?php if (!empty($freebie['raw_code'])): ?>
                        <div class="raw-code-container"><?php echo $freebie['raw_code']; ?></div>
                    <?php else: ?>
                        <a href="#" class="cta-button"><?php echo htmlspecialchars($ctaText); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($layout === 'sidebar'): ?>
            <!-- SIDEBAR LAYOUT (Content links, Mockup rechts) -->
            <div class="main-content layout-sidebar">
                <div class="content-container">
                    <?php if (!empty($bulletPoints)): ?>
                        <ul class="bullet-points">
                            <?php foreach ($bulletPoints as $point): ?>
                                <?php $cleanPoint = preg_replace('/^[✓✔︎•-]\s*/', '', trim($point)); ?>
                                <li><?php echo htmlspecialchars($cleanPoint); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <div class="optin-section">
                        <?php if (!empty($freebie['raw_code'])): ?>
                            <div class="raw-code-container"><?php echo $freebie['raw_code']; ?></div>
                        <?php else: ?>
                            <a href="#" class="cta-button"><?php echo htmlspecialchars($ctaText); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mockup-container">
                    <?php if ($mockupUrl): ?>
                        <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                    <?php else: ?>
                        <div style="width:260px;height:300px;background:linear-gradient(135deg,<?php echo htmlspecialchars($primaryColor); ?> 0%,#667eea 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:60px;color:white;">🎁</div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php else: ?>
            <!-- HYBRID LAYOUT (Mockup LINKS, Content RECHTS) -->
            <div class="main-content layout-hybrid">
                <div class="mockup-container">
                    <?php if ($mockupUrl): ?>
                        <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                    <?php else: ?>
                        <div style="width:260px;height:300px;background:linear-gradient(135deg,<?php echo htmlspecialchars($primaryColor); ?> 0%,#667eea 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:60px;color:white;">🎁</div>
                    <?php endif; ?>
                </div>
                
                <div class="content-container">
                    <?php if (!empty($bulletPoints)): ?>
                        <ul class="bullet-points">
                            <?php foreach ($bulletPoints as $point): ?>
                                <?php $cleanPoint = preg_replace('/^[✓✔︎•-]\s*/', '', trim($point)); ?>
                                <li><?php echo htmlspecialchars($cleanPoint); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <div class="optin-section">
                        <?php if (!empty($freebie['raw_code'])): ?>
                            <div class="raw-code-container"><?php echo $freebie['raw_code']; ?></div>
                        <?php else: ?>
                            <a href="#" class="cta-button"><?php echo htmlspecialchars($ctaText); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="footer">
            <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
            <span style="color:#cbd5e0;">•</span>
            <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
        </div>
    </div>
    
    <!-- COOKIE BANNER -->
    <div id="cookie-banner" class="cookie-banner">
        <div class="cookie-content">
            <div class="cookie-text">
                <h3>🍪 Wir respektieren deine Privatsphäre</h3>
                <p>Wir verwenden Cookies, um dein Erlebnis zu verbessern. <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Mehr erfahren</a></p>
            </div>
            <div class="cookie-actions">
                <button onclick="rejectCookies()" class="cookie-btn cookie-btn-reject">Ablehnen</button>
                <button onclick="showCookieSettings()" class="cookie-btn cookie-btn-settings">Einstellungen</button>
                <button onclick="acceptCookies()" class="cookie-btn cookie-btn-accept">Akzeptieren</button>
            </div>
        </div>
    </div>
    
    <!-- COOKIE SETTINGS MODAL -->
    <div id="cookie-settings-modal" class="cookie-settings-modal">
        <div class="cookie-settings-content">
            <div class="cookie-settings-header">
                <h2>Cookie-Einstellungen</h2>
                <button onclick="closeCookieSettings()" class="cookie-close-btn">×</button>
            </div>
            <div class="cookie-settings-body">
                <div class="cookie-category">
                    <div class="cookie-category-header">
                        <div class="cookie-category-title">Notwendige Cookies</div>
                        <label class="toggle-switch">
                            <input type="checkbox" checked disabled>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="cookie-category-desc">
                        Diese Cookies sind für die Grundfunktionen der Website erforderlich und können nicht deaktiviert werden.
                    </div>
                </div>
                
                <div class="cookie-category">
                    <div class="cookie-category-header">
                        <div class="cookie-category-title">Analyse-Cookies</div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="analytics-cookies">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="cookie-category-desc">
                        Diese Cookies helfen uns zu verstehen, wie Besucher mit unserer Website interagieren.
                    </div>
                </div>
                
                <div class="cookie-category">
                    <div class="cookie-category-header">
                        <div class="cookie-category-title">Marketing-Cookies</div>
                        <label class="toggle-switch">
                            <input type="checkbox" id="marketing-cookies">
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="cookie-category-desc">
                        Diese Cookies werden verwendet, um Werbung relevanter für Sie zu gestalten.
                    </div>
                </div>
            </div>
            <div class="cookie-settings-footer">
                <button onclick="saveCustomCookieSettings()" class="btn-settings-save">Einstellungen speichern</button>
            </div>
        </div>
    </div>
    
    <script>
        // ===== REFERRAL TRACKING =====
        const REFERRAL_CONFIG = {
            customerId: <?php echo json_encode($customer_id); ?>,
            refCode: <?php echo json_encode($referral_code_to_pass); ?>,
            freebieId: <?php echo json_encode($freebie_db_id); ?>
        };
        
        // Track Referral Click (wenn ref-Parameter vorhanden)
        if (REFERRAL_CONFIG.refCode && REFERRAL_CONFIG.customerId) {
            // Verhindere mehrfaches Tracking via LocalStorage
            const storageKey = 'referral_click_' + REFERRAL_CONFIG.refCode;
            const lastClick = localStorage.getItem(storageKey);
            const now = Date.now();
            
            // Nur tracken wenn letzter Klick > 24h her oder noch nie geklickt
            if (!lastClick || (now - parseInt(lastClick)) > 24 * 60 * 60 * 1000) {
                fetch('/api/referral/track-click.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: REFERRAL_CONFIG.customerId,
                        ref_code: REFERRAL_CONFIG.refCode,
                        referer: document.referrer
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✓ Referral-Klick getrackt');
                        localStorage.setItem(storageKey, now.toString());
                        
                        // Speichere ref für Danke-Seite
                        sessionStorage.setItem('pending_ref_code', REFERRAL_CONFIG.refCode);
                        sessionStorage.setItem('pending_ref_customer', REFERRAL_CONFIG.customerId);
                        sessionStorage.setItem('ref_click_time', now.toString());
                    }
                })
                .catch(err => console.error('Referral Tracking Error:', err));
            } else {
                console.log('⏭ Referral-Klick bereits getrackt (24h-Limit)');
                // Trotzdem für Danke-Seite speichern
                sessionStorage.setItem('pending_ref_code', REFERRAL_CONFIG.refCode);
                sessionStorage.setItem('pending_ref_customer', REFERRAL_CONFIG.customerId);
            }
        }
        
        // ===== FREEBIE CLICK TRACKING =====
        <?php if ($freebie_db_id && $customer_id): ?>
        (function() {
            const trackingData = {
                freebie_id: <?php echo json_encode($freebie_db_id); ?>,
                customer_id: <?php echo json_encode($customer_id); ?>
            };
            
            fetch('/api/track-freebie-click.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(trackingData)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Freebie Tracking:', data.success ? '✓ Tracked' : '✗ Failed');
            })
            .catch(error => {
                console.error('Tracking Error:', error);
            });
        })();
        <?php endif; ?>
        
        // ===== COOKIE FUNCTIONS =====
        function acceptCookies(){
            localStorage.setItem('cookieConsent','accepted');
            localStorage.setItem('analyticsCookies','true');
            localStorage.setItem('marketingCookies','true');
            hideCookieBanner();
            enableTracking();
        }
        
        function rejectCookies(){
            localStorage.setItem('cookieConsent','rejected');
            localStorage.setItem('analyticsCookies','false');
            localStorage.setItem('marketingCookies','false');
            hideCookieBanner();
            disableTracking();
        }
        
        function showCookieSettings(){
            document.getElementById('cookie-settings-modal').classList.add('show');
            const analytics = localStorage.getItem('analyticsCookies') === 'true';
            const marketing = localStorage.getItem('marketingCookies') === 'true';
            document.getElementById('analytics-cookies').checked = analytics;
            document.getElementById('marketing-cookies').checked = marketing;
        }
        
        function closeCookieSettings(){
            document.getElementById('cookie-settings-modal').classList.remove('show');
        }
        
        function saveCustomCookieSettings(){
            const analytics = document.getElementById('analytics-cookies').checked;
            const marketing = document.getElementById('marketing-cookies').checked;
            
            localStorage.setItem('cookieConsent','custom');
            localStorage.setItem('analyticsCookies', analytics ? 'true' : 'false');
            localStorage.setItem('marketingCookies', marketing ? 'true' : 'false');
            
            closeCookieSettings();
            hideCookieBanner();
            
            if(analytics || marketing){
                enableTracking();
            } else {
                disableTracking();
            }
        }
        
        function hideCookieBanner(){
            const b=document.getElementById('cookie-banner');
            if(b){
                b.classList.remove('show');
                setTimeout(()=>b.classList.add('hidden'),400);
            }
        }
        
        function enableTracking(){
            const analytics = localStorage.getItem('analyticsCookies') === 'true';
            const marketing = localStorage.getItem('marketingCookies') === 'true';
            console.log('Tracking enabled - Analytics:', analytics, 'Marketing:', marketing);
        }
        
        function disableTracking(){
            console.log('Tracking disabled');
        }
        
        // Auto-add placeholders to form inputs
        document.addEventListener('DOMContentLoaded',function(){
            const c=localStorage.getItem('cookieConsent');
            const b=document.getElementById('cookie-banner');
            if(!c&&b){
                setTimeout(()=>b.classList.add('show'),1000);
            }
            else if(c==='accepted' || c==='custom'){
                enableTracking();
            }
            
            // Add placeholders automatically
            const firstNameInput = document.querySelector('input[name="first_name"]');
            const emailInput = document.querySelector('input[name="mail"]');
            if(firstNameInput && !firstNameInput.placeholder) firstNameInput.placeholder = 'Vorname';
            if(emailInput && !emailInput.placeholder) emailInput.placeholder = 'E-Mail';
        });
    </script>
</body>
</html>
