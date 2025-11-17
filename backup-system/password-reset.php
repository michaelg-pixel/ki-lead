<?php
/**
 * Backup System - Passwort Reset
 * Sichere Passwort-Wiederherstellung per E-Mail
 */

session_start();
require_once __DIR__ . '/config.php';

// Admin E-Mail aus config
$ADMIN_EMAIL = 'michael.gluska@gmail.com';

// Funktion: Reset-Token generieren und speichern
function generateResetToken() {
    $token = bin2hex(random_bytes(32));
    $expiry = time() + 3600; // 1 Stunde gültig
    
    $tokenFile = BACKUP_ROOT_DIR . '/reset_token.json';
    file_put_contents($tokenFile, json_encode([
        'token' => password_hash($token, PASSWORD_DEFAULT),
        'expiry' => $expiry,
        'created' => date('Y-m-d H:i:s')
    ]));
    
    return $token;
}

// Funktion: Token validieren
function validateResetToken($token) {
    $tokenFile = BACKUP_ROOT_DIR . '/reset_token.json';
    
    if (!file_exists($tokenFile)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($tokenFile), true);
    
    // Token abgelaufen?
    if (time() > $data['expiry']) {
        unlink($tokenFile);
        return false;
    }
    
    // Token korrekt?
    if (!password_verify($token, $data['token'])) {
        return false;
    }
    
    return true;
}

// Funktion: E-Mail senden
function sendResetEmail($token) {
    global $ADMIN_EMAIL;
    
    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/password-reset.php?token=" . urlencode($token);
    
    $subject = "🔐 Backup System - Passwort zurücksetzen";
    $message = "
Hallo,

Du hast eine Passwort-Zurücksetzung für dein Backup System angefordert.

Klicke auf folgenden Link, um ein neues Passwort zu setzen:
$resetLink

⚠️ Dieser Link ist 1 Stunde gültig!

Falls du diese Anfrage nicht gestellt hast, ignoriere diese E-Mail einfach.

---
KI Lead System Backup Administration
" . date('d.m.Y H:i:s');
    
    $headers = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "Reply-To: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($ADMIN_EMAIL, $subject, $message, $headers);
}

// Verarbeitung
$step = 'request'; // request, sent, reset, success
$error = null;
$success = null;

// Schritt 1: E-Mail-Anfrage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email'] ?? '');
    
    // Nur Admin-E-Mail erlaubt
    if ($email === $ADMIN_EMAIL) {
        $token = generateResetToken();
        
        if (sendResetEmail($token)) {
            $step = 'sent';
            $success = "Reset-Link wurde an $ADMIN_EMAIL gesendet!";
        } else {
            $error = "E-Mail konnte nicht gesendet werden. Prüfe deine Server-E-Mail-Konfiguration.";
        }
    } else {
        // Aus Sicherheitsgründen keine spezifische Fehlermeldung
        $error = "Wenn diese E-Mail-Adresse registriert ist, wurde ein Reset-Link gesendet.";
    }
}

// Schritt 2: Token-Validierung und Passwort-Reset
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    if (validateResetToken($token)) {
        $step = 'reset';
        
        // Passwort setzen
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validierung
            if (empty($newPassword)) {
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
                $configContent = file_get_contents($configFile);
                
                // Altes Hash finden und ersetzen
                $pattern = "/define\('BACKUP_ADMIN_PASS',\s*'[^']+'\);/";
                $replacement = "define('BACKUP_ADMIN_PASS', '" . $newHash . "');";
                $configContent = preg_replace($pattern, $replacement, $configContent);
                
                if (file_put_contents($configFile, $configContent)) {
                    // Token-Datei löschen
                    $tokenFile = BACKUP_ROOT_DIR . '/reset_token.json';
                    if (file_exists($tokenFile)) {
                        unlink($tokenFile);
                    }
                    
                    $step = 'success';
                    $success = "✅ Passwort erfolgreich geändert! Du kannst dich jetzt anmelden.";
                } else {
                    $error = "Fehler beim Speichern des neuen Passworts. Prüfe die Dateiberechtigungen.";
                }
            }
        }
    } else {
        $error = "Ungültiger oder abgelaufener Reset-Link!";
        $step = 'request';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen - Backup System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        .reset-box h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
            font-size: 24px;
        }
        .reset-box p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
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
        .btn {
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
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #004085;
        }
        .info-box ul {
            margin: 10px 0 0 20px;
        }
        .info-box li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="reset-box">
        <?php if ($step === 'request'): ?>
            <!-- Schritt 1: E-Mail-Anfrage -->
            <h1>🔐 Passwort vergessen?</h1>
            <p>Gib deine Admin-E-Mail-Adresse ein und wir senden dir einen Reset-Link.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>E-Mail-Adresse</label>
                    <input type="email" name="email" placeholder="deine@email.de" required autofocus>
                </div>
                
                <button type="submit" name="request_reset" class="btn">Reset-Link senden</button>
                <a href="admin.php" class="btn btn-secondary">Zurück zum Login</a>
            </form>
            
        <?php elseif ($step === 'sent'): ?>
            <!-- Schritt 2: E-Mail gesendet -->
            <h1>✉️ E-Mail gesendet</h1>
            
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            
            <div class="info-box">
                <strong>Nächste Schritte:</strong>
                <ol>
                    <li>Prüfe dein E-Mail-Postfach (auch Spam-Ordner)</li>
                    <li>Klicke auf den Link in der E-Mail</li>
                    <li>Setze ein neues Passwort</li>
                </ol>
                <p style="margin-top: 10px;"><strong>⏰ Der Link ist 1 Stunde gültig.</strong></p>
            </div>
            
            <a href="admin.php" class="btn">Zurück zum Login</a>
            
        <?php elseif ($step === 'reset'): ?>
            <!-- Schritt 3: Neues Passwort setzen -->
            <h1>🔑 Neues Passwort setzen</h1>
            <p>Wähle ein sicheres Passwort mit mindestens 8 Zeichen.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" id="resetForm">
                <div class="form-group">
                    <label>Neues Passwort</label>
                    <input type="password" name="new_password" id="newPassword" required autofocus>
                    <div class="password-strength" id="strengthIndicator"></div>
                </div>
                
                <div class="form-group">
                    <label>Passwort bestätigen</label>
                    <input type="password" name="confirm_password" id="confirmPassword" required>
                </div>
                
                <button type="submit" name="set_password" class="btn">Passwort speichern</button>
            </form>
            
            <script>
                // Passwort-Stärke prüfen
                document.getElementById('newPassword').addEventListener('input', function(e) {
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
                document.getElementById('resetForm').addEventListener('submit', function(e) {
                    const password = document.getElementById('newPassword').value;
                    const confirm = document.getElementById('confirmPassword').value;
                    
                    if (password !== confirm) {
                        e.preventDefault();
                        alert('❌ Passwörter stimmen nicht überein!');
                    }
                });
            </script>
            
        <?php elseif ($step === 'success'): ?>
            <!-- Schritt 4: Erfolg -->
            <h1>✅ Passwort geändert!</h1>
            
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            
            <p>Dein Passwort wurde erfolgreich geändert. Du kannst dich jetzt mit deinem neuen Passwort anmelden.</p>
            
            <a href="admin.php" class="btn">Zum Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
