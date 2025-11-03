<?php
/**
 * Lead Dashboard - Empfehlungsprogramm
 * Leads können Freebies auswählen und ihre Empfehlungslinks teilen
 */

require_once __DIR__ . '/config/database.php';

session_start();

// Login Check
if (!isset($_SESSION['lead_id'])) {
    header('Location: lead_login.php');
    exit;
}

$db = getDBConnection();

// Lead Daten laden
$stmt = $db->prepare("
    SELECT * FROM lead_users 
    WHERE id = ?
");
$stmt->execute([$_SESSION['lead_id']]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    session_destroy();
    header('Location: lead_login.php');
    exit;
}

// Empfohlene Leads laden
$stmt = $db->prepare("
    SELECT referred_name as name, referred_email as email, status, invited_at as registered_at 
    FROM lead_referrals 
    WHERE referrer_id = ?
    ORDER BY invited_at DESC
");
$stmt->execute([$lead['id']]);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Eingelöste Belohnungen laden
$stmt = $db->prepare("
    SELECT reward_id, reward_name, claimed_at 
    FROM referral_claimed_rewards 
    WHERE lead_id = ?
    ORDER BY claimed_at DESC
");
$stmt->execute([$lead['id']]);
$claimed_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Belohnungen aus Datenbank laden (basierend auf user_id des Leads)
$reward_tiers = [];
if ($lead['user_id']) {
    $stmt = $db->prepare("
        SELECT 
            id,
            tier_level,
            tier_name,
            tier_description,
            required_referrals,
            reward_type,
            reward_title,
            reward_description,
            reward_icon,
            reward_color,
            reward_value
        FROM reward_definitions 
        WHERE user_id = ? AND is_active = 1
        ORDER BY tier_level ASC
    ");
    $stmt->execute([$lead['user_id']]);
    $reward_tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// KORREKTUR: Freebies aus customer_freebies laden mit korrekten Spaltennamen
$freebies = [];
if ($lead['user_id']) {
    // Erst Tabellenstruktur prüfen
    try {
        $stmt = $db->query("DESCRIBE customer_freebies");
        $cfColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Ermittle welche Spalten verfügbar sind
        $nameColumn = in_array('name', $cfColumns) ? 'cf.name' : 
                     (in_array('title', $cfColumns) ? 'cf.title' : 
                     (in_array('freebie_name', $cfColumns) ? 'cf.freebie_name' : 'f.name'));
        
        $descColumn = in_array('description', $cfColumns) ? 'cf.description' : 'f.description';
        $mockupColumn = in_array('mockup_image_url', $cfColumns) ? 
                       'COALESCE(cf.mockup_image_url, f.mockup_image_url)' : 
                       'f.mockup_image_url';
        
        $stmt = $db->prepare("
            SELECT 
                cf.id,
                cf.unique_id,
                {$nameColumn} as title,
                {$descColumn} as description,
                {$mockupColumn} as image_path,
                cf.customer_id
            FROM customer_freebies cf
            LEFT JOIN freebies f ON cf.template_id = f.id
            WHERE cf.customer_id = ?
            ORDER BY cf.id DESC
        ");
        $stmt->execute([$lead['user_id']]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error loading freebies: " . $e->getMessage());
        // Fallback: Versuche direkt aus freebies Tabelle
        try {
            $stmt = $db->prepare("
                SELECT 
                    f.id,
                    f.unique_id,
                    f.name as title,
                    f.description,
                    f.mockup_image_url as image_path,
                    f.user_id as customer_id
                FROM freebies f
                WHERE f.user_id = ?
                ORDER BY f.id DESC
            ");
            $stmt->execute([$lead['user_id']]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {
            error_log("Error loading freebies fallback: " . $e2->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Dashboard - Empfehlungsprogramm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .header h1 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        .logout-btn {
            float: right;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: white;
            color: #667eea;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        .stat-card .label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .freebie-selection-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .freebie-selection-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .freebie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .freebie-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .freebie-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        .freebie-card.selected {
            border-color: #28a745;
            background: #d4edda;
        }
        .freebie-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .freebie-card h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .freebie-card p {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        .referral-link-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: none;
        }
        .referral-link-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 24px;
        }
        .link-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .link-input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            background: #f8f9fa;
        }
        .copy-btn {
            padding: 15px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .rewards-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .rewards-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .reward-tier {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 4px solid #e0e0e0;
            transition: all 0.3s;
        }
        .reward-tier.unlocked {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .reward-tier.claimed {
            border-left-color: #667eea;
            background: #e7f3ff;
        }
        .reward-tier .icon {
            font-size: 40px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: white;
        }
        .reward-tier .info {
            flex: 1;
        }
        .reward-tier .tier-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .reward-tier .title {
            font-weight: 600;
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        .reward-tier .description {
            color: #666;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .reward-tier .requirement {
            color: #666;
            font-size: 14px;
        }
        .reward-tier .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
        }
        .reward-tier .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s;
        }
        .reward-tier .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }
        .status.locked {
            background: #f0f0f0;
            color: #666;
        }
        .status.unlocked {
            background: #28a745;
            color: white;
        }
        .status.claimed {
            background: #667eea;
            color: white;
        }
        .referrals-list {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .referrals-list h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }
        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-badge.converted {
            background: #cfe2ff;
            color: #084298;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-state .icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .alert-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .alert-box .icon {
            font-size: 32px;
        }
        .alert-box .content {
            flex: 1;
        }
        .alert-box h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .alert-box p {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .reward-tier {
                flex-direction: column;
                text-align: center;
            }
            .link-input-group {
                flex-direction: column;
            }
            .freebie-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="lead_logout.php" class="logout-btn">Logout</a>
        <h1>👋 Willkommen, <?php echo htmlspecialchars($lead['name']); ?>!</h1>
        <p>Dein Empfehlungs-Dashboard</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">🎯</div>
            <div class="label">Gesamt Empfehlungen</div>
            <div class="value"><?php echo $lead['total_referrals']; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">✅</div>
            <div class="label">Erfolgreiche Empfehlungen</div>
            <div class="value"><?php echo $lead['successful_referrals']; ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">🎁</div>
            <div class="label">Eingelöste Belohnungen</div>
            <div class="value"><?php echo count($claimed_rewards); ?></div>
        </div>
    </div>
    
    <!-- Freebie Auswahl -->
    <?php if (!empty($freebies)): ?>
    <div class="freebie-selection-section">
        <h2><i class="fas fa-gift"></i> Wähle ein Freebie zum Teilen</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Wähle ein Freebie aus, das du mit deinem Empfehlungslink teilen möchtest.
        </p>
        <div class="freebie-grid">
            <?php foreach ($freebies as $freebie): ?>
            <div class="freebie-card" onclick="selectFreebie('<?php echo htmlspecialchars($freebie['unique_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($freebie['title'], ENT_QUOTES); ?>')">
                <?php if (!empty($freebie['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($freebie['image_path']); ?>" alt="<?php echo htmlspecialchars($freebie['title']); ?>">
                <?php endif; ?>
                <h4><?php echo htmlspecialchars($freebie['title']); ?></h4>
                <?php if (!empty($freebie['description'])): ?>
                <p><?php echo htmlspecialchars(substr($freebie['description'], 0, 100)) . (strlen($freebie['description']) > 100 ? '...' : ''); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php elseif ($lead['user_id']): ?>
    <div class="freebie-selection-section">
        <div class="alert-box">
            <div class="icon">⚠️</div>
            <div class="content">
                <h3>Noch keine Freebies verfügbar</h3>
                <p>Dein Partner hat noch keine Freebies erstellt. Bitte wende dich an deinen Ansprechpartner.</p>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="freebie-selection-section">
        <div class="alert-box">
            <div class="icon">🔗</div>
            <div class="content">
                <h3>Account-Verknüpfung fehlt</h3>
                <p>Dein Lead-Account ist noch nicht mit einem Partner verknüpft. Bitte kontaktiere den Support.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Empfehlungslink (wird nach Auswahl angezeigt) -->
    <div class="referral-link-section" id="referralLinkSection">
        <h2><i class="fas fa-link"></i> Dein Empfehlungs-Link</h2>
        <p style="color: #666; margin-bottom: 15px;">
            Teile diesen Link für: <strong id="selectedFreebieTitle" style="color: #667eea;"></strong>
        </p>
        <div class="link-input-group">
            <input type="text" class="link-input" id="referral-link" readonly>
            <button class="copy-btn" onclick="copyLink()">
                <i class="fas fa-copy"></i> Link kopieren
            </button>
        </div>
        <p style="color: #999; font-size: 13px; margin-top: 10px;">
            <i class="fas fa-tag"></i> Dein Referral-Code: <strong><?php echo $lead['referral_code']; ?></strong>
        </p>
    </div>
    
    <div class="rewards-section">
        <h2><i class="fas fa-gift"></i> Belohnungs-Stufen</h2>
        <?php if (empty($reward_tiers)): ?>
            <?php if ($lead['user_id']): ?>
            <div class="alert-box">
                <div class="icon">🎁</div>
                <div class="content">
                    <h3>Noch keine Belohnungen konfiguriert</h3>
                    <p>Dein Partner hat noch keine Belohnungen eingerichtet. Sobald diese verfügbar sind, werden sie hier angezeigt.</p>
                </div>
            </div>
            <?php else: ?>
            <div class="alert-box">
                <div class="icon">🔗</div>
                <div class="content">
                    <h3>Account-Verknüpfung fehlt</h3>
                    <p>Um Belohnungen zu sehen, muss dein Account mit einem Partner verknüpft sein.</p>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <?php foreach ($reward_tiers as $tier): 
                $tier_id = $tier['id'];
                $is_claimed = false;
                foreach ($claimed_rewards as $claimed) {
                    if ($claimed['reward_id'] == $tier_id) {
                        $is_claimed = true;
                        break;
                    }
                }
                $is_unlocked = $lead['successful_referrals'] >= $tier['required_referrals'];
                $status = $is_claimed ? 'claimed' : ($is_unlocked ? 'unlocked' : 'locked');
                $progress_percent = min(100, ($lead['successful_referrals'] / $tier['required_referrals']) * 100);
                
                $icon_class = 'fa-gift';
                if (isset($tier['reward_icon']) && strpos($tier['reward_icon'], 'fa-') === 0) {
                    $icon_class = $tier['reward_icon'];
                }
                
                $badge_color = $tier['reward_color'] ?? '#667eea';
            ?>
                <div class="reward-tier <?php echo $status; ?>">
                    <div class="icon" style="color: <?php echo $badge_color; ?>">
                        <i class="fas <?php echo $icon_class; ?>"></i>
                    </div>
                    <div class="info">
                        <div class="tier-badge" style="background: <?php echo $badge_color; ?>; color: white;">
                            <?php echo htmlspecialchars($tier['tier_name'] ?? 'Stufe ' . $tier['tier_level']); ?>
                        </div>
                        <div class="title"><?php echo htmlspecialchars($tier['reward_title']); ?></div>
                        <?php if (!empty($tier['reward_description'])): ?>
                            <div class="description"><?php echo htmlspecialchars($tier['reward_description']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($tier['reward_value'])): ?>
                            <div class="description" style="font-weight: 600; color: <?php echo $badge_color; ?>;">
                                <?php echo htmlspecialchars($tier['reward_value']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="requirement">
                            <?php echo $tier['required_referrals']; ?> erfolgreiche Empfehlungen benötigt
                            (<?php echo $lead['successful_referrals']; ?>/<?php echo $tier['required_referrals']; ?>)
                        </div>
                        <?php if (!$is_claimed && !$is_unlocked): ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="status <?php echo $status; ?>">
                        <?php 
                        if ($is_claimed) {
                            echo '<i class="fas fa-check-circle"></i> Eingelöst';
                        } elseif ($is_unlocked) {
                            echo '<i class="fas fa-star"></i> Freigeschaltet!';
                        } else {
                            $remaining = $tier['required_referrals'] - $lead['successful_referrals'];
                            echo "<i class='fas fa-lock'></i> Noch {$remaining}";
                        }
                        ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="referrals-list">
        <h2><i class="fas fa-users"></i> Deine Empfehlungen</h2>
        <?php if (empty($referrals)): ?>
            <div class="empty-state">
                <div class="icon">📭</div>
                <p>Noch keine Empfehlungen vorhanden</p>
                <p style="font-size: 14px; margin-top: 10px;">
                    Teile deinen Link und starte mit dem Empfehlen!
                </p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-envelope"></i> E-Mail</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-calendar"></i> Registriert am</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($referral['name']); ?></td>
                            <td><?php echo htmlspecialchars($referral['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $referral['status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'pending' => 'Ausstehend',
                                        'active' => 'Aktiv',
                                        'converted' => 'Konvertiert',
                                        'cancelled' => 'Abgebrochen'
                                    ];
                                    echo $status_labels[$referral['status']] ?? ucfirst($referral['status']);
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($referral['registered_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <script>
        const leadReferralCode = '<?php echo $lead['referral_code']; ?>';
        const baseUrl = 'https://app.mehr-infos-jetzt.de';
        
        function selectFreebie(freebieUniqueId, freebieTitle) {
            // Alle Karten deselektieren
            document.querySelectorAll('.freebie-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Ausgewählte Karte markieren
            event.target.closest('.freebie-card').classList.add('selected');
            
            // Korrekter Freebie-Link mit Lead-Referral-Code
            const referralLink = `${baseUrl}/freebie/index.php?id=${freebieUniqueId}&ref=${leadReferralCode}`;
            document.getElementById('referral-link').value = referralLink;
            document.getElementById('selectedFreebieTitle').textContent = freebieTitle;
            document.getElementById('referralLinkSection').style.display = 'block';
            
            // Scroll zum Link
            document.getElementById('referralLinkSection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        function copyLink() {
            const input = document.getElementById('referral-link');
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                const btn = event.target.closest('button');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '#667eea';
                }, 2000);
            } catch (err) {
                alert('Bitte kopiere den Link manuell');
            }
        }
    </script>
</body>
</html>
