<?php
session_start();
require_once '../config/database.php';

$error = '';
$success = '';

// Login verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Bitte E-Mail und Passwort eingeben';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Prüfe ob User aktiv ist (falls Spalte existiert)
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    $error = 'Dein Account wurde deaktiviert';
                } else {
                    // Login erfolgreich - Session-Variablen setzen
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['name'] = $user['name'] ?? $user['email'];
                    $_SESSION['logged_in'] = true; // WICHTIG: für isLoggedIn() Funktion
                    
                    // Try to update last login (if column exists)
                    try {
                        $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $update->execute([$user['id']]);
                    } catch (PDOException $e) {
                        // Column doesn't exist, ignore
                    }
                    
                    // Weiterleitung basierend auf Rolle
                    if ($user['role'] === 'admin') {
                        header('Location: ../admin/dashboard.php');
                        exit;
                    } else {
                        header('Location: ../customer/dashboard.php');
                        exit;
                    }
                }
            } else {
                $error = 'E-Mail oder Passwort falsch';
            }
        } catch (Exception $e) {
            $error = 'Login-Fehler: ' . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Optinpilot</title>
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
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 48px;
            width: 100%;
            max-width: 440px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .logo h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .logo p {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 24px;
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
        
        .btn-login {
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
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c00;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #0a0;
            border: 1px solid #cfc;
        }
        
        .links {
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🎓 Optinpilot</h1>
            <p>Willkommen zurück!</p>
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
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">E-Mail</label>
                <input type="email" name="email" class="form-input" 
                       placeholder="deine@email.de" required autofocus
                       value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Passwort</label>
                <input type="password" name="password" class="form-input" 
                       placeholder="••••••••" required>
            </div>
            
            <button type="submit" class="btn-login">
                Anmelden
            </button>
        </form>
        
        <div class="links">
            Noch kein Konto? <a href="register.php">Jetzt registrieren</a>
        </div>
    </div>
</body>
</html>
