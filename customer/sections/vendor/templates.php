<?php
/**
 * Vendor Dashboard - Templates Tab
 * Template-Verwaltung für Vendor
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}
?>

<style>
.vendor-templates {
    padding: 2rem;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.create-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.create-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

/* Templates Grid */
.templates-grid {
    display: grid;
    gap: 1.5rem;
}

.template-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s;
}

.template-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.4);
}

.template-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.template-info {
    flex: 1;
}

.template-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.5rem;
}

.template-category {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: rgba(102, 126, 234, 0.2);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: #a5b4fc;
}

.template-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.template-status.published {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.template-status.draft {
    background: rgba(156, 163, 175, 0.2);
    color: #9ca3af;
}

.template-description {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    margin: 1rem 0;
    line-height: 1.6;
}

.template-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin: 1rem 0;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 0.75rem;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary, #9ca3af);
    margin-top: 0.25rem;
}

.template-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.action-btn {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    background: rgba(102, 126, 234, 0.1);
    color: var(--text-primary, #ffffff);
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.action-btn:hover {
    background: rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.5);
}

.action-btn.danger {
    border-color: rgba(239, 68, 68, 0.3);
    background: rgba(239, 68, 68, 0.1);
}

.action-btn.danger:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(10px);
    z-index: 10000;
    overflow-y: auto;
    padding: 2rem;
}

.modal.active {
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.modal-content {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 1.5rem;
    width: 100%;
    max-width: 900px;
    margin: 2rem auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-header {
    padding: 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
}

.close-modal {
    background: rgba(239, 68, 68, 0.2);
    border: none;
    color: #ef4444;
    width: 36px;
    height: 36px;
    border-radius: 0.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.2s;
}

.close-modal:hover {
    background: rgba(239, 68, 68, 0.3);
}

.modal-body {
    padding: 2rem;
    max-height: calc(100vh - 16rem);
    overflow-y: auto;
}

.form-grid {
    display: grid;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--text-primary, #ffffff);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label .required {
    color: #ef4444;
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.875rem;
    transition: all 0.2s;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.1);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.form-help {
    font-size: 0.75rem;
    color: var(--text-secondary, #9ca3af);
}

.color-picker-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.color-preview {
    width: 50px;
    height: 50px;
    border-radius: 0.5rem;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: var(--text-primary, #ffffff);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
}

.loading {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary, #9ca3af);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary, #9ca3af);
}

.empty-state-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary, #ffffff);
}

@media (max-width: 768px) {
    .vendor-templates {
        padding: 1rem;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .modal {
        padding: 0;
    }
    
    .modal-content {
        border-radius: 0;
        margin: 0;
        min-height: 100vh;
    }
    
    .template-actions {
        flex-direction: column;
    }
}
</style>

<div class="vendor-templates">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            <span>🎁</span>
            <span>Meine Templates</span>
        </h1>
        <button class="create-btn" onclick="openTemplateModal()">
            <span>➕</span>
            <span>Neues Template</span>
        </button>
    </div>
    
    <!-- Templates List -->
    <div id="templatesContainer">
        <div class="loading">
            <div>Lade Templates...</div>
        </div>
    </div>
</div>

<!-- Template Editor Modal -->
<div id="templateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Neues Template</h2>
            <button class="close-modal" onclick="closeTemplateModal()">×</button>
        </div>
        
        <form id="templateForm" onsubmit="saveTemplate(event)">
            <input type="hidden" id="templateId" name="id">
            
            <div class="modal-body">
                <div class="form-grid">
                    <!-- Template Basic Info -->
                    <div class="form-group">
                        <label class="form-label">
                            Template Name <span class="required">*</span>
                        </label>
                        <input type="text" id="templateName" name="template_name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Beschreibung</label>
                        <textarea id="templateDescription" name="template_description" class="form-textarea"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Kategorie</label>
                            <select id="category" name="category" class="form-select">
                                <option value="">Wählen...</option>
                                <option value="leadmagnet">Leadmagnet</option>
                                <option value="video_course">Videokurs</option>
                                <option value="ebook">E-Book</option>
                                <option value="checklist">Checkliste</option>
                                <option value="template">Template/Vorlage</option>
                                <option value="tool">Tool/Software</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nische</label>
                            <input type="text" id="niche" name="niche" class="form-input" placeholder="z.B. Online Marketing">
                        </div>
                    </div>
                    
                    <!-- Reward Configuration -->
                    <div class="form-group">
                        <label class="form-label">
                            Belohnungstyp <span class="required">*</span>
                        </label>
                        <select id="rewardType" name="reward_type" class="form-select" required>
                            <option value="leadmagnet">Leadmagnet</option>
                            <option value="video_course">Videokurs</option>
                            <option value="ebook">E-Book</option>
                            <option value="discount">Rabatt</option>
                            <option value="consultation">Beratung</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Belohnungstitel <span class="required">*</span>
                        </label>
                        <input type="text" id="rewardTitle" name="reward_title" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Belohnungsbeschreibung</label>
                        <textarea id="rewardDescription" name="reward_description" class="form-textarea"></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Wert (optional)</label>
                            <input type="text" id="rewardValue" name="reward_value" class="form-input" placeholder="z.B. 97€">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Icon (Emoji)</label>
                            <input type="text" id="rewardIcon" name="reward_icon" class="form-input" placeholder="🎁">
                        </div>
                    </div>
                    
                    <!-- Color Picker -->
                    <div class="form-group">
                        <label class="form-label">Farbe</label>
                        <div class="color-picker-group">
                            <input type="color" id="rewardColor" name="reward_color" value="#667eea">
                            <input type="text" id="rewardColorText" class="form-input" value="#667eea" 
                                   onchange="document.getElementById('rewardColor').value = this.value">
                            <div id="colorPreview" class="color-preview" style="background: #667eea;"></div>
                        </div>
                    </div>
                    
                    <!-- Delivery Settings -->
                    <div class="form-group">
                        <label class="form-label">Auslieferungsart</label>
                        <select id="deliveryType" name="reward_delivery_type" class="form-select">
                            <option value="manual">Manuell</option>
                            <option value="download">Download-Link</option>
                            <option value="email">Per E-Mail</option>
                            <option value="redirect">Redirect</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Download-URL / Redirect-URL</label>
                        <input type="url" id="downloadUrl" name="reward_download_url" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Anweisungen</label>
                        <textarea id="instructions" name="reward_instructions" class="form-textarea" 
                                  placeholder="Anweisungen für die Einlösung..."></textarea>
                    </div>
                    
                    <!-- Product Info -->
                    <div class="form-group">
                        <label class="form-label">Kursdauer (optional)</label>
                        <input type="text" id="courseDuration" name="course_duration" class="form-input" placeholder="z.B. 4 Wochen">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mockup-URL</label>
                        <input type="url" id="mockupUrl" name="product_mockup_url" class="form-input">
                        <span class="form-help">URL zum Produktbild/Mockup</span>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Original Produkt-Link</label>
                        <input type="url" id="originalProductLink" name="original_product_link" class="form-input">
                        <span class="form-help">Link zu deinem Originalprodukt</span>
                    </div>
                    
                    <!-- Marketplace Settings -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Empfohlene Stufe</label>
                            <input type="number" id="tierLevel" name="suggested_tier_level" class="form-input" 
                                   min="1" max="10" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Empf. Anzahl</label>
                            <input type="number" id="referralsRequired" name="suggested_referrals_required" 
                                   class="form-input" min="1" value="3">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Preis (€)</label>
                            <input type="number" id="price" name="marketplace_price" class="form-input" 
                                   min="0" step="0.01" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Digistore24 Produkt-ID</label>
                        <input type="text" id="digistoreId" name="digistore_product_id" class="form-input">
                        <span class="form-help">Optional: Für automatische Provisionszahlungen</span>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentTemplates = [];

// Load templates on page load
document.addEventListener('DOMContentLoaded', function() {
    loadTemplates();
    
    // Color picker sync
    document.getElementById('rewardColor').addEventListener('input', function() {
        document.getElementById('rewardColorText').value = this.value;
        document.getElementById('colorPreview').style.background = this.value;
    });
});

async function loadTemplates() {
    try {
        const response = await fetch('/api/vendor/templates/list.php');
        const data = await response.json();
        
        if (data.success) {
            currentTemplates = data.templates;
            renderTemplates(data.templates);
        } else {
            showError('Fehler beim Laden der Templates');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Ein Fehler ist aufgetreten');
    }
}

function renderTemplates(templates) {
    const container = document.getElementById('templatesContainer');
    
    if (templates.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3 class="empty-state-title">Noch keine Templates</h3>
                <p>Erstellen Sie Ihr erstes Template und bieten Sie es im Marktplatz an.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = `
        <div class="templates-grid">
            ${templates.map(template => `
                <div class="template-card">
                    <div class="template-card-header">
                        <div class="template-info">
                            <h3 class="template-name">${escapeHtml(template.template_name)}</h3>
                            ${template.category ? `<span class="template-category">${escapeHtml(template.category)}</span>` : ''}
                        </div>
                        <div class="template-status ${template.is_published ? 'published' : 'draft'}">
                            <span>${template.is_published ? '✓' : '○'}</span>
                            <span>${template.is_published ? 'Veröffentlicht' : 'Entwurf'}</span>
                        </div>
                    </div>
                    
                    ${template.template_description ? `
                        <p class="template-description">${escapeHtml(template.template_description)}</p>
                    ` : ''}
                    
                    <div class="template-stats">
                        <div class="stat-item">
                            <div class="stat-value">${template.sales_count || 0}</div>
                            <div class="stat-label">Verkäufe</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${(template.revenue || 0).toFixed(2)}€</div>
                            <div class="stat-label">Umsatz</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${template.marketplace_price || 0}€</div>
                            <div class="stat-label">Preis</div>
                        </div>
                    </div>
                    
                    <div class="template-actions">
                        <button class="action-btn" onclick="editTemplate(${template.id})">
                            ✏️ Bearbeiten
                        </button>
                        <button class="action-btn" onclick="togglePublish(${template.id}, ${template.is_published})">
                            ${template.is_published ? '📥 Zurückziehen' : '📢 Veröffentlichen'}
                        </button>
                        <button class="action-btn danger" onclick="deleteTemplate(${template.id})">
                            🗑️ Löschen
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>
    `;
}

function openTemplateModal(templateId = null) {
    const modal = document.getElementById('templateModal');
    const form = document.getElementById('templateForm');
    form.reset();
    
    if (templateId) {
        document.getElementById('modalTitle').textContent = 'Template bearbeiten';
        loadTemplate(templateId);
    } else {
        document.getElementById('modalTitle').textContent = 'Neues Template';
        document.getElementById('templateId').value = '';
    }
    
    modal.classList.add('active');
}

function closeTemplateModal() {
    const modal = document.getElementById('templateModal');
    modal.classList.remove('active');
}

async function loadTemplate(templateId) {
    try {
        const response = await fetch(`/api/vendor/templates/get.php?id=${templateId}`);
        const data = await response.json();
        
        if (data.success && data.template) {
            const t = data.template;
            
            // Fill form
            document.getElementById('templateId').value = t.id;
            document.getElementById('templateName').value = t.template_name;
            document.getElementById('templateDescription').value = t.template_description || '';
            document.getElementById('category').value = t.category || '';
            document.getElementById('niche').value = t.niche || '';
            document.getElementById('rewardType').value = t.reward_type;
            document.getElementById('rewardTitle').value = t.reward_title;
            document.getElementById('rewardDescription').value = t.reward_description || '';
            document.getElementById('rewardValue').value = t.reward_value || '';
            document.getElementById('rewardIcon').value = t.reward_icon || '';
            document.getElementById('rewardColor').value = t.reward_color || '#667eea';
            document.getElementById('rewardColorText').value = t.reward_color || '#667eea';
            document.getElementById('colorPreview').style.background = t.reward_color || '#667eea';
            document.getElementById('deliveryType').value = t.reward_delivery_type || 'manual';
            document.getElementById('downloadUrl').value = t.reward_download_url || '';
            document.getElementById('instructions').value = t.reward_instructions || '';
            document.getElementById('courseDuration').value = t.course_duration || '';
            document.getElementById('mockupUrl').value = t.product_mockup_url || '';
            document.getElementById('originalProductLink').value = t.original_product_link || '';
            document.getElementById('tierLevel').value = t.suggested_tier_level || 1;
            document.getElementById('referralsRequired').value = t.suggested_referrals_required || 3;
            document.getElementById('price').value = t.marketplace_price || 0;
            document.getElementById('digistoreId').value = t.digistore_product_id || '';
        } else {
            alert('Fehler beim Laden des Templates');
            closeTemplateModal();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
        closeTemplateModal();
    }
}

async function saveTemplate(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const templateId = document.getElementById('templateId').value;
    const url = templateId ? '/api/vendor/templates/update.php' : '/api/vendor/templates/create.php';
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeTemplateModal();
            loadTemplates();
            showSuccess(templateId ? 'Template aktualisiert' : 'Template erstellt');
        } else {
            alert(result.error || 'Fehler beim Speichern');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}

function editTemplate(templateId) {
    openTemplateModal(templateId);
}

async function togglePublish(templateId, currentStatus) {
    if (!confirm(currentStatus ? 'Template zurückziehen?' : 'Template veröffentlichen?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/vendor/templates/toggle-publish.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: templateId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadTemplates();
            showSuccess(currentStatus ? 'Template zurückgezogen' : 'Template veröffentlicht');
        } else {
            alert(result.error || 'Fehler');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}

async function deleteTemplate(templateId) {
    if (!confirm('Template wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!')) {
        return;
    }
    
    try {
        const response = await fetch('/api/vendor/templates/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: templateId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadTemplates();
            showSuccess('Template gelöscht');
        } else {
            alert(result.error || 'Fehler beim Löschen');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    // Simple alert for now - can be enhanced with toast notifications
    alert(message);
}

function showError(message) {
    alert(message);
}

// Close modal on outside click
document.getElementById('templateModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeTemplateModal();
    }
});
</script>
