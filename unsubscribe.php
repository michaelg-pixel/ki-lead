<?php
/**
 * Unsubscribe von Belohnungs-Emails
 * Lead kann sich von Email-Benachrichtigungen abmelden
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$success = false;
$error = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Token dekodieren
        $decoded = base64_decode($token);
        $parts = explode('|', $decoded);
        
        if (count($parts) === 2) {
            $email = $parts[0];
            $customer_id = (int)$parts[1];
            
            // Lead-Status auf 'unsubscribed' setzen
            $stmt = $pdo->prepare("
                UPDATE lead_users 
                SET status = 'unsubscribed', 
                    unsubscribed_at = NOW()
                WHERE email = ? AND user_id = ?
            ");
            $stmt->execute([$email, $customer_id]);
            
            if ($stmt->rowCount() > 0) {
                $success = true;
            } else {
                $error = 'Keine passende Anmeldung gefunden.';
            }
        } else {
            $error = 'Ungültiger Abmelde-Link.';
        }
    } catch (Exception $e) {
        $error = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
        error_log("Unsubscribe Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abmelden | Opt-in Pilot</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 24px;
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .success-box {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: left;
        }
        
        .success-box h3 {
            font-size: 16px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 8px;
        }
        
        .success-box p {
            font-size: 14px;
            color: #047857;
            margin: 0;
        }
        
        .error-box {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: left;
        }
        
        .error-box p {
            font-size: 14px;
            color: #991b1b;
            margin: 0;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 28px;
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
        
        .footer {
            margin-top: 32px;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="icon">✅</div>
            <h1>Erfolgreich abgemeldet</h1>
            <div class="success-box">
                <h3>Du wurdest abgemeldet</h3>
                <p>Du erhältst ab sofort keine Belohnungs-E-Mails mehr von uns.</p>
            </div>
            <p>
                Dein Dashboard-Zugang bleibt weiterhin aktiv. Du kannst dich jederzeit 
                wieder anmelden, indem du dich in dein Dashboard einloggst.
            </p>
        <?php elseif ($error): ?>
            <div class="icon">❌</div>
            <h1>Fehler beim Abmelden</h1>
            <div class="error-box">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <p>Falls das Problem weiterhin besteht, kontaktiere bitte unseren Support.</p>
        <?php else: ?>
            <div class="icon">📧</div>
            <h1>Abmeldung bestätigen</h1>
            <p>
                Möchtest du dich wirklich von Belohnungs-E-Mails abmelden?<br>
                Du erhältst dann keine Benachrichtigungen mehr über erreichte Belohnungsstufen.
            </p>
        <?php endif; ?>
        
        <a href="https://app.mehr-infos-jetzt.de" class="btn">Zur Startseite</a>
        
        <div class="footer">
            © <?php echo date('Y'); ?> Opt-in Pilot · 
            <a href="/datenschutz-programm.php" style="color: #999;">Datenschutz</a>
        </div>
    </div>
</body>
</html>
