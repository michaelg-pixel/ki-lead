<!-- Modal: Limits verwalten -->
<div id="manageLimitsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">📊 Limits verwalten</h3>
            <button class="modal-close" onclick="closeModal('manageLimitsModal')">×</button>
        </div>
        <form id="manageLimitsForm">
            <input type="hidden" name="user_id" id="limitsUserId">
            
            <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 20px;">ℹ️</span>
                    <strong style="color: #60a5fa;">Hinweis</strong>
                </div>
                <p style="color: #93c5fd; font-size: 13px; margin: 0; line-height: 1.5;">
                    Hier kannst du die Freebie-Limits und Empfehlungsprogramm-Slots für diesen Kunden manuell anpassen. 
                    Diese Einstellungen überschreiben die automatischen Produkteinstellungen.
                </p>
            </div>
            
            <div id="limitsUserInfo" style="background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <!-- Wird dynamisch gefüllt -->
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    🎁 Freebie-Limit (Eigene Freebies)
                </label>
                <input type="number" 
                       class="form-input" 
                       name="freebie_limit" 
                       id="limitsFreebie"
                       min="0" 
                       max="999"
                       placeholder="z.B. 10">
                <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">
                    Anzahl der eigenen Freebies, die der Kunde erstellen kann
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    🚀 Empfehlungsprogramm-Slots
                </label>
                <input type="number" 
                       class="form-input" 
                       name="referral_slots" 
                       id="limitsReferral"
                       min="0" 
                       max="999"
                       placeholder="z.B. 5">
                <small style="color: #888; font-size: 12px; display: block; margin-top: 4px;">
                    Anzahl der Empfehlungsprogramme, die der Kunde nutzen kann
                </small>
            </div>
            
            <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 12px; margin-top: 20px;">
                <div style="display: flex; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 16px;">⚠️</span>
                    <small style="color: #fcd34d; font-size: 12px; line-height: 1.5;">
                        Diese Änderungen werden sofort wirksam und überschreiben die Produkteinstellungen aus Digistore24.
                    </small>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('manageLimitsModal')">Abbrechen</button>
                <button type="submit" class="btn btn-primary">💾 Limits speichern</button>
            </div>
        </form>
    </div>
</div>

<script>
// Limits-Modal öffnen
async function manageLimits(userId) {
    openModal('manageLimitsModal');
    document.getElementById('limitsUserId').value = userId;
    
    // Lade aktuelle Limits und User-Daten
    try {
        const response = await fetch(`/api/customer-get-limits.php?user_id=${userId}`);
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // User-Info anzeigen
            document.getElementById('limitsUserInfo').innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <div style="color: #c084fc; font-size: 14px; font-weight: 600; margin-bottom: 4px;">
                            ${data.user.name}
                        </div>
                        <div style="color: #888; font-size: 13px;">
                            ${data.user.email}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #888; font-size: 11px; text-transform: uppercase; margin-bottom: 4px;">
                            Aktuelles Produkt
                        </div>
                        <div style="color: #60a5fa; font-size: 13px; font-weight: 600;">
                            ${data.product_name || 'Kein Produkt'}
                        </div>
                    </div>
                </div>
            `;
            
            // Aktuelle Werte setzen
            document.getElementById('limitsFreebie').value = data.freebie_limit || 0;
            document.getElementById('limitsReferral').value = data.referral_slots || 0;
            
            // Platzhalter mit aktuellen Werten aktualisieren
            document.getElementById('limitsFreebie').placeholder = `Aktuell: ${data.freebie_limit || 0}`;
            document.getElementById('limitsReferral').placeholder = `Aktuell: ${data.referral_slots || 0}`;
            
        } else {
            alert('❌ Fehler beim Laden der Limits: ' + result.message);
            closeModal('manageLimitsModal');
        }
    } catch (error) {
        alert('❌ Fehler beim Laden der Limits');
        closeModal('manageLimitsModal');
    }
}

// Limits speichern
document.getElementById('manageLimitsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Validierung
    const freebieLimit = formData.get('freebie_limit');
    const referralSlots = formData.get('referral_slots');
    
    if (!freebieLimit && !referralSlots) {
        alert('⚠️ Bitte mindestens einen Wert eingeben');
        return;
    }
    
    try {
        const response = await fetch('/api/customer-update-limits.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Limits erfolgreich aktualisiert!\n\nGeändert: ' + result.updated.join(', '));
            window.location.reload();
        } else {
            alert('❌ Fehler: ' + result.error);
        }
    } catch (error) {
        alert('❌ Fehler beim Aktualisieren der Limits');
    }
});
</script>

<style>
/* Zusätzliche Styles für Limits-Modal (falls nicht vorhanden) */
.form-input[type="number"] {
    font-family: 'Courier New', monospace;
}

.form-input::placeholder {
    color: #6b7280;
    font-style: italic;
}
</style>
