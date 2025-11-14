<?php
/**
 * Passwort zurücksetzen - Neues Passwort setzen
 * User kommt mit Token hier her und setzt neues Passwort
 */

session_start();
require_once '../config/database.php';

$error = '';
$success = '';
$tokenValid = false;
$user = null;

// Token aus URL holen
$token = $_GET['token'] ?? '';

// Token validieren
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $tokenValid = true;
        } else {
            $error = 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen Link an.';
        }
        
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        $error = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
    }
} else {
    $error = 'Kein Reset-Token angegeben.';
}

// Neues Passwort verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password) || empty($passwordConfirm)) {
        $error = 'Bitte fülle alle Felder aus';
    } elseif (strlen($password) < 8) {
        $error = 'Passwort muss mindestens 8 Zeichen lang sein';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwörter stimmen nicht überein';
    } else {
        try {
            // Passwort hashen
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Passwort updaten und Token löschen
            $stmt = $pdo->prepare("
                UPDATE users 
                SET password = ?,
                    password_reset_token = NULL,
                    password_reset_expires = NULL
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $user['id']]);
            
            $success = 'Dein Passwort wurde erfolgreich geändert! Du wirst in 3 Sekunden zum Login weitergeleitet...';
            
            // Log für Debugging
            error_log("Password reset successful for user ID: " . $user['id']);
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $error = 'Fehler beim Zurücksetzen des Passworts. Bitte versuche es erneut.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Passwort setzen - Optinpilot</title>
    <?php if ($success): ?>
    <meta http-equiv="refresh" content="3;url=login.php">
    <?php endif; ?>
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
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            width: 100%;
            max-width: 480px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .logo h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .logo p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .user-info {
            background: #f3f4f6;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .user-info p {
            color: #374151;
            font-size: 14px;
            margin: 0;
        }
        
        .user-info strong {
            color: #1f2937;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            color: #1f2937;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            margin-top: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .requirements {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .requirements h3 {
            color: #1e3a8a;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .requirements li {
            color: #1e3a8a;
            font-size: 13px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">🔑</div>
            <h1>Neues Passwort setzen</h1>
            <p>Wähle ein sicheres Passwort für deinen Account</p>
        </div>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            ⚠️ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($tokenValid && !$success): ?>
        
        <div class="user-info">
            <p>Passwort zurücksetzen für: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
        </div>
        
        <form method="POST" action="">
            <div class="requirements">
                <h3>📋 Passwort-Anforderungen:</h3>
                <ul>
                    <li>Mindestens 8 Zeichen lang</li>
                    <li>Groß- und Kleinbuchstaben verwenden</li>
                    <li>Mindestens eine Zahl</li>
                </ul>
            </div>
            
            <div class="form-group">
                <label class="form-label">Neues Passwort</label>
                <input type="password" name="password" class="form-input" 
                       placeholder="Mindestens 8 Zeichen" required autofocus 
                       minlength="8">
            </div>
            
            <div class="form-group">
                <label class="form-label">Passwort bestätigen</label>
                <input type="password" name="password_confirm" class="form-input" 
                       placeholder="Passwort wiederholen" required 
                       minlength="8">
            </div>
            
            <button type="submit" class="btn">
                Passwort jetzt ändern
            </button>
        </form>
        
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Zurück zum Login</a>
        </div>
    </div>
</body>
</html>
