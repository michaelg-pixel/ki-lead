<?php
// Template-ID prüfen
if (empty($_GET['id'])) {
    echo '<div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin: 20px;">
            <p style="color: #991b1b; font-weight: 600;">Template-ID fehlt!</p>
          </div>';
    echo '<a href="?page=freebies" style="color: #8b5cf6; text-decoration: underline;">← Zurück zur Übersicht</a>';
    exit;
}

$template_id = (int)$_GET['id'];

// Template laden
try {
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
    $stmt->execute([$template_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo '<div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin: 20px;">
                <p style="color: #991b1b; font-weight: 600;">Template nicht gefunden!</p>
              </div>';
        echo '<a href="?page=freebies" style="color: #8b5cf6; text-decoration: underline;">← Zurück zur Übersicht</a>';
        exit;
    }
    
    // Layout zurück-mappen
    $layoutMapping = [
        'layout1' => 'hybrid',
        'layout2' => 'centered',
        'layout3' => 'sidebar'
    ];
    
    if (isset($template['layout']) && isset($layoutMapping[$template['layout']])) {
        $template['layout'] = $layoutMapping[$template['layout']];
    }
    
    // Kurse für Dropdown laden
    $courses_stmt = $pdo->query("SELECT id, title FROM courses ORDER BY title");
    $courses = $courses_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo '<div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin: 20px;">
            <p style="color: #991b1b; font-weight: 600;">Datenbankfehler: ' . htmlspecialchars($e->getMessage()) . '</p>
          </div>';
    exit;
}
?>

<!-- Tailwind wird jetzt im dashboard.php Head geladen -->

<style>
    body {
        background: #1e1e2e;
    }
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-input, .form-select, .form-textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        transition: all 0.2s;
    }
    
    .form-input::placeholder,
    .form-textarea::placeholder {
        color: #9ca3af;
        opacity: 1;
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }
    .form-textarea {
        font-family: 'Courier New', monospace;
        resize: vertical;
    }
    .helper-text {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }
    .layout-btn {
        padding: 8px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
    }
    .layout-btn.active {
        background: #8b5cf6;
        color: white;
        border-color: #8b5cf6;
    }
    .file-upload-area {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .file-upload-area:hover {
        border-color: #8b5cf6;
        background: #f9fafb;
    }
    .file-upload-area.dragover {
        border-color: #8b5cf6;
        background: #f3e8ff;
    }
    .mockup-preview {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
        margin-top: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
</style>

<div class="max-w-7xl mx-auto">
    
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <a href="?page=freebies" class="text-gray-400 hover:text-white text-sm inline-block mb-2">
                ← Zurück
            </a>
            <h1 class="text-2xl font-bold text-white">Template bearbeiten</h1>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="previewTemplate()" class="bg-gray-700 text-white px-5 py-2.5 rounded-lg hover:bg-gray-600 transition">
                👁 Vorschau
            </button>
            <button type="button" onclick="saveTemplate()" id="saveBtn" class="bg-purple-600 text-white px-5 py-2.5 rounded-lg hover:bg-purple-700 transition font-medium">
                💾 Speichern
            </button>
        </div>
    </div>

    <!-- 2-Spalten Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Linke Spalte (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            
            <form id="editTemplateForm">
                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                
                <!-- Grundeinstellungen -->
                <div class="card">
                    <h2 class="card-title">Grundeinstellungen</h2>
                    
                    <div class="mb-4">
                        <label class="form-label">Template Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($template['name']); ?>" 
                               placeholder="z.B. KI-Kurs Lead-Magnet" class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="form-label">URL-Slug</label>
                        <input type="text" name="url_slug" value="<?php echo htmlspecialchars($template['url_slug'] ?? ''); ?>"
                               placeholder="ki-kurs-lead-magnet" class="form-input">
                        <p class="helper-text">Optional: Wird automatisch aus dem Namen generiert wenn leer</p>
                    </div>
                </div>

                <!-- Inhalte -->
                <div class="card">
                    <h2 class="card-title">Inhalte</h2>
                    
                    <div class="mb-4">
                        <label class="form-label">Pre-Headline</label>
                        <input type="text" name="preheadline" id="preheadline" value="<?php echo htmlspecialchars($template['preheadline'] ?? ''); ?>"
                               placeholder="z.B. NUR FÜR KURZE ZEIT" class="form-input">
                        <p class="helper-text">Kleiner Text über der Hauptüberschrift (optional)</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Headline *</label>
                        <input type="text" name="headline" id="headline" value="<?php echo htmlspecialchars($template['headline']); ?>"
                               placeholder="Dein kostenloser KI-Kurs wartet auf dich!" class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Subheadline</label>
                        <input type="text" name="subheadline" id="subheadline" value="<?php echo htmlspecialchars($template['subheadline'] ?? ''); ?>"
                               placeholder="Lerne in 7 Tagen die Grundlagen" class="form-input">
                    </div>
                </div>

                <!-- Bulletpoints -->
                <div class="card">
                    <h2 class="card-title">Bulletpoints</h2>
                    
                    <div>
                        <label class="form-label">Vorteile / Features</label>
                        <textarea name="bulletpoints" id="bulletpoints" rows="8" class="form-textarea"
                                  placeholder="✓ Erster Vorteil&#10;✓ Zweiter Vorteil&#10;✓ Dritter Vorteil&#10;✓ Vierter Vorteil"><?php echo htmlspecialchars($template['bullet_points'] ?? ''); ?></textarea>
                        <p class="helper-text">Ein Bulletpoint pro Zeile</p>
                    </div>
                </div>

                <!-- Erweiterte Einstellungen -->
                <div class="card">
                    <h2 class="card-title">Erweiterte Einstellungen</h2>
                    
                    <div class="mb-4">
                        <label class="form-label">E-Mail Optin Code</label>
                        <textarea name="optin_code" rows="4" class="form-textarea"
                                  placeholder="<form>...</form> oder Autoresponder-Code"></textarea>
                        <p class="helper-text">HTML-Code für E-Mail-Eintragung (Quentn, ActiveCampaign etc.)</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Custom Raw Code</label>
                        <textarea name="custom_raw_code" rows="4" class="form-textarea"
                                  placeholder="<script>...</script> oder zusätzlicher HTML-Code"><?php echo htmlspecialchars($template['raw_code'] ?? ''); ?></textarea>
                        <p class="helper-text">Zusätzlicher Code für Tracking, Pixel, etc.</p>
                    </div>
                    
                    <div>
                        <label class="form-label">Custom CSS</label>
                        <textarea name="custom_css" rows="4" class="form-textarea"
                                  placeholder=".custom-class { color: red; }"><?php echo htmlspecialchars($template['custom_css'] ?? ''); ?></textarea>
                        <p class="helper-text">Eigenes CSS für individuelle Anpassungen</p>
                    </div>
                </div>
                
            </form>
        </div>

        <!-- Rechte Spalte (1/3) -->
        <div class="space-y-6">
            
            <!-- Design -->
            <div class="card">
                <h2 class="card-title">Design</h2>
                
                <div class="mb-4">
                    <label class="form-label">Layout</label>
                    <div class="flex gap-2">
                        <button type="button" class="layout-btn <?php echo ($template['layout'] == 'hybrid') ? 'active' : ''; ?>" 
                                onclick="selectLayout('hybrid')">Hybrid</button>
                        <button type="button" class="layout-btn <?php echo ($template['layout'] == 'centered') ? 'active' : ''; ?>" 
                                onclick="selectLayout('centered')">Zentriert</button>
                        <button type="button" class="layout-btn <?php echo ($template['layout'] == 'sidebar') ? 'active' : ''; ?>" 
                                onclick="selectLayout('sidebar')">Sidebar</button>
                    </div>
                    <input type="hidden" name="layout" id="layoutInput" value="<?php echo $template['layout']; ?>">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Hintergrundfarbe</label>
                    <input type="color" name="background_color" id="background_color" value="<?php echo $template['background_color'] ?? '#FFFFFF'; ?>"
                           class="form-input" style="height: 50px; cursor: pointer;">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Primärfarbe</label>
                    <input type="color" name="primary_color" id="primary_color" value="<?php echo $template['primary_color'] ?? '#7C3AED'; ?>"
                           class="form-input" style="height: 50px; cursor: pointer;">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Mockup Bild</label>
                    <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('mockupImageInput').click()">
                        <input type="file" id="mockupImageInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
                        <div id="uploadPrompt" style="<?php echo !empty($template['mockup_image_url']) ? 'display: none;' : ''; ?>">
                            <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">📷 Bild hochladen</p>
                            <p style="color: #9ca3af; font-size: 12px;">Klicken oder Bild hierher ziehen</p>
                        </div>
                        <?php if (!empty($template['mockup_image_url'])): ?>
                        <div id="currentImage">
                            <img src="<?php echo htmlspecialchars($template['mockup_image_url']); ?>" class="mockup-preview" alt="Aktuelles Mockup">
                            <p style="color: #6b7280; font-size: 12px; margin-top: 8px;">Aktuelles Bild</p>
                            <button type="button" onclick="removeImage(event)" style="margin-top: 8px; padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                                🗑️ Entfernen
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <p class="helper-text">PNG, JPG oder GIF (max. 5MB)</p>
                    <input type="hidden" name="mockup_image_url" id="mockup_image_url" value="<?php echo htmlspecialchars($template['mockup_image_url'] ?? ''); ?>">
                </div>
            </div>

            <!-- Typografie -->
            <div class="card">
                <h2 class="card-title">Typografie</h2>
                
                <div class="mb-4">
                    <label class="form-label">Headline Schriftart</label>
                    <select name="headline_font" id="headline_font" class="form-select">
                        <option value="Inter" <?php echo ($template['headline_font'] ?? 'Inter') == 'Inter' ? 'selected' : ''; ?>>Inter (Standard)</option>
                        <option value="Poppins" <?php echo ($template['headline_font'] ?? '') == 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                        <option value="Roboto" <?php echo ($template['headline_font'] ?? '') == 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                        <option value="Montserrat" <?php echo ($template['headline_font'] ?? '') == 'Montserrat' ? 'selected' : ''; ?>>Montserrat</option>
                        <option value="Open Sans" <?php echo ($template['headline_font'] ?? '') == 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                        <option value="Lato" <?php echo ($template['headline_font'] ?? '') == 'Lato' ? 'selected' : ''; ?>>Lato</option>
                        <option value="Playfair Display" <?php echo ($template['headline_font'] ?? '') == 'Playfair Display' ? 'selected' : ''; ?>>Playfair Display</option>
                        <option value="Merriweather" <?php echo ($template['headline_font'] ?? '') == 'Merriweather' ? 'selected' : ''; ?>>Merriweather</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Headline Schriftgröße</label>
                    <div class="form-grid">
                        <div>
                            <input type="number" name="headline_size" id="headline_size" value="<?php echo $template['headline_size'] ?? 48; ?>" 
                                   class="form-input" min="20" max="100">
                            <p class="helper-text">Desktop (px)</p>
                        </div>
                        <div>
                            <input type="number" name="headline_size_mobile" id="headline_size_mobile" value="<?php echo $template['headline_size_mobile'] ?? 32; ?>" 
                                   class="form-input" min="16" max="60">
                            <p class="helper-text">Mobil (px)</p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Body Schriftart</label>
                    <select name="body_font" id="body_font" class="form-select">
                        <option value="Inter" <?php echo ($template['body_font'] ?? 'Inter') == 'Inter' ? 'selected' : ''; ?>>Inter (Standard)</option>
                        <option value="Poppins" <?php echo ($template['body_font'] ?? '') == 'Poppins' ? 'selected' : ''; ?>>Poppins</option>
                        <option value="Roboto" <?php echo ($template['body_font'] ?? '') == 'Roboto' ? 'selected' : ''; ?>>Roboto</option>
                        <option value="Montserrat" <?php echo ($template['body_font'] ?? '') == 'Montserrat' ? 'selected' : ''; ?>>Montserrat</option>
                        <option value="Open Sans" <?php echo ($template['body_font'] ?? '') == 'Open Sans' ? 'selected' : ''; ?>>Open Sans</option>
                        <option value="Lato" <?php echo ($template['body_font'] ?? '') == 'Lato' ? 'selected' : ''; ?>>Lato</option>
                    </select>
                </div>
                
                <div>
                    <label class="form-label">Body Schriftgröße</label>
                    <div class="form-grid">
                        <div>
                            <input type="number" name="body_size" id="body_size" value="<?php echo $template['body_size'] ?? 16; ?>" 
                                   class="form-input" min="12" max="24">
                            <p class="helper-text">Desktop (px)</p>
                        </div>
                        <div>
                            <input type="number" name="body_size_mobile" id="body_size_mobile" value="<?php echo $template['body_size_mobile'] ?? 14; ?>" 
                                   class="form-input" min="12" max="20">
                            <p class="helper-text">Mobil (px)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Call-to-Action -->
            <div class="card">
                <h2 class="card-title">Call-to-Action</h2>
                
                <div>
                    <label class="form-label">CTA Button Text</label>
                    <input type="text" name="cta_button_text" id="cta_button_text" value="<?php echo htmlspecialchars($template['cta_text'] ?? 'Jetzt kostenlos sichern'); ?>"
                           class="form-input">
                </div>
            </div>

            <!-- Verknüpfungen -->
            <div class="card">
                <h2 class="card-title">Verknüpfungen</h2>
                
                <div>
                    <label class="form-label">Videokurs</label>
                    <select name="course_id" class="form-select">
                        <option value="">-- Kein Videokurs --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo ($template['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="helper-text">Wird auf der Danke-Seite angezeigt</p>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- ============================================ -->
<!-- VORSCHAU MODAL -->
<!-- ============================================ -->
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
let uploadedImageFile = null;

// Drag & Drop Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('fileUploadArea');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, () => {
            uploadArea.classList.remove('dragover');
        }, false);
    });
    
    uploadArea.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    }, false);
});

function handleFileSelect(event) {
    const file = event.target.files[0];
    if (file) {
        handleFile(file);
    }
}

function handleFile(file) {
    // Validierung
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
    
    if (!allowedTypes.includes(file.type)) {
        alert('❌ Nur Bilddateien erlaubt (PNG, JPG, GIF, WEBP)');
        return;
    }
    
    if (file.size > maxSize) {
        alert('❌ Datei zu groß! Maximal 5MB erlaubt.');
        return;
    }
    
    // Bild speichern für Upload
    uploadedImageFile = file;
    
    // Vorschau anzeigen
    const reader = new FileReader();
    reader.onload = function(e) {
        const uploadArea = document.getElementById('fileUploadArea');
        uploadArea.innerHTML = `
            <div id="imagePreview">
                <img src="${e.target.result}" class="mockup-preview" alt="Vorschau">
                <p style="color: #10b981; font-size: 12px; margin-top: 8px;">✓ Neues Bild ausgewählt (wird beim Speichern hochgeladen)</p>
                <button type="button" onclick="removeImage(event)" style="margin-top: 8px; padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer;">
                    🗑️ Entfernen
                </button>
            </div>
        `;
    };
    reader.readAsDataURL(file);
}

function removeImage(event) {
    event.stopPropagation();
    uploadedImageFile = null;
    document.getElementById('mockup_image_url').value = '';
    const uploadArea = document.getElementById('fileUploadArea');
    uploadArea.innerHTML = `
        <input type="file" id="mockupImageInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
        <div id="uploadPrompt">
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">📷 Bild hochladen</p>
            <p style="color: #9ca3af; font-size: 12px;">Klicken oder Bild hierher ziehen</p>
        </div>
    `;
    uploadArea.onclick = () => document.getElementById('mockupImageInput').click();
}

function selectLayout(layout) {
    // Alle Buttons deaktivieren
    document.querySelectorAll('.layout-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Aktiven Button markieren
    event.target.classList.add('active');
    
    // Hidden Input aktualisieren
    document.getElementById('layoutInput').value = layout;
}

// VORSCHAU-FUNKTION
function previewTemplate() {
    const form = document.getElementById('editTemplateForm');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Layout hinzufügen
    data.layout = document.getElementById('layoutInput').value;
    
    // Schriftarten und -größen
    data.headline_font = document.getElementById('headline_font').value;
    data.headline_size = document.getElementById('headline_size').value;
    data.headline_size_mobile = document.getElementById('headline_size_mobile').value;
    data.body_font = document.getElementById('body_font').value;
    data.body_size = document.getElementById('body_size').value;
    data.body_size_mobile = document.getElementById('body_size_mobile').value;
    
    // Mockup URL - prüfe ob neues Bild hochgeladen wurde
    if (uploadedImageFile) {
        const reader = new FileReader();
        reader.onload = function(e) {
            data.mockup_image_url = e.target.result;
            data.show_mockup = '1'; // Immer anzeigen wenn Bild vorhanden
            showPreview(data);
        };
        reader.readAsDataURL(uploadedImageFile);
    } else {
        // Nutze existierende URL oder leeren String
        data.mockup_image_url = document.getElementById('mockup_image_url').value || '';
        data.show_mockup = data.mockup_image_url ? '1' : '0';
        showPreview(data);
    }
}

function showPreview(data) {
    // Vorschau-HTML generieren
    const previewHTML = generatePreviewHTML(data);
    
    // Modal anzeigen
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    
    content.innerHTML = previewHTML;
    modal.style.display = 'flex';
}

// Vorschau-Modal schließen
function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.style.display = 'none';
}

// HTML für Vorschau generieren
function generatePreviewHTML(data) {
    const layout = data.layout || 'hybrid';
    const bgColor = data.background_color || '#FFFFFF';
    const primaryColor = data.primary_color || '#7C3AED';
    const showMockup = data.show_mockup === '1' && data.mockup_image_url;
    const mockupUrl = data.mockup_image_url || '';
    
    // Typografie
    const headlineFont = data.headline_font || 'Inter';
    const headlineSize = data.headline_size || '48';
    const bodyFont = data.body_font || 'Inter';
    const bodySize = data.body_size || '16';
    
    // Google Fonts laden
    const fontsToLoad = new Set([headlineFont, bodyFont]);
    const fontLinks = Array.from(fontsToLoad).map(font => 
        `<link href="https://fonts.googleapis.com/css2?family=${font.replace(' ', '+')}:wght@400;600;700;800&display=swap" rel="stylesheet">`
    ).join('');
    
    // Bulletpoints verarbeiten
    let bulletpointsHTML = '';
    if (data.bulletpoints) {
        const bullets = data.bulletpoints.split('\n').filter(b => b.trim());
        bulletpointsHTML = bullets.map(bullet => 
            `<div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
                <span style="color: ${primaryColor}; font-size: 20px; flex-shrink: 0;">✓</span>
                <span style="color: #374151; font-size: ${bodySize}px; font-family: '${bodyFont}', sans-serif;">${escapeHtml(bullet.replace(/^[✓✔︎•-]\s*/, ''))}</span>
            </div>`
        ).join('');
    }
    
    // Layout-spezifisches HTML
    let layoutHTML = '';
    
    if (layout === 'hybrid' || layout === 'sidebar') {
        // Zwei-Spalten Layout
        layoutHTML = `
            ${fontLinks}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">
                <div style="order: ${layout === 'sidebar' ? '2' : '1'};">
                    ${showMockup && mockupUrl ? `
                        <img src="${escapeHtml(mockupUrl)}" alt="Mockup" style="width: 100%; height: auto; border-radius: 12px;">
                    ` : `
                        <div style="width: 100%; aspect-ratio: 4/3; background: linear-gradient(135deg, ${primaryColor}20, ${primaryColor}40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ${primaryColor}; font-size: 48px;">
                            🎁
                        </div>
                    `}
                </div>
                <div style="order: ${layout === 'sidebar' ? '1' : '2'};">
                    ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-family: '${headlineFont}', sans-serif;">${escapeHtml(data.preheadline)}</div>` : ''}
                    
                    <h1 style="font-size: ${headlineSize}px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px; font-family: '${headlineFont}', sans-serif;">
                        ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                    </h1>
                    
                    ${data.subheadline ? `<p style="font-size: 20px; color: #6b7280; margin-bottom: 32px; font-family: '${bodyFont}', sans-serif;">${escapeHtml(data.subheadline)}</p>` : ''}
                    
                    ${bulletpointsHTML ? `<div style="margin-bottom: 32px;">${bulletpointsHTML}</div>` : ''}
                    
                    <button style="background: ${primaryColor}; color: white; padding: 16px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; font-family: '${bodyFont}', sans-serif;">
                        ${escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern')}
                    </button>
                </div>
            </div>
        `;
    } else {
        // Zentriertes Layout
        layoutHTML = `
            ${fontLinks}
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-family: '${headlineFont}', sans-serif;">${escapeHtml(data.preheadline)}</div>` : ''}
                
                <h1 style="font-size: ${headlineSize}px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px; font-family: '${headlineFont}', sans-serif;">
                    ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                </h1>
                
                ${data.subheadline ? `<p style="font-size: 22px; color: #6b7280; margin-bottom: 40px; font-family: '${bodyFont}', sans-serif;">${escapeHtml(data.subheadline)}</p>` : ''}
                
                ${showMockup && mockupUrl ? `
                    <img src="${escapeHtml(mockupUrl)}" alt="Mockup" style="width: 100%; max-width: 500px; height: auto; border-radius: 12px; margin-bottom: 40px;">
                ` : `
                    <div style="width: 100%; max-width: 500px; aspect-ratio: 4/3; background: linear-gradient(135deg, ${primaryColor}20, ${primaryColor}40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ${primaryColor}; font-size: 64px; margin: 0 auto 40px;">
                        🎁
                    </div>
                `}
                
                ${bulletpointsHTML ? `<div style="text-align: left; max-width: 500px; margin: 0 auto 40px;">${bulletpointsHTML}</div>` : ''}
                
                <button style="background: ${primaryColor}; color: white; padding: 18px 48px; border: none; border-radius: 8px; font-size: 20px; font-weight: 600; cursor: pointer; font-family: '${bodyFont}', sans-serif;">
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
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function saveTemplate() {
    const btn = document.getElementById('saveBtn');
    const form = document.getElementById('editTemplateForm');
    const formData = new FormData(form);
    
    // Layout aus hidden input hinzufügen
    formData.set('layout', document.getElementById('layoutInput').value);
    
    // Typografie Felder hinzufügen
    formData.set('headline_font', document.getElementById('headline_font').value);
    formData.set('headline_size', document.getElementById('headline_size').value);
    formData.set('headline_size_mobile', document.getElementById('headline_size_mobile').value);
    formData.set('body_font', document.getElementById('body_font').value);
    formData.set('body_size', document.getElementById('body_size').value);
    formData.set('body_size_mobile', document.getElementById('body_size_mobile').value);
    
    // Wenn Bild hochgeladen wurde, hinzufügen
    if (uploadedImageFile) {
        formData.append('mockup_image', uploadedImageFile);
    }
    
    // Button deaktivieren
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Speichert...';
    
    try {
        const response = await fetch('/api/save-freebie.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            btn.innerHTML = '✅ Gespeichert!';
            setTimeout(() => {
                window.location.href = '?page=freebies&updated=1';
            }, 800);
        } else {
            alert('❌ Fehler: ' + (result.error || 'Unbekannter Fehler'));
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (error) {
        alert('❌ Netzwerkfehler: ' + error.message);
        console.error('Save error:', error);
        btn.disabled = false;
        btn.innerHTML = originalText;
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