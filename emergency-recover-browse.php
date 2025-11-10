<?php
// Emergency Recovery - Stelle Marktplatz-Dateien wieder her
echo "<h2>🚨 Emergency Recovery</h2>";

// Komplette marktplatz-browse.php mit dunklem Design
$marktplatz_browse_content = <<<'PHP'
<?php
// Öffentlicher Marktplatz - Freebies von anderen Usern durchsuchen
global $pdo;

if (!isset($pdo)) {
    require_once __DIR__ . '/../../config/database.php';
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

// Filter
$search = $_GET['search'] ?? '';
$niche = $_GET['niche'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Query bauen
$sql = "
    SELECT 
        cf.id,
        cf.headline,
        cf.subheadline,
        cf.mockup_image_url,
        cf.background_color,
        cf.niche,
        cf.marketplace_price,
        cf.marketplace_description,
        cf.marketplace_sales_count,
        cf.created_at,
        u.name as seller_name
    FROM customer_freebies cf
    JOIN users u ON cf.customer_id = u.id
    WHERE cf.marketplace_enabled = 1
    AND cf.freebie_type = 'custom'
    AND cf.customer_id != ?
";

$params = [$customer_id];

if ($search) {
    $sql .= " AND (cf.headline LIKE ? OR cf.marketplace_description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($niche) {
    $sql .= " AND cf.niche = ?";
    $params[] = $niche;
}

// Sortierung
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY cf.marketplace_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY cf.marketplace_price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY cf.marketplace_sales_count DESC";
        break;
    default:
        $sql .= " ORDER BY cf.created_at DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $marketplace_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $marketplace_freebies = [];
    $error = $e->getMessage();
}
?>

<style>
.marketplace-browse-container {
    padding: 32px;
    max-width: 1800px;
    margin: 0 auto;
    background: #0f0f1e;
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
    background: #1a1a2e;
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
    background: #1a1a2e;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid rgba(255,255,255,0.1);
}

.marketplace-item:hover {
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
    transform: translateY(-2px);
    border-color: rgba(102, 126, 234, 0.5);
}

.item-preview {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.item-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
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
}

.item-content {
    padding: 16px;
}

.item-niche {
    display: inline-block;
    font-size: 11px;
    padding: 3px 8px;
    background: rgba(102, 126, 234, 0.2);
    color: #a0aec0;
    border-radius: 10px;
    margin-bottom: 8px;
}

.item-content h3 {
    font-size: 16px;
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
    font-size: 13px;
    color: #a0aec0;
    line-height: 1.4;
    margin-bottom: 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
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

.item-sales {
    color: #10b981;
    font-weight: 600;
}

.btn-view-details {
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

.btn-view-details:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.empty-marketplace {
    text-align: center;
    padding: 80px 20px;
    background: #1a1a2e;
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
}
</style>

<div class="marketplace-browse-container">
    <div class="marketplace-browse-header">
        <h1>🛍️ Marktplatz durchsuchen</h1>
        <p>Entdecke professionelle Freebies von anderen Marketern</p>
    </div>

    <form method="GET" action="?page=marktplatz-browse">
        <div class="filters-bar">
            <div class="search-box">
                <input type="text" 
                       name="search" 
                       placeholder="Suche nach Freebies..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <select name="niche" class="filter-select" onchange="this.form.submit()">
                <option value="">Alle Kategorien</option>
                <?php foreach ($nicheLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $niche === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Neueste</option>
                <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Beliebteste</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Preis aufsteigend</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Preis absteigend</option>
            </select>
        </div>
    </form>

    <?php if (empty($marketplace_freebies)): ?>
        <div class="empty-marketplace">
            <div class="empty-marketplace-icon">🏪</div>
            <h3>Keine Freebies gefunden</h3>
            <p>Versuche eine andere Suche oder Kategorie</p>
        </div>
    <?php else: ?>
        <div class="marketplace-grid">
            <?php foreach ($marketplace_freebies as $item): ?>
                <div class="marketplace-item" onclick="window.location.href='?page=marktplatz-detail&id=<?= $item['id'] ?>'">
                    <div class="item-preview" style="background: <?= htmlspecialchars($item['background_color'] ?? '#667eea') ?>">
                        <?php if (!empty($item['mockup_image_url'])): ?>
                            <img src="<?= htmlspecialchars($item['mockup_image_url']) ?>" alt="Freebie Preview">
                        <?php endif; ?>
                        
                        <div class="item-price-badge">
                            <?= number_format($item['marketplace_price'], 2) ?>€
                        </div>
                    </div>

                    <div class="item-content">
                        <?php if (!empty($item['niche'])): ?>
                            <span class="item-niche">
                                <?= $nicheLabels[$item['niche']] ?? htmlspecialchars($item['niche']) ?>
                            </span>
                        <?php endif; ?>

                        <h3><?= htmlspecialchars($item['headline']) ?></h3>
                        
                        <?php if (!empty($item['marketplace_description'])): ?>
                            <p class="item-description"><?= htmlspecialchars($item['marketplace_description']) ?></p>
                        <?php endif; ?>

                        <div class="item-footer">
                            <span class="item-seller">
                                👤 <?= htmlspecialchars($item['seller_name']) ?>
                            </span>
                            <span class="item-sales">
                                📊 <?= (int)$item['marketplace_sales_count'] ?> Verkäufe
                            </span>
                        </div>
                        
                        <button class="btn-view-details" onclick="event.stopPropagation()">
                            👁️ Details ansehen
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
PHP;

$target_file = __DIR__ . '/customer/sections/marktplatz-browse.php';

// Erstelle Verzeichnis falls nötig
$dir = dirname($target_file);
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

// Schreibe Datei
if (file_put_contents($target_file, $marktplatz_browse_content)) {
    echo "✅ marktplatz-browse.php wiederhergestellt (" . strlen($marktplatz_browse_content) . " Bytes)<br>";
} else {
    echo "❌ Fehler beim Schreiben von marktplatz-browse.php<br>";
}

echo "<br><a href='/customer/dashboard.php?page=marktplatz-browse' style='display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;'>Marktplatz durchsuchen testen</a>";
?>
