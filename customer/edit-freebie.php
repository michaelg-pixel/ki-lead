<?php
/**
 * Freebie Editor - Settings/Einstellungen
 * Mit 3 verschiedenen Layouts: Hybrid, Zentriert, Sidebar
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

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
        die('Freebie nicht gefunden');
    }
}

// Kategorien laden
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM freebie_template_categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $warning_message = "⚠️ Kategorien nicht verfügbar.";
}

// Formular speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_freebie'])) {
    $data = [
        'headline' => trim($_POST['headline'] ?? ''),
        'subheadline' => trim($_POST['subheadline'] ?? ''),
        'preheadline' => trim($_POST['preheadline'] ?? ''),
        'bullet_points' => trim($_POST['bullet_points'] ?? ''),
        'bullet_icon_style' => $_POST['bullet_icon_style'] ?? 'standard',
        'cta_text' => trim($_POST['cta_text'] ?? ''),
        'layout' => $_POST['layout'] ?? 'hybrid',
        'background_color' => $_POST['background_color'] ?? '#FFFFFF',
        'primary_color' => $_POST['primary_color'] ?? '#8B5CF6',
        'mockup_image_url' => trim($_POST['mockup_image_url'] ?? ''),
        'video_url' => trim($_POST['video_url'] ?? ''),
        'video_format' => $_POST['video_format'] ?? 'widescreen',
        'optin_display_mode' => $_POST['optin_display_mode'] ?? 'direct',
        'popup_message' => trim($_POST['popup_message'] ?? 'Trage dich jetzt ein!'),
        'cta_animation' => $_POST['cta_animation'] ?? 'none',
        'font_heading' => $_POST['font_heading'] ?? 'Inter',
        'font_body' => $_POST['font_body'] ?? 'Inter',
        'font_size' => $_POST['font_size'] ?? 'medium',
        'category_id' => !empty($_POST['category_id']) ? intval($_POST['category_id']) : null
    ];
    
    // Code zusammenfügen
    $raw_code = trim($_POST['raw_code'] ?? '');
    $custom_code = trim($_POST['custom_code'] ?? '');
    $combined_code = $raw_code;
    if (!empty($custom_code)) {
        $combined_code .= "\n<!-- CUSTOM_TRACKING_CODE -->\n" . $custom_code;
    }
    $data['raw_code'] = $combined_code;
    
    try {
        if ($freebie) {
            // Update
            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = "$key = ?";
            }
            $sql = "UPDATE customer_freebies SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $values = array_values($data);
            $values[] = $freebie['id'];
            $stmt->execute($values);
            $success_message = "✅ Einstellungen gespeichert!";
            
            // Reload
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
            $stmt->execute([$freebie['id']]);
            $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // Create
            $unique_id = bin2hex(random_bytes(16));
            $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['headline'])) . '-' . substr($unique_id, 0, 8);
            
            $fields = array_keys($data);
            $fields[] = 'customer_id';
            $fields[] = 'unique_id';
            $fields[] = 'url_slug';
            $fields[] = 'freebie_type';
            $fields[] = 'created_at';
            
            $placeholders = str_repeat('?, ', count($data)) . '?, ?, ?, ?, NOW()';
            $sql = "INSERT INTO customer_freebies (" . implode(', ', $fields) . ") VALUES ($placeholders)";
            
            $stmt = $pdo->prepare($sql);
            $values = array_values($data);
            $values[] = $customer_id;
            $values[] = $unique_id;
            $values[] = $url_slug;
            $values[] = 'custom';
            $stmt->execute($values);
            
            $freebie_id = $pdo->lastInsertId();
            $success_message = "✅ Freebie erstellt!";
            
            header("Location: ?id={$freebie_id}");
            exit;
        }
        
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

// Form Defaults
$form = [
    'headline' => $freebie['headline'] ?? 'Wie du eigene KI Kurse in nur 7 Tagen verkaufst ohne diese selbst erstellen zu müssen?',
    'subheadline' => $freebie['subheadline'] ?? '',
    'preheadline' => $freebie['preheadline'] ?? 'NUR FÜR KURZE ZEIT - Kostenloser Download!',
    'bullet_points' => $freebie['bullet_points'] ?? "Erfahre in diesem Report wie einfach du dir ein KI-Kurs Imperium in nur 7 Tagen aufbaust!\nWarum du JETZT auf KI-Kurse setzen solltest, denn die Nachfrage nach Wissen über künstliche Intelligenz explodiert gerade!\nWarum Du dabei keinen einzigen Kurs selbst erstellen musst!\nWo du komplett fertige KI-Videokurse bekommst? Die du dann mit 100 Prozent Gewinn weiter verkaufen kannst.",
    'bullet_icon_style' => $freebie['bullet_icon_style'] ?? 'checkmark',
    'cta_text' => $freebie['cta_text'] ?? 'Jetzt für 0€ statt 27€ KOSTENLOS DOWNLOADEN',
    'layout' => $freebie['layout'] ?? 'hybrid',
    'background_color' => $freebie['background_color'] ?? '#F8F9FC',
    'primary_color' => $freebie['primary_color'] ?? '#5B8DEF',
    'raw_code' => $email_optin_code,
    'custom_code' => $custom_tracking_code,
    'mockup_image_url' => $freebie['mockup_image_url'] ?? 'https://i.imgur.com/example.png',
    'video_url' => $freebie['video_url'] ?? '',
    'video_format' => $freebie['video_format'] ?? 'widescreen',
    'optin_display_mode' => $freebie['optin_display_mode'] ?? 'direct',
    'popup_message' => $freebie['popup_message'] ?? 'Trage dich jetzt ein!',
    'cta_animation' => $freebie['cta_animation'] ?? 'none',
    'font_heading' => $freebie['font_heading'] ?? 'Inter',
    'font_body' => $freebie['font_body'] ?? 'Inter',
    'font_size' => $freebie['font_size'] ?? 'medium',
    'category_id' => $freebie['category_id'] ?? null
];

// Fonts
$google_fonts = [
    'Inter', 'Roboto', 'Open Sans', 'Montserrat', 'Poppins', 
    'Lato', 'Oswald', 'Raleway', 'Playfair Display', 'Merriweather'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Freebie bearbeiten' : 'Neues Freebie'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1800px; margin: 0 auto; }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #1a1a2e; font-size: 28px; margin-bottom: 8px; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        .nav-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .nav-tab {
            flex: 1;
            padding: 16px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nav-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .editor-grid {
            display: grid;
            grid-template-columns: 500px 1fr;
            gap: 24px;
        }
        
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .panel-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a1a2e;
        }
        
        .form-section { margin-bottom: 24px; }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a2e;
        }
        .form-group { margin-bottom: 16px; }
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
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .layout-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .layout-option {
            position: relative;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .layout-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        .layout-option:hover {
            border-color: #8B5CF6;
        }
        .layout-option input[type="radio"]:checked + .layout-content {
            border-color: #8B5CF6;
        }
        .layout-option input[type="radio"]:checked ~ .layout-label {
            color: #8B5CF6;
            font-weight: 700;
        }
        .layout-visual {
            width: 100%;
            height: 80px;
            background: #f3f4f6;
            border-radius: 6px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px;
        }
        .layout-visual-box {
            background: #d1d5db;
            border-radius: 4px;
        }
        .layout-label {
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: #666;
        }
        
        /* Hybrid Layout Visual */
        .layout-hybrid .layout-visual {
            flex-direction: column;
        }
        .layout-hybrid .top-section {
            width: 100%;
            height: 20px;
            background: #d1d5db;
            border-radius: 3px;
        }
        .layout-hybrid .grid-section {
            display: flex;
            gap: 6px;
            width: 100%;
        }
        .layout-hybrid .grid-left,
        .layout-hybrid .grid-right {
            flex: 1;
            height: 40px;
            background: #d1d5db;
            border-radius: 3px;
        }
        
        /* Centered Layout Visual */
        .layout-centered .layout-visual {
            flex-direction: column;
            gap: 4px;
        }
        .layout-centered .item {
            width: 100%;
            height: 15px;
            background: #d1d5db;
            border-radius: 3px;
        }
        
        /* Sidebar Layout Visual */
        .layout-sidebar .layout-visual {
            flex-direction: column;
        }
        .layout-sidebar .top {
            width: 100%;
            height: 20px;
            background: #d1d5db;
            border-radius: 3px;
            margin-bottom: 4px;
        }
        .layout-sidebar .bottom {
            display: flex;
            gap: 6px;
            width: 100%;
        }
        .layout-sidebar .left {
            flex: 1;
            height: 40px;
            background: #d1d5db;
            border-radius: 3px;
        }
        .layout-sidebar .right {
            flex: 1;
            height: 40px;
            background: #d1d5db;
            border-radius: 3px;
        }
        
        .btn-save {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); }
        
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
        
        /* Preview Styles */
        .preview-container {
            background: #fff;
            border-radius: 12px;
            padding: 40px 20px;
            min-height: 600px;
        }
        
        .preview-content {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Layout: Hybrid */
        .layout-preview-hybrid .headlines {
            text-align: center;
            margin-bottom: 40px;
        }
        .layout-preview-hybrid .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        /* Layout: Centered */
        .layout-preview-centered {
            text-align: center;
        }
        .layout-preview-centered .headlines {
            margin-bottom: 40px;
        }
        .layout-preview-centered .mockup {
            margin: 40px auto;
        }
        .layout-preview-centered .bullets {
            margin: 40px auto;
            max-width: 600px;
        }
        
        /* Layout: Sidebar */
        .layout-preview-sidebar .headlines {
            text-align: center;
            margin-bottom: 40px;
        }
        .layout-preview-sidebar .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        
        .preview-preheadline {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        
        .preview-headline {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
            color: #1a1a2e;
        }
        
        .preview-subheadline {
            font-size: 18px;
            color: #666;
            line-height: 1.5;
        }
        
        .preview-mockup {
            width: 100%;
            max-width: 400px;
            height: auto;
            border-radius: 8px;
        }
        
        .preview-bullets {
            list-style: none;
        }
        
        .preview-bullet {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 16px;
            line-height: 1.6;
            color: #374151;
        }
        
        .preview-bullet-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            font-weight: 700;
            margin-top: 2px;
        }
        
        .preview-cta {
            display: inline-block;
            padding: 18px 40px;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            text-align: center;
            margin-top: 24px;
            transition: transform 0.2s;
        }
        .preview-cta:hover {
            transform: translateY(-2px);
        }
        
        .optin-placeholder {
            padding: 40px 20px;
            background: #f3f4f6;
            border-radius: 8px;
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 24px;
        }
        
        @media (max-width: 1200px) {
            .editor-grid { 
                grid-template-columns: 1fr; 
            }
            .layout-preview-hybrid .grid,
            .layout-preview-sidebar .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="/customer/dashboard.php?page=freebies" class="back-link">← Zurück</a>
        <h1><?php echo $editMode ? '✏️ Freebie bearbeiten' : '✨ Neues Freebie'; ?></h1>
        <p>Erstelle deine perfekte Optin-Seite mit 3 verschiedenen Layouts</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($warning_message)): ?>
        <div class="alert alert-error"><?php echo $warning_message; ?></div>
    <?php endif; ?>
    
    <?php if ($editMode): ?>
    <div class="nav-tabs">
        <a href="?id=<?php echo $freebie['id']; ?>" class="nav-tab active">⚙️ Einstellungen</a>
        <a href="/customer/edit-course.php?id=<?php echo $freebie['id']; ?>" class="nav-tab">🎓 Videokurs</a>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="freebieForm">
        <div class="editor-grid">
            <div class="panel">
                <h2 class="panel-title">⚙️ Einstellungen</h2>
                
                <div class="form-section">
                    <div class="section-title">🎨 Layout auswählen</div>
                    <div class="layout-selector">
                        <label class="layout-option layout-hybrid">
                            <input type="radio" name="layout" value="hybrid" 
                                   <?php echo $form['layout'] === 'hybrid' ? 'checked' : ''; ?>
                                   onchange="updatePreview()">
                            <div class="layout-content">
                                <div class="layout-visual">
                                    <div class="top-section"></div>
                                    <div class="grid-section">
                                        <div class="grid-left"></div>
                                        <div class="grid-right"></div>
                                    </div>
                                </div>
                                <div class="layout-label">Hybrid</div>
                            </div>
                        </label>
                        
                        <label class="layout-option layout-centered">
                            <input type="radio" name="layout" value="centered"
                                   <?php echo $form['layout'] === 'centered' ? 'checked' : ''; ?>
                                   onchange="updatePreview()">
                            <div class="layout-content">
                                <div class="layout-visual">
                                    <div class="item"></div>
                                    <div class="item"></div>
                                    <div class="item"></div>
                                    <div class="item"></div>
                                </div>
                                <div class="layout-label">Zentriert</div>
                            </div>
                        </label>
                        
                        <label class="layout-option layout-sidebar">
                            <input type="radio" name="layout" value="sidebar"
                                   <?php echo $form['layout'] === 'sidebar' ? 'checked' : ''; ?>
                                   onchange="updatePreview()">
                            <div class="layout-content">
                                <div class="layout-visual">
                                    <div class="top"></div>
                                    <div class="bottom">
                                        <div class="left"></div>
                                        <div class="right"></div>
                                    </div>
                                </div>
                                <div class="layout-label">Sidebar</div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <?php if (!empty($categories)): ?>
                <div class="form-section">
                    <div class="section-title">🏷️ Marktplatz-Kategorie</div>
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">Keine</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo $form['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-section">
                    <div class="section-title">📝 Texte</div>
                    <div class="form-group">
                        <label class="form-label">Pre-Headline (Optional)</label>
                        <input type="text" name="preheadline" class="form-input" 
                               value="<?php echo htmlspecialchars($form['preheadline']); ?>"
                               onkeyup="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Haupt-Überschrift *</label>
                        <input type="text" name="headline" class="form-input" required
                               value="<?php echo htmlspecialchars($form['headline']); ?>"
                               onkeyup="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unter-Überschrift (Optional)</label>
                        <textarea name="subheadline" class="form-textarea"
                                  onkeyup="updatePreview()"><?php echo htmlspecialchars($form['subheadline']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bullet Points (eine pro Zeile)</label>
                        <textarea name="bullet_points" class="form-textarea"
                                  onkeyup="updatePreview()"><?php echo htmlspecialchars($form['bullet_points']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bullet Icon Style</label>
                        <select name="bullet_icon_style" class="form-select" onchange="updatePreview()">
                            <option value="checkmark" <?php echo $form['bullet_icon_style'] === 'checkmark' ? 'selected' : ''; ?>>✓ Checkmark</option>
                            <option value="standard" <?php echo $form['bullet_icon_style'] === 'standard' ? 'selected' : ''; ?>>→ Arrow</option>
                            <option value="star" <?php echo $form['bullet_icon_style'] === 'star' ? 'selected' : ''; ?>>★ Star</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA Button Text</label>
                        <input type="text" name="cta_text" class="form-input"
                               value="<?php echo htmlspecialchars($form['cta_text']); ?>"
                               onkeyup="updatePreview()">
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">🎨 Design</div>
                    <div class="form-group">
                        <label class="form-label">Hintergrundfarbe</label>
                        <input type="color" name="background_color" class="form-input"
                               value="<?php echo htmlspecialchars($form['background_color']); ?>"
                               onchange="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primärfarbe (CTA & Icons)</label>
                        <input type="color" name="primary_color" class="form-input"
                               value="<?php echo htmlspecialchars($form['primary_color']); ?>"
                               onchange="updatePreview()">
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">🎥 Video (Optional)</div>
                    <div class="form-group">
                        <label class="form-label">Video URL</label>
                        <input type="url" name="video_url" class="form-input"
                               value="<?php echo htmlspecialchars($form['video_url']); ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">🖼️ Mockup-Bild</div>
                    <div class="form-group">
                        <label class="form-label">Bild URL</label>
                        <input type="url" name="mockup_image_url" class="form-input"
                               value="<?php echo htmlspecialchars($form['mockup_image_url']); ?>"
                               placeholder="https://i.imgur.com/example.png"
                               onchange="updatePreview()">
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="section-title">📧 E-Mail Optin</div>
                    <div class="form-group">
                        <label class="form-label">Anzeige-Modus</label>
                        <select name="optin_display_mode" class="form-select" onchange="updatePreview()">
                            <option value="direct" <?php echo $form['optin_display_mode'] === 'direct' ? 'selected' : ''; ?>>
                                Direkt anzeigen
                            </option>
                            <option value="popup" <?php echo $form['optin_display_mode'] === 'popup' ? 'selected' : ''; ?>>
                                Als Popup (nach Button-Klick)
                            </option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">HTML/JavaScript Code</label>
                        <textarea name="raw_code" class="form-textarea" rows="6"><?php echo htmlspecialchars($form['raw_code']); ?></textarea>
                    </div>
                    <div class="form-group" id="popupMessageGroup" style="display: none;">
                        <label class="form-label">Popup Headline</label>
                        <input type="text" name="popup_message" class="form-input"
                               value="<?php echo htmlspecialchars($form['popup_message']); ?>">
                    </div>
                </div>
                
                <button type="submit" name="save_freebie" class="btn-save">
                    💾 Speichern
                </button>
            </div>
            
            <div class="panel">
                <h2 class="panel-title">👁️ Live Vorschau</h2>
                <div class="preview-container" id="previewContainer">
                    <div class="preview-content" id="previewContent">
                        <!-- Preview wird hier geladen -->
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Initial preview
document.addEventListener('DOMContentLoaded', function() {
    updatePreview();
    togglePopupFields();
    
    // Popup toggle
    document.querySelector('select[name="optin_display_mode"]').addEventListener('change', togglePopupFields);
});

function togglePopupFields() {
    const mode = document.querySelector('select[name="optin_display_mode"]').value;
    document.getElementById('popupMessageGroup').style.display = mode === 'popup' ? 'block' : 'none';
}

function updatePreview() {
    const form = document.getElementById('freebieForm');
    const layout = form.querySelector('input[name="layout"]:checked').value;
    const preheadline = form.querySelector('input[name="preheadline"]').value;
    const headline = form.querySelector('input[name="headline"]').value;
    const subheadline = form.querySelector('textarea[name="subheadline"]').value;
    const bulletPoints = form.querySelector('textarea[name="bullet_points"]').value;
    const bulletIconStyle = form.querySelector('select[name="bullet_icon_style"]').value;
    const ctaText = form.querySelector('input[name="cta_text"]').value;
    const backgroundColor = form.querySelector('input[name="background_color"]').value;
    const primaryColor = form.querySelector('input[name="primary_color"]').value;
    const mockupUrl = form.querySelector('input[name="mockup_image_url"]').value;
    const optinMode = form.querySelector('select[name="optin_display_mode"]').value;
    
    // Parse bullet points
    const bullets = bulletPoints.split('\n').filter(b => b.trim());
    
    // Icon HTML
    const iconMap = {
        'checkmark': '✓',
        'standard': '→',
        'star': '★'
    };
    const icon = iconMap[bulletIconStyle] || '✓';
    
    // Build preview
    let html = '';
    
    if (layout === 'hybrid') {
        html = `
            <div class="layout-preview-hybrid">
                <div class="headlines">
                    ${preheadline ? `<div class="preview-preheadline" style="color: ${primaryColor}">${preheadline}</div>` : ''}
                    <h1 class="preview-headline">${headline}</h1>
                    ${subheadline ? `<p class="preview-subheadline">${subheadline}</p>` : ''}
                </div>
                <div class="grid">
                    <div>
                        ${mockupUrl ? `<img src="${mockupUrl}" class="preview-mockup" alt="Mockup">` : '<div style="background: #f3f4f6; border-radius: 8px; height: 300px; display: flex; align-items: center; justify-content: center; color: #999;">Mockup Bild</div>'}
                    </div>
                    <div>
                        <ul class="preview-bullets">
                            ${bullets.map(b => `
                                <li class="preview-bullet">
                                    <span class="preview-bullet-icon" style="background: ${primaryColor}">${icon}</span>
                                    <span>${b}</span>
                                </li>
                            `).join('')}
                        </ul>
                        ${optinMode === 'direct' 
                            ? '<div class="optin-placeholder">📧 E-Mail Optin wird hier angezeigt</div>'
                            : `<a href="#" class="preview-cta" style="background: ${primaryColor}">${ctaText}</a>`
                        }
                    </div>
                </div>
            </div>
        `;
    } else if (layout === 'centered') {
        html = `
            <div class="layout-preview-centered">
                <div class="headlines">
                    ${preheadline ? `<div class="preview-preheadline" style="color: ${primaryColor}">${preheadline}</div>` : ''}
                    <h1 class="preview-headline">${headline}</h1>
                    ${subheadline ? `<p class="preview-subheadline">${subheadline}</p>` : ''}
                </div>
                <div class="mockup">
                    ${mockupUrl ? `<img src="${mockupUrl}" class="preview-mockup" alt="Mockup" style="margin: 0 auto; display: block;">` : '<div style="background: #f3f4f6; border-radius: 8px; height: 300px; max-width: 400px; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: #999;">Mockup Bild</div>'}
                </div>
                <div class="bullets">
                    <ul class="preview-bullets">
                        ${bullets.map(b => `
                            <li class="preview-bullet">
                                <span class="preview-bullet-icon" style="background: ${primaryColor}">${icon}</span>
                                <span>${b}</span>
                            </li>
                        `).join('')}
                    </ul>
                    ${optinMode === 'direct' 
                        ? '<div class="optin-placeholder">📧 E-Mail Optin wird hier angezeigt</div>'
                        : `<a href="#" class="preview-cta" style="background: ${primaryColor}">${ctaText}</a>`
                    }
                </div>
            </div>
        `;
    } else if (layout === 'sidebar') {
        html = `
            <div class="layout-preview-sidebar">
                <div class="headlines">
                    ${preheadline ? `<div class="preview-preheadline" style="color: ${primaryColor}">${preheadline}</div>` : ''}
                    <h1 class="preview-headline">${headline}</h1>
                    ${subheadline ? `<p class="preview-subheadline">${subheadline}</p>` : ''}
                </div>
                <div class="grid">
                    <div>
                        <ul class="preview-bullets">
                            ${bullets.map(b => `
                                <li class="preview-bullet">
                                    <span class="preview-bullet-icon" style="background: ${primaryColor}">${icon}</span>
                                    <span>${b}</span>
                                </li>
                            `).join('')}
                        </ul>
                        ${optinMode === 'direct' 
                            ? '<div class="optin-placeholder">📧 E-Mail Optin wird hier angezeigt</div>'
                            : `<a href="#" class="preview-cta" style="background: ${primaryColor}">${ctaText}</a>`
                        }
                    </div>
                    <div>
                        ${mockupUrl ? `<img src="${mockupUrl}" class="preview-mockup" alt="Mockup">` : '<div style="background: #f3f4f6; border-radius: 8px; height: 300px; display: flex; align-items: center; justify-content: center; color: #999;">Mockup Bild</div>'}
                    </div>
                </div>
            </div>
        `;
    }
    
    document.getElementById('previewContainer').style.background = backgroundColor;
    document.getElementById('previewContent').innerHTML = html;
}
</script>
</body>
</html>