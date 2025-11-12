<?php
/**
 * Öffentliche Freebie-Ansicht via unique_id
 * Diese Datei akzeptiert die unique_id und zeigt das entsprechende Freebie an
 */

require_once __DIR__ . '/../config/database.php';

// unique_id aus URL holen
$unique_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($unique_id)) {
    http_response_code(400);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Ungültige Freebie-ID</h1><p>Bitte überprüfen Sie den Link.</p></body></html>');
}

try {
    // Freebie aus customer_freebies laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE unique_id = ?");
    $stmt->execute([$unique_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        http_response_code(404);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Nicht gefunden</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Freebie nicht gefunden</h1><p>Dieses Freebie existiert nicht oder wurde gelöscht.</p></body></html>');
    }
    
    // Customer-ID für Footer-Links
    $customer_id = $freebie['customer_id'];
    
} catch (PDOException $e) {
    http_response_code(500);
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Fehler</title></head><body style="font-family:Arial;padding:50px;text-align:center;"><h1>❌ Datenbankfehler</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>');
}

// Layout, Farben und Fonts
$layout = $freebie['layout'] ?? 'hybrid';
$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$background_color = $freebie['background_color'] ?? '#FFFFFF';

// Font-Einstellungen
$font_heading = $freebie['font_heading'] ?? 'Inter';
$font_body = $freebie['font_body'] ?? 'Inter';

// 🆕 PIXEL-BASIERTE SCHRIFTGRÖSSEN AUS JSON LADEN
$sizes = [
    'headline' => '48px',
    'subheadline' => '20px',
    'body' => '16px',
    'preheadline' => '14px'
];

// Versuche font_size JSON zu dekodieren
if (!empty($freebie['font_size'])) {
    $decoded = json_decode($freebie['font_size'], true);
    if ($decoded && is_array($decoded)) {
        // Pixel-Werte mit "px" Suffix
        if (isset($decoded['headline'])) {
            $sizes['headline'] = $decoded['headline'] . 'px';
        }
        if (isset($decoded['subheadline'])) {
            $sizes['subheadline'] = $decoded['subheadline'] . 'px';
        }
        if (isset($decoded['bullet'])) {
            $sizes['body'] = $decoded['bullet'] . 'px';
        }
        if (isset($decoded['preheadline'])) {
            $sizes['preheadline'] = $decoded['preheadline'] . 'px';
        }
    }
}

// Mockup und Video
$show_mockup = !empty($freebie['mockup_image_url']);
$mockup_url = $freebie['mockup_image_url'] ?? '';
$show_video = !empty($freebie['video_url']);
$video_url = $freebie['video_url'] ?? '';
$video_format = $freebie['video_format'] ?? 'widescreen';

// Footer-Links
$impressum_link = "/impressum.php?customer=" . $customer_id;
$datenschutz_link = "/datenschutz.php?customer=" . $customer_id;

// Video Embed URL ermitteln
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1];
    }
    
    // Vimeo - unterstützt verschiedene Formate:
    // - https://vimeo.com/1127089878
    // - https://player.vimeo.com/video/1127089878
    // - https://vimeo.com/channels/staffpicks/1127089878
    if (preg_match('/(?:player\.)?vimeo\.com\/(?:video\/|channels\/[\w-]+\/)?(\d+)/', $url, $match)) {
        return 'https://player.vimeo.com/video/' . $match[1];
    }
    
    return null;
}

$video_embed_url = getVideoEmbedUrl($video_url);

// Custom Code / Tracking UND E-Mail Optin Code extrahieren
$custom_tracking_code = '';
$email_optin_code = '';
if (!empty($freebie['raw_code'])) {
    $parts = explode('<!-- CUSTOM_TRACKING_CODE -->', $freebie['raw_code']);
    $email_optin_code = trim($parts[0]); // E-Mail Optin Code
    if (isset($parts[1])) {
        $custom_tracking_code = trim($parts[1]); // Tracking Code
    }
}

// Bullet Icon Style
$bullet_icon_style = $freebie['bullet_icon_style'] ?? 'standard';

// Email Optin Display Mode
$optin_display_mode = $freebie['optin_display_mode'] ?? 'direct';
$popup_message = $freebie['popup_message'] ?? 'Trage dich jetzt unverbindlich ein!';
$cta_animation = $freebie['cta_animation'] ?? 'none';

// Email Optin Felder
$optin_headline = $freebie['optin_headline'] ?? 'Sichere dir jetzt deinen kostenlosen Zugang';
$optin_button_text = $freebie['optin_button_text'] ?? 'JETZT KOSTENLOS SICHERN!!!';
$optin_email_placeholder = $freebie['optin_email_placeholder'] ?? 'Deine E-Mail-Adresse';
?>