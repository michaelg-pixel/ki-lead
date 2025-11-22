<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * COMPLETE VERSION - Mit Lock-Check, Marketplace-Import und vollständiger Verwaltung
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// SPERRE: Prüfe ob Empfehlungsprogramm aktiviert ist
require_once __DIR__ . '/belohnungsstufen-lock-check.php';

// Ab hier: Normaler Code für aktivierte Programme

// Freebie-ID aus URL Parameter holen (optional)
$freebie_id = isset($_GET['freebie_id']) ? (int)$_GET['freebie_id'] : null;

try {
    $pdo = getDBConnection();
    
    // Freebies des Kunden laden
    $stmt_freebies = $pdo->prepare("
        SELECT 
            cf.id,
            cf.unique_id,
            cf.headline as title,
            cf.mockup_image_url,
            cf.freebie_type,
            COUNT(rd.id) as reward_count
        FROM customer_freebies cf
        LEFT JOIN reward_definitions rd ON rd.freebie_id = cf.id AND rd.user_id = ?
        WHERE cf.customer_id = ?
        GROUP BY cf.id
        ORDER BY cf.created_at DESC
    ");
    $stmt_freebies->execute([$customer_id, $customer_id]);
    $freebies = $stmt_freebies->fetchAll(PDO::FETCH_ASSOC);
    
    // Wenn Freebie-ID gesetzt, Belohnungen für dieses Freebie laden
    $rewards = [];
    if ($freebie_id) {
        $stmt_rewards = $pdo->prepare("
            SELECT 
                id,
                reward_title,
                reward_description,
                reward_icon,
                reward_color,
                referrals_required,
                delivery_type,
                email_subject,
                email_body,
                download_url,
                created_at
            FROM reward_definitions
            WHERE freebie_id = ? AND user_id = ?
            ORDER BY referrals_required ASC
        ");
        $stmt_rewards->execute([$freebie_id, $customer_id]);
        $rewards = $stmt_rewards->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Belohnungsstufen Error: " . $e->getMessage());
    $freebies = [];
    $rewards = [];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .freebie-selector {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .freebie-card {
            background: rgba(0, 0, 0, 0.2);
            border: 2px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.75rem;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .freebie-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .freebie-card.active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }
        
        .freebie-mockup {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin: 0 auto 0.75rem;
        }
        
        .freebie-title {
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .freebie-badge {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        .rewards-container {
            display: grid;
            gap: 1rem;
        }
        
        .reward-card {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: center;
        }
        
        .reward-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .reward-content {
            flex: 1;
        }
        
        .reward-title {
            color: white;
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .reward-description {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .reward-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .reward-badge {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .reward-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .btn-secondary {
            background: #374151;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 1rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #374151;
            margin-bottom: 1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            padding: 2rem;
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border-radius: 1rem;
            max-width: 600px;
            width: 100%;
            padding: 2rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.5rem;
            color: white;
            font-size: 0.9375rem;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .color-picker-wrapper {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .color-preview {
            width: 60px;
            height: 60px;
            border-radius: 0.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body style="background: linear-gradient(to bottom right, #1f2937, #111827); min-height: 100vh; padding: 1rem;">
    <div style="max-width: 1280px; margin: 0 auto;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: white; margin-bottom: 0.5rem;">
                        <i class="fas fa-trophy"></i> Belohnungsstufen
                    </h1>
                    <p style="color: rgba(255, 255, 255, 0.9);">
                        Erstelle automatische Belohnungen für deine Leads
                    </p>
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button class="btn btn-success" id="createBtn" onclick="openRewardModal()" <?php echo !$freebie_id ? 'disabled title="Bitte wähle zuerst ein Freebie"' : ''; ?>>
                        <i class="fas fa-plus"></i> Neue Belohnung
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Freebie Selector -->
        <div class="freebie-selector">
            <h2 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem;">
                <i class="fas fa-gift"></i> Freebie auswählen
            </h2>
            <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1rem;">
                Wähle ein Freebie aus, um Belohnungsstufen zu verwalten
            </p>
            
            <?php if (empty($freebies)): ?>
                <div style="text-align: center; padding: 2rem; background: rgba(0, 0, 0, 0.2); border-radius: 0.75rem;">
                    <p style="color: #9ca3af;">
                        Du hast noch keine Freebies. <a href="?page=freebies" style="color: #667eea;">Jetzt freischalten</a>
                    </p>
                </div>
            <?php else: ?>
                <div class="freebie-grid">
                    <?php foreach ($freebies as $freebie): ?>
                        <div class="freebie-card <?php echo $freebie['id'] == $freebie_id ? 'active' : ''; ?>" 
                             onclick="selectFreebie(<?php echo $freebie['id']; ?>)">
                            <?php if ($freebie['mockup_image_url']): ?>
                                <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                     class="freebie-mockup" 
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="freebie-mockup" style="background: rgba(102, 126, 234, 0.2); display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-gift" style="font-size: 2rem; color: #667eea;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="freebie-title"><?php echo htmlspecialchars($freebie['title']); ?></div>
                            <div class="freebie-badge">
                                <?php echo $freebie['reward_count']; ?> Belohnungen
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Rewards List -->
        <?php if ($freebie_id): ?>
            <div style="background: linear-gradient(to bottom right, #1f2937, #374151); border: 1px solid rgba(102, 126, 234, 0.3); border-radius: 1rem; padding: 2rem;">
                <h2 style="color: white; font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem;">
                    <i class="fas fa-list"></i> Belohnungsstufen
                </h2>
                
                <?php if (empty($rewards)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 style="color: white; font-size: 1.125rem; margin-bottom: 0.5rem;">
                            Noch keine Belohnungen
                        </h3>
                        <p style="color: #9ca3af; margin-bottom: 1.5rem;">
                            Erstelle deine erste Belohnungsstufe für dieses Freebie
                        </p>
                        <button class="btn btn-primary" onclick="openRewardModal()">
                            <i class="fas fa-plus"></i> Belohnung erstellen
                        </button>
                    </div>
                <?php else: ?>
                    <div class="rewards-container">
                        <?php foreach ($rewards as $reward): ?>
                            <div class="reward-card">
                                <div class="reward-icon" style="background: <?php echo htmlspecialchars($reward['reward_color'] ?? '#667eea'); ?>;">
                                    <?php echo $reward['reward_icon'] ?? '🎁'; ?>
                                </div>
                                
                                <div class="reward-content">
                                    <div class="reward-title">
                                        <?php echo htmlspecialchars($reward['reward_title']); ?>
                                    </div>
                                    <?php if ($reward['reward_description']): ?>
                                        <div class="reward-description">
                                            <?php echo htmlspecialchars($reward['reward_description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="reward-meta">
                                        <span class="reward-badge">
                                            <i class="fas fa-users"></i> <?php echo $reward['referrals_required']; ?> Empfehlungen
                                        </span>
                                        <span class="reward-badge">
                                            <i class="fas fa-<?php echo $reward['delivery_type'] === 'email' ? 'envelope' : 'link'; ?>"></i>
                                            <?php echo $reward['delivery_type'] === 'email' ? 'E-Mail' : 'Download'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="reward-actions">
                                    <button class="btn btn-secondary" onclick="editReward(<?php echo $reward['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteReward(<?php echo $reward['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Reward Modal -->
    <div id="rewardModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2 style="color: white; font-size: 1.5rem; font-weight: 700;">
                    <i class="fas fa-trophy"></i> <span id="modalTitle">Neue Belohnung</span>
                </h2>
                <button onclick="closeRewardModal()" style="background: none; border: none; color: #9ca3af; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="rewardForm" onsubmit="saveReward(event)">
                <input type="hidden" id="rewardId" value="">
                
                <div class="form-group">
                    <label class="form-label">Titel der Belohnung *</label>
                    <input type="text" id="rewardTitle" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea id="rewardDescription" class="form-textarea"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Benötigte Empfehlungen *</label>
                    <input type="number" id="referralsRequired" class="form-input" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon (Emoji)</label>
                    <input type="text" id="rewardIcon" class="form-input" placeholder="🎁">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Farbe</label>
                    <div class="color-picker-wrapper">
                        <input type="color" id="rewardColor" value="#667eea">
                        <div class="color-preview" id="colorPreview" style="background: #667eea;"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Auslieferung *</label>
                    <select id="deliveryType" class="form-select" onchange="toggleDeliveryFields()">
                        <option value="email">E-Mail</option>
                        <option value="download">Download-Link</option>
                    </select>
                </div>
                
                <div id="emailFields">
                    <div class="form-group">
                        <label class="form-label">E-Mail Betreff</label>
                        <input type="text" id="emailSubject" class="form-input">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-Mail Text</label>
                        <textarea id="emailBody" class="form-textarea"></textarea>
                    </div>
                </div>
                
                <div id="downloadFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Download-URL</label>
                        <input type="url" id="downloadUrl" class="form-input">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeRewardModal()">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const freebieId = <?php echo $freebie_id ?: 'null'; ?>;
        
        function selectFreebie(id) {
            window.location.href = '?page=belohnungsstufen&freebie_id=' + id;
        }
        
        function openRewardModal(rewardData = null) {
            document.getElementById('rewardModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            if (rewardData) {
                document.getElementById('modalTitle').textContent = 'Belohnung bearbeiten';
                document.getElementById('rewardId').value = rewardData.id;
                document.getElementById('rewardTitle').value = rewardData.reward_title;
                document.getElementById('rewardDescription').value = rewardData.reward_description || '';
                document.getElementById('referralsRequired').value = rewardData.referrals_required;
                document.getElementById('rewardIcon').value = rewardData.reward_icon || '';
                document.getElementById('rewardColor').value = rewardData.reward_color || '#667eea';
                document.getElementById('deliveryType').value = rewardData.delivery_type;
                document.getElementById('emailSubject').value = rewardData.email_subject || '';
                document.getElementById('emailBody').value = rewardData.email_body || '';
                document.getElementById('downloadUrl').value = rewardData.download_url || '';
                
                updateColorPreview();
                toggleDeliveryFields();
            } else {
                document.getElementById('modalTitle').textContent = 'Neue Belohnung';
                document.getElementById('rewardForm').reset();
                document.getElementById('rewardId').value = '';
                updateColorPreview();
            }
        }
        
        function closeRewardModal() {
            document.getElementById('rewardModal').classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function toggleDeliveryFields() {
            const type = document.getElementById('deliveryType').value;
            document.getElementById('emailFields').style.display = type === 'email' ? 'block' : 'none';
            document.getElementById('downloadFields').style.display = type === 'download' ? 'block' : 'none';
        }
        
        function updateColorPreview() {
            const color = document.getElementById('rewardColor').value;
            document.getElementById('colorPreview').style.background = color;
        }
        
        document.getElementById('rewardColor')?.addEventListener('input', updateColorPreview);
        
        async function saveReward(event) {
            event.preventDefault();
            
            const rewardId = document.getElementById('rewardId').value;
            const data = {
                freebie_id: freebieId,
                reward_title: document.getElementById('rewardTitle').value,
                reward_description: document.getElementById('rewardDescription').value,
                referrals_required: parseInt(document.getElementById('referralsRequired').value),
                reward_icon: document.getElementById('rewardIcon').value,
                reward_color: document.getElementById('rewardColor').value,
                delivery_type: document.getElementById('deliveryType').value,
                email_subject: document.getElementById('emailSubject').value,
                email_body: document.getElementById('emailBody').value,
                download_url: document.getElementById('downloadUrl').value
            };
            
            if (rewardId) {
                data.reward_id = parseInt(rewardId);
            }
            
            try {
                const response = await fetch('/api/rewards/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Belohnung gespeichert!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('❌ ' + (result.error || 'Fehler beim Speichern'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
            }
        }
        
        async function editReward(id) {
            try {
                const response = await fetch('/api/rewards/get.php?id=' + id);
                const data = await response.json();
                
                if (data.success) {
                    openRewardModal(data.reward);
                } else {
                    showNotification('❌ Fehler beim Laden', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
            }
        }
        
        async function deleteReward(id) {
            if (!confirm('Möchtest du diese Belohnung wirklich löschen?')) {
                return;
            }
            
            try {
                const response = await fetch('/api/rewards/delete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reward_id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('✅ Belohnung gelöscht', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('❌ ' + (result.error || 'Fehler beim Löschen'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('❌ Verbindungsfehler', 'error');
            }
        }
        
        function showNotification(message, type = 'info') {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                info: '#3b82f6'
            };
            
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
                z-index: 99999;
                animation: slideIn 0.3s ease-out;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Close modal on outside click
        document.getElementById('rewardModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRewardModal();
            }
        });
    </script>
</body>
</html>
