<?php
/**
 * Passwort zurücksetzen - E-Mail-Anfrage
 * User gibt E-Mail ein und bekommt Reset-Link zugeschickt
 */

session_start();
require_once '../config/database.php';
require_once '../includes/quentn_api.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Bitte gib eine gültige E-Mail-Adresse ein';
        $messageType = 'error';
    } else {
        try {
            // Rate Limiting prüfen
            if (!checkPasswordResetRateLimit($pdo, $email)) {
                $message = 'Zu viele Anfragen. Bitte versuche es in einer Stunde erneut.';
                $messageType = 'error';
            } else {
                // User suchen
                $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Immer gleiche Nachricht zeigen (gegen User-Enumeration)
                $message = 'Falls ein Account mit dieser E-Mail existiert, haben wir dir einen Link zum Zurücksetzen geschickt.';
                $messageType = 'success';
                
                // Wenn User existiert, Reset-Link senden
                if ($user) {
                    // Token generieren (kryptographisch sicher)
                    $token = bin2hex(random_bytes(32)); // 64 Zeichen
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Token in DB speichern
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET password_reset_token = ?, 
                            password_reset_expires = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$token, $expires, $user['id']]);
                    
                    // Reset-Link erstellen
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $resetLink = $protocol . '://' . $host . '/public/password-reset.php?token=' . $token;
                    
                    // E-Mail über Quentn senden
                    $result = sendPasswordResetEmail(
                        $user['email'],
                        $user['name'] ?? 'Benutzer',
                        $resetLink
                    );
                    
                    // Log für Debugging (nur im Fehlerfall)
                    if (!$result['success']) {
                        error_log("Password reset email failed for user {$user['id']}: " . $result['message']);
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            $message = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen - Optinpilot</title>
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
        
        .info-box {
            background: #dbeafe;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .info-box p {
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">🔐</div>
            <h1>Passwort zurücksetzen</h1>
            <p>Gib deine E-Mail-Adresse ein und wir senden dir einen Link zum Zurücksetzen</p>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php if ($messageType === 'success'): ?>
                ✅ <?php echo htmlspecialchars($message); ?>
            <?php else: ?>
                ⚠️ <?php echo htmlspecialchars($message); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($messageType !== 'success'): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">E-Mail-Adresse</label>
                <input type="email" name="email" class="form-input" 
                       placeholder="deine@email.de" required autofocus
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="info-box">
                <p>
                    💡 <strong>Info:</strong> Der Reset-Link ist aus Sicherheitsgründen nur 1 Stunde gültig.
                </p>
            </div>
            
            <button type="submit" class="btn">
                Reset-Link anfordern
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Zurück zum Login</a>
        </div>
    </div>
</body>
</html>
