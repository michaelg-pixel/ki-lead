<?php
/**
 * Freebie Editor - Settings/Einstellungen
 * Kompakte Version - nur Optin-Seite bearbeiten
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
    $warning_message = "⚠️ Kategorien nicht verfügbar. <a href='/tools/run-migrations.php'>Migration ausführen</a>";
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
            $sql = "UPDATE customer_freebies SET " .
                   implode(', ', array_map(fn($k) => "$k = ?", array_keys($data))) .
                   ", updated_at = NOW() WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), $freebie['id']]);
            $success_message = "✅ Einstellungen gespeichert!";
        } else {
            // Create
            $unique_id = bin2hex(random_bytes(16));
            $url_slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['headline'])) . '-' . substr($unique_id, 0, 8);
            
            $sql = "INSERT INTO customer_freebies (" . implode(', ', array_keys($data)) . 
                   ", customer_id, unique_id, url_slug, freebie_type, created_at) VALUES (" .
                   str_repeat('?, ', count($data)) . "?, ?, ?, 'custom', NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($data), $customer_id, $unique_id, $url_slug]);
            
            $freebie_id = $pdo->lastInsertId();
            header("Location: ?id={$freebie_id}");
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

// Form Defaults
$form = [
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
        .container { max-width: 1600px; margin: 0 auto; }
        
        /* Header */
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
        
        /* Navigation */
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
        .nav-tab:hover { transform: translateY(-2px); }
        
        /* Grid Layout */
        .editor-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        
        /* Form */
        .form-section { margin-bottom: 32px; }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1a1a2e;
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
        }
        .form-input:focus, .form-textarea:focus, .form-select:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        .form-textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Courier New', monospace;
        }
        
        /* Color Input */
        .color-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .color-wrapper { position: relative; }
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
        
        /* Layout Options */
        .layout-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .layout-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .layout-option:hover {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.05);
        }
        .layout-option.selected {
            border-color: #8B5CF6;
            background: rgba(139, 92, 246, 0.1);
        }
        .layout-option input { display: none; }
        
        /* Info Box */
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #1e3a8a;
        }
        
        /* Alert */
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
        
        /* Button */
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
        
        /* Preview */
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
            transform: scale(0.7);
            transform-origin: top center;
        }
        
        @media (max-width: 1200px) {
            .editor-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="/customer/dashboard.php?page=freebies" class="back-link">← Zurück</a>
        <h1><?php echo $editMode ? '✏️ Freebie bearbeiten' : '✨ Neues Freebie'; ?></h1>
        <p>Einstellungen für deine Optin-Seite</p>
    </div>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if (isset($warning_message)): ?>
        <div class="alert alert-error"><?php echo $warning_message; ?></div>
    <?php endif; ?>
    
    <!-- Navigation -->
    <?php if ($editMode): ?>
    <div class="nav-tabs">
        <a href="?id=<?php echo $freebie['id']; ?>" class="nav-tab active">⚙️ Einstellungen</a>
        <a href="edit-course.php?id=<?php echo $freebie['id']; ?>" class="nav-tab">🎓 Videokurs</a>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="editor-grid">
            <!-- Settings -->
            <div class="panel">
                <h2 class="panel-title">⚙️ Einstellungen</h2>
                
                <!-- Kategorie -->
                <?php if (!empty($categories)): ?>
                <div class="form-section">
                    <div class="section-title">🏷️ Marktplatz-Kategorie</div>
                    <div class="info-box">
                        Wähle eine Kategorie, um dein Freebie im Marktplatz anzubieten.
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <select name="category_id" class="form-select">
                            <option value="">Keine (nicht im Marktplatz)</option>
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
                
                <!-- Texte -->
                <div class="form-section">
                    <div class="section-title">📝 Texte</div>
                    <div class="form-group">
                        <label class="form-label">Pre-Headline (optional)</label>
                        <input type="text" name="preheadline" class="form-input"
                               value="<?php echo htmlspecialchars($form['preheadline']); ?>"
                               placeholder="z.B. KOSTENLOS">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Haupt-Überschrift *</label>
                        <input type="text" name="headline" class="form-input" required
                               value="<?php echo htmlspecialchars($form['headline']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unter-Überschrift (optional)</label>
                        <textarea name="subheadline" class="form-textarea" rows="2"><?php echo htmlspecialchars($form['subheadline']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bullet Points (eine pro Zeile)</label>
                        <textarea name="bullet_points" class="form-textarea" rows="4"><?php echo htmlspecialchars($form['bullet_points']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA Button Text</label>
                        <input type="text" name="cta_text" class="form-input"
                               value="<?php echo htmlspecialchars($form['cta_text']); ?>">
                    </div>
                </div>
                
                <!-- Video -->
                <div class="form-section">
                    <div class="section-title">🎥 Video</div>
                    <div class="form-group">
                        <label class="form-label">Video URL (optional)</label>
                        <input type="url" name="video_url" class="form-input"
                               value="<?php echo htmlspecialchars($form['video_url']); ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Format</label>
                        <div class="layout-grid" style="grid-template-columns: 1fr 1fr;">
                            <label class="layout-option <?php echo $form['video_format'] === 'widescreen' ? 'selected' : ''; ?>">
                                <input type="radio" name="video_format" value="widescreen" 
                                       <?php echo $form['video_format'] === 'widescreen' ? 'checked' : ''; ?>
                                       onchange="this.closest('label').parentElement.querySelectorAll('label').forEach(l => l.classList.remove('selected')); this.closest('label').classList.add('selected')">
                                <div>🖥️</div>
                                <div>Widescreen (16:9)</div>
                            </label>
                            <label class="layout-option <?php echo $form['video_format'] === 'portrait' ? 'selected' : ''; ?>">
                                <input type="radio" name="video_format" value="portrait"
                                       <?php echo $form['video_format'] === 'portrait' ? 'checked' : ''; ?>
                                       onchange="this.closest('label').parentElement.querySelectorAll('label').forEach(l => l.classList.remove('selected')); this.closest('label').classList.add('selected')">
                                <div>📱</div>
                                <div>Hochformat (9:16)</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Mockup -->
                <div class="form-section">
                    <div class="section-title">🖼️ Mockup-Bild</div>
                    <div class="form-group">
                        <label class="form-label">Bild URL (optional)</label>
                        <input type="url" name="mockup_image_url" class="form-input"
                               value="<?php echo htmlspecialchars($form['mockup_image_url']); ?>"
                               placeholder="https://i.imgur.com/example.png">
                    </div>
                </div>
                
                <!-- Design -->
                <div class="form-section">
                    <div class="section-title">🎨 Design</div>
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <div class="layout-grid">
                            <?php
                            $layouts = [
                                'centered' => ['icon' => '📄', 'name' => 'Zentriert'],
                                'left' => ['icon' => '◀️', 'name' => 'Links'],
                                'hybrid' => ['icon' => '⚡', 'name' => 'Hybrid']
                            ];
                            foreach ($layouts as $key => $layout):
                            ?>
                            <label class="layout-option <?php echo $form['layout'] === $key ? 'selected' : ''; ?>">
                                <input type="radio" name="layout" value="<?php echo $key; ?>" 
                                       <?php echo $form['layout'] === $key ? 'checked' : ''; ?>
                                       onchange="this.closest('label').parentElement.querySelectorAll('label').forEach(l => l.classList.remove('selected')); this.closest('label').classList.add('selected')">
                                <div><?php echo $layout['icon']; ?></div>
                                <div><?php echo $layout['name']; ?></div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="color-group">
                        <div class="form-group">
                            <label class="form-label">Hintergrundfarbe</label>
                            <div class="color-wrapper">
                                <div class="color-preview" style="background-color: <?php echo $form['background_color']; ?>"></div>
                                <input type="text" name="background_color" class="form-input color-input"
                                       value="<?php echo htmlspecialchars($form['background_color']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Primärfarbe</label>
                            <div class="color-wrapper">
                                <div class="color-preview" style="background-color: <?php echo $form['primary_color']; ?>"></div>
                                <input type="text" name="primary_color" class="form-input color-input"
                                       value="<?php echo htmlspecialchars($form['primary_color']); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Font für Überschriften</label>
                        <select name="font_heading" class="form-select">
                            <?php foreach ($google_fonts as $font): ?>
                            <option value="<?php echo $font; ?>" <?php echo $form['font_heading'] === $font ? 'selected' : ''; ?>>
                                <?php echo $font; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Font für Text</label>
                        <select name="font_body" class="form-select">
                            <?php foreach ($google_fonts as $font): ?>
                            <option value="<?php echo $font; ?>" <?php echo $form['font_body'] === $font ? 'selected' : ''; ?>>
                                <?php echo $font; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Optin Integration -->
                <div class="form-section">
                    <div class="section-title">📧 E-Mail Optin Code</div>
                    <div class="info-box">
                        Füge hier deinen Optin-Code ein (z.B. von Klick-Tipp, Active Campaign, etc.)
                    </div>
                    <div class="form-group">
                        <label class="form-label">HTML/JavaScript Code</label>
                        <textarea name="raw_code" class="form-textarea" rows="6"><?php echo htmlspecialchars($form['raw_code']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Anzeigemodus</label>
                        <div class="layout-grid" style="grid-template-columns: 1fr 1fr;">
                            <label class="layout-option <?php echo $form['optin_display_mode'] === 'direct' ? 'selected' : ''; ?>">
                                <input type="radio" name="optin_display_mode" value="direct"
                                       <?php echo $form['optin_display_mode'] === 'direct' ? 'checked' : ''; ?>
                                       onchange="this.closest('label').parentElement.querySelectorAll('label').forEach(l => l.classList.remove('selected')); this.closest('label').classList.add('selected')">
                                <div>📄</div>
                                <div>Direkt</div>
                            </label>
                            <label class="layout-option <?php echo $form['optin_display_mode'] === 'popup' ? 'selected' : ''; ?>">
                                <input type="radio" name="optin_display_mode" value="popup"
                                       <?php echo $form['optin_display_mode'] === 'popup' ? 'checked' : ''; ?>
                                       onchange="this.closest('label').parentElement.querySelectorAll('label').forEach(l => l.classList.remove('selected')); this.closest('label').classList.add('selected')">
                                <div>🎯</div>
                                <div>Popup</div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Custom Tracking -->
                <div class="form-section">
                    <div class="section-title">🔧 Custom Tracking</div>
                    <div class="form-group">
                        <label class="form-label">Zusätzlicher Code (optional)</label>
                        <textarea name="custom_code" class="form-textarea" rows="4" 
                                  placeholder="<!-- Tracking Pixel, Facebook Pixel, etc. -->"><?php echo htmlspecialchars($form['custom_code']); ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="save_freebie" class="btn-save">
                    💾 Speichern
                </button>
            </div>
            
            <!-- Preview -->
            <div class="panel">
                <h2 class="panel-title">👁️ Live-Vorschau</h2>
                <div class="preview-box">
                    <div class="preview-content" style="background-color: <?php echo $form['background_color']; ?>; font-family: <?php echo $form['font_body']; ?>;">
                        <?php if (!empty($form['video_url'])): ?>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <div style="background: #000; border-radius: 8px; padding: 20px; color: white;">
                                    🎥 Video wird hier angezeigt
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($form['mockup_image_url'])): ?>
                            <div style="text-align: center; margin-bottom: 20px;">
                                <div style="background: #f3f4f6; border-radius: 8px; padding: 20px;">
                                    🖼️ Mockup-Bild wird hier angezeigt
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($form['preheadline'])): ?>
                            <div style="font-size: 9px; font-weight: 700; text-transform: uppercase; text-align: center; margin-bottom: 10px; color: <?php echo $form['primary_color']; ?>;">
                                <?php echo htmlspecialchars($form['preheadline']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <h1 style="font-size: 22px; font-weight: 800; text-align: center; margin-bottom: 10px; font-family: <?php echo $form['font_heading']; ?>;">
                            <?php echo htmlspecialchars($form['headline']); ?>
                        </h1>
                        
                        <?php if (!empty($form['subheadline'])): ?>
                            <p style="font-size: 13px; color: #6b7280; text-align: center; margin-bottom: 18px;">
                                <?php echo nl2br(htmlspecialchars($form['subheadline'])); ?>
                            </p>
                        <?php endif; ?>
                        
                        <?php if (!empty($form['bullet_points'])): ?>
                            <div style="margin-bottom: 18px;">
                                <?php
                                $bullets = explode("\n", trim($form['bullet_points']));
                                foreach ($bullets as $bullet):
                                    if (empty(trim($bullet))) continue;
                                ?>
                                <div style="display: flex; align-items: start; gap: 8px; margin-bottom: 10px;">
                                    <span style="color: <?php echo $form['primary_color']; ?>; font-size: 14px;">✓</span>
                                    <span style="font-size: 12px; color: #374151;"><?php echo htmlspecialchars(trim($bullet)); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div style="text-align: center;">
                            <button style="padding: 10px 30px; background: <?php echo $form['primary_color']; ?>; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;">
                                <?php echo htmlspecialchars($form['cta_text']); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</body>
</html>