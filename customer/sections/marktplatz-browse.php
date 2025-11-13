<?php
// Marktplatz Browse Section - ERWEITERT mit Belohnungs-Templates
global $pdo;

if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Lade alle Freebies des Kunden für Import-Auswahl
$customerFreebies = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id,
            COALESCE(cf.headline, f.headline, f.name) as title
        FROM customer_freebies cf
        LEFT JOIN freebies f ON cf.template_id = f.id
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$customer_id]);
    $customerFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Load Customer Freebies Error: " . $e->getMessage());
}

// Nischen-Kategorien
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

// Belohnungs-Kategorien
$rewardCategories = [
    'ebook' => '📚 E-Book',
    'pdf' => '📄 PDF',
    'consultation' => '🎯 Beratung',
    'course' => '🎓 Kurs',
    'voucher' => '🎫 Gutschein',
    'discount' => '💸 Rabatt',
    'other' => '🎁 Sonstiges'
];
?>

<style>
.marketplace-browse-container {
    padding: 32px;
    max-width: 1800px;
    margin: 0 auto;
    min-height: 100vh;
}

.marketplace-browse-header {
    margin-bottom: 24px;
}

.marketplace-browse-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
}

.marketplace-browse-header p {
    font-size: 16px;
    color: #a0aec0;
}

/* TAB SYSTEM */
.marketplace-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    border-bottom: 2px solid rgba(255,255,255,0.1);
    padding-bottom: 0;
}

.marketplace-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    position: relative;
}

.marketplace-tab:hover {
    color: #ffffff;
    background: rgba(255,255,255,0.05);
}

.marketplace-tab.active {
    color: #ffffff;
    border-bottom-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.2) 0%, rgba(118, 75, 162, 0.2) 100%);
}

.filters-bar {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: grid;
    grid-template-columns: 1fr 300px 200px;
    gap: 16px;
    border: 1px solid rgba(255,255,255,0.1);
}

.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 14px;
    background: rgba(255,255,255,0.05);
    color: #ffffff;
    transition: all 0.2s;
}

.search-box input:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255,255,255,0.08);
}

.search-box::before {
    content: '🔍';
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    pointer-events: none;
}

.filter-select {
    padding: 12px 16px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    background: rgba(255,255,255,0.05);
    color: #ffffff;
    transition: all 0.2s;
}

.filter-select:focus {
    outline: none;
    border-color: #667eea;
    background: rgba(255,255,255,0.08);
}

.filter-select option {
    background: #1a1a2e;
    color: #ffffff;
}

.marketplace-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.marketplace-item {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.1);
}

.marketplace-item:hover {
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    transform: translateY(-4px);
    border-color: rgba(102, 126, 234, 0.5);
}

.marketplace-item.imported {
    border-color: rgba(16, 185, 129, 0.5);
}

.item-preview {
    position: relative;
    height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow: hidden;
}

.item-preview img {
    max-width: 90%;
    max-height: 90%;
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: center;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

.item-price-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #10b981;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 700;
    backdrop-filter: blur(10px);
    z-index: 10;
}

.item-niche-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(102, 126, 234, 0.95);
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    z-index: 10;
}

.item-imported-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: rgba(16, 185, 129, 0.95);
    color: white;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
    backdrop-filter: blur(10px);
    z-index: 10;
}

.item-content {
    padding: 20px;
}

.item-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.item-description {
    font-size: 14px;
    color: #a0aec0;
    line-height: 1.5;
    margin-bottom: 16px;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.item-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-bottom: 16px;
    padding: 12px;
    background: rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    font-size: 12px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #a0aec0;
}

.meta-icon {
    font-size: 14px;
}

.item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 12px;
    color: #a0aec0;
}

.item-seller {
    display: flex;
    align-items: center;
    gap: 6px;
}

.item-price {
    font-size: 24px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 12px;
}

.btn-view-details {
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

.btn-view-details:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

/* REWARD DETAIL MODAL */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 10000;
    overflow-y: auto;
    padding: 2rem 1rem;
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: linear-gradient(to bottom right, #1f2937, #374151);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 800px;
    width: 100%;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 2rem;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    flex: 1;
}

.modal-close {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    line-height: 1;
}

.modal-close:hover {
    color: #ef4444;
}

.reward-detail-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-section {
    background: rgba(0, 0, 0, 0.2);
    padding: 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(255,255,255,0.1);
}

.detail-section h3 {
    color: #ffffff;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #9ca3af;
    font-size: 0.875rem;
}

.detail-value {
    color: #ffffff;
    font-weight: 600;
    font-size: 0.875rem;
}

.import-section {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    padding: 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(102, 126, 234, 0.3);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    color: #ffffff;
    font-weight: 500;
    margin-bottom: 0.5rem;
    font-size: 0.9375rem;
}

.form-select {
    width: 100%;
    padding: 0.75rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 0.5rem;
    color: #ffffff;
    font-size: 0.9375rem;
}

.form-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-hint {
    color: #9ca3af;
    font-size: 0.8125rem;
    margin-top: 0.5rem;
}

.btn-import {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-import:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}

.btn-import:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.empty-marketplace {
    text-align: center;
    padding: 80px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
}

.empty-marketplace-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-marketplace h3 {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
}

.empty-marketplace p {
    font-size: 14px;
    color: #a0aec0;
}

.loading-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
}

.loading-spinner {
    font-size: 48px;
    margin-bottom: 16px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@media (max-width: 1400px) {
    .marketplace-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .marketplace-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters-bar {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .marketplace-browse-container {
        padding: 20px;
    }
    
    .marketplace-grid {
        grid-template-columns: 1fr;
    }
    
    .marketplace-browse-header h1 {
        font-size: 24px;
    }
    
    .marketplace-tabs {
        overflow-x: auto;
    }
    
    .modal-content {
        padding: 1.5rem;
    }
}
</style>

<div class="marketplace-browse-container">
    <!-- Header -->
    <div class="marketplace-browse-header">
        <h1>🛍️ Marktplatz</h1>
        <p>Entdecke professionelle Freebies und Belohnungs-Templates</p>
    </div>
    
    <!-- Tab System -->
    <div class="marketplace-tabs">
        <button class="marketplace-tab active" onclick="switchTab('freebies')" id="tab-freebies">
            📦 Freebies
        </button>
        <button class="marketplace-tab" onclick="switchTab('rewards')" id="tab-rewards">
            🎁 Belohnungen
        </button>
    </div>
    
    <!-- Filters Bar -->
    <div class="filters-bar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Suche..." onkeyup="filterMarketplace()">
        </div>
        
        <select id="categoryFilter" class="filter-select" onchange="filterMarketplace()">
            <option value="">Alle Kategorien</option>
        </select>
        
        <select id="sortFilter" class="filter-select" onchange="filterMarketplace()">
            <option value="newest">Neueste zuerst</option>
            <option value="popular">Beliebteste</option>
            <option value="price_low">Preis aufsteigend</option>
            <option value="price_high">Preis absteigend</option>
        </select>
    </div>
    
    <!-- Marketplace Grid -->
    <div id="marketplaceGrid" class="marketplace-grid">
        <div class="loading-state">
            <div class="loading-spinner">🔄</div>
            <p style="color: #a0aec0;">Lade Marktplatz...</p>
        </div>
    </div>
</div>

<!-- Reward Detail Modal -->
<div id="rewardDetailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="rewardModalTitle"></h2>
            <button class="modal-close" onclick="closeRewardModal()">✕</button>
        </div>
        
        <div id="rewardModalBody" class="reward-detail-grid">
            <!-- Wird dynamisch gefüllt -->
        </div>
    </div>
</div>

<script>
const nicheLabels = <?php echo json_encode($nicheLabels); ?>;
const rewardCategories = <?php echo json_encode($rewardCategories); ?>;
const customerFreebies = <?php echo json_encode($customerFreebies); ?>;

let currentTab = 'freebies';
let allFreebies = [];
let allRewards = [];
let currentData = [];

// Beim Laden der Seite
document.addEventListener('DOMContentLoaded', function() {
    loadMarketplaceData();
});

// Tab wechseln
function switchTab(tab) {
    currentTab = tab;
    
    // Tab-Buttons aktualisieren
    document.querySelectorAll('.marketplace-tab').forEach(btn => {
        btn.classList.remove('active');
    });
    document.getElementById(`tab-${tab}`).classList.add('active');
    
    // Filter-Kategorien aktualisieren
    const categoryFilter = document.getElementById('categoryFilter');
    categoryFilter.innerHTML = '<option value="">Alle Kategorien</option>';
    
    if (tab === 'freebies') {
        Object.entries(nicheLabels).forEach(([value, label]) => {
            categoryFilter.innerHTML += `<option value="${value}">${label}</option>`;
        });
    } else {
        Object.entries(rewardCategories).forEach(([value, label]) => {
            categoryFilter.innerHTML += `<option value="${value}">${label}</option>`;
        });
    }
    
    // Daten laden
    loadMarketplaceData();
}

// Daten laden
function loadMarketplaceData() {
    const grid = document.getElementById('marketplaceGrid');
    grid.innerHTML = '<div class="loading-state"><div class="loading-spinner">🔄</div><p style="color: #a0aec0;">Lade...</p></div>';
    
    const url = currentTab === 'freebies' ? '/api/marketplace-list.php' : '/api/vendor/marketplace-list.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (currentTab === 'freebies') {
                    allFreebies = data.freebies || [];
                    currentData = allFreebies;
                } else {
                    allRewards = data.templates || [];
                    currentData = allRewards;
                }
                filterMarketplace();
            } else {
                showEmptyState('Fehler beim Laden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showEmptyState('Verbindungsfehler');
        });
}

// Filtern
function filterMarketplace() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortFilter').value;
    
    let filtered = currentData.filter(item => {
        const name = currentTab === 'freebies' ? item.headline : item.template_name;
        const desc = currentTab === 'freebies' ? item.marketplace_description : item.template_description;
        const cat = currentTab === 'freebies' ? item.niche : item.category;
        
        const matchesSearch = !searchTerm || 
            name.toLowerCase().includes(searchTerm) ||
            (desc && desc.toLowerCase().includes(searchTerm));
        
        const matchesCategory = !category || cat === category;
        
        return matchesSearch && matchesCategory;
    });
    
    // Sortieren
    filtered.sort((a, b) => {
        switch(sort) {
            case 'popular':
                const aCount = currentTab === 'freebies' ? a.marketplace_sales_count : a.times_imported;
                const bCount = currentTab === 'freebies' ? b.marketplace_sales_count : b.times_imported;
                return (parseInt(bCount) || 0) - (parseInt(aCount) || 0);
            case 'price_low':
                return (parseFloat(a.marketplace_price) || 0) - (parseFloat(b.marketplace_price) || 0);
            case 'price_high':
                return (parseFloat(b.marketplace_price) || 0) - (parseFloat(a.marketplace_price) || 0);
            case 'newest':
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });
    
    displayItems(filtered);
}

// Items anzeigen
function displayItems(items) {
    const grid = document.getElementById('marketplaceGrid');
    
    if (items.length === 0) {
        showEmptyState('Keine Angebote gefunden');
        return;
    }
    
    grid.innerHTML = items.map(item => {
        return currentTab === 'freebies' ? createFreebieCard(item) : createRewardCard(item);
    }).join('');
}

// Freebie-Karte (bestehend)
function createFreebieCard(freebie) {
    const nicheLabel = nicheLabels[freebie.niche] || '📂 Sonstiges';
    const bgColor = freebie.background_color || '#667eea';
    const price = freebie.marketplace_price ? parseFloat(freebie.marketplace_price).toFixed(2).replace('.', ',') + ' €' : 'Kostenlos';
    const imageHtml = freebie.mockup_image_url 
        ? `<img src="${freebie.mockup_image_url}" alt="${freebie.headline}">`
        : '<div style="font-size: 64px;">🎁</div>';
    
    let actionButton = '';
    if (freebie.is_own) {
        actionButton = '<button class="btn-view-details" disabled>👤 Dein eigenes Freebie</button>';
    } else if (freebie.already_purchased) {
        actionButton = '<button class="btn-view-details" disabled style="background: rgba(34, 197, 94, 0.3);">✓ Bereits gekauft</button>';
    } else if (freebie.digistore_product_id) {
        let checkoutUrl = freebie.digistore_product_id;
        if (!checkoutUrl.startsWith('http')) {
            checkoutUrl = `https://www.digistore24.com/product/${freebie.digistore_product_id}`;
        }
        actionButton = `<a href="${checkoutUrl}" target="_blank" class="btn-view-details" style="text-decoration: none; display: block;">💳 Jetzt kaufen</a>`;
    } else {
        actionButton = '<button class="btn-view-details" disabled style="opacity: 0.5;">⚠️ Kein Kauflink</button>';
    }
    
    return `
        <div class="marketplace-item">
            <div class="item-preview" style="background: ${bgColor};">
                <div class="item-niche-badge">${nicheLabel}</div>
                ${freebie.marketplace_price ? `<div class="item-price-badge">${price}</div>` : ''}
                ${imageHtml}
            </div>
            
            <div class="item-content">
                <h3>${freebie.headline}</h3>
                
                ${freebie.marketplace_description ? 
                    `<div class="item-description">${freebie.marketplace_description}</div>` : 
                    ''
                }
                
                ${!freebie.marketplace_price || parseFloat(freebie.marketplace_price) > 0 ? `
                    <div class="item-price">${price}</div>
                ` : ''}
                
                <div class="item-footer">
                    <div class="item-seller">
                        <span>👤</span>
                        <span>${freebie.creator_name}</span>
                    </div>
                    <div>
                        <span>📊 ${freebie.marketplace_sales_count || 0} Verkäufe</span>
                    </div>
                </div>
                
                ${actionButton}
            </div>
        </div>
    `;
}

// Reward-Karte (NEU)
function createRewardCard(reward) {
    const categoryLabel = rewardCategories[reward.category] || '🎁 Sonstiges';
    const bgColor = reward.reward_color || '#667eea';
    const isImported = reward.is_imported_by_me;
    
    return `
        <div class="marketplace-item ${isImported ? 'imported' : ''}" onclick="showRewardDetail(${reward.id})">
            <div class="item-preview" style="background: ${bgColor};">
                <div class="item-niche-badge">${categoryLabel}</div>
                ${isImported ? '<div class="item-imported-badge">✓ Importiert</div>' : ''}
                <div style="font-size: 64px;">
                    <i class="fas ${reward.reward_icon || 'fa-gift'}"></i>
                </div>
            </div>
            
            <div class="item-content">
                <h3>${reward.template_name}</h3>
                
                ${reward.template_description ? 
                    `<div class="item-description">${reward.template_description}</div>` : 
                    ''
                }
                
                <div class="item-meta">
                    <div class="meta-item">
                        <span class="meta-icon">📥</span>
                        <span>${reward.times_imported || 0} Imports</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-icon">👥</span>
                        <span>${reward.suggested_referrals_required || 3} Empf.</span>
                    </div>
                </div>
                
                <div class="item-footer">
                    <div class="item-seller">
                        <span>👤</span>
                        <span>${reward.vendor_name}</span>
                    </div>
                    <div>
                        <span>✓ Kostenlos</span>
                    </div>
                </div>
                
                <button class="btn-view-details" ${isImported ? 'disabled style="opacity: 0.6;"' : ''}>
                    ${isImported ? '✓ Bereits importiert' : '📥 Details & Importieren'}
                </button>
            </div>
        </div>
    `;
}

// Reward Details anzeigen
function showRewardDetail(rewardId) {
    const reward = allRewards.find(r => r.id === rewardId);
    if (!reward || reward.is_imported_by_me) return;
    
    document.getElementById('rewardModalTitle').textContent = reward.template_name;
    
    const modalBody = document.getElementById('rewardModalBody');
    modalBody.innerHTML = `
        <div class="detail-section">
            <h3><i class="fas ${reward.reward_icon || 'fa-gift'}"></i> Belohnungs-Details</h3>
            <div class="detail-row">
                <span class="detail-label">Typ:</span>
                <span class="detail-value">${reward.reward_title}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Wert:</span>
                <span class="detail-value">${reward.reward_value || '-'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Vorgeschlagene Empfehlungen:</span>
                <span class="detail-value">${reward.suggested_referrals_required}</span>
            </div>
            ${reward.reward_description ? `
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <p style="color: #9ca3af; font-size: 0.875rem; line-height: 1.6;">
                        ${reward.reward_description}
                    </p>
                </div>
            ` : ''}
        </div>
        
        <div class="detail-section">
            <h3>👤 Vendor-Info</h3>
            <div class="detail-row">
                <span class="detail-label">Vendor:</span>
                <span class="detail-value">${reward.vendor_name}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Bereits importiert:</span>
                <span class="detail-value">${reward.times_imported} mal</span>
            </div>
        </div>
        
        <div class="import-section">
            <h3 style="color: #ffffff; font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem;">
                📥 In eigenes Belohnungssystem importieren
            </h3>
            
            <form id="importForm" onsubmit="importReward(event, ${reward.id})">
                <div class="form-group">
                    <label class="form-label">Freebie-Zuordnung</label>
                    <select name="freebie_id" class="form-select">
                        <option value="">Allgemeine Belohnung (für alle Freebies)</option>
                        ${customerFreebies.map(f => `
                            <option value="${f.id}">${f.title}</option>
                        `).join('')}
                    </select>
                    <div class="form-hint">
                        Optional: Wähle ein spezifisches Freebie oder lasse leer für allgemeine Nutzung
                    </div>
                </div>
                
                <button type="submit" class="btn-import" id="importBtn">
                    <i class="fas fa-download"></i> Jetzt importieren
                </button>
            </form>
        </div>
    `;
    
    document.getElementById('rewardDetailModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

// Reward Modal schließen
function closeRewardModal() {
    document.getElementById('rewardDetailModal').classList.remove('show');
    document.body.style.overflow = 'auto';
}

// Reward importieren
async function importReward(event, templateId) {
    event.preventDefault();
    
    const btn = document.getElementById('importBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importiere...';
    
    const formData = new FormData(event.target);
    const freebieId = formData.get('freebie_id') || null;
    
    try {
        const response = await fetch('/api/vendor/marketplace-import.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                template_id: templateId,
                freebie_id: freebieId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('✓ Belohnung erfolgreich importiert!', 'success');
            closeRewardModal();
            loadMarketplaceData(); // Neu laden
        } else {
            showNotification('Fehler: ' + result.error, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-download"></i> Jetzt importieren';
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Verbindungsfehler', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> Jetzt importieren';
    }
}

// Empty State
function showEmptyState(message) {
    const grid = document.getElementById('marketplaceGrid');
    grid.innerHTML = `
        <div style="grid-column: 1 / -1;" class="empty-marketplace">
            <div class="empty-marketplace-icon">🔍</div>
            <h3>Keine Angebote gefunden</h3>
            <p>${message}</p>
        </div>
    `;
}

// Notification
function showNotification(message, type = 'info') {
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type]};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        z-index: 99999;
        animation: slideIn 0.3s ease-out;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

// Animation Styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>
