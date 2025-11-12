<?php
/**
 * Multi-Webhook Manager - Admin Interface
 * Flexible Webhook-Konfigurationen mit Mehrfach-Produkt-IDs und Ressourcen
 */

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert');
}

// Prüfen ob Tabellen existieren
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'webhook_configurations'")->fetch();
    if (!$tableCheck) {
        // Tabellen existieren noch nicht - Migration erforderlich
        ?>
        <div style="max-width: 800px; margin: 50px auto; padding: 40px; background: white; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
            <div style="text-align: center; margin-bottom: 30px;">
                <div style="font-size: 64px; margin-bottom: 16px;">🔧</div>
                <h2 style="margin: 0 0 12px 0; color: #1f2937; font-size: 28px;">Migration erforderlich</h2>
                <p style="color: #6b7280; margin: 0;">Das Webhook-System muss zuerst installiert werden.</p>
            </div>
            
            <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0; color: #1e40af; line-height: 1.6;">
                    <strong>📋 Installationsschritte:</strong><br><br>
                    1. Öffne die Migration in einem neuen Tab<br>
                    2. Klicke auf "Migration starten"<br>
                    3. Warte bis "Migration erfolgreich" erscheint<br>
                    4. Kehre hierher zurück und lade die Seite neu
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="/database/migrate-webhook-system.html" 
                   target="_blank"
                   style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                          color: white; padding: 16px 32px; border-radius: 12px; text-decoration: none; 
                          font-weight: 600; font-size: 16px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                    🚀 Migration jetzt ausführen
                </a>
            </div>
            
            <div style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #f3f4f6;">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 12px 0;">
                    <strong>💡 Was wird installiert?</strong>
                </p>
                <ul style="color: #6b7280; font-size: 14px; line-height: 1.8; margin: 0; padding-left: 20px;">
                    <li>webhook_configurations - Haupttabelle für Webhooks</li>
                    <li>webhook_product_ids - Mehrere Produkt-IDs pro Webhook</li>
                    <li>webhook_course_access - Flexible Kurszuweisungen</li>
                    <li>webhook_ready_freebies - Spezifische Freebie-Templates</li>
                    <li>webhook_activity_log - Aktivitäts-Tracking</li>
                </ul>
            </div>
            
            <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 16px; border-radius: 8px; margin-top: 20px;">
                <p style="margin: 0; color: #856404; font-size: 13px;">
                    <strong>⚠️ Hinweis:</strong> Deine bestehenden digistore_products Webhooks bleiben vollständig erhalten!
                </p>
            </div>
        </div>
        <?php
        return;
    }
} catch (PDOException $e) {
    die('<div style="padding: 40px; text-align: center; color: #ef4444;">Datenbankfehler: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// Alle Webhooks laden
$webhooks = $pdo->query("
    SELECT 
        w.*,
        COUNT(DISTINCT wp.id) as product_count,
        COUNT(DISTINCT wc.id) as course_count,
        COUNT(DISTINCT wf.id) as freebie_count,
        (SELECT COUNT(*) FROM webhook_activity_log WHERE webhook_id = w.id) as activity_count
    FROM webhook_configurations w
    LEFT JOIN webhook_product_ids wp ON w.id = wp.webhook_id
    LEFT JOIN webhook_course_access wc ON w.id = wc.webhook_id
    LEFT JOIN webhook_ready_freebies wf ON w.id = wf.webhook_id
    GROUP BY w.id
    ORDER BY w.created_at DESC
")->fetchAll();

// Alle verfügbaren Kurse laden
$availableCourses = $pdo->query("
    SELECT id, title, description 
    FROM courses 
    WHERE is_active = 1
    ORDER BY title
")->fetchAll();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<style>
.webhook-manager {
    padding: 30px;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 32px;
    color: #1f2937;
    margin: 0;
}

.btn-create {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 14px 28px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.stat-card h4 {
    margin: 0 0 8px 0;
    color: #6b7280;
    font-size: 14px;
    font-weight: 500;
}

.stat-card .stat-value {
    font-size: 32px;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.webhooks-grid {
    display: grid;
    gap: 24px;
}

.webhook-card {
    background: white;
    border-radius: 16px;
    padding: 28px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
    position: relative;
}

.webhook-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
}

.webhook-card.inactive {
    opacity: 0.6;
    background: #f9fafb;
}

.webhook-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.webhook-title {
    flex: 1;
}

.webhook-title h3 {
    margin: 0 0 8px 0;
    font-size: 22px;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 10px;
}

.webhook-title p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.webhook-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.badge-active {
    background: #d1fae5;
    color: #065f46;
}

.badge-inactive {
    background: #fee2e2;
    color: #991b1b;
}

.badge-upsell {
    background: #fef3c7;
    color: #92400e;
}

.webhook-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
    padding: 20px;
    background: #f9fafb;
    border-radius: 12px;
    margin: 20px 0;
}

.meta-item {
    text-align: center;
}

.meta-item .label {
    font-size: 12px;
    color: #6b7280;
    margin-bottom: 4px;
}

.meta-item .value {
    font-size: 24px;
    font-weight: bold;
    color: #667eea;
}

.product-ids {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 16px 0;
}

.product-id-tag {
    background: #dbeafe;
    color: #1e40af;
    padding: 6px 12px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    font-weight: 600;
}

.resources-list {
    background: #f0fdf4;
    border-left: 4px solid #10b981;
    padding: 16px;
    border-radius: 8px;
    margin: 16px 0;
}

.resources-list h4 {
    margin: 0 0 12px 0;
    color: #065f46;
    font-size: 14px;
}

.resources-list ul {
    margin: 0;
    padding-left: 20px;
    color: #047857;
}

.resources-list li {
    margin-bottom: 6px;
}

.webhook-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #f3f4f6;
    flex-wrap: wrap;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: #f3f4f6;
    color: #4b5563;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
    border: 2px dashed #e5e7eb;
}

.empty-state-icon {
    font-size: 64px;
    margin-bottom: 16px;
}

.empty-state h3 {
    color: #1f2937;
    margin-bottom: 12px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 24px;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s;
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 20px;
    padding: 40px;
    max-width: 800px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.modal-header h2 {
    margin: 0;
    color: #1f2937;
}

.close-modal {
    background: #f3f4f6;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.close-modal:hover {
    background: #e5e7eb;
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #374151;
    font-weight: 600;
    font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 15px;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
}

.checkbox-group input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.help-text {
    font-size: 13px;
    color: #6b7280;
    margin-top: 6px;
}

.product-ids-input {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.product-ids-input input {
    flex: 1;
}

.product-ids-input button {
    padding: 12px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}

.product-ids-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.product-id-item {
    background: #dbeafe;
    color: #1e40af;
    padding: 8px 12px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: 'Courier New', monospace;
}

.product-id-item button {
    background: transparent;
    border: none;
    color: #1e40af;
    cursor: pointer;
    font-size: 16px;
    padding: 0;
    width: 20px;
    height: 20px;
}

.loading-indicator {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 3px solid #f3f4f6;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="webhook-manager">
    <?php if ($success): ?>
        <div class="alert alert-success">
            <span style="font-size: 24px;">✅</span>
            <span><strong>Erfolgreich!</strong> <?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <span style="font-size: 24px;">❌</span>
            <span><strong>Fehler:</strong> <?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <h1>🔗 Webhook-Verwaltung</h1>
        <button class="btn-create" onclick="openCreateModal()">
            ➕ Neuer Webhook
        </button>
    </div>
    
    <!-- Statistiken -->
    <div class="stats-grid">
        <div class="stat-card">
            <h4>Aktive Webhooks</h4>
            <div class="stat-value"><?php echo count(array_filter($webhooks, fn($w) => $w['is_active'])); ?></div>
        </div>
        <div class="stat-card">
            <h4>Gesamt Webhooks</h4>
            <div class="stat-value"><?php echo count($webhooks); ?></div>
        </div>
        <div class="stat-card">
            <h4>Upsell Webhooks</h4>
            <div class="stat-value"><?php echo count(array_filter($webhooks, fn($w) => $w['is_upsell'])); ?></div>
        </div>
        <div class="stat-card">
            <h4>Gesamt Aktivitäten</h4>
            <div class="stat-value"><?php echo array_sum(array_column($webhooks, 'activity_count')); ?></div>
        </div>
    </div>
    
    <!-- Webhooks Liste -->
    <div class="webhooks-grid">
        <?php if (empty($webhooks)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🔗</div>
                <h3>Noch keine Webhooks erstellt</h3>
                <p>Erstelle deinen ersten flexiblen Webhook mit mehreren Produkt-IDs und Ressourcen.</p>
                <button class="btn-create" onclick="openCreateModal()">
                    ➕ Ersten Webhook erstellen
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($webhooks as $webhook): ?>
                <?php
                // Produkt-IDs laden
                $stmt = $pdo->prepare("SELECT product_id FROM webhook_product_ids WHERE webhook_id = ?");
                $stmt->execute([$webhook['id']]);
                $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Kurse laden
                $stmt = $pdo->prepare("
                    SELECT c.title 
                    FROM webhook_course_access wc
                    JOIN courses c ON wc.course_id = c.id
                    WHERE wc.webhook_id = ?
                ");
                $stmt->execute([$webhook['id']]);
                $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
                ?>
                
                <div class="webhook-card <?php echo $webhook['is_active'] ? '' : 'inactive'; ?>">
                    <div class="webhook-header">
                        <div class="webhook-title">
                            <h3>
                                <?php echo htmlspecialchars($webhook['name']); ?>
                                <?php if ($webhook['is_upsell']): ?>
                                    <span style="font-size: 16px;">⬆️</span>
                                <?php endif; ?>
                            </h3>
                            <?php if ($webhook['description']): ?>
                                <p><?php echo htmlspecialchars($webhook['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="webhook-badges">
                            <span class="badge <?php echo $webhook['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $webhook['is_active'] ? '✅ Aktiv' : '❌ Inaktiv'; ?>
                            </span>
                            <?php if ($webhook['is_upsell']): ?>
                                <span class="badge badge-upsell">
                                    ⬆️ Upsell (<?php echo strtoupper($webhook['upsell_behavior']); ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="webhook-meta">
                        <div class="meta-item">
                            <div class="label">Eigene Freebies</div>
                            <div class="value"><?php echo $webhook['own_freebies_limit']; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Fertige Freebies</div>
                            <div class="value"><?php echo $webhook['ready_freebies_count']; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Empfehlungs-Slots</div>
                            <div class="value"><?php echo $webhook['referral_slots']; ?></div>
                        </div>
                        <div class="meta-item">
                            <div class="label">Aktivitäten</div>
                            <div class="value"><?php echo $webhook['activity_count']; ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($productIds)): ?>
                        <div>
                            <strong style="font-size: 14px; color: #6b7280;">🔑 Produkt-IDs:</strong>
                            <div class="product-ids">
                                <?php foreach ($productIds as $productId): ?>
                                    <span class="product-id-tag"><?php echo htmlspecialchars($productId); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($courses)): ?>
                        <div class="resources-list">
                            <h4>📚 Kurszugang gewährt:</h4>
                            <ul>
                                <?php foreach ($courses as $course): ?>
                                    <li><?php echo htmlspecialchars($course); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="webhook-actions">
                        <button class="btn btn-primary" onclick="editWebhook(<?php echo $webhook['id']; ?>)">
                            ✏️ Bearbeiten
                        </button>
                        <button class="btn btn-secondary" onclick="viewActivity(<?php echo $webhook['id']; ?>)">
                            📊 Aktivitäten
                        </button>
                        <button class="btn btn-secondary" onclick="testWebhook(<?php echo $webhook['id']; ?>)">
                            🧪 Testen
                        </button>
                        <button class="btn btn-danger" onclick="deleteWebhook(<?php echo $webhook['id']; ?>, '<?php echo htmlspecialchars($webhook['name']); ?>')">
                            🗑️ Löschen
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="webhookModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Neuer Webhook</h2>
            <button class="close-modal" onclick="closeModal()">✕</button>
        </div>
        
        <form id="webhookForm" method="POST" action="/admin/api/webhooks/save.php">
            <input type="hidden" name="webhook_id" id="webhook_id">
            
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required placeholder="z.B. Premium Paket 2025">
                <div class="help-text">Interner Name zur Identifikation des Webhooks</div>
            </div>
            
            <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea id="description" name="description" placeholder="Optionale Beschreibung des Webhooks"></textarea>
            </div>
            
            <div class="form-group">
                <label>Digistore24 Produkt-IDs *</label>
                <div class="product-ids-input">
                    <input type="text" id="newProductId" placeholder="Produkt-ID eingeben (z.B. 639493)">
                    <button type="button" onclick="addProductId()">Hinzufügen</button>
                </div>
                <div class="help-text">Mehrere Produkt-IDs werden durch Kommata getrennt (automatisch)</div>
                <input type="hidden" id="product_ids" name="product_ids">
                <div id="productIdsList" class="product-ids-list"></div>
            </div>
            
            <div class="form-group">
                <label for="own_freebies_limit">Eigene Freebies Limit</label>
                <input type="number" id="own_freebies_limit" name="own_freebies_limit" value="0" min="0">
                <div class="help-text">Wie viele eigene Freebies darf der Kunde erstellen?</div>
            </div>
            
            <div class="form-group">
                <label for="ready_freebies_count">Fertige Freebies</label>
                <input type="number" id="ready_freebies_count" name="ready_freebies_count" value="0" min="0">
                <div class="help-text">Anzahl der fertigen Template-Freebies</div>
            </div>
            
            <div class="form-group">
                <label for="referral_slots">Empfehlungs-Slots</label>
                <input type="number" id="referral_slots" name="referral_slots" value="0" min="0">
                <div class="help-text">Anzahl der Empfehlungsprogramm-Slots</div>
            </div>
            
            <div class="form-group">
                <label>Kurszugang</label>
                <?php foreach ($availableCourses as $course): ?>
                    <div class="checkbox-group">
                        <input type="checkbox" 
                               name="courses[]" 
                               value="<?php echo $course['id']; ?>" 
                               id="course_<?php echo $course['id']; ?>">
                        <label for="course_<?php echo $course['id']; ?>" style="margin: 0; font-weight: normal;">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="is_upsell" value="1" id="is_upsell" onchange="toggleUpsellOptions()">
                    <label for="is_upsell" style="margin: 0; font-weight: bold;">
                        ⬆️ Dies ist ein Upsell (addiert zu bestehenden Ressourcen)
                    </label>
                </div>
            </div>
            
            <div class="form-group" id="upsellBehaviorGroup" style="display: none;">
                <label for="upsell_behavior">Upsell-Verhalten</label>
                <select id="upsell_behavior" name="upsell_behavior">
                    <option value="add">ADD - Zu bestehenden Ressourcen addieren</option>
                    <option value="upgrade">UPGRADE - Nur höhere Werte übernehmen</option>
                    <option value="replace">REPLACE - Bestehende Werte ersetzen</option>
                </select>
                <div class="help-text">Wie sollen die Ressourcen verwaltet werden bei bestehenden Kunden?</div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" name="is_active" value="1" id="is_active" checked>
                    <label for="is_active" style="margin: 0; font-weight: bold;">
                        ✅ Webhook aktivieren (verarbeitet Käufe)
                    </label>
                </div>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    💾 Speichern
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let productIds = [];

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Neuer Webhook';
    document.getElementById('webhookForm').reset();
    document.getElementById('webhook_id').value = '';
    productIds = [];
    updateProductIdsList();
    document.getElementById('webhookModal').classList.add('active');
}

function closeModal() {
    document.getElementById('webhookModal').classList.remove('active');
}

function addProductId() {
    const input = document.getElementById('newProductId');
    const productId = input.value.trim();
    
    if (productId && !productIds.includes(productId)) {
        productIds.push(productId);
        updateProductIdsList();
        input.value = '';
    }
}

function removeProductId(productId) {
    productIds = productIds.filter(id => id !== productId);
    updateProductIdsList();
}

function updateProductIdsList() {
    const container = document.getElementById('productIdsList');
    const hiddenInput = document.getElementById('product_ids');
    
    hiddenInput.value = productIds.join(',');
    
    container.innerHTML = productIds.map(id => `
        <div class="product-id-item">
            <span>${id}</span>
            <button type="button" onclick="removeProductId('${id}')">✕</button>
        </div>
    `).join('');
}

function toggleUpsellOptions() {
    const isUpsell = document.getElementById('is_upsell').checked;
    document.getElementById('upsellBehaviorGroup').style.display = isUpsell ? 'block' : 'none';
}

async function editWebhook(id) {
    try {
        // Modal öffnen und Ladeindikator anzeigen
        document.getElementById('modalTitle').innerHTML = '✏️ Webhook bearbeiten <span class="loading-indicator"></span>';
        document.getElementById('webhookModal').classList.add('active');
        
        // Webhook-Daten laden
        const response = await fetch(`/admin/api/webhooks/get.php?webhook_id=${id}`);
        const result = await response.json();
        
        if (!result.success) {
            alert('❌ Fehler beim Laden: ' + result.error);
            closeModal();
            return;
        }
        
        const webhook = result.webhook;
        
        // Formular befüllen
        document.getElementById('webhook_id').value = webhook.id;
        document.getElementById('name').value = webhook.name || '';
        document.getElementById('description').value = webhook.description || '';
        document.getElementById('own_freebies_limit').value = webhook.own_freebies_limit || 0;
        document.getElementById('ready_freebies_count').value = webhook.ready_freebies_count || 0;
        document.getElementById('referral_slots').value = webhook.referral_slots || 0;
        
        // Produkt-IDs setzen
        productIds = result.product_ids || [];
        updateProductIdsList();
        
        // Kurse auswählen
        document.querySelectorAll('input[name="courses[]"]').forEach(checkbox => {
            checkbox.checked = result.course_ids.includes(parseInt(checkbox.value));
        });
        
        // Upsell-Optionen
        document.getElementById('is_upsell').checked = webhook.is_upsell == 1;
        document.getElementById('upsell_behavior').value = webhook.upsell_behavior || 'add';
        toggleUpsellOptions();
        
        // Aktiv-Status
        document.getElementById('is_active').checked = webhook.is_active == 1;
        
        // Titel aktualisieren (Ladeindikator entfernen)
        document.getElementById('modalTitle').textContent = '✏️ Webhook bearbeiten';
        
    } catch (error) {
        console.error('Fehler beim Laden:', error);
        alert('❌ Verbindungsfehler beim Laden der Webhook-Daten');
        closeModal();
    }
}

function viewActivity(id) {
    window.open(`/admin/api/webhooks/activity.php?webhook_id=${id}`, '_blank');
}

function testWebhook(id) {
    window.open(`/webhook/test-webhook.php?webhook_id=${id}`, '_blank');
}

async function deleteWebhook(id, name) {
    if (!confirm(`Webhook "${name}" wirklich löschen?\n\nAlle verknüpften Produkt-IDs und Konfigurationen werden entfernt.`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('webhook_id', id);
        
        const response = await fetch('/admin/api/webhooks/delete.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Webhook erfolgreich gelöscht');
            location.reload();
        } else {
            alert('❌ Fehler: ' + result.error);
        }
    } catch (error) {
        alert('❌ Verbindungsfehler');
    }
}

// Enter-Taste für Produkt-ID hinzufügen
document.getElementById('newProductId')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addProductId();
    }
});
</script>