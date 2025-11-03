<?php
/**
 * Lead Login System
 * Für Empfehlungsprogramm Teilnehmer
 */

require_once __DIR__ . '/config/database.php';

session_start();

// Wenn bereits eingeloggt, weiterleiten
if (isset($_SESSION['lead_id'])) {
    header('Location: lead_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Registrierung über Referral Code
if (isset($_GET['ref'])) {
    $_SESSION['referral_code'] = $_GET['ref'];
    $success = 'Du wurdest eingeladen! Registriere dich jetzt.';
}

// Login verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if ($email && $password) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT id, name, password_hash FROM lead_users WHERE email = ?");
            $stmt->execute([$email]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($lead && password_verify($password, $lead['password_hash'])) {
                $_SESSION['lead_id'] = $lead['id'];
                $_SESSION['lead_name'] = $lead['name'];
                
                // Last login aktualisieren
                $stmt = $db->prepare("UPDATE lead_users SET last_login_at = NOW() WHERE id = ?");
                $stmt->execute([$lead['id']]);
                
                header('Location: lead_dashboard.php');
                exit;
            } else {
                $error = 'Ungültige E-Mail oder Passwort';
            }
        } catch (Exception $e) {
            $error = 'Datenbankfehler: ' . $e->getMessage();
        }
    } else {
        $error = 'Bitte fülle alle Felder aus';
    }
}

// Registrierung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (!$name || !$email || !$password) {
        $error = 'Bitte fülle alle Felder aus';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwörter stimmen nicht überein';
    } elseif (strlen($password) < 6) {
        $error = 'Passwort muss mindestens 6 Zeichen lang sein';
    } else {
        try {
            $db = getDBConnection();
            
            // Prüfen ob E-Mail bereits existiert
            $stmt = $db->prepare("SELECT id FROM lead_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'E-Mail bereits registriert';
            } else {
                // Lead erstellen
                $referral_code = 'LEAD' . strtoupper(substr(md5($email . time()), 0, 8));
                $api_token = bin2hex(random_bytes(32));
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $referrer_code = $_SESSION['referral_code'] ?? null;
                
                $stmt = $db->prepare("
                    INSERT INTO lead_users 
                    (name, email, password_hash, referral_code, api_token, referrer_code, registered_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $name,
                    $email,
                    $password_hash,
                    $referral_code,
                    $api_token,
                    $referrer_code
                ]);
                
                $lead_id = $db->lastInsertId();
                
                // Wenn Referrer vorhanden, tracken
                if ($referrer_code) {
                    // Referrer finden und Zähler erhöhen
                    $stmt = $db->prepare("
                        UPDATE lead_users 
                        SET total_referrals = total_referrals + 1 
                        WHERE referral_code = ?
                    ");
                    $stmt->execute([$referrer_code]);
                    
                    // Referral tracken
                    $stmt = $db->prepare("SELECT id FROM lead_users WHERE referral_code = ?");
                    $stmt->execute([$referrer_code]);
                    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($referrer) {
                        $stmt = $db->prepare("
                            INSERT INTO lead_referrals 
                            (referrer_id, referred_email, referred_name, referred_user_id, status, invited_at) 
                            VALUES (?, ?, ?, ?, 'active', NOW())
                        ");
                        $stmt->execute([$referrer['id'], $email, $name, $lead_id]);
                    }
                }
                
                // Auto-Login
                $_SESSION['lead_id'] = $lead_id;
                $_SESSION['lead_name'] = $name;
                unset($_SESSION['referral_code']);
                
                header('Location: lead_dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registrierung fehlgeschlagen: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead Login - Empfehlungsprogramm</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: none;
            color: #666;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .tab.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            margin-bottom: -2px;
        }
        .form-content {
            display: none;
        }
        .form-content.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border-left: 4px solid #f44;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Lead Login</h1>
        <div class="subtitle">Empfehlungsprogramm</div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('login')">Login</button>
            <button class="tab" onclick="switchTab('register')">Registrieren</button>
        </div>
        
        <!-- Login Form -->
        <div id="login-form" class="form-content active">
            <form method="POST">
                <div class="form-group">
                    <label>E-Mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Passwort</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" name="login" class="btn">Einloggen</button>
            </form>
        </div>
        
        <!-- Register Form -->
        <div id="register-form" class="form-content">
            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>E-Mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Passwort</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Passwort bestätigen</label>
                    <input type="password" name="password_confirm" required>
                </div>
                <button type="submit" name="register" class="btn">Registrieren</button>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.form-content').forEach(f => f.classList.remove('active'));
            
            event.target.classList.add('active');
            document.getElementById(tab + '-form').classList.add('active');
        }
    </script>
</body>
</html>
