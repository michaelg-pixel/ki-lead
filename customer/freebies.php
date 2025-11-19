<?php
// Freebies Section für Customer Dashboard
// Diese Datei wird über dashboard.php?page=freebies eingebunden

// Sicherstellen, dass $pdo verfügbar ist
if (!isset($pdo)) {
    die('Datenbankverbindung fehlt');
}

// Customer ID holen
$customer_id = $_SESSION['user_id'] ?? 0;

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
    
    // Eigene Custom Freebies UND gekaufte Marktplatz-Freebies laden
    // WICHTIG: Auch Freebies ohne freebie_type (Marktplatz-Käufe) anzeigen!
    $stmt_custom = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE customer_id = ? 
        AND (
            freebie_type = 'custom' 
            OR copied_from_freebie_id IS NOT NULL
            OR original_creator_id IS NOT NULL
        )
        ORDER BY updated_at DESC, created_at DESC
    ");
    $stmt_custom->execute([$customer_id]);
    $custom_freebies = $stmt_custom->fetchAll(PDO::FETCH_ASSOC);
    
    // Prüfen, welche Freebies der Kunde bereits bearbeitet hat
    $stmt_customer = $pdo->prepare("
        SELECT template_id, id as customer_freebie_id, url_slug, unique_id
        FROM customer_freebies 
        WHERE customer_id = ? AND freebie_type = 'template'
    ");
    $stmt_customer->execute([$customer_id]);
    $customer_freebies = [];
    while ($row = $stmt_customer->fetch(PDO::FETCH_ASSOC)) {
        $customer_freebies[$row['template_id']] = $row;
    }
    
} catch (PDOException $e) {
    $freebies = [];
    $custom_freebies = [];
    $customer_freebies = [];
    $error = $e->getMessage();
}

// Domain für vollständige URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
?>

<style>
    .section-tabs {
        display: flex;
        gap: 12px;
        margin-bottom: 32px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        padding: 8px;
    }
    
    .section-tab {
        flex: 1;
        padding: 14px 24px;
        border: none;
        border-radius: 8px;
        background: transparent;
        color: #888;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .section-tab:hover {
        background: rgba(255, 255, 255, 0.05);
        color: white;
    }
    
    .section-tab.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .section-content {
        display: none;
    }
    
    .section-content.active {
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
    
    .freebie-card.locked {
        opacity: 0.6;
        border-color: rgba(239, 68, 68, 0.3);
    }
    
    .freebie-card.locked:hover {
        transform: translateY(-4px);
        border-color: rgba(239, 68, 68, 0.5);
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
        background: rgba(139, 92, 246, 0.95);
        color: white;
    }
    
    .badge-marketplace {
        background: rgba(249, 115, 22, 0.95);
        color: white;
    }
    
    /* 🟢 UNLOCK STATUS DOT - Zeigt Freischaltungs-Status */
    .unlock-status-dot {
        position: absolute;
        bottom: 16px;
        right: 16px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
        z-index: 10;
        opacity: 0;
        transition: all 0.3s ease;
    }
    
    .unlock-status-dot.loaded {
        opacity: 1;
    }
    
    .status-unlocked {
        background: #22c55e;
        animation: pulse-green 2s ease-in-out infinite;
    }
    
    .status-locked {
        background: #ef4444;
        animation: pulse-red 2s ease-in-out infinite;
    }
    
    @keyframes pulse-green {
        0%, 100% { box-shadow: 0 3px 12px rgba(34, 197, 94, 0.5); }
        50% { box-shadow: 0 3px 20px rgba(34, 197, 94, 0.8); }
    }
    
    @keyframes pulse-red {
        0%, 100% { box-shadow: 0 3px 12px rgba(239, 68, 68, 0.5); }
        50% { box-shadow: 0 3px 20px rgba(239, 68, 68, 0.8); }
    }
    
    .status-no-course {
        display: none;
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
    
    .action-btn-locked {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .action-btn-locked:hover {
        transform: none;
        box-shadow: none;
    }
    
    .action-btn-create {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        font-size: 16px;
        padding: 16px 32px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 700;
        transition: all 0.2s;
    }
    
    .action-btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
    }
    
    .stats-box {
        background: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 16px;
        display: flex;
        justify-content: space-around;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: white;
    }
    
    .stat-label {
        font-size: 11px;
        color: #888;
        text-transform: uppercase;
    }
    
    /* Debug Box */
    .debug-box {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.9);
        border: 2px solid #22c55e;
        border-radius: 8px;
        padding: 16px;
        color: white;
        font-family: monospace;
        font-size: 12px;
        max-width: 400px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 9999;
    }
    
    .debug-box .log-line {
        margin: 4px 0;
    }
    
    .debug-box .log-success { color: #22c55e; }
    .debug-box .log-error { color: #ef4444; }
    .debug-box .log-info { color: #3b82f6; }
    
    @media (max-width: 768px) {
        .freebies-grid {
            grid-template-columns: 1fr;
        }
        
        .section-tabs {
            flex-direction: column;
        }
        
        .debug-box {
            bottom: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
        }
    }
</style>

<div style="padding: 32px;">
    
    <!-- Header -->
    <div style="margin-bottom: 32px;">
        <h1 style="font-size: 36px; font-weight: 700; color: white; margin-bottom: 12px;">
            🎁 Freebies & Lead-Magneten
        </h1>
        <p style="font-size: 18px; color: #888;">
            Erstelle eigene Freebies oder nutze professionelle Templates
        </p>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #f87171;">
            <strong>⚠️ Fehler beim Laden:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Section Tabs -->
    <div class="section-tabs">
        <button class="section-tab active" onclick="switchSection('custom')">
            ✨ Meine Freebies
        </button>
        <button class="section-tab" onclick="switchSection('templates')">
            📚 Templates
        </button>
    </div>
    
    <!-- SECTION: CUSTOM FREEBIES -->
    <div class="section-content active" id="section-custom">
        <div style="text-align: center; margin-bottom: 32px;">
            <a href="custom-freebie-editor-tabs.php" class="action-btn-create">
                + Neues Freebie erstellen
            </a>
        </div>
        
        <?php if (empty($custom_freebies)): ?>
            <div style="text-align: center; padding: 80px 20px; background: rgba(255, 255, 255, 0.05); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="font-size: 64px; margin-bottom: 20px;">✨</div>
                <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">Noch keine eigenen Freebies</h3>
                <p style="font-size: 16px; color: #888; margin-bottom: 24px;">
                    Erstelle dein erstes individuelles Freebie mit unserem Editor!<br>
                    Inkl. Videokurs-Integration, E-Mail Optin und mehr.
                </p>
                <a href="custom-freebie-editor-tabs.php" class="action-btn-create">
                    🚀 Jetzt erstellen
                </a>
            </div>
        <?php else: ?>
            <div class="freebies-grid">
                <?php foreach ($custom_freebies as $freebie): 
                    $bgColor = $freebie['background_color'] ?: '#667eea';
                    $primaryColor = $freebie['primary_color'] ?: '#667eea';
                    
                    $preview_url = 'freebie-preview.php?id=' . $freebie['id'];
                    $identifier = $freebie['url_slug'] ?: $freebie['unique_id'];
                    $live_url = $protocol . '://' . $domain . '/freebie/' . $identifier;
                    
                    $date = new DateTime($freebie['updated_at'] ?: $freebie['created_at']);
                    $formattedDate = $date->format('d.m.Y');
                    
                    // Check ob vom Marktplatz gekauft
                    $isMarketplace = !empty($freebie['copied_from_freebie_id']);
                ?>
                    <div class="freebie-card">
                        <div class="freebie-preview" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <div class="freebie-badges">
                                <?php if ($isMarketplace): ?>
                                    <span class="freebie-badge badge-marketplace">🛒 Vom Marktplatz</span>
                                <?php else: ?>
                                    <span class="freebie-badge badge-custom">Eigenes Freebie</span>
                                <?php endif; ?>
                                <?php if (isset($freebie['has_course']) && $freebie['has_course']): ?>
                                <span class="freebie-badge">🎓 Mit Videokurs</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($freebie['mockup_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($freebie['headline']); ?>">
                            <?php else: ?>
                                <div class="freebie-preview-placeholder" style="color: <?php echo htmlspecialchars($primaryColor); ?>;">
                                    🎁
                                </div>
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
                                <span>📅 <?php echo $formattedDate; ?></span>
                                <span>🔗 <?php echo htmlspecialchars($identifier); ?></span>
                            </div>
                            
                            <div class="stats-box">
                                <div class="stat-item">
                                    <div class="stat-value">0</div>
                                    <div class="stat-label">Aufrufe</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">0</div>
                                    <div class="stat-label">Conversions</div>
                                </div>
                            </div>
                            
                            <div class="freebie-colors">
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;"></div>
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;"></div>
                            </div>
                            
                            <div class="freebie-actions">
                                <a href="<?php echo htmlspecialchars($preview_url); ?>" class="action-btn action-btn-preview">
                                    👁️ Vorschau
                                </a>
                                <a href="custom-freebie-editor-tabs.php?id=<?php echo $freebie['id']; ?>" class="action-btn action-btn-edit">
                                    ✏️ Bearbeiten
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- SECTION: TEMPLATES -->
    <div class="section-content" id="section-templates">
        <?php if (empty($freebies)): ?>
            <div style="text-align: center; padding: 80px 20px; background: rgba(255, 255, 255, 0.05); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
                <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">Noch keine Templates verfügbar</h3>
                <p style="font-size: 16px; color: #888; margin-bottom: 24px;">Schau bald wieder vorbei! Wir erstellen gerade großartige Templates für dich.</p>
            </div>
        <?php else: ?>
            <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 32px;">
                <h3 style="color: white; font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 So funktioniert's</h3>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>1.</strong> Wähle ein Template aus unserer Bibliothek</p>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>2.</strong> Templates mit 🟢 grünem Punkt sind freigeschaltet</p>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>3.</strong> Templates mit 🔴 rotem Punkt erfordern einen Produktkauf</p>
                <p style="color: #bbb; font-size: 14px; line-height: 1.6;"><strong>4.</strong> Füge deinen E-Mail-Optin Code ein und teile den Link!</p>
            </div>
            
            <div class="freebies-grid">
                <?php foreach ($freebies as $freebie): 
                    $isUsedByCustomer = isset($customer_freebies[$freebie['id']]);
                    $customer_data = $customer_freebies[$freebie['id']] ?? null;
                    
                    if ($isUsedByCustomer && $customer_data) {
                        $preview_url = 'freebie-preview.php?id=' . $customer_data['customer_freebie_id'];
                        $live_url = $protocol . '://' . $domain . '/freebie/' . ($customer_data['url_slug'] ?: $customer_data['unique_id']);
                    } else {
                        $identifier = $freebie['url_slug'] ?: $freebie['unique_id'];
                        $preview_url = 'https://app.mehr-infos-jetzt.de/freebie/' . $identifier;
                        $live_url = $preview_url;
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
                    <div class="freebie-card" data-card-id="<?php echo $freebie['id']; ?>">
                        <div class="freebie-preview" style="background: <?php echo htmlspecialchars($bgColor); ?>;">
                            <div class="freebie-badges">
                                <?php if ($isUsedByCustomer): ?>
                                    <span class="freebie-badge badge-used">✓ In Verwendung</span>
                                <?php endif; ?>
                                <span class="freebie-badge"><?php echo htmlspecialchars($layoutName); ?></span>
                            </div>
                            
                            <!-- 🟢 STATUS-PUNKT: Zeigt ob Template freigeschaltet ist -->
                            <div class="unlock-status-dot" data-template-id="<?php echo $freebie['id']; ?>"></div>
                            
                            <?php if (!empty($freebie['mockup_image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($freebie['name']); ?>">
                            <?php elseif (!empty($freebie['course_thumbnail'])): ?>
                                <img src="../uploads/thumbnails/<?php echo htmlspecialchars($freebie['course_thumbnail']); ?>" 
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
                            
                            <?php if ($isUsedByCustomer): ?>
                                <div class="stats-box">
                                    <div class="stat-item">
                                        <div class="stat-value">0</div>
                                        <div class="stat-label">Aufrufe</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value">0</div>
                                        <div class="stat-label">Conversions</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="freebie-colors">
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;" title="Hintergrundfarbe"></div>
                                <div class="color-dot" style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;" title="Primärfarbe"></div>
                            </div>
                            
                            <div class="freebie-actions">
                                <a href="<?php echo htmlspecialchars($preview_url); ?>" 
                                   <?php echo !$isUsedByCustomer ? 'target="_blank"' : ''; ?>
                                   class="action-btn action-btn-preview">
                                    👁️ <?php echo $isUsedByCustomer ? 'Meine Version' : 'Vorschau'; ?>
                                </a>
                                <a href="freebie-editor.php?template_id=<?php echo $freebie['id']; ?>" 
                                   class="action-btn action-btn-edit"
                                   data-use-btn="<?php echo $freebie['id']; ?>">
                                    <?php echo $isUsedByCustomer ? '✏️ Bearbeiten' : '✨ Nutzen'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Debug Console -->
<div class="debug-box" id="debugConsole">
    <strong>🔍 Debug Console</strong>
    <div id="debugLog"></div>
</div>

<script>
function switchSection(section) {
    // Update tabs
    document.querySelectorAll('.section-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update content
    document.querySelectorAll('.section-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById('section-' + section).classList.add('active');
}

// Debug Logger
const debugLog = document.getElementById('debugLog');
function log(message, type = 'info') {
    const time = new Date().toLocaleTimeString();
    const className = 'log-' + type;
    debugLog.innerHTML += `<div class="log-line ${className}">[${time}] ${message}</div>`;
    debugLog.scrollTop = debugLog.scrollHeight;
    console.log(message);
}

// 🟢 UNLOCK STATUS LADEN - Prüft Freischaltungs-Status via API
async function loadUnlockStatus() {
    log('🚀 Starte Unlock-Status Check...', 'info');
    
    try {
        log('📡 Rufe API auf: /customer/api/check-freebie-unlock-status.php', 'info');
        const response = await fetch('/customer/api/check-freebie-unlock-status.php');
        
        log(`📊 HTTP Status: ${response.status}`, response.ok ? 'success' : 'error');
        
        if (!response.ok) {
            log(`❌ API Fehler: ${response.status} ${response.statusText}`, 'error');
            return;
        }
        
        const data = await response.json();
        log(`✓ JSON Response erhalten`, 'success');
        log(`📦 Total Templates: ${data.total_templates}`, 'info');
        log(`🔓 Unlocked Count: ${data.unlocked_count}`, 'info');
        
        if (!data.success || !data.statuses) {
            log('❌ Ungültige API Response: ' + JSON.stringify(data).substring(0, 100), 'error');
            return;
        }
        
        let processedCount = 0;
        
        // Status für jedes Template setzen
        for (const [key, status] of Object.entries(data.statuses)) {
            // Nur Template-Status verarbeiten (nicht customer_freebies)
            if (key.startsWith('template_')) {
                const templateId = key.replace('template_', '');
                const dot = document.querySelector(`[data-template-id="${templateId}"]`);
                const card = document.querySelector(`[data-card-id="${templateId}"]`);
                const useBtn = document.querySelector(`[data-use-btn="${templateId}"]`);
                
                if (dot) {
                    // Status-Klasse setzen
                    dot.classList.remove('status-unlocked', 'status-locked', 'status-no-course');
                    
                    if (status.unlock_status === 'unlocked') {
                        dot.classList.add('status-unlocked');
                        dot.title = '✓ Freigeschaltet';
                        log(`✓ Template ${templateId} (${status.name}): UNLOCKED 🟢`, 'success');
                    } else if (status.unlock_status === 'locked') {
                        dot.classList.add('status-locked');
                        dot.title = '🔒 Gesperrt - Produkt kaufen erforderlich';
                        
                        // Card als locked markieren
                        if (card) card.classList.add('locked');
                        
                        // "Nutzen" Button blockieren
                        if (useBtn) {
                            useBtn.classList.remove('action-btn-edit');
                            useBtn.classList.add('action-btn-locked');
                            useBtn.innerHTML = '🔒 Gesperrt';
                            useBtn.onclick = function(e) {
                                e.preventDefault();
                                alert('🔒 Dieses Template ist gesperrt.\n\nBitte kaufe das zugehörige Produkt um es freizuschalten.');
                                return false;
                            };
                            useBtn.href = 'javascript:void(0)';
                        }
                        
                        log(`🔒 Template ${templateId} (${status.name}): LOCKED 🔴`, 'error');
                    } else if (status.unlock_status === 'no_course') {
                        dot.classList.add('status-no-course'); // Wird nicht angezeigt
                        log(`⊘ Template ${templateId} (${status.name}): NO_COURSE`, 'info');
                    }
                    
                    // Punkt sichtbar machen
                    dot.classList.add('loaded');
                    processedCount++;
                } else {
                    log(`⚠️ Dot nicht gefunden für Template ${templateId}`, 'error');
                }
            }
        }
        
        log(`✅ Unlock-Status geladen! ${processedCount} Templates verarbeitet`, 'success');
        
    } catch (error) {
        log(`❌ JavaScript Fehler: ${error.message}`, 'error');
        log(`Stack: ${error.stack}`, 'error');
    }
}

// Status laden sobald Seite geladen ist
log('⏳ Warte auf DOM...', 'info');
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        log('✓ DOM geladen', 'success');
        loadUnlockStatus();
    });
} else {
    log('✓ DOM bereits geladen', 'success');
    loadUnlockStatus();
}
</script>
