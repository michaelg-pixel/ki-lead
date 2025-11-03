<?php
/**
 * 🔄 MIGRATION TOOL: customer_id → user_id
 * Optimierte Version mit Fehlerbehandlung
 */

// Error Reporting für AJAX deaktivieren
error_reporting(0);
ini_set('display_errors', 0);

// Timeout erhöhen
set_time_limit(300);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '512M');

session_start();

// Admin-Check
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
           (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

if (!$isAdmin && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('⛔ Nur Admins! <a href="/make-admin.php?token=migration2024secure">Admin werden</a> | <a href="/check-session.php">Session prüfen</a>');
}

// AJAX Handler - MUSS VOR allem HTML kommen!
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Output-Buffering starten
    ob_start();
    
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        require_once __DIR__ . '/config/database.php';
        $pdo = getDBConnection();
        
        if (!$pdo) {
            throw new Exception('Datenbankverbindung fehlgeschlagen');
        }
        
        switch ($action) {
            case 'backup':
                $dir = __DIR__ . '/backups';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                $file = $dir . '/backup_' . date('YmdHis') . '.sql';
                
                // Structure-Only Backup
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                
                $sql = "-- STRUCTURE BACKUP\n";
                $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
                $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
                
                foreach ($tables as $table) {
                    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                    $sql .= $create[1] . ";\n\n";
                }
                
                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
                
                file_put_contents($file, $sql);
                $size = filesize($file);
                
                // Buffer leeren
                ob_clean();
                
                echo json_encode([
                    'success' => true, 
                    'file' => basename($file),
                    'size' => round($size / 1024, 2) . ' KB',
                    'tables' => count($tables)
                ]);
                break;
                
            case 'check':
                $oldTables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
                $oldColumns = 0;
                
                // Prüfe Spalten in wichtigen Tabellen
                $checkTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($checkTables as $table) {
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {
                        // Tabelle überspringen wenn Fehler
                        continue;
                    }
                }
                
                // Buffer leeren
                ob_clean();
                
                echo json_encode([
                    'success' => true,
                    'old_tables' => count($oldTables),
                    'old_columns' => $oldColumns,
                    'needs_migration' => (count($oldTables) > 0 || $oldColumns > 0)
                ]);
                break;
                
            case 'migrate':
                $pdo->beginTransaction();
                
                try {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                    $changes = [];
                    
                    // Tabellen umbenennen
                    $rename = [
                        'customer_freebies' => 'user_freebies',
                        'customer_freebie_limits' => 'user_freebie_limits',
                        'customer_courses' => 'user_courses',
                        'customer_progress' => 'user_progress'
                    ];
                    
                    foreach ($rename as $old => $new) {
                        if ($pdo->query("SHOW TABLES LIKE '$old'")->fetch()) {
                            $pdo->exec("RENAME TABLE `$old` TO `$new`");
                            $changes[] = "Tabelle: $old → $new";
                        }
                    }
                    
                    // Spalten umbenennen
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($tables as $table) {
                        try {
                            if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                                $col = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Field = 'customer_id'")->fetch();
                                $type = $col['Type'];
                                $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                                $pdo->exec("ALTER TABLE `$table` CHANGE COLUMN customer_id user_id $type $null");
                                $changes[] = "Spalte: $table.customer_id → user_id";
                            }
                        } catch (Exception $e) {
                            // Tabelle überspringen
                            continue;
                        }
                    }
                    
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                    $pdo->commit();
                    
                    // Buffer leeren
                    ob_clean();
                    
                    echo json_encode([
                        'success' => true, 
                        'changes' => $changes, 
                        'count' => count($changes)
                    ]);
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
                break;
                
            case 'verify':
                $oldTables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
                $oldColumns = 0;
                $newTables = 0;
                
                // Prüfe neue Tabellen
                $checkNew = ['user_freebies', 'user_freebie_limits'];
                foreach ($checkNew as $table) {
                    if ($pdo->query("SHOW TABLES LIKE '$table'")->fetch()) {
                        $newTables++;
                    }
                }
                
                // Prüfe alte Spalten
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    try {
                        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetch()) {
                            $oldColumns++;
                        }
                    } catch (Exception $e) {
                        continue;
                    }
                }
                
                $success = (count($oldTables) === 0 && $oldColumns === 0 && $newTables > 0);
                
                // Buffer leeren
                ob_clean();
                
                echo json_encode([
                    'success' => $success,
                    'old_tables' => count($oldTables),
                    'old_columns' => $oldColumns,
                    'new_tables' => $newTables
                ]);
                break;
                
            default:
                throw new Exception('Unbekannte Aktion: ' . $action);
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        // Buffer leeren
        ob_clean();
        
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Buffer ausgeben und beenden
    ob_end_flush();
    exit;
}

// Ab hier kommt die HTML-UI (nur wenn kein POST-Request!)
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
            transition: all 0.3s;
        }
        .step.active { border-left-color: #667eea; background: #f0f4ff; }
        .step.complete { border-left-color: #10b981; background: #f0fdf4; }
        .step h3 { margin-bottom: 15px; color: #1a1a2e; }
        .step p { color: #6b7280; margin-bottom: 15px; }
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
            transition: all 0.2s;
        }
        .btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-secondary { background: #f1f5f9; color: #64748b; }
        .result {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }
        .success { color: #10b981; font-weight: 600; }
        .error { color: #ef4444; font-weight: 600; }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            color: #856404;
        }
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .info-box {
            background: #e0e7ff;
            border: 2px solid #6366f1;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-size: 14px;
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
                    <li>Backup wird automatisch erstellt (nur Struktur)</li>
                    <li>Migration dauert ca. 1-2 Minuten</li>
                    <li>Browser NICHT schließen!</li>
                    <li>Nach Migration: Dateien löschen!</li>
                </ul>
            </div>
            
            <div class="step active" id="step0">
                <h3>0️⃣ System-Check</h3>
                <p>Prüfe was migriert werden muss</p>
                <button class="btn" onclick="runCheck()">System prüfen</button>
                <div id="result0" class="result"></div>
            </div>
            
            <div class="step" id="step1">
                <h3>1️⃣ Backup erstellen</h3>
                <p>Schnelles Structure-Backup</p>
                <button class="btn" onclick="runBackup()" disabled id="btn1">Backup erstellen</button>
                <button class="btn btn-secondary" onclick="skipBackup()">Überspringen</button>
                <div id="result1" class="result"></div>
            </div>
            
            <div class="step" id="step2">
                <h3>2️⃣ Migration durchführen</h3>
                <p>Tabellen und Spalten umbenennen</p>
                <button class="btn" onclick="runMigration()" disabled id="btn2">Migration starten</button>
                <div id="result2" class="result"></div>
            </div>
            
            <div class="step" id="step3">
                <h3>3️⃣ Verifizierung</h3>
                <p>Migration prüfen</p>
                <button class="btn" onclick="runVerify()" disabled id="btn3">Prüfen</button>
                <div id="result3" class="result"></div>
            </div>
        </div>
    </div>
    
    <script>
        async function callApi(action, button) {
            const originalText = button.textContent;
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> ' + originalText.split(' ')[0] + '...';
            
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=' + action
                });
                
                const text = await res.text();
                
                // Debug: Zeige rohe Antwort bei Fehler
                if (!text.trim().startsWith('{')) {
                    console.error('Server Response:', text);
                    throw new Error('Server gab HTML statt JSON zurück. Siehe Console.');
                }
                
                const data = JSON.parse(text);
                button.textContent = originalText;
                return data;
                
            } catch (error) {
                button.textContent = originalText;
                throw error;
            }
        }
        
        async function runCheck() {
            try {
                const data = await callApi('check', event.target);
                const result = document.getElementById('result0');
                result.style.display = 'block';
                
                if (data.success) {
                    let html = '<span class="success">✅ System-Check abgeschlossen</span><br><br>';
                    html += '📊 Gefunden:<br>';
                    html += '• Alte Tabellen: ' + data.old_tables + '<br>';
                    html += '• Alte Spalten: ' + data.old_columns + '<br><br>';
                    
                    if (data.needs_migration) {
                        html += '<span style="color: #f59e0b;">⚠️ Migration erforderlich!</span>';
                        document.getElementById('btn1').disabled = false;
                        document.getElementById('step1').classList.add('active');
                    } else {
                        html += '<span class="success">✅ Keine Migration nötig!</span>';
                    }
                    
                    result.innerHTML = html;
                    document.getElementById('step0').classList.add('complete');
                }
            } catch (error) {
                const result = document.getElementById('result0');
                result.innerHTML = '<span class="error">❌ ' + error.message + '</span>';
                result.style.display = 'block';
            }
        }
        
        async function runBackup() {
            try {
                const data = await callApi('backup', event.target);
                const result = document.getElementById('result1');
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">✅ Backup erstellt!</span><br><br>' +
                                     '📁 Datei: ' + data.file + '<br>' +
                                     '📊 Größe: ' + data.size + '<br>' +
                                     '📋 Tabellen: ' + data.tables;
                    completeStep(1);
                } else {
                    result.innerHTML = '<span class="error">❌ ' + data.error + '</span>';
                }
            } catch (error) {
                const result = document.getElementById('result1');
                result.innerHTML = '<span class="error">❌ ' + error.message + '</span>';
                result.style.display = 'block';
            }
        }
        
        function skipBackup() {
            if (confirm('⚠️ Backup überspringen? NICHT empfohlen!')) {
                document.getElementById('result1').innerHTML = '<span style="color: #f59e0b;">⚠️ Übersprungen</span>';
                document.getElementById('result1').style.display = 'block';
                completeStep(1);
            }
        }
        
        async function runMigration() {
            if (!confirm('⚠️ Migration starten?\n\nDatenbank wird geändert!')) return;
            
            try {
                const data = await callApi('migrate', document.getElementById('btn2'));
                const result = document.getElementById('result2');
                result.style.display = 'block';
                
                if (data.success) {
                    let html = '<span class="success">✅ Migration erfolgreich!</span><br><br>';
                    html += '📊 Änderungen: ' + data.count + '<br><br>';
                    data.changes.forEach(c => html += '✓ ' + c + '<br>');
                    result.innerHTML = html;
                    completeStep(2);
                } else {
                    result.innerHTML = '<span class="error">❌ ' + data.error + '</span>';
                }
            } catch (error) {
                const result = document.getElementById('result2');
                result.innerHTML = '<span class="error">❌ ' + error.message + '</span>';
                result.style.display = 'block';
            }
        }
        
        async function runVerify() {
            try {
                const data = await callApi('verify', document.getElementById('btn3'));
                const result = document.getElementById('result3');
                result.style.display = 'block';
                
                if (data.success) {
                    result.innerHTML = '<span class="success">🎉 MIGRATION ERFOLGREICH!</span><br><br>' +
                                     '📊 Ergebnis:<br>' +
                                     '• Alte Tabellen: ' + data.old_tables + '<br>' +
                                     '• Alte Spalten: ' + data.old_columns + '<br>' +
                                     '• Neue Tabellen: ' + data.new_tables + '<br><br>' +
                                     '<div class="info-box">' +
                                     '<strong>⚠️ WICHTIG:</strong><br><br>' +
                                     '1. Teste alle Funktionen<br>' +
                                     '2. Lösche: migrate-browser.php, make-admin.php, check-session.php<br>' +
                                     '3. Logout & Login<br>' +
                                     '</div>';
                    document.getElementById('step3').classList.add('complete');
                } else {
                    result.innerHTML = '<span class="error">❌ Probleme:</span><br>' +
                                     '• Alte Tabellen: ' + data.old_tables + '<br>' +
                                     '• Alte Spalten: ' + data.old_columns;
                }
            } catch (error) {
                const result = document.getElementById('result3');
                result.innerHTML = '<span class="error">❌ ' + error.message + '</span>';
                result.style.display = 'block';
            }
        }
        
        function completeStep(step) {
            document.getElementById('step' + step).classList.remove('active');
            document.getElementById('step' + step).classList.add('complete');
            const nextStep = step + 1;
            document.getElementById('btn' + nextStep).disabled = false;
            document.getElementById('step' + nextStep).classList.add('active');
        }
        
        // Auto-Start
        window.onload = () => {
            setTimeout(() => {
                document.querySelector('#step0 .btn').click();
            }, 500);
        };
    </script>
</body>
</html>
