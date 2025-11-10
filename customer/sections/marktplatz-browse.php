<?php
// Marktplatz Browse Section
global $pdo;

if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
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
?>

<style>
.marketplace-browse-container {
    padding: 32px;
    max-width: 1800px;
    margin: 0 auto;
    min-height: 100vh;
}

.marketplace-browse-header {
    margin-bottom: 32px;
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
}
</style>

<div class="marketplace-browse-container">
    <!-- Header -->
    <div class="marketplace-browse-header">
        <h1>🛍️ Marktplatz durchsuchen</h1>
        <p>Entdecke professionelle Freebies von anderen Mitgliedern</p>
    </div>
    
    <!-- Filters Bar -->
    <div class="filters-bar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Suche nach Freebies..." onkeyup="filterMarketplace()">
        </div>
        
        <select id="nicheFilter" class="filter-select" onchange="filterMarketplace()">
            <option value="">Alle Nischen</option>
            <?php foreach ($nicheLabels as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>">
                    <?php echo htmlspecialchars($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select id="sortFilter" class="filter-select" onchange="filterMarketplace()">
            <option value="newest">Neueste zuerst</option>
            <option value="price_low">Preis aufsteigend</option>
            <option value="price_high">Preis absteigend</option>
            <option value="popular">Beliebteste</option>
        </select>
    </div>
    
    <!-- Marketplace Grid -->
    <div id="marketplaceGrid" class="marketplace-grid">
        <div class="loading-state">
            <div class="loading-spinner">🔄</div>
            <p style="color: #a0aec0;">Lade Marktplatz-Angebote...</p>
        </div>
    </div>
</div>

<script>
const nicheLabels = <?php echo json_encode($nicheLabels); ?>;
let allFreebies = [];

// Beim Laden der Seite Freebies abrufen
document.addEventListener('DOMContentLoaded', function() {
    loadMarketplaceFreebies();
});

// Marktplatz-Freebies laden
function loadMarketplaceFreebies() {
    fetch('/api/marketplace-list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.freebies) {
                allFreebies = data.freebies;
                filterMarketplace();
            } else {
                showEmptyState('Fehler beim Laden der Angebote');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showEmptyState('Fehler beim Laden der Angebote');
        });
}

// Filtern und Sortieren
function filterMarketplace() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const nicheFilter = document.getElementById('nicheFilter').value;
    const sortFilter = document.getElementById('sortFilter').value;
    
    let filtered = allFreebies.filter(freebie => {
        // Suchfilter
        const matchesSearch = !searchTerm || 
            freebie.headline.toLowerCase().includes(searchTerm) ||
            (freebie.marketplace_description && freebie.marketplace_description.toLowerCase().includes(searchTerm));
        
        // Nischen-Filter
        const matchesNiche = !nicheFilter || freebie.niche === nicheFilter;
        
        return matchesSearch && matchesNiche;
    });
    
    // Sortieren
    filtered.sort((a, b) => {
        switch(sortFilter) {
            case 'price_low':
                return (parseFloat(a.marketplace_price) || 0) - (parseFloat(b.marketplace_price) || 0);
            case 'price_high':
                return (parseFloat(b.marketplace_price) || 0) - (parseFloat(a.marketplace_price) || 0);
            case 'popular':
                return (parseInt(b.marketplace_sales_count) || 0) - (parseInt(a.marketplace_sales_count) || 0);
            case 'newest':
            default:
                return new Date(b.created_at) - new Date(a.created_at);
        }
    });
    
    displayFreebies(filtered);
}

// Freebies anzeigen
function displayFreebies(freebies) {
    const grid = document.getElementById('marketplaceGrid');
    
    if (freebies.length === 0) {
        showEmptyState('Keine Angebote gefunden');
        return;
    }
    
    grid.innerHTML = freebies.map(freebie => createMarketplaceCard(freebie)).join('');
}

// Marktplatz-Karte erstellen
function createMarketplaceCard(freebie) {
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
                
                ${freebie.course_lessons_count || freebie.course_duration ? `
                    <div class="item-meta">
                        ${freebie.course_lessons_count ? `
                            <div class="meta-item">
                                <span class="meta-icon">📚</span>
                                <span>${freebie.course_lessons_count} Lektionen</span>
                            </div>
                        ` : ''}
                        ${freebie.course_duration ? `
                            <div class="meta-item">
                                <span class="meta-icon">⏱️</span>
                                <span>${freebie.course_duration}</span>
                            </div>
                        ` : ''}
                    </div>
                ` : ''}
                
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

// Empty State anzeigen
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
</script>