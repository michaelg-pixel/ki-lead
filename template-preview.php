<?php
/**
 * Template Vorschau für Freebies
 * Zeigt Admin-Templates in einer Vorschau an
 */

require_once __DIR__ . '/config/database.php';

// Template ID aus URL holen
$template_id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;

if (!$template_id) {
    die('Template ID fehlt');
}

// Template laden
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        die('Template nicht gefunden');
    }
    
    // Zugehörigen Kurs laden (falls vorhanden)
    $course = null;
    if (!empty($template['course_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$template['course_id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

$layout = $template['layout'] ?? 'hybrid';
$bgColor = $template['background_color'] ?? '#FFFFFF';
$primaryColor = $template['primary_color'] ?? '#8B5CF6';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($template['headline'] ?: $template['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: <?php echo htmlspecialchars($bgColor); ?>;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .preview-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(239, 68, 68, 0.95);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
        }
        
        .freebie-mockup {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .freebie-mockup img {
            max-width: 100%;
            max-height: 500px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .freebie-preheadline {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .freebie-headline {
            color: <?php echo htmlspecialchars($primaryColor); ?>;
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
            color: <?php echo htmlspecialchars($primaryColor); ?>;
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .bullet-text {
            color: #374151;
            font-size: 18px;
            line-height: 1.6;
        }
        
        .freebie-cta {
            text-align: center;
            margin-top: 40px;
        }
        
        .freebie-button {
            background: <?php echo htmlspecialchars($primaryColor); ?>;
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
            text-decoration: none;
        }
        
        .freebie-button:hover {
            transform: translateY(-2px);
        }
        
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
            
            .freebie-headline {
                font-size: 36px;
            }
            
            .freebie-subheadline {
                font-size: 18px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 20px 16px;
            }
            
            .preview-badge {
                top: 10px;
                right: 10px;
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .freebie-headline {
                font-size: 28px;
            }
            
            .freebie-subheadline {
                font-size: 16px;
            }
            
            .freebie-button {
                padding: 16px 40px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="preview-badge">
        👁️ VORSCHAU-MODUS
    </div>
    
    <div class="container">
        <?php
        // Mockup HTML
        $mockup_html = '';
        if (!empty($template['mockup_image_url'])) {
            $mockup_html = '
                <div class="freebie-mockup">
                    <img src="' . htmlspecialchars($template['mockup_image_url']) . '" alt="Mockup">
                </div>
            ';
        }
        
        // Preheadline HTML
        $preheadline_html = '';
        if (!empty($template['preheadline'])) {
            $preheadline_html = '<div class="freebie-preheadline">' . htmlspecialchars($template['preheadline']) . '</div>';
        }
        
        // Headline
        $headline_html = '<div class="freebie-headline">' . htmlspecialchars($template['headline'] ?: $template['name']) . '</div>';
        
        // Subheadline HTML
        $subheadline_html = '';
        if (!empty($template['subheadline'])) {
            $subheadline_html = '<div class="freebie-subheadline">' . htmlspecialchars($template['subheadline']) . '</div>';
        }
        
        // Bullet Points HTML
        $bullets_html = '';
        if (!empty($template['bullet_points'])) {
            $bullets = array_filter(explode("\n", $template['bullet_points']), function($b) { return trim($b) !== ''; });
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
        
        // CTA Button
        $cta_html = '
            <div class="freebie-cta">
                <a href="#" class="freebie-button" onclick="alert(\'Dies ist eine Vorschau. Nutze den Editor, um dein eigenes Freebie zu erstellen!\'); return false;">' . htmlspecialchars($template['cta_text'] ?: 'JETZT KOSTENLOS SICHERN') . '</a>
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
            echo str_replace('text-align: center;', 'text-align: left;', $cta_html);
            echo '</div>';
            echo '<div>' . $mockup_html . '</div>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
