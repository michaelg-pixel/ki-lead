<?php
/**
 * Freebie Public Page mit allen Layout-Varianten
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
$layout = $freebie['layout'] ?? 'hybrid';
$primaryColor = $freebie['primary_color'] ?? '#ed8936';
$backgroundColor = $freebie['background_color'] ?? '#FFFFFF';
$preheadline = $freebie['preheadline'] ?? '';
$headline = $freebie['headline'] ?? 'Willkommen';
$subheadline = $freebie['subheadline'] ?? '';
$ctaText = $freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN';
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
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* CENTERED LAYOUT */
        .layout-centered {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .layout-centered .header {
            background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #667eea 100%);
            padding: 60px 40px;
            text-align: center;
            color: white;
        }
        
        .layout-centered .content {
            padding: 45px 40px;
            text-align: center;
        }
        
        .layout-centered .mockup-container {
            text-align: center;
            margin-bottom: 40px;
        }
        
        /* HYBRID LAYOUT */
        .layout-hybrid {
            background: white;
            border-radius: 24px;
            padding: 60px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .layout-hybrid .hybrid-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .layout-hybrid .mockup-side {
            text-align: center;
        }
        
        .layout-hybrid .content-side {
            text-align: left;
        }
        
        /* SIDEBAR LAYOUT */
        .layout-sidebar {
            background: white;
            border-radius: 24px;
            padding: 60px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .layout-sidebar .sidebar-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .layout-sidebar .content-side {
            text-align: left;
        }
        
        .layout-sidebar .mockup-side {
            text-align: center;
        }
        
        /* GEMEINSAME ELEMENTE */
        .preheadline {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 16px;
            font-weight: 700;
            color: <?php echo htmlspecialchars($primaryColor); ?>;
        }
        
        .layout-centered .preheadline {
            color: rgba(255, 255, 255, 0.9);
        }
        
        h1 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 800;
            line-height: 1.2;
            color: #1a1a2e;
        }
        
        .layout-centered h1 {
            color: white;
        }
        
        .subheadline {
            font-size: 20px;
            margin-bottom: 32px;
            line-height: 1.6;
            color: #6b7280;
        }
        
        .layout-centered .subheadline {
            color: rgba(255, 255, 255, 0.95);
        }
        
        .mockup-image {
            max-width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .bullet-points {
            list-style: none;
            margin-bottom: 40px;
        }
        
        .bullet-points li {
            padding: 14px 0 14px 40px;
            position: relative;
            font-size: 17px;
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
            font-size: 24px;
        }
        
        .layout-centered .bullet-points {
            text-align: left;
            display: inline-block;
        }
        
        .cta-button {
            display: inline-block;
            padding: 20px 50px;
            background: <?php echo htmlspecialchars($primaryColor); ?>;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.2);
        }
        
        .layout-hybrid .cta-button,
        .layout-sidebar .cta-button {
            width: 100%;
        }
        
        .raw-code-container {
            margin: 30px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 12px;
        }
        
        .footer {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        
        .layout-centered .footer {
            background: #f9fafb;
            margin-top: 0;
            border-top: none;
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
        
        @media (max-width: 768px) {
            body { padding: 20px 16px; }
            
            h1 { font-size: 32px; }
            
            .layout-hybrid .hybrid-grid,
            .layout-sidebar .sidebar-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .layout-hybrid .content-side,
            .layout-sidebar .content-side {
                text-align: center;
            }
            
            .layout-hybrid,
            .layout-sidebar {
                padding: 40px 24px;
            }
            
            .layout-centered .header,
            .layout-centered .content {
                padding: 40px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <?php if ($layout === 'centered'): ?>
            <!-- CENTERED LAYOUT -->
            <div class="layout-centered">
                <div class="header">
                    <?php if ($preheadline): ?>
                        <div class="preheadline"><?php echo htmlspecialchars($preheadline); ?></div>
                    <?php endif; ?>
                    
                    <h1><?php echo htmlspecialchars($headline); ?></h1>
                    
                    <?php if ($subheadline): ?>
                        <p class="subheadline"><?php echo htmlspecialchars($subheadline); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="content">
                    <?php if ($mockupUrl): ?>
                        <div class="mockup-container">
                            <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image" style="max-width: 400px;">
                        </div>
                    <?php endif; ?>
                    
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
                            <?php echo htmlspecialchars($ctaText); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="footer">
                    <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
                    <span style="color: #d1d5db;">•</span>
                    <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
                </div>
            </div>
            
        <?php elseif ($layout === 'hybrid'): ?>
            <!-- HYBRID LAYOUT -->
            <div class="layout-hybrid">
                <div class="hybrid-grid">
                    <div class="mockup-side">
                        <?php if ($mockupUrl): ?>
                            <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 400px; background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #667eea 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 80px; color: white;">
                                🎁
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="content-side">
                        <?php if ($preheadline): ?>
                            <div class="preheadline"><?php echo htmlspecialchars($preheadline); ?></div>
                        <?php endif; ?>
                        
                        <h1><?php echo htmlspecialchars($headline); ?></h1>
                        
                        <?php if ($subheadline): ?>
                            <p class="subheadline"><?php echo htmlspecialchars($subheadline); ?></p>
                        <?php endif; ?>
                        
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
                                <?php echo htmlspecialchars($ctaText); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer">
                    <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
                    <span style="color: #d1d5db;">•</span>
                    <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
                </div>
            </div>
            
        <?php else: // sidebar ?>
            <!-- SIDEBAR LAYOUT -->
            <div class="layout-sidebar">
                <div class="sidebar-grid">
                    <div class="content-side">
                        <?php if ($preheadline): ?>
                            <div class="preheadline"><?php echo htmlspecialchars($preheadline); ?></div>
                        <?php endif; ?>
                        
                        <h1><?php echo htmlspecialchars($headline); ?></h1>
                        
                        <?php if ($subheadline): ?>
                            <p class="subheadline"><?php echo htmlspecialchars($subheadline); ?></p>
                        <?php endif; ?>
                        
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
                                <?php echo htmlspecialchars($ctaText); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mockup-side">
                        <?php if ($mockupUrl): ?>
                            <img src="<?php echo htmlspecialchars($mockupUrl); ?>" alt="Mockup" class="mockup-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 400px; background: linear-gradient(135deg, <?php echo htmlspecialchars($primaryColor); ?> 0%, #667eea 100%); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 80px; color: white;">
                                🎁
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="footer">
                    <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
                    <span style="color: #d1d5db;">•</span>
                    <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>