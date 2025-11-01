<?php
session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Kunde';

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
} else {
    // Neues Freebie - Limit prüfen
    if ($customCount >= $freebieLimit) {
        header('Location: dashboard.php?page=freebies');
        exit;
    }
}

// Verfügbare Kurse für Verlinkung laden
$stmt = $pdo->query("SELECT id, title FROM courses WHERE is_active = 1 ORDER BY title");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editMode ? 'Freebie bearbeiten' : 'Neues Freebie erstellen'; ?> - KI Leadsystem</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 20px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .back-btn {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .header h1 {
            font-size: 24px;
            color: white;
        }
        
        .save-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px;
        }
        
        .editor-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
        }
        
        .editor-panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
        }
        
        .panel-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #aaa;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: white;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        
        .color-input-group {
            display: grid;
            grid-template-columns: 1fr 80px;
            gap: 12px;
            align-items: end;
        }
        
        .color-preview {
            width: 80px;
            height: 48px;
            border-radius: 8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
        }
        
        .layout-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .layout-option {
            padding: 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .layout-option:hover {
            border-color: #667eea;
        }
        
        .layout-option.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .layout-icon {
            font-size: 32px;
            margin-bottom: 8px;
        }
        
        .layout-name {
            font-size: 13px;
            font-weight: 600;
            color: #aaa;
        }
        
        .layout-option.active .layout-name {
            color: #667eea;
        }
        
        .preview-frame {
            width: 100%;
            height: 800px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: white;
        }
        
        .bullet-points-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .bullet-input-group {
            display: flex;
            gap: 8px;
        }
        
        .bullet-input {
            flex: 1;
        }
        
        .btn-remove-bullet {
            padding: 12px 16px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: #f87171;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-remove-bullet:hover {
            background: rgba(239, 68, 68, 0.3);
        }
        
        .btn-add-bullet {
            padding: 10px 16px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 8px;
            color: #667eea;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-add-bullet:hover {
            background: rgba(102, 126, 234, 0.3);
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .info-box p {
            font-size: 14px;
            color: #bbb;
            line-height: 1.6;
        }
        
        @media (max-width: 1024px) {
            .editor-layout {
                grid-template-columns: 1fr;
            }
            
            .preview-frame {
                height: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="dashboard.php?page=freebies" class="back-btn">
                ← Zurück
            </a>
            <h1><?php echo $editMode ? '✏️ Freebie bearbeiten' : '✨ Neues Freebie erstellen'; ?></h1>
        </div>
        <button class="save-btn" onclick="saveFreebie()">
            💾 Speichern
        </button>
    </div>
    
    <div class="container">
        <div class="info-box">
            <p><strong>💡 Tipp:</strong> Erstelle eine ansprechende Freebie-Seite mit eigenem Design. Nach dem Speichern erhältst du automatisch die Links für dein Marketing!</p>
        </div>
        
        <div class="editor-layout">
            <!-- Editor Panel -->
            <div class="editor-panel">
                <h2 class="panel-title">⚙️ Einstellungen</h2>
                
                <form id="freebieForm">
                    <input type="hidden" id="freebieId" value="<?php echo $freebie['id'] ?? ''; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Interner Name</label>
                        <input type="text" id="name" class="form-input" 
                               value="<?php echo htmlspecialchars($freebie['name'] ?? ''); ?>" 
                               placeholder="z.B. Social Media Guide" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Überschrift (Preheadline)</label>
                        <input type="text" id="preheadline" class="form-input" 
                               value="<?php echo htmlspecialchars($freebie['preheadline'] ?? ''); ?>" 
                               placeholder="z.B. KOSTENLOSER GUIDE">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hauptüberschrift</label>
                        <input type="text" id="headline" class="form-input" 
                               value="<?php echo htmlspecialchars($freebie['headline'] ?? ''); ?>" 
                               placeholder="z.B. Dein Weg zu mehr Social Media Erfolg" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Unterüberschrift</label>
                        <textarea id="subheadline" class="form-textarea" 
                                  placeholder="z.B. Lerne die wichtigsten Strategien..."><?php echo htmlspecialchars($freebie['subheadline'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Button-Text</label>
                        <input type="text" id="ctaText" class="form-input" 
                               value="<?php echo htmlspecialchars($freebie['cta_text'] ?? 'Jetzt kostenlos sichern'); ?>" 
                               placeholder="Jetzt kostenlos sichern">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-Mail Optin Code</label>
                        <textarea id="emailOptinCode" class="form-textarea" 
                                  placeholder="Füge hier deinen E-Mail-Provider Code ein..."><?php echo htmlspecialchars($freebie['email_optin_code'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Danke-Nachricht</label>
                        <textarea id="thankyouMessage" class="form-textarea" 
                                  placeholder="z.B. Vielen Dank! Check deine E-Mails..."><?php echo htmlspecialchars($freebie['thankyou_message'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Hintergrundfarbe</label>
                        <div class="color-input-group">
                            <input type="text" id="backgroundColor" class="form-input" 
                                   value="<?php echo htmlspecialchars($freebie['background_color'] ?? '#667eea'); ?>" 
                                   placeholder="#667eea">
                            <input type="color" id="backgroundColorPicker" class="color-preview" 
                                   value="<?php echo htmlspecialchars($freebie['background_color'] ?? '#667eea'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Primärfarbe (Button)</label>
                        <div class="color-input-group">
                            <input type="text" id="primaryColor" class="form-input" 
                                   value="<?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>" 
                                   placeholder="#667eea">
                            <input type="color" id="primaryColorPicker" class="color-preview" 
                                   value="<?php echo htmlspecialchars($freebie['primary_color'] ?? '#667eea'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Layout</label>
                        <div class="layout-selector">
                            <div class="layout-option <?php echo (!$freebie || $freebie['layout'] === 'centered') ? 'active' : ''; ?>" 
                                 onclick="selectLayout('centered')">
                                <div class="layout-icon">📄</div>
                                <div class="layout-name">Zentriert</div>
                            </div>
                            <div class="layout-option <?php echo ($freebie && $freebie['layout'] === 'sidebar') ? 'active' : ''; ?>" 
                                 onclick="selectLayout('sidebar')">
                                <div class="layout-icon">📋</div>
                                <div class="layout-name">Sidebar</div>
                            </div>
                            <div class="layout-option <?php echo ($freebie && $freebie['layout'] === 'hybrid') ? 'active' : ''; ?>" 
                                 onclick="selectLayout('hybrid')">
                                <div class="layout-icon">🎨</div>
                                <div class="layout-name">Hybrid</div>
                            </div>
                        </div>
                        <input type="hidden" id="layout" value="<?php echo htmlspecialchars($freebie['layout'] ?? 'centered'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Bullet Points (Vorteile)</label>
                        <div class="bullet-points-container" id="bulletPointsContainer">
                            <?php 
                            $bulletPoints = [];
                            if ($freebie && !empty($freebie['bullet_points'])) {
                                $bulletPoints = json_decode($freebie['bullet_points'], true) ?: [];
                            }
                            if (empty($bulletPoints)) {
                                $bulletPoints = ['', '', ''];
                            }
                            foreach ($bulletPoints as $index => $point): 
                            ?>
                            <div class="bullet-input-group">
                                <input type="text" class="form-input bullet-input" 
                                       value="<?php echo htmlspecialchars($point); ?>" 
                                       placeholder="z.B. Sofort umsetzbar">
                                <?php if ($index > 0): ?>
                                <button type="button" class="btn-remove-bullet" onclick="removeBulletPoint(this)">🗑️</button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn-add-bullet" onclick="addBulletPoint()">
                            + Punkt hinzufügen
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Kurs verknüpfen (optional)</label>
                        <select id="courseId" class="form-select">
                            <option value="">Kein Kurs</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" 
                                    <?php echo ($freebie && $freebie['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Preview Panel -->
            <div class="editor-panel">
                <h2 class="panel-title">👁️ Live-Vorschau</h2>
                <iframe id="previewFrame" class="preview-frame" src="about:blank"></iframe>
            </div>
        </div>
    </div>
    
    <script>
        let selectedLayout = '<?php echo htmlspecialchars($freebie['layout'] ?? 'centered'); ?>';
        
        // Color Picker Sync
        const bgColorInput = document.getElementById('backgroundColor');
        const bgColorPicker = document.getElementById('backgroundColorPicker');
        const primaryColorInput = document.getElementById('primaryColor');
        const primaryColorPicker = document.getElementById('primaryColorPicker');
        
        bgColorInput.addEventListener('input', (e) => {
            bgColorPicker.value = e.target.value;
            updatePreview();
        });
        
        bgColorPicker.addEventListener('input', (e) => {
            bgColorInput.value = e.target.value;
            updatePreview();
        });
        
        primaryColorInput.addEventListener('input', (e) => {
            primaryColorPicker.value = e.target.value;
            updatePreview();
        });
        
        primaryColorPicker.addEventListener('input', (e) => {
            primaryColorInput.value = e.target.value;
            updatePreview();
        });
        
        // Layout Selection
        function selectLayout(layout) {
            selectedLayout = layout;
            document.getElementById('layout').value = layout;
            document.querySelectorAll('.layout-option').forEach(opt => opt.classList.remove('active'));
            event.target.closest('.layout-option').classList.add('active');
            updatePreview();
        }
        
        // Bullet Points Management
        function addBulletPoint() {
            const container = document.getElementById('bulletPointsContainer');
            const div = document.createElement('div');
            div.className = 'bullet-input-group';
            div.innerHTML = `
                <input type="text" class="form-input bullet-input" placeholder="z.B. Sofort umsetzbar">
                <button type="button" class="btn-remove-bullet" onclick="removeBulletPoint(this)">🗑️</button>
            `;
            container.appendChild(div);
        }
        
        function removeBulletPoint(btn) {
            btn.parentElement.remove();
        }
        
        // Live Preview Update
        let previewTimeout;
        document.querySelectorAll('.form-input, .form-textarea, .form-select').forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updatePreview, 500);
            });
        });
        
        function updatePreview() {
            const data = collectFormData();
            const frame = document.getElementById('previewFrame');
            
            // Generate preview HTML
            const html = generatePreviewHTML(data);
            
            frame.srcdoc = html;
        }
        
        function collectFormData() {
            const bulletInputs = document.querySelectorAll('.bullet-input');
            const bulletPoints = Array.from(bulletInputs)
                .map(input => input.value.trim())
                .filter(val => val !== '');
            
            return {
                name: document.getElementById('name').value,
                preheadline: document.getElementById('preheadline').value,
                headline: document.getElementById('headline').value,
                subheadline: document.getElementById('subheadline').value,
                cta_text: document.getElementById('ctaText').value,
                background_color: document.getElementById('backgroundColor').value,
                primary_color: document.getElementById('primaryColor').value,
                layout: selectedLayout,
                bullet_points: bulletPoints
            };
        }
        
        function generatePreviewHTML(data) {
            const bulletHTML = data.bullet_points.map(point => 
                `<li style="margin-bottom: 12px; font-size: 16px; line-height: 1.6;">✓ ${point}</li>`
            ).join('');
            
            return `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body {
                            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                            background: ${data.background_color};
                            min-height: 100vh;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            padding: 20px;
                        }
                        .container {
                            max-width: 800px;
                            background: white;
                            border-radius: 20px;
                            padding: 60px 40px;
                            text-align: center;
                            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                        }
                        .preheadline {
                            font-size: 14px;
                            font-weight: 700;
                            color: ${data.primary_color};
                            letter-spacing: 2px;
                            text-transform: uppercase;
                            margin-bottom: 16px;
                        }
                        .headline {
                            font-size: 42px;
                            font-weight: 700;
                            color: #1a1a2e;
                            line-height: 1.2;
                            margin-bottom: 20px;
                        }
                        .subheadline {
                            font-size: 18px;
                            color: #666;
                            line-height: 1.6;
                            margin-bottom: 32px;
                        }
                        .bullet-list {
                            list-style: none;
                            text-align: left;
                            max-width: 500px;
                            margin: 32px auto;
                            color: #333;
                        }
                        .cta-button {
                            display: inline-block;
                            padding: 18px 40px;
                            background: ${data.primary_color};
                            color: white;
                            font-size: 18px;
                            font-weight: 700;
                            border-radius: 12px;
                            text-decoration: none;
                            margin-top: 24px;
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        ${data.preheadline ? `<div class="preheadline">${data.preheadline}</div>` : ''}
                        <h1 class="headline">${data.headline || 'Deine Überschrift'}</h1>
                        ${data.subheadline ? `<p class="subheadline">${data.subheadline}</p>` : ''}
                        ${bulletHTML ? `<ul class="bullet-list">${bulletHTML}</ul>` : ''}
                        <a href="#" class="cta-button">${data.cta_text || 'Jetzt kostenlos sichern'}</a>
                    </div>
                </body>
                </html>
            `;
        }
        
        // Save Function
        function saveFreebie() {
            const data = collectFormData();
            data.id = document.getElementById('freebieId').value;
            data.email_optin_code = document.getElementById('emailOptinCode').value;
            data.thankyou_message = document.getElementById('thankyouMessage').value;
            data.course_id = document.getElementById('courseId').value;
            
            fetch('/api/save-custom-freebie.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('✅ Freebie erfolgreich gespeichert!');
                    window.location.href = 'dashboard.php?page=freebies';
                } else {
                    alert('❌ Fehler: ' + (result.error || 'Unbekannter Fehler'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Fehler beim Speichern');
            });
        }
        
        // Initial Preview
        updatePreview();
    </script>
</body>
</html>