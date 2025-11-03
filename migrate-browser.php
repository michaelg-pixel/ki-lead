<?php
/**
 * 🔄 MIGRATION TOOL: customer_id → user_id
 * Kompakte Version mit integrierter UI
 */

session_start();

// Admin-Check
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

if (!$isAdmin) {
    die('⛔ Nur Admins! <a href="/make-admin.php?token=migration2024secure">Admin werden</a> | <a href="/check-session.php">Session prüfen</a>');
}

require_once __DIR__ . '/config/database.php';

// AJAX Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    
    try {
        $pdo = getDBConnection();
        
        switch ($action) {
            case 'backup':
                $dir = __DIR__ . '/backups';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $file = $dir . '/backup_' . date('YmdHis') . '.sql';
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";
                foreach ($tables as $table) {
                    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n";
                }
                file_put_contents($file, $sql);
                echo json_encode(['success' => true, 'file' => basename($file)]);
                break;
                
            case 'migrate':
                $pdo->beginTransaction();
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                $changes = [];
                
                // Tabellen umbenennen
                $rename = [
                    'customer_freebies' => 'user_freebies',
                    'customer_freebie_limits' => 'user_freebie_limits'
                ];
                foreach ($rename as $old => $new) {
                    if ($pdo->query("SHOW TABLES LIKE '$old'")->fetch()) {
                        $pdo->exec("RENAME TABLE `$old` TO `$new`");
                        $changes[] = "$old → $new";
                    }
                }
                
                // Spalten umbenennen
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                        $pdo->exec("ALTER TABLE `$table` CHANGE COLUMN customer_id user_id INT(11)");
                        $changes[] = "$table.customer_id → user_id";
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                $pdo->commit();
                
                echo json_encode(['success' => true, 'changes' => $changes]);
                break;
                
            case 'verify':
                $old = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll();
                $issues = count($old) > 0 ? ['Alte Tabellen gefunden'] : [];
                echo json_encode(['success' => count($issues) === 0, 'issues' => $issues]);
                break;
                
            default:
                throw new Exception('Unbekannte Aktion');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 Migration Tool</title>
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
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; }
        .content { padding: 40px; }
        .step {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #dee2e6;
        }
        .step.active { border-left-color: #667eea; background: #f0f4ff; }
        .step.complete { border-left-color: #10b981; background: #f0fdf4; }
        .step h3 { margin-bottom: 15px; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin: 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .result {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 13px;
            max-height: 200px;
            overflow-y: auto;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Migration Tool</h1>
            <p>customer_id → user_id</p>
        </div>
        
        <div class="content">
            <div class="warning">
                <strong>⚠️ WICHTIG:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Backup wird automatisch erstellt</li>
                    <li>Migration kann einige Minuten dauern</li>
                    <li>Browser NICHT schließen!</li>
                    <li>Nach Migration: Dateien löschen!</li>
                </ul>
            </div>
            
            <div class="step" id="step1">
                <h3>1️⃣ Backup erstellen</h3>
                <p>Sicherung der Datenbank</p>
                <button class="btn" onclick="runBackup()">Backup erstellen</button>
                <div id="result1" class="result" style="display: none;"></div>
            </div>
            
            <div class="step" id="step2">
                <h3>2️⃣ Migration durchführen</h3>
                <p>Tabellen und Spalten umbenennen</p>
                <button class="btn" onclick="runMigration()" disabled id="btn2">Migration starten</button>
                <div id="result2" class="result" style="display: none;"></div>
            </div>
            
            <div class="step" id="step3">
                <h3>3️⃣ Verifizierung</h3>
                <p>Migration prüfen</p>
                <button class="btn" onclick="runVerify()" disabled id="btn3">Prüfen</button>
                <div id="result3" class="result" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <script>
        async function runBackup() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Wird erstellt...';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=backup'
                });
                const data = await res.json();
                
                const result = document.getElementById('result1');
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">✅ Backup erstellt: ' + data.file + '</span>';
                    document.getElementById('step1').classList.add('complete');
                    document.getElementById('btn2').disabled = false;
                    document.getElementById('step2').classList.add('active');
                } else {
                    result.innerHTML = '<span class="error">❌ ' + data.error + '</span>';
                }
            } catch (error) {
                document.getElementById('result1').innerHTML = '<span class="error">❌ ' + error + '</span>';
            }
            
            btn.textContent = 'Backup erstellen';
        }
        
        async function runMigration() {
            if (!confirm('⚠️ Migration jetzt starten? Datenbank wird geändert!')) return;
            
            const btn = document.getElementById('btn2');
            btn.disabled = true;
            btn.textContent = 'Migration läuft...';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=migrate'
                });
                const data = await res.json();
                
                const result = document.getElementById('result2');
                result.style.display = 'block';
                
                if (data.success) {
                    let html = '<span class="success">✅ Migration erfolgreich!</span><br><br>';
                    data.changes.forEach(c => html += '✓ ' + c + '<br>');
                    result.innerHTML = html;
                    document.getElementById('step2').classList.add('complete');
                    document.getElementById('btn3').disabled = false;
                    document.getElementById('step3').classList.add('active');
                } else {
                    result.innerHTML = '<span class="error">❌ ' + data.error + '</span>';
                }
            } catch (error) {
                document.getElementById('result2').innerHTML = '<span class="error">❌ ' + error + '</span>';
            }
            
            btn.textContent = 'Migration starten';
        }
        
        async function runVerify() {
            const btn = document.getElementById('btn3');
            btn.disabled = true;
            btn.textContent = 'Wird geprüft...';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=verify'
                });
                const data = await res.json();
                
                const result = document.getElementById('result3');
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">🎉 Migration erfolgreich abgeschlossen!</span><br><br>' +
                                     '<strong>⚠️ WICHTIG: Lösche jetzt folgende Dateien:</strong><br>' +
                                     '• migrate-browser.php<br>' +
                                     '• make-admin.php<br>' +
                                     '• check-session.php';
                    document.getElementById('step3').classList.add('complete');
                } else {
                    result.innerHTML = '<span class="error">❌ Probleme gefunden:</span><br>' + data.issues.join('<br>');
                }
            } catch (error) {
                document.getElementById('result3').innerHTML = '<span class="error">❌ ' + error + '</span>';
            }
            
            btn.textContent = 'Prüfen';
            btn.disabled = false;
        }
    </script>
</body>
</html>
