<?php
/**
 * Vendor Dashboard - Auszahlungen Tab
 * Übersicht über Auszahlungen und Transaktionen
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Placeholder: Später wird hier eine payouts Tabelle abgefragt
// Für jetzt zeigen wir nur die Gesamtsumme aus vendor_reward_templates
$stmt = $pdo->prepare("
    SELECT SUM(total_revenue) as total_earnings
    FROM vendor_reward_templates
    WHERE vendor_id = ?
");
$stmt->execute([$customer_id]);
$earnings = $stmt->fetchColumn() ?? 0;
?>

<style>
.vendor-payouts {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.payout-summary {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 3rem;
    text-align: center;
}

.summary-label {
    color: var(--text-secondary, #9ca3af);
    font-size: 1rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.summary-amount {
    font-size: 3rem;
    font-weight: 700;
    color: #10b981;
    margin-bottom: 1rem;
}

.summary-hint {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
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

.payouts-placeholder {
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

@media (max-width: 768px) {
    .vendor-payouts {
        padding: 1rem;
    }
    
    .payout-summary {
        padding: 1.5rem;
    }
    
    .summary-amount {
        font-size: 2rem;
    }
    
    .info-boxes {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="vendor-payouts">
    <!-- Gesamtverdienst -->
    <div class="payout-summary">
        <div class="summary-label">Gesamtverdienst</div>
        <div class="summary-amount"><?php echo number_format($earnings, 2, ',', '.'); ?>€</div>
        <div class="summary-hint">
            Ausstehender Betrag aus allen Template-Verkäufen und Claims
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
                Auszahlungen erfolgen automatisch zum 1. jeden Monats, wenn der Mindestbetrag von 50€ erreicht wurde.
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
    
    <!-- Auszahlungs-Historie (Platzhalter) -->
    <div class="payouts-placeholder">
        <div class="placeholder-icon">💸</div>
        <h3 class="placeholder-title">Auszahlungs-Historie</h3>
        <p class="placeholder-text">
            Hier werden alle Ihre Auszahlungen aufgelistet, sobald die erste Auszahlung erfolgt ist.
        </p>
        
        <!-- Beispiel wie es aussehen wird -->
        <div style="max-width: 800px; margin: 2rem auto; text-align: left;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(102, 126, 234, 0.2);">
                        <th style="padding: 1rem; text-align: left; color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">Datum</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">Betrag</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">Status</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-secondary, #9ca3af); font-size: 0.875rem;">Methode</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); opacity: 0.5;">
                        <td style="padding: 1rem; color: var(--text-primary, #ffffff);">01.12.2025</td>
                        <td style="padding: 1rem; color: #10b981; font-weight: 600;">450,00€</td>
                        <td style="padding: 1rem;">
                            <span class="payout-status paid">✓ Ausbezahlt</span>
                        </td>
                        <td style="padding: 1rem; color: var(--text-secondary, #9ca3af);">PayPal</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); opacity: 0.5;">
                        <td style="padding: 1rem; color: var(--text-primary, #ffffff);">01.01.2026</td>
                        <td style="padding: 1rem; color: #10b981; font-weight: 600;">320,50€</td>
                        <td style="padding: 1rem;">
                            <span class="payout-status processing">⏳ In Bearbeitung</span>
                        </td>
                        <td style="padding: 1rem; color: var(--text-secondary, #9ca3af);">Bank</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p class="placeholder-text" style="margin-top: 2rem;">
            <strong>Phase 7</strong> - Vollständige Auszahlungs-Verwaltung wird in der nächsten Phase implementiert
        </p>
    </div>
</div>
