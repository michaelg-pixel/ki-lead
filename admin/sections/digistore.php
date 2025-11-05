<?php
/**
 * Digistore24 Webhook-Zentrale
 * Admin kann hier Produkt-IDs hinterlegen und Produkte verwalten
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert');
}

// Erfolgsmeldungen
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Produkte aus DB laden
$products = $pdo->query("
    SELECT * FROM digistore_products 
    ORDER BY FIELD(product_type, 'launch', 'starter', 'pro', 'business', 'custom')
")->fetchAll();

// Statistiken
$activeProducts = $pdo->query("SELECT COUNT(*) FROM digistore_products WHERE is_active = 1")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$webhookUrl = 'https://app.ki-leadsystem.com/webhook/digistore24.php';
?>

<style>
.digistore-container {
    padding: 30px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-success {
    background: #d1fae5;
    border-left: 4px solid #10b981;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border-left: 4px solid #ef4444;
    color: #991b1b;
}

.webhook-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
}

.webhook-info h3 {
    margin: 0 0 16px 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.webhook-url {
    background: rgba(255, 255, 255, 0.2);
    padding: 12px 16px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 12px;
}

.copy-btn {
    background: rgba(255, 255, 255, 0.3);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.copy-btn:hover {
    background: rgba(255, 255, 255, 0.4);
    transform: scale(1.05);
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.stat-box {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.stat-box h4 {
    margin: 0 0 8px 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.stat-box .stat-value {
    font-size: 32px;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.products-grid {
    display: grid;
    gap: 24px;
}

.product-card {
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s;
}

.product-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
}

.product-card:hover::before {
    opacity: 1;
}

.product-card.inactive {
    opacity: 0.6;
    background: #f9fafb;
}

.product-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.product-title {
    flex: 1;
}

.product-title h3 {
    margin: 0 0 8px 0;
    font-size: 22px;
    color: #1f2937;
}

.product-type {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.type-launch {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

.type-starter {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
}

.type-pro {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    color: white;
}

.type-business {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.status-badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.product-price {
    font-size: 28px;
    font-weight: bold;
    color: #1f2937;
    margin: 16px 0;
}

.product-price small {
    font-size: 16px;
    color: #6b7280;
    font-weight: normal;
}

.product-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin: 20px 0;
}

.feature-box {
    background: #f9fafb;
    padding: 16px;
    border-radius: 10px;
    text-align: center;
    border: 1px solid #e5e7eb;
}

.feature-box .feature-value {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 4px;
}

.feature-box .feature-label {
    font-size: 12px;
    color: #6b7280;
    line-height: 1.4;
}

.product-form {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px solid #f3f4f6;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
}

.form-group input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.2s;
    font-family: 'Courier New', monospace;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group input:disabled {
    background: #f9fafb;
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.btn-secondary {
    background: #f3f4f6;
    color: #4b5563;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #e5e7eb;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-state p {
    color: #6b7280;
    font-size: 16px;
}

.help-text {
    background: #f0f9ff;
    border-left: 4px solid #3b82f6;
    padding: 16px 20px;
    border-radius: 8px;
    margin: 24px 0;
    font-size: 14px;
    color: #1e40af;
}

.help-text strong {
    display: block;
    margin-bottom: 8px;
    color: #1e3a8a;
}

@media (max-width: 768px) {
    .digistore-container {
        padding: 20px;
    }
    
    .product-features {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="digistore-container">
    <?php if ($success === 'updated'): ?>
        <div class="alert alert-success">
            <span style="font-size: 24px;">✅</span>
            <span><strong>Erfolgreich!</strong> Das Produkt wurde aktualisiert.</span>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <span style="font-size: 24px;">❌</span>
            <span><strong>Fehler:</strong> <?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Webhook Info -->
    <div class="webhook-info">
        <h3>
            <span style="font-size: 28px;">🔗</span>
            Webhook-URL für Digistore24
        </h3>
        <p style="margin: 0 0 12px 0; opacity: 0.95;">
            Kopiere diese URL und füge sie in deinem Digistore24 Produkt unter <strong>"IPN Settings"</strong> ein:
        </p>
        <div class="webhook-url">
            <code id="webhookUrl"><?php echo $webhookUrl; ?></code>
            <button class="copy-btn" onclick="copyWebhookUrl()">📋 Kopieren</button>
        </div>
    </div>
    
    <!-- Statistiken -->
    <div class="stats-row">
        <div class="stat-box">
            <h4>Aktive Produkte</h4>
            <div class="stat-value"><?php echo $activeProducts; ?></div>
        </div>
        <div class="stat-box">
            <h4>Gesamtkunden</h4>
            <div class="stat-value"><?php echo $totalCustomers; ?></div>
        </div>
        <div class="stat-box">
            <h4>Webhook Status</h4>
            <div class="stat-value">✅</div>
        </div>
    </div>
    
    <div class="help-text">
        <strong>📖 Anleitung:</strong>
        1. Trage unten bei jedem Produkt die Digistore24 Produkt-ID ein<br>
        2. Aktiviere das Produkt mit dem Schalter<br>
        3. Speichere die Änderungen<br>
        4. Der Webhook erkennt automatisch welches Produkt gekauft wurde und schaltet die entsprechenden Features frei!
    </div>
    
    <!-- Produkte -->
    <div class="products-grid">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🛒</div>
                <p>Keine Produkte gefunden.</p>
                <p style="font-size: 14px; margin-top: 12px;">
                    <a href="/database/setup-digistore-products.php" style="color: #667eea;">→ Datenbank einrichten</a>
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product-card <?php echo $product['is_active'] ? '' : 'inactive'; ?>">
                    <form method="POST" action="/api/digistore-update.php" onsubmit="return confirm('Änderungen speichern?');">
                        <input type="hidden" name="product_db_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="product-header">
                            <div class="product-title">
                                <h3><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <span class="product-type type-<?php echo $product['product_type']; ?>">
                                    <?php echo strtoupper($product['product_type']); ?>
                                </span>
                            </div>
                            <span class="status-badge <?php echo $product['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $product['is_active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                            </span>
                        </div>
                        
                        <div class="product-price">
                            <?php echo number_format($product['price'], 2, ',', '.'); ?> €
                            <small>
                                <?php 
                                    $billingTexts = [
                                        'one_time' => 'einmalig',
                                        'monthly' => 'pro Monat',
                                        'yearly' => 'pro Jahr'
                                    ];
                                    echo $billingTexts[$product['billing_type']] ?? $product['billing_type'];
                                ?>
                            </small>
                        </div>
                        
                        <div class="product-features">
                            <div class="feature-box">
                                <div class="feature-value"><?php echo $product['own_freebies_limit']; ?></div>
                                <div class="feature-label">Eigene Freebies</div>
                            </div>
                            
                            <?php if ($product['ready_freebies_count'] > 0): ?>
                            <div class="feature-box">
                                <div class="feature-value"><?php echo $product['ready_freebies_count']; ?></div>
                                <div class="feature-label">Fertige Freebies</div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="feature-box">
                                <div class="feature-value"><?php echo $product['referral_program_slots']; ?></div>
                                <div class="feature-label">Empfehlungs-Slots</div>
                            </div>
                        </div>
                        
                        <div class="product-form">
                            <div class="form-group">
                                <label for="product_id_<?php echo $product['id']; ?>">
                                    🔑 Digistore24 Produkt-ID
                                </label>
                                <input 
                                    type="text" 
                                    name="product_id" 
                                    id="product_id_<?php echo $product['id']; ?>"
                                    value="<?php echo htmlspecialchars($product['product_id']); ?>"
                                    placeholder="z.B. 123456 oder PRODUCT_2024"
                                    required
                                >
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_active" value="1" 
                                           <?php echo $product['is_active'] ? 'checked' : ''; ?>
                                           style="width: auto; margin-right: 8px;">
                                    Produkt aktivieren (Webhook verarbeitet dieses Produkt)
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    💾 Speichern
                                </button>
                                
                                <?php if ($product['is_active']): ?>
                                    <a href="/webhook/test-digistore.php?product_id=<?php echo urlencode($product['product_id']); ?>" 
                                       class="btn btn-secondary" target="_blank">
                                        🧪 Webhook testen
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function copyWebhookUrl() {
    const url = document.getElementById('webhookUrl').textContent;
    navigator.clipboard.writeText(url).then(() => {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '✅ Kopiert!';
        btn.style.background = 'rgba(16, 185, 129, 0.3)';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '';
        }, 2000);
    });
}
</script>
