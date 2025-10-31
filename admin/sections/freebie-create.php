<?php
// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../../config/fonts.php';

// Kurse für Dropdown holen
$courses_stmt = $pdo->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY title");
$courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);

// Wenn Template bearbeitet wird
$editing = false;
$template = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editing = true;
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo '<div class="alert alert-error">Template nicht gefunden!</div>';
        exit;
    }
}

// Defaults zusammenführen
$defaults = array_merge([
    'name' => '',
    'headline' => 'Dein kostenloser KI-Kurs wartet auf dich!',
    'subheadline' => 'Lerne in 7 Tagen die Grundlagen',
    'preheadline' => '',
    'mockup_image_url' => '',
    'course_id' => null,
    'cta_button_text' => 'Jetzt kostenlos sichern',
    'layout' => 'hybrid',
    'background_color' => '#FFF9E6',
    'primary_color' => '#FF8C00',
    'bulletpoints' => '',
    'email_optin_code' => '',
    'custom_raw_code' => '',
    'show_mockup' => 1,
    'is_master_template' => 1
], $fontConfig['defaults']);

$template = array_merge($defaults, $template);
?>

<!-- Google Fonts laden -->
<link href="<?php echo $fontConfig['google_fonts_url']; ?>" rel="stylesheet">

<style>
    .editor-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .editor-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .back-link {
        color: #888;
        text-decoration: none;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .back-link:hover {
        color: #667eea;
    }
    
    .editor-actions {
        display: flex;
        gap: 12px;
    }
    
    .btn-preview {
        padding: 10px 20px;
        background: rgba(255,255,255,0.1);
        color: white;
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-preview:hover {
        background: rgba(255,255,255,0.15);
    }
    
    .btn-save {
        padding: 10px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-save:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .editor-grid {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
    }
    
    .editor-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .editor-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group:last-child {
        margin-bottom: 0;
    }
    
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-label.required::after {
        content: ' *';
        color: #ef4444;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 10px 14px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 14px;
        color: #1f2937;
        transition: all 0.2s;
    }
    
    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: #667eea;
        background: white;
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 100px;
        font-family: monospace;
    }
    
    .form-textarea.large {
        min-height: 200px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 12px;
    }
    
    .color-input {
        width: 100%;
        height: 44px;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        cursor: pointer;
    }
    
    .layout-buttons {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }
    
    .layout-btn {
        padding: 12px;
        background: #f9fafb;
        border: 2px solid #e5e7eb;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s;
    }
    
    .layout-btn:hover {
        border-color: #667eea;
        color: #667eea;
    }
    
    .layout-btn.active {
        background: #667eea;
        border-color: #667eea;
        color: white;
    }
    
    .checkbox-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .checkbox-label {
        font-size: 14px;
        color: #374151;
        cursor: pointer;
    }
    
    .info-box {
        background: #eff6ff;
        border-left: 3px solid #3b82f6;
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 13px;
        color: #1e40af;
        margin-top: 8px;
    }
    
    .help-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    /* Font Select Styles */
    .form-select {
        font-family: inherit;
    }
    
    .form-select option {
        padding: 8px;
    }
    
    /* Upload Area Styles */
    .upload-area {
        border: 2px dashed #cbd5e0;
        background: #f9fafb;
        border-radius: 8px;
        padding: 32px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .upload-area:hover {
        border-color: #667eea;
        background: #f3f4f6;
    }
    
    .upload-area.dragover {
        border-color: #667eea;
        background: #eef2ff;
    }
    
    .upload-icon {
        font-size: 48px;
        color: #9ca3af;
        margin-bottom: 12px;
    }
    
    .upload-text {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .upload-hint {
        color: #9ca3af;
        font-size: 12px;
    }
    
    .preview-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin-top: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    @media (max-width: 1200px) {
        .editor-grid {
            grid-template-columns: 1fr;
        }
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="editor-container">
    <div class="editor-header">
        <div class="editor-title">
            <a href="?page=freebies" class="back-link">← Zurück</a>
            <h2 style="color: white; font-size: 24px; margin: 0;">
                <?php echo $editing ? 'Template bearbeiten' : 'Neues Freebie Template'; ?>
            </h2>
        </div>
        <div class="editor-actions">
            <button type="button" class="btn-preview" onclick="previewTemplate()">
                👁️ Vorschau
            </button>
            <button type="button" class="btn-save" onclick="saveFreebie()">
                💾 Speichern
            </button>
        </div>
    </div>
    
    <form id="freebieForm" onsubmit="return false;">
        <input type="hidden" name="template_id" id="template_id" value="<?php echo $template['id'] ?? ''; ?>">
        <input type="hidden" name="mockup_image_base64" id="mockup_image_base64">
        
        <div class="editor-grid">
            <!-- Linke Spalte: Hauptfelder -->
            <div class="editor-main">
                
                <!-- Grundeinstellungen -->
                <div class="card">
                    <h3 class="card-title">Grundeinstellungen</h3>
                    
                    <div class="form-group">
                        <label class="form-label required">Template Name</label>
                        <input type="text" name="name" id="template_name" class="form-input" 
                               placeholder="z.B. KI-Kurs Lead-Magnet"
                               value="<?php echo htmlspecialchars($template['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">URL-Slug</label>
                        <input type="text" name="url_slug" id="url_slug" class="form-input" 
                               placeholder="ki-kurs-lead-magnet"
                               value="<?php echo htmlspecialchars($template['url_slug'] ?? ''); ?>">
                        <p class="help-text">Optional: Wird automatisch aus dem Namen generiert wenn leer</p>
                    </div>
                </div>
                
                <!-- Pre-Headline -->
                <div class="card">
                    <h3 class="card-title">Pre-Headline</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Text</label>
                        <input type="text" name="preheadline" id="preheadline" class="form-input" 
                               placeholder="z.B. NUR FÜR KURZE ZEIT"
                               value="<?php echo htmlspecialchars($template['preheadline']); ?>"
                               oninput="updatePreview()">
                        <p class="help-text">Kleiner Text über der Hauptüberschrift (optional)</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Schriftart</label>
                            <select name="preheadline_font" id="preheadline_font" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['fonts'] as $font => $category): ?>
                                <option value="<?php echo htmlspecialchars($font); ?>" 
                                        <?php echo ($template['preheadline_font'] ?? 'Poppins') === $font ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($font); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Größe (px)</label>
                            <select name="preheadline_size" id="preheadline_size" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['sizes']['preheadline'] as $size): ?>
                                <option value="<?php echo $size; ?>" 
                                        <?php echo (int)($template['preheadline_size'] ?? 14) === $size ? 'selected' : ''; ?>>
                                    <?php echo $size; ?>px
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Headline -->
                <div class="card">
                    <h3 class="card-title">Headline</h3>
                    
                    <div class="form-group">
                        <label class="form-label required">Text</label>
                        <input type="text" name="headline" id="headline" class="form-input" 
                               placeholder="z.B. Dein kostenloser KI-Kurs wartet auf dich!"
                               value="<?php echo htmlspecialchars($template['headline']); ?>" 
                               oninput="updatePreview()" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Schriftart</label>
                            <select name="headline_font" id="headline_font" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['fonts'] as $font => $category): ?>
                                <option value="<?php echo htmlspecialchars($font); ?>" 
                                        <?php echo ($template['headline_font'] ?? 'Poppins') === $font ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($font); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Größe (px)</label>
                            <select name="headline_size" id="headline_size" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['sizes']['headline'] as $size): ?>
                                <option value="<?php echo $size; ?>" 
                                        <?php echo (int)($template['headline_size'] ?? 48) === $size ? 'selected' : ''; ?>>
                                    <?php echo $size; ?>px
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Subheadline -->
                <div class="card">
                    <h3 class="card-title">Subheadline</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Text</label>
                        <input type="text" name="subheadline" id="subheadline" class="form-input" 
                               placeholder="z.B. Lerne in 7 Tagen die Grundlagen"
                               value="<?php echo htmlspecialchars($template['subheadline']); ?>"
                               oninput="updatePreview()">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Schriftart</label>
                            <select name="subheadline_font" id="subheadline_font" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['fonts'] as $font => $category): ?>
                                <option value="<?php echo htmlspecialchars($font); ?>" 
                                        <?php echo ($template['subheadline_font'] ?? 'Poppins') === $font ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($font); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Größe (px)</label>
                            <select name="subheadline_size" id="subheadline_size" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['sizes']['subheadline'] as $size): ?>
                                <option value="<?php echo $size; ?>" 
                                        <?php echo (int)($template['subheadline_size'] ?? 20) === $size ? 'selected' : ''; ?>>
                                    <?php echo $size; ?>px
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Bulletpoints -->
                <div class="card">
                    <h3 class="card-title">Bulletpoints</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Vorteile / Features</label>
                        <textarea name="bulletpoints" id="bulletpoints" class="form-textarea large" 
                                  placeholder="✓ Erster Vorteil&#10;✓ Zweiter Vorteil&#10;✓ Dritter Vorteil&#10;✓ Vierter Vorteil"><?php echo htmlspecialchars($template['bulletpoints']); ?></textarea>
                        <p class="help-text">Ein Bulletpoint pro Zeile</p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Schriftart</label>
                            <select name="bulletpoints_font" id="bulletpoints_font" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['fonts'] as $font => $category): ?>
                                <option value="<?php echo htmlspecialchars($font); ?>" 
                                        <?php echo ($template['bulletpoints_font'] ?? 'Poppins') === $font ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($font); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Größe (px)</label>
                            <select name="bulletpoints_size" id="bulletpoints_size" class="form-select" onchange="updatePreview()">
                                <?php foreach ($fontConfig['sizes']['bulletpoints'] as $size): ?>
                                <option value="<?php echo $size; ?>" 
                                        <?php echo (int)($template['bulletpoints_size'] ?? 16) === $size ? 'selected' : ''; ?>>
                                    <?php echo $size; ?>px
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Erweitert -->
                <div class="card">
                    <h3 class="card-title">Erweiterte Einstellungen</h3>
                    
                    <div class="form-group">
                        <label class="form-label">E-Mail Optin Code</label>
                        <textarea name="email_optin_code" id="email_optin_code" class="form-textarea" 
                                  placeholder="<form>...</form> oder Autoresponder-Code"><?php echo htmlspecialchars($template['email_optin_code']); ?></textarea>
                        <p class="help-text">HTML-Code für E-Mail-Eintragung (Quentn, ActiveCampaign, etc.)</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Custom Raw Code</label>
                        <textarea name="custom_raw_code" id="custom_raw_code" class="form-textarea" 
                                  placeholder="<script>...</script> oder zusätzlicher HTML-Code"><?php echo htmlspecialchars($template['custom_raw_code']); ?></textarea>
                        <p class="help-text">Zusätzlicher Code für Tracking, Pixel, etc.</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Custom CSS</label>
                        <textarea name="custom_css" id="custom_css" class="form-textarea" 
                                  placeholder=".custom-class { color: red; }"><?php echo htmlspecialchars($template['custom_css'] ?? ''); ?></textarea>
                        <p class="help-text">Eigenes CSS für individuelle Anpassungen</p>
                    </div>
                </div>
            </div>
            
            <!-- Rechte Spalte: Design & Einstellungen -->
            <div class="editor-sidebar">
                
                <!-- Mockup Upload -->
                <div class="card">
                    <h3 class="card-title">📸 Mockup-Bild</h3>
                    
                    <?php if (!empty($template['mockup_image_url'])): ?>
                    <div class="form-group">
                        <p class="help-text" style="margin-bottom: 8px;">Aktuelles Mockup:</p>
                        <img src="<?php echo htmlspecialchars($template['mockup_image_url']); ?>" 
                             alt="Current Mockup" 
                             class="preview-image"
                             id="current-mockup">
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <div class="upload-area" id="uploadArea" onclick="document.getElementById('mockupFileInput').click()">
                            <input type="file" 
                                   id="mockupFileInput" 
                                   accept="image/*" 
                                   style="display: none;"
                                   onchange="handleFileSelect(event)">
                            
                            <div id="uploadPlaceholder">
                                <div class="upload-icon">☁️</div>
                                <div class="upload-text">
                                    <strong>Klicke zum Hochladen</strong> oder ziehe eine Datei hierher
                                </div>
                                <div class="upload-hint">PNG, JPG, GIF, WEBP (Max. 5MB)</div>
                            </div>
                            
                            <img id="imagePreview" class="preview-image" style="display: none;" alt="Preview">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Oder Mockup-URL</label>
                        <input type="url" name="mockup_image_url" id="mockup_image_url" class="form-input" 
                               placeholder="https://example.com/mockup.png"
                               value="<?php echo htmlspecialchars($template['mockup_image_url']); ?>"
                               oninput="updatePreview()">
                        <p class="help-text">Alternativ: Direkte URL zum Mockup-Bild</p>
                    </div>
                </div>
                
                <!-- Design -->
                <div class="card">
                    <h3 class="card-title">Design</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <div class="layout-buttons">
                            <button type="button" class="layout-btn <?php echo $template['layout'] === 'hybrid' ? 'active' : ''; ?>" 
                                    onclick="setLayout('hybrid')">Hybrid</button>
                            <button type="button" class="layout-btn <?php echo $template['layout'] === 'centered' ? 'active' : ''; ?>" 
                                    onclick="setLayout('centered')">Zentriert</button>
                            <button type="button" class="layout-btn <?php echo $template['layout'] === 'sidebar' ? 'active' : ''; ?>" 
                                    onclick="setLayout('sidebar')">Sidebar</button>
                        </div>
                        <input type="hidden" name="layout" id="layout" value="<?php echo $template['layout']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hintergrundfarbe</label>
                        <input type="color" name="background_color" id="background_color" class="color-input"
                               value="<?php echo $template['background_color']; ?>" 
                               oninput="updatePreview()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Primärfarbe</label>
                        <input type="color" name="primary_color" id="primary_color" class="color-input"
                               value="<?php echo $template['primary_color']; ?>" 
                               oninput="updatePreview()">
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="show_mockup" id="show_mockup" class="checkbox-input" 
                               value="1" <?php echo $template['show_mockup'] ? 'checked' : ''; ?>>
                        <label for="show_mockup" class="checkbox-label">Mockup anzeigen</label>
                    </div>
                </div>
                
                <!-- Call-to-Action -->
                <div class="card">
                    <h3 class="card-title">Call-to-Action</h3>
                    
                    <div class="form-group">
                        <label class="form-label">CTA Button Text</label>
                        <input type="text" name="cta_button_text" id="cta_button_text" class="form-input" 
                               placeholder="Jetzt kostenlos sichern"
                               value="<?php echo htmlspecialchars($template['cta_button_text']); ?>"
                               oninput="updatePreview()">
                    </div>
                </div>
                
                <!-- Erweitert -->
                <div class="card">
                    <h3 class="card-title">Verknüpfungen</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Videokurs</label>
                        <select name="course_id" id="course_id" class="form-select">
                            <option value="">-- Kein Videokurs --</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo ($template['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="help-text">Wird auf der Danke-Seite angezeigt</p>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="is_master_template" class="checkbox-input" 
                               value="1" <?php echo $template['is_master_template'] ? 'checked' : ''; ?> id="is_master">
                        <label for="is_master" class="checkbox-label">Als Master-Template markieren</label>
                    </div>
                    
                    <div class="info-box">
                        🍪 Cookie-Banner: Automatisch aktiviert
                    </div>
                </div>
                
            </div>
        </div>
    </form>
</div>

<!-- VORSCHAU MODAL -->
<div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 1200px; max-height: 90vh; overflow-y: auto; position: relative;">
        <div style="position: sticky; top: 0; background: white; border-bottom: 1px solid #e5e7eb; padding: 20px; display: flex; justify-content: space-between; align-items: center; z-index: 10;">
            <h3 style="color: #1f2937; font-size: 20px; margin: 0;">Vorschau</h3>
            <button onclick="closePreview()" style="padding: 8px 16px; background: #f3f4f6; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                ✕ Schließen
            </button>
        </div>
        <div id="previewContent" style="padding: 40px;">
            <!-- Preview wird hier geladen -->
        </div>
    </div>
</div>

<script>
// Hilfsfunktion: Wert sicher aus Input holen
function getInputValue(id, defaultValue = '') {
    const element = document.getElementById(id);
    if (!element) {
        console.warn('Element not found:', id);
        return defaultValue;
    }
    return element.value || defaultValue;
}

// Hilfsfunktion: Checkbox-Wert holen
function getCheckboxValue(id, defaultValue = 0) {
    const element = document.getElementById(id);
    if (!element) {
        console.warn('Checkbox not found:', id);
        return defaultValue;
    }
    return element.checked ? 1 : 0;
}

// Drag & Drop Funktionalität
const uploadArea = document.getElementById('uploadArea');

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
});

// File Select Handler
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        handleFile(file);
    }
}

// File Handler
function handleFile(file) {
    if (!file.type.startsWith('image/')) {
        alert('Bitte nur Bilddateien hochladen!');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('Datei ist zu groß! Maximale Größe: 5MB');
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const base64 = e.target.result;
        
        document.getElementById('uploadPlaceholder').style.display = 'none';
        const preview = document.getElementById('imagePreview');
        preview.src = base64;
        preview.style.display = 'block';
        
        document.getElementById('mockup_image_base64').value = base64;
        
        const currentMockup = document.getElementById('current-mockup');
        if (currentMockup) {
            currentMockup.style.display = 'none';
        }
    };
    reader.readAsDataURL(file);
}

// Layout setzen
function setLayout(layout) {
    document.getElementById('layout').value = layout;
    document.querySelectorAll('.layout-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    updatePreview();
}

// Live-Vorschau Update
function updatePreview() {
    console.log('Preview updated');
}

// Vorschau-Modal öffnen
function previewTemplate() {
    const data = {
        layout: getInputValue('layout', 'hybrid'),
        background_color: getInputValue('background_color', '#FFF9E6'),
        primary_color: getInputValue('primary_color', '#FF8C00'),
        show_mockup: getCheckboxValue('show_mockup', 1),
        mockup_image_url: getInputValue('mockup_image_url', ''),
        
        // Texte
        preheadline: getInputValue('preheadline', ''),
        headline: getInputValue('headline', ''),
        subheadline: getInputValue('subheadline', ''),
        bulletpoints: getInputValue('bulletpoints', ''),
        cta_button_text: getInputValue('cta_button_text', ''),
        
        // Fonts & Sizes
        preheadline_font: getInputValue('preheadline_font', 'Poppins'),
        preheadline_size: getInputValue('preheadline_size', '14'),
        headline_font: getInputValue('headline_font', 'Poppins'),
        headline_size: getInputValue('headline_size', '48'),
        subheadline_font: getInputValue('subheadline_font', 'Poppins'),
        subheadline_size: getInputValue('subheadline_size', '20'),
        bulletpoints_font: getInputValue('bulletpoints_font', 'Poppins'),
        bulletpoints_size: getInputValue('bulletpoints_size', '16'),
    };
    
    // Mockup URL oder Preview verwenden
    if (document.getElementById('imagePreview').style.display === 'block') {
        data.mockup_image_url = document.getElementById('imagePreview').src;
    }
    
    const previewHTML = generatePreviewHTML(data);
    
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    
    content.innerHTML = previewHTML;
    modal.style.display = 'flex';
}

// Vorschau-Modal schließen
function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

// HTML für Vorschau generieren
function generatePreviewHTML(data) {
    const layout = data.layout || 'hybrid';
    const bgColor = data.background_color || '#FFF9E6';
    const primaryColor = data.primary_color || '#FF8C00';
    const showMockup = data.show_mockup === 1;
    const mockupUrl = data.mockup_image_url || '';
    
    // Bulletpoints verarbeiten
    let bulletpointsHTML = '';
    if (data.bulletpoints) {
        const bullets = data.bulletpoints.split('\n').filter(b => b.trim());
        bulletpointsHTML = bullets.map(bullet => 
            `<div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
                <span style="color: ${primaryColor}; font-size: 20px; flex-shrink: 0;">✓</span>
                <span style="color: #374151; font-size: ${data.bulletpoints_size}px; font-family: '${data.bulletpoints_font}', sans-serif;">${escapeHtml(bullet.replace(/^[✓✔︎•-]\s*/, ''))}</span>
            </div>`
        ).join('');
    }
    
    // Layout-spezifisches HTML
    let layoutHTML = '';
    
    if (layout === 'hybrid' || layout === 'sidebar') {
        layoutHTML = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">
                <div style="order: ${layout === 'sidebar' ? '2' : '1'};">
                    ${showMockup && mockupUrl ? `
                        <img src="${escapeHtml(mockupUrl)}" alt="Mockup" style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">
                    ` : `
                        <div style="width: 100%; aspect-ratio: 4/3; background: linear-gradient(135deg, ${primaryColor}20, ${primaryColor}40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ${primaryColor}; font-size: 48px;">
                            🎁
                        </div>
                    `}
                </div>
                <div style="order: ${layout === 'sidebar' ? '1' : '2'};">
                    ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: ${data.preheadline_size}px; font-family: '${data.preheadline_font}', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">${escapeHtml(data.preheadline)}</div>` : ''}
                    
                    <h1 style="font-size: ${data.headline_size}px; font-family: '${data.headline_font}', sans-serif; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">
                        ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                    </h1>
                    
                    ${data.subheadline ? `<p style="font-size: ${data.subheadline_size}px; font-family: '${data.subheadline_font}', sans-serif; color: #6b7280; margin-bottom: 32px;">${escapeHtml(data.subheadline)}</p>` : ''}
                    
                    ${bulletpointsHTML ? `<div style="margin-bottom: 32px;">${bulletpointsHTML}</div>` : ''}
                    
                    <button style="background: ${primaryColor}; color: white; padding: 16px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ${primaryColor}40;">
                        ${escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern')}
                    </button>
                </div>
            </div>
        `;
    } else {
        layoutHTML = `
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: ${data.preheadline_size}px; font-family: '${data.preheadline_font}', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">${escapeHtml(data.preheadline)}</div>` : ''}
                
                <h1 style="font-size: ${data.headline_size}px; font-family: '${data.headline_font}', sans-serif; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">
                    ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                </h1>
                
                ${data.subheadline ? `<p style="font-size: ${data.subheadline_size}px; font-family: '${data.subheadline_font}', sans-serif; color: #6b7280; margin-bottom: 40px;">${escapeHtml(data.subheadline)}</p>` : ''}
                
                ${showMockup && mockupUrl ? `
                    <img src="${escapeHtml(mockupUrl)}" alt="Mockup" style="width: 100%; max-width: 500px; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); margin-bottom: 40px;">
                ` : `
                    <div style="width: 100%; max-width: 500px; aspect-ratio: 4/3; background: linear-gradient(135deg, ${primaryColor}20, ${primaryColor}40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ${primaryColor}; font-size: 64px; margin: 0 auto 40px;">
                        🎁
                    </div>
                `}
                
                ${bulletpointsHTML ? `<div style="text-align: left; max-width: 500px; margin: 0 auto 40px;">${bulletpointsHTML}</div>` : ''}
                
                <button style="background: ${primaryColor}; color: white; padding: 18px 48px; border: none; border-radius: 8px; font-size: 20px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ${primaryColor}40;">
                    ${escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern')}
                </button>
            </div>
        `;
    }
    
    return `
        <div style="background: ${bgColor}; padding: 80px 40px; min-height: 600px; border-radius: 8px;">
            ${layoutHTML}
        </div>
    `;
}

// HTML escapen
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ROBUSTE Speichern-Funktion
async function saveFreebie() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '⏳ Speichere...';
    btn.disabled = true;
    
    try {
        const data = {
            template_id: getInputValue('template_id', ''),
            name: getInputValue('template_name', ''),
            url_slug: getInputValue('url_slug', ''),
            headline: getInputValue('headline', ''),
            subheadline: getInputValue('subheadline', ''),
            preheadline: getInputValue('preheadline', ''),
            bulletpoints: getInputValue('bulletpoints', ''),
            cta_button_text: getInputValue('cta_button_text', 'Jetzt kostenlos sichern'),
            layout: getInputValue('layout', 'hybrid'),
            primary_color: getInputValue('primary_color', '#FF8C00'),
            secondary_color: '#EC4899',
            background_color: getInputValue('background_color', '#FFF9E6'),
            text_color: '#1F2937',
            cta_button_color: '#5B8DEF',
            mockup_image_url: getInputValue('mockup_image_url', ''),
            mockup_image_base64: getInputValue('mockup_image_base64', ''),
            custom_raw_code: getInputValue('custom_raw_code', ''),
            custom_css: getInputValue('custom_css', ''),
            email_optin_code: getInputValue('email_optin_code', ''),
            course_id: getInputValue('course_id', ''),
            is_master_template: getCheckboxValue('is_master', 1),
            show_mockup: getCheckboxValue('show_mockup', 1),
            
            // Font-Einstellungen
            preheadline_font: getInputValue('preheadline_font', 'Poppins'),
            preheadline_size: parseInt(getInputValue('preheadline_size', '14')),
            headline_font: getInputValue('headline_font', 'Poppins'),
            headline_size: parseInt(getInputValue('headline_size', '48')),
            subheadline_font: getInputValue('subheadline_font', 'Poppins'),
            subheadline_size: parseInt(getInputValue('subheadline_size', '20')),
            bulletpoints_font: getInputValue('bulletpoints_font', 'Poppins'),
            bulletpoints_size: parseInt(getInputValue('bulletpoints_size', '16')),
        };
        
        console.log('Sending data:', data);
        
        // Validierung
        if (!data.name || data.name.trim() === '') {
            throw new Error('Template-Name ist erforderlich');
        }
        
        if (!data.headline || data.headline.trim() === '') {
            throw new Error('Headline ist erforderlich');
        }
        
        const response = await fetch('/api/save-freebie.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const responseText = await response.text();
        console.log('Response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            throw new Error('Ungültige Server-Antwort: ' + responseText.substring(0, 200));
        }
        
        if (result.success) {
            alert('✅ Template erfolgreich gespeichert!');
            window.location.href = '?page=freebies';
        } else {
            throw new Error(result.error || 'Unbekannter Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('❌ Fehler: ' + error.message);
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Event Listeners
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('previewModal');
        if (modal && modal.style.display === 'flex') {
            closePreview();
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    }
});
</script>
