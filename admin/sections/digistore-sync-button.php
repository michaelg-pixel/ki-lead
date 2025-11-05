<!-- Sync-Button für Digistore-Produkte -->
<!-- Füge diesen Code in jedes product-card Form ein (in admin/sections/digistore.php) -->

<?php if ($product['is_active'] && !empty($product['product_id'])): ?>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">
        💾 Speichern
    </button>
    
    <button type="button" 
            class="btn btn-success" 
            onclick="syncProduct('<?php echo htmlspecialchars($product['product_id']); ?>', '<?php echo htmlspecialchars($product['product_name']); ?>', false)"
            title="Alle Kunden mit diesem Tarif aktualisieren">
        🔄 Alle Kunden aktualisieren
    </button>
    
    <a href="/webhook/test-digistore.php?product_id=<?php echo urlencode($product['product_id']); ?>" 
       class="btn btn-secondary" target="_blank">
        🧪 Webhook testen
    </a>
</div>
<?php else: ?>
<div class="form-actions">
    <button type="submit" class="btn btn-primary">
        💾 Speichern & Aktivieren
    </button>
</div>
<?php endif; ?>

<script>
// Produkt-Synchronisation
async function syncProduct(productId, productName, overwriteManual = false) {
    if (!confirm(`🔄 Tarif-Synchronisation\n\nMöchtest du ALLE Kunden mit dem Tarif "${productName}" auf die aktuellen Limits aktualisieren?\n\nDies betrifft:\n- Freebie-Limits\n- Empfehlungsprogramm-Slots\n\nManuell gesetzte Limits werden ${overwriteManual ? 'ÜBERSCHRIEBEN' : 'NICHT überschrieben'}.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('overwrite_manual', overwriteManual ? '1' : '0');
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="loading-spinner"></span> Synchronisiere...';
    btn.disabled = true;
    
    try {
        const response = await fetch('/api/product-sync-limits.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const stats = result.stats;
            const product = result.product;
            
            let message = `✅ Synchronisation erfolgreich!\n\n`;
            message += `📊 Statistik:\n`;
            message += `- Betroffene Kunden: ${stats.total_customers}\n`;
            message += `- Freebie-Limits aktualisiert: ${stats.freebies_updated}\n`;
            message += `- Referral-Slots aktualisiert: ${stats.referrals_updated}\n`;
            
            if (stats.manual_skipped > 0) {
                message += `- Manuell gesetzte übersprungen: ${stats.manual_skipped}\n\n`;
                message += `💡 Tipp: Diese ${stats.manual_skipped} Kunden haben manuell gesetzte Limits.\n`;
                message += `Möchtest du auch diese überschreiben?`;
                
                if (confirm(message)) {
                    // Nochmal mit overwrite_manual = true
                    await syncProduct(productId, productName, true);
                    return;
                }
            }
            
            message += `\n✨ Neue Limits:\n`;
            message += `- Freebies: ${product.freebies}\n`;
            message += `- Referral-Slots: ${product.referral_slots}`;
            
            alert(message);
            
            // Optional: Seite neu laden
            // window.location.reload();
        } else {
            alert('❌ Fehler bei der Synchronisation:\n\n' + result.error);
        }
    } catch (error) {
        console.error('Sync error:', error);
        alert('❌ Verbindungsfehler bei der Synchronisation');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

// Styling für Loading Spinner
const style = document.createElement('style');
style.textContent = `
    .loading-spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
`;
document.head.appendChild(style);
</script>

<!--
ANLEITUNG ZUR INTEGRATION:

1. Öffne: /admin/sections/digistore.php

2. Suche nach den form-actions Divs (ca. Zeile 550-570)

3. Ersetze die bestehenden form-actions mit dem Code von oben

4. Der Sync-Button erscheint nun bei allen aktiven Produkten

FEATURES:
- Aktualisiert alle Kunden mit diesem Tarif
- Respektiert manuelle Limits (optional überschreibbar)
- Zeigt detaillierte Statistik nach Sync
- Bestätigungsdialog mit allen Details
-->
