<?php
// Freebies Section für Customer Dashboard
// Diese Datei wird über dashboard.php?page=freebies eingebunden

// Globale PDO-Variable verwenden
global $pdo;

// Sicherstellen, dass $pdo verfügbar ist
if (!isset($pdo)) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

// Customer ID holen
if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Domain für vollständige URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

// FREEBIE-LIMIT FÜR KUNDE HOLEN
try {
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $limitData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $freebieLimit = $limitData['freebie_limit'] ?? 0;
    $packageName = $limitData['product_name'] ?? 'Basis';
    
    // Anzahl eigener Freebies zählen
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customer_freebies 
        WHERE customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$customer_id]);
    $customFreebiesCount = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $freebieLimit = 0;
    $customFreebiesCount = 0;
    $packageName = 'Unbekannt';
}

// Freebies aus der Datenbank laden (vom Admin erstellt - Templates)
try {
    $stmt = $pdo->query("
        SELECT 
            f.id,
            f.name,
            f.headline,
            f.subheadline,
            f.preheadline,
            f.mockup_image_url,
            f.background_color,
            f.primary_color,
            f.unique_id,
            f.url_slug,
            f.layout,
            f.cta_text,
            f.bullet_points,
            f.created_at,
            c.title as course_title,
            c.thumbnail as course_thumbnail
        FROM freebies f
        LEFT JOIN courses c ON f.course_id = c.id
        WHERE f.is_active = 1
        ORDER BY f.created_at DESC
    ");
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prüfen, welche Freebies der Kunde bereits bearbeitet hat (template-basiert)
    $stmt_customer = $pdo->prepare("
        SELECT template_id, id as customer_freebie_id, unique_id
        FROM customer_freebies 
        WHERE customer_id = ? AND (freebie_type = 'template' OR freebie_type IS NULL)
    ");
    $stmt_customer->execute([$customer_id]);
    $customer_freebies_data = [];
    while ($row = $stmt_customer->fetch(PDO::FETCH_ASSOC)) {
        if ($row['template_id']) {
            $customer_freebies_data[$row['template_id']] = [
                'id' => $row['customer_freebie_id'],
                'unique_id' => $row['unique_id']
            ];
        }
    }
    
    // EIGENE FREEBIES LADEN (custom type) - NUR vorhandene Spalten!
    $stmt_custom = $pdo->prepare("
        SELECT 
            cf.id,
            cf.headline,
            cf.subheadline,
            cf.background_color,
            cf.primary_color,
            cf.unique_id,
            cf.layout,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.customer_id = ? AND cf.freebie_type = 'custom'
        ORDER BY cf.created_at DESC
    ");
    $stmt_custom->execute([$customer_id]);
    $customFreebies = $stmt_custom->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $freebies = [];
    $customer_freebies_data = [];
    $customFreebies = [];
    $error = $e->getMessage();
}
?>

<style>
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
    
    .limit-info h3 {
        font-size: 20px;
        margin-bottom: 8px;
    }
    
    .limit-info p {
        font-size: 14px;
        opacity: 0.9;
    }
    
    .limit-stats {
        display: flex;
        gap: 24px;
        align-items: center;
    }
    
    .limit-stat {
        text-align: center;
    }
    
    .limit-number {
        font-size: 32px;
        font-weight: 700;
        display: block;
    }
    
    .limit-label {
        font-size: 12px;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .btn-create-custom {
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
    
    .btn-create-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }
    
    .btn-create-custom:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .tab-navigation {
        display: flex;
        gap: 8px;
        margin-bottom: 32px;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0;
    }
    
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

    .freebies-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
    }
    
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
    
    .freebie-preview {
        height: 200px;
        position: relative;
        overflow: hidden;
    }
    
    .freebie-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .freebie-preview-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 64px;
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
    
    .badge-used {
        background: rgba(34, 197, 94, 0.95);
        color: white;
    }
    
    .badge-custom {
        background: rgba(251, 191, 36, 0.95);
        color: white;
    }
    
    .freebie-content {
        padding: 24px;
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
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 12px 0;
        margin-bottom: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 13px;
        color: #888;
    }
    
    .freebie-colors {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
    }
    
    .color-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .freebie-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
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
    
    .action-btn-preview {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .action-btn-preview:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .action-btn-edit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .action-btn-edit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
    }
    
    .action-btn-delete {
        background: rgba(239, 68, 68, 0.2);
        color: #f87171;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
    
    .action-btn-delete:hover {
        background: rgba(239, 68, 68, 0.3);
    }
    
    .link-sections {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .link-section {
        background: rgba(102, 126, 234, 0.08);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 10px;
    }
    
    .link-header {
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
    
    .link-icon {
        font-size: 14px;
    }
    
    .link-item {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .link-input {
        flex: 1;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 6px;
        color: white;
        padding: 8px 12px;
        font-size: 11px;
        font-family: 'Courier New', monospace;
        min-width: 0;
        word-break: break-all;
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
        flex-shrink: 0;
        white-space: nowrap;
    }
    
    .btn-copy:hover {
        background: rgba(102, 126, 234, 0.5);
    }
    
    .link-notice {
        font-size: 11px;
        color: #aaa;
        margin-top: 8px;
        padding: 8px;
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.2);
        border-radius: 6px;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .freebies-grid {
            grid-template-columns: 1fr;
        }
        
        .limit-banner {
            flex-direction: column;
            text-align: center;
        }
        
        .limit-stats {
            width: 100%;
            justify-content: space-around;
        }
        
        .tab-navigation {
            overflow-x: auto;
        }
        
        .link-input {
            font-size: 10px;
            padding: 6px 10px;
        }
        
        .btn-copy {
            padding: 6px 10px;
            font-size: 11px;
        }
    }
</style>

<div style="padding: 32px;">
    
    <!-- Header -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 36px; font-weight: 700; color: white; margin-bottom: 12px;">
            🎁 Lead-Magneten
        </h1>
        <p style="font-size: 18px; color: #888;">
            Nutze Templates oder erstelle eigene Freebie-Seiten
        </p>
    </div>
    
    <!-- Limit Banner -->
    <div class="limit-banner">
        <div class="limit-info">
            <h3>📦 <?php echo htmlspecialchars($packageName); ?></h3>
            <p>Dein Tarif erlaubt dir <?php echo $freebieLimit; ?> eigene Freebie<?php echo $freebieLimit !== 1 ? 's' : ''; ?> zu erstellen</p>
        </div>
        <div class="limit-stats">
            <div class="limit-stat">
                <span class="limit-number"><?php echo $customFreebiesCount; ?></span>
                <span class="limit-label">Erstellt</span>
            </div>
            <div class="limit-stat">
                <span class="limit-number"><?php echo max(0, $freebieLimit - $customFreebiesCount); ?></span>
                <span class="limit-label">Verfügbar</span>
            </div>
        </div>
        <?php if ($customFreebiesCount < $freebieLimit): ?>
            <a href="/customer/custom-freebie-editor.php" class="btn-create-custom">
                ✨ Eigenes Freebie erstellen
            </a>
        <?php else: ?>
            <button class="btn-create-custom" disabled title="Limit erreicht">
                🔒 Limit erreicht
            </button>
        <?php endif; ?>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #f87171;">
            <strong>⚠️ Fehler beim Laden:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Tab Navigation -->
    <div class="tab-navigation">
        <button class="tab-btn active" onclick="switchTab('templates')">
            📚 Templates (<?php echo count($freebies); ?>)
        </button>
        <button class="tab-btn" onclick="switchTab('custom')">
            ✨ Meine Freebies (<?php echo count($customFreebies); ?>)
        </button>
    </div>
    
    <!-- TAB: Templates -->
    <div id="tab-templates" class="tab-content active">
        <?php if (empty($freebies)): ?>
            <div style="text-align: center; padding: 80px 20px; background: rgba(255, 255, 255, 0.05); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
                <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">Noch keine Templates verfügbar</h3>
                <p style="font-size: 16px; color: #888;">Schau bald wieder vorbei!</p>
            </div>
        <?php else: ?>
            <!-- Info Box -->
            <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 32px;">
                <h3 style="color: white; font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 So funktioniert's</h3>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>1.</strong> Wähle ein Freebie-Template aus</p>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>2.</strong> Klicke auf "Nutzen" und passe es an</p>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>3.</strong> Kopiere die Links und nutze sie in deinem Marketing</p>
            </div>
            
            <div class="freebies-grid">
                <?php foreach ($freebies as $freebie): 
                    $isUsedByCustomer = isset($customer_freebies_data[$freebie['id']]);
                    $customer_freebie_data = $customer_freebies_data[$freebie['id']] ?? null;
                    
                    if ($isUsedByCustomer && $customer_freebie_data) {
                        $previewUrl = '/customer/freebie-preview.php?id=' . $customer_freebie_data['id'];
                        $previewTarget = '';
                    } else {
                        $previewUrl = '/template-preview.php?template_id=' . $freebie['id'];
                        $previewTarget = 'target="_blank" rel="noopener noreferrer"';
                    }
                    
                    $editorUrl = '/customer/freebie-editor.php?template_id=' . $freebie['id'];
                    
                    $freebieLink = '';
                    $thankYouLink = '';
                    
                    if ($isUsedByCustomer && $customer_freebie_data && !empty($customer_freebie_data['unique_id'])) {
                        $freebieLink = $protocol . '://' . $domain . '/freebie/index.php?id=' . $customer_freebie_data['unique_id'];
                        $thankYouLink = $protocol . '://' . $domain . '/freebie/thankyou.php?id=' . $customer_freebie_data['id'] . '&customer=' . $customer_id;
                    }
                    
                    $bgColor = $freebie['background_color'] ?: '#667eea';
                    $primaryColor = $freebie['primary_color'] ?: '#667eea';
                    
                    $layoutNames = [
                        'hybrid' => 'Hybrid',
                        'centered' => 'Zentriert',
                        'sidebar' => 'Sidebar',
                        'layout1' => 'Modern',
                        'layout2' => 'Klassisch',
                        'layout3' => 'Minimal'
                    ];
                    $layoutName = $layoutNames[$freebie['layout']] ?? 'Standard';
                    
                    $date = new DateTime($freebie['created_at']);
                    $formattedDate = $date->format('d.m.Y');
                ?>
                    <div class="freebie-card">
                        <div class="freebie-preview" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <div class="freebie-badges">
                                <?php if ($isUsedByCustomer): ?>
                                    <span class="freebie-badge badge-used">✓ In Verwendung</span>
                                <?php endif; ?>
                                <span class="freebie-badge"><?php echo htmlspecialchars($layoutName); ?></span>
                            </div>
                            
                            <?php if (!empty($freebie['mockup_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($freebie['name']); ?>">
                            <?php elseif (!empty($freebie['course_thumbnail'])): ?>
                                <img src="/uploads/thumbnails/<?php echo htmlspecialchars($freebie['course_thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($freebie['name']); ?>">
                            <?php else: ?>
                                <div class="freebie-preview-placeholder" style="color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="freebie-content">
                            <h3 class="freebie-title">
                                <?php echo htmlspecialchars($freebie['headline'] ?: $freebie['name']); ?>
                            </h3>
                            
                            <?php if (!empty($freebie['subheadline'])): ?>
                                <p class="freebie-subtitle">
                                    <?php echo htmlspecialchars($freebie['subheadline']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="freebie-meta">
                                <?php if (!empty($freebie['course_title'])): ?>
                                    <span>📚 <?php echo htmlspecialchars($freebie['course_title']); ?></span>
                                <?php endif; ?>
                                <span>📅 <?php echo $formattedDate; ?></span>
                            </div>
                            
                            <div class="freebie-colors">
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;"></div>
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;"></div>
                            </div>
                            
                            <div class="freebie-actions">
                                <a href="<?php echo htmlspecialchars($previewUrl); ?>" 
                                   <?php echo $previewTarget; ?>
                                   class="action-btn action-btn-preview">
                                    👁️ <?php echo $isUsedByCustomer ? 'Meine Version' : 'Vorschau'; ?>
                                </a>
                                <a href="<?php echo htmlspecialchars($editorUrl); ?>" 
                                   class="action-btn action-btn-edit">
                                    <?php echo $isUsedByCustomer ? '✏️ Bearbeiten' : '✨ Nutzen'; ?>
                                </a>
                            </div>
                            
                            <?php if (!empty($freebieLink)): ?>
                                <div class="link-sections">
                                    <div class="link-section">
                                        <div class="link-header">
                                            <span class="link-icon">🔗</span>
                                            <span>Freebie-Link</span>
                                        </div>
                                        <div class="link-item">
                                            <input type="text" 
                                                   readonly 
                                                   value="<?php echo htmlspecialchars($freebieLink); ?>" 
                                                   class="link-input" 
                                                   id="freebie-link-<?php echo $freebie['id']; ?>">
                                            <button onclick="copyCustomerLink('freebie-link-<?php echo $freebie['id']; ?>')" 
                                                    class="btn-copy">
                                                📋
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="link-section">
                                        <div class="link-header">
                                            <span class="link-icon">🎉</span>
                                            <span>Danke-Seite</span>
                                        </div>
                                        <div class="link-item">
                                            <input type="text" 
                                                   readonly 
                                                   value="<?php echo htmlspecialchars($thankYouLink); ?>" 
                                                   class="link-input" 
                                                   id="thankyou-link-<?php echo $freebie['id']; ?>">
                                            <button onclick="copyCustomerLink('thankyou-link-<?php echo $freebie['id']; ?>')" 
                                                    class="btn-copy">
                                                📋
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="link-notice">
                                    ⚠️ Klicke auf "Nutzen" und speichere, um die Links zu aktivieren
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- TAB: Custom Freebies -->
    <div id="tab-custom" class="tab-content">
        <?php if (empty($customFreebies)): ?>
            <div style="text-align: center; padding: 80px 20px; background: rgba(255, 255, 255, 0.05); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="font-size: 64px; margin-bottom: 20px;">✨</div>
                <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">Noch keine eigenen Freebies</h3>
                <p style="font-size: 16px; color: #888; margin-bottom: 24px;">
                    Erstelle dein erstes eigenes Freebie mit unserem Editor!
                </p>
                <?php if ($customFreebiesCount < $freebieLimit): ?>
                    <a href="/customer/custom-freebie-editor.php" class="btn-create-custom">
                        ✨ Jetzt erstellen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="freebies-grid">
                <?php foreach ($customFreebies as $custom): 
                    $previewUrl = '/customer/freebie-preview.php?id=' . $custom['id'];
                    $editorUrl = '/customer/custom-freebie-editor.php?id=' . $custom['id'];
                    
                    $freebieLink = $protocol . '://' . $domain . '/freebie/index.php?id=' . $custom['unique_id'];
                    $thankYouLink = $protocol . '://' . $domain . '/freebie/thankyou.php?id=' . $custom['id'] . '&customer=' . $customer_id;
                    
                    $bgColor = $custom['background_color'] ?: '#667eea';
                    $primaryColor = $custom['primary_color'] ?: '#667eea';
                    
                    $date = new DateTime($custom['created_at']);
                    $formattedDate = $date->format('d.m.Y');
                ?>
                    <div class="freebie-card">
                        <div class="freebie-preview" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <div class="freebie-badges">
                                <span class="freebie-badge badge-custom">✨ Eigenes Freebie</span>
                            </div>
                            <div class="freebie-preview-placeholder" style="color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                🎁
                            </div>
                        </div>
                        
                        <div class="freebie-content">
                            <h3 class="freebie-title">
                                <?php echo htmlspecialchars($custom['headline']); ?>
                            </h3>
                            
                            <?php if (!empty($custom['subheadline'])): ?>
                                <p class="freebie-subtitle">
                                    <?php echo htmlspecialchars($custom['subheadline']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="freebie-meta">
                                <span>📅 <?php echo $formattedDate; ?></span>
                            </div>
                            
                            <div class="freebie-colors">
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;"></div>
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;"></div>
                            </div>
                            
                            <div class="freebie-actions">
                                <a href="<?php echo htmlspecialchars($previewUrl); ?>" 
                                   class="action-btn action-btn-preview">
                                    👁️ Vorschau
                                </a>
                                <a href="<?php echo htmlspecialchars($editorUrl); ?>" 
                                   class="action-btn action-btn-edit">
                                    ✏️ Bearbeiten
                                </a>
                            </div>
                            
                            <div class="link-sections">
                                <div class="link-section">
                                    <div class="link-header">
                                        <span class="link-icon">🔗</span>
                                        <span>Freebie-Link</span>
                                    </div>
                                    <div class="link-item">
                                        <input type="text" 
                                               readonly 
                                               value="<?php echo htmlspecialchars($freebieLink); ?>" 
                                               class="link-input" 
                                               id="custom-freebie-link-<?php echo $custom['id']; ?>">
                                        <button onclick="copyCustomerLink('custom-freebie-link-<?php echo $custom['id']; ?>')" 
                                                class="btn-copy">
                                            📋
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="link-section">
                                    <div class="link-header">
                                        <span class="link-icon">🎉</span>
                                        <span>Danke-Seite</span>
                                    </div>
                                    <div class="link-item">
                                        <input type="text" 
                                               readonly 
                                               value="<?php echo htmlspecialchars($thankYouLink); ?>" 
                                               class="link-input" 
                                               id="custom-thankyou-link-<?php echo $custom['id']; ?>">
                                        <button onclick="copyCustomerLink('custom-thankyou-link-<?php echo $custom['id']; ?>')" 
                                                class="btn-copy">
                                            📋
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="margin-top: 16px;">
                                <button onclick="deleteCustomFreebie(<?php echo $custom['id']; ?>)" 
                                        class="action-btn action-btn-delete" 
                                        style="width: 100%;">
                                    🗑️ Löschen
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- CTA Banner -->
    <div style="margin-top: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 32px; text-align: center;">
        <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">💡 Kombiniere Freebies mit Videokursen</h3>
        <p style="font-size: 16px; color: rgba(255, 255, 255, 0.9); margin-bottom: 24px;">
            Erstelle attraktive Freebies und verbinde sie mit professionellen Videokursen!
        </p>
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="?page=kurse" style="padding: 12px 24px; background: white; color: #667eea; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none;">
                📚 Kurse ansehen
            </a>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    event.target.classList.add('active');
}

function copyCustomerLink(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        
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
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value);
        }
    } catch (err) {
        console.error('Fehler beim Kopieren:', err);
        alert('Fehler beim Kopieren. Bitte manuell kopieren.');
    }
}

function deleteCustomFreebie(id) {
    if (!confirm('Möchtest du dieses Freebie wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    fetch('/api/delete-custom-freebie.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Fehler beim Löschen: ' + (data.error || 'Unbekannter Fehler'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Fehler beim Löschen');
    });
}
</script>