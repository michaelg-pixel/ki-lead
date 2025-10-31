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
                    <button onclick="previewTemplate(<?php echo htmlspecialchars(json_encode($freebie)); ?>)" 
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
    <div style="background: white; border-radius: 12px; width: 90%; max-width: 1200px; max-height: 90vh; overflow-y: auto; position: relative;">
        <div style="position: sticky; top: 0; background: white; border-bottom: 1px solid #e5e7eb; padding: 20px; display: flex; justify-content: space-between; align-items: center; z-index: 10;">
            <h3 style="color: #1f2937; font-size: 20px; margin: 0;">Template Vorschau</h3>
            <button onclick="closePreview()" style="padding: 8px 16px; background: #f3f4f6; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; transition: background 0.2s;">
                ✕ Schließen
            </button>
        </div>
        <div id="previewContent" style="padding: 40px;">
            <!-- Preview wird hier geladen -->
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
</style>

<script>
// VORSCHAU-FUNKTION
function previewTemplate(template) {
    // Layout-Mapping zurück
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
    const showMockup = data.show_mockup === '1';
    const mockupUrl = data.mockup_image_url || '';
    
    // Bulletpoints verarbeiten
    let bulletpointsHTML = '';
    if (data.bulletpoints) {
        const bullets = data.bulletpoints.split('\n').filter(b => b.trim());
        bulletpointsHTML = bullets.map(bullet => 
            `<div style="display: flex; align-items: start; gap: 12px; margin-bottom: 16px;">
                <span style="color: ${primaryColor}; font-size: 20px; flex-shrink: 0;">✓</span>
                <span style="color: #374151; font-size: 16px;">${escapeHtml(bullet.replace(/^[✓✔︎•-]\s*/, ''))}</span>
            </div>`
        ).join('');
    }
    
    // Layout-spezifisches HTML
    let layoutHTML = '';
    
    if (layout === 'hybrid' || layout === 'sidebar') {
        // Zwei-Spalten Layout
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
                    ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">${escapeHtml(data.preheadline)}</div>` : ''}
                    
                    <h1 style="font-size: 48px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">
                        ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                    </h1>
                    
                    ${data.subheadline ? `<p style="font-size: 20px; color: #6b7280; margin-bottom: 32px;">${escapeHtml(data.subheadline)}</p>` : ''}
                    
                    ${bulletpointsHTML ? `<div style="margin-bottom: 32px;">${bulletpointsHTML}</div>` : ''}
                    
                    <button style="background: ${primaryColor}; color: white; padding: 16px 40px; border: none; border-radius: 8px; font-size: 18px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px ${primaryColor}40;">
                        ${escapeHtml(data.cta_button_text || 'Jetzt kostenlos sichern')}
                    </button>
                </div>
            </div>
        `;
    } else {
        // Zentriertes Layout
        layoutHTML = `
            <div style="max-width: 800px; margin: 0 auto; text-align: center;">
                ${data.preheadline ? `<div style="color: ${primaryColor}; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;">${escapeHtml(data.preheadline)}</div>` : ''}
                
                <h1 style="font-size: 56px; font-weight: 800; color: #1f2937; line-height: 1.1; margin-bottom: 20px;">
                    ${escapeHtml(data.headline || 'Dein kostenloser Kurs')}
                </h1>
                
                ${data.subheadline ? `<p style="font-size: 22px; color: #6b7280; margin-bottom: 40px;">${escapeHtml(data.subheadline)}</p>` : ''}
                
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
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Modal bei ESC schließen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('previewModal');
            if (modal && modal.style.display === 'flex') {
                closePreview();
            }
        }
    });
    
    // Modal bei Klick außerhalb schließen
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    }
});

// LÖSCHEN-FUNKTION
async function deleteTemplate(templateId, templateName) {
    // Bestätigung
    if (!confirm(`Möchten Sie das Template "${templateName}" wirklich löschen?\n\n⚠️ Diese Aktion kann nicht rückgängig gemacht werden!`)) {
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