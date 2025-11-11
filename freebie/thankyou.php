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
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --primary-dark: #1e40af;
            --text-dark: #374151;
            --text-light: #6b7280;
            --background: #f9fafb;
            --white: #ffffff;
            --border: #e5e7eb;
        }
        
        body {
            font-family: '<?php echo $body_font; ?>', sans-serif;
            background: var(--background);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Vollbreiter Header */
        .page-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 20px 0;
            box-shadow: 0 4px 20px var(--primary-light);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .hero-headline {
            font-size: 52px;
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-weight: 900;
            color: white;
            line-height: 1.1;
            margin: 16px 0;
        }
        
        .hero-subheadline {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            max-width: 700px;
        }
        
        /* Warning Banner */
        .warning-banner {
            background: linear-gradient(135deg, var(--text-dark), #1f2937);
            padding: 16px 0;
            box-shadow: 0 4px 20px rgba(55, 65, 81, 0.3);
        }
        
        .warning-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        
        .warning-icon {
            font-size: 24px;
        }
        
        .warning-text {
            color: white;
            font-size: 15px;
            font-weight: 700;
        }
        
        /* Main Layout - 2 Spalten über gesamte Seite */
        .main-layout {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 40px;
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 24px;
            align-items: start;
        }
        
        /* Content Area - Links */
        .content-area {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        
        .content-box {
            background: var(--white);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .course-info {
            background: var(--primary-light);
            border-left: 4px solid var(--primary);
            padding: 24px 28px;
            border-radius: 16px;
            margin-bottom: 32px;
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
            color: var(--text-dark);
            line-height: 1.3;
        }
        
        /* Button Container */
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        
        /* Buttons */
        .cta-button, .referral-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 18px 40px;
            border: none;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 24px var(--primary-light);
        }
        
        .cta-button:hover, .referral-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px var(--primary-light);
        }
        
        .access-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-light);
            font-size: 13px;
            font-weight: 500;
            padding-top: 8px;
        }
        
        /* Mockup Box - WEITER UNTEN */
        .mockup-box {
            text-align: center;
        }
        
        .mockup-image {
            max-width: 280px;
            margin: 0 auto;
        }
        
        .mockup-image img {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }
        
        .mockup-placeholder {
            width: 280px;
            aspect-ratio: 3/4;
            margin: 0 auto;
            background: var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
        }
        
        /* Referral Promo */
        .referral-promo {
            background: var(--primary-light);
            border: 3px solid var(--primary);
            border-radius: 24px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        
        .referral-promo::before {
            content: '🎁';
            position: absolute;
            top: -30px;
            right: -30px;
            font-size: 160px;
            opacity: 0.1;
        }
        
        .referral-promo-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .referral-promo-title {
            font-size: 32px;
            font-weight: 900;
            color: var(--text-dark);
            margin-bottom: 12px;
            line-height: 1.2;
        }
        
        .referral-promo-subtitle {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 24px;
        }
        
        .referral-benefits {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        
        .benefit-item {
            background: white;
            padding: 20px;
            border-radius: 16px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            border: 1px solid var(--border);
        }
        
        .benefit-icon {
            font-size: 28px;
            flex-shrink: 0;
        }
        
        .benefit-content h4 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        
        .benefit-content p {
            font-size: 14px;
            color: var(--text-light);
            line-height: 1.5;
        }
        
        .referral-cta-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 14px;
            font-size: 18px;
            font-weight: 700;
            box-shadow: 0 8px 24px var(--primary-light);
            transition: all 0.3s;
        }
        
        .referral-cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px var(--primary-light);
        }
        
        /* Video Sidebar - STICKY über gesamte Seite */
        .video-sidebar {
            position: sticky;
            top: 24px;
            align-self: start;
        }
        
        .video-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 9/16;
            border-radius: 20px;
            background: #000;
            overflow: hidden;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.3);
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Bookmark Banner */
        .bookmark-banner {
            display: flex;
            align-items: center;
            gap: 20px;
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
            color: var(--text-dark);
            margin-bottom: 8px;
        }
        
        .bookmark-text {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 14px;
            line-height: 1.6;
        }
        
        .bookmark-button {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .bookmark-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px var(--primary-light);
        }
        
        /* Steps */
        .steps-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 28px;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .step {
            text-align: center;
            padding: 32px 24px;
            background: var(--background);
            border-radius: 20px;
            border: 2px solid var(--border);
            transition: all 0.3s;
        }
        
        .step:hover {
            transform: translateY(-6px);
            border-color: var(--primary);
            box-shadow: 0 10px 24px var(--primary-light);
        }
        
        .step-number {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 900;
            box-shadow: 0 6px 20px var(--primary-light);
        }
        
        .step h3 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }
        
        .step p {
            font-size: 15px;
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background: var(--white);
            padding: 32px 24px;
            text-align: center;
            border-top: 1px solid var(--border);
            margin-top: 40px;
        }
        
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .footer-text {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 12px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        /* Warning Box Styling */
        .warning-box {
            padding: 20px; 
            background: var(--primary-light); 
            border: 2px solid var(--primary); 
            border-radius: 12px; 
            text-align: center;
        }
        
        .warning-box-title {
            color: var(--text-dark); 
            font-weight: 600; 
            margin-bottom: 8px;
        }
        
        .warning-box-text {
            color: var(--text-dark); 
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-layout {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            
            .video-sidebar {
                position: static;
                max-width: 400px;
                margin: 0 auto;
            }
            
            .steps-grid,
            .referral-benefits {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            /* Header mobile */
            .page-header {
                padding: 16px 0;
            }
            
            .header-content {
                padding: 0 16px;
            }
            
            .hero-headline {
                font-size: 28px;
                margin: 12px 0;
            }
            
            .hero-subheadline {
                font-size: 15px;
            }
            
            .success-badge {
                font-size: 12px;
                padding: 8px 16px;
            }
            
            /* Warning Banner mobile */
            .warning-banner {
                padding: 12px 0;
            }
            
            .warning-content {
                padding: 0 16px;
                gap: 8px;
            }
            
            .warning-icon {
                font-size: 20px;
            }
            
            .warning-text {
                font-size: 13px;
            }
            
            /* Main Layout - Mobile zuerst Video */
            .main-layout {
                padding: 24px 16px;
                gap: 24px;
                display: flex;
                flex-direction: column;
            }
            
            /* Video ZUERST auf Mobile */
            .video-sidebar {
                order: -1;
                max-width: 100%;
                position: static !important;
            }
            
            .video-wrapper {
                border-radius: 16px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            }
            
            /* Content danach */
            .content-area {
                order: 1;
                gap: 24px;
            }
            
            .content-box {
                padding: 24px 20px;
                border-radius: 20px;
            }
            
            .course-info {
                padding: 20px 24px;
                margin-bottom: 24px;
            }
            
            .course-info-title {
                font-size: 20px;
            }
            
            .button-container {
                gap: 12px;
            }
            
            .cta-button,
            .referral-button {
                width: 100%;
                font-size: 16px;
                padding: 16px 32px;
            }
            
            .access-info {
                font-size: 12px;
                flex-wrap: wrap;
            }
            
            /* Mockup mobile */
            .mockup-image {
                max-width: 200px;
            }
            
            .mockup-placeholder {
                width: 200px;
                font-size: 50px;
            }
            
            /* Referral Promo mobile */
            .referral-promo {
                padding: 24px 20px;
                border-radius: 20px;
            }
            
            .referral-promo-title {
                font-size: 24px;
            }
            
            .referral-promo-subtitle {
                font-size: 14px;
            }
            
            .referral-benefits {
                gap: 12px;
                margin-bottom: 24px;
            }
            
            .benefit-item {
                padding: 16px;
            }
            
            .benefit-icon {
                font-size: 24px;
            }
            
            .benefit-content h4 {
                font-size: 15px;
            }
            
            .benefit-content p {
                font-size: 13px;
            }
            
            .referral-cta-button {
                width: 100%;
                font-size: 16px;
                padding: 16px 32px;
            }
            
            /* Bookmark mobile */
            .bookmark-banner {
                flex-direction: column;
                text-align: center;
                padding: 24px 20px;
            }
            
            .bookmark-icon {
                font-size: 36px;
            }
            
            .bookmark-title {
                font-size: 17px;
            }
            
            .bookmark-text {
                font-size: 13px;
            }
            
            .bookmark-button {
                width: 100%;
            }
            
            /* Steps mobile */
            .steps-title {
                font-size: 22px;
                margin-bottom: 20px;
            }
            
            .steps-grid {
                gap: 16px;
            }
            
            .step {
                padding: 24px 20px;
            }
            
            .step-number {
                width: 60px;
                height: 60px;
                font-size: 30px;
                margin-bottom: 16px;
            }
            
            .step h3 {
                font-size: 18px;
                margin-bottom: 10px;
            }
            
            .step p {
                font-size: 14px;
            }
            
            /* Footer mobile */
            .footer {
                padding: 24px 16px;
            }
            
            .footer-text {
                font-size: 13px;
            }
            
            .footer-links {
                gap: 16px;
                font-size: 13px;
            }
        }
        
        /* Extra klein - sehr kleine Handys */
        @media (max-width: 375px) {
            .hero-headline {
                font-size: 24px;
            }
            
            .hero-subheadline {
                font-size: 14px;
            }
            
            .referral-promo-title {
                font-size: 20px;
            }
            
            .cta-button,
            .referral-button,
            .referral-cta-button {
                font-size: 15px;
                padding: 14px 28px;
            }
        }
    </style>
</head>
<body>
    <?php if ($referral_enabled == 1): ?>
    <!-- Warning Banner - nur wenn Empfehlungsprogramm aktiv -->
    <div class="warning-banner">
        <div class="warning-content">
            <span class="warning-icon">🛑</span>
            <div class="warning-text">
                <strong>WICHTIG:</strong> Bitte schließe diese Seite nicht, bevor du das komplette Video gesehen hast!
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Vollbreiter Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="success-badge">
                <span>✓</span>
                <span>Erfolgreich angemeldet</span>
            </div>
            
            <h1 class="hero-headline">
                Vielen Dank!<br>
                Dein Zugang ist freigeschaltet
            </h1>
            
            <p class="hero-subheadline">
                Du hast jetzt sofortigen Zugang zu deinem exklusiven Freebie. Klicke auf den Button unten, um direkt zu starten.
            </p>
        </div>
    </div>
    
    <!-- Main Layout - 2 Spalten über gesamte Seite -->
    <div class="main-layout">
        
        <!-- Content Area - Links -->
        <div class="content-area">
            
            <!-- Action Box -->
            <div class="content-box">
                <?php if (!empty($freebie['course_title'])): ?>
                <div class="course-info">
                    <div class="course-info-label">Dein Freebie</div>
                    <div class="course-info-title"><?php echo htmlspecialchars($freebie['course_title']); ?></div>
                </div>
                <?php endif; ?>
                
                <!-- Button Container -->
                <div class="button-container">
                    <?php if (!empty($video_course_url)): ?>
                        <a href="<?php echo htmlspecialchars($video_course_url); ?>" class="cta-button">
                            <span style="font-size: 22px;">🚀</span>
                            <span><?php echo htmlspecialchars($video_button_text); ?></span>
                        </a>
                    <?php else: ?>
                        <div class="warning-box">
                            <p class="warning-box-title">⚠️ Kein Videokurs konfiguriert</p>
                            <p class="warning-box-text">Bitte fügen Sie einen Kurs im Dashboard hinzu.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($referral_url)): ?>
                        <a href="<?php echo htmlspecialchars($referral_url); ?>" class="referral-button">
                            <span style="font-size: 22px;">🎁</span>
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
            
            <!-- Mockup Box - WEITER UNTEN -->
            <div class="content-box mockup-box">
                <?php if (!empty($mockup_image)): ?>
                    <div class="mockup-image">
                        <img src="<?php echo htmlspecialchars($mockup_image); ?>" 
                             alt="<?php echo htmlspecialchars($freebie['course_title'] ?? $freebie['name'] ?? 'Freebie'); ?>">
                    </div>
                <?php else: ?>
                    <div class="mockup-placeholder">
                        🎓
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Referral Promo -->
            <?php if (!empty($referral_url)): ?>
            <div class="content-box referral-promo">
                <div class="referral-promo-badge">🎁 Exklusives Angebot</div>
                <h2 class="referral-promo-title">Verdiene attraktive Belohnungen!</h2>
                <p class="referral-promo-subtitle">Teile dieses Freebie mit Freunden und erhalte tolle Prämien</p>
                
                <div class="referral-benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">🎯</div>
                        <div class="benefit-content">
                            <h4>Einfach & Schnell</h4>
                            <p>Erhalte deinen persönlichen Empfehlungslink</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">💎</div>
                        <div class="benefit-content">
                            <h4>Attraktive Belohnungen</h4>
                            <p>Verdiene wertvolle Prämien für jeden Lead</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">📊</div>
                        <div class="benefit-content">
                            <h4>Live Tracking</h4>
                            <p>Behalte alle Conversions in Echtzeit im Blick</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">🏆</div>
                        <div class="benefit-content">
                            <h4>Bonus-System</h4>
                            <p>Je mehr Leads, desto höher deine Prämien</p>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <a href="<?php echo htmlspecialchars($referral_url); ?>" class="referral-cta-button">
                        <span style="font-size: 20px;">🎁</span>
                        <span>Zum Geschenk</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bookmark Banner -->
            <div class="content-box bookmark-banner">
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
            
            <!-- Steps -->
            <div class="content-box">
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
        
        <!-- Video Sidebar - STICKY über gesamte Seite -->
        <div class="video-sidebar">
            <div class="video-wrapper">
                <iframe src="<?php echo htmlspecialchars($video_embed_url); ?>?autoplay=1&loop=0&autopause=0" 
                        frameborder="0" 
                        allow="autoplay; fullscreen; picture-in-picture" 
                        allowfullscreen>
                </iframe>
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
                window.sidebar.addPanel(pageTitle, pageURL, '');
            } else if (window.external && ('AddFavorite' in window.external)) {
                window.external.AddFavorite(pageURL, pageTitle);
            } else if (window.opera && window.print) {
                const elem = document.createElement('a');
                elem.setAttribute('href', pageURL);
                elem.setAttribute('title', pageTitle);
                elem.setAttribute('rel', 'sidebar');
                elem.click();
            } else {
                const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
                const shortcut = isMac ? 'Cmd+D' : 'Ctrl+D';
                alert(`✨ Drücke ${shortcut} um diese Seite als Lesezeichen zu speichern!\n\nSo hast du jederzeit Zugriff auf dein Freebie.`);
            }
        }
    </script>
</body>
</html>