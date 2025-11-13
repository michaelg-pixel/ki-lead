<?php
/**
 * Vendor Dashboard - Einstellungen Tab
 * Vendor-Profil und Einstellungen bearbeiten
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Vendor-Daten laden
$stmt = $pdo->prepare("
    SELECT 
        vendor_company_name,
        vendor_website,
        vendor_description
    FROM users 
    WHERE id = ?
");
$stmt->execute([$customer_id]);
$vendor = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
.vendor-settings {
    padding: 2rem;
    max-width: 800px;
    margin: 0 auto;
}

.settings-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
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
.form-textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: var(--text-primary, #ffffff);
    font-size: 0.9375rem;
    transition: all 0.2s;
}

.form-input:focus,
.form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 120px;
}

.form-hint {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.875rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
    padding: 0.875rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 1rem;
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.5);
}

.success-message,
.error-message {
    padding: 1rem 1.5rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    display: none;
}

.success-message {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
}

.error-message {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.success-message.show,
.error-message.show {
    display: block;
}

.danger-zone {
    background: rgba(239, 68, 68, 0.05);
    border: 1px solid rgba(239, 68, 68, 0.2);
}

.danger-zone-title {
    color: #ef4444;
}

.danger-zone-description {
    color: var(--text-secondary, #9ca3af);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .vendor-settings {
        padding: 1rem;
    }
    
    .settings-section {
        padding: 1.5rem;
    }
}
</style>

<div class="vendor-settings">
    <div id="successMessage" class="success-message"></div>
    <div id="errorMessage" class="error-message"></div>
    
    <!-- Unternehmensinformationen -->
    <div class="settings-section">
        <h2 class="section-title">
            <span>🏢</span>
            <span>Unternehmensinformationen</span>
        </h2>
        
        <form id="companyForm" onsubmit="saveSettings(event)">
            <div class="form-group">
                <label class="form-label">
                    Firmenname <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    class="form-input" 
                    name="vendor_company_name" 
                    value="<?php echo htmlspecialchars($vendor['vendor_company_name'] ?? ''); ?>"
                    required 
                    minlength="3"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Website</label>
                <input 
                    type="url" 
                    class="form-input" 
                    name="vendor_website" 
                    value="<?php echo htmlspecialchars($vendor['vendor_website'] ?? ''); ?>"
                    placeholder="https://ihre-website.de"
                >
                <div class="form-hint">Ihre Unternehmenswebsite (optional)</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea 
                    class="form-textarea" 
                    name="vendor_description"
                    placeholder="Kurze Beschreibung Ihres Unternehmens..."
                ><?php echo htmlspecialchars($vendor['vendor_description'] ?? ''); ?></textarea>
                <div class="form-hint">Wird im Marktplatz bei Ihren Templates angezeigt</div>
            </div>
            
            <button type="submit" class="btn-save" id="saveBtn">
                <i class="fas fa-save"></i> Speichern
            </button>
        </form>
    </div>
    
    <!-- Gefahrenzone -->
    <div class="settings-section danger-zone">
        <h2 class="section-title danger-zone-title">
            <span>⚠️</span>
            <span>Gefahrenzone</span>
        </h2>
        
        <p class="danger-zone-description">
            Durch die Deaktivierung des Vendor-Modus werden alle Ihre Templates aus dem Marktplatz entfernt. 
            Bereits importierte Templates bei anderen Kunden bleiben bestehen, aber Sie können keine neuen Templates mehr erstellen.
        </p>
        
        <button class="btn-danger" onclick="confirmDeactivate()">
            <i class="fas fa-exclamation-triangle"></i> Vendor-Modus deaktivieren
        </button>
    </div>
</div>

<script>
async function saveSettings(event) {
    event.preventDefault();
    const btn = document.getElementById('saveBtn');
    const successMsg = document.getElementById('successMessage');
    const errorMsg = document.getElementById('errorMessage');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichert...';
    successMsg.classList.remove('show');
    errorMsg.classList.remove('show');
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('/api/vendor/update-settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            successMsg.textContent = 'Einstellungen erfolgreich gespeichert';
            successMsg.classList.add('show');
        } else {
            errorMsg.textContent = result.error || 'Fehler beim Speichern';
            errorMsg.classList.add('show');
        }
    } catch (error) {
        errorMsg.textContent = 'Ein unerwarteter Fehler ist aufgetreten';
        errorMsg.classList.add('show');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Speichern';
    }
}

function confirmDeactivate() {
    if (confirm('Möchten Sie den Vendor-Modus wirklich deaktivieren?\n\nAlle Templates werden aus dem Marktplatz entfernt.')) {
        if (confirm('Sind Sie sicher? Diese Aktion kann später rückgängig gemacht werden, aber Ihre Templates müssen neu veröffentlicht werden.')) {
            deactivateVendor();
        }
    }
}

async function deactivateVendor() {
    try {
        const response = await fetch('/api/vendor/deactivate.php', {
            method: 'POST'
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Vendor-Modus wurde deaktiviert');
            location.href = '?page=overview';
        } else {
            alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
        }
    } catch (error) {
        alert('Ein unerwarteter Fehler ist aufgetreten');
    }
}
</script>
