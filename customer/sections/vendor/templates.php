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
?>

<style>
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
        
        <button class="btn-create" onclick="alert('Template-Editor wird in Phase 4 implementiert')">
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
            <button class="btn-create" style="margin: 0 auto;" onclick="alert('Template-Editor wird in Phase 4 implementiert')">
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
                            <button class="template-btn" onclick="alert('Bearbeiten-Funktion wird in Phase 4 implementiert')">
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

<script>
async function togglePublish(templateId, newState) {
    const toggle = document.getElementById('toggle-' + templateId);
    
    try {
        const response = await fetch('/api/vendor/templates/toggle-publish.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: templateId,
                is_published: newState
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (newState) {
                toggle.classList.add('active');
            } else {
                toggle.classList.remove('active');
            }
            
            // Reload page to update badge
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
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: templateId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Ein Fehler ist aufgetreten');
    }
}
</script>
