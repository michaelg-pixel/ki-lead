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

// Eigene Freebies laden (für Marktplatz-Vorbereitung)
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
            freebie_type
        FROM customer_freebies
        WHERE customer_id = ? AND (freebie_type = 'custom' OR freebie_type = 'template')
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
    
    .marketplace-title {
        font-size: 36px;
        font-weight: 700;
        color: white;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .marketplace-subtitle {
        font-size: 18px;
        color: #888;
    }
    
    .info-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 32px;
        color: white;
    }
    
    .info-banner h3 {
        font-size: 20px;
        margin-bottom: 12px;
    }
    
    .info-banner p {
        font-size: 15px;
        opacity: 0.95;
        line-height: 1.6;
    }
    
    .tab-navigation {
        display: flex;
        gap: 8px;
        margin-bottom: 32px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0;
    }
    
    .tab-btn {
        padding: 14px 28px;
        background: transparent;
        border: none;
        color: #888;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tab-btn:hover {
        color: #667eea;
    }
    
    .tab-btn.active {
        color: white;
        border-bottom-color: #667eea;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Filter Section */
    .filter-section {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 32px;
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }
    
    .filter-label {
        color: white;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-select {
        flex: 1;
        min-width: 250px;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: white;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-select:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(102, 126, 234, 0.5);
    }
    
    .filter-select option {
        background: #1a1a2e;
        color: white;
        padding: 10px;
    }
    
    /* Grid System */
    .marketplace-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    
    .freebie-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
    }
    
    .freebie-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        border-color: rgba(102, 126, 234, 0.5);
    }
    
    .freebie-card.hidden {
        display: none;
    }
    
    .freebie-preview {
        height: 200px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    
    .freebie-preview img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    
    .freebie-badges {
        position: absolute;
        top: 12px;
        right: 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        align-items: flex-end;
    }
    
    .freebie-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
        color: #1a1a2e;
    }
    
    .badge-active {
        background: rgba(34, 197, 94, 0.95);
        color: white;
    }
    
    .badge-inactive {
        background: rgba(239, 68, 68, 0.95);
        color: white;
    }
    
    .badge-niche {
        background: rgba(102, 126, 234, 0.95);
        color: white;
        font-size: 10px;
    }
    
    .freebie-content {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .freebie-title {
        font-size: 20px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    
    .freebie-subtitle {
        font-size: 14px;
        color: #aaa;
        margin-bottom: 16px;
        line-height: 1.5;
    }
    
    .freebie-meta {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
        padding: 12px;
        background: rgba(0, 0, 0, 0.2);
        border-radius: 8px;
        font-size: 13px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #bbb;
    }
    
    .meta-icon {
        font-size: 16px;
    }
    
    .freebie-description {
        color: #aaa;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 16px;
        flex: 1;
    }
    
    .freebie-price {
        font-size: 28px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .price-label {
        font-size: 14px;
        color: #888;
        font-weight: 400;
    }
    
    .freebie-actions {
        display: grid;
        gap: 12px;
    }
    
    .action-btn {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .action-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .action-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
    }
    
    .action-btn-secondary {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .action-btn-secondary:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .action-btn-success {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    
    .action-btn-success:hover {
        background: rgba(34, 197, 94, 0.3);
    }
    
    .action-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 24px;
        color: white;
        margin-bottom: 12px;
    }
    
    .empty-state p {
        font-size: 16px;
        color: #888;
    }
    
    /* Modal Styles */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9998;
        backdrop-filter: blur(5px);
    }
    
    .modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .modal-content {
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        border-radius: 20px;
        max-width: 600px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 40px;
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .modal-close {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: white;
        font-size: 24px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }
    
    .modal-close:hover {
        background: rgba(239, 68, 68, 0.3);
        color: #f87171;
    }
    
    .modal-header {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 24px;
    }
    
    .form-group {
        margin-bottom: 24px;
    }
    
    .form-label {
        display: block;
        color: white;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .form-input,
    .form-textarea,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: white;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.2s;
    }
    
    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }
    
    .form-checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
    }
    
    .form-checkbox {
        width: 24px;
        height: 24px;
        cursor: pointer;
    }
    
    .form-checkbox-label {
        color: white;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        flex: 1;
    }
    
    .form-hint {
        font-size: 12px;
        color: #888;
        margin-top: 6px;
    }
    
    .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
    }
    
    @media (max-width: 1024px) {
        .marketplace-grid {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .marketplace-container {
            padding: 20px;
        }
        
        .marketplace-title {
            font-size: 28px;
        }
        
        .marketplace-grid {
            grid-template-columns: 1fr;
        }
        
        .tab-navigation {
            overflow-x: auto;
        }
        
        .modal-content {
            padding: 30px 20px;
        }
    }
</style>

<div class="marketplace-container">
    
    <!-- Header -->
    <div class="marketplace-header">
        <h1 class="marketplace-title">
            🏪 Marktplatz
        </h1>
        <p class="marketplace-subtitle">
            Teile deine Freebies oder kaufe Freebies von anderen
        </p>
    </div>
    
    <!-- Info Banner -->
    <div class="info-banner">
        <h3>💡 So funktioniert der Marktplatz</h3>
        <p>
            <strong>Als Verkäufer:</strong> Erstelle deine Freebies, bereite sie für den Marktplatz vor, füge einen DigiStore24-Link hinzu und aktiviere sie im Marktplatz.<br>
            <strong>Als Käufer:</strong> Durchsuche den Marktplatz, kaufe Freebies über DigiStore24, und sie werden automatisch in deinen Account kopiert!
        </p>
    </div>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchMarketplaceTab('prepare')">
            <span>✨</span>
            <span>Meine Freebies vorbereiten</span>
        </button>
        <button class="tab-btn" onclick="switchMarketplaceTab('browse')">
            <span>🔍</span>
            <span>Marktplatz durchsuchen</span>
        </button>
    </div>
    
    <!-- TAB 1: Meine Freebies vorbereiten -->
    <div id="tab-prepare" class="tab-content active">
        <?php if (empty($my_freebies)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>Noch keine Freebies erstellt</h3>
                <p>Erstelle zuerst ein Freebie, um es im Marktplatz anzubieten!</p>
                <div style="margin-top: 24px;">
                    <a href="?page=freebies" class="action-btn action-btn-primary">
                        🎁 Freebie erstellen
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="marketplace-grid">
                <?php foreach ($my_freebies as $freebie): 
                    $bgColor = $freebie['background_color'] ?: '#667eea';
                    $nicheValue = $freebie['niche'] ?? 'sonstiges';
                    $nicheLabel = $nicheLabels[$nicheValue] ?? '📂 Sonstiges';
                    $isActive = $freebie['marketplace_enabled'] == 1;
                ?>
                    <div class="freebie-card">
                        <div class="freebie-preview" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <div class="freebie-badges">
                                <span class="freebie-badge <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $isActive ? '✓ Aktiv' : '○ Inaktiv'; ?>
                                </span>
                                <span class="freebie-badge badge-niche"><?php echo htmlspecialchars($nicheLabel); ?></span>
                            </div>
                            
                            <?php if (!empty($freebie['mockup_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($freebie['headline']); ?>">
                            <?php else: ?>
                                <div style="font-size: 64px;">🎁</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="freebie-content">
                            <h3 class="freebie-title">
                                <?php echo htmlspecialchars($freebie['headline']); ?>
                            </h3>
                            
                            <?php if (!empty($freebie['subheadline'])): ?>
                                <p class="freebie-subtitle">
                                    <?php echo htmlspecialchars($freebie['subheadline']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="freebie-meta">
                                <?php if ($freebie['marketplace_price']): ?>
                                    <div class="meta-item">
                                        <span class="meta-icon">💰</span>
                                        <span><?php echo number_format($freebie['marketplace_price'], 2, ',', '.'); ?> €</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($freebie['course_lessons_count']): ?>
                                    <div class="meta-item">
                                        <span class="meta-icon">📚</span>
                                        <span><?php echo $freebie['course_lessons_count']; ?> Lektionen</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($freebie['course_duration']): ?>
                                    <div class="meta-item">
                                        <span class="meta-icon">⏱️</span>
                                        <span><?php echo htmlspecialchars($freebie['course_duration']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="meta-item">
                                    <span class="meta-icon">📊</span>
                                    <span><?php echo $freebie['marketplace_sales_count'] ?? 0; ?> Verkäufe</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($freebie['marketplace_description'])): ?>
                                <div class="freebie-description">
                                    <?php echo nl2br(htmlspecialchars($freebie['marketplace_description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="freebie-actions">
                                <button onclick="openMarketplaceEditor(<?php echo $freebie['id']; ?>)" 
                                        class="action-btn action-btn-primary">
                                    ⚙️ Marktplatz-Einstellungen
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- TAB 2: Marktplatz durchsuchen -->
    <div id="tab-browse" class="tab-content">
        <!-- Nischen Filter -->
        <div class="filter-section">
            <div class="filter-label">
                🔍 Nach Nische filtern:
            </div>
            <select id="marketplaceNicheFilter" class="filter-select" onchange="loadMarketplaceFreebies()">
                <option value="">Alle Nischen anzeigen</option>
                <?php foreach ($nicheLabels as $value => $label): ?>
                    <option value="<?php echo htmlspecialchars($value); ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div id="marketplaceGrid" class="marketplace-grid">
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">🔄</div>
                <p style="color: #888;">Lade Marktplatz-Freebies...</p>
            </div>
        </div>
    </div>
    
</div>

<!-- Modal für Marktplatz-Einstellungen -->
<div id="marketplaceEditorModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close" onclick="closeMarketplaceEditor()">×</button>
        <h2 class="modal-header">⚙️ Marktplatz-Einstellungen</h2>
        
        <form id="marketplaceEditorForm" onsubmit="saveMarketplaceSettings(event)">
            <input type="hidden" id="edit_freebie_id" name="freebie_id">
            
            <div class="form-group">
                <div class="form-checkbox-wrapper">
                    <input type="checkbox" id="marketplace_enabled" name="marketplace_enabled" class="form-checkbox">
                    <label for="marketplace_enabled" class="form-checkbox-label">
                        🟢 Im Marktplatz anzeigen
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="marketplace_price">
                    💰 Preis (in Euro)
                </label>
                <input 
                    type="number" 
                    id="marketplace_price" 
                    name="marketplace_price" 
                    class="form-input"
                    step="0.01"
                    min="0"
                    placeholder="z.B. 19.99">
                <div class="form-hint">Der Verkaufspreis für dein Freebie</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="digistore_product_id">
                    🔗 DigiStore24 Produkt-ID
                </label>
                <input 
                    type="text" 
                    id="digistore_product_id" 
                    name="digistore_product_id" 
                    class="form-input"
                    placeholder="z.B. 12345">
                <div class="form-hint">Deine DigiStore24 Produkt-ID für automatische Abwicklung</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="marketplace_description">
                    📝 Marktplatz-Beschreibung
                </label>
                <textarea 
                    id="marketplace_description" 
                    name="marketplace_description" 
                    class="form-textarea"
                    placeholder="Beschreibe dein Freebie für potenzielle Käufer..."></textarea>
                <div class="form-hint">Was bekommt der Käufer? Welchen Nutzen hat das Freebie?</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="course_lessons_count">
                    📚 Anzahl Lektionen
                </label>
                <input 
                    type="number" 
                    id="course_lessons_count" 
                    name="course_lessons_count" 
                    class="form-input"
                    min="0"
                    placeholder="z.B. 10">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="course_duration">
                    ⏱️ Kursdauer
                </label>
                <input 
                    type="text" 
                    id="course_duration" 
                    name="course_duration" 
                    class="form-input"
                    placeholder="z.B. 2 Stunden, 5 Wochen, etc.">
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeMarketplaceEditor()" class="action-btn action-btn-secondary" style="flex: 1;">
                    ✕ Abbrechen
                </button>
                <button type="submit" class="action-btn action-btn-primary" style="flex: 2;">
                    💾 Speichern
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const nicheLabels = <?php echo json_encode($nicheLabels); ?>;
let currentEditFreebieId = null;

// Tab-Switching
function switchMarketplaceTab(tab) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.closest('.tab-btn').classList.add('active');
    
    if (tab === 'browse') {
        loadMarketplaceFreebies();
    }
}

// Marktplatz-Editor öffnen
function openMarketplaceEditor(freebieId) {
    currentEditFreebieId = freebieId;
    
    // Aktuelle Daten laden
    fetch(`/api/get-freebie.php?id=${freebieId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.freebie) {
                const freebie = data.freebie;
                
                document.getElementById('edit_freebie_id').value = freebieId;
                document.getElementById('marketplace_enabled').checked = freebie.marketplace_enabled == 1;
                document.getElementById('marketplace_price').value = freebie.marketplace_price || '';
                document.getElementById('digistore_product_id').value = freebie.digistore_product_id || '';
                document.getElementById('marketplace_description').value = freebie.marketplace_description || '';
                document.getElementById('course_lessons_count').value = freebie.course_lessons_count || '';
                document.getElementById('course_duration').value = freebie.course_duration || '';
                
                document.getElementById('marketplaceEditorModal').classList.add('active');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Fehler beim Laden der Daten');
        });
}

// Marktplatz-Editor schließen
function closeMarketplaceEditor() {
    document.getElementById('marketplaceEditorModal').classList.remove('active');
    document.getElementById('marketplaceEditorForm').reset();
    currentEditFreebieId = null;
}

// Marktplatz-Einstellungen speichern
function saveMarketplaceSettings(event) {
    event.preventDefault();
    
    const formData = {
        freebie_id: document.getElementById('edit_freebie_id').value,
        marketplace_enabled: document.getElementById('marketplace_enabled').checked,
        marketplace_price: document.getElementById('marketplace_price').value || null,
        digistore_product_id: document.getElementById('digistore_product_id').value || null,
        marketplace_description: document.getElementById('marketplace_description').value || null,
        course_lessons_count: document.getElementById('course_lessons_count').value || null,
        course_duration: document.getElementById('course_duration').value || null
    };
    
    fetch('/api/marketplace-update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Einstellungen gespeichert!');
            closeMarketplaceEditor();
            location.reload();
        } else {
            alert('❌ Fehler: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Fehler beim Speichern');
    });
}

// Marktplatz-Freebies laden
function loadMarketplaceFreebies() {
    const niche = document.getElementById('marketplaceNicheFilter').value;
    const grid = document.getElementById('marketplaceGrid');
    
    grid.innerHTML = `
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 16px;">🔄</div>
            <p style="color: #888;">Lade Marktplatz-Freebies...</p>
        </div>
    `;
    
    const url = niche ? `/api/marketplace-list.php?niche=${encodeURIComponent(niche)}` : '/api/marketplace-list.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.freebies) {
                if (data.freebies.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column: 1 / -1;" class="empty-state">
                            <div class="empty-state-icon">🔍</div>
                            <h3>Keine Freebies gefunden</h3>
                            <p>Aktuell sind keine Freebies in dieser Kategorie verfügbar.</p>
                        </div>
                    `;
                } else {
                    grid.innerHTML = data.freebies.map(freebie => createMarketplaceCard(freebie)).join('');
                }
            } else {
                grid.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #f87171;">
                        ❌ Fehler beim Laden: ${data.error || 'Unbekannter Fehler'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            grid.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #f87171;">
                    ❌ Fehler beim Laden der Freebies
                </div>
            `;
        });
}

// Marktplatz-Karte erstellen
function createMarketplaceCard(freebie) {
    const nicheLabel = nicheLabels[freebie.niche] || '📂 Sonstiges';
    const bgColor = freebie.background_color || '#667eea';
    const imageHtml = freebie.mockup_image_url 
        ? `<img src="${freebie.mockup_image_url}" alt="${freebie.headline}">`
        : '<div style="font-size: 64px;">🎁</div>';
    
    let actionButton = '';
    if (freebie.is_own) {
        actionButton = `
            <button class="action-btn action-btn-secondary" disabled>
                👤 Dein eigenes Freebie
            </button>
        `;
    } else if (freebie.already_purchased) {
        actionButton = `
            <button class="action-btn action-btn-success" disabled>
                ✓ Bereits gekauft
            </button>
        `;
    } else if (freebie.digistore_product_id) {
        actionButton = `
            <a href="https://www.digistore24.com/product/${freebie.digistore_product_id}" 
               target="_blank" 
               class="action-btn action-btn-primary">
                💳 Jetzt kaufen
            </a>
        `;
    } else {
        actionButton = `
            <button class="action-btn action-btn-secondary" disabled>
                ⚠️ Kein Kauflink verfügbar
            </button>
        `;
    }
    
    return `
        <div class="freebie-card">
            <div class="freebie-preview" style="background: ${bgColor};">
                <div class="freebie-badges">
                    <span class="freebie-badge badge-niche">${nicheLabel}</span>
                </div>
                ${imageHtml}
            </div>
            
            <div class="freebie-content">
                <h3 class="freebie-title">${freebie.headline}</h3>
                
                ${freebie.subheadline ? `<p class="freebie-subtitle">${freebie.subheadline}</p>` : ''}
                
                <div class="freebie-meta">
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
                    
                    <div class="meta-item">
                        <span class="meta-icon">📊</span>
                        <span>${freebie.marketplace_sales_count || 0} Verkäufe</span>
                    </div>
                    
                    <div class="meta-item">
                        <span class="meta-icon">👤</span>
                        <span>${freebie.creator_name}</span>
                    </div>
                </div>
                
                ${freebie.marketplace_description ? `
                    <div class="freebie-description">${freebie.marketplace_description.replace(/\n/g, '<br>')}</div>
                ` : ''}
                
                <div class="freebie-price">
                    ${freebie.marketplace_price ? `${parseFloat(freebie.marketplace_price).toFixed(2).replace('.', ',')} €` : 'Kostenlos'}
                    <span class="price-label">Einmalzahlung</span>
                </div>
                
                <div class="freebie-actions">
                    ${actionButton}
                </div>
            </div>
        </div>
    `;
}

// Initial laden wenn auf Browse-Tab
document.addEventListener('DOMContentLoaded', function() {
    // Nichts laden beim Start, erst wenn Tab gewechselt wird
});
</script>