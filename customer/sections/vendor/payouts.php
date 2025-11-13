<?php
/**
 * Vendor Dashboard - Auszahlungen Tab
 * Übersicht über Auszahlungen und Transaktionen
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Gesamtverdienst aus Templates laden
$stmt = $pdo->prepare("
    SELECT SUM(total_revenue) as total_earnings
    FROM vendor_reward_templates
    WHERE vendor_id = ?
");
$stmt->execute([$customer_id]);
$total_earnings = $stmt->fetchColumn() ?? 0;

// Bereits ausbezahlte Beträge
$stmt = $pdo->prepare("
    SELECT 
        SUM(amount) as paid_amount,
        COUNT(*) as payout_count
    FROM vendor_payouts
    WHERE vendor_id = ? AND status = 'paid'
");
$stmt->execute([$customer_id]);
$payout_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$paid_amount = $payout_stats['paid_amount'] ?? 0;
$payout_count = $payout_stats['payout_count'] ?? 0;

// Ausstehender Betrag
$pending_amount = $total_earnings - $paid_amount;

// Auszahlungs-Historie laden
$stmt = $pdo->prepare("
    SELECT 
        id,
        amount,
        period_start,
        period_end,
        status,
        payment_method,
        payment_email,
        payment_account,
        transaction_id,
        transaction_date,
        created_at,
        paid_at
    FROM vendor_payouts
    WHERE vendor_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$customer_id]);
$payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nächste Auszahlung berechnen (1. des nächsten Monats)
$next_payout = new DateTime('first day of next month');
$next_payout_date = $next_payout->format('d.m.Y');

// Prüfe ob Mindestbetrag erreicht
$min_payout = 50.00;
$can_request = $pending_amount >= $min_payout;
?>

<style>
.vendor-payouts {
    padding: 2rem;
    max-width: 1200px;
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
    text-align: center;
}

.stat-card.highlight {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.stat-label {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.25rem;
}

.stat-value.success {
    color: #10b981;
}

.stat-value.warning {
    color: #f59e0b;
}

.stat-hint {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.75rem;
}

.info-boxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.info-box {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    padding: 1.5rem;
}

.info-box-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.info-box-content {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    line-height: 1.6;
}

.payouts-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    padding: 2rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
}

.payouts-table {
    width: 100%;
    border-collapse: collapse;
}

.payouts-table thead tr {
    border-bottom: 2px solid rgba(102, 126, 234, 0.2);
}

.payouts-table th {
    padding: 1rem;
    text-align: left;
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    font-weight: 600;
}

.payouts-table td {
    padding: 1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.payouts-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.02);
}

.payout-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.payout-status.pending {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.payout-status.paid {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.payout-status.processing {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.payout-status.failed {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1rem;
}

.empty-text {
    color: var(--text-secondary, #9ca3af);
    max-width: 500px;
    margin: 0 auto;
}

.amount {
    color: #10b981;
    font-weight: 600;
}

@media (max-width: 768px) {
    .vendor-payouts {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .info-boxes {
        grid-template-columns: 1fr;
    }
    
    .payouts-section {
        padding: 1rem;
        overflow-x: auto;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .payouts-table {
        min-width: 600px;
    }
}
</style>

<div class="vendor-payouts">
    <!-- Statistiken -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Gesamtverdienst</div>
            <div class="stat-value"><?php echo number_format($total_earnings, 2, ',', '.'); ?>€</div>
            <div class="stat-hint">Aus allen Templates</div>
        </div>
        
        <div class="stat-card highlight">
            <div class="stat-label">Ausstehend</div>
            <div class="stat-value warning"><?php echo number_format($pending_amount, 2, ',', '.'); ?>€</div>
            <div class="stat-hint">
                <?php if ($can_request): ?>
                    Bereit zur Auszahlung
                <?php else: ?>
                    Mindestens <?php echo number_format($min_payout, 2, ',', '.'); ?>€ erforderlich
                <?php endif; ?>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Ausbezahlt</div>
            <div class="stat-value success"><?php echo number_format($paid_amount, 2, ',', '.'); ?>€</div>
            <div class="stat-hint"><?php echo $payout_count; ?> Auszahlungen</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Nächste Auszahlung</div>
            <div class="stat-value" style="font-size: 1.5rem;"><?php echo $next_payout_date; ?></div>
            <div class="stat-hint">Automatisch</div>
        </div>
    </div>
    
    <!-- Info Boxen -->
    <div class="info-boxes">
        <div class="info-box">
            <div class="info-box-title">
                <span>📅</span>
                <span>Auszahlungsrhythmus</span>
            </div>
            <div class="info-box-content">
                Auszahlungen erfolgen automatisch zum 1. jeden Monats, wenn der Mindestbetrag von <?php echo number_format($min_payout, 2, ',', '.'); ?>€ erreicht wurde.
            </div>
        </div>
        
        <div class="info-box">
            <div class="info-box-title">
                <span>💳</span>
                <span>Zahlungsmethoden</span>
            </div>
            <div class="info-box-content">
                PayPal (bevorzugt) oder Banküberweisung. Stellen Sie sicher, dass Ihre Zahlungsdaten in den Einstellungen hinterlegt sind.
            </div>
        </div>
        
        <div class="info-box">
            <div class="info-box-title">
                <span>🧾</span>
                <span>Rechnungsstellung</span>
            </div>
            <div class="info-box-content">
                Sie erhalten automatisch eine Sammelrechnung für alle Auszahlungen des Vormonats per E-Mail.
            </div>
        </div>
    </div>
    
    <!-- Auszahlungs-Historie -->
    <div class="payouts-section">
        <div class="section-header">
            <h2 class="section-title">💸 Auszahlungs-Historie</h2>
        </div>
        
        <?php if (empty($payouts)): ?>
            <div class="empty-state">
                <div class="empty-icon">💸</div>
                <h3 class="empty-title">Noch keine Auszahlungen</h3>
                <p class="empty-text">
                    Sobald Sie Ihren ersten Verdienst von mindestens <?php echo number_format($min_payout, 2, ',', '.'); ?>€ erreichen, 
                    erfolgt die erste Auszahlung automatisch zum 1. des Folgemonats.
                </p>
            </div>
        <?php else: ?>
            <table class="payouts-table">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Zeitraum</th>
                        <th>Betrag</th>
                        <th>Status</th>
                        <th>Methode</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payouts as $payout): ?>
                    <tr>
                        <td style="color: var(--text-primary, #ffffff);">
                            <?php echo date('d.m.Y', strtotime($payout['created_at'])); ?>
                        </td>
                        <td style="color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">
                            <?php echo date('d.m.', strtotime($payout['period_start'])); ?> - 
                            <?php echo date('d.m.Y', strtotime($payout['period_end'])); ?>
                        </td>
                        <td class="amount">
                            <?php echo number_format($payout['amount'], 2, ',', '.'); ?>€
                        </td>
                        <td>
                            <?php
                            $status_icons = [
                                'pending' => '⏳',
                                'processing' => '🔄',
                                'paid' => '✓',
                                'failed' => '✕'
                            ];
                            $status_labels = [
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'paid' => 'Ausbezahlt',
                                'failed' => 'Fehlgeschlagen'
                            ];
                            ?>
                            <span class="payout-status <?php echo $payout['status']; ?>">
                                <?php echo $status_icons[$payout['status']]; ?>
                                <?php echo $status_labels[$payout['status']]; ?>
                            </span>
                        </td>
                        <td style="color: var(--text-secondary, #9ca3af);">
                            <?php 
                            echo $payout['payment_method'] === 'paypal' ? 'PayPal' : 'Banküberweisung';
                            ?>
                        </td>
                        <td style="color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">
                            <?php if ($payout['transaction_id']): ?>
                                ID: <?php echo htmlspecialchars($payout['transaction_id']); ?>
                            <?php elseif ($payout['payment_email']): ?>
                                <?php echo htmlspecialchars($payout['payment_email']); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>