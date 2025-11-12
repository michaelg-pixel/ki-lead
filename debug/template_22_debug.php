<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Debug - ID 22</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
        }
        
        .section {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            border: 2px solid #e5e7eb;
        }
        
        .section h2 {
            color: #1f2937;
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
        }
        
        .field {
            margin-bottom: 16px;
        }
        
        .field-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        
        .field-value {
            background: white;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            word-break: break-word;
        }
        
        .field-value.empty {
            color: #9ca3af;
            font-style: italic;
        }
        
        .field-value.null {
            color: #ef4444;
            font-weight: 600;
        }
        
        .field-value pre {
            margin: 0;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 16px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .status-badge.ok {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Template Debug - ID 22</h1>
        <p style="color: #6b7280; margin-bottom: 24px;">Detaillierte Ansicht aller Felder</p>
        
        <?php
        require_once __DIR__ . '/../config/database.php';
        
        try {
            $pdo = getDBConnection();
            
            // Template laden
            $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = 22");
            $stmt->execute();
            $template = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$template) {
                echo '<div class="status-badge error">❌ Template ID 22 nicht gefunden!</div>';
                exit;
            }
            
            // Customer Freebie prüfen
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE template_id = 22 LIMIT 1");
            $stmt->execute();
            $customer_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
            
            function displayField($label, $value) {
                $displayValue = '';
                $class = '';
                
                if ($value === null) {
                    $displayValue = 'NULL';
                    $class = 'null';
                } elseif ($value === '') {
                    $displayValue = '(leer)';
                    $class = 'empty';
                } else {
                    $displayValue = '<pre>' . htmlspecialchars($value) . '</pre>';
                }
                
                echo '<div class="field">';
                echo '<div class="field-label">' . htmlspecialchars($label) . '</div>';
                echo '<div class="field-value ' . $class . '">' . $displayValue . '</div>';
                echo '</div>';
            }
        ?>
        
        <div class="status-badge ok">✅ Template gefunden</div>
        
        <?php if ($customer_freebie): ?>
            <div class="status-badge warning">⚠️ Customer Freebie existiert bereits (wird bevorzugt)</div>
        <?php else: ?>
            <div class="status-badge ok">ℹ️  Kein Customer Freebie vorhanden (Template-Daten werden verwendet)</div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Grunddaten -->
            <div class="section">
                <h2>📋 Grunddaten</h2>
                <?php
                displayField('ID', $template['id']);
                displayField('Name', $template['name']);
                displayField('URL Slug', $template['url_slug']);
                displayField('Nische', $template['niche']);
                displayField('Kurs ID', $template['course_id']);
                displayField('Layout', $template['layout']);
                ?>
            </div>
            
            <!-- Texte -->
            <div class="section">
                <h2>✍️ Texte</h2>
                <?php
                displayField('Pre-Headline', $template['preheadline']);
                displayField('Headline', $template['headline']);
                displayField('Subheadline', $template['subheadline']);
                displayField('Bullet Points', $template['bullet_points']);
                displayField('CTA Text', $template['cta_text']);
                ?>
            </div>
            
            <!-- Farben -->
            <div class="section">
                <h2>🎨 Farben</h2>
                <?php
                displayField('Primärfarbe', $template['primary_color']);
                displayField('Sekundärfarbe', $template['secondary_color']);
                displayField('Hintergrundfarbe', $template['background_color']);
                displayField('Textfarbe', $template['text_color']);
                displayField('Button-Farbe', $template['cta_button_color']);
                ?>
            </div>
            
            <!-- Schriftarten -->
            <div class="section">
                <h2>📝 Schriftarten & Größen</h2>
                <?php
                displayField('Headline Font', $template['headline_font']);
                displayField('Headline Size', $template['headline_size']);
                displayField('Preheadline Font', $template['preheadline_font']);
                displayField('Preheadline Size', $template['preheadline_size']);
                displayField('Subheadline Font', $template['subheadline_font']);
                displayField('Subheadline Size', $template['subheadline_size']);
                displayField('Bulletpoints Font', $template['bulletpoints_font']);
                displayField('Bulletpoints Size', $template['bulletpoints_size']);
                displayField('Body Font', $template['body_font']);
                displayField('Body Size', $template['body_size']);
                ?>
            </div>
            
            <!-- Mockup & Medien -->
            <div class="section">
                <h2>🖼️ Mockup & Medien</h2>
                <?php
                displayField('Mockup URL', $template['mockup_image_url']);
                displayField('Mockup anzeigen', $template['show_mockup'] ? 'Ja' : 'Nein');
                
                if (!empty($template['mockup_image_url'])) {
                    echo '<div class="field">';
                    echo '<div class="field-label">Mockup Vorschau</div>';
                    echo '<div class="field-value">';
                    echo '<img src="' . htmlspecialchars($template['mockup_image_url']) . '" style="max-width: 100%; height: auto; border-radius: 8px;" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';">';
                    echo '<div style="display: none; color: #ef4444; font-weight: 600;">❌ Bild konnte nicht geladen werden</div>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Custom Code -->
            <div class="section">
                <h2>💻 Custom Code</h2>
                <?php
                displayField('Custom Raw Code', $template['raw_code']);
                displayField('Custom CSS', $template['custom_css']);
                displayField('Pixel Code', $template['pixel_code']);
                ?>
            </div>
        </div>
        
        <div style="margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="/admin/dashboard.php?page=freebie-edit&id=22" class="btn">
                ✏️ Template bearbeiten
            </a>
            <a href="/customer/freebie-editor.php?template_id=22" class="btn">
                👁️ Customer Editor anzeigen
            </a>
            <a href="/migrations/add_font_columns.html" class="btn">
                🔧 Font-Spalten Migration
            </a>
        </div>
        
        <?php
        } catch (Exception $e) {
            echo '<div class="status-badge error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</body>
</html>