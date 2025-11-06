<?php
/**
 * Custom Freebie Editor mit Tab-System
 * Tab 1: Einstellungen (Optin-Seite)
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
            
            // Redirect to edit mode
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
    'Georgia' => 'Georgia, serif'
];

$google_fonts = [
    'Inter' => 'Inter:wght@400;600;700;800',
    'Roboto' => 'Roboto:wght@400;500;700;900',
    'Open Sans' => 'Open+Sans:wght@400;600;700',
    'Montserrat' => 'Montserrat:wght@400;600;700',
    'Poppins' => 'Poppins:wght@400;600;700'
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        /* TAB SYSTEM */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 8px;
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
        
        .tab-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
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
        
        .form-input, .form-textarea, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        
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
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        
        .btn-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .save-btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            margin-top: 24px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
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
        
        /* VIDEOKURS TAB */
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
        
        .module-actions {
            display: flex;
            gap: 8px;
        }
        
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
        
        .lesson-info {
            flex: 1;
        }
        
        .lesson-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 4px;
        }
        
        .lesson-meta {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .lesson-actions {
            display: flex;
            gap: 8px;
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
        
        .modal.active {
            display: flex;
        }
        
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
        
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
            }
            
            .module-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .lesson-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="/customer/dashboard.php?page=freebies" class="back-btn">← Zurück</a>
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
        
        <!-- TAB 1: EINSTELLUNGEN -->
        <div class="tab-content <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" id="tab-settings">
            <form method="POST">
                <div class="panel">
                    <h2 style="margin-bottom: 24px;">Optin-Seite Einstellungen</h2>
                    
                    <div class="form-group">
                        <label class="form-label">Hauptüberschrift *</label>
                        <input type="text" name="headline" class="form-input" required
                               value="<?php echo htmlspecialchars($form_data['headline']); ?>"
                               placeholder="Sichere dir jetzt deinen kostenlosen Zugang">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Unterüberschrift</label>
                        <input type="text" name="subheadline" class="form-input"
                               value="<?php echo htmlspecialchars($form_data['subheadline']); ?>"
                               placeholder="Starte noch heute">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Bullet Points (eine pro Zeile)</label>
                        <textarea name="bullet_points" class="form-textarea"><?php echo htmlspecialchars($form_data['bullet_points']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Button Text *</label>
                        <input type="text" name="cta_text" class="form-input" required
                               value="<?php echo htmlspecialchars($form_data['cta_text']); ?>"
                               placeholder="JETZT KOSTENLOS SICHERN">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Primärfarbe</label>
                        <input type="color" name="primary_color" class="form-input"
                               value="<?php echo htmlspecialchars($form_data['primary_color']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-Mail Optin Code</label>
                        <div class="info-box">
                            <div class="info-box-title">💡 Hinweis</div>
                            <div class="info-box-text">
                                Füge hier den HTML-Code deines E-Mail-Tools ein (Quentn, Klicktipp, etc.)
                            </div>
                        </div>
                        <textarea name="raw_code" class="form-textarea" rows="8"><?php echo htmlspecialchars($form_data['raw_code']); ?></textarea>
                    </div>
                    
                    <input type="hidden" name="layout" value="<?php echo $form_data['layout']; ?>">
                    <input type="hidden" name="background_color" value="<?php echo $form_data['background_color']; ?>">
                    <input type="hidden" name="preheadline" value="<?php echo $form_data['preheadline']; ?>">
                    <input type="hidden" name="bullet_icon_style" value="<?php echo $form_data['bullet_icon_style']; ?>">
                    <input type="hidden" name="mockup_image_url" value="<?php echo $form_data['mockup_image_url']; ?>">
                    <input type="hidden" name="video_url" value="<?php echo $form_data['video_url']; ?>">
                    <input type="hidden" name="video_format" value="<?php echo $form_data['video_format']; ?>">
                    <input type="hidden" name="optin_display_mode" value="<?php echo $form_data['optin_display_mode']; ?>">
                    <input type="hidden" name="popup_message" value="<?php echo $form_data['popup_message']; ?>">
                    <input type="hidden" name="cta_animation" value="<?php echo $form_data['cta_animation']; ?>">
                    <input type="hidden" name="font_heading" value="<?php echo $form_data['font_heading']; ?>">
                    <input type="hidden" name="font_body" value="<?php echo $form_data['font_body']; ?>">
                    <input type="hidden" name="font_size" value="<?php echo $form_data['font_size']; ?>">
                    <input type="hidden" name="custom_code" value="<?php echo htmlspecialchars($form_data['custom_code']); ?>">
                    
                    <button type="submit" name="save_freebie" class="btn btn-primary save-btn">
                        💾 Einstellungen speichern
                    </button>
                </div>
            </form>
        </div>
        
        <!-- TAB 2: VIDEOKURS -->
        <?php if ($editMode): ?>
        <div class="tab-content <?php echo $active_tab === 'course' ? 'active' : ''; ?>" id="tab-course">
            <div class="panel">
                <div class="course-header">
                    <div>
                        <h2>Videokurs Management</h2>
                        <p style="color: #666; font-size: 14px;">Erstelle Module und Lektionen für dein Freebie</p>
                    </div>
                    <?php if (!$course): ?>
                    <button type="button" class="btn btn-primary" onclick="createCourse()">
                        + Kurs erstellen
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-primary" onclick="openModuleModal()">
                        + Modul hinzufügen
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if (!$course): ?>
                    <div class="info-box">
                        <div class="info-box-title">🎓 Videokurs erstellen</div>
                        <div class="info-box-text">
                            Erstelle einen Videokurs für dein Freebie. Du kannst Module und Lektionen mit Videos und PDFs hinzufügen.
                            Leads können den Kurs ohne Login anschauen und ihr Fortschritt wird automatisch getrackt.
                        </div>
                    </div>
                <?php else: ?>
                    <div id="modulesList">
                        <?php foreach ($modules as $module): ?>
                        <div class="module-card" data-module-id="<?php echo $module['id']; ?>">
                            <div class="module-header">
                                <div>
                                    <div class="module-title"><?php echo htmlspecialchars($module['title']); ?></div>
                                    <?php if ($module['description']): ?>
                                    <p style="color: #666; font-size: 14px; margin-top: 4px;">
                                        <?php echo htmlspecialchars($module['description']); ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <div class="module-actions">
                                    <button type="button" class="btn btn-secondary" 
                                            onclick="openLessonModal(<?php echo $module['id']; ?>)">
                                        + Lektion
                                    </button>
                                    <button type="button" class="btn btn-danger" 
                                            onclick="deleteModule(<?php echo $module['id']; ?>)">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            
                            <div class="lessons-list">
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                <div class="lesson-item" data-lesson-id="<?php echo $lesson['id']; ?>">
                                    <div class="lesson-info">
                                        <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                        <div class="lesson-meta">
                                            <?php if ($lesson['video_url']): ?>📹 Video<?php endif; ?>
                                            <?php if ($lesson['pdf_url']): ?>📄 PDF<?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="lesson-actions">
                                        <button type="button" class="btn btn-danger" 
                                                onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                            🗑️
                                        </button>
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
                <input type="text" id="moduleTitle" class="form-input" required>
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
                <input type="text" id="lessonTitle" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea id="lessonDescription" class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Video URL (YouTube/Vimeo)</label>
                <input type="url" id="lessonVideoUrl" class="form-input" 
                       placeholder="https://www.youtube.com/watch?v=...">
            </div>
            <div class="form-group">
                <label class="form-label">PDF URL (optional)</label>
                <input type="url" id="lessonPdfUrl" class="form-input" 
                       placeholder="https://...">
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
        
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            document.querySelector(`[onclick="switchTab('${tab}')"]`).classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
        }
        
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
            
            const result = await apiCall('create_course', {
                freebie_id: freebieId,
                title: title,
                description: ''
            });
            
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
        }
        
        function openModuleModal() {
            document.getElementById('moduleModal').classList.add('active');
        }
        
        function closeModuleModal() {
            document.getElementById('moduleModal').classList.remove('active');
            document.getElementById('moduleTitle').value = '';
            document.getElementById('moduleDescription').value = '';
        }
        
        async function saveModule() {
            const title = document.getElementById('moduleTitle').value;
            if (!title) {
                alert('Bitte Titel eingeben');
                return;
            }
            
            const result = await apiCall('create_module', {
                course_id: courseId,
                title: title,
                description: document.getElementById('moduleDescription').value
            });
            
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
        }
        
        async function deleteModule(moduleId) {
            if (!confirm('Modul wirklich löschen? Alle Lektionen werden ebenfalls gelöscht.')) return;
            
            const result = await apiCall('delete_module', { module_id: moduleId });
            
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
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
            if (!title) {
                alert('Bitte Titel eingeben');
                return;
            }
            
            const result = await apiCall('create_lesson', {
                module_id: document.getElementById('lessonModuleId').value,
                title: title,
                description: document.getElementById('lessonDescription').value,
                video_url: document.getElementById('lessonVideoUrl').value,
                pdf_url: document.getElementById('lessonPdfUrl').value
            });
            
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
        }
        
        async function deleteLesson(lessonId) {
            if (!confirm('Lektion wirklich löschen?')) return;
            
            const result = await apiCall('delete_lesson', { lesson_id: lessonId });
            
            if (result.success) {
                location.reload();
            } else {
                alert('Fehler: ' + result.error);
            }
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
