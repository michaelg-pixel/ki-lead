<?php
/**
 * Test-Script für Reward Tag Funktionalität
 * 
 * Aufrufen über: https://app.mehr-infos-jetzt.de/test-reward-tag.php
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reward Tag Test</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #7C3AED;
            margin-bottom: 10px;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #7C3AED;
        }
        .success {
            color: #10b981;
            background: #d1fae5;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .info {
            color: #3b82f6;
            background: #dbeafe;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .code {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #7C3AED;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 10px 10px 0;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #6d28d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Reward Tag Test & Migration</h1>
        <p>Teste die reward_tag Funktionalität für Quentn-Kampagnen</p>
        
        <?php
        
        $pdo = getDBConnection();
        
        // Migration ausführen wenn Parameter gesetzt
        if (isset($_GET['run_migration'])) {
            echo '<div class="section">';
            echo '<h2>📦 Migration ausführen</h2>';
            
            try {
                // Prüfe Struktur
                $columns = $pdo->query("SHOW COLUMNS FROM reward_definitions")->fetchAll(PDO::FETCH_ASSOC);
                $columnNames = array_column($columns, 'Field');
                
                // reward_tag zu reward_definitions
                $checkColumn = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'reward_tag'");
                
                if ($checkColumn->rowCount() == 0) {
                    $lastColumn = end($columnNames);
                    $pdo->exec("
                        ALTER TABLE reward_definitions
                        ADD COLUMN reward_tag VARCHAR(100) NULL 
                        COMMENT 'Optional: Benutzerdefinierter Tag für Kampagnen-Trigger'
                        AFTER {$lastColumn}
                    ");
                    echo '<div class="success">✅ reward_tag zu reward_definitions hinzugefügt (nach ' . $lastColumn . ')</div>';
                } else {
                    echo '<div class="info">ℹ️ reward_tag existiert bereits in reward_definitions</div>';
                }
                
                // reward_tag zu customer_email_api_settings
                $checkTable = $pdo->query("SHOW TABLES LIKE 'customer_email_api_settings'");
                
                if ($checkTable->rowCount() > 0) {
                    $checkColumn = $pdo->query("SHOW COLUMNS FROM customer_email_api_settings LIKE 'reward_tag'");
                    
                    if ($checkColumn->rowCount() == 0) {
                        $apiColumns = $pdo->query("SHOW COLUMNS FROM customer_email_api_settings")->fetchAll(PDO::FETCH_ASSOC);
                        $apiColumnNames = array_column($apiColumns, 'Field');
                        $afterColumn = in_array('api_url', $apiColumnNames) ? 'api_url' : end($apiColumnNames);
                        
                        $pdo->exec("
                            ALTER TABLE customer_email_api_settings
                            ADD COLUMN reward_tag VARCHAR(100) NULL 
                            COMMENT 'Optional: Globaler Tag für alle Belohnungen'
                            AFTER {$afterColumn}
                        ");
                        echo '<div class="success">✅ reward_tag zu customer_email_api_settings hinzugefügt (nach ' . $afterColumn . ')</div>';
                    } else {
                        echo '<div class="info">ℹ️ reward_tag existiert bereits in customer_email_api_settings</div>';
                    }
                } else {
                    echo '<div class="info">ℹ️ Tabelle customer_email_api_settings existiert nicht</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
        }
        
        // Tag setzen wenn Parameter gesetzt
        if (isset($_GET['set_tag']) && isset($_GET['user_id'])) {
            $userId = intval($_GET['user_id']);
            $tag = $_GET['tag'] ?? 'Optinpilot-Belohnung';
            
            echo '<div class="section">';
            echo '<h2>🏷️ Tag setzen</h2>';
            
            try {
                $updated = 0;
                
                // Option 1: Global für alle Belohnungen (wenn Tabelle existiert)
                $checkTable = $pdo->query("SHOW TABLES LIKE 'customer_email_api_settings'");
                if ($checkTable->rowCount() > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE customer_email_api_settings 
                        SET reward_tag = ?
                        WHERE customer_id = ?
                    ");
                    $stmt->execute([$tag, $userId]);
                    
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="success">✅ Global-Tag auf "' . htmlspecialchars($tag) . '" gesetzt</div>';
                        $updated++;
                    } else {
                        echo '<div class="info">ℹ️ Keine API-Settings für diesen User gefunden</div>';
                    }
                }
                
                // Option 2: Für alle Rewards dieses Users
                $stmt = $pdo->prepare("
                    UPDATE reward_definitions 
                    SET reward_tag = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$tag, $userId]);
                
                $affected = $stmt->rowCount();
                if ($affected > 0) {
                    echo '<div class="success">✅ Tag für ' . $affected . ' Belohnungen gesetzt</div>';
                    $updated++;
                } else {
                    echo '<div class="info">ℹ️ Keine Belohnungen für diesen User gefunden</div>';
                }
                
                if ($updated == 0) {
                    echo '<div class="error">❌ Konnte Tag nicht setzen - User ID existiert nicht?</div>';
                }
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Fehler: ' . $e->getMessage() . '</div>';
            }
            
            echo '</div>';
        }
        
        // Status anzeigen
        echo '<div class="section">';
        echo '<h2>📊 Aktueller Status</h2>';
        
        // Prüfe Spalten
        $checkRewardDef = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'reward_tag'");
        $checkTable = $pdo->query("SHOW TABLES LIKE 'customer_email_api_settings'");
        $hasApiTable = $checkTable->rowCount() > 0;
        $checkApiSettings = $hasApiTable ? $pdo->query("SHOW COLUMNS FROM customer_email_api_settings LIKE 'reward_tag'") : null;
        
        echo '<h3>Datenbank-Felder</h3>';
        echo '<table>';
        echo '<tr><th>Tabelle</th><th>Feld</th><th>Status</th></tr>';
        echo '<tr><td>reward_definitions</td><td>reward_tag</td><td>' . 
             ($checkRewardDef->rowCount() > 0 ? '<span style="color: #10b981;">✅ Existiert</span>' : '<span style="color: #ef4444;">❌ Fehlt</span>') . 
             '</td></tr>';
        
        if ($hasApiTable) {
            echo '<tr><td>customer_email_api_settings</td><td>reward_tag</td><td>' . 
                 ($checkApiSettings->rowCount() > 0 ? '<span style="color: #10b981;">✅ Existiert</span>' : '<span style="color: #ef4444;">❌ Fehlt</span>') . 
                 '</td></tr>';
        } else {
            echo '<tr><td>customer_email_api_settings</td><td>-</td><td><span style="color: #6b7280;">ℹ️ Tabelle existiert nicht</span></td></tr>';
        }
        
        echo '</table>';
        
        // Zeige Reward Definitions
        $stmt = $pdo->query("
            SELECT 
                rd.*,
                u.email as customer_email,
                u.company_name
            FROM reward_definitions rd
            LEFT JOIN users u ON rd.user_id = u.id
            ORDER BY rd.user_id, rd.tier_level
            LIMIT 10
        ");
        
        echo '<h3>Belohnungs-Definitionen (Top 10)</h3>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Customer</th><th>Tier</th><th>Titel</th><th>Reward Tag</th></tr>';
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . htmlspecialchars($row['customer_email']) . '</td>';
            echo '<td>' . $row['tier_level'] . '</td>';
            echo '<td>' . htmlspecialchars($row['reward_title']) . '</td>';
            echo '<td>' . (isset($row['reward_tag']) && $row['reward_tag'] ? '<strong>' . htmlspecialchars($row['reward_tag']) . '</strong>' : '<em>nicht gesetzt</em>') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // API Settings anzeigen wenn vorhanden
        if ($hasApiTable) {
            $stmt = $pdo->query("
                SELECT 
                    eas.*,
                    u.email as customer_email
                FROM customer_email_api_settings eas
                LEFT JOIN users u ON eas.customer_id = u.id
                LIMIT 5
            ");
            
            echo '<h3>Email-API-Settings (Top 5)</h3>';
            echo '<table>';
            echo '<tr><th>Customer</th><th>Provider</th><th>Global Reward Tag</th></tr>';
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['customer_email'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($row['provider'] ?? 'N/A') . '</td>';
                echo '<td>' . (isset($row['reward_tag']) && $row['reward_tag'] ? '<strong>' . htmlspecialchars($row['reward_tag']) . '</strong>' : '<em>nicht gesetzt</em>') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        echo '</div>';
        
        // Cronjob Status
        echo '<div class="section">';
        echo '<h2>⏰ Cronjob Status</h2>';
        
        echo '<div class="info">';
        echo '<strong>Cronjob URL:</strong><br>';
        echo 'https://app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php';
        echo '</div>';
        
        echo '<div class="code">';
        echo '*/5 * * * * php /var/www/app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php';
        echo '</div>';
        
        echo '</div>';
        
        // Anleitung
        echo '<div class="section">';
        echo '<h2>📋 Anleitung</h2>';
        
        echo '<h3>1. Migration ausführen</h3>';
        echo '<a href="?run_migration=1" class="btn">Migration jetzt ausführen</a>';
        
        echo '<h3>2. Tag für deine Quentn-Kampagne setzen</h3>';
        echo '<p>Setze den Tag "Optinpilot-Belohnung" für deine User-ID:</p>';
        
        // Finde erste User-ID mit Rewards
        $firstUser = $pdo->query("SELECT DISTINCT user_id FROM reward_definitions LIMIT 1")->fetch();
        if ($firstUser) {
            echo '<a href="?set_tag=1&user_id=' . $firstUser['user_id'] . '&tag=Optinpilot-Belohnung" class="btn">';
            echo 'Tag setzen für User ID ' . $firstUser['user_id'];
            echo '</a>';
        }
        
        echo '<h3>3. In Quentn</h3>';
        echo '<ol>';
        echo '<li>Kampagne mit Start-Tag "Optinpilot-Belohnung" erstellen</li>';
        echo '<li>E-Mail-Template mit Platzhaltern anlegen:
            <div class="code">
            Verfügbare Platzhalter:<br>
            - {{reward_title}}<br>
            - {{reward_description}}<br>
            - {{reward_value}}<br>
            - {{reward_download_url}}<br>
            - {{reward_instructions}}<br>
            - {{successful_referrals}}<br>
            - {{current_points}}<br>
            - {{referral_code}}
            </div>
        </li>';
        echo '<li>Custom Fields in Quentn anlegen (werden automatisch aktualisiert)</li>';
        echo '</ol>';
        
        echo '<h3>4. Test</h3>';
        echo '<p>Cronjob manuell aufrufen:</p>';
        echo '<a href="https://app.mehr-infos-jetzt.de/api/rewards/auto-deliver-cron.php" target="_blank" class="btn">Cronjob testen</a>';
        
        echo '</div>';
        
        ?>
    </div>
</body>
</html>
