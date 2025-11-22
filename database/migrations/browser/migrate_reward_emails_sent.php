<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailgun reward_emails_sent Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #1a1a1a;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .step {
            background: #f9fafb;
            padding: 24px;
            margin: 20px 0;
            border-radius: 12px;
            border-left: 4px solid #8B5CF6;
        }
        .step h3 {
            color: #1a1a1a;
            font-size: 18px;
            margin-bottom: 12px;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
            padding: 16px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #10b981;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 16px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #ef4444;
        }
        .info {
            background: #dbeafe;
            color: #1e40af;
            padding: 16px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #3b82f6;
        }
        .progress {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        .check-item {
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .check-item.success {
            border-color: #10b981;
            background: #d1fae5;
        }
        .check-item.error {
            border-color: #ef4444;
            background: #fee2e2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Mailgun Email-Tracking Migration</h1>
        <div class="subtitle">reward_emails_sent Tabelle für Belohnungs-Email-Tracking</div>

        <div class="info">
            <strong>📋 Was macht diese Migration?</strong><br>
            Erstellt die Tabelle <code>reward_emails_sent</code> für das Tracking von versendeten Belohnungs-Emails via Mailgun.
        </div>

        <div class="step">
            <h3>🔍 Schritt 1: System-Check</h3>
            <button onclick="checkSystem()">System prüfen</button>
            <div id="checkResults"></div>
        </div>

        <div class="step">
            <h3>🚀 Schritt 2: Migration ausführen</h3>
            <button onclick="runMigration()" id="migrateBtn" disabled>Migration starten</button>
            <div class="progress">
                <div class="progress-bar" id="progressBar"></div>
            </div>
            <div id="migrationResults"></div>
        </div>

        <div class="step">
            <h3>✅ Schritt 3: Verifizierung</h3>
            <button onclick="verifyMigration()" id="verifyBtn" disabled>Tabelle verifizieren</button>
            <div id="verifyResults"></div>
        </div>
    </div>

    <script>
        let systemReady = false;

        async function checkSystem() {
            const resultsDiv = document.getElementById('checkResults');
            resultsDiv.innerHTML = '<p>Prüfe System...</p>';
            
            try {
                const response = await fetch('?action=check');
                const data = await response.json();
                
                let html = '';
                
                if (data.database) {
                    html += '<div class="check-item success">✅ Datenbankverbindung OK</div>';
                } else {
                    html += '<div class="check-item error">❌ Datenbankverbindung fehlgeschlagen</div>';
                    resultsDiv.innerHTML = html;
                    return;
                }
                
                if (data.tableExists) {
                    html += '<div class="check-item success">ℹ️ Tabelle existiert bereits</div>';
                    document.getElementById('verifyBtn').disabled = false;
                } else {
                    html += '<div class="check-item">📋 Tabelle muss erstellt werden</div>';
                    document.getElementById('migrateBtn').disabled = false;
                    systemReady = true;
                }
                
                resultsDiv.innerHTML = html;
                
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">❌ Fehler: ' + error.message + '</div>';
            }
        }

        async function runMigration() {
            const resultsDiv = document.getElementById('migrationResults');
            const progressBar = document.getElementById('progressBar');
            const btn = document.getElementById('migrateBtn');
            
            btn.disabled = true;
            resultsDiv.innerHTML = '<p>Migration läuft...</p>';
            progressBar.style.width = '30%';
            
            try {
                const response = await fetch('?action=migrate', { method: 'POST' });
                const data = await response.json();
                
                progressBar.style.width = '100%';
                
                if (data.success) {
                    resultsDiv.innerHTML = '<div class="success">✅ Migration erfolgreich durchgeführt!</div>';
                    document.getElementById('verifyBtn').disabled = false;
                } else {
                    resultsDiv.innerHTML = '<div class="error">❌ Fehler: ' + data.error + '</div>';
                    btn.disabled = false;
                }
                
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">❌ Fehler: ' + error.message + '</div>';
                btn.disabled = false;
                progressBar.style.width = '0%';
            }
        }

        async function verifyMigration() {
            const resultsDiv = document.getElementById('verifyResults');
            resultsDiv.innerHTML = '<p>Verifiziere Tabelle...</p>';
            
            try {
                const response = await fetch('?action=verify');
                const data = await response.json();
                
                let html = '<div class="success"><strong>✅ Tabelle erfolgreich erstellt!</strong></div>';
                html += '<pre>' + JSON.stringify(data.structure, null, 2) + '</pre>';
                
                resultsDiv.innerHTML = html;
                
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">❌ Fehler: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>

<?php
// Backend-Logik
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    switch ($_GET['action']) {
        case 'check':
            try {
                // Datenbankverbindung testen
                $pdo->query("SELECT 1");
                
                // Tabelle prüfen
                $stmt = $pdo->query("SHOW TABLES LIKE 'reward_emails_sent'");
                $tableExists = $stmt->rowCount() > 0;
                
                echo json_encode([
                    'database' => true,
                    'tableExists' => $tableExists
                ]);
            } catch (PDOException $e) {
                echo json_encode(['database' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'migrate':
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS reward_emails_sent (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        lead_id INT NOT NULL,
                        reward_id INT NOT NULL,
                        mailgun_id VARCHAR(255) NULL COMMENT 'Mailgun Message-ID für Tracking',
                        email_type VARCHAR(50) DEFAULT 'reward_unlocked' COMMENT 'reward_unlocked, welcome, verification, reminder',
                        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        opened_at DATETIME NULL,
                        clicked_at DATETIME NULL,
                        failed_at DATETIME NULL,
                        error_message TEXT NULL,
                        
                        INDEX idx_lead (lead_id),
                        INDEX idx_reward (reward_id),
                        INDEX idx_mailgun_id (mailgun_id),
                        INDEX idx_email_type (email_type),
                        INDEX idx_sent_at (sent_at),
                        UNIQUE KEY unique_reward (lead_id, reward_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                echo json_encode(['success' => true]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
            
        case 'verify':
            try {
                $stmt = $pdo->query("DESCRIBE reward_emails_sent");
                $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'structure' => $structure
                ]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;
    }
    
    exit;
}
?>
