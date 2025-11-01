<?php
/**
 * Danke-Seite nach Freebie-Anforderung
 * Zeigt Bestätigung und Button zum Videokurs
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
        c.description as course_description
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

$thank_you_headline = $freebie['thank_you_headline'] ?? 'Vielen Dank!';
$thank_you_text = $freebie['thank_you_text'] ?? 'Dein Freebie ist auf dem Weg zu dir. Schau in dein E-Mail-Postfach!';

// Kurs-Button Text und URL
$video_button_text = $freebie['video_button_text'] ?? 'Zum Videokurs';

// Kurs-URL aus verknüpftem Kurs generieren oder Fallback verwenden
$video_course_url = '';
if (!empty($freebie['course_id'])) {
    // Verknüpfter Kurs existiert - Link zum Kurs erstellen (mit ID statt slug)
    $video_course_url = '/customer/course-view.php?id=' . $freebie['course_id'];
} elseif (!empty($freebie['video_course_url'])) {
    // Fallback auf manuelle URL
    $video_course_url = $freebie['video_course_url'];
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($thank_you_headline); ?> - <?php echo htmlspecialchars($freebie['name']); ?></title>
    
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
            color: #1F2937;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .container {
            flex: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 80px 40px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        /* Success Icon Animation */
        .success-icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 40px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $primary_color; ?>dd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            animation: scaleIn 0.5s ease-out;
            box-shadow: 0 10px 40px <?php echo $primary_color; ?>40;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .headline {
            font-size: 48px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 800;
            color: #1F2937;
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }
        
        .message {
            font-size: 20px;
            color: #6b7280;
            margin-bottom: 48px;
            line-height: 1.6;
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
        
        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Steps */
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 48px;
            animation: fadeInUp 0.6s ease-out 0.6s both;
        }
        
        .step {
            background: #f9fafb;
            padding: 32px 24px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .step-number {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            background: <?php echo $primary_color; ?>;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
        }
        
        .step h3 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #1F2937;
        }
        
        .step p {
            font-size: 14px;
            color: #6b7280;
        }
        
        /* Course Info Card */
        .course-info {
            background: linear-gradient(135deg, <?php echo $primary_color; ?>15, <?php echo $primary_color; ?>25);
            border: 2px solid <?php echo $primary_color; ?>40;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            animation: fadeInUp 0.6s ease-out 0.7s both;
        }
        
        .course-info h3 {
            font-size: 24px;
            color: #1F2937;
            margin-bottom: 12px;
        }
        
        .course-info p {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 24px;
        }
        
        /* Video Button */
        .video-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, <?php echo $primary_color; ?>dd);
            color: white;
            padding: 20px 48px;
            border: none;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 8px 24px <?php echo $primary_color; ?>40;
            transition: all 0.3s;
            text-decoration: none;
            animation: fadeInUp 0.6s ease-out 0.8s both;
        }
        
        .video-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px <?php echo $primary_color; ?>60;
        }
        
        .video-button svg {
            width: 24px;
            height: 24px;
        }
        
        /* Footer */
        .footer {
            margin-top: auto;
            padding: 40px 20px;
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
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: <?php echo $primary_color; ?>;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }
            
            .headline {
                font-size: 32px;
            }
            
            .message {
                font-size: 16px;
            }
            
            .steps {
                grid-template-columns: 1fr;
            }
            
            .course-info {
                padding: 24px;
            }
            
            .course-info h3 {
                font-size: 20px;
            }
            
            .video-button {
                padding: 16px 32px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">
            ✓
        </div>
        
        <h1 class="headline"><?php echo htmlspecialchars($thank_you_headline); ?></h1>
        
        <p class="message"><?php echo nl2br(htmlspecialchars($thank_you_text)); ?></p>
        
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>E-Mail prüfen</h3>
                <p>Schau in dein Postfach (auch im Spam-Ordner)</p>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <h3>Freebie herunterladen</h3>
                <p>Klicke auf den Download-Link in der E-Mail</p>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <h3>Direkt loslegen</h3>
                <p>Starte jetzt mit deinem kostenlosen Kurs</p>
            </div>
        </div>
        
        <?php if (!empty($video_course_url)): ?>
            <?php if (!empty($freebie['course_title'])): ?>
                <!-- Kurs-Info-Card -->
                <div class="course-info">
                    <h3>🎓 Dein nächster Schritt</h3>
                    <p>
                        <strong><?php echo htmlspecialchars($freebie['course_title']); ?></strong>
                        <?php if (!empty($freebie['course_description'])): ?>
                            <br>
                            <?php echo htmlspecialchars(substr($freebie['course_description'], 0, 150)); ?>
                            <?php echo strlen($freebie['course_description']) > 150 ? '...' : ''; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <a href="<?php echo htmlspecialchars($video_course_url); ?>" class="video-button">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo htmlspecialchars($video_button_text); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> - Alle Rechte vorbehalten</p>
        <div class="footer-links">
            <a href="/impressum.php">Impressum</a>
            <a href="/datenschutz.php">Datenschutz</a>
        </div>
    </div>
    
    <?php if (!empty($freebie['pixel_code'])): ?>
        <!-- Tracking Pixel -->
        <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
</body>
</html>
