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
$template_id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;

if (!$template_id) {
    header('Location: dashboard.php?page=freebies');
    exit;
}

// Template aus der freebies Tabelle laden
try {
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        die('Template nicht gefunden');
    }
    
    // Prüfen ob der Kunde bereits eine Version dieses Templates hat
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE customer_id = ? AND template_id = ?
    ");
    $stmt->execute([$customer_id, $template_id]);
    $customer_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Zugewiesenen Kurs laden (falls vorhanden)
    $assigned_course = null;
    if (!empty($template['course_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$template['course_id']]);
        $assigned_course = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// Formular speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_freebie'])) {
    $headline = trim($_POST['headline'] ?? '');
    $subheadline = trim($_POST['subheadline'] ?? '');
    $preheadline = trim($_POST['preheadline'] ?? '');
    $bullet_points = trim($_POST['bullet_points'] ?? '');
    $cta_text = trim($_POST['cta_text'] ?? '');
    $layout = $_POST['layout'] ?? 'hybrid';
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $primary_color = $_POST['primary_color'] ?? '#8B5CF6';
    $raw_code = trim($_POST['raw_code'] ?? '');
    
    // 🆕 POPUP-FELDER
    $optin_display_mode = $_POST['optin_display_mode'] ?? 'direct';
    $popup_message = trim($_POST['popup_message'] ?? 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!');
    $cta_animation = $_POST['cta_animation'] ?? 'none';
    
    // Unique ID für die Freebie-Seite
    if (!$customer_freebie) {
        $unique_id = bin2hex(random_bytes(16));
        $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $headline)) . '-' . substr($unique_id, 0, 8);
    } else {
        $unique_id = $customer_freebie['unique_id'];
        $url_slug = $customer_freebie['url_slug'];
    }
    
    // Mockup-Image-URL aus Template holen (falls nicht schon vorhanden)
    $mockup_image_url = $customer_freebie['mockup_image_url'] ?? $template['mockup_image_url'] ?? '';
    
    // Font-Einstellungen vom Template übernehmen (falls noch nicht vorhanden)
    $preheadline_font = $customer_freebie['preheadline_font'] ?? $template['preheadline_font'] ?? 'Poppins';
    $preheadline_size = $customer_freebie['preheadline_size'] ?? $template['preheadline_size'] ?? 14;
    $headline_font = $customer_freebie['headline_font'] ?? $template['headline_font'] ?? 'Poppins';
    $headline_size = $customer_freebie['headline_size'] ?? $template['headline_size'] ?? 48;
    $subheadline_font = $customer_freebie['subheadline_font'] ?? $template['subheadline_font'] ?? 'Poppins';
    $subheadline_size = $customer_freebie['subheadline_size'] ?? $template['subheadline_size'] ?? 20;
    $bulletpoints_font = $customer_freebie['bulletpoints_font'] ?? $template['bulletpoints_font'] ?? 'Poppins';
    $bulletpoints_size = $customer_freebie['bulletpoints_size'] ?? $template['bulletpoints_size'] ?? 16;
    
    try {
        if ($customer_freebie) {
            // Update existing customer_freebies
            $stmt = $pdo->prepare("
                UPDATE customer_freebies SET
                    headline = ?, subheadline = ?, preheadline = ?,
                    bullet_points = ?, cta_text = ?, layout = ?,
                    background_color = ?, primary_color = ?, raw_code = ?,
                    mockup_image_url = ?,
                    preheadline_font = ?, preheadline_size = ?,
                    headline_font = ?, headline_size = ?,
                    subheadline_font = ?, subheadline_size = ?,
                    bulletpoints_font = ?, bulletpoints_size = ?,
                    optin_display_mode = ?, popup_message = ?, cta_animation = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $headline, $subheadline, $preheadline,
                $bullet_points, $cta_text, $layout,
                $background_color, $primary_color, $raw_code,
                $mockup_image_url,
                $preheadline_font, $preheadline_size,
                $headline_font, $headline_size,
                $subheadline_font, $subheadline_size,
                $bulletpoints_font, $bulletpoints_size,
                $optin_display_mode, $popup_message, $cta_animation,
                $customer_freebie['id']
            ]);
            $customer_freebie_id = $customer_freebie['id'];
            
            $success_message = "✅ Freebie erfolgreich aktualisiert!";
        } else {
            // Create new in customer_freebies
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebies (
                    customer_id, template_id, headline, subheadline, preheadline,
                    bullet_points, cta_text, layout, background_color, primary_color,
                    raw_code, unique_id, url_slug, mockup_image_url,
                    preheadline_font, preheadline_size,
                    headline_font, headline_size,
                    subheadline_font, subheadline_size,
                    bulletpoints_font, bulletpoints_size,
                    optin_display_mode, popup_message, cta_animation,
                    freebie_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'template', NOW())
            ");
            $stmt->execute([
                $customer_id, $template_id, $headline, $subheadline, $preheadline,
                $bullet_points, $cta_text, $layout, $background_color, $primary_color,
                $raw_code, $unique_id, $url_slug, $mockup_image_url,
                $preheadline_font, $preheadline_size,
                $headline_font, $headline_size,
                $subheadline_font, $subheadline_size,
                $bulletpoints_font, $bulletpoints_size,
                $optin_display_mode, $popup_message, $cta_animation
            ]);
            $customer_freebie_id = $pdo->lastInsertId();
            
            $success_message = "✅ Freebie erfolgreich erstellt!";
        }
        
        // Reload customer freebie
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
        $stmt->execute([$customer_freebie_id]);
        $customer_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        $error_message = "❌ Fehler: " . $e->getMessage();
    }
}

// Form data zusammenstellen - FIX: Wenn customer_freebie existiert, dessen Werte verwenden (auch leere Strings!)
// Nur wenn customer_freebie NICHT existiert oder Feld nicht gesetzt ist, auf Template zurückgreifen
$form_data = [
    'headline' => ($customer_freebie && array_key_exists('headline', $customer_freebie)) ? $customer_freebie['headline'] : ($template['headline'] ?? 'Sichere dir jetzt deinen kostenlosen Kurs'),
    'subheadline' => ($customer_freebie && array_key_exists('subheadline', $customer_freebie)) ? $customer_freebie['subheadline'] : ($template['subheadline'] ?? ''),
    'preheadline' => ($customer_freebie && array_key_exists('preheadline', $customer_freebie)) ? $customer_freebie['preheadline'] : ($template['preheadline'] ?? ''),
    'bullet_points' => ($customer_freebie && array_key_exists('bullet_points', $customer_freebie)) ? $customer_freebie['bullet_points'] : ($template['bullet_points'] ?? "✓ Sofortiger Zugang\n✓ Professionelle Inhalte\n✓ Schritt für Schritt Anleitung"),
    'cta_text' => ($customer_freebie && array_key_exists('cta_text', $customer_freebie)) ? $customer_freebie['cta_text'] : ($template['cta_text'] ?? 'JETZT KOSTENLOS SICHERN'),
    'layout' => ($customer_freebie && array_key_exists('layout', $customer_freebie)) ? $customer_freebie['layout'] : ($template['layout'] ?? 'hybrid'),
    'background_color' => ($customer_freebie && array_key_exists('background_color', $customer_freebie)) ? $customer_freebie['background_color'] : ($template['background_color'] ?? '#FFFFFF'),
    'primary_color' => ($customer_freebie && array_key_exists('primary_color', $customer_freebie)) ? $customer_freebie['primary_color'] : ($template['primary_color'] ?? '#8B5CF6'),
    'raw_code' => ($customer_freebie && array_key_exists('raw_code', $customer_freebie)) ? $customer_freebie['raw_code'] : ($template['raw_code'] ?? ($template['custom_raw_code'] ?? '')),
    'mockup_image_url' => ($customer_freebie && array_key_exists('mockup_image_url', $customer_freebie)) ? $customer_freebie['mockup_image_url'] : ($template['mockup_image_url'] ?? ''),
    // 🆕 POPUP-FELDER
    'optin_display_mode' => ($customer_freebie && array_key_exists('optin_display_mode', $customer_freebie)) ? $customer_freebie['optin_display_mode'] : 'direct',
    'popup_message' => ($customer_freebie && array_key_exists('popup_message', $customer_freebie)) ? $customer_freebie['popup_message'] : 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
    'cta_animation' => ($customer_freebie && array_key_exists('cta_animation', $customer_freebie)) ? $customer_freebie['cta_animation'] : 'none'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie Editor - <?php echo htmlspecialchars($template['name'] ?? 'Template'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        .course-info {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .course-icon {
            width: 48px;
            height: 48px;
            background: rgba(16, 185, 129, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .course-details h4 {
            font-size: 14px;
            font-weight: 600;
            color: #047857;
            margin-bottom: 4px;
        }
        
        .course-details p {
            font-size: 12px;
            color: #059669;
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
            <h1>🎁 Freebie bearbeiten</h1>
            <p>Template: <?php echo htmlspecialchars($template['name'] ?? 'Unbenannt'); ?></p>
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
                    
                    <!-- Zugewiesener Kurs (nicht änderbar) -->
                    <?php if ($assigned_course): ?>
                        <div class="form-section">
                            <div class="section-title">📚 Zugewiesener Kurs (fest)</div>
                            <div class="course-info">
                                <div class="course-icon">🎓</div>
                                <div class="course-details">
                                    <h4><?php echo htmlspecialchars($assigned_course['title']); ?></h4>
                                    <p>Dieser Kurs wird automatisch mit deinem Freebie verknüpft</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Texte -->
                    <div class="form-section">
                        <div class="section-title">✍️ Texte</div>
                        
                        <div class="form-group">
                            <label class="form-label">Vorüberschrift (optional)</label>
                            <input type="text" name="preheadline" class="form-input" 
                                   value="<?php echo htmlspecialchars($form_data['preheadline']); ?>"
                                   placeholder="NUR FÜR KURZE ZEIT">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hauptüberschrift *</label>
                            <input type="text" name="headline" class="form-input" required
                                   value="<?php echo htmlspecialchars($form_data['headline']); ?>"
                                   placeholder="Sichere dir jetzt deinen kostenlosen Kurs">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Unterüberschrift (optional)</label>
                            <input type="text" name="subheadline" class="form-input"
                                   value="<?php echo htmlspecialchars($form_data['subheadline']); ?>"
                                   placeholder="Starte noch heute und lerne die besten Strategien">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Bullet Points (eine pro Zeile)</label>
                            <textarea name="bullet_points" class="form-textarea" style="font-family: inherit;"><?php echo htmlspecialchars($form_data['bullet_points']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Button Text *</label>
                            <input type="text" name="cta_text" class="form-input" required
                                   value="<?php echo htmlspecialchars($form_data['cta_text']); ?>"
                                   placeholder="JETZT KOSTENLOS SICHERN">
                        </div>
                    </div>
                    
                    <!-- Layout -->
                    <div class="form-section">
                        <div class="section-title">🎨 Layout</div>
                        <div class="layout-options">
                            <label class="layout-option <?php echo $form_data['layout'] === 'hybrid' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="hybrid" 
                                       <?php echo $form_data['layout'] === 'hybrid' ? 'checked' : ''; ?>
                                       onchange="updateLayoutSelection(this)">
                                <div class="layout-content">
                                    <div class="layout-icon">⚡</div>
                                    <div class="layout-name">Hybrid</div>
                                </div>
                                <div class="layout-check">✓</div>
                            </label>
                            
                            <label class="layout-option <?php echo $form_data['layout'] === 'centered' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="centered"
                                       <?php echo $form_data['layout'] === 'centered' ? 'checked' : ''; ?>
                                       onchange="updateLayoutSelection(this)">
                                <div class="layout-content">
                                    <div class="layout-icon">🎯</div>
                                    <div class="layout-name">Zentriert</div>
                                </div>
                                <div class="layout-check">✓</div>
                            </label>
                            
                            <label class="layout-option <?php echo $form_data['layout'] === 'sidebar' ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="sidebar"
                                       <?php echo $form_data['layout'] === 'sidebar' ? 'checked' : ''; ?>
                                       onchange="updateLayoutSelection(this)">
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
                                <label class="form-label">Primärfarbe</label>
                                <div class="color-input-wrapper">
                                    <input type="color" id="primary_color_picker" 
                                           value="<?php echo htmlspecialchars($form_data['primary_color']); ?>"
                                           class="color-preview"
                                           onchange="document.getElementById('primary_color').value = this.value">
                                    <input type="text" name="primary_color" id="primary_color" 
                                           class="form-input color-input"
                                           value="<?php echo htmlspecialchars($form_data['primary_color']); ?>"
                                           oninput="document.getElementById('primary_color_picker').value = this.value">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Hintergrundfarbe</label>
                                <div class="color-input-wrapper">
                                    <input type="color" id="background_color_picker"
                                           value="<?php echo htmlspecialchars($form_data['background_color']); ?>"
                                           class="color-preview"
                                           onchange="document.getElementById('background_color').value = this.value">
                                    <input type="text" name="background_color" id="background_color"
                                           class="form-input color-input"
                                           value="<?php echo htmlspecialchars($form_data['background_color']); ?>"
                                           oninput="document.getElementById('background_color_picker').value = this.value">
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
                                       onchange="togglePopupOptions(this)">
                                <div class="toggle-content">
                                    <div class="toggle-icon">📄</div>
                                    <div class="toggle-name">Direkt anzeigen</div>
                                </div>
                            </label>
                            
                            <label class="toggle-option <?php echo $form_data['optin_display_mode'] === 'popup' ? 'selected' : ''; ?>">
                                <input type="radio" name="optin_display_mode" value="popup"
                                       <?php echo $form_data['optin_display_mode'] === 'popup' ? 'checked' : ''; ?>
                                       onchange="togglePopupOptions(this)">
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
                                       placeholder="Trage dich jetzt unverbindlich ein!">
                                <small style="color: #6b7280; font-size: 12px; display: block; margin-top: 4px;">
                                    Diese Nachricht wird im Popup über dem Formular angezeigt
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Button-Animation</label>
                                <select name="cta_animation" class="form-select">
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
                        
                        <?php if ($customer_freebie && !empty($customer_freebie['unique_id'])): ?>
                            <div class="preview-box" style="padding: 0; background: #f9fafb; overflow: hidden; min-height: 700px;">
                                <iframe 
                                    id="livePreview"
                                    src="https://app.mehr-infos-jetzt.de/freebie/index.php?id=<?php echo $customer_freebie['unique_id']; ?>&preview=1" 
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
        
        function updateLayoutSelection(radio) {
            document.querySelectorAll('.layout-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.layout-option').classList.add('selected');
        }
        
        // Nach erfolgreichem Speichern: iFrame neu laden
        <?php if (isset($success_message) && $customer_freebie): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const iframe = document.getElementById('livePreview');
            if (iframe) {
                // Cache-Bust mit Timestamp
                const currentSrc = iframe.src.split('&t=')[0];
                iframe.src = currentSrc + '&t=' + Date.now();
                
                // Smooth scroll zur Vorschau
                setTimeout(function() {
                    iframe.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>