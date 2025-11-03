<?php
/**
 * 🔄 MIGRATION TOOL: customer_id → user_id
 * Mit verbessertem Error-Handling
 */

error_reporting(0);
ini_set('display_errors', 0);

set_time_limit(300);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

session_start();

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

if (!$isAdmin && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('⛔ Nur Admins! <a href="/make-admin.php?token=migration2024secure">Admin werden</a>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $pdo = null;
    
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDBConnection();
        
        if (!$pdo) {
            throw new Exception('Datenbankverbindung fehlgeschlagen');
        }
        
        switch ($action) {
            case 'backup':
                $dir = __DIR__ . '/backups';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                
                $file = $dir . '/backup_' . date('YmdHis') . '.sql';
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                $sql = "-- STRUCTURE BACKUP " . date('Y-m-d H:i:s') . "\n\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
                
                foreach ($tables as $table) {
                    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                    $sql .= $create[1] . ";\n\n";
                }
                
                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
                file_put_contents($file, $sql);
                
                ob_clean();
                echo json_encode([
                    'success' => true, 
                    'file' => basename($file),
                    'size' => round(filesize($file) / 1024, 2) . ' KB',
                    'tables' => count($tables)
                ]);
                break;
                
            case 'check':
                $oldTables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
                $oldColumns = 0;
                
                $checkTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($checkTables as $table) {
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {}
                }
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'old_tables' => count($oldTables),
                    'old_columns' => $oldColumns,
                    'needs_migration' => (count($oldTables) > 0 || $oldColumns > 0)
                ]);
                break;
                
            case 'migrate':
                $changes = [];
                $skipped = [];
                $errors = [];
                
                // Transaction starten
                $transactionStarted = false;
                try {
                    $pdo->beginTransaction();
                    $transactionStarted = true;
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                } catch (Exception $e) {
                    $errors[] = "Transaction-Start: " . $e->getMessage();
                }
                
                // Tabellen umbenennen
                $rename = [
                    'customer_freebies' => 'user_freebies',
                    'customer_freebie_limits' => 'user_freebie_limits',
                    'customer_courses' => 'user_courses',
                    'customer_progress' => 'user_progress'
                ];
                
                foreach ($rename as $old => $new) {
                    try {
                        $oldExists = $pdo->query("SHOW TABLES LIKE '$old'")->fetch();
                        
                        if ($oldExists) {
                            $newExists = $pdo->query("SHOW TABLES LIKE '$new'")->fetch();
                            
                            if ($newExists) {
                                $pdo->exec("DROP TABLE IF EXISTS `$old`");
                                $skipped[] = "$old (Ziel existiert, alte gelöscht)";
                            } else {
                                $pdo->exec("RENAME TABLE `$old` TO `$new`");
                                $changes[] = "Tabelle: $old → $new";
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Tabelle $old: " . $e->getMessage();
                    }
                }
                
                // Spalten umbenennen
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    try {
                        $hasOld = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch();
                        
                        if ($hasOld) {
                            $hasNew = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'user_id'")->fetch();
                            
                            if ($hasNew) {
                                $pdo->exec("ALTER TABLE `$table` DROP COLUMN customer_id");
                                $skipped[] = "$table.customer_id (user_id existiert, alte gelöscht)";
                            } else {
                                $col = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Field = 'customer_id'")->fetch();
                                $type = $col['Type'];
                                $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                                $pdo->exec("ALTER TABLE `$table` CHANGE COLUMN customer_id user_id $type $null");
                                $changes[] = "Spalte: $table.customer_id → user_id";
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Spalte $table: " . $e->getMessage();
                    }
                }
                
                // Transaction beenden
                try {
                    if ($transactionStarted) {
                        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                        $pdo->commit();
                    }
                } catch (Exception $e) {
                    $errors[] = "Commit: " . $e->getMessage();
                }
                
                ob_clean();
                echo json_encode([
                    'success' => (count($changes) > 0 || count($skipped) > 0),
                    'changes' => $changes,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'count' => count($changes) + count($skipped)
                ]);
                break;
                
            case 'verify':
                $oldTables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
                $oldColumns = 0;
                $newTables = 0;
                
                $checkNew = ['user_freebies', 'user_freebie_limits'];
                foreach ($checkNew as $table) {
                    if ($pdo->query("SHOW TABLES LIKE '$table'")->fetch()) {
                        $newTables++;
                    }
                }
                
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {}
                }
                
                $success = (count($oldTables) === 0 && $oldColumns === 0 && $newTables > 0);
                
                ob_clean();
                echo json_encode([
                    'success' => $success,
                    'old_tables' => count($oldTables),
                    'old_columns' => $oldColumns,
                    'new_tables' => $newTables
                ]);
                break;
                
            default:
                throw new Exception('Unbekannte Aktion');
        }
        
    } catch (Exception $e) {
        // Nur rollback wenn Transaction aktiv
        if ($pdo && method_exists($pdo, 'inTransaction') && $pdo->inTransaction()) {
            try {
                $pdo->rollBack();
            } catch (Exception $rollbackError) {
                // Ignorieren
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage()
        ]);
    }
    
    ob_end_flush();
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
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
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: #f1f5f9; color: #64748b; }
        .result {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .success { color: #10b981; font-weight: 600; }
        .error { color: #ef4444; font-weight: 600; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin-bottom: 30px; }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .info-box { background: #e0e7ff; border: 2px solid #6366f1; border-radius: 8px; padding: 15px; margin-top: 15px; }
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
                <strong>⚠️ WICHTIG:</strong> Migration läuft automatisch, Browser nicht schließen!
            </div>
            
            <div class="step active" id="step0">
                <h3>0️⃣ System-Check</h3>
                <button class="btn" onclick="runCheck()">Starten</button>
                <div id="result0" class="result"></div>
            </div>
            
            <div class="step" id="step1">
                <h3>1️⃣ Backup</h3>
                <button class="btn" onclick="runBackup()" disabled id="btn1">Backup</button>
                <button class="btn btn-secondary" onclick="skipBackup()">Skip</button>
                <div id="result1" class="result"></div>
            </div>
            
            <div class="step" id="step2">
                <h3>2️⃣ Migration</h3>
                <button class="btn" onclick="runMigration()" disabled id="btn2">Starten</button>
                <div id="result2" class="result"></div>
            </div>
            
            <div class="step" id="step3">
                <h3>3️⃣ Verifizierung</h3>
                <button class="btn" onclick="runVerify()" disabled id="btn3">Prüfen</button>
                <div id="result3" class="result"></div>
            </div>
        </div>
    </div>
    
    <script>
        async function callApi(action, button) {
            const txt = button.textContent;
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> ...';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=' + action
                });
                
                const text = await res.text();
                if (!text.trim().startsWith('{')) {
                    console.error('Response:', text);
                    throw new Error('Ungültige Antwort vom Server');
                }
                
                button.textContent = txt;
                return JSON.parse(text);
            } catch (e) {
                button.textContent = txt;
                throw e;
            }
        }
        
        async function runCheck() {
            try {
                const data = await callApi('check', event.target);
                const r = document.getElementById('result0');
                r.style.display = 'block';
                
                if (data.success) {
                    r.innerHTML = '<span class="success">✅ OK</span><br>Tabellen: ' + data.old_tables + '<br>Spalten: ' + data.old_columns;
                    document.getElementById('step0').classList.add('complete');
                    document.getElementById('btn1').disabled = false;
                    document.getElementById('step1').classList.add('active');
                }
            } catch (e) {
                document.getElementById('result0').innerHTML = '<span class="error">❌ ' + e.message + '</span>';
                document.getElementById('result0').style.display = 'block';
            }
        }
        
        async function runBackup() {
            try {
                const data = await callApi('backup', event.target);
                const r = document.getElementById('result1');
                r.style.display = 'block';
                
                if (data.success) {
                    r.innerHTML = '<span class="success">✅ ' + data.file + ' (' + data.size + ')</span>';
                    completeStep(1);
                }
            } catch (e) {
                document.getElementById('result1').innerHTML = '<span class="error">❌ ' + e.message + '</span>';
                document.getElementById('result1').style.display = 'block';
            }
        }
        
        function skipBackup() {
            document.getElementById('result1').innerHTML = '⚠️ Übersprungen';
            document.getElementById('result1').style.display = 'block';
            completeStep(1);
        }
        
        async function runMigration() {
            if (!confirm('Migration starten?')) return;
            
            try {
                const data = await callApi('migrate', document.getElementById('btn2'));
                const r = document.getElementById('result2');
                r.style.display = 'block';
                
                let html = data.success ? '<span class="success">✅ Erfolg!</span><br><br>' : '<span class="error">⚠️ Mit Fehlern</span><br><br>';
                
                if (data.changes) {
                    html += '<strong>Geändert:</strong><br>';
                    data.changes.forEach(c => html += '✓ ' + c + '<br>');
                }
                
                if (data.skipped) {
                    html += '<br><strong>Übersprungen:</strong><br>';
                    data.skipped.forEach(s => html += '⊘ ' + s + '<br>');
                }
                
                if (data.errors && data.errors.length > 0) {
                    html += '<br><strong>Fehler:</strong><br>';
                    data.errors.forEach(e => html += '❌ ' + e + '<br>');
                }
                
                r.innerHTML = html;
                completeStep(2);
            } catch (e) {
                document.getElementById('result2').innerHTML = '<span class="error">❌ ' + e.message + '</span>';
                document.getElementById('result2').style.display = 'block';
            }
        }
        
        async function runVerify() {
            try {
                const data = await callApi('verify', document.getElementById('btn3'));
                const r = document.getElementById('result3');
                r.style.display = 'block';
                
                if (data.success) {
                    r.innerHTML = '<span class="success">🎉 FERTIG!</span><br><br>' +
                                 '<div class="info-box">Lösche jetzt:<br>• migrate-browser.php<br>• make-admin.php<br>• check-session.php</div>';
                    document.getElementById('step3').classList.add('complete');
                } else {
                    r.innerHTML = '❌ Alte Tabellen: ' + data.old_tables + '<br>Alte Spalten: ' + data.old_columns;
                }
            } catch (e) {
                document.getElementById('result3').innerHTML = '<span class="error">❌ ' + e.message + '</span>';
                document.getElementById('result3').style.display = 'block';
            }
        }
        
        function completeStep(s) {
            document.getElementById('step' + s).classList.add('complete');
            document.getElementById('btn' + (s+1)).disabled = false;
            document.getElementById('step' + (s+1)).classList.add('active');
        }
        
        window.onload = () => setTimeout(() => document.querySelector('#step0 .btn').click(), 500);
    </script>
</body>
</html>
