<?php
// Marktplatz Section für Customer Dashboard - NUR EIGENE FREEBIES
global $pdo;

if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Domain für Links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$base_thankyou_url = $protocol . '://' . $domain . '/public/marketplace-thankyou.php';

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

// NUR EIGENE CUSTOM FREEBIES laden
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
    
    .freebie-preview {
        height: 240px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .freebie-preview img {
        max-width: 90%;
        max-height: 90%;
        width: auto;
        height: auto;
        object-fit: contain;
        object-position: center;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
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
    .form-textarea {
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
    .form-textarea:focus {
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
    
    .copy-section {
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        display: none;
    }
    
    .copy-section.visible {
        display: block;
    }
    
    .copy-section-title {
        color: #fbbf24;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .copy-input-wrapper {
        display: flex;
        gap: 8px;
    }
    
    .copy-input {
        flex: 1;
        padding: 10px 12px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(251, 191, 36, 0.3);
        border-radius: 6px;
        color: white;
        font-size: 12px;
        font-family: 'Courier New', monospace;
    }
    
    .copy-btn {
        padding: 10px 16px;
        background: rgba(251, 191, 36, 0.3);
        border: 1px solid #fbbf24;
        border-radius: 6px;
        color: #fbbf24;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .copy-btn:hover {
        background: rgba(251, 191, 36, 0.5);
    }
    
    .modal-actions {
        display: flex;
        gap: 12px;
        margin-top: 32px;
    }
    
    .warning-banner {
        background: rgba(251, 191, 36, 0.15);
        border: 2px solid #fbbf24;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 24px;
        color: #fbbf24;
        font-size: 14px;
        line-height: 1.6;
    }
    
    .warning-banner strong {
        display: block;
        margin-bottom: 8px;
        font-size: 15px;
    }
    
    .warning-banner a {
        color: white;
        text-decoration: underline;
    }
    
    .marketplace-footer {
        margin-top: 48px;
        padding-top: 24px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .footer-link {
        color: #888;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.2s;
    }
    
    .footer-link:hover {
        color: #667eea;
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
        
        .modal-content {
            padding: 30px 20px;
        }
        
        .copy-input-wrapper {
            flex-direction: column;
        }
    }
</style>

<div class="marketplace-container">
    
    <!-- Header -->
    <div class="marketplace-header">
        <h1 class="marketplace-title">
            🏪 Meine Marktplatz-Angebote
        </h1>
        <p class="marketplace-subtitle">
            Bereite deine Freebies für den Marktplatz vor
        </p>
    </div>
    
    <!-- Info Banner -->
    <div class="info-banner">
        <h3>💡 So funktioniert der Marktplatz</h3>
        <p>
            Erstelle deine eigenen Freebies, bereite sie für den Marktplatz vor, füge einen DigiStore24-Link hinzu und aktiviere sie. Andere Kunden können deine Freebies dann im <a href="?page=marktplatz-browse" style="color: white; text-decoration: underline;">Marktplatz</a> entdecken und kaufen!<br>
            <strong>Joint Venture:</strong> Wir sind mit je 15% als Joint Venture Partner an jedem Verkauf beteiligt.
        </p>
    </div>
    
    <!-- Meine EIGENEN Freebies -->
    <?php if (empty($my_freebies)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <h3>Noch keine eigenen Freebies erstellt</h3>
            <p>Erstelle zuerst ein eigenes Freebie, um es im Marktplatz anzubieten!</p>
            <div style="margin-top: 24px;">
                <a href="?page=freebies" class="action-btn action-btn-primary">
                    🎁 Eigenes Freebie erstellen
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
                                <?php echo nl2br(htmlspecialchars(substr($freebie['marketplace_description'], 0, 150))); ?>
                                <?php if (strlen($freebie['marketplace_description']) > 150): ?>...<?php endif; ?>
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
    
    <!-- Footer -->
    <div class="marketplace-footer">
        <a href="?page=marktplatz-browse" class="footer-link">🔍 Marktplatz durchsuchen</a>
        <a href="/impressum.php" class="footer-link" target="_blank">📄 Impressum</a>
        <a href="/datenschutz.php" class="footer-link" target="_blank">🔒 Datenschutz</a>
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
                <div class="form-hint">Der Verkaufspreis für dein Freebie (wir sind mit 15% Joint Venture Partner beteiligt)</div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="digistore_product_id">
                    🔗 DigiStore24 Checkout-Link oder Produkt-ID
                </label>
                <input 
                    type="text" 
                    id="digistore_product_id" 
                    name="digistore_product_id" 
                    class="form-input"
                    oninput="updateThankYouUrl()"
                    placeholder="z.B. https://www.digistore24.com/product/12345 oder nur 12345">
                <div class="form-hint">Gib hier deinen vollständigen DigiStore24 Link oder nur die Produkt-ID ein. Vergiss nicht, die 15% Partnerprovision einzutragen!</div>
            </div>
            
            <!-- Dynamisch generierter Danke-Seiten Link -->
            <div id="thankYouSection" class="copy-section">
                <div class="copy-section-title">
                    <span>🎉</span>
                    <span>Dieser Link für DigiStore24 "Thank You Page":</span>
                </div>
                <div class="copy-input-wrapper">
                    <input 
                        type="text" 
                        class="copy-input" 
                        value="<?php echo htmlspecialchars($base_thankyou_url); ?>" 
                        readonly 
                        id="thankyouUrl">
                    <button type="button" class="copy-btn" onclick="copyThankYouUrl()">
                        📋 Kopieren
                    </button>
                </div>
                <div class="form-hint" style="margin-top: 8px;">
                    ✅ Dieser Link enthält bereits deine Produkt-ID! Kopiere ihn und trage ihn in DigiStore24 als "Thank You Page" ein.
                </div>
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
                    📚 Anzahl Lektionen (optional)
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
                    ⏱️ Kursdauer (optional)
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
const BASE_THANKYOU_URL = '<?php echo $base_thankyou_url; ?>';
let currentEditFreebieId = null;

// Extrahiert die Produkt-ID aus verschiedenen DigiStore24-Formaten
function extractProductId(input) {
    if (!input || input.trim() === '') return null;
    
    input = input.trim();
    
    // Nur Zahlen
    if (/^\d+$/.test(input)) {
        return input;
    }
    
    // Verschiedene URL-Patterns
    const patterns = [
        /\/product\/(\d+)/i,
        /\/redir\/(\d+)/i,
        /digi(?:store)?24\.com.*?(\d+)/i,
        /(\d{4,})/i
    ];
    
    for (const pattern of patterns) {
        const match = input.match(pattern);
        if (match) {
            return match[1];
        }
    }
    
    return null;
}

// Aktualisiert den Thank-You-URL wenn DigiStore-Link eingegeben wird
function updateThankYouUrl() {
    const digistoreInput = document.getElementById('digistore_product_id').value;
    const productId = extractProductId(digistoreInput);
    const thankYouSection = document.getElementById('thankYouSection');
    const thankYouInput = document.getElementById('thankyouUrl');
    
    if (productId) {
        // Zeige Section und generiere vollständigen Link
        thankYouSection.classList.add('visible');
        thankYouInput.value = BASE_THANKYOU_URL + '?product_id=' + productId;
    } else {
        // Verstecke Section wenn keine valide ID
        thankYouSection.classList.remove('visible');
        thankYouInput.value = BASE_THANKYOU_URL;
    }
}

// Danke-Seiten URL kopieren
function copyThankYouUrl() {
    const input = document.getElementById('thankyouUrl');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = '✓ Kopiert!';
        button.style.background = 'rgba(34, 197, 94, 0.5)';
        button.style.borderColor = '#22c55e';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.background = '';
            button.style.borderColor = '';
        }, 2000);
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value);
        }
    } catch (err) {
        alert('Bitte manuell kopieren: ' + input.value);
    }
}

// Marktplatz-Editor öffnen
function openMarketplaceEditor(freebieId) {
    currentEditFreebieId = freebieId;
    
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
                
                // Thank-You URL aktualisieren
                updateThankYouUrl();
                
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
    document.getElementById('thankYouSection').classList.remove('visible');
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
            let message = '✅ Einstellungen gespeichert!';
            
            // Warnung über fehlende Rechtstexte anzeigen
            if (data.warning) {
                message += '\n\n⚠️ ' + data.warning + '\n\nMöchtest du jetzt deine Rechtstexte ausfüllen?';
                
                if (confirm(message)) {
                    window.location.href = 'legal-texts.php';
                    return;
                }
            } else {
                message += ' Dein Freebie ist jetzt im Marktplatz sichtbar.';
            }
            
            alert(message);
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
</script>