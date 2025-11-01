<?php
// Font-Konfiguration laden
$fontConfig = require __DIR__ . '/../../config/fonts.php';

// Freebie Templates aus Datenbank holen
$freebies = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Domain für vollständige URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
?>

<!-- Google Fonts laden -->
<link href="<?php echo $fontConfig['google_fonts_url']; ?>" rel="stylesheet">

<div class="section">
    <div class="section-header">
        <h3 class="section-title">Freebie Templates</h3>
        <a href="?page=freebie-create" class="btn">+ Neues Template</a>
    </div>
    
    <?php if (count($freebies) > 0): ?>
    <div class="freebies-grid">
        <?php foreach ($freebies as $freebie): ?>
        <div class="freebie-card">
            <div class="freebie-mockup">
                <?php if (!empty($freebie['mockup_image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" alt="Mockup">
                <?php else: ?>
                    <div class="no-mockup">📱</div>
                <?php endif; ?>
            </div>
            <div class="freebie-content">
                <h4><?php echo htmlspecialchars($freebie['name']); ?></h4>
                <p><?php echo htmlspecialchars($freebie['headline'] ?? 'Keine Headline'); ?></p>
                
                <div class="freebie-meta">
                    <span class="badge badge-admin">Master Template</span>
                    <span style="color: #888; font-size: 12px;">
                        <?php echo date('d.m.Y', strtotime($freebie['created_at'])); ?>
                    </span>
                </div>
                
                <!-- TRACKING STATS -->
                <div class="tracking-stats">
                    <div class="stat">
                        <span class="stat-icon">👁️</span>
                        <span class="stat-value"><?php echo $freebie['freebie_clicks'] ?? 0; ?></span>
                        <span class="stat-label">Freebie-Klicks</span>
                    </div>
                    <div class="stat">
                        <span class="stat-icon">✓</span>
                        <span class="stat-value"><?php echo $freebie['thank_you_clicks'] ?? 0; ?></span>
                        <span class="stat-label">Danke-Klicks</span>
                    </div>
                </div>
                
                <!-- FREEBIE LINK -->
                <div class="link-section">
                    <div class="link-header">
                        <span class="link-icon">🔗</span>
                        <span>Freebie-Link</span>
                    </div>
                    <?php 
                    $freebieLink = $freebie['public_link'] ?? '/freebie/view.php?id=' . $freebie['id'];
                    $fullFreebieLink = $protocol . '://' . $domain . $freebieLink;
                    ?>
                    
                    <div class="link-item">
                        <input type="text" readonly value="<?php echo htmlspecialchars($fullFreebieLink); ?>" class="link-input" id="freebie-<?php echo $freebie['id']; ?>">
                        <button onclick="copyLink('freebie-<?php echo $freebie['id']; ?>')" class="btn-copy" title="Link kopieren">📋</button>
                    </div>
                </div>
                
                <!-- DANKE-SEITE LINK -->
                <div class="link-section">
                    <div class="link-header">
                        <span class="link-icon">🎉</span>
                        <span>Danke-Seite</span>
                    </div>
                    <?php 
                    $thankYouLink = $freebie['thank_you_link'] ?? '/freebie/thankyou.php?id=' . $freebie['id'];
                    $fullThankYouLink = $protocol . '://' . $domain . $thankYouLink;
                    ?>
                    
                    <div class="link-item">
                        <input type="text" readonly value="<?php echo htmlspecialchars($fullThankYouLink); ?>" class="link-input" id="thankyou-<?php echo $freebie['id']; ?>">
                        <button onclick="copyLink('thankyou-<?php echo $freebie['id']; ?>')" class="btn-copy" title="Link kopieren">📋</button>
                    </div>
                </div>
                
                <div class="freebie-actions">
                    <button onclick='previewTemplate(<?php echo json_encode($freebie); ?>)' 
                            class="btn-preview" 
                            title="Vorschau öffnen">
                        👁️ Vorschau
                    </button>
                    <a href="?page=freebie-edit&id=<?php echo $freebie['id']; ?>" class="btn-secondary">Bearbeiten</a>
                    <button onclick="deleteTemplate(<?php echo $freebie['id']; ?>, '<?php echo htmlspecialchars($freebie['name'], ENT_QUOTES); ?>')" class="btn-danger">Löschen</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-state-icon">🎁</div>
        <p>Noch keine Freebie Templates erstellt</p>
        <a href="?page=freebie-create" class="btn" style="margin-top: 16px;">Erstes Template erstellen</a>
    </div>
    <?php endif; ?>
</div>

<!-- VORSCHAU MODAL -->
<div id="previewModal" class="preview-modal">
    <div class="preview-container">
        <div class="preview-header">
            <h3>Template Vorschau</h3>
            <button onclick="closePreview()" class="preview-close">✕ Schließen</button>
        </div>
        <div id="previewContent" class="preview-body">
            <!-- Preview wird hier geladen -->
        </div>
    </div>
</div>

<style>
    /* Grid für Freebie Cards */
    .freebies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }
    
    .freebie-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s;
    }
    
    .freebie-card:hover {
        transform: translateY(-4px);
    }
    
    .freebie-mockup {
        width: 100%;
        height: 200px;
        background: #0f0f1e;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .freebie-mockup img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .no-mockup {
        font-size: 48px;
        opacity: 0.3;
    }
    
    .freebie-content {
        padding: 20px;
    }
    
    .freebie-content h4 {
        color: white;
        font-size: 18px;
        margin-bottom: 8px;
    }
    
    .freebie-content p {
        color: #888;
        font-size: 14px;
        margin-bottom: 16px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .freebie-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    /* TRACKING STATS */
    .tracking-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }
    
    .stat {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        padding: 12px;
        text-align: center;
    }
    
    .stat-icon {
        font-size: 20px;
        display: block;
        margin-bottom: 4px;
    }
    
    .stat-value {
        display: block;
        color: white;
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    
    .stat-label {
        display: block;
        color: #888;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* LINK SECTIONS */
    .link-section {
        background: rgba(102, 126, 234, 0.05);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 12px;
    }
    
    .link-header {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #667eea;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .link-icon {
        font-size: 16px;
    }
    
    .link-item {
        display: flex;
        gap: 8px;
    }
    
    .link-input {
        flex: 1;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 6px;
        color: white;
        padding: 8px 12px;
        font-size: 12px;
        font-family: 'Courier New', monospace;
        min-width: 0;
    }
    
    .btn-copy {
        padding: 8px 12px;
        background: rgba(102, 126, 234, 0.3);
        border: 1px solid #667eea;
        border-radius: 6px;
        color: white;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    
    .btn-copy:hover {
        background: rgba(102, 126, 234, 0.5);
    }
    
    .freebie-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 16px;
    }
    
    .btn-preview {
        grid-column: 1 / -1;
        padding: 10px 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        cursor: pointer;
    }
    
    .btn-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        padding: 8px 16px;
        background: rgba(102, 126, 234, 0.2);
        color: #667eea;
        border: 1px solid #667eea;
        border-radius: 8px;
        text-align: center;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        background: rgba(102, 126, 234, 0.3);
    }
    
    .btn-danger {
        padding: 8px 16px;
        background: rgba(255, 107, 107, 0.1);
        color: #ff6b6b;
        border: 1px solid #ff6b6b;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
    }
    
    .btn-danger:hover {
        background: rgba(255, 107, 107, 0.2);
    }
    
    /* PREVIEW MODAL */
    .preview-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .preview-container {
        background: white;
        border-radius: 12px;
        width: 100%;
        max-width: 1400px;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
    }
    
    .preview-header {
        position: sticky;
        top: 0;
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 10;
    }
    
    .preview-header h3 {
        color: #1f2937;
        font-size: 20px;
        margin: 0;
    }
    
    .preview-close {
        padding: 8px 16px;
        background: #f3f4f6;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.2s;
    }
    
    .preview-close:hover {
        background: #e5e7eb;
    }
    
    .preview-body {
        padding: 40px;
        position: relative;
    }
    
    /* ============================================
       RESPONSIVE DESIGN - MOBILE OPTIMIERUNG
       ============================================ */
    
    /* Tablets */
    @media (max-width: 1024px) {
        .freebies-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
    }
    
    /* Mobile Geräte */
    @media (max-width: 768px) {
        .freebies-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .freebie-card {
            border-radius: 12px;
        }
        
        .freebie-mockup {
            height: 180px;
        }
        
        .freebie-content {
            padding: 16px;
        }
        
        .freebie-content h4 {
            font-size: 16px;
        }
        
        .freebie-content p {
            font-size: 13px;
        }
        
        /* Tracking Stats anpassen */
        .tracking-stats {
            gap: 8px;
        }
        
        .stat {
            padding: 10px 8px;
        }
        
        .stat-value {
            font-size: 20px;
        }
        
        .stat-label {
            font-size: 10px;
        }
        
        /* Link Sections */
        .link-section {
            padding: 10px;
        }
        
        .link-header {
            font-size: 12px;
        }
        
        .link-input {
            font-size: 11px;
            padding: 6px 10px;
        }
        
        .btn-copy {
            padding: 6px 10px;
            font-size: 12px;
        }
        
        /* Actions */
        .freebie-actions {
            gap: 6px;
        }
        
        .btn-preview,
        .btn-secondary,
        .btn-danger {
            padding: 8px 12px;
            font-size: 13px;
        }
        
        /* Preview Modal */
        .preview-modal {
            padding: 10px;
        }
        
        .preview-container {
            max-height: 95vh;
        }
        
        .preview-header {
            padding: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .preview-header h3 {
            font-size: 18px;
        }
        
        .preview-close {
            order: 1;
            width: 100%;
            padding: 10px;
        }
        
        .preview-body {
            padding: 20px 16px;
        }
    }
    
    /* Sehr kleine Mobile Geräte */
    @media (max-width: 480px) {
        .freebie-mockup {
            height: 160px;
        }
        
        .freebie-content {
            padding: 12px;
        }
        
        .freebie-content h4 {
            font-size: 15px;
        }
        
        .freebie-meta {
            font-size: 11px;
        }
        
        .badge {
            font-size: 10px;
            padding: 4px 8px;
        }
        
        .tracking-stats {
            grid-template-columns: 1fr;
        }
        
        .stat {
            padding: 8px;
        }
        
        .link-input {
            font-size: 10px;
        }
        
        .freebie-actions {
            grid-template-columns: 1fr;
        }
        
        .btn-secondary,
        .btn-danger {
            grid-column: 1;
        }
        
        .preview-body {
            padding: 16px 12px;
        }
    }
    
    /* Landscape Modus */
    @media (max-height: 600px) and (max-width: 768px) {
        .preview-container {
            max-height: 98vh;
        }
        
        .preview-header {
            padding: 12px 16px;
        }
        
        .preview-body {
            padding: 20px 16px;
        }
    }
</style>

<script>
// Link kopieren
function copyLink(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        
        // Button-Feedback
        const button = input.nextElementSibling;
        const originalText = button.innerHTML;
        button.innerHTML = '✓';
        button.style.background = 'rgba(34, 197, 94, 0.3)';
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.style.background = '';
        }, 2000);
    } catch (err) {
        alert('Fehler beim Kopieren');
    }
}

function previewTemplate(template) {
    const layoutMapping = {
        'layout1': 'hybrid',
        'layout2': 'centered',
        'layout3': 'sidebar'
    };
    
    const data = {
        name: template.name,
        headline: template.headline,
        subheadline: template.subheadline || '',
        preheadline: template.preheadline || '',
        bulletpoints: template.bullet_points || '',
        cta_button_text: template.cta_text || 'Jetzt kostenlos sichern',
        layout: layoutMapping[template.layout] || 'hybrid',
        background_color: template.background_color || '#FFFFFF',
        primary_color: template.primary_color || '#7C3AED',
        mockup_image_url: template.mockup_image_url || '',
        show_mockup: template.mockup_image_url ? '1' : '0',
        
        // Font-Einstellungen
        preheadline_font: template.preheadline_font || 'Poppins',
        preheadline_size: template.preheadline_size || 14,
        headline_font: template.headline_font || 'Poppins',
        headline_size: template.headline_size || 48,
        subheadline_font: template.subheadline_font || 'Poppins',
        subheadline_size: template.subheadline_size || 20,
        bulletpoints_font: template.bulletpoints_font || 'Poppins',
        bulletpoints_size: template.bulletpoints_size || 16,
    };
    
    document.documentElement.style.setProperty('--primary-color', data.primary_color);
    
    const previewHTML = generatePreviewHTML(data);
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    
    content.innerHTML = previewHTML;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
    document.body.style.overflow = '';
}

function generatePreviewHTML(data) {
    const layout = data.layout || 'hybrid';
    const bgColor = data.background_color || '#FFFFFF';
    const primaryColor = data.primary_color || '#7C3AED';
    const showMockup = data.show_mockup === '1';
    const mockupUrl = data.mockup_image_url || '';
    
    const preheadlineFont = data.preheadline_font || 'Poppins';
    const preheadlineSize = data.preheadline_size || 14;
    const headlineFont = data.headline_font || 'Poppins';
    const headlineSize = data.headline_size || 48;
    const subheadlineFont = data.subheadline_font || 'Poppins';
    const subheadlineSize = data.subheadline_size || 20;
    const bulletpointsFont = data.bulletpoints_font || 'Poppins';
    const bulletpointsSize = data.bulletpoints_size || 16;
    
    let bulletpointsHTML = '';
    if (data.bulletpoints) {
        const bullets = data.bulletpoints.split('\n').filter(b => b.trim());
        bulletpointsHTML = bullets.map(bullet => {
            return '<div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">' +
                '<span style="color: ' + primaryColor + '; font-size: 20px; flex-shrink: 0;">✓</span>' +
                '<span style="color: #374151; font-size: ' + bulletpointsSize + 'px; font-family: \'' + bulletpointsFont + '\', sans-serif; line-height: 1.5;">' + escapeHtml(bullet.replace(/^[✓✔︎•-]\s*/, '')) + '</span>' +
                '</div>';
        }).join('');
    }
    
    const mockupHTML = showMockup && mockupUrl ? 
        '<img src="' + escapeHtml(mockupUrl) + '" alt="Mockup" style="width: 100%; max-width: 380px; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">' :
        '<div style="width: 100%; max-width: 380px; aspect-ratio: 3/4; background: linear-gradient(135deg, ' + primaryColor + '20, ' + primaryColor + '40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ' + primaryColor + '; font-size: 64px;">🎁</div>';
    
    const preheadlineHTML = data.preheadline ? 
        '<div style="color: ' + primaryColor + '; font-size: ' + preheadlineSize + 'px; font-family: \'' + preheadlineFont + '\', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; text-align: center;">' + escapeHtml(data.preheadline) + '</div>' : '';
    
    const headlineHTML = '<h1 style="font-size: ' + headlineSize + 'px; font-family: \'' + headlineFont + '\', sans-serif; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px; text-align: center;">' +
        escapeHtml(data.headline || 'Dein kostenloser Kurs') + '</h1>';
    
    const subheadlineHTML = data.subheadline ? 
        '<p style="font-size: ' + subheadlineSize + 'px; font-family: \'' + subheadlineFont + '\', sans-serif; color: #6b7280; margin-bottom: 32px; line-height: 1.6; text-align: center;">' + escapeHtml(data.subheadline) + '</p>' : '';
    
    const bulletpointsWrapHTML = bulletpointsHTML ? 
        '<div style="margin-bottom: 32px;">' + bulletpointsHTML + '</div>' : '';
    
    const ctaHTML = '<div style="text-align: center;"><button style="background: ' + primaryColor + '; color: white; padding: 16px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ' + primaryColor + '40; transition: transform 0.2s;">' +
        escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern') + '</button></div>';
    
    let layoutHTML = '';
    
    if (layout === 'centered') {
        const mockupCenteredHTML = showMockup && mockupUrl ?
            '<img src="' + escapeHtml(mockupUrl) + '" alt="Mockup" style="width: 100%; max-width: 380px; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); margin: 0 auto 40px;">' :
            '<div style="width: 100%; max-width: 380px; aspect-ratio: 3/4; background: linear-gradient(135deg, ' + primaryColor + '20, ' + primaryColor + '40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ' + primaryColor + '; font-size: 64px; margin: 0 auto 40px;">🎁</div>';
        
        const bulletpointsCenteredHTML = bulletpointsHTML ?
            '<div style="text-align: left; max-width: 500px; margin: 0 auto 40px;">' + bulletpointsHTML + '</div>' : '';
        
        layoutHTML = '<div style="max-width: 800px; margin: 0 auto; text-align: center;">' +
            preheadlineHTML +
            headlineHTML +
            subheadlineHTML +
            mockupCenteredHTML +
            bulletpointsCenteredHTML +
            '<button style="background: ' + primaryColor + '; color: white; padding: 18px 48px; border: none; border-radius: 8px; font-size: 20px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ' + primaryColor + '40; transition: transform 0.2s;">' +
            escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern') + '</button>' +
            '</div>';
    } else if (layout === 'hybrid') {
        layoutHTML = '<div style="display: grid; grid-template-columns: 2fr 3fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">' +
            '<div style="display: flex; justify-content: center;">' + mockupHTML + '</div>' +
            '<div>' + preheadlineHTML + headlineHTML + subheadlineHTML + bulletpointsWrapHTML + ctaHTML + '</div>' +
            '</div>';
    } else {
        layoutHTML = '<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">' +
            '<div>' + preheadlineHTML + headlineHTML + subheadlineHTML + bulletpointsWrapHTML + ctaHTML + '</div>' +
            '<div style="display: flex; justify-content: center;">' + mockupHTML + '</div>' +
            '</div>';
    }
    
    return '<div style="background: ' + bgColor + '; padding: 80px 40px; min-height: 600px; border-radius: 8px; position: relative;">' +
        layoutHTML +
        '</div>';
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('previewModal');
            if (modal && modal.style.display === 'flex') {
                closePreview();
            }
        }
    });
    
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    }
});

async function deleteTemplate(templateId, templateName) {
    if (!confirm('Möchten Sie das Template "' + templateName + '" wirklich löschen?\n\n⚠️ Diese Aktion kann nicht rückgängig gemacht werden!')) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete-freebie.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: templateId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Template erfolgreich gelöscht!');
            window.location.href = '?page=freebies&deleted=1';
        } else {
            alert('❌ Fehler beim Löschen:\n\n' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('❌ Netzwerkfehler:\n\n' + error.message);
        console.error('Delete error:', error);
    }
}
</script>