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
            // Update existing customer_freebies - INKLUSIVE Font-Felder!
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
                $customer_freebie['id']
            ]);
            $customer_freebie_id = $customer_freebie['id'];
            
            $success_message = "✅ Freebie erfolgreich aktualisiert!";
        } else {
            // Create new in customer_freebies - MIT Font-Feldern
            $stmt = $pdo->prepare("
                INSERT INTO customer_freebies (
                    customer_id, template_id, headline, subheadline, preheadline,
                    bullet_points, cta_text, layout, background_color, primary_color,
                    raw_code, unique_id, url_slug, mockup_image_url,
                    preheadline_font, preheadline_size,
                    headline_font, headline_size,
                    subheadline_font, subheadline_size,
                    bulletpoints_font, bulletpoints_size,
                    freebie_type, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'template', NOW())
            ");
            $stmt->execute([
                $customer_id, $template_id, $headline, $subheadline, $preheadline,
                $bullet_points, $cta_text, $layout, $background_color, $primary_color,
                $raw_code, $unique_id, $url_slug, $mockup_image_url,
                $preheadline_font, $preheadline_size,
                $headline_font, $headline_size,
                $subheadline_font, $subheadline_size,
                $bulletpoints_font, $bulletpoints_size
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

// Rest of the PHP code stays the same...
$form_data = [
    'headline' => $customer_freebie['headline'] ?? $template['headline'] ?? 'Sichere dir jetzt deinen kostenlosen Kurs',
    'subheadline' => $customer_freebie['subheadline'] ?? $template['subheadline'] ?? '',
    'preheadline' => $customer_freebie['preheadline'] ?? $template['preheadline'] ?? '',
    'bullet_points' => $customer_freebie['bullet_points'] ?? $template['bullet_points'] ?? "✓ Sofortiger Zugang\n✓ Professionelle Inhalte\n✓ Schritt für Schritt Anleitung",
    'cta_text' => $customer_freebie['cta_text'] ?? $template['cta_text'] ?? 'JETZT KOSTENLOS SICHERN',
    'layout' => $customer_freebie['layout'] ?? $template['layout'] ?? 'hybrid',
    'background_color' => $customer_freebie['background_color'] ?? $template['background_color'] ?? '#FFFFFF',
    'primary_color' => $customer_freebie['primary_color'] ?? $template['primary_color'] ?? '#8B5CF6',
    'raw_code' => $customer_freebie['raw_code'] ?? $template['raw_code'] ?? '',
    'mockup_image_url' => $customer_freebie['mockup_image_url'] ?? $template['mockup_image_url'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebie Editor - <?php echo htmlspecialchars($template['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* All the CSS stays exactly the same - no changes needed */
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
        .form-textarea {
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
        .form-textarea:focus {
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
        
        .preview-preheadline {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .preview-headline {
            font-size: 22px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 10px;
        }
        
        .preview-subheadline {
            font-size: 13px;
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
            font-size: 14px;
            flex-shrink: 0;
        }
        
        .preview-bullet-text {
            font-size: 12px;
            color: #374151;
            line-height: 1.4;
        }
        
        .preview-form {
            background: rgba(0, 0, 0, 0.02);
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
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
            <p>Template: <?php echo htmlspecialchars($template['name']); ?></p>
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
                                   placeholder="NUR FÜR KURZE ZEIT"
                                   oninput="updatePreview()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hauptüberschrift *</label>
                            <input type="text" name="headline" class="form-input" required
                                   value="<?php echo htmlspecialchars($form_data['headline']); ?>"
                                   placeholder="Sichere dir jetzt deinen kostenlosen Kurs"
                                   oninput="updatePreview()">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Unterüberschrift (optional)</label>
                            <input type="text" name="subheadline" class="form-input"
                                   value="<?php echo htmlspecialchars($form_data['subheadline']); ?>"
                                   placeholder="Starte noch heute und lerne die besten Strategien"
                                   oninput="updatePreview()">
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
                        <div class="preview-box">
                            <div class="preview-content" id="previewContent">
                                <!-- Wird durch JavaScript gefüllt -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        const mockupUrl = <?php echo json_encode($form_data['mockup_image_url']); ?>;
        
        function updateLayoutSelection(radio) {
            document.querySelectorAll('.layout-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            radio.closest('.layout-option').classList.add('selected');
        }
        
        function updatePreview() {
            const preheadline = document.querySelector('input[name="preheadline"]').value;
            const headline = document.querySelector('input[name="headline"]').value;
            const subheadline = document.querySelector('input[name="subheadline"]').value;
            const bulletPoints = document.querySelector('textarea[name="bullet_points"]').value;
            const ctaText = document.querySelector('input[name="cta_text"]').value;
            const layout = document.querySelector('input[name="layout"]:checked').value;
            const primaryColor = document.getElementById('primary_color').value;
            const backgroundColor = document.getElementById('background_color').value;
            
            const previewContent = document.getElementById('previewContent');
            previewContent.style.background = backgroundColor;
            
            const textAlign = (layout === 'centered') ? 'center' : 'center';
            
            let bulletHTML = '';
            if (bulletPoints.trim()) {
                const bullets = bulletPoints.split('\n').filter(b => b.trim());
                bulletHTML = bullets.map(bullet => {
                    const cleanBullet = bullet.replace(/^[✓✔︎•-]\s*/, '');
                    return `
                        <div class="preview-bullet">
                            <span class="preview-bullet-icon" style="color: ${primaryColor};">✓</span>
                            <span class="preview-bullet-text">${escapeHtml(cleanBullet)}</span>
                        </div>
                    `;
                }).join('');
                bulletHTML = `<div class="preview-bullets">${bulletHTML}</div>`;
            }
            
            let mockupHTML = '';
            if (mockupUrl) {
                mockupHTML = `
                    <div class="preview-mockup">
                        <img src="${escapeHtml(mockupUrl)}" alt="Mockup" style="max-width: 180px;">
                    </div>
                `;
            }
            
            const preheadlineHTML = preheadline ? `
                <div class="preview-preheadline" style="color: ${primaryColor}; text-align: ${textAlign};">
                    ${escapeHtml(preheadline)}
                </div>
            ` : '';
            
            const headlineHTML = `
                <div class="preview-headline" style="color: ${primaryColor}; text-align: ${textAlign};">
                    ${escapeHtml(headline || 'Deine Hauptüberschrift')}
                </div>
            `;
            
            const subheadlineHTML = subheadline ? `
                <div class="preview-subheadline" style="text-align: ${textAlign};">${escapeHtml(subheadline)}</div>
            ` : '';
            
            const ctaHTML = `
                <div class="preview-cta" style="text-align: ${textAlign};">
                    <button class="preview-button" style="background: ${primaryColor}; color: white;">
                        ${escapeHtml(ctaText || 'BUTTON TEXT')}
                    </button>
                </div>
            `;
            
            let layoutHTML = '';
            
            if (layout === 'centered') {
                layoutHTML = `
                    <div style="max-width: 800px; margin: 0 auto;">
                        ${preheadlineHTML}
                        ${headlineHTML}
                        ${subheadlineHTML}
                        ${mockupHTML}
                        ${bulletHTML}
                        ${ctaHTML}
                    </div>
                `;
            } else if (layout === 'hybrid') {
                layoutHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                        <div>
                            ${mockupHTML}
                        </div>
                        <div>
                            ${preheadlineHTML}
                            ${headlineHTML}
                            ${subheadlineHTML}
                            ${bulletHTML}
                            ${ctaHTML}
                        </div>
                    </div>
                `;
            } else { // sidebar
                layoutHTML = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; align-items: center;">
                        <div>
                            ${preheadlineHTML}
                            ${headlineHTML}
                            ${subheadlineHTML}
                            ${bulletHTML}
                            ${ctaHTML}
                        </div>
                        <div>
                            ${mockupHTML}
                        </div>
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
        
        // Initial preview
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
        });
    </script>
</body>
</html>
