<?php
// Freebie Templates aus Datenbank holen
$freebies = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="section">
    <div class="section-header">
        <h3 class="section-title">Freebie Templates</h3>
        <a href="?page=freebie-create" class="btn">+ Neues Template</a>
    </div>
    
    <?php if (count($freebies) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin-top: 24px;">
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
<div id="previewModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 1400px; max-height: 90vh; overflow-y: auto; position: relative;">
        <div style="position: sticky; top: 0; background: white; border-bottom: 1px solid #e5e7eb; padding: 20px; display: flex; justify-content: space-between; align-items: center; z-index: 10;">
            <h3 style="color: #1f2937; font-size: 20px; margin: 0;">Template Vorschau</h3>
            <button onclick="closePreview()" style="padding: 8px 16px; background: #f3f4f6; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                ✕ Schließen
            </button>
        </div>
        <div id="previewContent" style="padding: 40px; position: relative;">
            <!-- Preview wird hier geladen -->
        </div>
    </div>
</div>

<!-- COOKIE EINSTELLUNGEN MODAL -->
<div id="cookieSettingsModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10001; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
        <div style="padding: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h3 style="font-size: 24px; font-weight: 700; color: #1f2937; margin: 0;">Cookie-Einstellungen</h3>
                <button onclick="closeCookieSettings()" style="width: 32px; height: 32px; border-radius: 50%; background: #f3f4f6; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #6b7280;">×</button>
            </div>
            
            <p style="color: #6b7280; margin-bottom: 32px; line-height: 1.6;">Wir verwenden Cookies und ähnliche Technologien, um Ihnen ein optimales Erlebnis zu bieten. Wählen Sie, welche Cookies Sie zulassen möchten.</p>
            
            <div style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 16px;">
                    <div>
                        <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Notwendige Cookies</h4>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Erforderlich für die Grundfunktionen der Website</p>
                    </div>
                    <div style="padding: 8px 16px; background: #e5e7eb; border-radius: 6px; font-size: 12px; font-weight: 600; color: #6b7280;">Immer aktiv</div>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f9fafb; border-radius: 8px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Analyse & Statistik</h4>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Helfen uns, die Nutzung zu verstehen und zu verbessern</p>
                    </div>
                    <label style="position: relative; display: inline-block; width: 48px; height: 24px; cursor: pointer;">
                        <input type="checkbox" id="analyticsCookie" style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e5e7eb; transition: .3s; border-radius: 24px;"></span>
                        <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%;"></span>
                    </label>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <div style="flex: 1;">
                        <h4 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 4px 0;">Marketing</h4>
                        <p style="font-size: 14px; color: #6b7280; margin: 0;">Ermöglichen personalisierte Werbung und Inhalte</p>
                    </div>
                    <label style="position: relative; display: inline-block; width: 48px; height: 24px; cursor: pointer;">
                        <input type="checkbox" id="marketingCookie" style="opacity: 0; width: 0; height: 0;">
                        <span style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e5e7eb; transition: .3s; border-radius: 24px;"></span>
                        <span style="position: absolute; content: ''; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%;"></span>
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button onclick="saveSelectedCookies()" style="flex: 1; padding: 14px; background: var(--primary-color, #7C3AED); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                    Auswahl speichern
                </button>
                <button onclick="acceptAllCookiesFromSettings()" style="flex: 1; padding: 14px; background: transparent; color: var(--primary-color, #7C3AED); border: 2px solid var(--primary-color, #7C3AED); border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;">
                    Alle akzeptieren
                </button>
            </div>
        </div>
    </div>
</div>

<style>
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
    }
    
    .freebie-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
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
    
    /* Toggle Switch Styles */
    input[type="checkbox"]:checked + span {
        background-color: var(--primary-color, #7C3AED);
    }
    
    input[type="checkbox"]:checked + span + span {
        transform: translateX(24px);
    }
</style>

<script>
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
        show_mockup: template.mockup_image_url ? '1' : '0'
    };
    
    document.documentElement.style.setProperty('--primary-color', data.primary_color);
    
    const previewHTML = generatePreviewHTML(data);
    const modal = document.getElementById('previewModal');
    const content = document.getElementById('previewContent');
    
    content.innerHTML = previewHTML;
    modal.style.display = 'flex';
}

function closePreview() {
    document.getElementById('previewModal').style.display = 'none';
}

function generatePreviewHTML(data) {
    const layout = data.layout || 'hybrid';
    const bgColor = data.background_color || '#FFFFFF';
    const primaryColor = data.primary_color || '#7C3AED';
    const showMockup = data.show_mockup === '1';
    const mockupUrl = data.mockup_image_url || '';
    
    let bulletpointsHTML = '';
    if (data.bulletpoints) {
        const bullets = data.bulletpoints.split('\n').filter(b => b.trim());
        bulletpointsHTML = bullets.map(bullet => {
            return '<div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">' +
                '<span style="color: ' + primaryColor + '; font-size: 20px; flex-shrink: 0;">✓</span>' +
                '<span style="color: #374151; font-size: 16px; line-height: 1.5;">' + escapeHtml(bullet.replace(/^[✓✔︎•-]\s*/, '')) + '</span>' +
                '</div>';
        }).join('');
    }
    
    // Kleineres Mockup (max 380px statt volle Breite)
    const mockupHTML = showMockup && mockupUrl ? 
        '<img src="' + escapeHtml(mockupUrl) + '" alt="Mockup" style="width: 100%; max-width: 380px; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15);">' :
        '<div style="width: 100%; max-width: 380px; aspect-ratio: 3/4; background: linear-gradient(135deg, ' + primaryColor + '20, ' + primaryColor + '40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ' + primaryColor + '; font-size: 64px;">🎁</div>';
    
    const preheadlineHTML = data.preheadline ? 
        '<div style="color: ' + primaryColor + '; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">' + escapeHtml(data.preheadline) + '</div>' : '';
    
    const headlineHTML = '<h1 style="font-size: 48px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">' +
        escapeHtml(data.headline || 'Dein kostenloser Kurs') + '</h1>';
    
    const subheadlineHTML = data.subheadline ? 
        '<p style="font-size: 20px; color: #6b7280; margin-bottom: 32px; line-height: 1.6;">' + escapeHtml(data.subheadline) + '</p>' : '';
    
    const bulletpointsWrapHTML = bulletpointsHTML ? 
        '<div style="margin-bottom: 32px;">' + bulletpointsHTML + '</div>' : '';
    
    const ctaHTML = '<button style="background: ' + primaryColor + '; color: white; padding: 16px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ' + primaryColor + '40; transition: transform 0.2s;">' +
        escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern') + '</button>';
    
    let layoutHTML = '';
    
    if (layout === 'hybrid') {
        // Hybrid: 40% Bild, 60% Text
        layoutHTML = '<div style="display: grid; grid-template-columns: 2fr 3fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">' +
            '<div style="display: flex; justify-content: center;">' + mockupHTML + '</div>' +
            '<div>' + preheadlineHTML + headlineHTML + subheadlineHTML + bulletpointsWrapHTML + ctaHTML + '</div>' +
            '</div>';
    } else if (layout === 'sidebar') {
        // Sidebar: 60% Text, 40% Bild
        layoutHTML = '<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 60px; align-items: center; max-width: 1200px; margin: 0 auto;">' +
            '<div>' + preheadlineHTML + headlineHTML + subheadlineHTML + bulletpointsWrapHTML + ctaHTML + '</div>' +
            '<div style="display: flex; justify-content: center;">' + mockupHTML + '</div>' +
            '</div>';
    } else {
        // Centered Layout
        const mockupCenteredHTML = showMockup && mockupUrl ?
            '<img src="' + escapeHtml(mockupUrl) + '" alt="Mockup" style="width: 100%; max-width: 380px; height: auto; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.15); margin: 0 auto 40px;">' :
            '<div style="width: 100%; max-width: 380px; aspect-ratio: 3/4; background: linear-gradient(135deg, ' + primaryColor + '20, ' + primaryColor + '40); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: ' + primaryColor + '; font-size: 64px; margin: 0 auto 40px;">🎁</div>';
        
        const bulletpointsCenteredHTML = bulletpointsHTML ?
            '<div style="text-align: left; max-width: 500px; margin: 0 auto 40px;">' + bulletpointsHTML + '</div>' : '';
        
        const ctaCenteredHTML = '<button style="background: ' + primaryColor + '; color: white; padding: 18px 48px; border: none; border-radius: 8px; font-size: 20px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ' + primaryColor + '40; transition: transform 0.2s;">' +
            escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern') + '</button>';
        
        layoutHTML = '<div style="max-width: 800px; margin: 0 auto; text-align: center;">' +
            preheadlineHTML +
            '<h1 style="font-size: 56px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">' + escapeHtml(data.headline || 'Dein kostenloser Kurs') + '</h1>' +
            (data.subheadline ? '<p style="font-size: 22px; color: #6b7280; margin-bottom: 40px; line-height: 1.6;">' + escapeHtml(data.subheadline) + '</p>' : '') +
            mockupCenteredHTML +
            bulletpointsCenteredHTML +
            ctaCenteredHTML +
            '</div>';
    }
    
    // Moderner Cookie Banner
    const cookieBanner = '<div id="cookieBanner" style="position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%); max-width: 600px; width: calc(100% - 48px); background: white; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 24px; z-index: 1000; border-radius: 16px; border: 1px solid #e5e7eb;">' +
        '<div style="display: flex; align-items: start; gap: 16px; margin-bottom: 20px;">' +
        '<div style="width: 48px; height: 48px; background: linear-gradient(135deg, ' + primaryColor + ', ' + primaryColor + 'dd); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">' +
        '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="white" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>' +
        '</div>' +
        '<div style="flex: 1;">' +
        '<h3 style="font-size: 16px; font-weight: 700; color: #1f2937; margin: 0 0 8px 0;">🍪 Cookies & Datenschutz</h3>' +
        '<p style="font-size: 14px; color: #6b7280; line-height: 1.5; margin: 0;">Wir nutzen Cookies für ein besseres Erlebnis. Mit "Akzeptieren" stimmst du der Nutzung zu. <a href="#" style="color: ' + primaryColor + '; text-decoration: underline;">Mehr Info</a></p>' +
        '</div>' +
        '</div>' +
        '<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;">' +
        '<button onclick="showCookieSettings()" style="padding: 10px 16px; background: white; color: #6b7280; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">⚙️ Einstellungen</button>' +
        '<button onclick="declineCookies()" style="padding: 10px 16px; background: #f3f4f6; color: #6b7280; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">Ablehnen</button>' +
        '<button onclick="acceptCookies()" style="padding: 10px 16px; background: ' + primaryColor + '; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 8px ' + primaryColor + '40;">Akzeptieren</button>' +
        '</div>' +
        '</div>';
    
    return '<div style="background: ' + bgColor + '; padding: 80px 40px; min-height: 600px; border-radius: 8px; position: relative;">' +
        layoutHTML +
        '</div>' +
        cookieBanner +
        '<style>' +
        '#cookieBanner button:hover { opacity: 0.9; transform: translateY(-1px); }' +
        '@media (max-width: 768px) {' +
        '#cookieBanner { bottom: 16px; width: calc(100% - 32px); padding: 20px; }' +
        '#cookieBanner > div:last-child { grid-template-columns: 1fr; }' +
        '}' +
        '</style>';
}

function acceptCookies() {
    document.getElementById('cookieBanner').style.display = 'none';
    console.log('Alle Cookies akzeptiert');
}

function declineCookies() {
    document.getElementById('cookieBanner').style.display = 'none';
    console.log('Nur notwendige Cookies');
}

function showCookieSettings() {
    document.getElementById('cookieSettingsModal').style.display = 'flex';
}

function closeCookieSettings() {
    document.getElementById('cookieSettingsModal').style.display = 'none';
}

function saveSelectedCookies() {
    const analytics = document.getElementById('analyticsCookie').checked;
    const marketing = document.getElementById('marketingCookie').checked;
    console.log('Cookies gespeichert:', { analytics, marketing });
    document.getElementById('cookieSettingsModal').style.display = 'none';
    document.getElementById('cookieBanner').style.display = 'none';
}

function acceptAllCookiesFromSettings() {
    document.getElementById('analyticsCookie').checked = true;
    document.getElementById('marketingCookie').checked = true;
    console.log('Alle Cookies aus Einstellungen akzeptiert');
    document.getElementById('cookieSettingsModal').style.display = 'none';
    document.getElementById('cookieBanner').style.display = 'none';
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
            const settingsModal = document.getElementById('cookieSettingsModal');
            if (settingsModal && settingsModal.style.display === 'flex') {
                closeCookieSettings();
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
    
    const settingsModal = document.getElementById('cookieSettingsModal');
    if (settingsModal) {
        settingsModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCookieSettings();
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