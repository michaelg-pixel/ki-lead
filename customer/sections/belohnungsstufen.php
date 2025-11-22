<?php
/**
 * Customer Dashboard - Belohnungsstufen verwalten
 * FIXED VERSION - Korrigierte Spaltennamen
 */

// Sicherstellen, dass Session aktiv ist
if (!isset($customer_id)) {
    die('Nicht autorisiert');
}

// SPERRE: Prüfe ob Empfehlungsprogramm aktiviert ist
require_once __DIR__ . '/belohnungsstufen-lock-check.php';

try {
    $pdo = getDBConnection();
    
    // Freebies des Kunden mit Empfehlungslinks laden
    $stmt_freebies = $pdo->prepare("
        SELECT 
            cf.id,
            cf.unique_id,
            cf.headline as title,
            cf.mockup_image_url,
            cf.freebie_type,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.customer_id = ?
        ORDER BY cf.created_at DESC
    ");
    $stmt_freebies->execute([$customer_id]);
    $freebies = $stmt_freebies->fetchAll(PDO::FETCH_ASSOC);
    
    // Für jedes Freebie die Belohnungen laden (mit korrekten Spaltennamen!)
    $freebie_rewards = [];
    foreach ($freebies as $freebie) {
        $stmt_rewards = $pdo->prepare("
            SELECT 
                id,
                reward_title,
                reward_description,
                reward_icon,
                reward_color,
                required_referrals,
                reward_delivery_type,
                email_subject,
                email_body,
                reward_download_url,
                created_at
            FROM reward_definitions
            WHERE freebie_id = ? AND user_id = ?
            ORDER BY required_referrals ASC
        ");
        $stmt_rewards->execute([$freebie['id'], $customer_id]);
        $freebie_rewards[$freebie['id']] = $stmt_rewards->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    error_log("Belohnungsstufen Error: " . $e->getMessage());
    $freebies = [];
    $freebie_rewards = [];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        
        .page-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        .header-title {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .header-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        
        .freebie-container {
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
        
        .freebie-header {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .freebie-mockup {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }
        
        .freebie-mockup-placeholder {
            width: 120px;
            height: 120px;
            background: rgba(102, 126, 234, 0.2);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(102, 126, 234, 0.3);
        }
        
        .freebie-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 1rem;
        }
        
        .freebie-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .referral-link-section {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .referral-link-label {
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .referral-link-wrapper {
            display: flex;
            gap: 0.5rem;
        }
        
        .referral-link-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: white;
            font-size: 0.875rem;
            font-family: 'Courier New', monospace;
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
            white-space: nowrap;
            font-size: 0.9375rem;
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
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
        }
        
        .rewards-section {
            margin-top: 1.5rem;
        }
        
        .rewards-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .rewards-title {
            color: white;
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .rewards-list {
            display: grid;
            gap: 1rem;
        }
        
        .reward-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.2);
            border-radius: 0.75rem;
            padding: 1.25rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.25rem;
            align-items: center;
            transition: all 0.3s;
        }
        
        .reward-card:hover {
            border-color: rgba(102, 126, 234, 0.5);
            background: rgba(0, 0, 0, 0.3);
        }
        
        .reward-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .reward-content {
            flex: 1;
        }
        
        .reward-title {
            color: white;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .reward-description {
            color: #9ca3af;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .reward-meta {
            display: flex;
            gap: 0.75rem;
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
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
            border: 1px dashed rgba(102, 126, 234, 0.3);
        }
        
        .empty-icon {
            font-size: 2.5rem;
            color: #374151;
            margin-bottom: 0.75rem;
        }
        
        .empty-text {
            color: #9ca3af;
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
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .modal-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.9375rem;
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
        
        .no-freebies {
            text-align: center;
            padding: 4rem 2rem;
            background: linear-gradient(to bottom right, #1f2937, #374151);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 1rem;
        }
        
        .no-freebies-icon {
            font-size: 4rem;
            color: #374151;
            margin-bottom: 1rem;
        }
        
        .no-freebies-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .no-freebies-text {
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .freebie-header {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .freebie-mockup,
            .freebie-mockup-placeholder {
                margin: 0 auto;
            }
            
            .referral-link-wrapper {
                flex-direction: column;
            }
            
            .reward-card {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .reward-icon {
                margin: 0 auto;
            }
            
            .reward-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(to bottom right, #1f2937, #111827); min-height: 100vh;">
    <div class="page-container">
        
        <!-- Header -->
        <div class="header-section">
            <h1 class="header-title">
                <i class="fas fa-trophy"></i> Belohnungsstufen
            </h1>
            <p class="header-subtitle">
                Erstelle automatische Belohnungen für jedes deiner Freebies
            </p>
        </div>
        
        <!-- Freebies List -->
        <?php if (empty($freebies)): ?>
            <div class="no-freebies">
                <div class="no-freebies-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h2 class="no-freebies-title">Noch keine Freebies</h2>
                <p class="no-freebies-text">
                    Du benötigst mindestens ein Freebie, um Belohnungsstufen zu erstellen.
                </p>
                <a href="?page=freebies" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Jetzt Freebies freischalten
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($freebies as $freebie): ?>
                <div class="freebie-container">
                    <!-- Freebie Header mit Bild und Info -->
                    <div class="freebie-header">
                        <?php if ($freebie['mockup_image_url']): ?>
                            <img src="<?php echo htmlspecialchars($freebie['mockup_image_url']); ?>" 
                                 class="freebie-mockup" 
                                 alt="<?php echo htmlspecialchars($freebie['title']); ?>"
                                 onerror="this.parentElement.innerHTML='<div class=\'freebie-mockup-placeholder\'><i class=\'fas fa-gift\' style=\'font-size: 3rem; color: #667eea;\'></i></div>'">
                        <?php else: ?>
                            <div class="freebie-mockup-placeholder">
                                <i class="fas fa-gift" style="font-size: 3rem; color: #667eea;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="freebie-info">
                            <h2 class="freebie-title">
                                <?php echo htmlspecialchars($freebie['title']); ?>
                            </h2>
                            
                            <!-- Empfehlungslink -->
                            <div class="referral-link-section">
                                <label class="referral-link-label">
                                    <i class="fas fa-link"></i> Empfehlungslink für dieses Freebie
                                </label>
                                <div class="referral-link-wrapper">
                                    <input type="text" 
                                           class="referral-link-input" 
                                           value="<?php echo 'https://mehr-infos-jetzt.de/f/?id=' . htmlspecialchars($freebie['unique_id']); ?>" 
                                           readonly
                                           id="referralLink_<?php echo $freebie['id']; ?>">
                                    <button class="btn btn-primary btn-sm" onclick="copyReferralLink(<?php echo $freebie['id']; ?>)">
                                        <i class="fas fa-copy"></i> Kopieren
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Belohnungen Section -->
                    <div class="rewards-section">
                        <div class="rewards-header">
                            <h3 class="rewards-title">
                                <i class="fas fa-trophy"></i> 
                                Belohnungsstufen
                                <span style="color: #9ca3af; font-weight: 400; font-size: 0.875rem;">
                                    (<?php echo count($freebie_rewards[$freebie['id']] ?? []); ?>)
                                </span>
                            </h3>
                            <button class="btn btn-success btn-sm" onclick="openRewardModal(<?php echo $freebie['id']; ?>)">
                                <i class="fas fa-plus"></i> Neue Belohnung
                            </button>
                        </div>
                        
                        <?php if (empty($freebie_rewards[$freebie['id']])): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <p class="empty-text">
                                    Noch keine Belohnungen für dieses Freebie
                                </p>
                                <button class="btn btn-primary btn-sm" onclick="openRewardModal(<?php echo $freebie['id']; ?>)">
                                    <i class="fas fa-plus"></i> Erste Belohnung erstellen
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="rewards-list">
                                <?php foreach ($freebie_rewards[$freebie['id']] as $reward): ?>
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
                                                    <i class="fas fa-users"></i> <?php echo $reward['required_referrals']; ?> Empfehlungen
                                                </span>
                                                <span class="reward-badge">
                                                    <i class="fas fa-<?php echo $reward['reward_delivery_type'] === 'email' ? 'envelope' : 'link'; ?>"></i>
                                                    <?php echo $reward['reward_delivery_type'] === 'email' ? 'E-Mail' : ucfirst($reward['reward_delivery_type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="reward-actions">
                                            <button class="btn btn-secondary btn-sm" onclick="editReward(<?php echo $reward['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteReward(<?php echo $reward['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
    </div>
    
    <!-- Reward Modal -->
    <div id="rewardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-trophy"></i> <span id="modalTitle">Neue Belohnung</span>
                </h2>
                <button class="modal-close" onclick="closeRewardModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="rewardForm" onsubmit="saveReward(event)">
                <input type="hidden" id="rewardId" value="">
                <input type="hidden" id="freebieId" value="">
                
                <div class="form-group">
                    <label class="form-label">Titel der Belohnung *</label>
                    <input type="text" id="rewardTitle" class="form-input" required placeholder="z.B. Bronze Level">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea id="rewardDescription" class="form-textarea" placeholder="Kurze Beschreibung der Belohnung"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Benötigte Empfehlungen *</label>
                    <input type="number" id="requiredReferrals" class="form-input" min="0" required placeholder="z.B. 3">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon (Emoji)</label>
                    <input type="text" id="rewardIcon" class="form-input" placeholder="🎁" maxlength="2">
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
                        <option value="manual">Manuell</option>
                    </select>
                </div>
                
                <div id="emailFields">
                    <div class="form-group">
                        <label class="form-label">E-Mail Betreff</label>
                        <input type="text" id="emailSubject" class="form-input" placeholder="🎉 Glückwunsch! Du hast eine Belohnung freigeschaltet">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">E-Mail Text</label>
                        <textarea id="emailBody" class="form-textarea" placeholder="Hallo {{name}},

herzlichen Glückwunsch! Du hast das {{reward_title}} Level erreicht!

Deine Belohnung:
{{reward_description}}

Vielen Dank für deine Empfehlungen!"></textarea>
                    </div>
                </div>
                
                <div id="downloadFields" style="display: none;">
                    <div class="form-group">
                        <label class="form-label">Download-URL</label>
                        <input type="url" id="downloadUrl" class="form-input" placeholder="https://...">
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
        function copyReferralLink(freebieId) {
            const input = document.getElementById('referralLink_' + freebieId);
            input.select();
            document.execCommand('copy');
            
            showNotification('✅ Link kopiert!', 'success');
        }
        
        function openRewardModal(freebieId, rewardData = null) {
            document.getElementById('rewardModal').classList.add('active');
            document.body.style.overflow = 'hidden';
            
            document.getElementById('freebieId').value = freebieId;
            
            if (rewardData) {
                document.getElementById('modalTitle').textContent = 'Belohnung bearbeiten';
                document.getElementById('rewardId').value = rewardData.id;
                document.getElementById('rewardTitle').value = rewardData.reward_title;
                document.getElementById('rewardDescription').value = rewardData.reward_description || '';
                document.getElementById('requiredReferrals').value = rewardData.required_referrals;
                document.getElementById('rewardIcon').value = rewardData.reward_icon || '';
                document.getElementById('rewardColor').value = rewardData.reward_color || '#667eea';
                document.getElementById('deliveryType').value = rewardData.reward_delivery_type;
                document.getElementById('emailSubject').value = rewardData.email_subject || '';
                document.getElementById('emailBody').value = rewardData.email_body || '';
                document.getElementById('downloadUrl').value = rewardData.reward_download_url || '';
                
                updateColorPreview();
                toggleDeliveryFields();
            } else {
                document.getElementById('modalTitle').textContent = 'Neue Belohnung';
                document.getElementById('rewardForm').reset();
                document.getElementById('rewardId').value = '';
                document.getElementById('freebieId').value = freebieId;
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
                freebie_id: parseInt(document.getElementById('freebieId').value),
                reward_title: document.getElementById('rewardTitle').value,
                reward_description: document.getElementById('rewardDescription').value,
                required_referrals: parseInt(document.getElementById('requiredReferrals').value),
                reward_icon: document.getElementById('rewardIcon').value,
                reward_color: document.getElementById('rewardColor').value,
                reward_delivery_type: document.getElementById('deliveryType').value,
                email_subject: document.getElementById('emailSubject').value,
                email_body: document.getElementById('emailBody').value,
                reward_download_url: document.getElementById('downloadUrl').value
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
                    openRewardModal(data.reward.freebie_id, data.reward);
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
                font-weight: 600;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Close modal on outside click
        document.getElementById('rewardModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRewardModal();
            }
        });
        
        // ESC key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRewardModal();
            }
        });
    </script>
</body>
</html>