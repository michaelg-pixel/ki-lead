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
            max-width: 400px;
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
        
        /* KEINE text-align Regeln mehr in den CSS-Klassen - wird komplett per inline-style gesteuert */
        .preview-preheadline {
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .preview-headline {
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        
        .preview-subheadline {
            color: #6b7280;
            margin-bottom: 18px;
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
            flex-shrink: 0;
        }
        
        .preview-bullet-text {
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
        
        /* 🆕 PIXEL INPUT GRID */
        .pixel-inputs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-top: 16px;
        }
        
        .pixel-input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .pixel-input-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
        }
        
        .pixel-input-wrapper {
            position: relative;
        }
        
        .pixel-input {
            width: 100%;
            padding: 10px 32px 10px 12px;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .pixel-input:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .pixel-suffix {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            font-weight: 600;
            color: #9ca3af;
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
    <div class="editor-container">
        <div class="editor-header">
            <a href="/customer/dashboard.php?page=freebies" class="back-button">
                ← Zurück zur Übersicht
            </a>
            <h1><?php echo $editMode ? '✏️ Freebie bearbeiten' : '✨ Neues Freebie erstellen'; ?></h1>
            <p>Erstelle deine eigene individuelle Freebie-Seite</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <span><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="freebieForm">
            <div class="editor-grid">
                <!-- Linke Seite: Einstellungen -->
                <div class="editor-panel">
                    <h2 class="panel-title">⚙️ Einstellungen</h2>
                    
                    <!-- Rechtstexte Info -->
                    <div class="legal-info">
                        <h3>⚖️ Rechtstexte automatisch verknüpft!</h3>
                        <p>Sobald du dieses Freebie speicherst, werden automatisch deine Impressum- und Datenschutz-Links im Footer der Freebie-Seite angezeigt. Du kannst deine Rechtstexte unter <strong>"Dashboard → Rechtstexte"</strong> bearbeiten.</p>
                    </div>
                    
                    <!-- Video -->
                    <div class="form-section">
                        <div class="section-title">🎥 Video</div>
                        <div class="info-box">
                            <div class="info-box-title">💡 Hinweis</div>
                            <div class="info-box-text">
                                Füge hier die URL deines Videos ein (YouTube, Vimeo, etc.). Das Video wird automatisch eingebettet und responsiv dargestellt.
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Video URL</label>
                            <input type="url" name="video_url" id="videoUrl" class="form-input"
                                   value="<?php echo htmlspecialchars($form_data['video_url']); ?>"
                                   placeholder="https://www.youtube.com/watch?v=... oder https://vimeo.com/..."
                                   oninput="updatePreview()">
                            
                            <div class="video-format-options">
                                <label class="format-option <?php echo $form_data['video_format'] === 'widescreen' ? 'selected' : ''; ?>">
                                    <input type="radio" name="video_format" value="widescreen" 
                                           <?php echo $form_data['video_format'] === 'widescreen' ? 'checked' : ''; ?>
                                           onchange="updatePreview(); updateFormatSelection(this)">
                                    <div class="format-content">
                                        <div class="format-icon">🖥️</div>
                                        <div class="format-name">Widescreen (16:9)</div>
                                    </div>
                                    <div class="format-check">✓</div>
                                </label>
                                
                                <label class="format-option <?php echo $form_data['video_format'] === 'portrait' ? 'selected' : ''; ?>">
                                    <input type="radio" name="video_format" value="portrait"
                                           <?php echo $form_data['video_format'] === 'portrait' ? 'checked' : ''; ?>
                                           onchange="updatePreview(); updateFormatSelection(this)">
                                    <div class="format-content">
                                        <div class="format-icon">📱</div>
                                        <div class="format-name">Hochformat (9:16)</div>
                                    </div>
                                    <div class="format-check">✓</div>
                                </label>
                            </div>
                            
                            <?php if (!empty($form_data['video_url'])): ?>
                            <div class="video-preview" id="videoPreviewContainer">
                                <!-- Video preview will be inserted here by JavaScript -->
                            </div>
                            <div class="mockup-actions">
                                <button type="button" class="btn-mockup btn-mockup-remove" onclick="removeVideo()">
                                    🗑️ Video entfernen
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Mockup-Bild -->
                    <div class="form-section">
                        <div class="section-title">🖼️ Mockup-Bild</div>
                        <div class="info-box">
                            <div class="info-box-title">💡 Hinweis</div>
                            <div class="info-box-text">
                                Füge hier die URL deines Mockup-Bildes ein (z.B. von Imgur, Dropbox oder einem anderen Image-Hosting).
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Mockup-Bild URL</label>
                            <input type="url" name="mockup_image_url" id="mockupImageUrl" class="form-input"
                                   value="<?php echo htmlspecialchars($form_data['mockup_image_url']); ?>"
                                   placeholder="https://i.imgur.com/example.png"
                                   oninput="updatePreview()">
                            
                            <?php if (!empty($form_data['mockup_image_url'])): ?>
                            <div class="mockup-preview" id="mockupPreviewContainer">
                                <img src="<?php echo htmlspecialchars($form_data['mockup_image_url']); ?>" alt="Mockup Preview" id="mockupPreviewImg">
                            </div>
                            <div class="mockup-actions">
                                <button type="button" class="btn-mockup btn-mockup-remove" onclick="removeMockup()">
                                    🗑️ Mockup entfernen
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Texte -->
                    <div class="form-section">
                        <div class="section-title">✍️ Texte</div>
                        
                        <div class="form-group">
                            <label class="form-label">Vorüberschrift (optional)</label>
                            <input type="text" name="preheadline" class="form-input" 
                                   value="<?php echo htmlspecialchars($form_data['preheadline']); ?>"
                                   placeholder="NUR FÜR KURZE ZEIT"
                                   oninput="updatePreview()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hauptüberschrift *</label>
                            <input type="text" name="headline" class="form-input" required
                                   value="<?php echo htmlspecialchars($form_data['headline']); ?>"
                                   placeholder="Sichere dir jetzt deinen kostenlosen Zugang"
                                   oninput="updatePreview()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Unterüberschrift (optional)</label>
                            <input type="text" name="subheadline" class="form-input"
                                   value="<?php echo htmlspecialchars($form_data['subheadline']); ?>"
                                   placeholder="Starte noch heute und lerne die besten Strategien"
                                   oninput="updatePreview()">
                        </div>
                        
                        <!-- BULLET ICON STYLE AUSWAHL -->
                        <div class="form-group">
                            <label class="form-label">Bulletpoint-Stil</label>
                            <div class="bullet-style-options">
                                <label class="bullet-style-option <?php echo $form_data['bullet_icon_style'] === 'standard' ? 'selected' : ''; ?>">
                                    <input type="radio" name="bullet_icon_style" value="standard" 
                                           <?php echo $form_data['bullet_icon_style'] === 'standard' ? 'checked' : ''; ?>
                                           onchange="updatePreview(); updateBulletStyleSelection(this)">
                                    <div class="bullet-style-content">
                                        <div class="bullet-style-icon">✓</div>
                                        <div class="bullet-style-name">Standard Checkmarken</div>
                                        <div class="bullet-style-desc">Grüne Haken</div>
                                    </div>
                                    <div class="bullet-style-check">✓</div>
                                </label>
                                
                                <label class="bullet-style-option <?php echo $form_data['bullet_icon_style'] === 'custom' ? 'selected' : ''; ?>">
                                    <input type="radio" name="bullet_icon_style" value="custom"
                                           <?php echo $form_data['bullet_icon_style'] === 'custom' ? 'checked' : ''; ?>
                                           onchange="updatePreview(); updateBulletStyleSelection(this)">
                                    <div class="bullet-style-content">
                                        <div class="bullet-style-icon">🎨</div>
                                        <div class="bullet-style-name">Eigene Icons</div>
                                        <div class="bullet-style-desc">Emojis oder Icons</div>
                                    </div>
                                    <div class="bullet-style-check">✓</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Bullet Points (eine pro Zeile)</label>
                            <div class="info-box" id="bulletPointsHint">
                                <div class="info-box-title">💡 Hinweis</div>
                                <div class="info-box-text" id="bulletPointsHintText">
                                    Bei "Standard Checkmarken": Text eingeben, Haken werden automatisch hinzugefügt<br>
                                    Bei "Eigene Icons": Emoji/Icon am Anfang jeder Zeile einfügen (z.B. 💻 Text)
                                </div>
                            </div>
                            <textarea name="bullet_points" class="form-textarea" style="font-family: inherit;"
                                      oninput="updatePreview()"><?php echo htmlspecialchars($form_data['bullet_points']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Button Text *</label>
                            <input type="text" name="cta_text" class="form-input" required
                                   value="<?php echo htmlspecialchars($form_data['cta_text']); ?>"
                                   placeholder="JETZT KOSTENLOS SICHERN"
                                   oninput="updatePreview()">
                        </div>
                    </div>
                    
                    <!-- 🆕 SCHRIFTARTEN & SCHRIFTGRÖSSE MIT PIXEL-INPUTS -->
                    <div class="form-section">
                        <div class="section-title">✨ Schriftarten & Größe</div>
                        
                        <div class="info-box">
                            <div class="info-box-title">💡 Websichere & Google Fonts</div>
                            <div class="info-box-text">
                                Wähle zwischen websicheren Fonts (100% DSGVO-konform, keine externen Server) und Google Fonts (hochwertige Premium-Schriften).
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Überschrift-Schriftart</label>
                            <select name="font_heading" class="form-select" onchange="updatePreview()">
                                <optgroup label="🌐 Websichere Fonts (DSGVO-konform)">
                                    <?php foreach ($webfonts as $name => $stack): ?>
                                    <option value="<?php echo $name; ?>" <?php echo $form_data['font_heading'] === $name ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="🎨 Google Fonts (Premium-Qualität)">
                                    <?php foreach ($google_fonts as $name => $family): ?>
                                    <option value="<?php echo $name; ?>" <?php echo $form_data['font_heading'] === $name ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Text-Schriftart</label>
                            <select name="font_body" class="form-select" onchange="updatePreview()">
                                <optgroup label="🌐 Websichere Fonts (DSGVO-konform)">
                                    <?php foreach ($webfonts as $name => $stack): ?>
                                    <option value="<?php echo $name; ?>" <?php echo $form_data['font_body'] === $name ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="🎨 Google Fonts (Premium-Qualität)">
                                    <?php foreach ($google_fonts as $name => $family): ?>
                                    <option value="<?php echo $name; ?>" <?php echo $form_data['font_body'] === $name ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Schriftgrößen (in Pixel)</label>
                            <div class="pixel-inputs-grid">
                                <div class="pixel-input-group">
                                    <label class="pixel-input-label">Headline</label>
                                    <div class="pixel-input-wrapper">
                                        <input type="number" name="font_size_headline" class="pixel-input" 
                                               value="<?php echo $form_data['font_size_headline']; ?>" 
                                               min="12" max="72" step="1"
                                               oninput="updatePreview()">
                                        <span class="pixel-suffix">px</span>
                                    </div>
                                </div>
                                
                                <div class="pixel-input-group">
                                    <label class="pixel-input-label">Subheadline</label>
                                    <div class="pixel-input-wrapper">
                                        <input type="number" name="font_size_subheadline" class="pixel-input" 
                                               value="<?php echo $form_data['font_size_subheadline']; ?>" 
                                               min="10" max="32" step="1"
                                               oninput="updatePreview()">
                                        <span class="pixel-suffix">px</span>
                                    </div>
                                </div>
                                
                                <div class="pixel-input-group">
                                    <label class="pixel-input-label">Preheadline</label>
                                    <div class="pixel-input-wrapper">
                                        <input type="number" name="font_size_preheadline" class="pixel-input" 
                                               value="<?php echo $form_data['font_size_preheadline']; ?>" 
                                               min="8" max="24" step="1"
                                               oninput="updatePreview()">
                                        <span class="pixel-suffix">px</span>
                                    </div>
                                </div>
                                
                                <div class="pixel-input-group">
                                    <label class="pixel-input-label">Bullet Points</label>
                                    <div class="pixel-input-wrapper">
                                        <input type="number" name="font_size_bullet" class="pixel-input" 
                                               value="<?php echo $form_data['font_size_bullet']; ?>" 
                                               min="10" max="24" step="1"
                                               oninput="updatePreview()">
                                        <span class="pixel-suffix">px</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Layout -->
                    <div class="form-section">
                        <div class="section-title">🎨 Layout</div>
                        <div class="layout-options">
                            <label class="layout-option <?php echo $form_data['layout'] === 'hybrid' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="hybrid" 
                                       <?php echo $form_data['layout'] === 'hybrid' ? 'checked' : ''; ?>
                                       onchange="updatePreview(); updateLayoutSelection(this)">
                                <div class="layout-content">
                                    <div class="layout-icon">⚡</div>
                                    <div class="layout-name">Hybrid</div>
                                </div>
                                <div class="layout-check">✓</div>
                            </label>
                            
                            <label class="layout-option <?php echo $form_data['layout'] === 'centered' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="centered"
                                       <?php echo $form_data['layout'] === 'centered' ? 'checked' : ''; ?>
                                       onchange="updatePreview(); updateLayoutSelection(this)">
                                <div class="layout-content">
                                    <div class="layout-icon">◉</div>
                                    <div class="layout-name">Zentriert</div>
                                </div>
                                <div class="layout-check">✓</div>
                            </label>
                            
                            <label class="layout-option <?php echo $form_data['layout'] === 'sidebar' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="sidebar"
                                       <?php echo $form_data['layout'] === 'sidebar' ? 'checked' : ''; ?>
                                       onchange="updatePreview(); updateLayoutSelection(this)">
                                <div class="layout-content">
                                    <div class="layout-icon">▭</div>
                                    <div class="layout-name">Sidebar</div>
                                </div>
                                <div class="layout-check">✓</div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Farben -->
                    <div class="form-section">
                        <div class="section-title">🎨 Farben</div>
                        <div class="color-group">
                            <div class="form-group">
                                <label class="form-label">Hintergrundfarbe</label>
                                <div class="color-input-wrapper">
                                    <input type="color" id="background_color_picker"
                                           value="<?php echo htmlspecialchars($form_data['background_color']); ?>"
                                           class="color-preview"
                                           onchange="document.getElementById('background_color').value = this.value; updatePreview()">
                                    <input type="text" name="background_color" id="background_color"
                                           class="form-input color-input"
                                           value="<?php echo htmlspecialchars($form_data['background_color']); ?>"
                                           oninput="document.getElementById('background_color_picker').value = this.value; updatePreview()">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Primärfarbe</label>
                                <div class="color-input-wrapper">
                                    <input type="color" id="primary_color_picker" 
                                           value="<?php echo htmlspecialchars($form_data['primary_color']); ?>"
                                           class="color-preview"
                                           onchange="document.getElementById('primary_color').value = this.value; updatePreview()">
                                    <input type="text" name="primary_color" id="primary_color" 
                                           class="form-input color-input"
                                           value="<?php echo htmlspecialchars($form_data['primary_color']); ?>"
                                           oninput="document.getElementById('primary_color_picker').value = this.value; updatePreview()">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- E-MAIL OPTIN ANZEIGE-MODUS -->
                    <div class="form-section">
                        <div class="section-title">🎯 E-Mail Optin Anzeige</div>
                        <div class="info-box">
                            <div class="info-box-title">💡 Wähle, wie dein E-Mail Optin angezeigt wird</div>
                            <div class="info-box-text">
                                <strong>Direkt:</strong> Das Formular wird direkt auf der Seite angezeigt<br>
                                <strong>Popup:</strong> Ein Button öffnet ein stylisches Popup mit dem Formular
                            </div>
                        </div>
                        
                        <div class="popup-toggle-options">
                            <label class="toggle-option <?php echo $form_data['optin_display_mode'] === 'direct' ? 'selected' : ''; ?>">
                                <input type="radio" name="optin_display_mode" value="direct" 
                                       <?php echo $form_data['optin_display_mode'] === 'direct' ? 'checked' : ''; ?>
                                       onchange="togglePopupOptions(this); updatePreview()">
                                <div class="toggle-content">
                                    <div class="toggle-icon">📄</div>
                                    <div class="toggle-name">Direkt anzeigen</div>
                                </div>
                            </label>
                            
                            <label class="toggle-option <?php echo $form_data['optin_display_mode'] === 'popup' ? 'selected' : ''; ?>">
                                <input type="radio" name="optin_display_mode" value="popup"
                                       <?php echo $form_data['optin_display_mode'] === 'popup' ? 'checked' : ''; ?>
                                       onchange="togglePopupOptions(this); updatePreview()">
                                <div class="toggle-content">
                                    <div class="toggle-icon">✨</div>
                                    <div class="toggle-name">Als Popup</div>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Popup-spezifische Optionen -->
                        <div id="popupOptions" class="conditional-field <?php echo $form_data['optin_display_mode'] === 'popup' ? 'active' : ''; ?>">
                            <div class="form-group">
                                <label class="form-label">Popup-Nachricht</label>
                                <input type="text" name="popup_message" class="form-input"
                                       value="<?php echo htmlspecialchars($form_data['popup_message']); ?>"
                                       placeholder="Trage dich jetzt unverbindlich ein!"
                                       oninput="updatePreview()">
                                <small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">
                                    Diese Nachricht wird im Popup über dem Formular angezeigt
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Button-Animation</label>
                                <select name="cta_animation" class="form-select" onchange="updatePreview()">
                                    <option value="none" <?php echo $form_data['cta_animation'] === 'none' ? 'selected' : ''; ?>>
                                        Keine Animation
                                    </option>
                                    <option value="pulse" <?php echo $form_data['cta_animation'] === 'pulse' ? 'selected' : ''; ?>>
                                        Pulse (sanft pulsierend) ⭐
                                    </option>
                                    <option value="shake" <?php echo $form_data['cta_animation'] === 'shake' ? 'selected' : ''; ?>>
                                        Shake (wackelnd)
                                    </option>
                                    <option value="bounce" <?php echo $form_data['cta_animation'] === 'bounce' ? 'selected' : ''; ?>>
                                        Bounce (hüpfend)
                                    </option>
                                    <option value="glow" <?php echo $form_data['cta_animation'] === 'glow' ? 'selected' : ''; ?>>
                                        Glow (leuchtend)
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Erweiterte Einstellungen -->
                    <div class="form-section">
                        <div class="section-title">🔧 Erweiterte Einstellungen</div>
                        
                        <!-- Custom Code / Facebook Pixel -->
                        <div class="form-group">
                            <label class="form-label">Custom Code (Tracking Pixel, etc.)</label>
                            <div class="info-box">
                                <div class="info-box-title">💡 Hinweis</div>
                                <div class="info-box-text">
                                    Füge hier deinen Facebook Pixel, Google Analytics Code oder andere Tracking-Codes ein. 
                                    Der Code wird im <strong>&lt;head&gt;</strong> Bereich der Seite eingefügt.
                                </div>
                            </div>
                            <textarea name="custom_code" class="form-textarea" rows="6"
                                      placeholder='<!-- Facebook Pixel Code -->
<script>
  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){...};
</script>
<!-- End Facebook Pixel Code -->'><?php echo htmlspecialchars($form_data['custom_code']); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Raw Code / E-Mail Optin -->
                    <div class="form-section">
                        <div class="section-title">📧 E-Mail Optin Code</div>
                        <div class="info-box">
                            <div class="info-box-title">💡 Hinweis</div>
                            <div class="info-box-text">
                                Füge hier den HTML-Code deines E-Mail-Marketing-Tools ein (Quentn, Klicktipp, GetResponse, etc.). 
                                Das Formular wird automatisch integriert und responsiv dargestellt.
                            </div>
                        </div>
                        <textarea name="raw_code" class="form-textarea" rows="8"
                                  placeholder='<form action="https://..." method="post">
  <input type="email" name="email" placeholder="E-Mail">
  <button type="submit">Absenden</button>
</form>'><?php echo htmlspecialchars($form_data['raw_code']); ?></textarea>
                    </div>
                    
                    <button type="submit" name="save_freebie" class="save-button">
                        💾 Freebie speichern
                    </button>
                </div>
                
                <!-- Rechte Seite: Live-Vorschau -->
                <div class="preview-panel">
                    <div class="editor-panel">
                        <h2 class="panel-title">👁️ Live-Vorschau</h2>
                        
                        <?php if ($freebie && !empty($freebie['unique_id'])): ?>
                            <div class="preview-box" style="padding: 0; background: #f9fafb; overflow: hidden; min-height: 700px;">
                                <iframe 
                                    id="livePreview"
                                    src="https://app.mehr-infos-jetzt.de/freebie/index.php?id=<?php echo $freebie['unique_id']; ?>&preview=1" 
                                    style="width: 125%; height: 1000px; border: 2px dashed #d1d5db; border-radius: 12px; background: white; transform: scale(0.8); transform-origin: top left;"
                                    frameborder="0">
                                </iframe>
                            </div>
                            <div style="text-align: center; margin-top: 12px; color: #6b7280; font-size: 13px;">
                                💡 Die Vorschau wird nach dem Speichern automatisch aktualisiert
                            </div>
                        <?php else: ?>
                            <div class="preview-box" style="display: flex; align-items: center; justify-content: center; min-height: 400px; text-align: center;">
                                <div>
                                    <div style="font-size: 64px; margin-bottom: 16px;">👀</div>
                                    <h3 style="color: #374151; margin-bottom: 8px;">Vorschau nicht verfügbar</h3>
                                    <p style="color: #6b7280; font-size: 14px;">
                                        Speichere dein Freebie einmal ab, um die Live-Vorschau zu sehen
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        // 🆕 FONT STACKS MAPPING
        const fontStacks = {
            // Websafe Fonts
            'System UI': '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'Arial': 'Arial, "Helvetica Neue", Helvetica, sans-serif',
            'Helvetica': '"Helvetica Neue", Helvetica, Arial, sans-serif',
            'Verdana': 'Verdana, Geneva, sans-serif',
            'Trebuchet MS': '"Trebuchet MS", "Lucida Grande", sans-serif',
            'Georgia': 'Georgia, "Times New Roman", serif',
            'Times New Roman': '"Times New Roman", Times, Georgia, serif',
            'Courier New': '"Courier New", Courier, monospace',
            'Tahoma': 'Tahoma, Geneva, sans-serif',
            'Comic Sans MS': '"Comic Sans MS", "Comic Sans", cursive',
            // Google Fonts
            'Inter': '"Inter", sans-serif',
            'Roboto': '"Roboto", sans-serif',
            'Open Sans': '"Open Sans", sans-serif',
            'Montserrat': '"Montserrat", sans-serif',
            'Poppins': '"Poppins", sans-serif',
            'Lato': '"Lato", sans-serif',
            'Oswald': '"Oswald", sans-serif',
            'Raleway': '"Raleway", sans-serif',
            'Playfair Display': '"Playfair Display", serif',
            'Merriweather': '"Merriweather", serif'
        };
        
        // POPUP-TOGGLE FUNKTION
        function togglePopupOptions(radio) {
            const popupOptions = document.getElementById('popupOptions');
            const isPopup = radio.value === 'popup';
            
            if (isPopup) {
                popupOptions.classList.add('active');
            } else {
                popupOptions.classList.remove('active');
            }
            
            // Toggle-Option visuell markieren
            document.querySelectorAll('.toggle-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.toggle-option').classList.add('selected');
        }
        
        // BULLET STYLE TOGGLE FUNKTION
        function updateBulletStyleSelection(radio) {
            document.querySelectorAll('.bullet-style-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.bullet-style-option').classList.add('selected');
        }

        function removeVideo() {
            document.getElementById('videoUrl').value = '';
            const container = document.getElementById('videoPreviewContainer');
            if (container) {
                container.remove();
            }
            updatePreview();
        }

        function removeMockup() {
            document.getElementById('mockupImageUrl').value = '';
            const container = document.getElementById('mockupPreviewContainer');
            if (container) {
                container.remove();
            }
            updatePreview();
        }
        
        function updateFormatSelection(radio) {
            document.querySelectorAll('.format-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.format-option').classList.add('selected');
        }

        function updateLayoutSelection(radio) {
            document.querySelectorAll('.layout-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.layout-option').classList.add('selected');
        }
        
        // iFrame Reload nach Speichern
        <?php if (isset($success_message) && $freebie): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('livePreview');
            if (iframe) {
                const currentSrc = iframe.src.split('&t=')[0];
                iframe.src = currentSrc + '&t=' + Date.now();
                
                setTimeout(function() {
                    iframe.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
