<?php
/**
 * 🚀 LEAD SYSTEM - DATABASE MIGRATION
 * 
 * Browser-Skript zum Erstellen der Lead-System Tabellen
 * 
 * AUFRUF:
 * https://app.mehr-infos-jetzt.de/install-lead-system.php
 * 
 * SICHERHEIT:
 * Dieses Skript sollte nach erfolgreicher Installation gelöscht werden!
 */

// Fehlerausgabe aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pfad zur Datenbank-Konfiguration
require_once __DIR__ . '/config/database.php';

// Prüfen ob bereits installiert
$check_installed = false;
$installation_started = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $installation_started = true;
}

// Installation durchführen
if ($installation_started) {
    try {
        $db = getDBConnection();
        
        // SQL-Datei einlesen
        $sql_file = __DIR__ . '/database/migrations/005_lead_system.sql';
        
        if (!file_exists($sql_file)) {
            throw new Exception("SQL-Datei nicht gefunden: {$sql_file}");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // SQL in einzelne Statements aufteilen
        $statements = array_filter(
            array_map('trim', explode(';', $sql_content)),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       !preg_match('/^\/\*/', $stmt);
            }
        );
        
        $results = [];
        $errors = [];
        
        // Jedes Statement ausführen
        foreach ($statements as $statement) {
            try {
                $db->exec($statement);
                
                // Tabellen-/View-Namen extrahieren für bessere Anzeige
                if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $results[] = ['type' => 'table', 'name' => $matches[1], 'status' => 'success'];
                } elseif (preg_match('/CREATE.*?VIEW.*?`?(\w+)`?/i', $statement, $matches)) {
                    $results[] = ['type' => 'view', 'name' => $matches[1], 'status' => 'success'];
                } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                    $results[] = ['type' => 'alter', 'name' => $matches[1], 'status' => 'success'];
                } else {
                    $results[] = ['type' => 'statement', 'name' => substr($statement, 0, 50) . '...', 'status' => 'success'];
                }
            } catch (PDOException $e) {
                // Fehler ignorieren wenn Tabelle bereits existiert
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                        $results[] = ['type' => 'table', 'name' => $matches[1], 'status' => 'exists'];
                    }
                } else {
                    $errors[] = [
                        'statement' => substr($statement, 0, 100) . '...',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        
        $success = count($errors) === 0;
        
    } catch (Exception $e) {
        $success = false;
        $errors[] = [
            'statement' => 'General Error',
            'error' => $e->getMessage()
        ];
    }
}

// Prüfen welche Tabellen existieren
try {
    $db = getDBConnection();
    $stmt = $db->query("SHOW TABLES LIKE 'lead_%'");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $check_installed = count($existing_tables) > 0;
} catch (Exception $e) {
    $check_installed = false;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚀 Lead System Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 36px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            color: #1976D2;
            margin-bottom: 10px;
            font-size: 18px;
        }
        .info-box ul {
            margin: 10px 0 10px 20px;
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
            margin-bottom: 30px;
        }
        .warning-box strong {
            color: #856404;
            font-size: 16px;
            display: block;
            margin-bottom: 10px;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
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
            margin-bottom: 30px;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 15px;
        }
        .result-item {
            padding: 10px;
            margin: 8px 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        .result-item.success {
            background: #d4edda;
            color: #155724;
        }
        .result-item.exists {
            background: #fff3cd;
            color: #856404;
        }
        .result-item .icon {
            font-size: 20px;
        }
        .result-item .type {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .result-item .name {
            flex: 1;
            font-weight: 600;
        }
        .error-item {
            background: #f8d7da;
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 3px solid #dc3545;
        }
        .error-item .error-msg {
            color: #721c24;
            font-size: 13px;
            margin-top: 5px;
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .existing-tables {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .existing-tables ul {
            margin: 10px 0 0 20px;
        }
        .existing-tables li {
            color: #666;
            margin: 5px 0;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Lead System</h1>
        <div class="subtitle">Datenbank Installation</div>
        
        <?php if (!$installation_started): ?>
            
            <div class="info-box">
                <h3>📦 Was wird installiert:</h3>
                <ul>
                    <li><strong>lead_users</strong> - Lead Login-System mit Empfehlungscodes</li>
                    <li><strong>lead_referrals</strong> - Tracking einzelner Empfehlungen</li>
                    <li><strong>referral_reward_tiers</strong> - Erreichte Belohnungsstufen</li>
                    <li><strong>referral_claimed_rewards</strong> - Eingelöste Belohnungen</li>
                    <li><strong>lead_activity_log</strong> - Aktivitäts-Tracking</li>
                    <li><strong>view_lead_overview</strong> - Übersichts-View</li>
                </ul>
            </div>
            
            <?php if ($check_installed): ?>
                <div class="warning-box">
                    <strong>⚠️ Achtung!</strong>
                    <p>Es existieren bereits Lead-System Tabellen:</p>
                    <div class="existing-tables">
                        <ul>
                            <?php foreach ($existing_tables as $table): ?>
                                <li><?php echo htmlspecialchars($table); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <p style="margin-top: 10px;">Die Installation wird vorhandene Tabellen NICHT überschreiben.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="install" class="button">
                    ⚡ JETZT INSTALLIEREN
                </button>
            </form>
            
            <div class="warning-box" style="margin-top: 20px;">
                <strong>🔒 Sicherheitshinweis:</strong>
                <p>Lösche diese Datei nach erfolgreicher Installation!</p>
            </div>
            
        <?php else: ?>
            
            <?php if ($success): ?>
                <div class="success-box">
                    <h3>✅ Installation erfolgreich abgeschlossen!</h3>
                    <p>Alle Tabellen wurden erstellt oder waren bereits vorhanden.</p>
                    
                    <?php if (!empty($results)): ?>
                        <div style="margin-top: 20px;">
                            <?php foreach ($results as $result): ?>
                                <div class="result-item <?php echo $result['status']; ?>">
                                    <span class="icon">
                                        <?php echo $result['status'] === 'success' ? '✅' : '⚠️'; ?>
                                    </span>
                                    <span class="type"><?php echo $result['type']; ?></span>
                                    <span class="name"><?php echo htmlspecialchars($result['name']); ?></span>
                                    <span style="opacity: 0.7; font-size: 12px;">
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
                        <li><strong>Lösche diese Datei:</strong> install-lead-system.php</li>
                        <li><strong>Teste das Login:</strong> <a href="lead_login.php" target="_blank">lead_login.php</a></li>
                        <li><strong>Registriere einen Test-Lead</strong> und prüfe das Dashboard</li>
                        <li><strong>Konfiguriere die Belohnungen</strong> im Dashboard</li>
                    </ol>
                </div>
                
                <div class="info-box" style="margin-top: 20px;">
                    <h3>📋 Wichtige URLs:</h3>
                    <ul>
                        <li><strong>Lead Login:</strong> <a href="lead_login.php">lead_login.php</a></li>
                        <li><strong>Lead Dashboard:</strong> <a href="lead_dashboard.php">lead_dashboard.php</a></li>
                        <li><strong>API Endpoint:</strong> api_referral_rewards/register-lead.php</li>
                    </ul>
                </div>
                
            <?php else: ?>
                <div class="error-box">
                    <h3>❌ Fehler bei der Installation</h3>
                    <p>Es sind Fehler aufgetreten:</p>
                    
                    <?php foreach ($errors as $error): ?>
                        <div class="error-item">
                            <strong>Statement:</strong> <?php echo htmlspecialchars($error['statement']); ?>
                            <div class="error-msg"><?php echo htmlspecialchars($error['error']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($results)): ?>
                    <div class="info-box">
                        <h3>✅ Erfolgreich erstellt:</h3>
                        <?php foreach ($results as $result): ?>
                            <?php if ($result['status'] === 'success'): ?>
                                <div class="result-item success">
                                    <span class="icon">✅</span>
                                    <span class="type"><?php echo $result['type']; ?></span>
                                    <span class="name"><?php echo htmlspecialchars($result['name']); ?></span>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <button type="submit" name="install" class="button">
                        🔄 Erneut versuchen
                    </button>
                </form>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</body>
</html>
