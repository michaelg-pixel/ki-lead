<?php
/**
 * Freebie Danke-Seite mit Dashboard-Zugang
 * Zeigt Video + Download + Button zum Lead-Dashboard
 */

require_once __DIR__ . '/../config/database.php';

// Parameter aus URL
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$email = isset($_GET['email']) ? trim($_GET['email']) : '';
$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($freebie_id <= 0) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Ungültige Freebie-ID</h1></body></html>');
}

// Freebie laden (entweder aus customer_freebies oder freebies)
$freebie = null;
$referral_enabled = 0;

try {
    // Erst in customer_freebies suchen
    $stmt = $pdo->prepare("
        SELECT cf.*, u.referral_enabled 
        FROM customer_freebies cf
        LEFT JOIN users u ON cf.customer_id = u.id
        WHERE cf.id = ?
    ");
    $stmt->execute([$freebie_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'];
        $referral_enabled = (int)($freebie['referral_enabled'] ?? 0);
    } else {
        // Wenn nicht gefunden, in freebies suchen
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
        $stmt->execute([$freebie_id]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$freebie) {
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Freebie nicht gefunden</h1></body></html>');
    }
} catch (PDOException $e) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Datenbankfehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>');
}

// Login-Token für Dashboard-Zugang generieren (wenn E-Mail vorhanden)
$dashboard_link = null;
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && $customer_id) {
    try {
        // Prüfen ob lead_login_tokens Tabelle existiert
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $pdo->prepare("
            CREATE TABLE IF NOT EXISTS lead_login_tokens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                token VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) NOT NULL,
                name VARCHAR(255),
                customer_id INT,
                freebie_id INT,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_token (token),
                INDEX idx_email (email),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $stmt->execute();
        
        // Token speichern
        $stmt = $pdo->prepare("
            INSERT INTO lead_login_tokens 
            (token, email, name, customer_id, freebie_id, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$token, $email, $name, $customer_id, $freebie_id, $expires_at]);
        
        // Dashboard-Link generieren
        $dashboard_link = '/lead-dashboard-unified.php?token=' . $token;
        
    } catch (PDOException $e) {
        error_log("Token-Fehler: " . $e->getMessage());
        // Fallback ohne Token
        $dashboard_link = '/lead_login.php';
    }
}

// Styling
$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$headline_font = $freebie['headline_font'] ?? 'Poppins';
$body_font = $freebie['body_font'] ?? 'Poppins';

// Video URL und Download Link
$video_url = $freebie['video_url'] ?? '';
$download_url = $freebie['download_url'] ?? '';
$download_text = $freebie['download_text'] ?? 'Jetzt herunterladen';

// Footer-Links
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vielen Dank - <?php echo htmlspecialchars($freebie['name'] ?? 'Dein Freebie'); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: '<?php echo $body_font; ?>', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 24px;
            padding: 60px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .success-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        h1 {
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-size: 42px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        
        .email-info {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 16px 20px;
            margin-bottom: 40px;
            border-radius: 8px;
        }
        
        .email-info p {
            color: #1e40af;
            font-size: 14px;
            margin: 0;
        }
        
        .email-address {
            font-weight: 600;
            color: #1e3a8a;
        }
        
        /* Dashboard Button - PROMINENT */
        .dashboard-section {
            text-align: center;
            margin: 40px 0;
            padding: 32px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, color-mix(in srgb, <?php echo $primary_color; ?> 80%, black));
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .dashboard-section h2 {
            color: white;
            font-size: 28px;
            margin-bottom: 12px;
            font-weight: 800;
        }
        
        .dashboard-section p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            margin-bottom: 24px;
        }
        
        .dashboard-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 20px 48px;
            background: white;
            color: <?php echo $primary_color; ?>;
            text-decoration: none;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 800;
            transition: all 0.3s;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }
        
        .dashboard-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.3);
        }
        
        .dashboard-button i {
            font-size: 24px;
        }
        
        .dashboard-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        
        .dashboard-feature {
            background: rgba(255, 255, 255, 0.1);
            padding: 16px;
            border-radius: 12px;
            color: white;
            text-align: center;
        }
        
        .dashboard-feature i {
            font-size: 32px;
            margin-bottom: 8px;
            display: block;
        }
        
        .dashboard-feature span {
            font-size: 14px;
            display: block;
        }
        
        /* Video Container */
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            background: #000;
            border-radius: 16px;
            margin-bottom: 40px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        /* Download Button */
        .download-section {
            text-align: center;
            margin: 40px 0;
        }
        
        .download-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 36px;
            background: #f3f4f6;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s;
            border: 2px solid #e5e7eb;
        }
        
        .download-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            border-color: <?php echo $primary_color; ?>;
        }
        
        .download-icon {
            font-size: 24px;
        }
        
        /* Next Steps */
        .next-steps {
            background: #f9fafb;
            padding: 32px;
            border-radius: 16px;
            margin-top: 40px;
        }
        
        .next-steps h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        
        .step {
            display: flex;
            align-items: start;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .step-number {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            background: <?php echo $primary_color; ?>;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-content h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }
        
        .step-content p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            margin-top: 60px;
            padding-top: 32px;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 40px 24px;
            }
            
            h1 {
                font-size: 32px;
            }
            
            .subtitle {
                font-size: 16px;
            }
            
            .dashboard-section h2 {
                font-size: 24px;
            }
            
            .dashboard-button {
                width: 100%;
                justify-content: center;
                font-size: 18px;
            }
            
            .download-button {
                width: 100%;
                justify-content: center;
            }
            
            .dashboard-features {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="success-badge">
            ✓ Erfolgreich angemeldet
        </div>
        
        <h1>Vielen Dank<?php echo $name ? ', ' . htmlspecialchars($name) : ''; ?>!</h1>
        
        <p class="subtitle">
            Deine Anmeldung war erfolgreich. Du hast jetzt Zugang zu deinem kostenlosen 
            <?php echo htmlspecialchars($freebie['name'] ?? 'Freebie'); ?>.
        </p>
        
        <?php if ($email): ?>
        <div class="email-info">
            <p>
                📧 Wir haben eine Bestätigung an <span class="email-address"><?php echo htmlspecialchars($email); ?></span> gesendet.
                Bitte überprüfe auch deinen Spam-Ordner.
            </p>
        </div>
        <?php endif; ?>
        
        <?php if ($dashboard_link): ?>
        <!-- DASHBOARD ZUGANG - HAUPT-CALL-TO-ACTION -->
        <div class="dashboard-section">
            <h2>🚀 Dein persönliches Dashboard</h2>
            <p>
                Greife jetzt auf deine Kurse zu<?php echo $referral_enabled ? ' und verdiene mit unserem Empfehlungsprogramm' : ''; ?>!
            </p>
            
            <a href="<?php echo htmlspecialchars($dashboard_link); ?>" class="dashboard-button">
                <i class="fas fa-rocket"></i>
                <span>Zum Dashboard</span>
            </a>
            
            <div class="dashboard-features">
                <div class="dashboard-feature">
                    <i class="fas fa-video"></i>
                    <span>Videokurse ansehen</span>
                </div>
                <div class="dashboard-feature">
                    <i class="fas fa-chart-line"></i>
                    <span>Fortschritt tracken</span>
                </div>
                <?php if ($referral_enabled): ?>
                <div class="dashboard-feature">
                    <i class="fas fa-gift"></i>
                    <span>Empfehlungen teilen</span>
                </div>
                <div class="dashboard-feature">
                    <i class="fas fa-star"></i>
                    <span>Belohnungen erhalten</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($video_url)): ?>
        <!-- Video Embed -->
        <div class="video-container">
            <?php
            // YouTube Embed Code automatisch generieren
            $embed_code = $video_url;
            
            // Wenn es eine YouTube URL ist, konvertiere zu Embed
            if (strpos($video_url, 'youtube.com/watch') !== false) {
                preg_match('/[?&]v=([^&]+)/', $video_url, $matches);
                if (isset($matches[1])) {
                    $embed_code = 'https://www.youtube.com/embed/' . $matches[1];
                }
            } elseif (strpos($video_url, 'youtu.be/') !== false) {
                $video_id = basename(parse_url($video_url, PHP_URL_PATH));
                $embed_code = 'https://www.youtube.com/embed/' . $video_id;
            } elseif (strpos($video_url, 'vimeo.com/') !== false) {
                $video_id = basename(parse_url($video_url, PHP_URL_PATH));
                $embed_code = 'https://player.vimeo.com/video/' . $video_id;
            }
            
            // Wenn es ein iframe ist, direkt ausgeben
            if (strpos($embed_code, '<iframe') !== false) {
                echo $embed_code;
            } else {
                // Ansonsten als iframe einbetten
                echo '<iframe src="' . htmlspecialchars($embed_code) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($download_url)): ?>
        <!-- Download Button -->
        <div class="download-section">
            <a href="<?php echo htmlspecialchars($download_url); ?>" class="download-button" target="_blank">
                <span class="download-icon">⬇</span>
                <span><?php echo htmlspecialchars($download_text); ?></span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Next Steps -->
        <div class="next-steps">
            <h2>Was passiert als Nächstes?</h2>
            
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Dashboard öffnen</h3>
                    <p>Klicke auf den Button oben, um zu deinem persönlichen Dashboard zu gelangen.</p>
                </div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Kurs starten</h3>
                    <p>Im Dashboard findest du alle deine Kurse und kannst direkt mit dem Lernen beginnen.</p>
                </div>
            </div>
            
            <?php if ($referral_enabled): ?>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Belohnungen verdienen</h3>
                    <p>Teile deinen Empfehlungslink und erhalte attraktive Belohnungen für jeden Lead!</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> - Alle Rechte vorbehalten</p>
            <div class="footer-links">
                <a href="<?php echo $impressum_link; ?>">Impressum</a>
                <a href="<?php echo $datenschutz_link; ?>">Datenschutz</a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($freebie['pixel_code'])): ?>
    <!-- Tracking Pixel -->
    <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
</body>
</html>