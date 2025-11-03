<?php
/**
 * Lead Dashboard - Empfehlungsprogramm
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
    SELECT * FROM referral_leads 
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
    SELECT name, email, status, registered_at 
    FROM referral_leads 
    WHERE referrer_code = ?
    ORDER BY registered_at DESC
");
$stmt->execute([$lead['referral_code']]);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Eingelöste Belohnungen laden
$stmt = $db->prepare("
    SELECT reward_id, claimed_at 
    FROM referral_claimed_rewards 
    WHERE lead_id = ?
    ORDER BY claimed_at DESC
");
$stmt->execute([$lead['id']]);
$claimed_rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Belohnungsstufen definieren
$reward_tiers = [
    ['referrals' => 3, 'reward' => 'E-Book: Social Media Hacks', 'icon' => '📚'],
    ['referrals' => 5, 'reward' => '1:1 Beratungsgespräch (30 Min)', 'icon' => '💬'],
    ['referrals' => 10, 'reward' => 'Kostenloser Kurs-Zugang', 'icon' => '🎓'],
    ['referrals' => 20, 'reward' => 'VIP Mitgliedschaft (3 Monate)', 'icon' => '👑'],
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Dashboard - Empfehlungsprogramm</title>
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
        }
        .reward-tier .info {
            flex: 1;
        }
        .reward-tier .title {
            font-weight: 600;
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }
        .reward-tier .requirement {
            color: #666;
            font-size: 14px;
        }
        .reward-tier .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
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
        <h2>🔗 Dein Empfehlungs-Link</h2>
        <p style="color: #666; margin-bottom: 15px;">
            Teile diesen Link mit deinen Freunden und Bekannten. 
            Für jede erfolgreiche Anmeldung erhältst du Belohnungen!
        </p>
        <div class="link-input-group">
            <input type="text" class="link-input" id="referral-link" 
                   value="https://app.mehr-infos-jetzt.de/lead_login.php?ref=<?php echo $lead['referral_code']; ?>" 
                   readonly>
            <button class="copy-btn" onclick="copyLink()">📋 Link kopieren</button>
        </div>
        <p style="color: #999; font-size: 13px;">
            Dein Referral-Code: <strong><?php echo $lead['referral_code']; ?></strong>
        </p>
    </div>
    
    <div class="rewards-section">
        <h2>🎁 Belohnungs-Stufen</h2>
        <?php foreach ($reward_tiers as $tier): 
            $is_claimed = in_array($tier['referrals'], array_column($claimed_rewards, 'reward_id'));
            $is_unlocked = $lead['successful_referrals'] >= $tier['referrals'];
            $status = $is_claimed ? 'claimed' : ($is_unlocked ? 'unlocked' : 'locked');
        ?>
            <div class="reward-tier <?php echo $status; ?>">
                <div class="icon"><?php echo $tier['icon']; ?></div>
                <div class="info">
                    <div class="title"><?php echo $tier['reward']; ?></div>
                    <div class="requirement">
                        <?php echo $tier['referrals']; ?> erfolgreiche Empfehlungen benötigt
                        (<?php echo $lead['successful_referrals']; ?>/<?php echo $tier['referrals']; ?>)
                    </div>
                </div>
                <div class="status <?php echo $status; ?>">
                    <?php 
                    if ($is_claimed) {
                        echo '✅ Eingelöst';
                    } elseif ($is_unlocked) {
                        echo '🎉 Freigeschaltet!';
                    } else {
                        $remaining = $tier['referrals'] - $lead['successful_referrals'];
                        echo "🔒 Noch {$remaining}";
                    }
                    ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="referrals-list">
        <h2>👥 Deine Empfehlungen</h2>
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
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Status</th>
                        <th>Registriert am</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($referral['name']); ?></td>
                            <td><?php echo htmlspecialchars($referral['email']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $referral['status']; ?>">
                                    <?php echo ucfirst($referral['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d.m.Y', strtotime($referral['registered_at'])); ?></td>
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
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✅ Kopiert!';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '#667eea';
            }, 2000);
        }
    </script>
</body>
</html>
