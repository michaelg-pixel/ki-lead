<?php
session_start();

// Login-Check (gleiche Logik wie dashboard.php)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../public/login.php');
    exit;
}

require_once '../config/database.php';
$pdo = getDBConnection();

// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../config/fonts.php';

$customer_id = $_SESSION['user_id'];
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$freebie_id) {
    header('Location: dashboard.php?page=freebies');
    exit;
}

// Customer Freebie laden
try {
    $stmt = $pdo->prepare("
        SELECT cf.*, f.name as template_name, f.mockup_image_url, c.title as course_title
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        LEFT JOIN courses c ON f.course_id = c.id
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

// Font-Einstellungen aus DB mit Fallback auf Defaults
$preheadlineFont = $freebie['preheadline_font'] ?? $fontConfig['defaults']['preheadline_font'];
$preheadlineSize = $freebie['preheadline_size'] ?? $fontConfig['defaults']['preheadline_size'];
$headlineFont = $freebie['headline_font'] ?? $fontConfig['defaults']['headline_font'];
$headlineSize = $freebie['headline_size'] ?? $fontConfig['defaults']['headline_size'];
$subheadlineFont = $freebie['subheadline_font'] ?? $fontConfig['defaults']['subheadline_font'];
$subheadlineSize = $freebie['subheadline_size'] ?? $fontConfig['defaults']['subheadline_size'];
$bulletpointsFont = $freebie['bulletpoints_font'] ?? $fontConfig['defaults']['bulletpoints_font'];
$bulletpointsSize = $freebie['bulletpoints_size'] ?? $fontConfig['defaults']['bulletpoints_size'];

// 🆕 BULLET ICON STYLE (mit Fallback auf Standard)
$bulletIconStyle = $freebie['bullet_icon_style'] ?? 'standard';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie Vorschau - <?php echo htmlspecialchars($freebie['headline'] ?? 'Freebie'); ?></title>
    
    <!-- Google Fonts laden - alle verfügbaren Schriftarten -->
    <link href="<?php echo $fontConfig['google_fonts_url']; ?>" rel="stylesheet">
    
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
            background: <?php echo htmlspecialchars($freebie['background_color'] ?? '#FFFFFF'); ?>;
            border-radius: 12px;
            padding: 80px 60px;
            min-height: 600px;
        }
        
        .freebie-mockup {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .freebie-mockup img {
            max-width: 100%;
            height: auto;
            max-width: 380px;
            border-radius: 12px;
        }
        
        /* HEADLINES IMMER ZENTRIERT */
        .freebie-preheadline {
            color: <?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>;
            font-size: <?php echo (int)$preheadlineSize; ?>px;
            font-family: '<?php echo htmlspecialchars($preheadlineFont); ?>', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .freebie-headline {
            color: <?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>;
            font-size: <?php echo (int)$headlineSize; ?>px;
            font-family: '<?php echo htmlspecialchars($headlineFont); ?>', sans-serif;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .freebie-subheadline {
            color: #6b7280;
            font-size: <?php echo (int)$subheadlineSize; ?>px;
            font-family: '<?php echo htmlspecialchars($subheadlineFont); ?>', sans-serif;
            margin-bottom: 40px;
            text-align: center;
            line-height: 1.6;
        }
        
        .freebie-bullets {
            margin-bottom: 40px;
        }
        
        .freebie-bullet {
            display: flex;
            align-items: start;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .bullet-icon {
            color: <?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .bullet-text {
            color: #374151;
            font-size: <?php echo (int)$bulletpointsSize; ?>px;
            font-family: '<?php echo htmlspecialchars($bulletpointsFont); ?>', sans-serif;
            line-height: 1.6;
        }
        
        .freebie-form {
            background: rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .freebie-form form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .freebie-form input[type="text"],
        .freebie-form input[type="email"] {
            width: 100%;
            padding: 14px 18px;
            margin-bottom: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }
        
        .freebie-form button,
        .freebie-form input[type="submit"] {
            width: 100%;
            padding: 14px 18px;
            background: <?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .freebie-form button:hover,
        .freebie-form input[type="submit"]:hover {
            transform: translateY(-2px);
        }
        
        .freebie-cta {
            text-align: center;
        }
        
        .freebie-button {
            background: <?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>;
            color: white;
            padding: 20px 60px;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
            display: inline-block;
        }
        
        .freebie-button:hover {
            transform: translateY(-2px);
        }
        
        /* Layout-Styles */
        .layout-centered {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .layout-hybrid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        
        .layout-sidebar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }
        
        @media (max-width: 1024px) {
            .layout-hybrid,
            .layout-sidebar {
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
                font-size: <?php echo max(24, (int)$headlineSize - 10); ?>px;
            }
            
            .freebie-subheadline {
                font-size: <?php echo max(14, (int)$subheadlineSize - 2); ?>px;
            }
            
            .bullet-text {
                font-size: <?php echo max(14, (int)$bulletpointsSize - 2); ?>px;
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
            <p>Template: <?php echo htmlspecialchars($freebie['template_name'] ?? 'Unbekannt'); ?></p>
            
            <div class="action-bar">
                <a href="freebie-editor.php?template_id=<?php echo $freebie['template_id']; ?>" class="action-button action-primary">
                    ✏️ Bearbeiten
                </a>
            </div>
        </div>
        
        <div class="preview-wrapper">
            <div class="freebie-content">
                <?php
                $layout = $freebie['layout'] ?? 'hybrid';
                
                // HEADLINES - IMMER ZENTRIERT, IMMER OBEN
                // Preheadline HTML
                $preheadline_html = '';
                if (!empty($freebie['preheadline'])) {
                    $preheadline_html = '<div class="freebie-preheadline">' . htmlspecialchars($freebie['preheadline']) . '</div>';
                }
                
                // Headline - IMMER ZENTRIERT
                $headline_html = '<div class="freebie-headline">' . htmlspecialchars($freebie['headline'] ?? 'Freebie Headline') . '</div>';
                
                // Subheadline HTML - IMMER ZENTRIERT
                $subheadline_html = '';
                if (!empty($freebie['subheadline'])) {
                    $subheadline_html = '<div class="freebie-subheadline">' . htmlspecialchars($freebie['subheadline']) . '</div>';
                }
                
                // Mockup HTML - IMMER ANZEIGEN
                $mockup_html = '';
                $mockup_url = $freebie['mockup_image_url'] ?? '';
                if (!empty($mockup_url)) {
                    $mockup_html = '
                        <div class="freebie-mockup">
                            <img src="' . htmlspecialchars($mockup_url) . '" alt="Mockup">
                        </div>
                    ';
                }
                
                // 🆕 BULLET POINTS MIT BULLET ICON STYLE LOGIK
                $bullets_html = '';
                if (!empty($freebie['bullet_points'])) {
                    $bullets = array_filter(explode("\n", $freebie['bullet_points']), function($b) { return trim($b) !== ''; });
                    if (!empty($bullets)) {
                        $bullets_html = '<div class="freebie-bullets">';
                        
                        foreach ($bullets as $bullet) {
                            $bullet = trim($bullet);
                            $icon = '✓';
                            $text = $bullet;
                            $iconColor = htmlspecialchars($freebie['primary_color'] ?? '#667eea');
                            
                            // 🆕 LOGIK FÜR BULLET ICON STYLE
                            if ($bulletIconStyle === 'custom') {
                                // Versuche Emoji/Icon am Anfang zu extrahieren
                                if (preg_match('/^([\x{1F300}-\x{1F9FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}])/u', $bullet, $matches)) {
                                    $icon = $matches[1];
                                    $text = trim(substr($bullet, strlen($icon)));
                                    $iconColor = 'inherit';
                                } else {
                                    // Fallback: erstes Zeichen prüfen
                                    $firstChar = mb_substr($bullet, 0, 1);
                                    if ($firstChar && !preg_match('/[a-zA-Z0-9\s]/', $firstChar)) {
                                        $icon = $firstChar;
                                        $text = trim(mb_substr($bullet, 1));
                                        $iconColor = 'inherit';
                                    }
                                }
                            } else {
                                // Standard: Text bereinigen und grünen Haken nutzen
                                $text = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                            }
                            
                            $bullets_html .= '
                                <div class="freebie-bullet">
                                    <div class="bullet-icon" style="color: ' . $iconColor . ';">' . htmlspecialchars($icon) . '</div>
                                    <div class="bullet-text">' . htmlspecialchars($text) . '</div>
                                </div>
                            ';
                        }
                        $bullets_html .= '</div>';
                    }
                }
                
                // Form HTML (Raw Code richtig einbinden)
                $form_html = '';
                if (!empty($freebie['raw_code'])) {
                    $form_html = '<div class="freebie-form">' . $freebie['raw_code'] . '</div>';
                }
                
                // CTA Button (nur wenn KEIN raw_code vorhanden)
                $cta_html = '';
                if (empty($freebie['raw_code'])) {
                    $cta_text = $freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN';
                    $cta_html = '
                        <div class="freebie-cta">
                            <a href="#" class="freebie-button">' . htmlspecialchars($cta_text) . '</a>
                        </div>
                    ';
                }
                
                // Layout rendern - HEADLINES IMMER ZENTRIERT OBEN
                if ($layout === 'centered') {
                    // CENTERED: Alles zentriert untereinander
                    echo '<div class="layout-centered">';
                    echo $preheadline_html;
                    echo $headline_html;
                    echo $subheadline_html;
                    echo $mockup_html;
                    echo $bullets_html;
                    echo $form_html;
                    echo $cta_html;
                    echo '</div>';
                    
                } elseif ($layout === 'hybrid') {
                    // HYBRID: Headlines oben zentriert, dann Mockup LINKS, Bullets RECHTS
                    echo $preheadline_html;
                    echo $headline_html;
                    echo $subheadline_html;
                    
                    echo '<div class="layout-hybrid">';
                    echo '<div>' . $mockup_html . '</div>';
                    echo '<div>';
                    echo $bullets_html;
                    echo $form_html;
                    echo $cta_html;
                    echo '</div>';
                    echo '</div>';
                    
                } else { // sidebar
                    // SIDEBAR: Headlines oben zentriert, dann Bullets LINKS, Mockup RECHTS
                    echo $preheadline_html;
                    echo $headline_html;
                    echo $subheadline_html;
                    
                    echo '<div class="layout-sidebar">';
                    echo '<div>';
                    echo $bullets_html;
                    echo $form_html;
                    echo $cta_html;
                    echo '</div>';
                    echo '<div>' . $mockup_html . '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
