<?php
/**
 * 🚀 LEAD SYSTEM - DIREKTE DATENBANK-INSTALLATION
 * 
 * Erstellt alle Tabellen direkt im Browser
 * AUFRUF: https://app.mehr-infos-jetzt.de/create-lead-tables.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$results = [];
$errors = [];
$installation_done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tables'])) {
    try {
        $db = getDBConnection();
        
        // 1. Lead Users Tabelle
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS lead_users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    referral_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Eigener Empfehlungscode',
                    referrer_code VARCHAR(50) DEFAULT NULL COMMENT 'Code des Werbers',
                    api_token VARCHAR(64) UNIQUE DEFAULT NULL,
                    user_id INT DEFAULT NULL COMMENT 'Verknüpfung zu users Tabelle',
                    
                    total_referrals INT DEFAULT 0 COMMENT 'Gesamt eingeladene Personen',
                    successful_referrals INT DEFAULT 0 COMMENT 'Bestätigte Empfehlungen',
                    rewards_earned INT DEFAULT 0 COMMENT 'Anzahl Belohnungen',
                    
                    status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
                    email_verified BOOLEAN DEFAULT FALSE,
                    verification_token VARCHAR(64) DEFAULT NULL,
                    
                    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_login_at DATETIME DEFAULT NULL,
                    verified_at DATETIME DEFAULT NULL,
                    
                    INDEX idx_email (email),
                    INDEX idx_referral_code (referral_code),
                    INDEX idx_referrer_code (referrer_code),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['table' => 'lead_users', 'status' => 'success'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = ['table' => 'lead_users', 'status' => 'exists'];
            } else {
                throw $e;
            }
        }
        
        // 2. Lead Referrals Tabelle
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS lead_referrals (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    referrer_id INT NOT NULL COMMENT 'Lead der geworben hat',
                    referred_email VARCHAR(255) NOT NULL,
                    referred_name VARCHAR(255) DEFAULT NULL,
                    referred_user_id INT DEFAULT NULL COMMENT 'ID des geworbenen Leads',
                    
                    status ENUM('pending', 'active', 'converted', 'cancelled') DEFAULT 'pending',
                    conversion_type VARCHAR(50) DEFAULT NULL,
                    conversion_value DECIMAL(10,2) DEFAULT 0.00,
                    
                    ip_hash VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 Hash der IP',
                    user_agent TEXT DEFAULT NULL,
                    
                    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    converted_at DATETIME DEFAULT NULL,
                    
                    INDEX idx_referrer (referrer_id),
                    INDEX idx_email (referred_email),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['table' => 'lead_referrals', 'status' => 'success'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = ['table' => 'lead_referrals', 'status' => 'exists'];
            } else {
                throw $e;
            }
        }
        
        // 3. Referral Reward Tiers Tabelle
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS referral_reward_tiers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lead_id INT NOT NULL,
                    tier_id INT NOT NULL COMMENT 'Welche Stufe (1=3 Refs, 2=5 Refs, etc)',
                    tier_name VARCHAR(100) DEFAULT NULL,
                    rewards_earned INT DEFAULT 1,
                    current_referrals INT DEFAULT 0,
                    
                    unlocked BOOLEAN DEFAULT TRUE,
                    notified BOOLEAN DEFAULT FALSE,
                    
                    achieved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    notified_at DATETIME DEFAULT NULL,
                    
                    INDEX idx_lead (lead_id),
                    INDEX idx_tier (tier_id),
                    UNIQUE KEY unique_lead_tier (lead_id, tier_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['table' => 'referral_reward_tiers', 'status' => 'success'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = ['table' => 'referral_reward_tiers', 'status' => 'exists'];
            } else {
                throw $e;
            }
        }
        
        // 4. Referral Claimed Rewards Tabelle
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS referral_claimed_rewards (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lead_id INT NOT NULL,
                    reward_id INT NOT NULL,
                    reward_name VARCHAR(255) DEFAULT NULL,
                    reward_type VARCHAR(50) DEFAULT NULL,
                    
                    reward_value DECIMAL(10,2) DEFAULT 0.00,
                    notes TEXT DEFAULT NULL,
                    
                    delivered BOOLEAN DEFAULT FALSE,
                    delivery_method VARCHAR(50) DEFAULT NULL,
                    
                    claimed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    delivered_at DATETIME DEFAULT NULL,
                    
                    INDEX idx_lead (lead_id),
                    INDEX idx_reward (reward_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['table' => 'referral_claimed_rewards', 'status' => 'success'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = ['table' => 'referral_claimed_rewards', 'status' => 'exists'];
            } else {
                throw $e;
            }
        }
        
        // 5. Lead Activity Log Tabelle
        try {
            $db->exec("
                CREATE TABLE IF NOT EXISTS lead_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lead_id INT NOT NULL,
                    activity_type VARCHAR(50) NOT NULL,
                    activity_data JSON DEFAULT NULL,
                    ip_hash VARCHAR(64) DEFAULT NULL,
                    user_agent TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_lead (lead_id),
                    INDEX idx_type (activity_type),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $results[] = ['table' => 'lead_activity_log', 'status' => 'success'];
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $results[] = ['table' => 'lead_activity_log', 'status' => 'exists'];
            } else {
                throw $e;
            }
        }
        
        $installation_done = true;
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Prüfen welche Tabellen existieren
$existing_tables = [];
try {
    $db = getDBConnection();
    $stmt = $db->query("SHOW TABLES LIKE 'lead_%'");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $errors[] = "Verbindungsfehler: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Lead System - Tabellen erstellen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        .info-box ul {
            margin: 10px 0 0 20px;
        }
        .info-box li {
            margin: 5px 0;
            color: #555;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        .table-item {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .table-item.success {
            background: #d4edda;
            color: #155724;
        }
        .table-item.exists {
            background: #fff3cd;
            color: #856404;
        }
        .table-item .icon {
            font-size: 24px;
        }
        .table-item .name {
            flex: 1;
            font-weight: 600;
        }
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 40px;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .next-steps {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .next-steps h4 {
            color: #333;
            margin-bottom: 15px;
        }
        .next-steps ol {
            margin-left: 20px;
        }
        .next-steps li {
            margin: 10px 0;
            color: #666;
            line-height: 1.6;
        }
        .link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Lead System</h1>
        <div class="subtitle">Datenbank-Tabellen erstellen</div>
        
        <?php if (!$installation_done && empty($existing_tables)): ?>
            
            <div class="info-box">
                <h3>📦 Diese Tabellen werden erstellt:</h3>
                <ul>
                    <li><strong>lead_users</strong> - Lead-Accounts mit Login</li>
                    <li><strong>lead_referrals</strong> - Empfehlungs-Tracking</li>
                    <li><strong>referral_reward_tiers</strong> - Belohnungsstufen</li>
                    <li><strong>referral_claimed_rewards</strong> - Eingelöste Belohnungen</li>
                    <li><strong>lead_activity_log</strong> - Aktivitäts-Log</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="create_tables" class="button">
                    ⚡ TABELLEN JETZT ERSTELLEN
                </button>
            </form>
            
        <?php elseif (!empty($existing_tables) && !$installation_done): ?>
            
            <div class="warning-box">
                <strong>⚠️ Achtung!</strong>
                <p style="margin-top: 10px;">Diese Tabellen existieren bereits:</p>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($existing_tables as $table): ?>
                        <li><?php echo htmlspecialchars($table); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="create_tables" class="button">
                    🔄 Fehlende Tabellen ergänzen
                </button>
            </form>
            
        <?php else: ?>
            
            <?php if (empty($errors)): ?>
                <div class="success-box">
                    <h3>✅ Installation erfolgreich!</h3>
                    <p>Alle Tabellen wurden erstellt.</p>
                    
                    <?php if (!empty($results)): ?>
                        <div style="margin-top: 20px;">
                            <?php foreach ($results as $result): ?>
                                <div class="table-item <?php echo $result['status']; ?>">
                                    <span class="icon">
                                        <?php echo $result['status'] === 'success' ? '✅' : '⚠️'; ?>
                                    </span>
                                    <span class="name"><?php echo htmlspecialchars($result['table']); ?></span>
                                    <span style="font-size: 13px; opacity: 0.8;">
                                        <?php echo $result['status'] === 'success' ? 'erstellt' : 'existiert bereits'; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="next-steps">
                    <h4>🎯 Nächste Schritte:</h4>
                    <ol>
                        <li><strong>Lösche diese Datei:</strong> create-lead-tables.php</li>
                        <li><strong>Teste die Registrierung:</strong> 
                            <a href="lead_login.php" class="link" target="_blank">lead_login.php</a>
                        </li>
                        <li><strong>Prüfe das Dashboard:</strong>
                            <a href="lead_dashboard.php" class="link" target="_blank">lead_dashboard.php</a>
                        </li>
                    </ol>
                </div>
                
            <?php else: ?>
                <div class="error-box">
                    <h3>❌ Fehler aufgetreten</h3>
                    <?php foreach ($errors as $error): ?>
                        <p style="margin: 10px 0;"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                
                <form method="POST">
                    <button type="submit" name="create_tables" class="button">
                        🔄 Erneut versuchen
                    </button>
                </form>
            <?php endif; ?>
            
        <?php endif; ?>
        
        <div class="warning-box" style="margin-top: 20px;">
            <strong>🔒 Sicherheitshinweis:</strong>
            <p style="margin-top: 5px;">Lösche diese Datei nach erfolgreicher Installation!</p>
        </div>
    </div>
</body>
</html>
