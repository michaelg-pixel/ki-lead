<?php
/**
 * Customer Sections - Empfehlungsprogramm Extended
 * Erweiterte Version mit Referral-Integration
 */

require_once __DIR__ . '/config/database.php';

session_start();

// Login Check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['customer_id'])) {
    header('Location: /customer/login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? $_SESSION['customer_id'];
$db = getDBConnection();

// Prüfen ob User als Lead registriert ist
$stmt = $db->prepare("
    SELECT rl.*, 
           (SELECT COUNT(*) FROM referral_leads WHERE referrer_code = rl.referral_code) as referral_count
    FROM referral_leads rl 
    WHERE rl.email = (SELECT email FROM users WHERE id = ?)
");
$stmt->execute([$user_id]);
$lead_data = $stmt->fetch(PDO::FETCH_ASSOC);

// Wenn nicht als Lead registriert, erstellen
if (!$lead_data) {
    $stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $referral_code = substr(md5($user['email'] . time()), 0, 10);
        $api_token = bin2hex(random_bytes(32));
        
        $stmt = $db->prepare("
            INSERT INTO referral_leads 
            (name, email, referral_code, api_token, user_id, registered_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user['name'],
            $user['email'],
            $referral_code,
            $api_token,
            $user_id
        ]);
        
        // Daten neu laden
        $stmt = $db->prepare("
            SELECT rl.*, 
                   (SELECT COUNT(*) FROM referral_leads WHERE referrer_code = rl.referral_code) as referral_count
            FROM referral_leads rl 
            WHERE rl.email = ?
        ");
        $stmt->execute([$user['email']]);
        $lead_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$referral_link = 'https://app.mehr-infos-jetzt.de/lead_login.php?ref=' . ($lead_data['referral_code'] ?? '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empfehlungsprogramm</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .referral-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }
        .referral-section h2 {
            font-size: 32px;
            margin-bottom: 15px;
        }
        .referral-section p {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 25px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .stat-box .value {
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-box .label {
            font-size: 14px;
            opacity: 0.9;
        }
        .link-box {
            background: white;
            color: #333;
            padding: 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 15px;
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
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .benefit-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .benefit-card .icon {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .benefit-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }
        .benefit-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255,255,255,0.3);
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
    <div class="referral-section">
        <h2>🎁 Empfehlungsprogramm</h2>
        <p>Empfehle uns weiter und erhalte tolle Belohnungen!</p>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="value"><?php echo $lead_data['total_referrals'] ?? 0; ?></div>
                <div class="label">Gesamt Empfehlungen</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo $lead_data['successful_referrals'] ?? 0; ?></div>
                <div class="label">Erfolgreiche</div>
            </div>
            <div class="stat-box">
                <div class="value"><?php echo $lead_data['rewards_earned'] ?? 0; ?></div>
                <div class="label">Belohnungen</div>
            </div>
        </div>
        
        <div class="link-box">
            <input type="text" class="link-input" id="referral-link" 
                   value="<?php echo $referral_link; ?>" readonly>
            <button class="copy-btn" onclick="copyLink()">📋 Link kopieren</button>
        </div>
        
        <a href="lead_dashboard.php" class="cta-button">
            → Zu meinem Empfehlungs-Dashboard
        </a>
    </div>
    
    <div class="benefits-grid">
        <div class="benefit-card">
            <div class="icon">📚</div>
            <h3>3 Empfehlungen</h3>
            <p>E-Book: Social Media Marketing Hacks</p>
        </div>
        <div class="benefit-card">
            <div class="icon">💬</div>
            <h3>5 Empfehlungen</h3>
            <p>1:1 Beratungsgespräch (30 Min)</p>
        </div>
        <div class="benefit-card">
            <div class="icon">🎓</div>
            <h3>10 Empfehlungen</h3>
            <p>Kostenloser Kurs-Zugang</p>
        </div>
        <div class="benefit-card">
            <div class="icon">👑</div>
            <h3>20 Empfehlungen</h3>
            <p>VIP Mitgliedschaft (3 Monate)</p>
        </div>
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
