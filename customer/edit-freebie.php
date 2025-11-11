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

// Anzahl eigener Freebies
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
$stmt->execute([$customer_id]);
$customCount = $stmt->fetchColumn();

// Bearbeiten oder Neu?
$editMode = false;
$freebie = null;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
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
    
    // 🆕 FONT-FELDER
    $font_heading = $_POST['font_heading'] ?? 'Inter';
    $font_body = $_POST['font_body'] ?? 'Inter';
    $font_size = $_POST['font_size'] ?? 'medium';
    
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
            
            $success_message = "✅ Freebie erfolgreich aktualisiert!";
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
            
            $success_message = "✅ Freebie erfolgreich erstellt!";
        }
        
        // Reload freebie
        if ($editMode) {
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
            $stmt->execute([$freebie['id']]);
            $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
            $stmt->execute([$freebie_id]);
            $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
            $editMode = true;
        }
        
    } catch (PDOException $e) {
        $error_message = "❌ Fehler: " . $e->getMessage();
    }
}

// Custom Code aus raw_code extrahieren (wenn vorhanden)
$email_optin_code = '';
$custom_tracking_code = '';
if ($freebie && !empty($freebie['raw_code'])) {
    $parts = explode('<!-- CUSTOM_TRACKING_CODE -->', $freebie['raw_code']);
    $email_optin_code = trim($parts[0]);
    $custom_tracking_code = isset($parts[1]) ? trim($parts[1]) : '';
}

// Daten für Formular vorbereiten - MIT FONT-FELDERN
$form_data = [
    'headline' => $freebie['headline'] ?? 'Sichere dir jetzt deinen kostenlosen Zugang',
    'subheadline' => $freebie['subheadline'] ?? '',
    'preheadline' => $freebie['preheadline'] ?? '',
    'bullet_points' => $freebie['bullet_points'] ?? "✓ Sofortiger Zugang\n✓ Professionelle Inhalte\n✓ Schritt für Schritt Anleitung",
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
    'font_size' => $freebie['font_size'] ?? 'medium'
];

// 🆕 WEBFONTS UND GOOGLE FONTS
$webfonts = [
    'System UI' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
    'Arial' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    'Helvetica' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
    'Verdana' => 'Verdana, Geneva, sans-serif',
    'Trebuchet MS' => '"Trebuchet MS", "Lucida Grande", sans-serif',
    'Georgia' => 'Georgia, "Times New Roman", serif',
    'Times New Roman' => '"Times New Roman", Times, Georgia, serif',
    'Courier New' => '"Courier New", Courier, monospace',
    'Tahoma' => 'Tahoma, Geneva, sans-serif',
    'Comic Sans MS' => '"Comic Sans MS", "Comic Sans", cursive'
];

$google_fonts = [
    'Inter' => 'Inter:wght@400;600;700;800',
    'Roboto' => 'Roboto:wght@400;500;700;900',
    'Open Sans' => 'Open+Sans:wght@400;600;700;800',
    'Montserrat' => 'Montserrat:wght@400;600;700;800',
    'Poppins' => 'Poppins:wght@400;600;700;800',
    'Lato' => 'Lato:wght@400;700;900',
    'Oswald' => 'Oswald:wght@400;600;700',
    'Raleway' => 'Raleway:wght@400;600;700;800',
    'Playfair Display' => 'Playfair+Display:wght@400;700;900',
    'Merriweather' => 'Merriweather:wght@400;700;900'
];

// Google Fonts URLs generieren
$google_fonts_urls = [];
foreach ($google_fonts as $name => $family) {
    $google_fonts_urls[$name] = "https://fonts.googleapis.com/css2?family={$family}&display=swap";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Freebie bearbeiten' : 'Neues Freebie erstellen'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- 🆕 GOOGLE FONTS DYNAMISCH LADEN -->
    <?php foreach ($google_fonts as $name => $family): ?>
    <link href="<?php echo $google_fonts_urls[$name]; ?>" rel="stylesheet">
    <?php endforeach; ?>
    
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
        }
        
        .editor-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .editor-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .editor-header h1 {
            color: #1a1a2e;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .editor-header p {
            color: #666;
            font-size: 14px;
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
        
        .editor-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }
        
        .editor-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .panel-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
            background: white;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: 'Courier New', monospace;
        }
        
        .layout-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .layout-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .layout-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        
        .layout-option input {
            position: absolute;
            opacity: 0;
        }
        
        .layout-option input:checked + .layout-content {
            color: #8B5CF6;
        }
        
        .layout-option input:checked ~ .layout-check {
            opacity: 1;
        }
        
        .layout-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .layout-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .layout-name {
            font-size: 13px;
            font-weight: 600;
        }
        
        .layout-check {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .color-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .color-input-wrapper {
            position: relative;
        }
        
        .color-preview {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
            cursor: pointer;
        }
        
        .color-input {
            padding-left: 56px !important;
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }
        
        .info-box-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .info-box-text {
            font-size: 13px;
            color: #1e3a8a;
            line-height: 1.6;
        }
        
        .mockup-preview {
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }
        
        .mockup-preview img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .video-preview {
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            background: #000;
        }
        
        .video-preview iframe {
            width: 100%;
            display: block;
        }
        
        .mockup-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .btn-mockup {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-mockup-remove {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .btn-mockup-remove:hover {
            background: rgba(239, 68, 68, 0.2);
        }
        
        .preview-panel {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .preview-mockup {
            width: 100%;
            max-width: 265px;
            height: auto;
            border-radius: 8px;
        }
        
        .preview-box {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 20px;
            min-height: 400px;
        }
        
        .preview-content {
            background: white;
            border-radius: 8px;
            padding: 30px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transform: scale(0.65);
            transform-origin: top center;
        }
        
        .preview-mockup {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .preview-mockup img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .preview-video {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .preview-video iframe {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .preview-preheadline {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .preview-headline {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .preview-subheadline {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 18px;
            text-align: center;
            line-height: 1.5;
        }
        
        .preview-bullets {
            margin-bottom: 18px;
            text-align: left;
        }
        
        .preview-bullet {
            display: flex;
            align-items: start;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .preview-bullet-icon {
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .preview-bullet-text {
            font-size: 12px;
            color: #374151;
            line-height: 1.4;
        }
        
        .preview-cta {
            text-align: center;
        }
        
        .preview-button {
            display: inline-block;
            padding: 10px 30px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .preview-button:hover {
            transform: translateY(-1px);
        }
        
        .save-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 24px;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        /* RECHTSTEXTE INFO */
        .legal-info {
            background: linear-gradient(135deg, #10B981, #059669);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            color: white;
        }
        
        .legal-info h3 {
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .legal-info p {
            font-size: 13px;
            opacity: 0.95;
            line-height: 1.6;
        }

        .video-format-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 12px;
        }

        .format-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }

        .format-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }

        .format-option input {
            position: absolute;
            opacity: 0;
        }

        .format-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }

        .format-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .format-name {
            font-size: 13px;
            font-weight: 600;
        }

        .format-check {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .format-option input:checked ~ .format-check {
            opacity: 1;
        }

        /* POPUP-STYLES */
        .popup-toggle-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .toggle-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }

        .toggle-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }

        .toggle-option input {
            position: absolute;
            opacity: 0;
        }

        .toggle-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }

        .toggle-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .toggle-name {
            font-size: 13px;
            font-weight: 600;
        }

        .conditional-field {
            display: none;
            animation: fadeIn 0.3s;
        }

        .conditional-field.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* CTA Button Animationen für Preview */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 5px currentColor; }
            50% { box-shadow: 0 0 20px currentColor; }
        }

        .animate-pulse { animation: pulse 2s ease-in-out infinite; }
        .animate-shake { animation: shake 0.5s ease-in-out infinite; }
        .animate-bounce { animation: bounce 1s ease-in-out infinite; }
        .animate-glow { animation: glow 2s ease-in-out infinite; }
        
        /* BULLET ICON STYLE OPTIONEN */
        .bullet-style-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .bullet-style-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .bullet-style-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        
        .bullet-style-option input {
            position: absolute;
            opacity: 0;
        }
        
        .bullet-style-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .bullet-style-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .bullet-style-name {
            font-size: 13px;
            font-weight: 600;
        }
        
        .bullet-style-desc {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .bullet-style-check {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .bullet-style-option input:checked ~ .bullet-style-check {
            opacity: 1;
        }
        
        /* 🆕 FONT-SIZE OPTIONS */
        .font-size-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 12px;
        }
        
        .font-size-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .font-size-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        
        .font-size-option input {
            position: absolute;
            opacity: 0;
        }
        
        .font-size-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .font-size-label {
            font-size: 13px;
            font-weight: 600;
        }
        
        .font-size-check {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 20px;
            height: 20px;
            background: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .font-size-option input:checked ~ .font-size-check {
            opacity: 1;
        }
        
        @media (max-width: 1200px) {
            .editor-grid {
                grid-template-columns: 1fr;
            }
            
            .preview-panel {
                position: static;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <!-- Content continues but truncated due to length limit -->
</body>
</html>