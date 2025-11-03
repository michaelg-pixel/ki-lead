<?php
/**
 * 🔄 MIGRATION TOOL: customer_id → user_id
 * Interaktives Browser-Tool zur sicheren Migration
 * 
 * AUFRUF: https://deine-domain.de/migrate-browser.php
 * WICHTIG: Nach erfolgreicher Migration diese Datei LÖSCHEN!
 */

// Sicherheits-Check: Nur für Admins
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('⛔ Zugriff verweigert! Nur Admins dürfen dieses Script ausführen.');
}

require_once __DIR__ . '/config/database.php';

// AJAX-Requests verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'check_system':
                echo json_encode(checkSystem());
                break;
                
            case 'create_backup':
                echo json_encode(createBackup());
                break;
                
            case 'migrate_database':
                echo json_encode(migrateDatabase());
                break;
                
            case 'migrate_frontend':
                echo json_encode(migrateFrontend());
                break;
                
            case 'verify_migration':
                echo json_encode(verifyMigration());
                break;
                
            case 'rollback':
                echo json_encode(rollback());
                break;
                
            default:
                throw new Exception('Unbekannte Aktion');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

/**
 * System-Check
 */
function checkSystem() {
    $pdo = getDBConnection();
    
    $checks = [];
    
    // 1. Datenbank-Verbindung
    $checks['database'] = [
        'name' => 'Datenbank-Verbindung',
        'status' => $pdo ? 'ok' : 'error',
        'message' => $pdo ? 'Verbindung erfolgreich' : 'Verbindung fehlgeschlagen'
    ];
    
    // 2. Tabellen prüfen
    $tables = $pdo->query("SHOW TABLES LIKE 'customer_%'")->fetchAll(PDO::FETCH_COLUMN);
    $checks['tables'] = [
        'name' => 'Zu migrierende Tabellen',
        'status' => count($tables) > 0 ? 'warning' : 'ok',
        'message' => count($tables) . ' Tabellen gefunden: ' . implode(', ', $tables),
        'count' => count($tables)
    ];
    
    // 3. Spalten prüfen
    $columnsToMigrate = 0;
    $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($allTables as $table) {
        $columns = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetchAll();
        $columnsToMigrate += count($columns);
    }
    $checks['columns'] = [
        'name' => 'Spalten mit customer_id',
        'status' => $columnsToMigrate > 0 ? 'warning' : 'ok',
        'message' => $columnsToMigrate . ' Spalten gefunden',
        'count' => $columnsToMigrate
    ];
    
    // 4. Schreibrechte prüfen
    $testFile = __DIR__ . '/test_write_' . time() . '.tmp';
    $canWrite = @file_put_contents($testFile, 'test') !== false;
    if ($canWrite) @unlink($testFile);
    
    $checks['permissions'] = [
        'name' => 'Schreibrechte',
        'status' => $canWrite ? 'ok' : 'error',
        'message' => $canWrite ? 'Schreibrechte vorhanden' : 'Keine Schreibrechte!'
    ];
    
    // 5. Backup-Verzeichnis
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }
    $checks['backup_dir'] = [
        'name' => 'Backup-Verzeichnis',
        'status' => is_dir($backupDir) && is_writable($backupDir) ? 'ok' : 'error',
        'message' => is_dir($backupDir) ? 'Verzeichnis vorhanden' : 'Verzeichnis fehlt!',
        'path' => $backupDir
    ];
    
    return [
        'success' => true,
        'checks' => $checks,
        'ready' => !in_array('error', array_column($checks, 'status'))
    ];
}

/**
 * Backup erstellen
 */
function createBackup() {
    $pdo = getDBConnection();
    $backupDir = __DIR__ . '/backups';
    $timestamp = date('Y-m-d_H-i-s');
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // 1. Datenbank-Backup
    $dbBackupFile = $backupDir . '/db_backup_' . $timestamp . '.sql';
    
    // Hole alle Tabellen
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $sql = "-- Database Backup: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        // Structure
        $createTable = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $createTable[1] . ";\n\n";
        
        // Data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $vals = [];
                foreach ($row as $val) {
                    $vals[] = $val === null ? 'NULL' : $pdo->quote($val);
                }
                $values[] = '(' . implode(', ', $vals) . ')';
            }
            $sql .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    file_put_contents($dbBackupFile, $sql);
    
    // 2. Code-Backup (wichtige Dateien)
    $filesToBackup = [
        'admin/users.php',
        'customer/dashboard.php',
        'customer/freebies.php',
        'includes/auth.php',
        'includes/ReferralHelper.php'
    ];
    
    $codeBackupFile = $backupDir . '/code_backup_' . $timestamp . '.tar.gz';
    
    $filesExist = [];
    foreach ($filesToBackup as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $filesExist[] = $file;
        }
    }
    
    if (!empty($filesExist)) {
        $tar = new PharData($codeBackupFile);
        foreach ($filesExist as $file) {
            $tar->addFile(__DIR__ . '/' . $file, $file);
        }
    }
    
    return [
        'success' => true,
        'message' => 'Backup erfolgreich erstellt',
        'files' => [
            'database' => basename($dbBackupFile),
            'code' => basename($codeBackupFile),
            'size_db' => filesize($dbBackupFile),
            'size_code' => file_exists($codeBackupFile) ? filesize($codeBackupFile) : 0
        ],
        'timestamp' => $timestamp
    ];
}

/**
 * Datenbank migrieren
 */
function migrateDatabase() {
    $pdo = getDBConnection();
    $changes = [];
    
    try {
        $pdo->beginTransaction();
        
        // Foreign Key Checks deaktivieren
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        
        // 1. Tabellen umbenennen
        $tablesToRename = [
            'customer_freebies' => 'user_freebies',
            'customer_freebie_limits' => 'user_freebie_limits',
            'customer_courses' => 'user_courses',
            'customer_progress' => 'user_progress',
            'customer_tutorials' => 'user_tutorials'
        ];
        
        foreach ($tablesToRename as $old => $new) {
            $exists = $pdo->query("SHOW TABLES LIKE '$old'")->fetch();
            if ($exists) {
                $pdo->exec("RENAME TABLE `$old` TO `$new`");
                $changes[] = "Tabelle umbenannt: $old → $new";
            }
        }
        
        // 2. Spalten in allen Tabellen umbenennen
        $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($allTables as $table) {
            $columns = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetchAll();
            
            if (!empty($columns)) {
                foreach ($columns as $col) {
                    $columnInfo = $pdo->query("SHOW COLUMNS FROM `$table` WHERE Field = 'customer_id'")->fetch();
                    
                    $type = $columnInfo['Type'];
                    $null = $columnInfo['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
                    $default = $columnInfo['Default'] !== null ? "DEFAULT '{$columnInfo['Default']}'" : '';
                    
                    $pdo->exec("ALTER TABLE `$table` CHANGE COLUMN `customer_id` `user_id` $type $null $default");
                    $changes[] = "Spalte umbenannt in $table: customer_id → user_id";
                }
            }
        }
        
        // Foreign Key Checks aktivieren
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Datenbank erfolgreich migriert',
            'changes' => $changes,
            'count' => count($changes)
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Frontend migrieren
 */
function migrateFrontend() {
    $changes = [];
    $filesToProcess = [];
    
    // Finde alle PHP-Dateien
    $directories = ['admin', 'customer', 'includes', 'api'];
    
    foreach ($directories as $dir) {
        if (is_dir(__DIR__ . '/' . $dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(__DIR__ . '/' . $dir)
            );
            
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $filesToProcess[] = $file->getPathname();
                }
            }
        }
    }
    
    // Verarbeite jede Datei
    foreach ($filesToProcess as $file) {
        $content = file_get_contents($file);
        $originalContent = $content;
        $fileChanges = 0;
        
        // Ersetzungen
        $replacements = [
            "/\\\$_SESSION\['customer_id'\]/" => "\$_SESSION['user_id']",
            "/\\\$customer_id([^a-zA-Z_])/i" => "\$userId$1",
            "/customer_id/" => "user_id",
            "/customerId/" => "userId",
            "/get-customer-details/" => "get-user-details",
            "/customer_freebies/" => "user_freebies",
            "/customer_freebie_limits/" => "user_freebie_limits",
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content) {
                $fileChanges++;
                $content = $newContent;
            }
        }
        
        if ($content !== $originalContent) {
            // Backup erstellen
            copy($file, $file . '.backup');
            
            // Datei aktualisieren
            file_put_contents($file, $content);
            
            $changes[] = basename($file) . " ($fileChanges Änderungen)";
        }
    }
    
    return [
        'success' => true,
        'message' => 'Frontend erfolgreich migriert',
        'changes' => $changes,
        'count' => count($changes)
    ];
}

/**
 * Migration verifizieren
 */
function verifyMigration() {
    $pdo = getDBConnection();
    $issues = [];
    $success = [];
    
    // 1. Prüfe ob alte Tabellen noch existieren
    $oldTables = ['customer_freebies', 'customer_freebie_limits', 'customer_courses'];
    foreach ($oldTables as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($exists) {
            $issues[] = "Alte Tabelle existiert noch: $table";
        } else {
            $success[] = "Tabelle erfolgreich umbenannt: $table";
        }
    }
    
    // 2. Prüfe neue Tabellen
    $newTables = ['user_freebies', 'user_freebie_limits', 'user_courses'];
    foreach ($newTables as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($exists) {
            $success[] = "Neue Tabelle existiert: $table";
        } else {
            $issues[] = "Neue Tabelle fehlt: $table";
        }
    }
    
    // 3. Prüfe Spalten
    $allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $customerIdFound = 0;
    
    foreach ($allTables as $table) {
        $columns = $pdo->query("SHOW COLUMNS FROM `$table` LIKE 'customer_id'")->fetchAll();
        $customerIdFound += count($columns);
    }
    
    if ($customerIdFound > 0) {
        $issues[] = "$customerIdFound Spalten mit 'customer_id' gefunden";
    } else {
        $success[] = "Alle customer_id Spalten erfolgreich umbenannt";
    }
    
    return [
        'success' => count($issues) === 0,
        'issues' => $issues,
        'success_items' => $success,
        'ready' => count($issues) === 0
    ];
}

/**
 * Rollback durchführen
 */
function rollback() {
    $backupDir = __DIR__ . '/backups';
    
    // Finde neuestes Backup
    $files = glob($backupDir . '/db_backup_*.sql');
    if (empty($files)) {
        throw new Exception('Kein Backup gefunden!');
    }
    
    rsort($files);
    $latestBackup = $files[0];
    
    // Restore durchführen
    $pdo = getDBConnection();
    $sql = file_get_contents($latestBackup);
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Code-Backups wiederherstellen
    $backupFiles = glob(__DIR__ . '/**/*.backup');
    foreach ($backupFiles as $backup) {
        $original = str_replace('.backup', '', $backup);
        copy($backup, $original);
    }
    
    return [
        'success' => true,
        'message' => 'Rollback erfolgreich',
        'backup_file' => basename($latestBackup)
    ];
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔄 Migration Tool: customer_id → user_id</title>
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
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 40px;
        }
        
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning ul {
            margin-left: 20px;
            color: #856404;
        }
        
        .step {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid #dee2e6;
        }
        
        .step.active {
            border-left-color: #667eea;
            background: #f0f4ff;
        }
        
        .step.complete {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        
        .step.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #dee2e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        
        .step.active .step-number {
            background: #667eea;
        }
        
        .step.complete .step-number {
            background: #10b981;
        }
        
        .step.error .step-number {
            background: #ef4444;
        }
        
        .step-title {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a2e;
        }
        
        .step-description {
            color: #6b7280;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .step-status {
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .step-status.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .step-status.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .step-status.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .checks-list {
            list-style: none;
            margin: 15px 0;
        }
        
        .checks-list li {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checks-list li.ok {
            background: #d1fae5;
            color: #065f46;
        }
        
        .checks-list li.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .checks-list li.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s;
        }
        
        .changes-list {
            background: #f9fafb;
            border-radius: 8px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        
        .changes-list div {
            padding: 4px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .changes-list div:last-child {
            border-bottom: none;
        }
        
        .footer {
            padding: 20px 40px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 Migration Tool</h1>
            <p>customer_id → user_id Migration</p>
        </div>
        
        <div class="content">
            <div class="warning">
                <h3>⚠️ WICHTIGE HINWEISE</h3>
                <ul>
                    <li>Diese Migration ändert die Datenbank-Struktur!</li>
                    <li>Ein Backup wird automatisch erstellt</li>
                    <li>Die Migration kann einige Minuten dauern</li>
                    <li>Schließe den Browser NICHT während der Migration</li>
                    <li>Nach erfolgreicher Migration diese Datei LÖSCHEN!</li>
                </ul>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar" style="width: 0%"></div>
            </div>
            
            <!-- Schritt 1: System-Check -->
            <div class="step" id="step1">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <div class="step-title">System-Check</div>
                </div>
                <div class="step-description">
                    Überprüfung der System-Voraussetzungen
                </div>
                <div id="step1-content"></div>
                <button class="btn btn-primary" onclick="runStep1()">
                    System prüfen
                </button>
            </div>
            
            <!-- Schritt 2: Backup -->
            <div class="step" id="step2">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <div class="step-title">Backup erstellen</div>
                </div>
                <div class="step-description">
                    Sicherung von Datenbank und Code
                </div>
                <div id="step2-content"></div>
                <button class="btn btn-primary" onclick="runStep2()" disabled id="step2-btn">
                    Backup erstellen
                </button>
            </div>
            
            <!-- Schritt 3: Datenbank -->
            <div class="step" id="step3">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <div class="step-title">Datenbank migrieren</div>
                </div>
                <div class="step-description">
                    Umbenennung von Tabellen und Spalten
                </div>
                <div id="step3-content"></div>
                <button class="btn btn-primary" onclick="runStep3()" disabled id="step3-btn">
                    Datenbank migrieren
                </button>
            </div>
            
            <!-- Schritt 4: Frontend -->
            <div class="step" id="step4">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <div class="step-title">Frontend migrieren</div>
                </div>
                <div class="step-description">
                    Aktualisierung von PHP und JavaScript
                </div>
                <div id="step4-content"></div>
                <button class="btn btn-primary" onclick="runStep4()" disabled id="step4-btn">
                    Frontend migrieren
                </button>
            </div>
            
            <!-- Schritt 5: Verifizierung -->
            <div class="step" id="step5">
                <div class="step-header">
                    <div class="step-number">5</div>
                    <div class="step-title">Verifizierung</div>
                </div>
                <div class="step-description">
                    Überprüfung der Migration
                </div>
                <div id="step5-content"></div>
                <button class="btn btn-primary" onclick="runStep5()" disabled id="step5-btn">
                    Migration verifizieren
                </button>
            </div>
            
            <!-- Rollback -->
            <div class="step" id="step-rollback" style="display: none;">
                <div class="step-header">
                    <div class="step-number">⏪</div>
                    <div class="step-title">Rollback</div>
                </div>
                <div class="step-description">
                    Migration rückgängig machen
                </div>
                <button class="btn btn-danger" onclick="runRollback()">
                    Rollback durchführen
                </button>
            </div>
        </div>
        
        <div class="footer">
            💡 Tipp: Nach erfolgreicher Migration diese Datei löschen!
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        let completedSteps = [];
        
        function updateProgress() {
            const progress = (completedSteps.length / 5) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }
        
        function setStepActive(stepNum) {
            document.getElementById('step' + stepNum).classList.add('active');
        }
        
        function setStepComplete(stepNum) {
            const step = document.getElementById('step' + stepNum);
            step.classList.remove('active');
            step.classList.add('complete');
            completedSteps.push(stepNum);
            updateProgress();
            
            // Nächsten Schritt aktivieren
            if (stepNum < 5) {
                const nextBtn = document.getElementById('step' + (stepNum + 1) + '-btn');
                if (nextBtn) nextBtn.disabled = false;
                setStepActive(stepNum + 1);
            }
            
            // Rollback-Option anzeigen
            if (stepNum >= 3) {
                document.getElementById('step-rollback').style.display = 'block';
            }
        }
        
        function setStepError(stepNum, message) {
            const step = document.getElementById('step' + stepNum);
            step.classList.remove('active');
            step.classList.add('error');
            
            const content = document.getElementById('step' + stepNum + '-content');
            content.innerHTML = '<div class="step-status error">❌ ' + message + '</div>';
        }
        
        function showLoading(stepNum, button) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Wird ausgeführt...';
        }
        
        function hideLoading(stepNum, button, text) {
            button.disabled = false;
            button.innerHTML = text;
        }
        
        async function runStep1() {
            const button = document.querySelector('#step1 .btn');
            showLoading(1, button);
            setStepActive(1);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=check_system'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<ul class="checks-list">';
                    for (const [key, check] of Object.entries(data.checks)) {
                        html += `<li class="${check.status}">`;
                        html += check.status === 'ok' ? '✅' : (check.status === 'warning' ? '⚠️' : '❌');
                        html += ` <strong>${check.name}:</strong> ${check.message}</li>`;
                    }
                    html += '</ul>';
                    
                    document.getElementById('step1-content').innerHTML = html;
                    
                    if (data.ready) {
                        setStepComplete(1);
                    } else {
                        setStepError(1, 'System nicht bereit für Migration!');
                    }
                } else {
                    setStepError(1, data.error);
                }
            } catch (error) {
                setStepError(1, 'Fehler: ' + error.message);
            }
            
            hideLoading(1, button, 'System prüfen');
        }
        
        async function runStep2() {
            const button = document.querySelector('#step2 .btn');
            showLoading(2, button);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create_backup'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="step-status success">✅ ' + data.message + '</div>';
                    html += '<div style="font-size: 13px; color: #6b7280;">';
                    html += '📁 Datenbank: ' + data.files.database + ' (' + Math.round(data.files.size_db / 1024) + ' KB)<br>';
                    html += '📁 Code: ' + data.files.code + ' (' + Math.round(data.files.size_code / 1024) + ' KB)';
                    html += '</div>';
                    
                    document.getElementById('step2-content').innerHTML = html;
                    setStepComplete(2);
                } else {
                    setStepError(2, data.error);
                }
            } catch (error) {
                setStepError(2, 'Fehler: ' + error.message);
            }
            
            hideLoading(2, button, 'Backup erstellen');
        }
        
        async function runStep3() {
            const button = document.querySelector('#step3 .btn');
            showLoading(3, button);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=migrate_database'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="step-status success">✅ ' + data.message + '</div>';
                    html += '<div class="changes-list">';
                    data.changes.forEach(change => {
                        html += '<div>✓ ' + change + '</div>';
                    });
                    html += '</div>';
                    
                    document.getElementById('step3-content').innerHTML = html;
                    setStepComplete(3);
                } else {
                    setStepError(3, data.error);
                }
            } catch (error) {
                setStepError(3, 'Fehler: ' + error.message);
            }
            
            hideLoading(3, button, 'Datenbank migrieren');
        }
        
        async function runStep4() {
            const button = document.querySelector('#step4 .btn');
            showLoading(4, button);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=migrate_frontend'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    let html = '<div class="step-status success">✅ ' + data.message + '</div>';
                    html += '<div class="changes-list">';
                    data.changes.forEach(change => {
                        html += '<div>✓ ' + change + '</div>';
                    });
                    html += '</div>';
                    
                    document.getElementById('step4-content').innerHTML = html;
                    setStepComplete(4);
                } else {
                    setStepError(4, data.error);
                }
            } catch (error) {
                setStepError(4, 'Fehler: ' + error.message);
            }
            
            hideLoading(4, button, 'Frontend migrieren');
        }
        
        async function runStep5() {
            const button = document.querySelector('#step5 .btn');
            showLoading(5, button);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=verify_migration'
                });
                
                const data = await response.json();
                
                let html = '';
                
                if (data.success) {
                    html += '<div class="step-status success">🎉 Migration erfolgreich abgeschlossen!</div>';
                    html += '<ul class="checks-list">';
                    data.success_items.forEach(item => {
                        html += '<li class="ok">✅ ' + item + '</li>';
                    });
                    html += '</ul>';
                    html += '<div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 8px; color: #92400e;">';
                    html += '⚠️ <strong>WICHTIG:</strong> Lösche jetzt diese Datei (migrate-browser.php) aus Sicherheitsgründen!';
                    html += '</div>';
                    
                    setStepComplete(5);
                } else {
                    html += '<div class="step-status error">❌ Probleme gefunden</div>';
                    html += '<ul class="checks-list">';
                    data.issues.forEach(issue => {
                        html += '<li class="error">❌ ' + issue + '</li>';
                    });
                    html += '</ul>';
                    setStepError(5, 'Migration nicht vollständig');
                }
                
                document.getElementById('step5-content').innerHTML = html;
                
            } catch (error) {
                setStepError(5, 'Fehler: ' + error.message);
            }
            
            hideLoading(5, button, 'Migration verifizieren');
        }
        
        async function runRollback() {
            if (!confirm('⚠️ WARNUNG: Möchtest du wirklich ein Rollback durchführen? Dies macht alle Änderungen rückgängig!')) {
                return;
            }
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=rollback'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Rollback erfolgreich durchgeführt!');
                    location.reload();
                } else {
                    alert('❌ Rollback fehlgeschlagen: ' + data.error);
                }
            } catch (error) {
                alert('❌ Fehler beim Rollback: ' + error.message);
            }
        }
        
        // Auto-Start System-Check
        window.onload = function() {
            runStep1();
        };
    </script>
</body>
</html>
