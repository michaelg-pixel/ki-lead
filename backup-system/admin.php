<?php
/**
 * Backup Admin Interface - ENHANCED mit Security & One-Click-Restore
 * Separate Administrationsoberfläche für Backup-Verwaltung
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/restore.php';

// Security Layer initialisieren
$security = new BackupSecurity();
$security->checkRateLimit();

// Login-Check
if (!isset($_SESSION['backup_admin_logged_in'])) {
    // Login-Formular
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $success = ($username === BACKUP_ADMIN_USER && password_verify($password, BACKUP_ADMIN_PASS));
        $security->checkLoginAttempt($username, $success);
        
        if ($success) {
            $_SESSION['backup_admin_logged_in'] = true;
            $_SESSION['backup_admin_user'] = $username;
            $_SESSION['csrf_token'] = BackupSecurity::generateCSRFToken();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = "Ungültige Zugangsdaten!";
        }
    }
    
    // Login-Seite anzeigen
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Backup System - Login</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                width: 100%;
                max-width: 400px;
            }
            .login-box h1 {
                text-align: center;
                margin-bottom: 30px;
                color: #333;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                margin-bottom: 5px;
                color: #555;
                font-weight: 500;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #e0e0e0;
                border-radius: 5px;
                font-size: 14px;
                transition: border-color 0.3s;
            }
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            .btn-login:hover {
                transform: translateY(-2px);
            }
            .error {
                background: #fee;
                color: #c00;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔐 Backup System</h1>
            
            <?php if (isset($loginError)): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Benutzername</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Passwort</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn-login">Anmelden</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// CSRF-Token für alle POST-Requests validieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!BackupSecurity::validateCSRFToken($token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
    }
}

// API-Endpunkte
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'list_backups':
            echo json_encode(getBackupsList());
            break;
            
        case 'create_backup':
            $type = BackupSecurity::sanitizeInput($_POST['type'] ?? 'full');
            $engine = new BackupEngine();
            $success = $engine->execute($type);
            echo json_encode(['success' => $success]);
            break;
            
        case 'restore_backup':
            $file = BackupSecurity::sanitizeInput($_POST['file'] ?? '');
            $type = BackupSecurity::sanitizeInput($_POST['type'] ?? '');
            $restore = new RestoreEngine();
            
            if ($type === 'database') {
                $result = $restore->restoreDatabase($file);
            } else {
                $result = $restore->restoreFiles($file);
            }
            
            echo json_encode($result);
            break;
            
        case 'rollback':
            $file = BackupSecurity::sanitizeInput($_POST['file'] ?? '');
            $restore = new RestoreEngine();
            $result = $restore->rollback($file);
            echo json_encode($result);
            break;
            
        case 'list_rollbacks':
            $restore = new RestoreEngine();
            echo json_encode($restore->listRollbacks());
            break;
            
        case 'delete_backup':
            $file = BackupSecurity::sanitizeInput($_POST['file'] ?? '');
            $success = deleteBackup($file);
            echo json_encode(['success' => $success]);
            break;
            
        case 'download_backup':
            $file = BackupSecurity::sanitizeInput($_GET['file'] ?? '');
            downloadBackup($file);
            break;
            
        case 'get_logs':
            echo json_encode(getLogs());
            break;
            
        case 'get_stats':
            echo json_encode(getStats());
            break;
            
        case 'emergency_restore':
            // Notfall-Wiederherstellung (neuestes Backup)
            $backups = getBackupsList();
            $restore = new RestoreEngine();
            
            $results = [];
            
            // Neuestes DB-Backup
            if (!empty($backups['database'])) {
                $latestDB = $backups['database'][0]['filename'];
                $results['database'] = $restore->restoreDatabase($latestDB);
            }
            
            // Neueste Dateien (optional, da zeitintensiv)
            if (isset($_POST['include_files']) && $_POST['include_files'] === 'true') {
                if (!empty($backups['files'])) {
                    $latestFiles = $backups['files'][0]['filename'];
                    $results['files'] = $restore->restoreFiles($latestFiles);
                }
            }
            
            echo json_encode($results);
            break;
    }
    exit;
}

// Hilfsfunktionen
function getBackupsList() {
    $backups = ['database' => [], 'files' => []];
    
    // Datenbank-Backups
    $dbFiles = glob(BACKUP_DB_DIR . '/*');
    rsort($dbFiles);
    foreach ($dbFiles as $file) {
        $backups['database'][] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'size_formatted' => formatBytes(filesize($file)),
            'created' => filemtime($file),
            'created_formatted' => date('d.m.Y H:i', filemtime($file))
        ];
    }
    
    // Datei-Backups
    $fileBackups = glob(BACKUP_FILES_DIR . '/*');
    rsort($fileBackups);
    foreach ($fileBackups as $file) {
        $backups['files'][] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'size_formatted' => formatBytes(filesize($file)),
            'created' => filemtime($file),
            'created_formatted' => date('d.m.Y H:i', filemtime($file))
        ];
    }
    
    return $backups;
}

function deleteBackup($filename) {
    // Path Traversal verhindern
    $filename = basename($filename);
    
    $file = BACKUP_DB_DIR . '/' . $filename;
    if (!file_exists($file)) {
        $file = BACKUP_FILES_DIR . '/' . $filename;
    }
    
    if (file_exists($file) && BackupSecurity::validatePath($file, BACKUP_ROOT_DIR)) {
        return unlink($file);
    }
    return false;
}

function downloadBackup($filename) {
    // Path Traversal verhindern
    $filename = basename($filename);
    
    $file = BACKUP_DB_DIR . '/' . $filename;
    if (!file_exists($file)) {
        $file = BACKUP_FILES_DIR . '/' . $filename;
    }
    
    if (file_exists($file) && BackupSecurity::validatePath($file, BACKUP_ROOT_DIR)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

function getLogs() {
    $logs = [];
    $logFiles = glob(BACKUP_LOGS_DIR . '/*.log');
    rsort($logFiles);
    
    foreach (array_slice($logFiles, 0, 10) as $file) {
        $logs[] = [
            'date' => basename($file, '.log'),
            'content' => file_get_contents($file)
        ];
    }
    
    return $logs;
}

function getStats() {
    $stats = [
        'total_backups' => 0,
        'total_size' => 0,
        'last_backup' => null,
        'disk_usage' => [
            'used' => disk_total_space(BACKUP_ROOT_DIR) - disk_free_space(BACKUP_ROOT_DIR),
            'free' => disk_free_space(BACKUP_ROOT_DIR),
            'total' => disk_total_space(BACKUP_ROOT_DIR)
        ]
    ];
    
    $allFiles = array_merge(
        glob(BACKUP_DB_DIR . '/*'),
        glob(BACKUP_FILES_DIR . '/*')
    );
    
    $stats['total_backups'] = count($allFiles);
    
    foreach ($allFiles as $file) {
        $stats['total_size'] += filesize($file);
    }
    
    if (!empty($allFiles)) {
        usort($allFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $stats['last_backup'] = filemtime($allFiles[0]);
    }
    
    return $stats;
}

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

$csrfToken = BackupSecurity::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup System - Administration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        
        /* Emergency Button */
        .emergency-section {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .emergency-section h2 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-emergency {
            background: #dc3545;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-emergency:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        
        .actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .actions h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .backup-list {
            list-style: none;
        }
        
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            transition: background 0.2s;
        }
        
        .backup-item:hover {
            background: #f8f9fa;
        }
        
        .backup-info {
            flex: 1;
        }
        
        .backup-info .name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .backup-info .meta {
            font-size: 13px;
            color: #666;
        }
        
        .backup-actions {
            display: flex;
            gap: 10px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-content h3 {
            margin-bottom: 15px;
        }
        
        .modal-content p {
            margin-bottom: 20px;
            color: #666;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🔐 Backup System Administration</h1>
            <a href="?logout" class="logout-btn">Abmelden</a>
        </div>
    </div>
    
    <div class="container">
        <!-- Notfall-Wiederherstellung -->
        <div class="emergency-section">
            <h2>
                🚨 Notfall-Wiederherstellung
            </h2>
            <p style="margin-bottom: 15px; color: #856404;">
                Bei einem Angriff oder System-Crash: Stelle mit einem Klick das neueste Backup wieder her.
                <strong>Das System erstellt automatisch einen Rollback-Punkt!</strong>
            </p>
            <button class="btn-emergency" onclick="emergencyRestore()">
                🚨 NOTFALL-WIEDERHERSTELLUNG STARTEN
            </button>
        </div>
        
        <!-- Statistiken -->
        <div class="stats-grid" id="stats">
            <div class="stat-card">
                <h3>Gesamt Backups</h3>
                <div class="value" id="stat-total">-</div>
            </div>
            <div class="stat-card">
                <h3>Gesamtgröße</h3>
                <div class="value" id="stat-size">-</div>
            </div>
            <div class="stat-card">
                <h3>Letztes Backup</h3>
                <div class="value" id="stat-last">-</div>
            </div>
            <div class="stat-card">
                <h3>Speicherplatz frei</h3>
                <div class="value" id="stat-disk">-</div>
            </div>
        </div>
        
        <!-- Aktionen -->
        <div class="actions">
            <h2>Backup erstellen</h2>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="createBackup('database')">
                    💾 Datenbank-Backup
                </button>
                <button class="btn btn-secondary" onclick="createBackup('files')">
                    📁 Datei-Backup
                </button>
                <button class="btn btn-success" onclick="createBackup('full')">
                    🚀 Vollständiges Backup
                </button>
            </div>
        </div>
        
        <!-- Backup-Listen -->
        <div class="section">
            <h2>Backups verwalten</h2>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab('database')">Datenbank-Backups</button>
                <button class="tab" onclick="switchTab('files')">Datei-Backups</button>
                <button class="tab" onclick="switchTab('rollbacks')">Rollback-Punkte</button>
                <button class="tab" onclick="switchTab('logs')">Logs</button>
            </div>
            
            <div id="tab-database" class="tab-content active">
                <ul class="backup-list" id="database-backups"></ul>
            </div>
            
            <div id="tab-files" class="tab-content">
                <ul class="backup-list" id="files-backups"></ul>
            </div>
            
            <div id="tab-rollbacks" class="tab-content">
                <ul class="backup-list" id="rollback-list"></ul>
            </div>
            
            <div id="tab-logs" class="tab-content">
                <div class="log-viewer" id="log-viewer">Lade Logs...</div>
            </div>
        </div>
    </div>
    
    <!-- Restore-Bestätigungs-Modal -->
    <div id="restore-modal" class="modal">
        <div class="modal-content">
            <h3>⚠️ Wiederherstellung bestätigen</h3>
            <p id="restore-message"></p>
            <div class="alert alert-warning">
                <strong>Wichtig:</strong> Ein Rollback-Punkt wird automatisch erstellt, sodass du diese Aktion rückgängig machen kannst.
            </div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeRestoreModal()">Abbrechen</button>
                <button class="btn btn-danger" onclick="confirmRestore()">Wiederherstellen</button>
            </div>
        </div>
    </div>
    
    <script>
        const csrfToken = '<?= $csrfToken ?>';
        let restoreData = null;
        
        // Seite initialisieren
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadBackups();
        });
        
        // Statistiken laden
        async function loadStats() {
            try {
                const response = await fetch('?action=get_stats');
                const stats = await response.json();
                
                document.getElementById('stat-total').textContent = stats.total_backups;
                document.getElementById('stat-size').textContent = formatBytes(stats.total_size);
                document.getElementById('stat-last').textContent = stats.last_backup 
                    ? new Date(stats.last_backup * 1000).toLocaleString('de-DE')
                    : 'Kein Backup';
                document.getElementById('stat-disk').textContent = formatBytes(stats.disk_usage.free);
            } catch (error) {
                console.error('Fehler beim Laden der Statistiken:', error);
            }
        }
        
        // Backups laden
        async function loadBackups() {
            try {
                const response = await fetch('?action=list_backups');
                const backups = await response.json();
                
                renderBackupList('database', backups.database);
                renderBackupList('files', backups.files);
            } catch (error) {
                console.error('Fehler beim Laden der Backups:', error);
            }
        }
        
        // Rollbacks laden
        async function loadRollbacks() {
            try {
                const response = await fetch('?action=list_rollbacks');
                const rollbacks = await response.json();
                
                const list = document.getElementById('rollback-list');
                
                if (rollbacks.length === 0) {
                    list.innerHTML = '<li class="backup-item">Keine Rollback-Punkte vorhanden</li>';
                    return;
                }
                
                list.innerHTML = rollbacks.map(rollback => `
                    <li class="backup-item">
                        <div class="backup-info">
                            <div class="name">${rollback.filename}</div>
                            <div class="meta">
                                ${rollback.created_formatted} • ${rollback.size_formatted} • ${rollback.type}
                            </div>
                        </div>
                        <div class="backup-actions">
                            <button class="btn btn-warning btn-sm" onclick="rollbackToPoint('${rollback.filename}')">
                                ↩️ Rollback
                            </button>
                            <button class="btn btn-primary btn-sm" onclick="downloadBackup('${rollback.filename}')">
                                ⬇️ Download
                            </button>
                        </div>
                    </li>
                `).join('');
            } catch (error) {
                console.error('Fehler beim Laden der Rollbacks:', error);
            }
        }
        
        // Backup-Liste rendern
        function renderBackupList(type, backups) {
            const list = document.getElementById(`${type}-backups`);
            
            if (backups.length === 0) {
                list.innerHTML = '<li class="backup-item">Keine Backups vorhanden</li>';
                return;
            }
            
            list.innerHTML = backups.map(backup => `
                <li class="backup-item">
                    <div class="backup-info">
                        <div class="name">${backup.filename}</div>
                        <div class="meta">
                            ${backup.created_formatted} • ${backup.size_formatted}
                        </div>
                    </div>
                    <div class="backup-actions">
                        <button class="btn btn-success btn-sm" onclick="restoreBackup('${backup.filename}', '${type}')">
                            🔄 Wiederherstellen
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="downloadBackup('${backup.filename}')">
                            ⬇️ Download
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="deleteBackup('${backup.filename}')">
                            🗑️ Löschen
                        </button>
                    </div>
                </li>
            `).join('');
        }
        
        // Backup erstellen
        async function createBackup(type) {
            if (!confirm(`${type.toUpperCase()}-Backup jetzt erstellen?`)) return;
            
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => btn.disabled = true);
            
            try {
                const formData = new FormData();
                formData.append('type', type);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('?action=create_backup', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Backup erfolgreich erstellt!');
                    loadStats();
                    loadBackups();
                } else {
                    alert('❌ Backup fehlgeschlagen!');
                }
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
            } finally {
                buttons.forEach(btn => btn.disabled = false);
            }
        }
        
        // Backup wiederherstellen (mit Bestätigung)
        function restoreBackup(filename, type) {
            restoreData = { filename, type };
            document.getElementById('restore-message').textContent = 
                `Möchtest du "${filename}" wirklich wiederherstellen? Dies überschreibt die aktuelle ${type === 'database' ? 'Datenbank' : 'Dateien'}.`;
            document.getElementById('restore-modal').classList.add('active');
        }
        
        // Restore-Modal schließen
        function closeRestoreModal() {
            document.getElementById('restore-modal').classList.remove('active');
            restoreData = null;
        }
        
        // Restore bestätigen und ausführen
        async function confirmRestore() {
            if (!restoreData) return;
            
            closeRestoreModal();
            
            const { filename, type } = restoreData;
            
            if (!confirm('⚠️ LETZTE WARNUNG: Wiederherstellung jetzt starten?')) return;
            
            try {
                const formData = new FormData();
                formData.append('file', filename);
                formData.append('type', type);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('?action=restore_backup', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ Wiederherstellung erfolgreich!\n\nRollback-Punkt erstellt: ${result.rollback_file || 'N/A'}\n\nQueries ausgeführt: ${result.queries_executed || 'N/A'}`);
                    loadStats();
                    loadBackups();
                } else {
                    alert('❌ Wiederherstellung fehlgeschlagen:\n' + (result.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
            }
        }
        
        // Notfall-Wiederherstellung
        async function emergencyRestore() {
            if (!confirm('🚨 NOTFALL-WIEDERHERSTELLUNG\n\nDies stellt das neueste Backup wieder her.\n\nFortfahren?')) return;
            
            if (!confirm('⚠️ LETZTE WARNUNG!\n\nDies ist eine kritische Operation.\nEin Rollback-Punkt wird automatisch erstellt.\n\nJETZT STARTEN?')) return;
            
            try {
                const includeFiles = confirm('Auch Dateien wiederherstellen?\n\n(Dies kann mehrere Minuten dauern)\n\nJa = Vollständige Wiederherstellung\nNein = Nur Datenbank');
                
                const formData = new FormData();
                formData.append('include_files', includeFiles ? 'true' : 'false');
                formData.append('csrf_token', csrfToken);
                
                alert('⏳ Wiederherstellung läuft...\n\nBitte warten und diese Seite NICHT schließen!');
                
                const response = await fetch('?action=emergency_restore', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                let message = '✅ NOTFALL-WIEDERHERSTELLUNG ABGESCHLOSSEN!\n\n';
                
                if (result.database) {
                    if (result.database.success) {
                        message += `✅ Datenbank: Erfolgreich\n`;
                        message += `   Rollback: ${result.database.rollback_file}\n\n`;
                    } else {
                        message += `❌ Datenbank: Fehlgeschlagen\n   Fehler: ${result.database.error}\n\n`;
                    }
                }
                
                if (result.files) {
                    if (result.files.success) {
                        message += `✅ Dateien: Erfolgreich\n`;
                        message += `   Rollback: ${result.files.rollback_file}\n`;
                    } else {
                        message += `❌ Dateien: Fehlgeschlagen\n   Fehler: ${result.files.error}\n`;
                    }
                }
                
                alert(message);
                
                // Seite neu laden
                setTimeout(() => {
                    if (confirm('System wiederhergestellt!\n\nSeite jetzt neu laden?')) {
                        location.reload();
                    }
                }, 1000);
                
            } catch (error) {
                alert('❌ Notfall-Wiederherstellung fehlgeschlagen:\n' + error.message);
            }
        }
        
        // Rollback zu einem Punkt
        async function rollbackToPoint(filename) {
            if (!confirm(`Rollback zu "${filename}" durchführen?\n\nDies stellt den System-Zustand zum Zeitpunkt dieses Rollback-Punkts wieder her.`)) return;
            
            try {
                const formData = new FormData();
                formData.append('file', filename);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('?action=rollback', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Rollback erfolgreich!');
                    loadStats();
                    loadBackups();
                } else {
                    alert('❌ Rollback fehlgeschlagen:\n' + (result.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
            }
        }
        
        // Backup herunterladen
        function downloadBackup(filename) {
            window.location.href = `?action=download_backup&file=${encodeURIComponent(filename)}`;
        }
        
        // Backup löschen
        async function deleteBackup(filename) {
            if (!confirm(`Backup "${filename}" wirklich löschen?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('file', filename);
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('?action=delete_backup', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Backup gelöscht!');
                    loadStats();
                    loadBackups();
                } else {
                    alert('❌ Löschen fehlgeschlagen!');
                }
            } catch (error) {
                alert('❌ Fehler: ' + error.message);
            }
        }
        
        // Tab wechseln
        function switchTab(tabName) {
            // Tabs
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Content
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(`tab-${tabName}`).classList.add('active');
            
            // Spezielle Aktionen
            if (tabName === 'logs') {
                loadLogs();
            } else if (tabName === 'rollbacks') {
                loadRollbacks();
            }
        }
        
        // Logs laden
        async function loadLogs() {
            try {
                const response = await fetch('?action=get_logs');
                const logs = await response.json();
                
                const viewer = document.getElementById('log-viewer');
                if (logs.length === 0) {
                    viewer.textContent = 'Keine Logs vorhanden';
                    return;
                }
                
                viewer.textContent = logs.map(log => 
                    `=== ${log.date} ===\n${log.content}\n`
                ).join('\n');
            } catch (error) {
                document.getElementById('log-viewer').textContent = 'Fehler beim Laden der Logs';
            }
        }
        
        // Bytes formatieren
        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            let i = 0;
            while (bytes > 1024 && i < units.length - 1) {
                bytes /= 1024;
                i++;
            }
            return bytes.toFixed(2) + ' ' + units[i];
        }
    </script>
</body>
</html>
