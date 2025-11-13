<?php
/**
 * Vendor Bereich - Hauptseite
 * Zeigt Aktivierungs-Card oder Tab-Navigation je nach Vendor-Status
 */

if (!defined('INCLUDED')) {
    die('Direct access not permitted');
}

// Customer-Daten laden
$stmt = $pdo->prepare("
    SELECT 
        id,
        is_vendor,
        vendor_company_name,
        vendor_website,
        vendor_description,
        vendor_activated_at
    FROM users 
    WHERE id = ?
");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

$is_vendor = (bool)$customer['is_vendor'];
$vendor_id = $customer['id']; // Für Verwendung in Sub-Pages
$active_tab = $_GET['tab'] ?? 'overview';
?>

<style>
/* Vendor Bereich Styles */
.vendor-container {
    max-width: 1200px;
    margin: 0 auto;
}

.vendor-header {
    margin-bottom: 2rem;
}

.vendor-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.vendor-subtitle {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.9375rem;
}

/* Aktivierungs-Card */
.activation-card {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 1rem;
    padding: 3rem;
    text-align: center;
    max-width: 600px;
    margin: 4rem auto;
    animation: fadeInUp 0.6s ease-out;
}

.activation-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
}

.activation-title {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--text-primary, #ffffff);
    margin-bottom: 1rem;
}

.activation-description {
    color: var(--text-secondary, #9ca3af);
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.activation-benefits {
    text-align: left;
    margin-bottom: 2rem;
}

.activation-benefits ul {
    list-style: none;
    padding: 0;
}

.activation-benefits li {
    padding: 0.75rem 0;
    color: var(--text-primary, #ffffff);
    font-size: 0.9375rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.activation-benefits li:before {
    content: "✓";
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border-radius: 50%;
    font-weight: bold;
    flex-shrink: 0;
}

.btn-activate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2.5rem;
    border-radius: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn-activate:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}

/* Tab Navigation */
.vendor-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid rgba(102, 126, 234, 0.2);
    overflow-x: auto;
    padding-bottom: 0;
}

.vendor-tabs::-webkit-scrollbar {
    height: 4px;
}

.vendor-tabs::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.vendor-tabs::-webkit-scrollbar-thumb {
    background: rgba(102, 126, 234, 0.5);
    border-radius: 2px;
}

.vendor-tab {
    padding: 1rem 1.5rem;
    color: var(--text-secondary, #9ca3af);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.3s;
    white-space: nowrap;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vendor-tab:hover {
    color: var(--text-primary, #ffffff);
    background: rgba(102, 126, 234, 0.1);
}

.vendor-tab.active {
    color: var(--text-primary, #ffffff);
    border-bottom-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
}

.vendor-tab-content {
    animation: fadeInUp 0.4s ease-out;
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
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--dark-bg, #1f2937);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
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
    background: var(--darker-bg, #111827);
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
    min-height: 100px;
}

.form-checkbox-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.form-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
    flex-shrink: 0;
    margin-top: 2px;
}

.form-checkbox-label {
    color: var(--text-secondary, #9ca3af);
    font-size: 0.875rem;
    line-height: 1.5;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
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
    flex: 1;
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

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive */
@media (max-width: 768px) {
    .activation-card {
        padding: 2rem 1.5rem;
        margin: 2rem 1rem;
    }
    
    .activation-title {
        font-size: 1.5rem;
    }
    
    .vendor-tabs {
        gap: 0.25rem;
    }
    
    .vendor-tab {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}
</style>

<div class="vendor-container">
    <?php if (!$is_vendor): ?>
        <!-- Aktivierungs-Card -->
        <div class="activation-card">
            <div class="activation-icon">💎</div>
            <h2 class="activation-title">Werden Sie Vendor!</h2>
            <p class="activation-description">
                Erstellen Sie eigene Belohnungs-Templates und bieten Sie diese kostenlos im Marktplatz an. 
                Teilen Sie Ihre besten Belohnungsideen mit der Community!
            </p>
            
            <div class="activation-benefits">
                <ul>
                    <li>Erstellen Sie unbegrenzt viele Belohnungs-Templates</li>
                    <li>Veröffentlichen Sie Ihre Templates im Marktplatz</li>
                    <li>Helfen Sie anderen beim Aufbau ihrer Belohnungssysteme</li>
                    <li>Sehen Sie wie oft Ihre Templates verwendet werden</li>
                    <li>Detaillierte Statistiken und Analysen</li>
                    <li>Einfache Verwaltung aller Templates</li>
                </ul>
            </div>
            
            <button class="btn-activate" onclick="openActivationModal()">
                <i class="fas fa-rocket"></i> Jetzt Vendor werden
            </button>
        </div>
    <?php else: ?>
        <!-- Vendor Dashboard -->
        <div class="vendor-header">
            <h1 class="vendor-title">
                <span>💎</span> Vendor Bereich
            </h1>
            <p class="vendor-subtitle">
                Vendor seit: <?php echo date('d.m.Y', strtotime($customer['vendor_activated_at'])); ?>
                <?php if ($customer['vendor_company_name']): ?>
                    • <?php echo htmlspecialchars($customer['vendor_company_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        
        <!-- Tab Navigation -->
        <div class="vendor-tabs">
            <a href="?page=vendor-bereich&tab=overview" class="vendor-tab <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Übersicht
            </a>
            <a href="?page=vendor-bereich&tab=templates" class="vendor-tab <?php echo $active_tab === 'templates' ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> Templates
            </a>
            <a href="?page=vendor-bereich&tab=statistics" class="vendor-tab <?php echo $active_tab === 'statistics' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i> Statistiken
            </a>
            <a href="?page=vendor-bereich&tab=settings" class="vendor-tab <?php echo $active_tab === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Einstellungen
            </a>
        </div>
        
        <!-- Tab Content -->
        <div class="vendor-tab-content">
            <?php
            switch ($active_tab) {
                case 'overview':
                    include __DIR__ . '/vendor/overview.php';
                    break;
                case 'templates':
                    include __DIR__ . '/vendor/templates.php';
                    break;
                case 'statistics':
                    include __DIR__ . '/vendor/statistics.php';
                    break;
                case 'settings':
                    include __DIR__ . '/vendor/settings.php';
                    break;
                default:
                    include __DIR__ . '/vendor/overview.php';
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!$is_vendor): ?>
<!-- Aktivierungs-Modal -->
<div class="modal" id="activationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Vendor-Modus aktivieren</h3>
            <button class="modal-close" onclick="closeActivationModal()">×</button>
        </div>
        
        <div class="error-message" id="errorMessage"></div>
        
        <form id="activationForm" onsubmit="submitActivation(event)">
            <div class="form-group">
                <label class="form-label">
                    Firmenname <span class="required">*</span>
                </label>
                <input 
                    type="text" 
                    class="form-input" 
                    name="company_name" 
                    required 
                    minlength="3"
                    placeholder="Ihre Firma GmbH"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Website</label>
                <input 
                    type="url" 
                    class="form-input" 
                    name="website" 
                    placeholder="https://ihre-website.de"
                >
            </div>
            
            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <textarea 
                    class="form-textarea" 
                    name="description"
                    placeholder="Kurze Beschreibung Ihres Unternehmens..."
                ></textarea>
            </div>
            
            <div class="form-group">
                <div class="form-checkbox-wrapper">
                    <input 
                        type="checkbox" 
                        class="form-checkbox" 
                        id="legalConfirm" 
                        required
                    >
                    <label for="legalConfirm" class="form-checkbox-label">
                        Ich bestätige, dass ich ein gültiges Impressum und eine Datenschutzerklärung habe 
                        und alle rechtlichen Anforderungen erfülle.
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeActivationModal()">
                    Abbrechen
                </button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-check"></i> Vendor-Modus aktivieren
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openActivationModal() {
    document.getElementById('activationModal').classList.add('show');
}

function closeActivationModal() {
    document.getElementById('activationModal').classList.remove('show');
    document.getElementById('activationForm').reset();
    document.getElementById('errorMessage').classList.remove('show');
}

async function submitActivation(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = document.getElementById('submitBtn');
    const errorMessage = document.getElementById('errorMessage');
    
    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Aktiviere...';
    
    // Hide previous errors
    errorMessage.classList.remove('show');
    
    // Collect form data
    const formData = {
        company_name: form.company_name.value,
        website: form.website.value,
        description: form.description.value
    };
    
    try {
        const response = await fetch('/api/vendor/activate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Success - reload page
            window.location.reload();
        } else {
            // Show errors
            if (data.errors && Array.isArray(data.errors)) {
                errorMessage.innerHTML = data.errors.join('<br>');
            } else {
                errorMessage.textContent = data.error || 'Ein Fehler ist aufgetreten';
            }
            errorMessage.classList.add('show');
            
            // Re-enable button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Vendor-Modus aktivieren';
        }
    } catch (error) {
        console.error('Error:', error);
        errorMessage.textContent = 'Ein unerwarteter Fehler ist aufgetreten';
        errorMessage.classList.add('show');
        
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Vendor-Modus aktivieren';
    }
}

// Close modal on outside click
document.getElementById('activationModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeActivationModal();
    }
});
</script>
<?php endif; ?>