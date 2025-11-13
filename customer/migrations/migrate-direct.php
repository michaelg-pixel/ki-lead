<?php
/**
 * Direkte Migration ohne API
 * Führt die Empfehlungsprogramm-Migrationen direkt aus
 */
session_start();

// Auth-Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'customer'])) {
    die('Nicht authentifiziert. Bitte einloggen.');
}

require_once __DIR__ . '/../../config/database.php';
$pdo = getDBConnection();

$migrationRun = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    $migrationRun = true;
    
    // Migration 1: API Settings Tabelle
    try {
        $sql1 = "
            CREATE TABLE IF NOT EXISTS customer_email_api_settings (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT NOT NULL,
                
                -- API Provider
                provider VARCHAR(50) NOT NULL COMMENT 'quentn, klicktipp, getresponse, brevo, activecampaign, mailchimp',
                
                -- API Credentials
                api_key VARCHAR(500) NOT NULL,
                api_secret VARCHAR(500) DEFAULT NULL,
                
                -- Listen/Tag Konfiguration
                start_tag VARCHAR(200) DEFAULT NULL,
                list_id VARCHAR(200) DEFAULT NULL,
                campaign_id VARCHAR(200) DEFAULT NULL,
                
                -- Double Opt-in
                double_optin_enabled BOOLEAN DEFAULT TRUE,
                double_optin_form_id VARCHAR(200) DEFAULT NULL,
                
                -- Webhook
                webhook_url VARCHAR(500) DEFAULT NULL,
                webhook_secret VARCHAR(255) DEFAULT NULL,
                
                -- Status
                is_active BOOLEAN DEFAULT TRUE,
                is_verified BOOLEAN DEFAULT FALSE,
                last_verified_at DATETIME DEFAULT NULL,
                verification_error TEXT DEFAULT NULL,
                
                -- Zusätzliche Einstellungen
                custom_settings JSON DEFAULT NULL,
                
                -- Timestamps
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                -- Foreign Keys & Indexes
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_customer_provider (customer_id, provider),
                INDEX idx_customer_active (customer_id, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql1);
        $results[] = ['step' => 1, 'name' => 'Email API Settings Tabelle', 'status' => 'success', 'message' => 'Erfolgreich erstellt'];
    } catch (PDOException $e) {
        $results[] = ['step' => 1, 'name' => 'Email API Settings Tabelle', 'status' => 'error', 'message' => $e->getMessage()];
    }
    
    // Migration 2: Reward Definitions erweitern
    $alterStatements = [
        "ALTER TABLE reward_definitions ADD COLUMN email_subject VARCHAR(200) DEFAULT NULL",
        "ALTER TABLE reward_definitions ADD COLUMN email_body TEXT DEFAULT NULL",
        "ALTER TABLE reward_definitions ADD COLUMN email_template_id INT DEFAULT NULL",
        "ALTER TABLE reward_definitions ADD COLUMN auto_send_email BOOLEAN DEFAULT FALSE",
        "ALTER TABLE reward_definitions ADD COLUMN send_via_api BOOLEAN DEFAULT TRUE",
        "ALTER TABLE reward_definitions ADD COLUMN attachment_urls JSON DEFAULT NULL",
        "ALTER TABLE reward_definitions ADD COLUMN notification_webhook VARCHAR(500) DEFAULT NULL"
    ];
    
    $successCount = 0;
    $skipCount = 0;
    $errorCount = 0;
    
    foreach ($alterStatements as $sql) {
        try {
            $pdo->exec($sql);
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $skipCount++;
            } else {
                $errorCount++;
            }
        }
    }
    
    if ($errorCount === 0) {
        $results[] = [
            'step' => 2,
            'name' => 'Reward Definitions erweitert',
            'status' => 'success',
            'message' => "$successCount Spalten hinzugefügt, $skipCount übersprungen"
        ];
    } else {
        $results[] = [
            'step' => 2,
            'name' => 'Reward Definitions erweitert',
            'status' => 'warning',
            'message' => "$successCount erfolgreich, $skipCount übersprungen, $errorCount Fehler"
        ];
    }
    
    // Migration 3: Email Tracking Tabellen
    try {
        $sql3 = "
            CREATE TABLE IF NOT EXISTS lead_reward_emails (
                id INT PRIMARY KEY AUTO_INCREMENT,
                lead_id INT NOT NULL,
                customer_id INT NOT NULL,
                reward_id INT NOT NULL,
                
                email_to VARCHAR(255) NOT NULL,
                email_subject VARCHAR(200) NOT NULL,
                email_body TEXT NOT NULL,
                
                send_status ENUM('pending', 'sent', 'failed', 'bounced') DEFAULT 'pending',
                send_method ENUM('api', 'smtp', 'manual') DEFAULT 'api',
                api_provider VARCHAR(50) DEFAULT NULL,
                api_message_id VARCHAR(255) DEFAULT NULL,
                
                sent_at DATETIME DEFAULT NULL,
                opened_at DATETIME DEFAULT NULL,
                clicked_at DATETIME DEFAULT NULL,
                open_count INT DEFAULT 0,
                click_count INT DEFAULT 0,
                
                error_message TEXT DEFAULT NULL,
                retry_count INT DEFAULT 0,
                max_retries INT DEFAULT 3,
                
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                FOREIGN KEY (lead_id) REFERENCES lead_users(id) ON DELETE CASCADE,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reward_id) REFERENCES reward_definitions(id) ON DELETE CASCADE,
                INDEX idx_lead_reward (lead_id, reward_id),
                INDEX idx_customer_status (customer_id, send_status),
                INDEX idx_sent_at (sent_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS email_api_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT NOT NULL,
                provider VARCHAR(50) NOT NULL,
                
                endpoint VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                request_payload JSON DEFAULT NULL,
                
                response_code INT DEFAULT NULL,
                response_body JSON DEFAULT NULL,
                success BOOLEAN DEFAULT FALSE,
                error_message TEXT DEFAULT NULL,
                
                duration_ms INT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_customer_provider (customer_id, provider),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $pdo->exec($sql3);
        $results[] = ['step' => 3, 'name' => 'Email Tracking Tabellen', 'status' => 'success', 'message' => 'Erfolgreich erstellt'];
    } catch (PDOException $e) {
        $results[] = ['step' => 3, 'name' => 'Email Tracking Tabellen', 'status' => 'error', 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direkte Migration - Empfehlungsprogramm API</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-box strong {
            color: #1e40af;
        }
        .result {
            background: #f9fafb;
            border-left: 4px solid #10b981;
            padding: 16px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .result.error {
            border-color: #ef4444;
        }
        .result.warning {
            border-color: #f59e0b;
        }
        .result-header {
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2937;
        }
        .result-message {
            color: #4b5563;
            font-size: 14px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Direkte Migration</h1>
        <p class="subtitle">Empfehlungsprogramm API-Einstellungen und Email-System</p>
        
        <div class="info-box">
            <strong>Session Info:</strong><br>
            User ID: <?php echo $_SESSION['user_id']; ?><br>
            Role: <?php echo $_SESSION['role']; ?><br>
            Name: <?php echo $_SESSION['name'] ?? 'N/A'; ?>
        </div>
        
        <?php if (!$migrationRun): ?>
            <p style="margin: 20px 0;">Diese Migration erstellt:</p>
            <ol style="margin-left: 20px; line-height: 1.8;">
                <li><strong>customer_email_api_settings</strong> - Tabelle für Email-Provider API-Einstellungen</li>
                <li><strong>Erweiterte reward_definitions</strong> - Neue Spalten für Email-Versand</li>
                <li><strong>lead_reward_emails</strong> - Tracking für versendete Belohnungs-Emails</li>
                <li><strong>email_api_logs</strong> - Logs für Email-API Aufrufe</li>
            </ol>
            
            <form method="post" style="margin-top: 30px;">
                <button type="submit" name="run_migration" class="btn">
                    ✨ Migration jetzt ausführen
                </button>
            </form>
        <?php else: ?>
            <h2 style="margin: 30px 0 20px; color: #1f2937;">Migrations-Ergebnisse:</h2>
            
            <?php foreach ($results as $result): ?>
                <div class="result <?php echo $result['status']; ?>">
                    <div class="result-header">
                        <?php 
                        $icon = $result['status'] === 'success' ? '✅' : ($result['status'] === 'error' ? '❌' : '⚠️');
                        echo "$icon Schritt {$result['step']}: {$result['name']}";
                        ?>
                    </div>
                    <div class="result-message"><?php echo htmlspecialchars($result['message']); ?></div>
                </div>
            <?php endforeach; ?>
            
            <?php 
            $hasError = array_filter($results, fn($r) => $r['status'] === 'error');
            if (empty($hasError)):
            ?>
                <div class="success-box">
                    <h3 style="color: #065f46; margin-bottom: 10px;">✅ Migration erfolgreich!</h3>
                    <p style="color: #047857; margin-bottom: 10px;">Alle Schritte wurden erfolgreich durchgeführt.</p>
                    <p style="color: #047857;"><strong>Nächste Schritte:</strong></p>
                    <ol style="margin-left: 20px; margin-top: 10px; color: #047857;">
                        <li>Gehe zum Empfehlungsprogramm im Dashboard</li>
                        <li>Aktiviere das Empfehlungsprogramm</li>
                        <li>Konfiguriere deine Email-API (Brevo, Quentn, etc.)</li>
                        <li>Erstelle Belohnungsstufen mit automatischem Email-Versand</li>
                    </ol>
                </div>
                
                <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn" style="display: inline-block; text-decoration: none; margin-top: 20px;">
                    Zum Empfehlungsprogramm →
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>