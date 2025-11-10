<?php
// Marktplatz Section für Customer Dashboard
global $pdo;

if (!isset($pdo)) {
    require_once '../config/database.php';
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
    max-width: 1600px;
    margin: 0 auto;
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
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.freebie-marketplace-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.freebie-marketplace-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.freebie-preview {
    position: relative;
    height: 240px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.freebie-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.marketplace-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(255, 255, 255, 0.95);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.marketplace-badge.active {
    background: #10b981;
    color: white;
}

.marketplace-badge.inactive {
    background: #ef4444;
    color: white;
}

.freebie-content {
    padding: 20px;
}

.freebie-niche {
    display: inline-block;
    font-size: 12px;
    padding: 4px 10px;
    background: #f3f4f6;
    border-radius: 12px;
    margin-bottom: 12px;
    font-weight: 500;
}

.freebie-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
    line-height: 1.4;
}

.freebie-content p {
    font-size: 14px;
    color: #666;
    line-height: 1.5;
    margin-bottom: 16px;
}

.marketplace-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: #666;
}

.marketplace-form {
    border-top: 1px solid #e5e7eb;
    padding-top: 16px;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
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
    min-height: 80px;
    resize: vertical;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
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
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 20px;
    width: 20px;
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
    transform: translateX(22px);
}

.toggle-switch label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.btn-save-marketplace {
    width: 100%;
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

.btn-save-marketplace:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.thankyou-link-box {
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.thankyou-link-box label {
    display: block;
    font-size: 13px;
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

.empty-state {
    text-align: center;
    padding: 80px 20px;
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
            <h3>Noch keine Freebies erstellt</h3>
            <p>Erstelle zuerst ein Custom Freebie, um es auf dem Marktplatz anzubieten</p>
            <a href="?page=freebies" class="btn-create-freebie">
                ➕ Erstes Freebie erstellen
            </a>
        </div>
    <?php else: ?>
        <div class="freebies-grid">
            <?php foreach ($my_freebies as $freebie): ?>
                <div class="freebie-marketplace-card">
                    <div class="freebie-preview" style="background: <?= htmlspecialchars($freebie['background_color'] ?? '#667eea') ?>">
                        <?php if (!empty($freebie['mockup_image_url'])): ?>
                            <img src="<?= htmlspecialchars($freebie['mockup_image_url']) ?>" alt="Freebie Preview">
                        <?php endif; ?>
                        
                        <div class="marketplace-badge <?= $freebie['marketplace_enabled'] ? 'active' : 'inactive' ?>">
                            <?= $freebie['marketplace_enabled'] ? '✓ Aktiv' : '○ Inaktiv' ?>
                        </div>
                    </div>

                    <div class="freebie-content">
                        <?php if (!empty($freebie['niche'])): ?>
                            <span class="freebie-niche">
                                <?= $nicheLabels[$freebie['niche']] ?? htmlspecialchars($freebie['niche']) ?>
                            </span>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($freebie['headline']) ?></h3>
                        
                        <?php if (!empty($freebie['subheadline'])): ?>
                            <p><?= htmlspecialchars($freebie['subheadline']) ?></p>
                        <?php endif; ?>

                        <?php if ($freebie['marketplace_enabled']): ?>
                            <div class="marketplace-stats">
                                <div class="stat-item">
                                    💰 <?= number_format($freebie['marketplace_price'] ?? 0, 2) ?>€
                                </div>
                                <div class="stat-item">
                                    📊 <?= (int)($freebie['marketplace_sales_count'] ?? 0) ?> Verkäufe
                                </div>
                            </div>
                        <?php endif; ?>

                        <form class="marketplace-form" onsubmit="saveMarketplaceSettings(event, <?= $freebie['id'] ?>)">
                            <div class="toggle-switch">
                                <label class="switch">
                                    <input type="checkbox" 
                                           name="marketplace_enabled" 
                                           <?= $freebie['marketplace_enabled'] ? 'checked' : '' ?>
                                           onchange="toggleMarketplaceFields(this, <?= $freebie['id'] ?>)">
                                    <span class="slider"></span>
                                </label>
                                <label>Auf Marktplatz anbieten</label>
                            </div>

                            <div id="marketplace-fields-<?= $freebie['id'] ?>" style="<?= $freebie['marketplace_enabled'] ? '' : 'display: none;' ?>">
                                <div class="form-group">
                                    <label>💰 Verkaufspreis (in €)</label>
                                    <input type="number" 
                                           name="marketplace_price" 
                                           step="0.01" 
                                           min="0" 
                                           value="<?= htmlspecialchars($freebie['marketplace_price'] ?? '9.99') ?>" 
                                           placeholder="9.99">
                                </div>

                                <div class="form-group">
                                    <label>🔗 Digistore24 Produkt-ID</label>
                                    <input type="number" 
                                           name="digistore_product_id" 
                                           value="<?= htmlspecialchars($freebie['digistore_product_id'] ?? '') ?>" 
                                           placeholder="z.B. 12345">
                                </div>

                                <div class="form-group">
                                    <label>📝 Marktplatz-Beschreibung</label>
                                    <textarea name="marketplace_description" 
                                              placeholder="Beschreibe dein Freebie für potenzielle Käufer..."><?= htmlspecialchars($freebie['marketplace_description'] ?? '') ?></textarea>
                                </div>

                                <div class="thankyou-link-box">
                                    <label>🎁 Thank You Link für Digistore24</label>
                                    <p style="font-size: 12px; color: #666; margin-bottom: 8px;">
                                        Kopiere diesen Link und füge ihn in deine Digistore24 Produkteinstellungen ein
                                    </p>
                                    <div class="link-copy-wrapper">
                                        <input type="text" 
                                               readonly 
                                               id="thankyou-link-<?= $freebie['id'] ?>"
                                               value="https://app.mehr-infos-jetzt.de/public/marketplace-thankyou.php?product_id=<?= $freebie['digistore_product_id'] ?? 'DEINE_PRODUKT_ID' ?>&freebie_id=<?= $freebie['id'] ?>">
                                        <button type="button" 
                                                class="btn-copy-link" 
                                                onclick="copyThankYouLink(<?= $freebie['id'] ?>)">
                                            📋 Kopieren
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn-save-marketplace">
                                💾 Einstellungen speichern
                            </button>
                        </form>
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

<script>
function toggleMarketplaceFields(checkbox, freebieId) {
    const fields = document.getElementById(`marketplace-fields-${freebieId}`);
    if (checkbox.checked) {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}

async function saveMarketplaceSettings(event, freebieId) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        action: 'update_marketplace',
        freebie_id: freebieId,
        enabled: form.marketplace_enabled.checked ? 1 : 0,
        price: formData.get('marketplace_price') || 0,
        digistore_id: formData.get('digistore_product_id') || 0,
        description: formData.get('marketplace_description') || ''
    };
    
    const submitBtn = form.querySelector('button[type="submit"]');
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
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 2000);
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        alert('Fehler beim Speichern: ' + error.message);
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }
}

function copyThankYouLink(freebieId) {
    const input = document.getElementById(`thankyou-link-${freebieId}`);
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
</script>
