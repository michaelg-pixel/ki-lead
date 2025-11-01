<?php
/**
 * Freebie Public Page - OHNE AUTH-CHECK
 * URL: /freebie/{unique_id} oder /freebie/index.php?id={unique_id}
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// DIREKTE DB-VERBINDUNG (keine includes die Auth-Check haben könnten)
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

// Get the identifier from URL
$identifier = $_GET['id'] ?? null;

// Fallback: Parse from REQUEST_URI for clean URLs
if (!$identifier) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $path = parse_url($requestUri, PHP_URL_PATH);
    $segments = explode('/', trim($path, '/'));
    
    foreach ($segments as $index => $segment) {
        if ($segment === 'freebie' && isset($segments[$index + 1])) {
            $identifier = $segments[$index + 1];
            break;
        }
    }
}

// Error if no identifier
if (!$identifier) {
    http_response_code(404);
    die('No Freebie ID provided');
}

// Find the freebie
$customer_id = null;
try {
    // Check customer_freebies first
    $stmt = $pdo->prepare("SELECT cf.*, u.id as customer_id FROM customer_freebies cf LEFT JOIN users u ON cf.customer_id = u.id WHERE cf.unique_id = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'] ?? null;
    } else {
        // Check template freebies
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
$primaryColor = $freebie['primary_color'] ?? '#8B5CF6';
$backgroundColor = $freebie['background_color'] ?? '#FFFFFF';

// Parse bullet points
$bulletPoints = [];
if (!empty($freebie['bullet_points'])) {
    $bulletPoints = array_filter(explode("\n", $freebie['bullet_points']));
}

// Footer links with customer_id if available
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['headline'] ?? 'Freebie'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?php echo htmlspecialchars($backgroundColor); ?>;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #667eea 100%);
            padding: 60px 40px;
            text-align: center;
            color: white;
        }
        
        .preheadline {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            opacity: 0.9;
            margin-bottom: 16px;
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
        
        .bullet-points {
            list-style: none;
            margin-bottom: 35px;
        }
        
        .bullet-points li {
            padding: 14px 0 14px 40px;
            position: relative;
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
        }
        
        .bullet-points li:before {
            content: "✓";
            position: absolute;
            left: 0;
            top: 14px;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-weight: bold;
            font-size: 22px;
        }
        
        .cta-button {
            display: inline-block;
            width: 100%;
            padding: 18px 24px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.4);
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        .footer {
            background: #f9fafb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .footer a {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            margin: 0 12px;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        .raw-code-container {
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 28px;
            }
            .header, .content {
                padding: 40px 24px;
            }
        }
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
            <?php if (!empty($bulletPoints)): ?>
                <ul class="bullet-points">
                    <?php foreach ($bulletPoints as $point): ?>
                        <?php $cleanPoint = preg_replace('/^[✓✔︎•-]\s*/', '', trim($point)); ?>
                        <li><?php echo htmlspecialchars($cleanPoint); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <?php if (!empty($freebie['raw_code'])): ?>
                <div class="raw-code-container">
                    <?php echo $freebie['raw_code']; ?>
                </div>
            <?php else: ?>
                <a href="#" class="cta-button">
                    <?php echo htmlspecialchars($freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN'); ?>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
            <span style="color: #d1d5db;">•</span>
            <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
        </div>
    </div>
</body>
</html>