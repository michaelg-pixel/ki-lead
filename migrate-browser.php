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

// DEBUG: Session-Variablen anzeigen (nur für Entwicklung)
$debug = false; // Auf true setzen zum Debuggen
if ($debug) {
    echo "<pre>";
    echo "Session-Debug:\n";
    echo "user_id: " . ($_SESSION['user_id'] ?? 'nicht gesetzt') . "\n";
    echo "customer_id: " . ($_SESSION['customer_id'] ?? 'nicht gesetzt') . "\n";
    echo "role: " . ($_SESSION['role'] ?? 'nicht gesetzt') . "\n";
    echo "is_admin: " . ($_SESSION['is_admin'] ?? 'nicht gesetzt') . "\n";
    echo "\nAlle Session-Variablen:\n";
    print_r($_SESSION);
    echo "</pre>";
}

// Flexible Admin-Prüfung: Unterstützt alte UND neue Session-Variablen
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['customer_id']);
$isAdmin = false;

// Prüfe verschiedene Admin-Varianten
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    $isAdmin = true;
} elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $isAdmin = true;
}

if (!$isLoggedIn || !$isAdmin) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>⛔ Zugriff verweigert</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 28px;
                color: #1a1a2e;
                margin-bottom: 15px;
            }
            p {
                color: #6b7280;
                line-height: 1.6;
                margin-bottom: 25px;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .debug-info {
                margin-top: 30px;
                padding: 15px;
                background: #f9fafb;
                border-radius: 8px;
                text-align: left;
                font-size: 12px;
                font-family: 'Courier New', monospace;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">⛔</div>
            <h1>Zugriff verweigert!</h1>
            <p>
                Nur Administratoren dürfen dieses Migrations-Tool verwenden.
                <br><br>
                <strong>Mögliche Gründe:</strong>
            </p>
            <ul style="text-align: left; color: #6b7280; margin-bottom: 25px;">
                <li>Du bist nicht eingeloggt</li>
                <li>Du bist nicht als Administrator angemeldet</li>
                <li>Deine Session ist abgelaufen</li>
            </ul>
            
            <a href="/admin/dashboard.php" class="btn">Zum Admin-Dashboard</a>
            
            <?php if ($debug): ?>
            <div class="debug-info">
                <strong>Debug-Info:</strong><br>
                Logged In: <?php echo $isLoggedIn ? 'Ja' : 'Nein'; ?><br>
                Is Admin: <?php echo $isAdmin ? 'Ja' : 'Nein'; ?><br>
                Session user_id: <?php echo $_SESSION['user_id'] ?? 'nicht gesetzt'; ?><br>
                Session customer_id: <?php echo $_SESSION['customer_id'] ?? 'nicht gesetzt'; ?><br>
                Session role: <?php echo $_SESSION['role'] ?? 'nicht gesetzt'; ?><br>
                Session is_admin: <?php echo isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'true' : 'false') : 'nicht gesetzt'; ?>
            </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
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
    
    $codeBackupSize = 0;
    $backedUpFiles = [];
    
    foreach ($filesToBackup as $file) {
        $fullPath = __DIR__ . '/' . $file;
        if (file_exists($fullPath)) {
            $backupPath = $backupDir . '/code_' . str_replace('/', '_', $file) . '_' . $timestamp . '.backup';
            copy($fullPath, $backupPath);
            $codeBackupSize += filesize($backupPath);
            $backedUpFiles[] = $file;
        }
    }
    
    return [
        'success' => true,
        'message' => 'Backup erfolgreich erstellt',
        'files' => [
            'database' => basename($dbBackupFile),
            'code_files' => $backedUpFiles,
            'size_db' => filesize($dbBackupFile),
            'size_code' => $codeBackupSize
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

?><?php include 'migrate-browser-ui.html'; ?>
