<?php
/**
 * Danke-Seite nach Freebie-Anforderung
 * Direkter Zugang zum Freebie-Videokurs
 */

require_once __DIR__ . '/../config/database.php';

// Freebie-ID aus URL holen
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($freebie_id <= 0) {
    die('Ungültige Freebie-ID');
}

// Freebie aus Datenbank laden MIT verknüpftem Kurs
$stmt = $pdo->prepare("
    SELECT 
        f.*,
        c.id as course_id,
        c.title as course_title,
        c.description as course_description,
        c.mockup_url as course_mockup
    FROM freebies f
    LEFT JOIN courses c ON f.course_id = c.id
    WHERE f.id = ?
");
$stmt->execute([$freebie_id]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    die('Freebie nicht gefunden');
}

// Klick-Tracking für Danke-Seite
$update = $pdo->prepare("UPDATE freebies SET thank_you_clicks = thank_you_clicks + 1 WHERE id = ?");
$update->execute([$freebie_id]);

// Standardwerte
$primary_color = $freebie['primary_color'] ?? '#7C3AED';
$background_color = $freebie['background_color'] ?? '#FFFFFF';
$headline_font = $freebie['headline_font'] ?? 'Poppins';
$body_font = $freebie['body_font'] ?? 'Poppins';

// Kurs-Button Text und URL
$video_button_text = $freebie['video_button_text'] ?? 'Zum Videokurs';

// Kurs-URL aus verknüpftem Kurs generieren
$video_course_url = '';
if (!empty($freebie['course_id'])) {
    $video_course_url = '/customer/course-view.php?id=' . $freebie['course_id'];
} elseif (!empty($freebie['video_course_url'])) {
    $video_course_url = $freebie['video_course_url'];
}

// Mockup-Bild des Kurses oder Freebie
$mockup_image = $freebie['course_mockup'] ?? $freebie['mockup_image_url'] ?? '';

// Aktuelle URL für Bookmark-Funktion
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name']); ?> - Dein Zugang</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: <?php echo $primary_color; ?>;
            --primary-light: <?php echo $primary_color; ?>20;
            --primary-dark: <?php echo $primary_color; ?>dd;
        }
        
        body {
            font-family: '<?php echo $body_font; ?>', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #1F2937;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .container {
            flex: 1;
            max-width: 900px;
            margin: 40px auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        /* Success Card */
        .success-card {
            background: white;
            border-radius: 24px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .headline {
            font-size: 48px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 900;
            color: #111827;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        
        .subheadline {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 40px;
            font-weight: 500;
        }
        
        .course-title {
            font-size: 32px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 800;
            color: #111827;
            margin-bottom: 32px;
            line-height: 1.3;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Mockup Image - KLEINER */
        .mockup-container {
            max-width: 350px;
            margin: 0 auto 40px;
            animation: fadeIn 0.8s ease-out 0.3s both;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .mockup-container img {
            width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        
        .mockup-placeholder {
            width: 100%;
            aspect-ratio: 3/4;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-light));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 80px;
        }
        
        /* Main CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 24px 60px;
            border: none;
            border-radius: 16px;
            font-size: 22px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 30px var(--primary-light);
            transition: all 0.3s;
            margin-bottom: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .cta-button:hover::before {
            left: 100%;
        }
        
        .cta-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px var(--primary-light);
        }
        
        .cta-icon {
            font-size: 28px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .access-info {
            color: #9ca3af;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Bookmark Banner */
        .bookmark-banner {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #f59e0b;
            border-radius: 16px;
            padding: 24px 32px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
            animation: slideUp 0.6s ease-out 0.2s both;
        }
        
        .bookmark-icon {
            font-size: 40px;
            flex-shrink: 0;
        }
        
        .bookmark-content {
            flex: 1;
        }
        
        .bookmark-title {
            font-size: 18px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 6px;
        }
        
        .bookmark-text {
            font-size: 14px;
            color: #78350f;
            margin-bottom: 12px;
        }
        
        .bookmark-button {
            padding: 10px 20px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .bookmark-button:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        
        /* Info Steps - NUR 2 SCHRITTE */
        .info-steps {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            animation: slideUp 0.6s ease-out 0.3s both;
        }
        
        .steps-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            text-align: center;
            margin-bottom: 32px;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .step {
            text-align: center;
            padding: 32px 24px;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 16px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .step:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 8px 24px var(--primary-light);
        }
        
        .step-number {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 900;
            box-shadow: 0 6px 20px var(--primary-light);
        }
        
        .step h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .step p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.7;
        }
        
        /* Footer */
        .footer {
            margin-top: auto;
            padding: 32px 20px;
            text-align: center;
        }
        
        .footer-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: #4b5563;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                margin: 20px auto;
                gap: 16px;
            }
            
            .success-card {
                padding: 40px 24px;
            }
            
            .headline {
                font-size: 32px;
            }
            
            .subheadline {
                font-size: 16px;
            }
            
            .course-title {
                font-size: 24px;
            }
            
            .mockup-container {
                max-width: 280px;
            }
            
            .cta-button {
                width: 100%;
                padding: 20px 40px;
                font-size: 18px;
            }
            
            .bookmark-banner {
                flex-direction: column;
                text-align: center;
                padding: 20px;
            }
            
            .info-steps {
                padding: 24px 20px;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success Card mit Kurs -->
        <div class="success-card">
            <h1 class="headline">Vielen Dank!</h1>
            <p class="subheadline">Du hast jetzt sofortigen Zugang zu deinem Freebie!</p>
            
            <?php if (!empty($freebie['course_title'])): ?>
                <h2 class="course-title"><?php echo htmlspecialchars($freebie['course_title']); ?></h2>
            <?php endif; ?>
            
            <!-- Mockup Image - KLEINER -->
            <?php if (!empty($mockup_image)): ?>
                <div class="mockup-container">
                    <img src="<?php echo htmlspecialchars($mockup_image); ?>" 
                         alt="<?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name']); ?>">
                </div>
            <?php else: ?>
                <div class="mockup-container">
                    <div class="mockup-placeholder">
                        🎓
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($video_course_url)): ?>
                <a href="<?php echo htmlspecialchars($video_course_url); ?>" class="cta-button">
                    <span class="cta-icon">🚀</span>
                    <span><?php echo htmlspecialchars($video_button_text); ?></span>
                </a>
                
                <p class="access-info">
                    ⚡ Sofortiger Zugang • Keine Wartezeit • Direkt loslegen
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Bookmark Banner -->
        <div class="bookmark-banner">
            <div class="bookmark-icon">🔖</div>
            <div class="bookmark-content">
                <div class="bookmark-title">💡 Wichtig: Speichere diese Seite!</div>
                <div class="bookmark-text">
                    Sichere dir dauerhaften Zugang zu deinem Freebie. Speichere diese Seite als Lesezeichen in deinem Browser.
                </div>
                <button onclick="bookmarkPage()" class="bookmark-button">
                    ⭐ Seite als Lesezeichen speichern
                </button>
            </div>
        </div>
        
        <!-- Info Steps - NUR 2 SCHRITTE -->
        <div class="info-steps">
            <h2 class="steps-title">So geht's weiter 👇</h2>
            <div class="steps-grid">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Seite speichern</h3>
                    <p>Speichere diese Seite als Lesezeichen, um jederzeit Zugriff auf dein Freebie zu haben</p>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Freebie abrufen</h3>
                    <p>Klicke auf den Button oben und erhalte sofortigen Zugang zu deinem exklusiven Videokurs</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p class="footer-text">&copy; <?php echo date('Y'); ?> - Alle Rechte vorbehalten</p>
            <div class="footer-links">
                <a href="/impressum.php">Impressum</a>
                <span style="color: #d1d5db;">•</span>
                <a href="/datenschutz.php">Datenschutzerklärung</a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($freebie['pixel_code'])): ?>
        <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
    
    <script>
        function bookmarkPage() {
            const pageTitle = '<?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name']); ?> - Dein Zugang';
            const pageURL = window.location.href;
            
            // Moderne Browser
            if (window.sidebar && window.sidebar.addPanel) {
                // Firefox
                window.sidebar.addPanel(pageTitle, pageURL, '');
            } else if (window.external && ('AddFavorite' in window.external)) {
                // IE
                window.external.AddFavorite(pageURL, pageTitle);
            } else if (window.opera && window.print) {
                // Opera
                const elem = document.createElement('a');
                elem.setAttribute('href', pageURL);
                elem.setAttribute('title', pageTitle);
                elem.setAttribute('rel', 'sidebar');
                elem.click();
            } else {
                // Für moderne Browser (Chrome, Safari, Edge)
                const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                const shortcut = isMac ? 'Cmd+D' : 'Ctrl+D';
                
                alert(`✨ Drücke ${shortcut} um diese Seite als Lesezeichen zu speichern!\n\nSo hast du jederzeit Zugriff auf dein Freebie.`);
            }
        }
    </script>
</body>
</html>
