<?php
/**
 * 🔄 MIGRATION TOOL: customer_id → user_id
 * Mit View-Support und besserer Diagnostik
 */

error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(300);
ini_set('memory_limit', '512M');

session_start();

$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

if (!$isAdmin && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('⛔ Nur Admins!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $pdo = null;
    
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDBConnection();
        
        switch ($action) {
            case 'backup':
                $dir = __DIR__ . '/backups';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                
                $file = $dir . '/backup_' . date('YmdHis') . '.sql';
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                $sql = "-- BACKUP " . date('Y-m-d H:i:s') . "\n\n";
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
                    'size' => round(filesize($file) / 1024, 2) . ' KB'
                ]);
                break;
                
            case 'check':
                // Tabellen
                $oldTables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
                
                // Spalten (nur echte Tabellen, keine Views)
                $oldColumns = 0;
                $allTables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
                
                foreach ($allTables as $row) {
                    $table = $row[0];
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {}
                }
                
                // Views
                $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);
                $oldViews = [];
                foreach ($views as $row) {
                    $view = $row[0];
                    if (stripos($view, 'customer') !== false) {
                        $oldViews[] = $view;
                    }
                }
                
                ob_clean();
                echo json_encode([
                    'success' => true,
                    'old_tables' => $oldTables,
                    'old_columns' => $oldColumns,
                    'old_views' => $oldViews,
                    'needs_migration' => (count($oldTables) > 0 || $oldColumns > 0)
                ]);
                break;
                
            case 'migrate':
                $changes = [];
                $skipped = [];
                $errors = [];
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                
                // 1. TABELLEN umbenennen
                $rename = [
                    'customer_freebies' => 'user_freebies',
                    'customer_freebie_limits' => 'user_freebie_limits',
                    'customer_courses' => 'user_courses',
                    'customer_progress' => 'user_progress',
                    'customer_tutorials' => 'user_tutorials'
                ];
                
                foreach ($rename as $old => $new) {
                    try {
                        $oldExists = $pdo->query("SHOW TABLES LIKE '$old'")->fetch();
                        
                        if ($oldExists) {
                            $newExists = $pdo->query("SHOW TABLES LIKE '$new'")->fetch();
                            
                            if ($newExists) {
                                $pdo->exec("DROP TABLE IF EXISTS `$old`");
                                $skipped[] = "$old → gelöscht (Ziel existiert)";
                            } else {
                                $pdo->exec("RENAME TABLE `$old` TO `$new`");
                                $changes[] = "Tabelle: $old → $new";
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "Tabelle $old: " . $e->getMessage();
                    }
                }
                
                // 2. SPALTEN umbenennen (nur echte Tabellen!)
                $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
                
                foreach ($tables as $row) {
                    $table = $row[0];
                    
                    try {
                        $hasOld = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch();
                        
                        if ($hasOld) {
                            $hasNew = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'user_id'")->fetch();
                            
                            if ($hasNew) {
                                $pdo->exec("ALTER TABLE `$table` DROP COLUMN customer_id");
                                $skipped[] = "$table.customer_id → gelöscht (user_id existiert)";
                            } else {
                                $col = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Field = 'customer_id'")->fetch();
                                $type = $col['Type'];
                                $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                                $pdo->exec("ALTER TABLE `$table` CHANGE COLUMN customer_id user_id $type $null");
                                $changes[] = "Spalte: $table.customer_id → user_id";
                            }
                        }
                    } catch (Exception $e) {
                        $errors[] = "$table: " . $e->getMessage();
                    }
                }
                
                // 3. VIEWS updaten (falls vorhanden)
                $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM);
                
                foreach ($views as $row) {
                    $view = $row[0];
                    
                    try {
                        // View Definition holen
                        $viewDef = $pdo->query("SHOW CREATE VIEW `$view`")->fetch();
                        $createView = $viewDef['Create View'];
                        
                        // Ersetze customer_ mit user_
                        $newCreateView = str_replace('customer_', 'user_', $createView);
                        $newCreateView = str_replace('customer_id', 'user_id', $newCreateView);
                        
                        if ($newCreateView !== $createView) {
                            // View droppen und neu erstellen
                            $pdo->exec("DROP VIEW IF EXISTS `$view`");
                            $pdo->exec($newCreateView);
                            $changes[] = "View: $view aktualisiert";
                        }
                    } catch (Exception $e) {
                        $errors[] = "View $view: " . $e->getMessage();
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                
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
                
                // Nur echte Tabellen prüfen
                $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
                
                foreach ($tables as $row) {
                    $table = $row[0];
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {}
                }
                
                $checkNew = ['user_freebies', 'user_freebie_limits'];
                foreach ($checkNew as $table) {
                    if ($pdo->query("SHOW TABLES LIKE '$table'")->fetch()) {
                        $newTables++;
                    }
                }
                
                $success = (count($oldTables) === 0 && $oldColumns === 0 && $newTables > 0);
                
                ob_clean();
                echo json_encode([
                    'success' => $success,
                    'old_tables' => $oldTables,
                    'old_tables_count' => count($oldTables),
                    'old_columns' => $oldColumns,
                    'new_tables' => $newTables
                ]);
                break;
                
            default:
                throw new Exception('Unbekannte Aktion');
        }
        
    } catch (Exception $e) {
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
            max-width: 900px;
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
        .header h1 { font-size: 32px; }
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
        .btn:disabled { opacity: 0.5; }
        .btn-secondary { background: #f1f5f9; color: #64748b; }
        .result {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        }
        .success { color: #10b981; font-weight: 600; }
        .error { color: #ef4444; font-weight: 600; }
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
        .list { margin-left: 20px; line-height: 1.8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Migration Tool</h1>
            <p>customer_id → user_id (mit View-Support)</p>
        </div>
        
        <div class="content">
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
            button.innerHTML = '<span class="spinner"></span>';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=' + action
                });
                
                const text = await res.text();
                if (!text.trim().startsWith('{')) throw new Error('Ungültige Antwort');
                
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
                    let html = '<span class="success">✅ System-Check OK</span><br><br>';
                    html += '<strong>Gefunden:</strong><br>';
                    if (data.old_tables.length > 0) {
                        html += '📋 Alte Tabellen:<br><div class="list">';
                        data.old_tables.forEach(t => html += '• ' + t + '<br>');
                        html += '</div>';
                    }
                    html += '• Alte Spalten: ' + data.old_columns + '<br>';
                    if (data.old_views.length > 0) {
                        html += '👁️ Views mit customer:<br><div class="list">';
                        data.old_views.forEach(v => html += '• ' + v + '<br>');
                        html += '</div>';
                    }
                    
                    r.innerHTML = html;
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
                r.innerHTML = data.success ? '<span class="success">✅ ' + data.file + '</span>' : '<span class="error">❌</span>';
                completeStep(1);
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
                
                let html = data.success ? '<span class="success">✅ Migration abgeschlossen!</span><br><br>' : '<span class="error">⚠️ Mit Problemen</span><br><br>';
                
                if (data.changes && data.changes.length > 0) {
                    html += '<strong>✓ Geändert:</strong><br><div class="list">';
                    data.changes.forEach(c => html += '• ' + c + '<br>');
                    html += '</div><br>';
                }
                
                if (data.skipped && data.skipped.length > 0) {
                    html += '<strong>⊘ Übersprungen:</strong><br><div class="list">';
                    data.skipped.forEach(s => html += '• ' + s + '<br>');
                    html += '</div><br>';
                }
                
                if (data.errors && data.errors.length > 0) {
                    html += '<strong>❌ Fehler:</strong><br><div class="list">';
                    data.errors.forEach(e => html += '• ' + e + '<br>');
                    html += '</div>';
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
                    r.innerHTML = '<span class="success">🎉 MIGRATION ERFOLGREICH!</span><br><br>' +
                                 '<div class="info-box"><strong>✅ Fertig!</strong><br><br>' +
                                 'Lösche jetzt diese Dateien:<br>' +
                                 '• migrate-browser.php<br>' +
                                 '• make-admin.php<br>' +
                                 '• check-session.php</div>';
                    document.getElementById('step3').classList.add('complete');
                } else {
                    let html = '<span class="error">❌ Noch nicht fertig</span><br><br>';
                    if (data.old_tables.length > 0) {
                        html += '📋 Noch vorhandene customer_* Tabellen:<br><div class="list">';
                        data.old_tables.forEach(t => html += '• ' + t + '<br>');
                        html += '</div>';
                    }
                    html += '• Alte Spalten: ' + data.old_columns + '<br>';
                    html += '• Neue Tabellen: ' + data.new_tables;
                    r.innerHTML = html;
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
