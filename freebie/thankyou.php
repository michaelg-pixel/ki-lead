<?php
/**
 * Danke-Seite nach Freebie-Anforderung
 * Direkter Zugang zum Freebie-Videokurs
 * Mit dynamischem Video-Content basierend auf Empfehlungsprogramm-Status
 */

require_once __DIR__ . '/../config/database.php';

// Freebie-ID und Customer-ID aus URL holen
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id_from_url = isset($_GET['customer']) ? (int)$_GET['customer'] : null;

if ($freebie_id <= 0) {
    die('Ungültige Freebie-ID');
}

// Freebie aus Datenbank laden - ERST customer_freebies prüfen, dann templates
$customer_id = $customer_id_from_url;
$is_customer_freebie = false;
$has_freebie_course = false;
$freebie_course_id = null;

try {
    // Zuerst prüfen, ob es ein Customer-Freebie ist
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            cf.customer_id,
            c.id as course_id,
            c.title as course_title,
            c.description as course_description,
            c.mockup_url as course_mockup,
            fc.id as freebie_course_id,
            fc.title as freebie_course_title
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        LEFT JOIN courses c ON f.course_id = c.id
        LEFT JOIN freebie_courses fc ON cf.id = fc.freebie_id
        WHERE cf.id = ?
    ");
    $stmt->execute([$freebie_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        // Es ist ein Customer-Freebie
        $customer_id = $freebie['customer_id'];
        $is_customer_freebie = true;
        
        // Prüfe ob es einen Freebie Course gibt
        if (!empty($freebie['freebie_course_id'])) {
            $has_freebie_course = true;
            $freebie_course_id = $freebie['freebie_course_id'];
            // Verwende freebie_course_title falls vorhanden
            if (!empty($freebie['freebie_course_title'])) {
                $freebie['course_title'] = $freebie['freebie_course_title'];
            }
        }
    } else {
        // Es ist ein Template-Freebie
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
        
        // Wenn customer_id in URL vorhanden, verwende diese
        if ($customer_id_from_url) {
            $customer_id = $customer_id_from_url;
        }
    }
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// Klick-Tracking für Danke-Seite
try {
    if ($is_customer_freebie) {
        $update = $pdo->prepare("UPDATE customer_freebies SET thank_you_clicks = COALESCE(thank_you_clicks, 0) + 1 WHERE id = ?");
    } else {
        $update = $pdo->prepare("UPDATE freebies SET thank_you_clicks = COALESCE(thank_you_clicks, 0) + 1 WHERE id = ?");
    }
    $update->execute([$freebie_id]);
} catch (PDOException $e) {
    // Tracking-Fehler ignorieren
}

// Standardwerte
$primary_color = $freebie['primary_color'] ?? '#7C3AED';
$background_color = $freebie['background_color'] ?? '#FFFFFF';
$headline_font = $freebie['headline_font'] ?? 'Poppins';
$body_font = $freebie['body_font'] ?? 'Poppins';

// Kurs-Button Text und URL
$video_button_text = $freebie['video_button_text'] ?? 'Zum Videokurs';

// Kurs-URL generieren mit öffentlichem Zugangs-Token
$video_course_url = '';

// PRIORITÄT 1: Freebie Course (custom course für dieses Freebie)
if ($has_freebie_course && $freebie_course_id) {
    $video_course_url = '/customer/freebie-course-player.php?id=' . $freebie_course_id;
}
// PRIORITÄT 2: Template Course ID (Kurs aus dem Template)
elseif (!empty($freebie['course_id'])) {
    $access_token = md5($freebie_id . '_' . $freebie['course_id'] . '_freebie_access');
    $video_course_url = '/customer/course-player.php?id=' . $freebie['course_id'] . '&access_token=' . $access_token . '&freebie_id=' . $freebie_id;
}
// PRIORITÄT 3: Manuelle URL (video_course_url Feld)
elseif (!empty($freebie['video_course_url'])) {
    $video_course_url = $freebie['video_course_url'];
}

// DEBUG: Logging für Entwicklung (kann später entfernt werden)
error_log("Freebie ID: $freebie_id, Has Freebie Course: " . ($has_freebie_course ? 'yes' : 'no') . ", Freebie Course ID: $freebie_course_id, Template Course ID: " . ($freebie['course_id'] ?? 'none') . ", Video URL: $video_course_url");

// Empfehlungsprogramm-Status prüfen
$referral_enabled = 0;
$ref_code = '';
$referral_url = '';

if ($customer_id) {
    try {
        $stmt = $pdo->prepare("SELECT ref_code, referral_enabled FROM users WHERE id = ?");
        $stmt->execute([$customer_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $ref_code = $user['ref_code'] ?? '';
        $referral_enabled = $user['referral_enabled'] ?? 0;
        
        // Nur wenn referral_enabled = 1 UND ref_code vorhanden ist, dann URL setzen
        if ($referral_enabled == 1 && $ref_code) {
            $referral_url = '/lead_login.php?ref=' . $ref_code;
        }
    } catch (PDOException $e) {
        // Fehler ignorieren
    }
}

// Video-URL basierend auf Empfehlungsprogramm-Status
$video_embed_url = '';
if ($referral_enabled == 1) {
    // Empfehlungsprogramm aktiv - Video 1134525205
    $video_embed_url = 'https://player.vimeo.com/video/1134525205';
} else {
    // Empfehlungsprogramm nicht aktiv - Video 1134525175
    $video_embed_url = 'https://player.vimeo.com/video/1134525175';
}

// KORREKTUR: Mockup-Bild Logik - Customer-Freebie Mockup hat Vorrang!
if ($is_customer_freebie) {
    $mockup_image = $freebie['mockup_image_url'] ?? $freebie['course_mockup'] ?? '';
} else {
    $mockup_image = $freebie['course_mockup'] ?? $freebie['mockup_image_url'] ?? '';
}

// Footer-Links mit customer_id
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

// Aktuelle URL für Bookmark-Funktion
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name'] ?? 'Dein Freebie'); ?> - Dein Zugang</title>
    
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
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: #1F2937;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Header Banner - Modernes Design */
        .header-banner {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
            padding: 16px 24px;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.3);
            position: relative;
            overflow: hidden;
        }
        
        .header-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            position: relative;
            z-index: 1;
        }
        
        .header-icon {
            font-size: 32px;
            animation: pulse-icon 2s ease-in-out infinite;
        }
        
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .header-text {
            color: white;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            line-height: 1.4;
        }
        
        .header-text strong {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 24px;
        }
        
        /* Hero Section - 2 Spalten Grid */
        .hero-section {
            background: white;
            border-radius: 32px;
            padding: 60px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
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
        
        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 60px;
            align-items: center;
        }
        
        /* Hero Content - Links */
        .hero-content {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        
        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .hero-headline {
            font-size: 56px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 900;
            color: #111827;
            line-height: 1.1;
            margin: 0;
        }
        
        .hero-highlight {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subheadline {
            font-size: 20px;
            color: #6b7280;
            line-height: 1.6;
            margin: 0;
        }
        
        .course-info {
            background: linear-gradient(135deg, var(--primary-light), rgba(124, 58, 237, 0.05));
            border-left: 4px solid var(--primary);
            padding: 24px 28px;
            border-radius: 16px;
        }
        
        .course-info-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        
        .course-info-title {
            font-size: 24px;
            font-weight: 800;
            color: #111827;
            line-height: 1.3;
        }
        
        /* Mockup in Hero */
        .hero-mockup {
            max-width: 250px;
            margin: 0 0 8px 0;
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
        
        .hero-mockup img {
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
            font-size: 60px;
        }
        
        /* Button Container */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        /* Main CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 22px 48px;
            border: none;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 10px 30px var(--primary-light);
            transition: all 0.3s;
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
        
        /* Referral Button */
        .referral-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 20px 48px;
            border: none;
            border-radius: 16px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }
        
        .referral-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s;
        }
        
        .referral-button:hover::before {
            left: 100%;
        }
        
        .referral-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }
        
        .access-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #9ca3af;
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Hero Video - Rechts */
        .hero-video {
            position: relative;
            width: 100%;
            max-width: 400px;
            justify-self: center;
        }
        
        .video-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 9/16;
            border-radius: 24px;
            background: #000;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Referral Promo Box */
        .referral-promo {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 3px solid #f59e0b;
            border-radius: 32px;
            padding: 48px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
            animation: slideUp 0.6s ease-out 0.2s both;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }
        
        .referral-promo::before {
            content: '🎁';
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 180px;
            opacity: 0.1;
        }
        
        .referral-promo-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .referral-promo-badge {
            display: inline-block;
            background: #f59e0b;
            color: white;
            padding: 10px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .referral-promo-title {
            font-size: 36px;
            font-weight: 900;
            color: #92400e;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .referral-promo-subtitle {
            font-size: 18px;
            color: #78350f;
            font-weight: 600;
        }
        
        .referral-benefits {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .benefit-item {
            background: white;
            padding: 28px;
            border-radius: 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .benefit-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .benefit-icon {
            font-size: 32px;
            flex-shrink: 0;
        }
        
        .benefit-content h4 {
            font-size: 18px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 8px;
        }
        
        .benefit-content p {
            font-size: 15px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .referral-cta {
            text-align: center;
        }
        
        .referral-cta-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 20px 48px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
            transition: all 0.3s;
        }
        
        .referral-cta-button:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(245, 158, 11, 0.5);
        }
        
        /* Bookmark Banner */
        .bookmark-banner {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 24px;
            padding: 32px;
            display: flex;
            align-items: center;
            gap: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.4s both;
            margin-bottom: 40px;
        }
        
        .bookmark-icon {
            font-size: 48px;
            flex-shrink: 0;
        }
        
        .bookmark-content {
            flex: 1;
        }
        
        .bookmark-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        
        .bookmark-text {
            font-size: 15px;
            color: #6b7280;
            margin-bottom: 16px;
            line-height: 1.6;
        }
        
        .bookmark-button {
            padding: 12px 28px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .bookmark-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }
        
        /* Info Steps */
        .info-steps {
            background: white;
            border-radius: 32px;
            padding: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            animation: slideUp 0.6s ease-out 0.5s both;
            margin-bottom: 60px;
        }
        
        .steps-title {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            text-align: center;
            margin-bottom: 48px;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 32px;
        }
        
        .step {
            text-align: center;
            padding: 40px 32px;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 24px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .step:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 12px 30px var(--primary-light);
        }
        
        .step-number {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 900;
            box-shadow: 0 8px 24px var(--primary-light);
        }
        
        .step h3 {
            font-size: 22px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 16px;
        }
        
        .step p {
            font-size: 16px;
            color: #6b7280;
            line-height: 1.7;
        }
        
        /* Footer */
        .footer {
            padding: 40px 24px;
            text-align: center;
        }
        
        .footer-content {
            background: white;
            border-radius: 24px;
            padding: 32px;
            max-width: 1400px;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        
        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
            font-weight: 500;
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
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .hero-video {
                max-width: 350px;
                margin: 0 auto;
            }
            
            .hero-content {
                text-align: center;
            }
            
            .success-badge,
            .hero-mockup {
                margin-left: auto;
                margin-right: auto;
            }
        }
        
        @media (max-width: 768px) {
            .header-banner {
                padding: 12px 16px;
            }
            
            .header-text {
                font-size: 14px;
            }
            
            .header-icon {
                font-size: 24px;
            }
            
            .container {
                padding: 32px 16px;
            }
            
            .hero-section {
                padding: 32px 24px;
                border-radius: 24px;
            }
            
            .hero-headline {
                font-size: 36px;
            }
            
            .hero-subheadline {
                font-size: 16px;
            }
            
            .course-info-title {
                font-size: 20px;
            }
            
            .hero-mockup {
                max-width: 200px;
            }
            
            .cta-button {
                width: 100%;
                padding: 20px 40px;
                font-size: 18px;
            }
            
            .referral-button {
                width: 100%;
                padding: 18px 36px;
                font-size: 16px;
            }
            
            .hero-video {
                max-width: 100%;
            }
            
            .referral-promo {
                padding: 32px 24px;
                border-radius: 24px;
            }
            
            .referral-promo-title {
                font-size: 28px;
            }
            
            .referral-benefits {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .bookmark-banner {
                flex-direction: column;
                text-align: center;
                padding: 24px;
            }
            
            .info-steps {
                padding: 32px 24px;
            }
            
            .steps-title {
                font-size: 24px;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .step {
                padding: 32px 24px;
            }
        }
    </style>
</head>
<body>
    <?php if ($referral_enabled == 1): ?>
    <!-- Header Banner - nur wenn Empfehlungsprogramm aktiv -->
    <div class="header-banner">
        <div class="header-content">
            <span class="header-icon">🛑</span>
            <div class="header-text">
                <strong>Wichtig:</strong> Bitte schließe diese Seite nicht, bevor du das komplette Video gesehen hast!
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container">
        
        <!-- Hero Section - 2 Spalten Grid -->
        <div class="hero-section">
            <div class="hero-grid">
                
                <!-- Links: Content & Buttons -->
                <div class="hero-content">
                    <div class="success-badge">
                        <span>✓</span>
                        <span>Erfolgreich angemeldet</span>
                    </div>
                    
                    <div>
                        <h1 class="hero-headline">
                            Vielen Dank!<br>
                            <span class="hero-highlight">Dein Zugang ist freigeschaltet</span>
                        </h1>
                        <p class="hero-subheadline">
                            Du hast jetzt sofortigen Zugang zu deinem exklusiven Freebie. Klicke auf den Button unten, um direkt zu starten.
                        </p>
                    </div>
                    
                    <?php if (!empty($freebie['course_title'])): ?>
                    <div class="course-info">
                        <div class="course-info-label">Dein Freebie</div>
                        <div class="course-info-title"><?php echo htmlspecialchars($freebie['course_title']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mockup Image -->
                    <?php if (!empty($mockup_image)): ?>
                        <div class="hero-mockup">
                            <img src="<?php echo htmlspecialchars($mockup_image); ?>" 
                                 alt="<?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name'] ?? 'Freebie'); ?>">
                        </div>
                    <?php else: ?>
                        <div class="hero-mockup">
                            <div class="mockup-placeholder">
                                🎓
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Button Container -->
                    <div class="button-container">
                        <?php if (!empty($video_course_url)): ?>
                            <a href="<?php echo htmlspecialchars($video_course_url); ?>" class="cta-button">
                                <span class="cta-icon">🚀</span>
                                <span><?php echo htmlspecialchars($video_button_text); ?></span>
                            </a>
                        <?php else: ?>
                            <!-- DEBUG: Button anzeigen auch wenn URL fehlt -->
                            <div style="padding: 20px; background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; text-align: center;">
                                <p style="color: #92400e; font-weight: 600; margin-bottom: 8px;">⚠️ Kein Videokurs konfiguriert</p>
                                <p style="color: #78350f; font-size: 14px;">Bitte fügen Sie einen Kurs im Dashboard hinzu.</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($referral_url)): ?>
                            <a href="<?php echo htmlspecialchars($referral_url); ?>" class="referral-button">
                                <span class="cta-icon">🎁</span>
                                <span>Zum Geschenk</span>
                            </a>
                        <?php endif; ?>
                        
                        <div class="access-info">
                            <span>⚡</span>
                            <span>Sofortiger Zugang</span>
                            <span>•</span>
                            <span>Keine Wartezeit</span>
                            <span>•</span>
                            <span>Direkt loslegen</span>
                        </div>
                    </div>
                </div>
                
                <!-- Rechts: Video -->
                <div class="hero-video">
                    <div class="video-wrapper">
                        <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>?autoplay=1&loop=0&autopause=0" 
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Referral Promo Box -->
        <?php if (!empty($referral_url)): ?>
        <div class="referral-promo">
            <div class="referral-promo-header">
                <div class="referral-promo-badge">🎁 Exklusives Angebot</div>
                <h2 class="referral-promo-title">Verdiene attraktive Belohnungen!</h2>
                <p class="referral-promo-subtitle">Teile dieses Freebie mit Freunden und erhalte tolle Prämien</p>
            </div>
            
            <div class="referral-benefits">
                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <div class="benefit-content">
                        <h4>Einfach & Schnell</h4>
                        <p>Erhalte deinen persönlichen Empfehlungslink und teile ihn mit deinem Netzwerk</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">💎</div>
                    <div class="benefit-content">
                        <h4>Attraktive Belohnungen</h4>
                        <p>Verdiene wertvolle Prämien für jeden Lead, den du uns bringst</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">📊</div>
                    <div class="benefit-content">
                        <h4>Live Tracking</h4>
                        <p>Behalte alle Klicks und Conversions in Echtzeit im Blick</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🏆</div>
                    <div class="benefit-content">
                        <h4>Bonus-System</h4>
                        <p>Je mehr Leads, desto höher deine Belohnungsstufe und Prämien</p>
                    </div>
                </div>
            </div>
            
            <div class="referral-cta">
                <a href="<?php echo htmlspecialchars($referral_url); ?>" class="referral-cta-button">
                    <span style="font-size: 24px;">🎁</span>
                    <span>Zum Empfehlungsprogramm</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
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
        
        <!-- Info Steps -->
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
                <a href="<?php echo htmlspecialchars($impressum_link); ?>">Impressum</a>
                <span style="color: #d1d5db;">•</span>
                <a href="<?php echo htmlspecialchars($datenschutz_link); ?>">Datenschutzerklärung</a>
            </div>
        </div>
    </div>
    
    <?php if (!empty($freebie['pixel_code'])): ?>
        <?php echo $freebie['pixel_code']; ?>
    <?php endif; ?>
    
    <script>
        function bookmarkPage() {
            const pageTitle = '<?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name'] ?? 'Dein Freebie'); ?> - Dein Zugang';
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