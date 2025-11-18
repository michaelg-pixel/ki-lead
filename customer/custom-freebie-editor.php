<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Freebie-Limit prüfen
$stmt = $pdo->prepare("SELECT freebie_limit FROM customer_freebie_limits WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$limitData = $stmt->fetch(PDO::FETCH_ASSOC);
$freebieLimit = $limitData['freebie_limit'] ?? 0;

// Anzahl eigener Freebies (alle ohne template_id - inkl. Marktplatz-Käufe)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND template_id IS NULL");
$stmt->execute([$customer_id]);
$customCount = $stmt->fetchColumn();

// Bearbeiten oder Neu?
$editMode = false;
$freebie = null;

if (isset($_GET['id'])) {
    $editMode = true;
    // FIX: Erlaubt Bearbeitung von ALLEN eigenen Freebies (custom + Marktplatz)
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND template_id IS NULL
    ");
    $stmt->execute([$_GET['id'], $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        die('Freebie nicht gefunden oder keine Berechtigung');
    }
}

// Verfügbare Kurse für Verlinkung laden
$stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formular speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_freebie'])) {
    $headline = trim($_POST['headline'] ?? '');
    $subheadline = trim($_POST['subheadline'] ?? '');
    $preheadline = trim($_POST['preheadline'] ?? '');
    $bullet_points = trim($_POST['bullet_points'] ?? '');
    $bullet_icon_style = $_POST['bullet_icon_style'] ?? 'standard';
    $cta_text = trim($_POST['cta_text'] ?? '');
    $layout = $_POST['layout'] ?? 'hybrid';
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $primary_color = $_POST['primary_color'] ?? '#8B5CF6';
    $raw_code = trim($_POST['raw_code'] ?? '');
    $custom_code = trim($_POST['custom_code'] ?? '');
    $mockup_image_url = trim($_POST['mockup_image_url'] ?? '');
    $video_url = trim($_POST['video_url'] ?? '');
    $video_format = $_POST['video_format'] ?? 'widescreen';
    
    // 🆕 FONT-FELDER (jetzt mit Pixelwerten)
    $font_heading = $_POST['font_heading'] ?? 'Inter';
    $font_body = $_POST['font_body'] ?? 'Inter';
    $font_size_headline = intval($_POST['font_size_headline'] ?? 28);
    $font_size_subheadline = intval($_POST['font_size_subheadline'] ?? 16);
    $font_size_bullet = intval($_POST['font_size_bullet'] ?? 14);
    $font_size_preheadline = intval($_POST['font_size_preheadline'] ?? 12);
    
    // Font sizes als JSON speichern
    $font_size = json_encode([
        'headline' => $font_size_headline,
        'subheadline' => $font_size_subheadline,
        'bullet' => $font_size_bullet,
        'preheadline' => $font_size_preheadline
    ]);
    
    // POPUP-FELDER
    $optin_display_mode = $_POST['optin_display_mode'] ?? 'direct';
    $popup_message = trim($_POST['popup_message'] ?? 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!');
    $cta_animation = $_POST['cta_animation'] ?? 'none';
    
    // Custom Code in raw_code speichern (mit Trennzeichen)
    $combined_code = $raw_code;
    if (!empty($custom_code)) {
        $combined_code .= "\n<!-- CUSTOM_TRACKING_CODE -->\n" . $custom_code;
    }
    
    // Unique ID für die Freebie-Seite
    if (!$freebie) {
        $unique_id = bin2hex(random_bytes(16));
        $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $headline)) . '-' . substr($unique_id, 0, 8);
    } else {
        $unique_id = $freebie['unique_id'];
        $url_slug = $freebie['url_slug'];
    }
    
    try {
        if ($freebie) {
            // Update existing - MIT FONT-FELDERN
            $stmt = $pdo->prepare("
                UPDATE customer_freebies SET
                    headline = ?, subheadline = ?, preheadline = ?,
                    bullet_points = ?, bullet_icon_style = ?, cta_text = ?, layout = ?,
                    background_color = ?, primary_color = ?, raw_code = ?,
                    mockup_image_url = ?, video_url = ?, video_format = ?,
                    optin_display_mode = ?, popup_message = ?, cta_animation = ?,
                    font_heading = ?, font_body = ?, font_size = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $headline, $subheadline, $preheadline,
                $bullet_points, $bullet_icon_style, $cta_text, $layout,
                $background_color, $primary_color, $combined_code,
                $mockup_image_url, $video_url, $video_format,
                $optin_display_mode, $popup_message, $cta_animation,
                $font_heading, $font_body, $font_size,
                $freebie['id']
            ]);
            
            $_SESSION['freebie_success'] = "✅ Freebie erfolgreich aktualisiert!";
            
            // Redirect zur gleichen Seite um doppeltes Speichern zu verhindern
            header("Location: /customer/custom-freebie-editor.php?id=" . $freebie['id']);
            exit;
            
        } else {
            // Create new - MIT FONT-FELDERN
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebies (
                    customer_id, headline, subheadline, preheadline,
                    bullet_points, bullet_icon_style, cta_text, layout, background_color, primary_color,
                    raw_code, mockup_image_url, video_url, video_format,
                    optin_display_mode, popup_message, cta_animation,
                    font_heading, font_body, font_size,
                    unique_id, url_slug, freebie_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'custom', NOW())
            ");
            $stmt->execute([
                $customer_id, $headline, $subheadline, $preheadline,
                $bullet_points, $bullet_icon_style, $cta_text, $layout, $background_color, $primary_color,
                $combined_code, $mockup_image_url, $video_url, $video_format,
                $optin_display_mode, $popup_message, $cta_animation,
                $font_heading, $font_body, $font_size,
                $unique_id, $url_slug
            ]);
            
            $freebie_id = $pdo->lastInsertId();
            
            $_SESSION['freebie_success'] = "✅ Freebie erfolgreich erstellt!";
            
            // 🔥 REDIRECT ZUR EDIT-SEITE MIT ID
            header("Location: /customer/custom-freebie-editor.php?id=" . $freebie_id);
            exit;
        }
        
    } catch (PDOException $e) {
        $error_message = "❌ Fehler: " . $e->getMessage();
    }
}

// Success Message aus Session holen (falls vorhanden)
$success_message = null;
if (isset($_SESSION['freebie_success'])) {
    $success_message = $_SESSION['freebie_success'];
    unset($_SESSION['freebie_success']);
}

// Custom Code aus raw_code extrahieren (wenn vorhanden)
$email_optin_code = '';
$custom_tracking_code = '';
if ($freebie && !empty($freebie['raw_code'])) {
    $parts = explode('<!-- CUSTOM_TRACKING_CODE -->', $freebie['raw_code']);
    $email_optin_code = trim($parts[0]);
    $custom_tracking_code = isset($parts[1]) ? trim($parts[1]) : '';
}

// Font Sizes aus JSON laden oder Standardwerte
$font_size_values = [
    'headline' => 28,
    'subheadline' => 16,
    'bullet' => 14,
    'preheadline' => 12
];

if ($freebie && !empty($freebie['font_size'])) {
    $decoded = json_decode($freebie['font_size'], true);
    if ($decoded) {
        $font_size_values = array_merge($font_size_values, $decoded);
    }
}

// Daten für Formular vorbereiten - MIT FONT-FELDERN
$form_data = [
    'headline' => $freebie['headline'] ?? 'Sichere dir jetzt deinen kostenlosen Zugang',
    'subheadline' => $freebie['subheadline'] ?? '',
    'preheadline' => $freebie['preheadline'] ?? '',
    'bullet_points' => $freebie['bullet_points'] ?? "Sofortiger Zugang\nProfessionelle Inhalte\nSchritt für Schritt Anleitung",
    'bullet_icon_style' => $freebie['bullet_icon_style'] ?? 'standard',
    'cta_text' => $freebie['cta_text'] ?? 'JETZT KOSTENLOS SICHERN',
    'layout' => $freebie['layout'] ?? 'hybrid',
    'background_color' => $freebie['background_color'] ?? '#FFFFFF',
    'primary_color' => $freebie['primary_color'] ?? '#8B5CF6',
    'raw_code' => $email_optin_code,
    'custom_code' => $custom_tracking_code,
    'mockup_image_url' => $freebie['mockup_image_url'] ?? '',
    'video_url' => $freebie['video_url'] ?? '',
    'video_format' => $freebie['video_format'] ?? 'widescreen',
    'optin_display_mode' => $freebie['optin_display_mode'] ?? 'direct',
    'popup_message' => $freebie['popup_message'] ?? 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
    'cta_animation' => $freebie['cta_animation'] ?? 'none',
    // 🆕 FONT-FELDER
    'font_heading' => $freebie['font_heading'] ?? 'Inter',
    'font_body' => $freebie['font_body'] ?? 'Inter',
    'font_size_headline' => $font_size_values['headline'],
    'font_size_subheadline' => $font_size_values['subheadline'],
    'font_size_bullet' => $font_size_values['bullet'],
    'font_size_preheadline' => $font_size_values['preheadline']
];

// 🆕 WEBFONTS UND GOOGLE FONTS
$webfonts = [
    'System UI' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    'Arial' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    'Georgia' => 'Georgia, "Times New Roman", serif',
    'Verdana' => 'Verdana, Geneva, sans-serif',
    'Helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif'
];

$google_fonts = [
    'Inter' => 'Inter:wght@400;600;700;800',
    'Roboto' => 'Roboto:wght@400;500;700;900',
    'Open Sans' => 'Open+Sans:wght@400;600;700;800',
    'Montserrat' => 'Montserrat:wght@400;600;700;800',
    'Poppins' => 'Poppins:wght@400;600;700;800'
];

// Google Fonts URLs generieren
$google_fonts_urls = [];
foreach ($google_fonts as $name => $family) {
    $google_fonts_urls[$name] = "https://fonts.googleapis.com/css2?family={$family}&display=swap";
}
?>