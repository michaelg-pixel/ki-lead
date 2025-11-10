<?php
// Marktplatz Section für Customer Dashboard
global $pdo;

if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Nischen-Kategorien Labels
$nicheLabels = [
    'online-business' => '💼 Online Business & Marketing',
    'gesundheit-fitness' => '💪 Gesundheit & Fitness',
    'persoenliche-entwicklung' => '🧠 Persönliche Entwicklung',
    'finanzen-investment' => '💰 Finanzen & Investment',
    'immobilien' => '🏠 Immobilien',
    'ecommerce-dropshipping' => '🛒 E-Commerce & Dropshipping',
    'affiliate-marketing' => '📈 Affiliate Marketing',
    'social-media-marketing' => '📱 Social Media Marketing',
    'ki-automation' => '🤖 KI & Automation',
    'coaching-consulting' => '👔 Coaching & Consulting',
    'spiritualitaet-mindfulness' => '✨ Spiritualität & Mindfulness',
    'beziehungen-dating' => '❤️ Beziehungen & Dating',
    'eltern-familie' => '👨‍👩‍👧 Eltern & Familie',
    'karriere-beruf' => '🎯 Karriere & Beruf',
    'hobbys-freizeit' => '🎨 Hobbys & Freizeit',
    'sonstiges' => '📂 Sonstiges'
];

// AJAX Handler für Marktplatz-Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'update_marketplace') {
            $freebie_id = (int)$_POST['freebie_id'];
            $enabled = (int)$_POST['enabled'];
            $price = floatval($_POST['price']);
            $digistore_id = (int)$_POST['digistore_id'];
            $description = trim($_POST['description']);
            
            // Verify ownership
            $stmt = $pdo->prepare("SELECT id FROM customer_freebies WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'");
            $stmt->execute([$freebie_id, $customer_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Freebie nicht gefunden');
            }
            
            $stmt = $pdo->prepare("
                UPDATE customer_freebies 
                SET marketplace_enabled = ?,
                    marketplace_price = ?,
                    digistore_product_id = ?,
                    marketplace_description = ?
                WHERE id = ? AND customer_id = ?
            ");
            
            $stmt->execute([
                $enabled,
                $price,
                $digistore_id,
                $description,
                $freebie_id,
                $customer_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Marktplatz-Einstellungen gespeichert!']);
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// NUR eigene Custom Freebies laden
try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            headline,
            subheadline,
            mockup_image_url,
            background_color,
            primary_color,
            niche,
            marketplace_enabled,
            marketplace_price,
            digistore_product_id,
            marketplace_description,
            course_lessons_count,
            course_duration,
            marketplace_sales_count,
            freebie_type,
            created_at
        FROM customer_freebies 
        WHERE customer_id = ? 
        AND freebie_type = 'custom'
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$customer_id]);
    $my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_freebies = [];
    $error = $e->getMessage();
}
?>

<style>
.marketplace-container {
    padding: 32px;
    max-width: 1800px;
    margin: 0 auto;
    background: #f9fafb;
    min-height: 100vh;
}

.marketplace-header {
    margin-bottom: 32px;
}

.marketplace-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.marketplace-header p {
    font-size: 16px;
    color: #666;
    line-height: 1.6;
}

.marketplace-info-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 32px;
}

.marketplace-info-box h3 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 12px;
}

.marketplace-info-box p {
    font-size: 14px;
    line-height: 1.6;
    opacity: 0.95;
}

.freebies-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 24px;
}

.freebie-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.freebie-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.marketplace-status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.marketplace-status-badge.active {
    background: #10b981;
    color: white;
}

.marketplace-status-badge.inactive {
    background: #ef4444;
    color: white;
}

.freebie-preview {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.freebie-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.freebie-content {
    padding: 16px;
}

.freebie-niche {
    display: inline-block;
    font-size: 11px;
    padding: 3px 8px;
    background: #f3f4f6;
    border-radius: 10px;
    margin-bottom: 8px;
    font-weight: 500;
}

.freebie-content h3 {
    font-size: 16px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.freebie-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    font-size: 13px;
}

.stat-price {
    color: #10b981;
    font-weight: 600;
}

.stat-sales {
    color: #666;
}

.btn-edit {
    width: 100%;
    margin-top: 12px;
    padding: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9998;
    backdrop-filter: blur(4px);
}

.modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease;
}

.marketplace-modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        transform: translateY(30px);
        opacity: 0;
    }
    to { 
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #1a1a1a;
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 52px;
    height: 28px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #cbd5e0;
    transition: 0.3s;
    border-radius: 28px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 22px;
    width: 22px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #10b981;
}

input:checked + .slider:before {
    transform: translateX(24px);
}

.toggle-switch label {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
}

.thankyou-link-box {
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
}

.thankyou-link-box label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.link-copy-wrapper {
    display: flex;
    gap: 8px;
}

.link-copy-wrapper input {
    flex: 1;
    padding: 10px 12px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Courier New', monospace;
}

.btn-copy-link {
    padding: 10px 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.btn-copy-link:hover {
    background: #059669;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
}

.btn-cancel {
    flex: 1;
    padding: 12px;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background: #e5e7eb;
}

.btn-save {
    flex: 2;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.empty-state p {
    font-size: 14px;
    color: #666;
    margin-bottom: 24px;
}

.btn-create-freebie {
    display: inline-block;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-create-freebie:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.marketplace-footer {
    margin-top: 48px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    font-size: 13px;
    color: #666;
}

.marketplace-footer a {
    color: #667eea;
    text-decoration: none;
    margin: 0 12px;
}

.marketplace-footer a:hover {
    text-decoration: underline;
}

@media (max-width: 1400px) {
    .freebies-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .freebies-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .marketplace-container {
        padding: 20px;
    }
    
    .freebies-grid {
        grid-template-columns: 1fr;
    }
    
    .marketplace-header h1 {
        font-size: 24px;
    }
    
    .marketplace-modal {
        width: 95%;
        max-height: 95vh;
    }
}
</style>

<div class="marketplace-container">
    <div class="marketplace-header">
        <h1>🏪 Marktplatz</h1>
        <p>Verkaufe deine eigenen Freebies an andere Marketer und generiere passives Einkommen</p>
    </div>

    <div class="marketplace-info-box">
        <h3>💡 So funktioniert's</h3>
        <p>
            1️⃣ Erstelle ein Custom Freebie<br>
            2️⃣ Aktiviere es für den Marktplatz und lege einen Preis fest<br>
            3️⃣ Verknüpfe es mit deiner Digistore24 Produkt-ID<br>
            4️⃣ Nutze den Thank You Link für die Produktauslieferung<br>
            5️⃣ Nach dem Kauf landet das Freebie automatisch im Account des Käufers
        </p>
    </div>

    <?php if (empty($my_freebies)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>Noch keine Custom Freebies erstellt</h3>
            <p>Erstelle zuerst ein Custom Freebie, um es auf dem Marktplatz anzubieten.<br>
            Template-Freebies können nicht verkauft werden.</p>
            <a href="?page=freebies" class="btn-create-freebie">
                ➕ Erstes Custom Freebie erstellen
            </a>
        </div>
    <?php else: ?>
        <div class="freebies-grid">
            <?php foreach ($my_freebies as $freebie): ?>
                <div class="freebie-card" onclick="openMarketplaceModal(<?= $freebie['id'] ?>)">
                    <div class="marketplace-status-badge <?= $freebie['marketplace_enabled'] ? 'active' : 'inactive' ?>">
                        <?= $freebie['marketplace_enabled'] ? '✓ Aktiv' : '○ Inaktiv' ?>
                    </div>
                    
                    <div class="freebie-preview" style="background: <?= htmlspecialchars($freebie['background_color'] ?? '#667eea') ?>">
                        <?php if (!empty($freebie['mockup_image_url'])): ?>
                            <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" alt="Freebie Preview">
                        <?php endif; ?>
                    </div>

                    <div class="freebie-content">
                        <?php if (!empty($freebie['niche'])): ?>
                            <span class="freebie-niche">
                                <?= $nicheLabels[$freebie['niche']] ?? htmlspecialchars($freebie['niche']) ?>
                            </span>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($freebie['headline']) ?></h3>

                        <div class="freebie-stats">
                            <span class="stat-price">💰 <?= number_format($freebie['marketplace_price'] ?? 0, 2) ?>€</span>
                            <span class="stat-sales">📊 <?= (int)($freebie['marketplace_sales_count'] ?? 0) ?></span>
                        </div>
                        
                        <button class="btn-edit" onclick="event.stopPropagation(); openMarketplaceModal(<?= $freebie['id'] ?>)">
                            ⚙️ Einstellungen
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="marketplace-footer">
        <p>
            © <?= date('Y') ?> KI Leadsystem • 
            <a href="https://mehr-infos-jetzt.de/impressum" target="_blank">Impressum</a> • 
            <a href="https://mehr-infos-jetzt.de/datenschutz" target="_blank">Datenschutz</a>
        </p>
    </div>
</div>

<!-- Marketplace Modal -->
<div class="modal-overlay" id="marketplaceModal" onclick="if(event.target === this) closeMarketplaceModal()">
    <div class="marketplace-modal">
        <div class="modal-header">
            <h2>🏪 Marktplatz-Einstellungen</h2>
            <button class="modal-close" onclick="closeMarketplaceModal()">×</button>
        </div>
        
        <form id="marketplaceForm" onsubmit="saveMarketplaceSettings(event)">
            <div class="modal-body">
                <div class="toggle-switch">
                    <label class="switch">
                        <input type="checkbox" id="modalMarketplaceEnabled" name="marketplace_enabled" onchange="toggleModalFields()">
                        <span class="slider"></span>
                    </label>
                    <label for="modalMarketplaceEnabled">Auf Marktplatz anbieten</label>
                </div>

                <div id="modalMarketplaceFields" style="display: none;">
                    <div class="form-group">
                        <label>💰 Verkaufspreis (in €)</label>
                        <input type="number" 
                               id="modalPrice"
                               name="marketplace_price" 
                               step="0.01" 
                               min="0" 
                               placeholder="9.99">
                    </div>

                    <div class="form-group">
                        <label>🔗 DigiStore24 Produkt-ID</label>
                        <input type="number" 
                               id="modalDigistoreId"
                               name="digistore_product_id" 
                               placeholder="z.B. 613818">
                    </div>

                    <div class="form-group">
                        <label>📝 Marktplatz-Beschreibung</label>
                        <textarea id="modalDescription"
                                  name="marketplace_description" 
                                  placeholder="Beschreibe dein Freebie für potenzielle Käufer..."></textarea>
                    </div>

                    <div class="thankyou-link-box">
                        <label>🎁 Thank You Link für DigiStore24</label>
                        <p style="font-size: 12px; color: #666; margin-bottom: 8px;">
                            Kopiere diesen Link und füge ihn in deine DigiStore24 Produkteinstellungen ein
                        </p>
                        <div class="link-copy-wrapper">
                            <input type="text" 
                                   readonly 
                                   id="modalThankYouLink">
                            <button type="button" 
                                    class="btn-copy-link" 
                                    onclick="copyModalThankYouLink()">
                                📋 Kopieren
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeMarketplaceModal()">
                    Abbrechen
                </button>
                <button type="submit" class="btn-save">
                    💾 Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Freebie-Daten für Modal
const freebiesData = <?= json_encode($my_freebies) ?>;
let currentFreebieId = null;

function openMarketplaceModal(freebieId) {
    currentFreebieId = freebieId;
    const freebie = freebiesData.find(f => f.id == freebieId);
    
    if (!freebie) return;
    
    // Felder füllen
    document.getElementById('modalMarketplaceEnabled').checked = freebie.marketplace_enabled == 1;
    document.getElementById('modalPrice').value = freebie.marketplace_price || '9.99';
    document.getElementById('modalDigistoreId').value = freebie.digistore_product_id || '';
    document.getElementById('modalDescription').value = freebie.marketplace_description || '';
    
    // Thank You Link
    const digistoreId = freebie.digistore_product_id || 'DEINE_PRODUKT_ID';
    document.getElementById('modalThankYouLink').value = 
        `https://app.mehr-infos-jetzt.de/public/marketplace-thankyou.php?product_id=${digistoreId}&freebie_id=${freebieId}`;
    
    // Felder anzeigen/verstecken
    toggleModalFields();
    
    // Modal öffnen
    document.getElementById('marketplaceModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMarketplaceModal() {
    document.getElementById('marketplaceModal').classList.remove('active');
    document.body.style.overflow = '';
    currentFreebieId = null;
}

function toggleModalFields() {
    const enabled = document.getElementById('modalMarketplaceEnabled').checked;
    const fields = document.getElementById('modalMarketplaceFields');
    fields.style.display = enabled ? 'block' : 'none';
}

async function saveMarketplaceSettings(event) {
    event.preventDefault();
    
    if (!currentFreebieId) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        action: 'update_marketplace',
        freebie_id: currentFreebieId,
        enabled: form.marketplace_enabled.checked ? 1 : 0,
        price: formData.get('marketplace_price') || 0,
        digistore_id: formData.get('digistore_product_id') || 0,
        description: formData.get('marketplace_description') || ''
    };
    
    const submitBtn = form.querySelector('.btn-save');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = '💾 Speichere...';
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('?page=marktplatz', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            submitBtn.textContent = '✓ Gespeichert!';
            
            // Update data in array
            const freebie = freebiesData.find(f => f.id == currentFreebieId);
            if (freebie) {
                freebie.marketplace_enabled = data.enabled;
                freebie.marketplace_price = data.price;
                freebie.digistore_product_id = data.digistore_id;
                freebie.marketplace_description = data.description;
            }
            
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                closeMarketplaceModal();
                location.reload(); // Reload to show updated cards
            }, 1000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

function copyModalThankYouLink() {
    const input = document.getElementById('modalThankYouLink');
    input.select();
    document.execCommand('copy');
    
    const btn = event.target;
    const originalText = btn.textContent;
    btn.textContent = '✓ Kopiert!';
    btn.style.background = '#059669';
    
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.background = '';
    }, 2000);
}

// ESC-Taste zum Schließen
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMarketplaceModal();
    }
});
</script>
