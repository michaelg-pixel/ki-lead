<?php
/**
 * VEREINFACHTE Freebies Section - Garantiert funktionierend
 * Zeigt alle Freebies ohne komplexe Filter
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
    SELECT template_id, id as customer_freebie_id, unique_id 
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
.simple-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
.simple-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 20px; }
.simple-card h3 { color: white; margin-bottom: 10px; }
.simple-card p { color: #aaa; font-size: 14px; margin-bottom: 15px; }
.simple-btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px; font-size: 14px; }
.simple-btn:hover { background: #5568d3; }
.simple-header { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin-bottom: 20px; }
.tab-header { display: flex; gap: 10px; margin: 20px 0; }
.tab-header button { padding: 12px 24px; background: rgba(255,255,255,0.1); color: white; border: none; border-radius: 8px; cursor: pointer; }
.tab-header button.active { background: #667eea; }
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>

<div style="padding: 32px;">
    <h1 style="font-size: 32px; color: white; margin-bottom: 20px;">🎁 Lead-Magneten</h1>
    
    <!-- Limit Banner -->
    <div class="simple-header">
        <h3 style="color: white; margin-bottom: 8px;">📦 <?php echo htmlspecialchars($packageName); ?></h3>
        <p style="color: #aaa;">
            Du hast <strong><?php echo $customFreebiesCount; ?> / <?php echo $freebieLimit; ?></strong> eigene Freebies erstellt
        </p>
        <?php if ($customFreebiesCount < $freebieLimit): ?>
            <a href="/public/customer/edit-freebie.php" class="simple-btn">✨ Eigenes Freebie erstellen</a>
        <?php else: ?>
            <button class="simple-btn" disabled style="opacity: 0.5;">🔒 Limit erreicht</button>
        <?php endif; ?>
    </div>
    
    <!-- Tabs -->
    <div class="tab-header">
        <button class="active" onclick="showTab('templates')">📚 Templates (<?php echo count($templates); ?>)</button>
        <button onclick="showTab('custom')">✨ Meine Freebies (<?php echo count($customFreebies); ?>)</button>
    </div>
    
    <!-- Tab 1: Templates -->
    <div id="tab-templates" class="tab-content active">
        <h2 style="color: white; margin-bottom: 20px;">📚 Verfügbare Templates</h2>
        
        <?php if (empty($templates)): ?>
            <div class="simple-card">
                <p style="text-align: center; padding: 40px;">Noch keine Templates vorhanden</p>
            </div>
        <?php else: ?>
            <div class="simple-grid">
                <?php foreach ($templates as $template): 
                    $isUsed = isset($customer_templates[$template['id']]);
                    $customerData = $customer_templates[$template['id']] ?? null;
                ?>
                    <div class="simple-card">
                        <h3><?php echo htmlspecialchars($template['headline'] ?: $template['name']); ?></h3>
                        <p><?php echo htmlspecialchars($template['subheadline'] ?? ''); ?></p>
                        
                        <?php if ($isUsed): ?>
                            <span style="display: inline-block; padding: 4px 12px; background: rgba(34, 197, 94, 0.2); color: #22c55e; border-radius: 12px; font-size: 12px; margin-bottom: 10px;">✓ In Verwendung</span>
                        <?php endif; ?>
                        
                        <div>
                            <?php if ($isUsed): ?>
                                <a href="/customer/freebie-preview.php?id=<?php echo $customerData['customer_freebie_id']; ?>" 
                                   class="simple-btn">👁️ Meine Version</a>
                                <a href="/customer/freebie-editor.php?template_id=<?php echo $template['id']; ?>" 
                                   class="simple-btn">✏️ Bearbeiten</a>
                            <?php else: ?>
                                <a href="/template-preview.php?template_id=<?php echo $template['id']; ?>" 
                                   class="simple-btn" target="_blank">👁️ Vorschau</a>
                                <a href="/customer/freebie-editor.php?template_id=<?php echo $template['id']; ?>" 
                                   class="simple-btn">✨ Nutzen</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($isUsed && $customerData): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                                <p style="font-size: 12px; color: #888; margin-bottom: 5px;">🔗 Freebie-Link:</p>
                                <input type="text" readonly 
                                       value="<?php echo $protocol . '://' . $domain . '/freebie/index.php?id=' . $customerData['unique_id']; ?>"
                                       style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; color: white; font-size: 11px;"
                                       onclick="this.select();">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Tab 2: Custom Freebies -->
    <div id="tab-custom" class="tab-content">
        <h2 style="color: white; margin-bottom: 20px;">✨ Deine eigenen Freebies</h2>
        
        <?php if (empty($customFreebies)): ?>
            <div class="simple-card">
                <p style="text-align: center; padding: 40px;">
                    Noch keine eigenen Freebies erstellt<br>
                    <a href="/public/customer/edit-freebie.php" class="simple-btn" style="margin-top: 20px;">✨ Jetzt erstellen</a>
                </p>
            </div>
        <?php else: ?>
            <div class="simple-grid">
                <?php foreach ($customFreebies as $custom): ?>
                    <div class="simple-card">
                        <h3><?php echo htmlspecialchars($custom['headline']); ?></h3>
                        <p><?php echo htmlspecialchars($custom['subheadline'] ?? ''); ?></p>
                        
                        <div>
                            <a href="/customer/freebie-preview.php?id=<?php echo $custom['id']; ?>" 
                               class="simple-btn">👁️ Vorschau</a>
                            <a href="/public/customer/edit-freebie.php?id=<?php echo $custom['id']; ?>" 
                               class="simple-btn">✏️ Bearbeiten</a>
                        </div>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1);">
                            <p style="font-size: 12px; color: #888; margin-bottom: 5px;">🔗 Freebie-Link:</p>
                            <input type="text" readonly 
                                   value="<?php echo $protocol . '://' . $domain . '/freebie/index.php?id=' . $custom['unique_id']; ?>"
                                   style="width: 100%; padding: 8px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 4px; color: white; font-size: 11px;"
                                   onclick="this.select();">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-header button').forEach(el => el.classList.remove('active'));
    
    // Show selected
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>