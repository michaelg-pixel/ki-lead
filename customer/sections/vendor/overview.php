<?php
/**
 * Vendor Dashboard - Übersicht Tab
 * Zeigt Statistiken und letzte Aktivitäten
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Stats laden
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_templates,
        SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_templates,
        SUM(times_imported) as total_imports,
        SUM(times_claimed) as total_claims
    FROM vendor_reward_templates
    WHERE vendor_id = ?
");
$stmt->execute([$customer_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Letzte Aktivitäten laden
$stmt = $pdo->prepare("
    SELECT 
        'import' as type,
        vrt.template_name,
        u.name as customer_name,
        rti.import_date as date
    FROM reward_template_imports rti
    JOIN vendor_reward_templates vrt ON rti.template_id = vrt.id
    JOIN users u ON rti.customer_id = u.id
    WHERE vrt.vendor_id = ?
    
    UNION ALL
    
    SELECT 
        'claim' as type,
        vrt.template_name,
        u.name as customer_name,
        rtc.claimed_at as date
    FROM reward_template_claims rtc
    JOIN vendor_reward_templates vrt ON rtc.template_id = vrt.id
    JOIN users u ON rtc.customer_id = u.id
    WHERE vrt.vendor_id = ?
    
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute([$customer_id, $customer_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.vendor-overview {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.4);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(102, 126, 234, 0.2);
    border-radius: 0.75rem;
    font-size: 1.5rem;
}

.stat-value {
    font-size: 2.25rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    font-weight: 500;
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 3rem;
}

.quick-action-btn {
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    color: var(--text-primary, #ffffff);
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 600;
    transition: all 0.2s;
}

.quick-action-btn:hover {
    background: rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.5);
    transform: translateX(4px);
}

.quick-action-icon {
    font-size: 1.25rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.activity-list {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    overflow: hidden;
}

.activity-item {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: background 0.2s;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: rgba(102, 126, 234, 0.05);
}

.activity-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.activity-icon.import {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.activity-icon.claim {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-title {
    color: var(--text-primary, #ffffff);
    font-weight: 600;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.activity-description {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
}

.activity-date {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    white-space: nowrap;
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

.empty-state-description {
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .vendor-overview {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .stat-value {
        font-size: 1.75rem;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .activity-date {
        align-self: flex-end;
    }
}
</style>

<div class="vendor-overview">
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?php echo $stats['total_templates'] ?? 0; ?></div>
                    <div class="stat-label">Templates</div>
                </div>
                <div class="stat-icon">🎁</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?php echo $stats['published_templates'] ?? 0; ?></div>
                    <div class="stat-label">Veröffentlicht</div>
                </div>
                <div class="stat-icon">📢</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?php echo $stats['total_imports'] ?? 0; ?></div>
                    <div class="stat-label">Imports</div>
                </div>
                <div class="stat-icon">📥</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?php echo $stats['total_claims'] ?? 0; ?></div>
                    <div class="stat-label">Claims</div>
                </div>
                <div class="stat-icon">✅</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="?page=vendor-bereich&tab=templates" class="quick-action-btn">
            <span class="quick-action-icon">➕</span>
            <span>Neues Template</span>
        </a>
        <a href="?page=vendor-bereich&tab=statistics" class="quick-action-btn">
            <span class="quick-action-icon">📊</span>
            <span>Statistiken</span>
        </a>
        <a href="?page=vendor-bereich&tab=settings" class="quick-action-btn">
            <span class="quick-action-icon">⚙️</span>
            <span>Einstellungen</span>
        </a>
    </div>
    
    <!-- Letzte Aktivitäten -->
    <h2 class="section-title">
        <span>📜</span>
        <span>Letzte Aktivitäten</span>
    </h2>
    
    <?php if (empty($activities)): ?>
        <div class="activity-list">
            <div class="empty-state">
                <div class="empty-state-icon">📭</div>
                <h3 class="empty-state-title">Noch keine Aktivitäten</h3>
                <p class="empty-state-description">
                    Erstellen Sie Ihr erstes Template und veröffentlichen Sie es im Marktplatz, 
                    um Aktivitäten zu sehen.
                </p>
                <a href="?page=vendor-bereich&tab=templates" class="quick-action-btn" style="display: inline-flex; margin: 0 auto;">
                    <span class="quick-action-icon">🚀</span>
                    <span>Template erstellen</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="activity-list">
            <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $activity['type']; ?>">
                        <?php echo $activity['type'] === 'import' ? '📥' : '✅'; ?>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <span><?php echo $activity['type'] === 'import' ? 'Import' : 'Claim'; ?></span>
                            <span>•</span>
                            <span><?php echo htmlspecialchars($activity['template_name']); ?></span>
                        </div>
                        <div class="activity-description">
                            <?php if ($activity['type'] === 'import'): ?>
                                von <?php echo htmlspecialchars($activity['customer_name']); ?> importiert
                            <?php else: ?>
                                von einem Lead eingelöst (Customer: <?php echo htmlspecialchars($activity['customer_name']); ?>)
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="activity-date">
                        <?php 
                        $date = new DateTime($activity['date']);
                        echo $date->format('d.m.Y H:i');
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
