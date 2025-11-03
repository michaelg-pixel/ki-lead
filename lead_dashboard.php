<?php
/**
 * Lead Dashboard - Empfehlungsprogramm
 * Verbesserte Version mit Datenbankintegration
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

// Fallback: Wenn keine Belohnungen konfiguriert sind, Standard-Belohnungen anzeigen
if (empty($reward_tiers)) {
    $reward_tiers = [
        [
            'id' => 1,
            'tier_level' => 1,
            'tier_name' => 'Bronze',
            'required_referrals' => 3,
            'reward_title' => 'E-Book: Social Media Hacks',
            'reward_icon' => 'fa-book',
            'reward_type' => 'ebook',
            'reward_description' => 'Erhalte unser exklusives E-Book',
            'reward_value' => 'Wert: 19€',
            'reward_color' => '#cd7f32'
        ],
        [
            'id' => 2,
            'tier_level' => 2,
            'tier_name' => 'Silber',
            'required_referrals' => 5,
            'reward_title' => '1:1 Beratungsgespräch (30 Min)',
            'reward_icon' => 'fa-comments',
            'reward_type' => 'consultation',
            'reward_description' => 'Persönliche Beratung mit unserem Experten',
            'reward_value' => 'Wert: 150€',
            'reward_color' => '#c0c0c0'
        ],
        [
            'id' => 3,
            'tier_level' => 3,
            'tier_name' => 'Gold',
            'required_referrals' => 10,
            'reward_title' => 'Kostenloser Kurs-Zugang',
            'reward_icon' => 'fa-graduation-cap',
            'reward_type' => 'course',
            'reward_description' => 'Voller Zugang zu unserem Premium-Kurs',
            'reward_value' => 'Wert: 497€',
            'reward_color' => '#ffd700'
        ],
        [
            'id' => 4,
            'tier_level' => 4,
            'tier_name' => 'Platin',
            'required_referrals' => 20,
            'reward_title' => 'VIP Mitgliedschaft (3 Monate)',
            'reward_icon' => 'fa-crown',
            'reward_type' => 'vip',
            'reward_description' => 'Exklusiver VIP-Status mit allen Vorteilen',
            'reward_value' => 'Wert: 997€',
            'reward_color' => '#e5e4e2'
        ]
    ];
}

// Empfehlungslink (einfache Version - user_id wird automatisch vom ref_code ermittelt)
$referral_link = 'https://app.mehr-infos-jetzt.de/lead_login.php?ref=' . $lead['referral_code'];
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
        .referral-link-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
        
        @media (max-width: 768px) {
            .reward-tier {
                flex-direction: column;
                text-align: center;
            }
            .link-input-group {
                flex-direction: column;
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
    
    <div class="referral-link-section">
        <h2><i class="fas fa-link"></i> Dein Empfehlungs-Link</h2>
        <p style="color: #666; margin-bottom: 15px;">
            Teile diesen Link mit deinen Freunden und Bekannten. 
            Für jede erfolgreiche Anmeldung erhältst du Belohnungen!
        </p>
        <div class="link-input-group">
            <input type="text" class="link-input" id="referral-link" 
                   value="<?php echo htmlspecialchars($referral_link); ?>" 
                   readonly>
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
            <div class="empty-state">
                <div class="icon">🎁</div>
                <p>Noch keine Belohnungen konfiguriert</p>
            </div>
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
                
                // Icon bestimmen
                $icon_class = 'fa-gift';
                if (isset($tier['reward_icon']) && strpos($tier['reward_icon'], 'fa-') === 0) {
                    $icon_class = $tier['reward_icon'];
                }
                
                // Farbe für Badge
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
        function copyLink() {
            const input = document.getElementById('referral-link');
            input.select();
            input.setSelectionRange(0, 99999); // Für Mobile
            
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
