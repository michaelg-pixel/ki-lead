<?php
/**
 * Backup System - IP Entsperren
 * Tool zum Aufheben von IP-Sperren
 */

require_once __DIR__ . '/config.php';

$success = null;
$error = null;
$blockedIPs = [];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Sicherheitsabfrage
$SECRET_CODE = 'unblock-ki-lead-2024';

// Funktion: Alle Sperren lesen
function getBlockedIPs() {
    $blockFile = BACKUP_ROOT_DIR . '/.blocked_ips';
    
    if (!file_exists($blockFile)) {
        return [];
    }
    
    $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
    
    // Mit Ablaufzeit formatieren
    $formatted = [];
    foreach ($blocked as $ip => $unblockTime) {
        $formatted[] = [
            'ip' => $ip,
            'unblock_time' => date('d.m.Y H:i:s', $unblockTime),
            'remaining' => max(0, $unblockTime - time())
        ];
    }
    
    return $formatted;
}

// Funktion: IP entsperren
function unblockIP($ip) {
    $blockFile = BACKUP_ROOT_DIR . '/.blocked_ips';
    $loginAttemptsFile = BACKUP_ROOT_DIR . '/.login_attempts_' . md5($ip);
    $rateLimitFile = BACKUP_ROOT_DIR . '/.rate_limit_' . md5($ip);
    
    $removed = false;
    
    // Aus Block-Liste entfernen
    if (file_exists($blockFile)) {
        $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
        if (isset($blocked[$ip])) {
            unset($blocked[$ip]);
            file_put_contents($blockFile, json_encode($blocked));
            $removed = true;
        }
    }
    
    // Login-Attempts zurücksetzen
    if (file_exists($loginAttemptsFile)) {
        unlink($loginAttemptsFile);
        $removed = true;
    }
    
    // Rate-Limit zurücksetzen
    if (file_exists($rateLimitFile)) {
        unlink($rateLimitFile);
        $removed = true;
    }
    
    return $removed;
}

// Funktion: Alle IPs entsperren
function unblockAllIPs() {
    $blockFile = BACKUP_ROOT_DIR . '/.blocked_ips';
    
    // Block-Datei löschen
    if (file_exists($blockFile)) {
        unlink($blockFile);
    }
    
    // Alle Login-Attempt-Dateien löschen
    $files = glob(BACKUP_ROOT_DIR . '/.login_attempts_*');
    foreach ($files as $file) {
        unlink($file);
    }
    
    // Alle Rate-Limit-Dateien löschen
    $files = glob(BACKUP_ROOT_DIR . '/.rate_limit_*');
    foreach ($files as $file) {
        unlink($file);
    }
    
    return true;
}

// Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    
    if ($code !== $SECRET_CODE) {
        $error = "Ungültiger Sicherheitscode!";
    } else {
        if (isset($_POST['unblock_ip'])) {
            $ip = $_POST['ip'] ?? '';
            if (unblockIP($ip)) {
                $success = "✅ IP $ip wurde entsperrt!";
            } else {
                $error = "IP $ip war nicht gesperrt.";
            }
        } elseif (isset($_POST['unblock_all'])) {
            if (unblockAllIPs()) {
                $success = "✅ Alle IPs wurden entsperrt!";
            } else {
                $error = "Fehler beim Entsperren.";
            }
        } elseif (isset($_POST['unblock_my_ip'])) {
            if (unblockIP($clientIP)) {
                $success = "✅ Deine IP ($clientIP) wurde entsperrt!";
            } else {
                $error = "Deine IP ($clientIP) war nicht gesperrt.";
            }
        }
    }
}

$blockedIPs = getBlockedIPs();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Entsperren - Backup System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-size: 24px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #004085;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #856404;
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
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-block {
            width: 100%;
            margin-top: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
        .ip-list {
            list-style: none;
            margin-top: 20px;
        }
        .ip-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .ip-info {
            flex: 1;
        }
        .ip-info .ip {
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
        }
        .ip-info .time {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .your-ip {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #004085;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔓 IP Entsperren</h1>
        <p class="subtitle">Backup System - Sicherheitsverwaltung</p>
        
        <div class="your-ip">
            Deine aktuelle IP: <?= htmlspecialchars($clientIP) ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="warning-box">
            <strong>⚠️ Sicherheitshinweis:</strong>
            Dieses Tool hebt IP-Sperren auf, die durch fehlgeschlagene Login-Versuche entstanden sind.
        </div>
        
        <?php if (count($blockedIPs) > 0): ?>
            <h2 style="margin-top: 30px; margin-bottom: 15px;">Gesperrte IPs (<?= count($blockedIPs) ?>)</h2>
            <ul class="ip-list">
                <?php foreach ($blockedIPs as $blocked): ?>
                    <li class="ip-item">
                        <div class="ip-info">
                            <div class="ip"><?= htmlspecialchars($blocked['ip']) ?></div>
                            <div class="time">
                                Entsperrt um: <?= htmlspecialchars($blocked['unblock_time']) ?>
                                (in <?= ceil($blocked['remaining'] / 60) ?> Min.)
                            </div>
                        </div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="code" value="<?= htmlspecialchars($SECRET_CODE) ?>">
                            <input type="hidden" name="ip" value="<?= htmlspecialchars($blocked['ip']) ?>">
                            <button type="submit" name="unblock_ip" class="btn btn-success">Entsperren</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <div class="info-box">
                ✅ Aktuell sind keine IPs gesperrt.
            </div>
        <?php endif; ?>
        
        <h2 style="margin-top: 30px; margin-bottom: 15px;">Schnellaktionen</h2>
        
        <form method="POST">
            <div class="form-group">
                <label>Sicherheitscode</label>
                <input type="text" name="code" placeholder="unblock-ki-lead-2024" required>
            </div>
            
            <button type="submit" name="unblock_my_ip" class="btn btn-success btn-block">
                🔓 Meine IP entsperren (<?= htmlspecialchars($clientIP) ?>)
            </button>
            
            <?php if (count($blockedIPs) > 0): ?>
                <button type="submit" name="unblock_all" class="btn btn-danger btn-block">
                    🔓 ALLE IPs entsperren
                </button>
            <?php endif; ?>
        </form>
        
        <a href="admin.php" class="back-link">← Zurück zum Login</a>
    </div>
</body>
</html>
