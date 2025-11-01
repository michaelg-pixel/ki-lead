<?php
// Freebies Section für Customer Dashboard
// Diese Datei wird über dashboard.php?page=freebies eingebunden

// Globale PDO-Variable verwenden
global $pdo;

// Sicherstellen, dass $pdo verfügbar ist
if (!isset($pdo)) {
    // Fallback: Versuche Verbindung selbst herzustellen
    require_once '../config/database.php';
    $pdo = getDBConnection();
}

// Customer ID holen (mit Fallback)
if (!isset($customer_id)) {
    $customer_id = $_SESSION['user_id'] ?? 0;
}

// Domain für vollständige URLs
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

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
        ORDER BY f.created_at DESC
    ");
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prüfen, welche Freebies der Kunde bereits bearbeitet hat
    $stmt_customer = $pdo->prepare("
        SELECT template_id, id as customer_freebie_id, unique_id
        FROM customer_freebies 
        WHERE customer_id = ?
    ");
    $stmt_customer->execute([$customer_id]);
    $customer_freebies_data = [];
    while ($row = $stmt_customer->fetch(PDO::FETCH_ASSOC)) {
        $customer_freebies_data[$row['template_id']] = [
            'id' => $row['customer_freebie_id'],
            'unique_id' => $row['unique_id']
        ];
    }
    
} catch (PDOException $e) {
    $freebies = [];
    $customer_freebies_data = [];
    $error = $e->getMessage();
}
?>

<style>
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
    
    /* LINK SECTIONS */
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
            Nutze unsere professionell erstellten Freebie-Templates für dein Marketing
        </p>
    </div>
    
    <?php if (isset($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 16px; margin-bottom: 24px; color: #f87171;">
            <strong>⚠️ Fehler beim Laden:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($freebies)): ?>
        <!-- Leerer Zustand -->
        <div style="text-align: center; padding: 80px 20px; background: rgba(255, 255, 255, 0.05); border-radius: 16px; border: 1px solid rgba(255, 255, 255, 0.1);">
            <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
            <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">Noch keine Freebies verfügbar</h3>
            <p style="font-size: 16px; color: #888; margin-bottom: 24px;">Schau bald wieder vorbei! Wir erstellen gerade großartige kostenlose Inhalte für dich.</p>
            <a href="?page=kurse" class="action-btn action-btn-edit">
                📚 Zu den Kursen
            </a>
        </div>
    <?php else: ?>
        
        <!-- Info Box -->
        <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 32px;">
            <h3 style="color: white; font-size: 16px; font-weight: 600; margin-bottom: 12px;">💡 So funktioniert's</h3>
            <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>1.</strong> Wähle ein Freebie-Template aus unserer Bibliothek</p>
            <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>2.</strong> Klicke auf "Nutzen" um es zu bearbeiten und anzupassen</p>
            <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>3.</strong> Füge deinen E-Mail-Optin Code ein und passe die Farben an</p>
            <p style="color: #bbb; font-size: 14px; line-height: 1.6; margin-bottom: 8px;"><strong>4.</strong> Kopiere die Links und teile sie in deinem Marketing!</p>
            <p style="color: #60a5fa; font-size: 13px; margin-top: 12px;">✨ Die Links werden erst verfügbar, nachdem du das Template bearbeitet und gespeichert hast!</p>
        </div>
        
        <!-- Freebies Grid -->
        <div class="freebies-grid">
            <?php foreach ($freebies as $freebie): 
                $isUsedByCustomer = isset($customer_freebies_data[$freebie['id']]);
                $customer_freebie_data = $customer_freebies_data[$freebie['id']] ?? null;
                
                // Vorschau URL
                if ($isUsedByCustomer && $customer_freebie_data) {
                    // Zeige die Customer-Version
                    $previewUrl = '/customer/freebie-preview.php?id=' . $customer_freebie_data['id'];
                    $previewTarget = '';
                } else {
                    // Zeige Template-Vorschau
                    $previewUrl = '/template-preview.php?template_id=' . $freebie['id'];
                    $previewTarget = 'target="_blank" rel="noopener noreferrer"';
                }
                
                // Editor URL
                $editorUrl = '/customer/freebie-editor.php?template_id=' . $freebie['id'];
                
                // Öffentliche Links - CUSTOMER-VERSION wenn bearbeitet
                $freebieLink = '';
                $thankYouLink = '';
                
                if ($isUsedByCustomer && $customer_freebie_data && !empty($customer_freebie_data['unique_id'])) {
                    // WICHTIG: Parameter-URL verwenden (funktioniert), nicht Clean URL
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
                    <!-- Bild / Vorschau -->
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
                    
                    <!-- Content -->
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
                            <div class="color-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;" title="Hintergrundfarbe"></div>
                            <div class="color-dot" style="background-color: <?php echo htmlspecialchars($primaryColor); ?>;" title="Primärfarbe"></div>
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
                        
                        <!-- LINKS SECTION - Nur wenn bearbeitet -->
                        <?php if (!empty($freebieLink)): ?>
                            <div class="link-sections">
                                <!-- Freebie Link -->
                                <div class="link-section">
                                    <div class="link-header">
                                        <span class="link-icon">🔗</span>
                                        <span>Freebie-Link (Deine Version)</span>
                                    </div>
                                    <div class="link-item">
                                        <input type="text" 
                                               readonly 
                                               value="<?php echo htmlspecialchars($freebieLink); ?>" 
                                               class="link-input" 
                                               id="freebie-link-<?php echo $freebie['id']; ?>">
                                        <button onclick="copyCustomerLink('freebie-link-<?php echo $freebie['id']; ?>')" 
                                                class="btn-copy" 
                                                title="Link kopieren">
                                            📋
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Danke-Seite Link -->
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
                                                class="btn-copy" 
                                                title="Link kopieren">
                                            📋
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="link-notice">
                                ⚠️ Klicke auf "Nutzen" und speichere deine Änderungen, um die Links zu aktivieren
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA Banner -->
        <div style="margin-top: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: 32px; text-align: center;">
            <h3 style="font-size: 24px; color: white; margin-bottom: 12px;">💡 Tipp: Kombiniere Freebies mit Videokursen</h3>
            <p style="font-size: 16px; color: rgba(255, 255, 255, 0.9); margin-bottom: 24px;">
                Erstelle ein attraktives Freebie und verbinde es mit einem unserer professionellen Videokurse, 
                um maximale Conversion-Raten zu erzielen!
            </p>
            <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                <a href="?page=kurse" style="padding: 12px 24px; background: white; color: #667eea; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none;">
                    📚 Kurse ansehen
                </a>
                <a href="?page=fortschritt" style="padding: 12px 24px; background: rgba(255, 255, 255, 0.2); color: white; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none;">
                    📈 Fortschritt
                </a>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<script>
// Link kopieren Funktion
function copyCustomerLink(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    input.select();
    input.setSelectionRange(0, 99999); // Für mobile Geräte
    
    try {
        document.execCommand('copy');
        
        // Button-Feedback
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
        
        // Zusätzlich moderne Clipboard API verwenden (falls unterstützt)
        if (navigator.clipboard) {
            navigator.clipboard.writeText(input.value);
        }
    } catch (err) {
        console.error('Fehler beim Kopieren:', err);
        alert('Fehler beim Kopieren. Bitte manuell kopieren.');
    }
}
</script>