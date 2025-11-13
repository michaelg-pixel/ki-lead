<?php
/**
 * Vendor Dashboard - Statistiken Tab
 * Detaillierte Statistiken und Analysen
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Template-spezifische Stats wenn template_id übergeben
$template_id = $_GET['template_id'] ?? null;

if ($template_id) {
    // Einzelnes Template laden
    $stmt = $pdo->prepare("
        SELECT * FROM vendor_reward_templates 
        WHERE id = ? AND vendor_id = ?
    ");
    $stmt->execute([$template_id, $customer_id]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        echo '<div style="padding: 2rem; text-align: center;"><h3>Template nicht gefunden</h3></div>';
        return;
    }
}
?>

<style>
.vendor-statistics {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.stats-placeholder {
    background: rgba(255, 255, 255, 0.05);
    border: 2px dashed rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 4rem 2rem;
    text-align: center;
}

.placeholder-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.placeholder-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1rem;
}

.placeholder-text {
    color: var(--text-secondary, #9ca3af);
    margin-bottom: 1.5rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 2rem 0;
    display: inline-block;
    text-align: left;
}

.feature-list li {
    padding: 0.5rem 0;
    color: var(--text-secondary, #9ca3af);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.feature-list li:before {
    content: "📊";
    font-size: 1.25rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    text-decoration: none;
    transition: all 0.2s;
}

.btn-back:hover {
    background: rgba(102, 126, 234, 0.2);
    border-color: rgba(102, 126, 234, 0.5);
}
</style>

<div class="vendor-statistics">
    <?php if ($template_id && $template): ?>
        <div style="margin-bottom: 2rem;">
            <a href="?page=vendor-bereich&tab=statistics" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                <span>Zurück zur Übersicht</span>
            </a>
        </div>
        
        <h2 style="color: white; margin-bottom: 1rem;">
            📊 Statistiken: <?php echo htmlspecialchars($template['template_name']); ?>
        </h2>
    <?php endif; ?>
    
    <div class="stats-placeholder">
        <div class="placeholder-icon">📈</div>
        <h3 class="placeholder-title">Statistiken & Analysen</h3>
        <p class="placeholder-text">
            In diesem Bereich werden detaillierte Statistiken und Analysen für Ihre Templates angezeigt.
        </p>
        
        <ul class="feature-list">
            <li>Imports über Zeit (Line Chart)</li>
            <li>Claims pro Template (Bar Chart)</li>
            <li>Verteilung nach Kategorie (Pie Chart)</li>
            <li>Top-Templates nach Performance</li>
            <li>Detaillierte Import- und Claim-Historie</li>
            <li>Umsatz-Entwicklung</li>
            <li>Conversion-Raten</li>
        </ul>
        
        <p class="placeholder-text">
            <strong>Phase 6</strong> - Wird in der nächsten Implementierungsphase umgesetzt
        </p>
    </div>
</div>
