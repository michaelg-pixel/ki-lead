<?php
/**
 * Vendor Dashboard - Templates Tab
 * Template-Verwaltung und CRUD-Operationen
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Templates laden
$filter_status = $_GET['filter_status'] ?? 'all';
$filter_category = $_GET['filter_category'] ?? '';

$sql = "
    SELECT 
        id,
        template_name,
        template_description,
        category,
        niche,
        reward_type,
        reward_title,
        reward_icon,
        reward_color,
        preview_image,
        is_published,
        is_featured,
        marketplace_price,
        times_imported,
        times_claimed,
        total_revenue,
        created_at
    FROM vendor_reward_templates
    WHERE vendor_id = ?
";

$params = [$customer_id];

if ($filter_status === 'published') {
    $sql .= " AND is_published = 1";
} elseif ($filter_status === 'draft') {
    $sql .= " AND is_published = 0";
}

if ($filter_category) {
    $sql .= " AND category = ?";
    $params[] = $filter_category;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kategorien für Filter
$categories = [
    'ebook' => '📚 E-Book',
    'consultation' => '💬 Beratung',
    'discount' => '🏷️ Rabatt',
    'course' => '🎓 Kurs',
    'voucher' => '🎟️ Gutschein',
    'software' => '💻 Software',
    'template' => '📄 Vorlage',
    'other' => '🎁 Sonstiges'
];

// Reward Types
$reward_types = [
    'ebook' => 'E-Book',
    'video' => 'Video',
    'course' => 'Online-Kurs',
    'consultation' => 'Beratungsgespräch',
    'discount' => 'Rabatt-Code',
    'voucher' => 'Gutschein',
    'physical' => 'Physisches Produkt',
    'service' => 'Dienstleistung',
    'software' => 'Software-Zugang',
    'other' => 'Sonstiges'
];
?>

<style>
/* Hier bleiben alle bisherigen Styles... */
.vendor-templates {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.templates-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    gap: 1rem;
    flex-wrap: wrap;
}

.templates-filters {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-select {
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-create {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.template-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    overflow: hidden;
    transition: all 0.3s;
}

.template-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.4);
}

.template-preview {
    width: 100%;
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    position: relative;
}

.template-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.template-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(8px);
    color: white;
    padding: 0.375rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.template-badge.published {
    background: rgba(16, 185, 129, 0.9);
}

.template-badge.draft {
    background: rgba(156, 163, 175, 0.9);
}

.template-content {
    padding: 1.5rem;
}

.template-header {
    margin-bottom: 1rem;
}

.template-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.5rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.template-description {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.template-meta {
    display: flex;
    gap: 1rem;
    margin: 1rem 0;
    padding: 1rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.template-stat {
    flex: 1;
    text-align: center;
}

.template-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.25rem;
}

.template-stat-label {
    font-size: 0.75rem;
    color: var(--text-secondary, #9ca3af);
}

.template-revenue {
    font-size: 1.5rem;
    font-weight: 700;
    color: #10b981;
    text-align: center;
    margin: 0.75rem 0;
}

.template-actions {
    display: flex;
    gap: 0.5rem;
}

.template-toggle {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.template-toggle:hover {
    background: rgba(102, 126, 234, 0.15);
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    transition: background 0.3s;
}

.toggle-switch.active {
    background: #10b981;
}

.toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s;
}

.toggle-switch.active::after {
    transform: translateX(20px);
}

.template-btn {
    padding: 0.75rem 1rem;
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary, #ffffff);
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
}

.template-btn:hover {
    background: rgba(102, 126, 234, 0.1);
    border-color: rgba(102, 126, 234, 0.5);
}

.template-btn.danger {
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.template-btn.danger:hover {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.5);
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
}

.empty-state-icon {
    font-size: 5rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-state-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.75rem;
}

.empty-state-description {
    color: var(--text-secondary, #9ca3af);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(4px);
    overflow-y: auto;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--dark-bg, #1f2937);
    border-radius: 1rem;
    padding: 0;
    max-width: 900px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-secondary, #9ca3af);
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
}

.modal-footer {
    padding: 1.5rem 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(102, 126, 234, 0.3);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-row.single {
    grid-template-columns: 1fr;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    color: var(--text-primary, #ffffff);
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
}

.form-label .required {
    color: #ef4444;
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: var(--darker-bg, #111827);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.9375rem;
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
    resize: vertical;
    min-height: 100px;
}

.form-hint {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.color-picker-wrapper {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.color-picker {
    width: 60px;
    height: 40px;
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    cursor: pointer;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    font-size: 0.9375rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary, #ffffff);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.15);
}

.error-message {
    background: rgba(239, 68, 68, 0.1);
    border-left: 3px solid #ef4444;
    padding: 1rem;
    border-radius: 0.5rem;
    color: #ef4444;
    font-size: 0.875rem;
    margin-bottom: 1rem;
    display: none;
}

.error-message.show {
    display: block;
}

@media (max-width: 768px) {
    .vendor-templates {
        padding: 1rem;
    }
    
    .templates-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .templates-filters {
        flex-direction: column;
    }
    
    .filter-select {
        width: 100%;
    }
    
    .templates-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
}
</style>

<div class="vendor-templates">
    <div class="templates-header">
        <div class="templates-filters">
            <select class="filter-select" onchange="location.href='?page=vendor-bereich&tab=templates&filter_status='+this.value+'&filter_category=<?php echo htmlspecialchars($filter_category); ?>'">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Alle Templates</option>
                <option value="published" <?php echo $filter_status === 'published' ? 'selected' : ''; ?>>Veröffentlicht</option>
                <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Entwürfe</option>
            </select>
            
            <select class="filter-select" onchange="location.href='?page=vendor-bereich&tab=templates&filter_status=<?php echo htmlspecialchars($filter_status); ?>&filter_category='+this.value">
                <option value="">Alle Kategorien</option>
                <?php foreach ($categories as $cat_value => $cat_label): ?>
                    <option value="<?php echo $cat_value; ?>" <?php echo $filter_category === $cat_value ? 'selected' : ''; ?>>
                        <?php echo $cat_label; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button class="btn-create" onclick="openTemplateModal()">
            <i class="fas fa-plus"></i>
            <span>Neues Template</span>
        </button>
    </div>
    
    <?php if (empty($templates)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3 class="empty-state-title">Noch keine Templates</h3>
            <p class="empty-state-description">
                Erstellen Sie Ihr erstes Belohnungs-Template und teilen Sie es mit anderen Kunden im Marktplatz.
            </p>
            <button class="btn-create" style="margin: 0 auto;" onclick="openTemplateModal()">
                <i class="fas fa-rocket"></i>
                <span>Erstes Template erstellen</span>
            </button>
        </div>
    <?php else: ?>
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
                <div class="template-card">
                    <div class="template-preview" style="background: <?php echo htmlspecialchars($template['reward_color']); ?>;">
                        <?php if ($template['preview_image']): ?>
                            <img src="<?php echo htmlspecialchars($template['preview_image']); ?>" alt="Preview">
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($template['reward_icon'] ?: '🎁'); ?></span>
                        <?php endif; ?>
                        
                        <div class="template-badge <?php echo $template['is_published'] ? 'published' : 'draft'; ?>">
                            <?php echo $template['is_published'] ? '✓ Veröffentlicht' : '📝 Entwurf'; ?>
                        </div>
                    </div>
                    
                    <div class="template-content">
                        <div class="template-header">
                            <h3 class="template-name"><?php echo htmlspecialchars($template['template_name']); ?></h3>
                            <?php if ($template['template_description']): ?>
                                <p class="template-description"><?php echo htmlspecialchars($template['template_description']); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="template-meta">
                            <div class="template-stat">
                                <div class="template-stat-value"><?php echo $template['times_imported']; ?></div>
                                <div class="template-stat-label">Imports</div>
                            </div>
                            <div class="template-stat">
                                <div class="template-stat-value"><?php echo $template['times_claimed']; ?></div>
                                <div class="template-stat-label">Claims</div>
                            </div>
                        </div>
                        
                        <?php if ($template['total_revenue'] > 0): ?>
                            <div class="template-revenue">
                                <?php echo number_format($template['total_revenue'], 2, ',', '.'); ?>€
                            </div>
                        <?php endif; ?>
                        
                        <div class="template-actions">
                            <div class="template-toggle" onclick="togglePublish(<?php echo $template['id']; ?>, <?php echo $template['is_published'] ? 'false' : 'true'; ?>)">
                                <span>Im Marktplatz</span>
                                <div class="toggle-switch <?php echo $template['is_published'] ? 'active' : ''; ?>" id="toggle-<?php echo $template['id']; ?>"></div>
                            </div>
                        </div>
                        
                        <div class="template-actions" style="margin-top: 0.5rem;">
                            <button class="template-btn" onclick="editTemplate(<?php echo $template['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?page=vendor-bereich&tab=statistics&template_id=<?php echo $template['id']; ?>" class="template-btn">
                                <i class="fas fa-chart-bar"></i>
                            </a>
                            <button class="template-btn danger" onclick="confirmDelete(<?php echo $template['id']; ?>, '<?php echo addslashes($template['template_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Template Editor Modal -->
<div class="modal" id="templateModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Neues Template erstellen</h3>
            <button class="modal-close" onclick="closeTemplateModal()">×</button>
        </div>
        
        <div class="modal-body">
            <div class="error-message" id="errorMessage"></div>
            
            <form id="templateForm">
                <input type="hidden" id="templateId" name="id">
                
                <!-- Grunddaten -->
                <div class="form-section">
                    <h4 class="form-section-title">📝 Grunddaten</h4>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Template-Name <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="templateName" name="template_name" required minlength="3" placeholder="z.B. Gratis E-Book: Social Media Marketing">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Beschreibung</label>
                        <textarea class="form-textarea" id="templateDescription" name="template_description" placeholder="Kurze Beschreibung des Templates..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Kategorie</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">-- Wählen --</option>
                                <?php foreach ($categories as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nische</label>
                            <input type="text" class="form-input" id="niche" name="niche" placeholder="z.B. Online-Business, Fitness">
                        </div>
                    </div>
                </div>
                
                <!-- Belohnung -->
                <div class="form-section">
                    <h4 class="form-section-title">🎁 Belohnung</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Belohnungs-Typ <span class="required">*</span>
                            </label>
                            <select class="form-select" id="rewardType" name="reward_type" required>
                                <option value="">-- Wählen --</option>
                                <?php foreach ($reward_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                Belohnungs-Titel <span class="required">*</span>
                            </label>
                            <input type="text" class="form-input" id="rewardTitle" name="reward_title" required placeholder="z.B. Gratis E-Book">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Belohnungs-Beschreibung</label>
                        <textarea class="form-textarea" id="rewardDescription" name="reward_description" placeholder="Was erhält der Lead genau?"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Wert (Display)</label>
                            <input type="text" class="form-input" id="rewardValue" name="reward_value" placeholder="z.B. Wert 29€">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Icon</label>
                            <input type="text" class="form-input" id="rewardIcon" name="reward_icon" placeholder="Emoji oder fa-gift">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Farbe</label>
                        <div class="color-picker-wrapper">
                            <input type="color" class="color-picker" id="rewardColor" name="reward_color" value="#667eea">
                            <input type="text" class="form-input" style="flex: 1;" id="rewardColorText" value="#667eea" onchange="document.getElementById('rewardColor').value = this.value">
                        </div>
                    </div>
                </div>
                
                <!-- Zugriff & Lieferung -->
                <div class="form-section">
                    <h4 class="form-section-title">🚀 Zugriff & Lieferung</h4>
                    
                    <div class="form-group">
                        <label class="form-label">Lieferungs-Typ</label>
                        <select class="form-select" id="deliveryType" name="reward_delivery_type">
                            <option value="manual">Manuell</option>
                            <option value="automatic">Automatisch</option>
                            <option value="code">Zugriffscode</option>
                            <option value="url">URL/Link</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Download URL</label>
                        <input type="url" class="form-input" id="downloadUrl" name="reward_download_url" placeholder="https://...">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Anweisungen</label>
                        <textarea class="form-textarea" id="instructions" name="reward_instructions" placeholder="Anweisungen für den Lead..."></textarea>
                    </div>
                </div>
                
                <!-- Empfehlungs-Vorschlag -->
                <div class="form-section">
                    <h4 class="form-section-title">🎯 Empfehlungs-Vorschlag</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Vorgeschlagene Stufe</label>
                            <input type="number" class="form-input" id="tierLevel" name="suggested_tier_level" value="1" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Erforderliche Empfehlungen</label>
                            <input type="number" class="form-input" id="referralsRequired" name="suggested_referrals_required" value="3" min="1">
                        </div>
                    </div>
                </div>
                
                <!-- Marktplatz -->
                <div class="form-section">
                    <h4 class="form-section-title">🏪 Marktplatz</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Preis</label>
                            <input type="number" class="form-input" id="price" name="marketplace_price" value="0.00" min="0" step="0.01">
                            <div class="form-hint">0 = Kostenlos</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">DigiStore24 Produkt-ID</label>
                            <input type="text" class="form-input" id="digistoreId" name="digistore_product_id" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeTemplateModal()">Abbrechen</button>
            <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveTemplate()">
                <i class="fas fa-save"></i> Speichern
            </button>
        </div>
    </div>
</div>

<script>
// Color picker sync
document.getElementById('rewardColor')?.addEventListener('change', function() {
    document.getElementById('rewardColorText').value = this.value;
});

function openTemplateModal(templateId = null) {
    const modal = document.getElementById('templateModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('templateForm');
    const errorMsg = document.getElementById('errorMessage');
    
    // Reset
    form.reset();
    errorMsg.classList.remove('show');
    document.getElementById('templateId').value = '';
    
    if (templateId) {
        modalTitle.textContent = 'Template bearbeiten';
        loadTemplate(templateId);
    } else {
        modalTitle.textContent = 'Neues Template erstellen';
    }
    
    modal.classList.add('show');
}

function closeTemplateModal() {
    document.getElementById('templateModal').classList.remove('show');
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
            document.getElementById('deliveryType').value = t.reward_delivery_type || 'manual';
            document.getElementById('downloadUrl').value = t.reward_download_url || '';
            document.getElementById('instructions').value = t.reward_instructions || '';
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

async function saveTemplate() {
    const form = document.getElementById('templateForm');
    const saveBtn = document.getElementById('saveBtn');
    const errorMsg = document.getElementById('errorMessage');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichert...';
    errorMsg.classList.remove('show');
    
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    const templateId = document.getElementById('templateId').value;
    const endpoint = templateId ? '/api/vendor/templates/update.php' : '/api/vendor/templates/create.php';
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            if (result.errors && Array.isArray(result.errors)) {
                errorMsg.innerHTML = result.errors.join('<br>');
            } else {
                errorMsg.textContent = result.error || 'Ein Fehler ist aufgetreten';
            }
            errorMsg.classList.add('show');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Speichern';
        }
    } catch (error) {
        console.error('Error:', error);
        errorMsg.textContent = 'Ein unerwarteter Fehler ist aufgetreten';
        errorMsg.classList.add('show');
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Speichern';
    }
}

function editTemplate(templateId) {
    openTemplateModal(templateId);
}

async function togglePublish(templateId, newState) {
    const toggle = document.getElementById('toggle-' + templateId);
    
    try {
        const response = await fetch('/api/vendor/templates/toggle-publish.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: templateId, is_published: newState })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (newState) {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}

function confirmDelete(templateId, templateName) {
    if (confirm(`Möchten Sie das Template "${templateName}" wirklich löschen?\n\nDiese Aktion kann nicht rückgängig gemacht werden.`)) {
        deleteTemplate(templateId);
    }
}

async function deleteTemplate(templateId) {
    try {
        const response = await fetch('/api/vendor/templates/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: templateId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (data.warning) {
                alert(data.warning);
            }
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}

// Close modal on outside click
document.getElementById('templateModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeTemplateModal();
    }
});
</script>
