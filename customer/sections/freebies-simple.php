<?php
/**
 * VERBESSERTE Freebies Section - Mit korrekten Links und Videokurs-Zugang
 */

global $pdo;
if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

// Limit holen
$stmt = $pdo->prepare("SELECT freebie_limit, product_name FROM customer_freebie_limits WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$limitData = $stmt->fetch(PDO::FETCH_ASSOC);
$freebieLimit = $limitData['freebie_limit'] ?? 0;
$packageName = $limitData['product_name'] ?? 'Basis';

// Custom Freebies zählen
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
$stmt->execute([$customer_id]);
$customFreebiesCount = $stmt->fetchColumn();

// Templates laden
$stmt = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Angepasste Templates laden
$stmt = $pdo->prepare("
    SELECT template_id, id as customer_freebie_id, unique_id, mockup_image_url, has_course
    FROM customer_freebies 
    WHERE customer_id = ? AND (freebie_type = 'template' OR freebie_type IS NULL)
");
$stmt->execute([$customer_id]);
$customer_templates = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['template_id']) {
        $customer_templates[$row['template_id']] = $row;
    }
}

// Custom Freebies laden
$stmt = $pdo->prepare("
    SELECT * FROM customer_freebies 
    WHERE customer_id = ? AND freebie_type = 'custom' 
    ORDER BY created_at DESC
");
$stmt->execute([$customer_id]);
$customFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.freebies-container { padding: 32px; }
.freebies-header { margin-bottom: 32px; }
.freebies-title { font-size: 36px; font-weight: 700; color: white; margin-bottom: 12px; }
.freebies-subtitle { font-size: 18px; color: #888; }

.limit-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 32px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
}
.limit-info h3 { font-size: 20px; margin-bottom: 8px; }
.limit-info p { font-size: 14px; opacity: 0.9; }
.btn-create {
    background: white;
    color: #667eea;
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.btn-create:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); }
.btn-create:disabled { opacity: 0.5; cursor: not-allowed; }

.tab-header { display: flex; gap: 8px; margin-bottom: 32px; border-bottom: 2px solid rgba(255, 255, 255, 0.1); }
.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    color: #888;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.tab-btn:hover { color: #667eea; }
.tab-btn.active { color: white; border-bottom-color: #667eea; }
.tab-content { display: none; }
.tab-content.active { display: block; }

.freebies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 24px; }
.freebie-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
}
.freebie-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    border-color: rgba(102, 126, 234, 0.5);
}

.freebie-mockup {
    height: 200px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.freebie-mockup img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: center;
}
.mockup-placeholder {
    font-size: 64px;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.freebie-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(34, 197, 94, 0.95);
    color: white;
}

.freebie-content { padding: 24px; }
.freebie-title { font-size: 20px; font-weight: 700; color: white; margin-bottom: 8px; line-height: 1.3; }
.freebie-subtitle { font-size: 14px; color: #aaa; margin-bottom: 16px; line-height: 1.5; }

.freebie-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}
.freebie-actions.has-course {
    grid-template-columns: 1fr 1fr 1fr;
}
.btn {
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
.btn-preview { background: rgba(255, 255, 255, 0.1); color: white; }
.btn-preview:hover { background: rgba(255, 255, 255, 0.2); }
.btn-edit { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.btn-edit:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4); }
.btn-course { background: rgba(251, 146, 60, 0.2); color: #fb923c; border: 1px solid rgba(251, 146, 60, 0.3); }
.btn-course:hover { background: rgba(251, 146, 60, 0.3); }

.link-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.link-box {
    background: rgba(102, 126, 234, 0.08);
    border: 1px solid rgba(102, 126, 234, 0.2);
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
}
.link-label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #667eea;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.link-input-wrapper { display: flex; gap: 8px; }
.link-input {
    flex: 1;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    color: white;
    padding: 8px 12px;
    font-size: 11px;
    font-family: 'Courier New', monospace;
}
.btn-copy {
    padding: 8px 12px;
    background: rgba(102, 126, 234, 0.3);
    border: 1px solid #667eea;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
    white-space: nowrap;
    border: none;
}
.btn-copy:hover { background: rgba(102, 126, 234, 0.5); }

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.empty-icon { font-size: 64px; margin-bottom: 20px; }
.empty-title { font-size: 24px; color: white; margin-bottom: 12px; }
.empty-text { font-size: 16px; color: #888; }

@media (max-width: 768px) {
    .freebies-grid { grid-template-columns: 1fr; }
    .limit-banner { flex-direction: column; text-align: center; }
    .freebie-actions { grid-template-columns: 1fr; }
    .freebie-actions.has-course { grid-template-columns: 1fr; }
}
</style>

<div class="freebies-container">
    <div class="freebies-header">
        <h1 class="freebies-title">🎁 Lead-Magneten</h1>
        <p class="freebies-subtitle">Nutze Templates oder erstelle eigene Freebie-Seiten</p>
    </div>
    
    <!-- Limit Banner -->
    <div class="limit-banner">
        <div class="limit-info">
            <h3>📦 <?php echo htmlspecialchars($packageName); ?></h3>
            <p>Du hast <strong><?php echo $customFreebiesCount; ?> / <?php echo $freebieLimit; ?></strong> eigene Freebies erstellt</p>
        </div>
        <?php if ($customFreebiesCount < $freebieLimit): ?>
            <a href="/public/customer/edit-freebie.php" class="btn-create">✨ Eigenes Freebie erstellen</a>
        <?php else: ?>
            <button class="btn-create" disabled>🔒 Limit erreicht</button>
        <?php endif; ?>
    </div>
    
    <!-- Tabs -->
    <div class="tab-header">
        <button class="tab-btn active" onclick="showTab('templates')">📚 Templates (<?php echo count($templates); ?>)</button>
        <button class="tab-btn" onclick="showTab('custom')">✨ Meine Freebies (<?php echo count($customFreebies); ?>)</button>
    </div>
    
    <!-- Tab 1: Templates -->
    <div id="tab-templates" class="tab-content active">
        <?php if (empty($templates)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3 class="empty-title">Noch keine Templates verfügbar</h3>
                <p class="empty-text">Schau bald wieder vorbei!</p>
            </div>
        <?php else: ?>
            <div class="freebies-grid">
                <?php foreach ($templates as $template): 
                    $isUsed = isset($customer_templates[$template['id']]);
                    $customerData = $customer_templates[$template['id']] ?? null;
                    
                    // Mockup URL - Customer-Version bevorzugen
                    $mockupUrl = $template['mockup_image_url'] ?? '';
                    if ($isUsed && $customerData && !empty($customerData['mockup_image_url'])) {
                        $mockupUrl = $customerData['mockup_image_url'];
                    }
                    
                    $bgColor = $template['background_color'] ?? '#667eea';
                    $primaryColor = $template['primary_color'] ?? '#667eea';
                    $hasCourse = $isUsed && $customerData && !empty($customerData['has_course']);
                ?>
                    <div class="freebie-card">
                        <div class="freebie-mockup" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <?php if ($isUsed): ?>
                                <span class="freebie-badge">✓ In Verwendung</span>
                            <?php endif; ?>
                            
                            <?php if (!empty($mockupUrl)): ?>
                                <img src="<?php echo htmlspecialchars($mockupUrl); ?>" 
                                     alt="<?php echo htmlspecialchars($template['name']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="mockup-placeholder" style="display: none; color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
                            <?php else: ?>
                                <div class="mockup-placeholder" style="color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="freebie-content">
                            <h3 class="freebie-title"><?php echo htmlspecialchars($template['headline'] ?: $template['name']); ?></h3>
                            <?php if (!empty($template['subheadline'])): ?>
                                <p class="freebie-subtitle"><?php echo htmlspecialchars($template['subheadline']); ?></p>
                            <?php endif; ?>
                            
                            <div class="freebie-actions <?php echo $hasCourse ? 'has-course' : ''; ?>">
                                <?php if ($isUsed): ?>
                                    <a href="/customer/freebie-preview.php?id=<?php echo $customerData['customer_freebie_id']; ?>" class="btn btn-preview">👁️ Vorschau</a>
                                <?php else: ?>
                                    <a href="/template-preview.php?template_id=<?php echo $template['id']; ?>" class="btn btn-preview" target="_blank">👁️ Vorschau</a>
                                <?php endif; ?>
                                <a href="/customer/freebie-editor.php?template_id=<?php echo $template['id']; ?>" class="btn btn-edit">
                                    <?php echo $isUsed ? '✏️ Bearbeiten' : '✨ Nutzen'; ?>
                                </a>
                                <?php if ($hasCourse): ?>
                                    <a href="/public/customer/edit-course.php?id=<?php echo $customerData['customer_freebie_id']; ?>" class="btn btn-course" title="Videokurs bearbeiten">🎓 Kurs</a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isUsed && $customerData): ?>
                                <div class="link-section">
                                    <!-- Freebie Link -->
                                    <div class="link-box">
                                        <div class="link-label">
                                            <span>🔗</span>
                                            <span>Freebie-Link</span>
                                        </div>
                                        <div class="link-input-wrapper">
                                            <input type="text" readonly 
                                                   value="<?php echo $protocol . '://' . $domain . '/freebie/index.php?id=' . $customerData['unique_id']; ?>"
                                                   class="link-input"
                                                   id="freebie-<?php echo $template['id']; ?>">
                                            <button onclick="copyLink('freebie-<?php echo $template['id']; ?>')" class="btn-copy">📋</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Danke-Seiten Link -->
                                    <div class="link-box">
                                        <div class="link-label">
                                            <span>🎉</span>
                                            <span>Danke-Seite</span>
                                        </div>
                                        <div class="link-input-wrapper">
                                            <input type="text" readonly 
                                                   value="<?php echo $protocol . '://' . $domain . '/freebie/thankyou.php?id=' . $customerData['customer_freebie_id'] . '&customer=' . $customer_id; ?>"
                                                   class="link-input"
                                                   id="thankyou-<?php echo $template['id']; ?>">
                                            <button onclick="copyLink('thankyou-<?php echo $template['id']; ?>')" class="btn-copy">📋</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab 2: Custom Freebies -->
    <div id="tab-custom" class="tab-content">
        <?php if (empty($customFreebies)): ?>
            <div class="empty-state">
                <div class="empty-icon">✨</div>
                <h3 class="empty-title">Noch keine eigenen Freebies</h3>
                <p class="empty-text">Erstelle dein erstes eigenes Freebie!</p>
                <?php if ($customFreebiesCount < $freebieLimit): ?>
                    <a href="/public/customer/edit-freebie.php" class="btn-create" style="margin-top: 20px;">✨ Jetzt erstellen</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="freebies-grid">
                <?php foreach ($customFreebies as $custom): 
                    $bgColor = $custom['background_color'] ?? '#667eea';
                    $primaryColor = $custom['primary_color'] ?? '#667eea';
                    $hasCourse = !empty($custom['has_course']);
                ?>
                    <div class="freebie-card">
                        <div class="freebie-mockup" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <?php if (!empty($custom['mockup_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($custom['mockup_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($custom['headline']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="mockup-placeholder" style="display: none; color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
                            <?php else: ?>
                                <div class="mockup-placeholder" style="color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="freebie-content">
                            <h3 class="freebie-title"><?php echo htmlspecialchars($custom['headline']); ?></h3>
                            <?php if (!empty($custom['subheadline'])): ?>
                                <p class="freebie-subtitle"><?php echo htmlspecialchars($custom['subheadline']); ?></p>
                            <?php endif; ?>
                            
                            <div class="freebie-actions <?php echo $hasCourse ? 'has-course' : ''; ?>">
                                <a href="/customer/freebie-preview.php?id=<?php echo $custom['id']; ?>" class="btn btn-preview">👁️ Vorschau</a>
                                <a href="/public/customer/edit-freebie.php?id=<?php echo $custom['id']; ?>" class="btn btn-edit">✏️ Bearbeiten</a>
                                <?php if ($hasCourse): ?>
                                    <a href="/public/customer/edit-course.php?id=<?php echo $custom['id']; ?>" class="btn btn-course" title="Videokurs bearbeiten">🎓 Kurs</a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="link-section">
                                <!-- Freebie Link -->
                                <div class="link-box">
                                    <div class="link-label">
                                        <span>🔗</span>
                                        <span>Freebie-Link</span>
                                    </div>
                                    <div class="link-input-wrapper">
                                        <input type="text" readonly 
                                               value="<?php echo $protocol . '://' . $domain . '/freebie/index.php?id=' . $custom['unique_id']; ?>"
                                               class="link-input"
                                               id="custom-freebie-<?php echo $custom['id']; ?>">
                                        <button onclick="copyLink('custom-freebie-<?php echo $custom['id']; ?>')" class="btn-copy">📋</button>
                                    </div>
                                </div>
                                
                                <!-- Danke-Seiten Link -->
                                <div class="link-box">
                                    <div class="link-label">
                                        <span>🎉</span>
                                        <span>Danke-Seite</span>
                                    </div>
                                    <div class="link-input-wrapper">
                                        <input type="text" readonly 
                                               value="<?php echo $protocol . '://' . $domain . '/freebie/thankyou.php?id=' . $custom['id'] . '&customer=' . $customer_id; ?>"
                                               class="link-input"
                                               id="custom-thankyou-<?php echo $custom['id']; ?>">
                                        <button onclick="copyLink('custom-thankyou-<?php echo $custom['id']; ?>')" class="btn-copy">📋</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

function copyLink(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value);
        }
        
        const button = input.nextElementSibling;
        if (button) {
            const originalHTML = button.innerHTML;
            button.innerHTML = '✓';
            button.style.background = 'rgba(34, 197, 94, 0.5)';
            button.style.borderColor = '#22c55e';
            
            setTimeout(() => {
                button.innerHTML = originalHTML;
                button.style.background = '';
                button.style.borderColor = '';
            }, 2000);
        }
    } catch (err) {
        console.error('Kopieren fehlgeschlagen:', err);
        alert('Bitte manuell kopieren: Strg+C');
    }
}
</script>