<?php
/**
 * Custom Freebie Editor mit Tab-System
 * Tab 1: Einstellungen (Optin-Seite) - Vollständiges Design
 * Tab 2: Videokurs (Module & Lektionen)
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'settings';

// Bearbeiten oder Neu?
$editMode = false;
$freebie = null;
$course = null;

if (isset($_GET['id'])) {
    $editMode = true;
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$_GET['id'], $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        die('Freebie nicht gefunden');
    }
    
    // Kurs laden wenn vorhanden
    if ($freebie['has_course']) {
        $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
        $stmt->execute([$freebie['id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Formular speichern (Settings)
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
    $font_heading = $_POST['font_heading'] ?? 'Inter';
    $font_body = $_POST['font_body'] ?? 'Inter';
    $font_size = $_POST['font_size'] ?? 'medium';
    $optin_display_mode = $_POST['optin_display_mode'] ?? 'direct';
    $popup_message = trim($_POST['popup_message'] ?? 'Trage dich jetzt unverbindlich ein!');
    $cta_animation = $_POST['cta_animation'] ?? 'none';
    
    $combined_code = $raw_code;
    if (!empty($custom_code)) {
        $combined_code .= "\n<!-- CUSTOM_TRACKING_CODE -->\n" . $custom_code;
    }
    
    if (!$freebie) {
        $unique_id = bin2hex(random_bytes(16));
        $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $headline)) . '-' . substr($unique_id, 0, 8);
    } else {
        $unique_id = $freebie['unique_id'];
        $url_slug = $freebie['url_slug'];
    }
    
    try {
        if ($freebie) {
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
            $success_message = "✅ Einstellungen gespeichert!";
        } else {
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
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
            $stmt->execute([$freebie_id]);
            $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
            $editMode = true;
            
            $success_message = "✅ Freebie erstellt!";
            
            // Redirect to edit mode with course tab available
            header("Location: ?id={$freebie_id}&tab=settings");
            exit;
        }
        
        // Reload
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$freebie['id']]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "❌ Fehler: " . $e->getMessage();
    }
}

// Custom Code extrahieren
$email_optin_code = '';
$custom_tracking_code = '';
if ($freebie && !empty($freebie['raw_code'])) {
    $parts = explode('<!-- CUSTOM_TRACKING_CODE -->', $freebie['raw_code']);
    $email_optin_code = trim($parts[0]);
    $custom_tracking_code = isset($parts[1]) ? trim($parts[1]) : '';
}

// Form Data
$form_data = [
    'headline' => $freebie['headline'] ?? 'Sichere dir jetzt deinen kostenlosen Zugang',
    'subheadline' => $freebie['subheadline'] ?? '',
    'preheadline' => $freebie['preheadline'] ?? '',
    'bullet_points' => $freebie['bullet_points'] ?? "Sofortiger Zugang\nProfessionelle Inhalte\nSchritt für Schritt",
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
    'popup_message' => $freebie['popup_message'] ?? 'Trage dich jetzt ein!',
    'cta_animation' => $freebie['cta_animation'] ?? 'none',
    'font_heading' => $freebie['font_heading'] ?? 'Inter',
    'font_body' => $freebie['font_body'] ?? 'Inter',
    'font_size' => $freebie['font_size'] ?? 'medium'
];

// Fonts
$webfonts = [
    'System UI' => '-apple-system, BlinkMacSystemFont, sans-serif',
    'Arial' => 'Arial, sans-serif',
    'Helvetica' => 'Helvetica, sans-serif',
    'Verdana' => 'Verdana, sans-serif',
    'Georgia' => 'Georgia, serif',
    'Times New Roman' => '"Times New Roman", serif',
    'Courier New' => '"Courier New", monospace',
    'Tahoma' => 'Tahoma, sans-serif'
];

$google_fonts = [
    'Inter' => 'Inter:wght@400;600;700;800',
    'Roboto' => 'Roboto:wght@400;500;700;900',
    'Open Sans' => 'Open+Sans:wght@400;600;700',
    'Montserrat' => 'Montserrat:wght@400;600;700',
    'Poppins' => 'Poppins:wght@400;600;700',
    'Lato' => 'Lato:wght@400;700',
    'Oswald' => 'Oswald:wght@400;600',
    'Raleway' => 'Raleway:wght@400;600',
    'Playfair Display' => 'Playfair+Display:wght@400;700',
    'Merriweather' => 'Merriweather:wght@400;700'
];

$google_fonts_urls = [];
foreach ($google_fonts as $name => $family) {
    $google_fonts_urls[$name] = "https://fonts.googleapis.com/css2?family={$family}&display=swap";
}

// Module und Lektionen laden (für Videokurs-Tab)
$modules = [];
if ($course) {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               l.id as lesson_id, l.title as lesson_title, 
               l.description as lesson_description,
               l.video_url, l.pdf_url, l.sort_order as lesson_order
        FROM freebie_course_modules m
        LEFT JOIN freebie_course_lessons l ON m.id = l.module_id
        WHERE m.course_id = ?
        ORDER BY m.sort_order, l.sort_order
    ");
    $stmt->execute([$course['id']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $mid = $row['id'];
        if (!isset($modules[$mid])) {
            $modules[$mid] = [
                'id' => $mid,
                'title' => $row['title'],
                'description' => $row['description'],
                'lessons' => []
            ];
        }
        if ($row['lesson_id']) {
            $modules[$mid]['lessons'][] = [
                'id' => $row['lesson_id'],
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'video_url' => $row['video_url'],
                'pdf_url' => $row['pdf_url']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Freebie bearbeiten' : 'Neues Freebie'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php foreach ($google_fonts_urls as $url): ?>
    <link href="<?php echo $url; ?>" rel="stylesheet">
    <?php endforeach; ?>
    
    <style>
        /* Base Styles from custom-freebie-editor.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
        
        .editor-header p { color: #666; font-size: 14px; }
        
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
        
        .back-button:hover { gap: 12px; }
        
        /* TAB SYSTEM */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
        }
        
        .tab-btn {
            flex: 1;
            padding: 16px 24px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .tab-btn:hover { background: rgba(102, 126, 234, 0.1); }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        /* Rest of the styles from custom-freebie-editor.php */
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
        
        .form-section { margin-bottom: 32px; }
        .form-section:last-child { margin-bottom: 0; }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group { margin-bottom: 20px; }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
            background: white;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: 'Courier New', monospace;
        }
        
        /* Layout Options */
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
        
        .layout-option input { position: absolute; opacity: 0; }
        .layout-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .layout-icon { font-size: 32px; margin-bottom: 8px; }
        .layout-name { font-size: 13px; font-weight: 600; }
        
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
        
        /* Color Inputs */
        .color-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .color-input-wrapper { position: relative; }
        
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
        
        .color-input { padding-left: 56px !important; }
        
        /* Info Box */
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
        
        /* Legal Info */
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
        
        /* Preview */
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
        
        /* Preview Elements */
        .preview-mockup { text-align: center; margin-bottom: 20px; }
        .preview-mockup img { max-width: 100%; height: auto; border-radius: 8px; }
        .preview-video { text-align: center; margin-bottom: 20px; }
        .preview-video iframe { border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        
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
        
        .preview-bullets { margin-bottom: 18px; text-align: left; }
        
        .preview-bullet {
            display: flex;
            align-items: start;
            gap: 8px;
            margin-bottom: 10px;
        }
        
        .preview-bullet-icon { font-size: 14px; flex-shrink: 0; }
        .preview-bullet-text {
            font-size: 12px;
            color: #374151;
            line-height: 1.4;
        }
        
        .preview-cta { text-align: center; }
        
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
        
        .preview-button:hover { transform: translateY(-1px); }
        
        /* Buttons */
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
        
        .save-button:hover { transform: translateY(-2px); }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        /* Alert */
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
        
        /* Video Format Options */
        .video-format-options, .popup-toggle-options, .bullet-style-options, .font-size-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 12px;
        }
        
        .font-size-options { grid-template-columns: repeat(3, 1fr); }
        
        .format-option, .toggle-option, .bullet-style-option, .font-size-option {
            position: relative;
            cursor: pointer;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .format-option:hover, .toggle-option:hover, .bullet-style-option:hover, .font-size-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        
        .format-option input, .toggle-option input, .bullet-style-option input, .font-size-option input {
            position: absolute;
            opacity: 0;
        }
        
        .format-option.selected, .toggle-option.selected, .bullet-style-option.selected, .font-size-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        
        .format-icon, .toggle-icon, .bullet-style-icon {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .format-name, .toggle-name, .bullet-style-name, .font-size-label {
            font-size: 13px;
            font-weight: 600;
        }
        
        .bullet-style-desc {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .format-check, .bullet-style-check, .font-size-check {
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
        
        .format-option input:checked ~ .format-check,
        .bullet-style-option input:checked ~ .bullet-style-check,
        .font-size-option input:checked ~ .font-size-check {
            opacity: 1;
        }
        
        /* Conditional Field */
        .conditional-field {
            display: none;
            animation: fadeIn 0.3s;
        }
        
        .conditional-field.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Animations */
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
        
        /* Mockup/Video Actions */
        .mockup-preview, .video-preview {
            margin-top: 12px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e5e7eb;
        }
        
        .mockup-preview img { width: 100%; height: auto; display: block; }
        .video-preview { background: #000; }
        .video-preview iframe { width: 100%; display: block; }
        
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
        
        .btn-mockup-remove:hover { background: rgba(239, 68, 68, 0.2); }
        
        /* VIDEOKURS TAB STYLES */
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .module-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border: 2px solid #e5e7eb;
        }
        
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .module-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a2e;
        }
        
        .module-actions { display: flex; gap: 8px; }
        
        .lesson-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .lesson-info { flex: 1; }
        
        .lesson-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .lesson-meta {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .lesson-actions { display: flex; gap: 8px; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active { display: flex; }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
        }
        
        .modal-footer {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .editor-grid { grid-template-columns: 1fr; }
            .preview-panel {
                position: static;
                max-height: none;
            }
        }
        
        @media (max-width: 768px) {
            .tabs { flex-direction: column; }
            .module-header, .lesson-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="editor-container">
        <div class="editor-header">
            <a href="/customer/dashboard.php?page=freebies" class="back-button">← Zurück</a>
            <h1><?php echo $editMode ? '✏️ Freebie bearbeiten' : '✨ Neues Freebie'; ?></h1>
            <p>Erstelle und verwalte dein individuelles Freebie</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- TAB NAVIGATION -->
        <div class="tabs">
            <button class="tab-btn <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" 
                    onclick="switchTab('settings')">
                ⚙️ Einstellungen
            </button>
            <?php if ($editMode): ?>
            <button class="tab-btn <?php echo $active_tab === 'course' ? 'active' : ''; ?>" 
                    onclick="switchTab('course')">
                🎓 Videokurs
            </button>
            <?php endif; ?>
        </div>
        
        <!-- TAB 1: EINSTELLUNGEN (Vollständiges Design) -->
        <div class="tab-content <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" id="tab-settings">
            <form method="POST" id="freebieForm">
                <div class="editor-grid">
                    <!-- Linke Seite: Einstellungen (Complete from custom-freebie-editor.php) -->
                    <div class="editor-panel">
                        <h2 class="panel-title">⚙️ Einstellungen</h2>
                        
                        <!-- Rechtstexte Info -->
                        <div class="legal-info">
                            <h3>⚖️ Rechtstexte automatisch verknüpft!</h3>
                            <p>Sobald du dieses Freebie speicherst, werden automatisch deine Impressum- und Datenschutz-Links im Footer der Freebie-Seite angezeigt.</p>
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
                                       placeholder="https://www.youtube.com/watch?v=..."
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
                            </div>
                        </div>
                        
                        <!-- Mockup-Bild -->
                        <div class="form-section">
                            <div class="section-title">🖼️ Mockup-Bild</div>
                            <div class="info-box">
                                <div class="info-box-title">💡 Hinweis</div>
                                <div class="info-box-text">
                                    Füge hier die URL deines Mockup-Bildes ein (z.B. von Imgur, Dropbox).
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mockup-Bild URL</label>
                                <input type="url" name="mockup_image_url" id="mockupImageUrl" class="form-input"
                                       value="<?php echo htmlspecialchars($form_data['mockup_image_url']); ?>"
                                       placeholder="https://i.imgur.com/example.png"
                                       oninput="updatePreview()">
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
                                       placeholder="Starte noch heute"
                                       oninput="updatePreview()">
                            </div>
                            
                            <!-- BULLET ICON STYLE -->
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
                        
                        <!-- Schriftarten & Größe -->
                        <div class="form-section">
                            <div class="section-title">✨ Schriftarten & Größe</div>
                            
                            <div class="form-group">
                                <label class="form-label">Überschrift-Schriftart</label>
                                <select name="font_heading" class="form-select" onchange="updatePreview()">
                                    <optgroup label="🌐 Websichere Fonts">
                                        <?php foreach ($webfonts as $name => $stack): ?>
                                        <option value="<?php echo $name; ?>" <?php echo $form_data['font_heading'] === $name ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🎨 Google Fonts">
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
                                    <optgroup label="🌐 Websichere Fonts">
                                        <?php foreach ($webfonts as $name => $stack): ?>
                                        <option value="<?php echo $name; ?>" <?php echo $form_data['font_body'] === $name ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="🎨 Google Fonts">
                                        <?php foreach ($google_fonts as $name => $family): ?>
                                        <option value="<?php echo $name; ?>" <?php echo $form_data['font_body'] === $name ? 'selected' : ''; ?>>
                                            <?php echo $name; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Schriftgröße</label>
                                <div class="font-size-options">
                                    <label class="font-size-option <?php echo $form_data['font_size'] === 'small' ? 'selected' : ''; ?>">
                                        <input type="radio" name="font_size" value="small" 
                                               <?php echo $form_data['font_size'] === 'small' ? 'checked' : ''; ?>
                                               onchange="updatePreview(); updateFontSizeSelection(this)">
                                        <div class="font-size-label" style="font-size: 11px;">Klein</div>
                                        <div class="font-size-check">✓</div>
                                    </label>
                                    
                                    <label class="font-size-option <?php echo $form_data['font_size'] === 'medium' ? 'selected' : ''; ?>">
                                        <input type="radio" name="font_size" value="medium"
                                               <?php echo $form_data['font_size'] === 'medium' ? 'checked' : ''; ?>
                                               onchange="updatePreview(); updateFontSizeSelection(this)">
                                        <div class="font-size-label" style="font-size: 13px;">Mittel</div>
                                        <div class="font-size-check">✓</div>
                                    </label>
                                    
                                    <label class="font-size-option <?php echo $form_data['font_size'] === 'large' ? 'selected' : ''; ?>">
                                        <input type="radio" name="font_size" value="large"
                                               <?php echo $form_data['font_size'] === 'large' ? 'checked' : ''; ?>
                                               onchange="updatePreview(); updateFontSizeSelection(this)">
                                        <div class="font-size-label" style="font-size: 15px;">Groß</div>
                                        <div class="font-size-check">✓</div>
                                    </label>
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
                                        <div class="layout-icon">🎯</div>
                                        <div class="layout-name">Zentriert</div>
                                    </div>
                                    <div class="layout-check">✓</div>
                                </label>
                                
                                <label class="layout-option <?php echo $form_data['layout'] === 'sidebar' ? 'selected' : ''; ?>">
                                    <input type="radio" name="layout" value="sidebar"
                                           <?php echo $form_data['layout'] === 'sidebar' ? 'checked' : ''; ?>
                                           onchange="updatePreview(); updateLayoutSelection(this)">
                                    <div class="layout-content">
                                        <div class="layout-icon">📱</div>
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
                        
                        <!-- E-Mail Optin Anzeige -->
                        <div class="form-section">
                            <div class="section-title">🎯 E-Mail Optin Anzeige</div>
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
                            
                            <div id="popupOptions" class="conditional-field <?php echo $form_data['optin_display_mode'] === 'popup' ? 'active' : ''; ?>">
                                <div class="form-group">
                                    <label class="form-label">Popup-Nachricht</label>
                                    <input type="text" name="popup_message" class="form-input"
                                           value="<?php echo htmlspecialchars($form_data['popup_message']); ?>"
                                           placeholder="Trage dich jetzt ein!"
                                           oninput="updatePreview()">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Button-Animation</label>
                                    <select name="cta_animation" class="form-select" onchange="updatePreview()">
                                        <option value="none" <?php echo $form_data['cta_animation'] === 'none' ? 'selected' : ''; ?>>Keine</option>
                                        <option value="pulse" <?php echo $form_data['cta_animation'] === 'pulse' ? 'selected' : ''; ?>>Pulse ⭐</option>
                                        <option value="shake" <?php echo $form_data['cta_animation'] === 'shake' ? 'selected' : ''; ?>>Shake</option>
                                        <option value="bounce" <?php echo $form_data['cta_animation'] === 'bounce' ? 'selected' : ''; ?>>Bounce</option>
                                        <option value="glow" <?php echo $form_data['cta_animation'] === 'glow' ? 'selected' : ''; ?>>Glow</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Erweiterte Einstellungen -->
                        <div class="form-section">
                            <div class="section-title">🔧 Erweiterte Einstellungen</div>
                            <div class="form-group">
                                <label class="form-label">Custom Code (Tracking Pixel, etc.)</label>
                                <textarea name="custom_code" class="form-textarea" rows="6"><?php echo htmlspecialchars($form_data['custom_code']); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- E-Mail Optin Code -->
                        <div class="form-section">
                            <div class="section-title">📧 E-Mail Optin Code</div>
                            <textarea name="raw_code" class="form-textarea" rows="8"><?php echo htmlspecialchars($form_data['raw_code']); ?></textarea>
                        </div>
                        
                        <button type="submit" name="save_freebie" class="save-button">
                            💾 Freebie speichern
                        </button>
                    </div>
                    
                    <!-- Rechte Seite: Live-Vorschau -->
                    <div class="preview-panel">
                        <div class="editor-panel">
                            <h2 class="panel-title">👁️ Live-Vorschau</h2>
                            <div class="preview-box">
                                <div class="preview-content" id="previewContent"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- TAB 2: VIDEOKURS -->
        <?php if ($editMode): ?>
        <div class="tab-content <?php echo $active_tab === 'course' ? 'active' : ''; ?>" id="tab-course">
            <div class="editor-panel">
                <div class="course-header">
                    <div>
                        <h2>Videokurs Management</h2>
                        <p style="color: #666; font-size: 14px;">Erstelle Module und Lektionen für dein Freebie</p>
                    </div>
                    <?php if (!$course): ?>
                    <button type="button" class="btn btn-primary" onclick="createCourse()">+ Kurs erstellen</button>
                    <?php else: ?>
                    <button type="button" class="btn btn-primary" onclick="openModuleModal()">+ Modul hinzufügen</button>
                    <?php endif; ?>
                </div>
                
                <?php if (!$course): ?>
                    <div class="info-box">
                        <div class="info-box-title">🎓 Videokurs erstellen</div>
                        <div class="info-box-text">
                            Erstelle einen Videokurs für dein Freebie. Du kannst Module und Lektionen mit Videos und PDFs hinzufügen.
                        </div>
                    </div>
                <?php else: ?>
                    <div id="modulesList">
                        <?php foreach ($modules as $module): ?>
                        <div class="module-card">
                            <div class="module-header">
                                <div>
                                    <div class="module-title"><?php echo htmlspecialchars($module['title']); ?></div>
                                    <?php if ($module['description']): ?>
                                    <p style="color: #666; font-size: 14px;"><?php echo htmlspecialchars($module['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="module-actions">
                                    <button type="button" class="btn btn-secondary" onclick="openLessonModal(<?php echo $module['id']; ?>)">+ Lektion</button>
                                    <button type="button" class="btn btn-danger" onclick="deleteModule(<?php echo $module['id']; ?>)">🗑️</button>
                                </div>
                            </div>
                            
                            <div class="lessons-list">
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                <div class="lesson-item">
                                    <div class="lesson-info">
                                        <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                        <div class="lesson-meta">
                                            <?php if ($lesson['video_url']): ?>📹 Video<?php endif; ?>
                                            <?php if ($lesson['pdf_url']): ?>📄 PDF<?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="lesson-actions">
                                        <button type="button" class="btn btn-danger" onclick="deleteLesson(<?php echo $lesson['id']; ?>)">🗑️</button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Module Modal -->
    <div class="modal" id="moduleModal">
        <div class="modal-content">
            <div class="modal-header">Modul hinzufügen</div>
            <div class="form-group">
                <label class="form-label">Modultitel *</label>
                <input type="text" id="moduleTitle" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea id="moduleDescription" class="form-textarea"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModuleModal()">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveModule()">Speichern</button>
            </div>
        </div>
    </div>
    
    <!-- Lesson Modal -->
    <div class="modal" id="lessonModal">
        <div class="modal-content">
            <div class="modal-header">Lektion hinzufügen</div>
            <input type="hidden" id="lessonModuleId">
            <div class="form-group">
                <label class="form-label">Lektionstitel *</label>
                <input type="text" id="lessonTitle" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea id="lessonDescription" class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Video URL (YouTube/Vimeo)</label>
                <input type="url" id="lessonVideoUrl" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">PDF URL (optional)</label>
                <input type="url" id="lessonPdfUrl" class="form-input">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeLessonModal()">Abbrechen</button>
                <button type="button" class="btn btn-primary" onclick="saveLesson()">Speichern</button>
            </div>
        </div>
    </div>
    
    <script>
        const freebieId = <?php echo $freebie ? $freebie['id'] : 0; ?>;
        const courseId = <?php echo $course ? $course['id'] : 0; ?>;
        
        // Font Stacks
        const fontStacks = {
            'System UI': '-apple-system, BlinkMacSystemFont, sans-serif',
            'Arial': 'Arial, sans-serif',
            'Helvetica': 'Helvetica, sans-serif',
            'Verdana': 'Verdana, sans-serif',
            'Georgia': 'Georgia, serif',
            'Times New Roman': '"Times New Roman", serif',
            'Courier New': '"Courier New", monospace',
            'Tahoma': 'Tahoma, sans-serif',
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
        
        const fontSizes = {
            'small': { headline: '20px', subheadline: '12px', bullet: '11px', preheadline: '8px' },
            'medium': { headline: '22px', subheadline: '13px', bullet: '12px', preheadline: '9px' },
            'large': { headline: '26px', subheadline: '15px', bullet: '14px', preheadline: '10px' }
        };
        
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
        
        function togglePopupOptions(radio) {
            const popupOptions = document.getElementById('popupOptions');
            if (radio.value === 'popup') {
                popupOptions.classList.add('active');
            } else {
                popupOptions.classList.remove('active');
            }
            document.querySelectorAll('.toggle-option').forEach(opt => opt.classList.remove('selected'));
            radio.closest('.toggle-option').classList.add('selected');
        }
        
        function updateBulletStyleSelection(radio) {
            document.querySelectorAll('.bullet-style-option').forEach(opt => opt.classList.remove('selected'));
            radio.closest('.bullet-style-option').classList.add('selected');
        }
        
        function updateFontSizeSelection(radio) {
            document.querySelectorAll('.font-size-option').forEach(opt => opt.classList.remove('selected'));
            radio.closest('.font-size-option').classList.add('selected');
        }
        
        function updateFormatSelection(radio) {
            document.querySelectorAll('.format-option').forEach(opt => opt.classList.remove('selected'));
            radio.closest('.format-option').classList.add('selected');
        }
        
        function updateLayoutSelection(radio) {
            document.querySelectorAll('.layout-option').forEach(opt => opt.classList.remove('selected'));
            radio.closest('.layout-option').classList.add('selected');
        }
        
        function getVideoEmbedUrl(url) {
            if (!url) return null;
            let youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
            if (youtubeMatch) return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
            let vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
            if (vimeoMatch) return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
            return null;
        }
        
        function extractIconFromBullet(bullet) {
            const emojiRegex = /^([\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}])/u;
            const match = bullet.match(emojiRegex);
            if (match) return { icon: match[1], text: bullet.substring(match[1].length).trim() };
            const firstChar = bullet.charAt(0);
            if (firstChar && !/[a-zA-Z0-9\s]/.test(firstChar)) {
                return { icon: firstChar, text: bullet.substring(1).trim() };
            }
            return null;
        }
        
        function updatePreview() {
            const preheadline = document.querySelector('input[name="preheadline"]').value;
            const headline = document.querySelector('input[name="headline"]').value;
            const subheadline = document.querySelector('input[name="subheadline"]').value;
            const bulletPoints = document.querySelector('textarea[name="bullet_points"]').value;
            const bulletIconStyle = document.querySelector('input[name="bullet_icon_style"]:checked').value;
            const ctaText = document.querySelector('input[name="cta_text"]').value;
            const layout = document.querySelector('input[name="layout"]:checked').value;
            const primaryColor = document.getElementById('primary_color').value;
            const backgroundColor = document.getElementById('background_color').value;
            const mockupImageUrl = document.getElementById('mockupImageUrl').value;
            const videoUrl = document.getElementById('videoUrl').value;
            const videoFormat = document.querySelector('input[name="video_format"]:checked').value;
            const fontHeading = document.querySelector('select[name="font_heading"]').value;
            const fontBody = document.querySelector('select[name="font_body"]').value;
            const fontSize = document.querySelector('input[name="font_size"]:checked').value;
            const ctaAnimation = document.querySelector('select[name="cta_animation"]').value;
            
            const headingFontFamily = fontStacks[fontHeading] || fontStacks['Inter'];
            const bodyFontFamily = fontStacks[fontBody] || fontStacks['Inter'];
            const sizes = fontSizes[fontSize] || fontSizes['medium'];
            
            const previewContent = document.getElementById('previewContent');
            previewContent.style.background = backgroundColor;
            previewContent.style.fontFamily = bodyFontFamily;
            
            let bulletHTML = '';
            if (bulletPoints.trim()) {
                const bullets = bulletPoints.split('\n').filter(b => b.trim());
                bulletHTML = bullets.map(bullet => {
                    let icon = '✓';
                    let text = bullet;
                    if (bulletIconStyle === 'custom') {
                        const extracted = extractIconFromBullet(bullet);
                        if (extracted) {
                            icon = extracted.icon;
                            text = extracted.text;
                        } else {
                            text = bullet;
                        }
                    } else {
                        text = bullet.replace(/^[✓✔︎•-]\s*/, '').trim();
                    }
                    const iconColor = bulletIconStyle === 'standard' ? primaryColor : 'inherit';
                    return `
                        <div class="preview-bullet">
                            <span class="preview-bullet-icon" style="color: ${iconColor}; font-size: ${sizes.bullet};">${icon}</span>
                            <span class="preview-bullet-text" style="font-family: ${bodyFontFamily}; font-size: ${sizes.bullet};">${escapeHtml(text)}</span>
                        </div>
                    `;
                }).join('');
                bulletHTML = `<div class="preview-bullets">${bulletHTML}</div>`;
            }
            
            let mockupHTML = '';
            if (mockupImageUrl) {
                mockupHTML = `<div class="preview-mockup"><img src="${escapeHtml(mockupImageUrl)}" alt="Mockup" style="max-width: 180px;"></div>`;
            }
            
            let videoHTML = '';
            const embedUrl = getVideoEmbedUrl(videoUrl);
            if (embedUrl) {
                const isPortrait = videoFormat === 'portrait';
                const videoWidth = isPortrait ? '315px' : '100%';
                const videoHeight = isPortrait ? '560px' : '315px';
                const videoMaxWidth = isPortrait ? '315px' : '560px';
                videoHTML = `
                    <div class="preview-video" style="max-width: ${videoMaxWidth}; margin: 0 auto;">
                        <iframe width="${videoWidth}" height="${videoHeight}" src="${embedUrl}" frameborder="0" allowfullscreen style="display: block; margin: 0 auto;"></iframe>
                    </div>
                `;
            }
            
            const preheadlineHTML = preheadline ? `<div class="preview-preheadline" style="color: ${primaryColor}; font-family: ${bodyFontFamily}; font-size: ${sizes.preheadline};">${escapeHtml(preheadline)}</div>` : '';
            const subheadlineHTML = subheadline ? `<div class="preview-subheadline" style="font-family: ${bodyFontFamily}; font-size: ${sizes.subheadline};">${escapeHtml(subheadline)}</div>` : '';
            const mediaElement = videoHTML || mockupHTML || `<div style="text-align: center; color: ${primaryColor}; font-size: 50px;">🎁</div>`;
            const animationClass = ctaAnimation !== 'none' ? `animate-${ctaAnimation}` : '';
            const ctaButton = `<button class="preview-button ${animationClass}" style="background: ${primaryColor}; color: white; font-family: ${bodyFontFamily};">${escapeHtml(ctaText || 'BUTTON TEXT')}</button>`;
            
            let layoutHTML = '';
            if (layout === 'centered') {
                layoutHTML = `
                    <div style="max-width: 800px; margin: 0 auto;">
                        ${mediaElement}${preheadlineHTML}
                        <div class="preview-headline" style="color: ${primaryColor}; font-family: ${headingFontFamily}; font-size: ${sizes.headline};">${escapeHtml(headline || 'Deine Hauptüberschrift')}</div>
                        ${subheadlineHTML}${bulletHTML}<div class="preview-cta">${ctaButton}</div>
                    </div>
                `;
            } else if (layout === 'hybrid') {
                layoutHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                        <div>${mediaElement}</div>
                        <div>
                            ${preheadlineHTML}
                            <div class="preview-headline" style="color: ${primaryColor}; text-align: left; font-family: ${headingFontFamily}; font-size: ${sizes.headline};">${escapeHtml(headline || 'Deine Hauptüberschrift')}</div>
                            ${subheadlineHTML ? `<div class="preview-subheadline" style="text-align: left; font-family: ${bodyFontFamily}; font-size: ${sizes.subheadline};">${escapeHtml(subheadline)}</div>` : ''}
                            ${bulletHTML}<div class="preview-cta" style="text-align: left;">${ctaButton}</div>
                        </div>
                    </div>
                `;
            } else {
                layoutHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                        <div>
                            ${preheadlineHTML}
                            <div class="preview-headline" style="color: ${primaryColor}; text-align: left; font-family: ${headingFontFamily}; font-size: ${sizes.headline};">${escapeHtml(headline || 'Deine Hauptüberschrift')}</div>
                            ${subheadlineHTML ? `<div class="preview-subheadline" style="text-align: left; font-family: ${bodyFontFamily}; font-size: ${sizes.subheadline};">${escapeHtml(subheadline)}</div>` : ''}
                            ${bulletHTML}<div class="preview-cta" style="text-align: left;">${ctaButton}</div>
                        </div>
                        <div>${mediaElement}</div>
                    </div>
                `;
            }
            previewContent.innerHTML = layoutHTML;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Videokurs API Funktionen
        async function apiCall(action, data) {
            const response = await fetch('/customer/api/freebie-course-api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action, ...data})
            });
            return await response.json();
        }
        
        async function createCourse() {
            const title = prompt('Kurstitel:');
            if (!title) return;
            const result = await apiCall('create_course', { freebie_id: freebieId, title: title, description: '' });
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
        }
        
        function openModuleModal() { document.getElementById('moduleModal').classList.add('active'); }
        function closeModuleModal() {
            document.getElementById('moduleModal').classList.remove('active');
            document.getElementById('moduleTitle').value = '';
            document.getElementById('moduleDescription').value = '';
        }
        
        async function saveModule() {
            const title = document.getElementById('moduleTitle').value;
            if (!title) { alert('Bitte Titel eingeben'); return; }
            const result = await apiCall('create_module', {
                course_id: courseId,
                title: title,
                description: document.getElementById('moduleDescription').value
            });
            if (result.success) { location.reload(); } else { alert('Fehler: ' + result.error); }
        }
        
        async function deleteModule(moduleId) {
            if (!confirm('Modul wirklich löschen?')) return;
            const result = await apiCall('delete_module', { module_id: moduleId });
            if (result.success) { location.reload(); } else { alert('Fehler: ' + result.error); }
        }
        
        function openLessonModal(moduleId) {
            document.getElementById('lessonModuleId').value = moduleId;
            document.getElementById('lessonModal').classList.add('active');
        }
        
        function closeLessonModal() {
            document.getElementById('lessonModal').classList.remove('active');
            document.getElementById('lessonTitle').value = '';
            document.getElementById('lessonDescription').value = '';
            document.getElementById('lessonVideoUrl').value = '';
            document.getElementById('lessonPdfUrl').value = '';
        }
        
        async function saveLesson() {
            const title = document.getElementById('lessonTitle').value;
            if (!title) { alert('Bitte Titel eingeben'); return; }
            const result = await apiCall('create_lesson', {
                module_id: document.getElementById('lessonModuleId').value,
                title: title,
                description: document.getElementById('lessonDescription').value,
                video_url: document.getElementById('lessonVideoUrl').value,
                pdf_url: document.getElementById('lessonPdfUrl').value
            });
            if (result.success) { location.reload(); } else { alert('Fehler: ' + result.error); }
        }
        
        async function deleteLesson(lessonId) {
            if (!confirm('Lektion wirklich löschen?')) return;
            const result = await apiCall('delete_lesson', { lesson_id: lessonId });
            if (result.success) { location.reload(); } else { alert('Fehler: ' + result.error); }
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
</body>
</html>