<?php
/**
 * Freebie Preview Page
 * URL: /freebie/{unique_id} oder /freebie/{url_slug}
 * Diese Datei gehört in: /htdocs/app.mehr-infos-jetzt.de/freebie/index.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the identifier from URL
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));

// Find the identifier after 'freebie'
$identifier = null;
foreach ($segments as $index => $segment) {
    if ($segment === 'freebie' && isset($segments[$index + 1])) {
        $identifier = $segments[$index + 1];
        break;
    }
}

// Fallback: check for query parameter
if (!$identifier && isset($_GET['id'])) {
    $identifier = $_GET['id'];
}

// Error if no identifier found
if (!$identifier) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Fehler</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; background: #1a1a2e; color: white; text-align: center; }
            a { color: #667eea; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>❌ Keine Freebie-ID gefunden</h1>
        <p><strong>Request URI:</strong> ' . htmlspecialchars($requestUri) . '</p>
        <p><strong>Erwartet:</strong> /freebie/{unique_id}</p>
        <hr>
        <a href="/customer/dashboard.php?page=freebies">← Zurück zu Freebies</a>
    </body>
    </html>
    ');
}

// Database connection
require_once __DIR__ . '/../config/database.php';

// Check database connection
if (!isset($pdo) || !$pdo) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Datenbankfehler</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; background: #1a1a2e; color: white; text-align: center; }
            a { color: #667eea; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>❌ Datenbankverbindung fehlgeschlagen</h1>
        <p>Bitte überprüfen Sie die Konfiguration in <code>/config/database.php</code></p>
        <hr>
        <a href="/customer/dashboard.php?page=freebies">← Zurück zu Freebies</a>
    </body>
    </html>
    ');
}

// Find the freebie
try {
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Datenbankfehler</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; background: #1a1a2e; color: white; text-align: center; }
            a { color: #667eea; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>❌ Datenbankfehler</h1>
        <p><strong>Fehler:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
        <hr>
        <a href="/customer/dashboard.php?page=freebies">← Zurück zu Freebies</a>
    </body>
    </html>
    ');
}

// Check if freebie exists
if (!$freebie) {
    die('
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <title>Nicht gefunden</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; background: #1a1a2e; color: white; text-align: center; }
            a { color: #667eea; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>
        <h1>❌ Freebie nicht gefunden</h1>
        <p><strong>Gesucht nach:</strong> ' . htmlspecialchars($identifier) . '</p>
        <p>Dieses Freebie existiert nicht in der Datenbank.</p>
        <hr>
        <a href="/customer/dashboard.php?page=freebies">← Zurück zu Freebies</a>
    </body>
    </html>
    ');
}

// Handle form submission
$leadCaptured = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['capture_lead'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errorMessage = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else {
        try {
            // Insert lead
            $leadStmt = $pdo->prepare("INSERT INTO leads (freebie_id, email, ip_address, created_at) VALUES (?, ?, ?, NOW())");
            $leadStmt->execute([$freebie['id'], $email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            // Update usage count
            $updateStmt = $pdo->prepare("UPDATE freebies SET usage_count = COALESCE(usage_count, 0) + 1 WHERE id = ?");
            $updateStmt->execute([$freebie['id']]);
            
            $leadCaptured = true;
        } catch (PDOException $e) {
            // If leads table doesn't exist, still show success
            $leadCaptured = true;
        }
    }
}

// Get values with defaults
$primaryColor = $freebie['primary_color'] ?? '#7C3AED';
$secondaryColor = $freebie['secondary_color'] ?? '#EC4899';
$backgroundColor = $freebie['background_color'] ?? '#FFFFFF';
$textColor = $freebie['text_color'] ?? '#1F2937';
$ctaButtonColor = $freebie['cta_button_color'] ?? '#5B8DEF';
$headingFont = $freebie['heading_font'] ?? 'Inter';
$bodyFont = $freebie['body_font'] ?? 'Inter';

// Parse bullet points
$bulletPoints = [];
if (!empty($freebie['bullet_points'])) {
    $decoded = json_decode($freebie['bullet_points'], true);
    if (is_array($decoded)) {
        $bulletPoints = $decoded;
    } else {
        $bulletPoints = array_filter(explode("\n", $freebie['bullet_points']));
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['headline'] ?? 'Freebie'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <?php if (!empty($freebie['pixel_code'])): ?>
        <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '<?php echo $bodyFont; ?>', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, <?php echo $secondaryColor; ?> 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        
        h1, h2, h3 {
            font-family: '<?php echo $headingFont; ?>', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .container {
            background: white;
            max-width: 650px;
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, <?php echo $secondaryColor; ?> 100%);
            padding: 60px 40px;
            text-align: center;
            color: white;
        }
        
        .preheadline {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            opacity: 0.9;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        h1 {
            font-size: 38px;
            margin-bottom: 16px;
            font-weight: 800;
            line-height: 1.2;
        }
        
        .subheadline {
            font-size: 18px;
            opacity: 0.95;
            line-height: 1.6;
        }
        
        .content {
            padding: 45px 40px;
        }
        
        .description {
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.8;
            color: #4a5568;
        }
        
        .bullet-points {
            list-style: none;
            margin-bottom: 35px;
        }
        
        .bullet-points li {
            padding: 14px 0 14px 40px;
            position: relative;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .bullet-points li:before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 14px;
            color: <?php echo $primaryColor; ?>;
            font-weight: bold;
            font-size: 22px;
        }
        
        .form-container {
            background: #f8fafc;
            padding: 45px 40px;
            border-radius: 16px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s;
            font-family: inherit;
            margin-bottom: 20px;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: <?php echo $primaryColor; ?>;
            box-shadow: 0 0 0 3px <?php echo $primaryColor; ?>20;
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px 24px;
            background: <?php echo $ctaButtonColor; ?>;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px <?php echo $ctaButtonColor; ?>40;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px <?php echo $ctaButtonColor; ?>50;
        }
        
        .success-message {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #34d399;
            color: #065f46;
            padding: 40px;
            border-radius: 16px;
            text-align: center;
        }
        
        .success-icon {
            font-size: 72px;
            margin-bottom: 20px;
        }
        
        .success-message h2 {
            font-size: 30px;
            margin-bottom: 16px;
        }
        
        .success-message p {
            font-size: 17px;
            margin-bottom: 28px;
        }
        
        .download-link {
            display: inline-block;
            padding: 18px 45px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }
        
        .download-link:hover {
            transform: translateY(-3px);
        }
        
        .error-message {
            background: #fee2e2;
            border: 2px solid #f87171;
            color: #991b1b;
            padding: 16px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .privacy-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 16px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 32px;
            }
            
            .header, .content {
                padding: 40px 30px;
            }
            
            .form-container {
                padding: 35px 25px;
            }
        }
        
        <?php if (!empty($freebie['custom_css'])): ?>
        <?php echo $freebie['custom_css']; ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (!empty($freebie['preheadline'])): ?>
                <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($freebie['headline']); ?></h1>
            
            <?php if (!empty($freebie['subheadline'])): ?>
                <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="content">
            <?php if ($leadCaptured): ?>
                <div class="success-message">
                    <div class="success-icon">✅</div>
                    <h2>Vielen Dank!</h2>
                    <p>Ihre Anmeldung war erfolgreich.</p>
                    <?php if (!empty($freebie['freebie_url'])): ?>
                        <a href="<?php echo htmlspecialchars($freebie['freebie_url']); ?>" 
                           class="download-link" 
                           target="_blank">
                            📥 Jetzt herunterladen
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if ($errorMessage): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($freebie['description'])): ?>
                    <div class="description">
                        <?php echo nl2br(htmlspecialchars($freebie['description'])); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($bulletPoints)): ?>
                    <ul class="bullet-points">
                        <?php foreach ($bulletPoints as $point): ?>
                            <li><?php echo htmlspecialchars(is_array($point) ? ($point['text'] ?? '') : $point); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                
                <div class="form-container">
                    <form method="POST" action="">
                        <input type="email" 
                               name="email" 
                               required 
                               placeholder="<?php echo htmlspecialchars($freebie['optin_placeholder_email'] ?? 'Deine E-Mail-Adresse'); ?>"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        
                        <button type="submit" name="capture_lead" class="submit-btn">
                            <?php echo htmlspecialchars($freebie['optin_button_text'] ?? 'KOSTENLOS DOWNLOADEN'); ?>
                        </button>
                        
                        <?php if (!empty($freebie['optin_privacy_text'])): ?>
                            <div class="privacy-text">
                                <?php echo htmlspecialchars($freebie['optin_privacy_text']); ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>