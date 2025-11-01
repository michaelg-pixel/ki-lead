<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: ../public/login.php');
    exit;
}

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

// Freebie-URL erstellen
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$freebie_url = $protocol . '://' . $domain . '/freebie/' . ($freebie['url_slug'] ?: $freebie['unique_id']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie Vorschau - <?php echo htmlspecialchars($freebie['headline']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        .action-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #667eea;
        }
        
        .preview-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .freebie-content {
            background: <?php echo htmlspecialchars($freebie['background_color']); ?>;
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
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .freebie-preheadline {
            color: <?php echo htmlspecialchars($freebie['primary_color']); ?>;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .freebie-headline {
            color: <?php echo htmlspecialchars($freebie['primary_color']); ?>;
            font-size: 48px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .freebie-subheadline {
            color: #6b7280;
            font-size: 20px;
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
            color: <?php echo htmlspecialchars($freebie['primary_color']); ?>;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .bullet-text {
            color: #374151;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .freebie-form {
            background: rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
        }
        
        .freebie-cta {
            text-align: center;
        }
        
        .freebie-button {
            background: <?php echo htmlspecialchars($freebie['primary_color']); ?>;
            color: white;
            padding: 20px 60px;
            border: none;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s;
        }
        
        .freebie-button:hover {
            transform: translateY(-2px);
        }
        
        .url-box {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 24px;
        }
        
        .url-box h3 {
            color: #047857;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .url-input-wrapper {
            display: flex;
            gap: 12px;
        }
        
        .url-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            background: white;
        }
        
        .copy-button {
            padding: 12px 24px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .copy-button:hover {
            background: #059669;
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
            align-items: center;
        }
        
        .layout-sidebar {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
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
                font-size: 32px;
            }
            
            .freebie-subheadline {
                font-size: 16px;
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
            <p>Template: <?php echo htmlspecialchars($freebie['template_name']); ?></p>
            
            <div style="margin-top: 20px;">
                <div class="action-bar">
                    <a href="freebie-editor.php?template_id=<?php echo $freebie['template_id']; ?>" class="action-button action-primary">
                        ✏️ Bearbeiten
                    </a>
                    <a href="<?php echo $freebie_url; ?>" target="_blank" class="action-button action-secondary">
                        🔗 Live-Ansicht öffnen
                    </a>
                </div>
                
                <div class="url-box">
                    <h3>🎯 Dein Freebie-Link</h3>
                    <div class="url-input-wrapper">
                        <input type="text" class="url-input" value="<?php echo htmlspecialchars($freebie_url); ?>" readonly id="freebieUrl">
                        <button class="copy-button" onclick="copyUrl()">📋 Kopieren</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="preview-wrapper">
            <div class="freebie-content">
                <?php
                $layout = $freebie['layout'] ?? 'hybrid';
                
                // Mockup HTML
                $mockup_html = '';
                if (!empty($freebie['mockup_image_url'])) {
                    $mockup_html = '
                        <div class="freebie-mockup">
                            <img src="' . htmlspecialchars($freebie['mockup_image_url']) . '" alt="Mockup" style="max-width: 380px;">
                        </div>
                    ';
                }
                
                // Preheadline HTML
                $preheadline_html = '';
                if (!empty($freebie['preheadline'])) {
                    $preheadline_html = '<div class="freebie-preheadline">' . htmlspecialchars($freebie['preheadline']) . '</div>';
                }
                
                // Headline
                $headline_html = '<div class="freebie-headline">' . htmlspecialchars($freebie['headline']) . '</div>';
                
                // Subheadline HTML
                $subheadline_html = '';
                if (!empty($freebie['subheadline'])) {
                    $subheadline_html = '<div class="freebie-subheadline">' . htmlspecialchars($freebie['subheadline']) . '</div>';
                }
                
                // Bullet Points HTML
                $bullets_html = '';
                if (!empty($freebie['bullet_points'])) {
                    $bullets = array_filter(explode("\n", $freebie['bullet_points']), function($b) { return trim($b) !== ''; });
                    if (!empty($bullets)) {
                        $bullets_html = '<div class="freebie-bullets">';
                        foreach ($bullets as $bullet) {
                            $clean_bullet = preg_replace('/^[✓✔︎•-]\s*/', '', trim($bullet));
                            $bullets_html .= '
                                <div class="freebie-bullet">
                                    <div class="bullet-icon">✓</div>
                                    <div class="bullet-text">' . htmlspecialchars($clean_bullet) . '</div>
                                </div>
                            ';
                        }
                        $bullets_html .= '</div>';
                    }
                }
                
                // Form HTML (falls Raw Code vorhanden)
                $form_html = '';
                if (!empty($freebie['raw_code'])) {
                    $form_html = '<div class="freebie-form">' . $freebie['raw_code'] . '</div>';
                }
                
                // CTA Button
                $cta_html = '
                    <div class="freebie-cta">
                        <button class="freebie-button">' . htmlspecialchars($freebie['cta_text']) . '</button>
                    </div>
                ';
                
                // Layout rendern
                if ($layout === 'centered') {
                    echo '<div class="layout-centered">';
                    echo $mockup_html;
                    echo $preheadline_html;
                    echo $headline_html;
                    echo $subheadline_html;
                    echo $bullets_html;
                    echo $form_html;
                    echo $cta_html;
                    echo '</div>';
                } elseif ($layout === 'hybrid') {
                    echo '<div class="layout-hybrid">';
                    echo '<div>' . $mockup_html . '</div>';
                    echo '<div>';
                    echo $preheadline_html;
                    echo str_replace('text-align: center;', 'text-align: left;', $headline_html);
                    echo str_replace('text-align: center;', 'text-align: left;', $subheadline_html);
                    echo $bullets_html;
                    echo $form_html;
                    echo str_replace('text-align: center;', 'text-align: left;', $cta_html);
                    echo '</div>';
                    echo '</div>';
                } else { // sidebar
                    echo '<div class="layout-sidebar">';
                    echo '<div>';
                    echo $preheadline_html;
                    echo str_replace('text-align: center;', 'text-align: left;', $headline_html);
                    echo str_replace('text-align: center;', 'text-align: left;', $subheadline_html);
                    echo $bullets_html;
                    echo $form_html;
                    echo str_replace('text-align: center;', 'text-align: left;', $cta_html);
                    echo '</div>';
                    echo '<div>' . $mockup_html . '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
        function copyUrl() {
            const input = document.getElementById('freebieUrl');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                const button = document.querySelector('.copy-button');
                const originalText = button.innerHTML;
                button.innerHTML = '✅ Kopiert!';
                button.style.background = '#059669';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.background = '';
                }, 2000);
            } catch (err) {
                alert('Fehler beim Kopieren. Bitte manuell kopieren.');
            }
        }
    </script>
</body>
</html>
