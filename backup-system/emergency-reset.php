<?php
/**
 * Backup System - Notfall Passwort-Reset
 * Direktes Ändern des Passworts ohne E-Mail
 * WICHTIG: Diese Datei nach Verwendung löschen!
 */

session_start();

$success = null;
$error = null;

// Sicherheitsabfrage
$SECRET_CODE = 'ki-lead-backup-2024'; // Ändere diesen Code für mehr Sicherheit!

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($code !== $SECRET_CODE) {
        $error = "Ungültiger Sicherheitscode!";
    } elseif (empty($newPassword)) {
        $error = "Bitte gib ein Passwort ein.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Passwort muss mindestens 8 Zeichen lang sein.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "Passwörter stimmen nicht überein.";
    } else {
        // Neues Passwort-Hash generieren
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // config.php aktualisieren
        $configFile = __DIR__ . '/config.php';
        
        if (!file_exists($configFile)) {
            $error = "config.php nicht gefunden!";
        } else {
            $configContent = file_get_contents($configFile);
            
            // Altes Hash finden und ersetzen
            $pattern = "/define\('BACKUP_ADMIN_PASS',\s*'[^']+'\);/";
            $replacement = "define('BACKUP_ADMIN_PASS', '" . $newHash . "');";
            $configContent = preg_replace($pattern, $replacement, $configContent);
            
            if (file_put_contents($configFile, $configContent)) {
                $success = "✅ Passwort erfolgreich geändert!";
                error_log("Backup admin password changed via emergency reset at " . date('Y-m-d H:i:s'));
            } else {
                $error = "Fehler beim Speichern. Prüfe die Dateiberechtigungen!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notfall Passwort-Reset - Backup System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .emergency-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
        }
        .emergency-box h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #dc3545;
            font-size: 24px;
        }
        .emergency-box p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #856404;
        }
        .warning-box strong {
            display: block;
            margin-bottom: 8px;
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
            border-color: #dc3545;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: #6c757d;
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
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        .delete-warning {
            background: #fee2e2;
            border: 2px solid #dc2626;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            color: #7f1d1d;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="emergency-box">
        <h1>🚨 Notfall Passwort-Reset</h1>
        <p>Direktes Ändern des Backup-Admin-Passworts</p>
        
        <div class="warning-box">
            <strong>⚠️ Sicherheitshinweis:</strong>
            Diese Datei sollte nur im Notfall verwendet werden. 
            Lösche sie nach der Verwendung unbedingt wieder!
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <p>Du kannst dich jetzt mit deinem neuen Passwort anmelden.</p>
            <a href="admin.php" class="btn">Zum Login</a>
            
            <div class="delete-warning">
                <strong>🗑️ Wichtig: Lösche diese Datei jetzt!</strong><br>
                Datei: <code>backup-system/emergency-reset.php</code>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label>Sicherheitscode</label>
                    <input type="text" name="code" placeholder="ki-lead-backup-2024" required autofocus>
                    <small style="color: #666; font-size: 12px;">
                        Standard: ki-lead-backup-2024 (siehe Code in Zeile 12)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Neues Passwort</label>
                    <input type="password" name="new_password" id="newPassword" required>
                    <div class="password-strength" id="strengthIndicator"></div>
                </div>
                
                <div class="form-group">
                    <label>Passwort bestätigen</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required>
                </div>
                
                <button type="submit" class="btn">🚨 Passwort ändern</button>
                <a href="admin.php" class="btn btn-secondary">Abbrechen</a>
            </form>
        <?php endif; ?>
    </div>
    
    <script>
        // Passwort-Stärke prüfen
        document.getElementById('newPassword')?.addEventListener('input', function(e) {
            const password = e.target.value;
            const indicator = document.getElementById('strengthIndicator');
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (password.length === 0) {
                indicator.textContent = '';
            } else if (strength <= 2) {
                indicator.textContent = '❌ Schwaches Passwort';
                indicator.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                indicator.textContent = '⚠️ Mittleres Passwort';
                indicator.className = 'password-strength strength-medium';
            } else {
                indicator.textContent = '✅ Starkes Passwort';
                indicator.className = 'password-strength strength-strong';
            }
        });
        
        // Passwörter vergleichen
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('❌ Passwörter stimmen nicht überein!');
            }
        });
    </script>
</body>
</html>
