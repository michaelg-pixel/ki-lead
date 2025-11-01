<?php
/**
 * Öffentliche Freebie-Ansicht
 * Zeigt das Freebie-Template mit vollständigem Layout
 * WICHTIG: Diese Seite ist ÖFFENTLICH und erfordert KEINE Authentifizierung!
 */

// KEINE Auth-Checks hier!
// Diese Seite muss für jeden zugänglich sein

require_once __DIR__ . '/../config/database.php';

// Freebie-ID aus URL holen
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($freebie_id <= 0) {
    http_response_code(400);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Ungültige Freebie-ID</h1><p>Bitte überprüfen Sie den Link.</p></body></html>');
}

// Freebie aus Datenbank laden MIT customer_id
try {
    // Erst versuchen, customer_id direkt aus freebies zu holen
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$freebie_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        http_response_code(404);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht gefunden</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Freebie nicht gefunden</h1><p>Dieses Freebie existiert nicht.</p></body></html>');
    }
    
    // Customer-ID ermitteln
    $customer_id = null;
    if (isset($freebie['customer_id']) && !empty($freebie['customer_id'])) {
        // Direktes Feld in freebies Tabelle
        $customer_id = $freebie['customer_id'];
    } else {
        // Über customer_freebies Tabelle (template_id, nicht freebie_id!)
        $stmt_customer = $pdo->prepare("SELECT customer_id FROM customer_freebies WHERE template_id = ? LIMIT 1");
        $stmt_customer->execute([$freebie_id]);
        $customer_relation = $stmt_customer->fetch(PDO::FETCH_ASSOC);
        if ($customer_relation) {
            $customer_id = $customer_relation['customer_id'];
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Datenbankfehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>');
}

// Klick-Tracking
try {
    $update = $pdo->prepare("UPDATE freebies SET freebie_clicks = COALESCE(freebie_clicks, 0) + 1 WHERE id = ?");
    $update->execute([$freebie_id]);
} catch (PDOException $e) {
    // Fehler beim Tracking ignorieren, Seite trotzdem anzeigen
}

// Layout-Mapping
$layoutMapping = [
    'layout1' => 'hybrid',
    'layout2' => 'centered',
    'layout3' => 'sidebar'
];
$layout = $layoutMapping[$freebie['layout']] ?? 'hybrid';

// Farben
$primary_color = $freebie['primary_color'] ?? '#7C3AED';
$background_color = $freebie['background_color'] ?? '#FFFFFF';
$text_color = $freebie['text_color'] ?? '#1F2937';

// Fonts
$headline_font = $freebie['headline_font'] ?? 'Poppins';
$headline_size = $freebie['headline_size'] ?? 48;
$body_font = $freebie['body_font'] ?? 'Poppins';
$body_size = $freebie['body_size'] ?? 16;
$preheadline_font = $freebie['preheadline_font'] ?? $headline_font;
$preheadline_size = $freebie['preheadline_size'] ?? 14;
$subheadline_font = $freebie['subheadline_font'] ?? $body_font;
$subheadline_size = $freebie['subheadline_size'] ?? 20;
$bulletpoints_font = $freebie['bulletpoints_font'] ?? $body_font;
$bulletpoints_size = $freebie['bulletpoints_size'] ?? $body_size;

// Mockup
$show_mockup = !empty($freebie['mockup_image_url']);
$mockup_url = $freebie['mockup_image_url'] ?? '';

// Footer-Links mit customer_id
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['name'] ?? 'Freebie'); ?></title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '<?php echo $body_font; ?>', sans-serif;
            background: <?php echo $background_color; ?>;
            color: <?php echo $text_color; ?>;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }
        
        /* Preheadline */
        .preheadline {
            color: <?php echo $primary_color; ?>;
            font-size: <?php echo $preheadline_size; ?>px;
            font-family: '<?php echo $preheadline_font; ?>', sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
            text-align: center;
        }
        
        /* Headline */
        .headline {
            font-size: <?php echo $headline_size; ?>px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 800;
            color: <?php echo $text_color; ?>;
            line-height: 1.1;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Subheadline */
        .subheadline {
            font-size: <?php echo $subheadline_size; ?>px;
            font-family: '<?php echo $subheadline_font; ?>', sans-serif;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.6;
            text-align: center;
        }
        
        /* Layouts */
        .layout-hybrid {
            display: grid;
            grid-template-columns: 2fr 3fr;
            gap: 60px;
            align-items: center;
        }
        
        .layout-centered {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        
        .layout-sidebar {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 60px;
            align-items: center;
        }
        
        /* Mockup */
        .mockup {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .mockup img {
            width: 100%;
            max-width: 380px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        
        .mockup-placeholder {
            width: 100%;
            max-width: 380px;
            aspect-ratio: 3/4;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>20, <?php echo $primary_color; ?>40);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: <?php echo $primary_color; ?>;
            font-size: 64px;
        }
        
        /* Bulletpoints */
        .bulletpoints {
            margin-bottom: 32px;
        }
        
        .bulletpoint {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .bulletpoint-icon {
            color: <?php echo $primary_color; ?>;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .bulletpoint-text {
            color: <?php echo $text_color; ?>;
            font-size: <?php echo $bulletpoints_size; ?>px;
            font-family: '<?php echo $bulletpoints_font; ?>', sans-serif;
            line-height: 1.5;
        }
        
        /* CTA Button */
        .cta-button {
            background: <?php echo $primary_color; ?>;
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px <?php echo $primary_color; ?>40;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .cta-button:hover {
            transform: translateY(-2px);
        }
        
        /* Footer */
        .footer {
            margin-top: 120px;
            padding-top: 40px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 16px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: <?php echo $primary_color; ?>;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }
            
            .layout-hybrid,
            .layout-sidebar {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .headline {
                font-size: 32px;
            }
            
            .subheadline {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($layout === 'centered'): ?>
            <!-- CENTERED LAYOUT -->
            <div class="layout-centered">
                <?php if (!empty($freebie['preheadline'])): ?>
                    <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
                <?php endif; ?>
                
                <h1 class="headline"><?php echo htmlspecialchars($freebie['headline'] ?? 'Deine Überschrift'); ?></h1>
                
                <?php if (!empty($freebie['subheadline'])): ?>
                    <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
                <?php endif; ?>
                
                <?php if ($show_mockup): ?>
                    <div class="mockup" style="margin-bottom: 40px;">
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup">
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($freebie['bullet_points'])): ?>
                    <div class="bulletpoints" style="text-align: left; max-width: 500px; margin: 0 auto 40px;">
                        <?php
                        $bullets = explode("\n", $freebie['bullet_points']);
                        foreach ($bullets as $bullet):
                            $bullet = trim($bullet);
                            if (empty($bullet)) continue;
                            $bullet = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                        ?>
                            <div class="bulletpoint">
                                <span class="bulletpoint-icon">✓</span>
                                <span class="bulletpoint-text"><?php echo htmlspecialchars($bullet); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <button class="cta-button" onclick="window.location.href='<?php echo htmlspecialchars($freebie['thank_you_link'] ?? '#'); ?>'">
                    <?php echo htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern'); ?>
                </button>
            </div>
            
        <?php elseif ($layout === 'hybrid'): ?>
            <!-- HYBRID LAYOUT -->
            <div class="layout-hybrid">
                <?php if ($show_mockup): ?>
                    <div class="mockup">
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup">
                    </div>
                <?php endif; ?>
                
                <div>
                    <?php if (!empty($freebie['preheadline'])): ?>
                        <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
                    <?php endif; ?>
                    
                    <h1 class="headline"><?php echo htmlspecialchars($freebie['headline'] ?? 'Deine Überschrift'); ?></h1>
                    
                    <?php if (!empty($freebie['subheadline'])): ?>
                        <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($freebie['bullet_points'])): ?>
                        <div class="bulletpoints">
                            <?php
                            $bullets = explode("\n", $freebie['bullet_points']);
                            foreach ($bullets as $bullet):
                                $bullet = trim($bullet);
                                if (empty($bullet)) continue;
                                $bullet = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                            ?>
                                <div class="bulletpoint">
                                    <span class="bulletpoint-icon">✓</span>
                                    <span class="bulletpoint-text"><?php echo htmlspecialchars($bullet); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center;">
                        <button class="cta-button" onclick="window.location.href='<?php echo htmlspecialchars($freebie['thank_you_link'] ?? '#'); ?>'">
                            <?php echo htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <!-- SIDEBAR LAYOUT -->
            <div class="layout-sidebar">
                <div>
                    <?php if (!empty($freebie['preheadline'])): ?>
                        <div class="preheadline"><?php echo htmlspecialchars($freebie['preheadline']); ?></div>
                    <?php endif; ?>
                    
                    <h1 class="headline"><?php echo htmlspecialchars($freebie['headline'] ?? 'Deine Überschrift'); ?></h1>
                    
                    <?php if (!empty($freebie['subheadline'])): ?>
                        <p class="subheadline"><?php echo htmlspecialchars($freebie['subheadline']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($freebie['bullet_points'])): ?>
                        <div class="bulletpoints">
                            <?php
                            $bullets = explode("\n", $freebie['bullet_points']);
                            foreach ($bullets as $bullet):
                                $bullet = trim($bullet);
                                if (empty($bullet)) continue;
                                $bullet = preg_replace('/^[✓✔︎•-]\s*/', '', $bullet);
                            ?>
                                <div class="bulletpoint">
                                    <span class="bulletpoint-icon">✓</span>
                                    <span class="bulletpoint-text"><?php echo htmlspecialchars($bullet); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="text-align: center;">
                        <button class="cta-button" onclick="window.location.href='<?php echo htmlspecialchars($freebie['thank_you_link'] ?? '#'); ?>'">
                            <?php echo htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern'); ?>
                        </button>
                    </div>
                </div>
                
                <?php if ($show_mockup): ?>
                    <div class="mockup">
                        <img src="<?php echo htmlspecialchars($mockup_url); ?>" alt="Mockup">
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer mit kundenspezifischen Links -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> - Alle Rechte vorbehalten</p>
        <div class="footer-links">
            <a href="<?php echo $impressum_link; ?>">Impressum</a>
            <a href="<?php echo $datenschutz_link; ?>">Datenschutz</a>
        </div>
    </div>
    
    <?php if (!empty($freebie['pixel_code'])): ?>
        <!-- Tracking Pixel -->
        <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
</body>
</html>