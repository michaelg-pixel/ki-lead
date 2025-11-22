<?php
/**
 * Email-Verifizierung für Leads
 * Optionale Sicherheitsmaßnahme gegen Betrug
 */

require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();
$success = false;
$error = '';
$expired = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Token prüfen
        $stmt = $pdo->prepare("
            SELECT ev.*, lu.email, lu.user_id, lu.referrer_id
            FROM email_verifications ev
            JOIN lead_users lu ON ev.lead_id = lu.id
            WHERE ev.token = ?
        ");
        $stmt->execute([$token]);
        $verification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            $error = 'Ungültiger Verifizierungs-Link.';
        } elseif ($verification['verified_at']) {
            $error = 'Diese E-Mail wurde bereits verifiziert.';
        } elseif (strtotime($verification['expires_at']) < time()) {
            $expired = true;
            $error = 'Dieser Verifizierungs-Link ist abgelaufen.';
        } else {
            // Verifizierung durchführen
            $pdo->beginTransaction();
            
            // 1. Verifizierung markieren
            $stmt = $pdo->prepare("
                UPDATE email_verifications 
                SET verified_at = NOW()
                WHERE token = ?
            ");
            $stmt->execute([$token]);
            
            // 2. Lead-Status auf 'active' setzen
            $stmt = $pdo->prepare("
                UPDATE lead_users 
                SET status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([$verification['lead_id']]);
            
            // 3. Falls Referrer vorhanden: Counter erhöhen
            if ($verification['referrer_id']) {
                $stmt = $pdo->prepare("
                    UPDATE lead_users 
                    SET 
                        total_referrals = COALESCE(total_referrals, 0) + 1,
                        successful_referrals = COALESCE(successful_referrals, 0) + 1
                    WHERE id = ?
                ");
                $stmt->execute([$verification['referrer_id']]);
                
                // 4. Belohnungs-Check durchführen
                require_once __DIR__ . '/mailgun/includes/MailgunService.php';
                
                // Referrer-Daten laden
                $stmt = $pdo->prepare("SELECT * FROM lead_users WHERE id = ?");
                $stmt->execute([$verification['referrer_id']]);
                $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($referrer) {
                    $newReferrals = $referrer['successful_referrals'];
                    
                    // Erreichte Belohnungsstufen prüfen
                    $stmt = $pdo->prepare("
                        SELECT * FROM reward_definitions 
                        WHERE user_id = ? 
                        AND required_referrals <= ?
                        AND required_referrals > ?
                        ORDER BY required_referrals DESC
                    ");
                    $stmt->execute([
                        $verification['user_id'], 
                        $newReferrals, 
                        $newReferrals - 1
                    ]);
                    
                    while ($reward = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Prüfe ob noch nicht versendet
                        $stmt_check = $pdo->prepare("
                            SELECT id FROM reward_emails_sent 
                            WHERE lead_id = ? AND reward_id = ?
                        ");
                        $stmt_check->execute([$verification['referrer_id'], $reward['id']]);
                        
                        if ($stmt_check->rowCount() === 0) {
                            // Kunde-Daten laden
                            $stmt_customer = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt_customer->execute([$verification['user_id']]);
                            $customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);
                            
                            // Belohnungs-Email senden
                            $mailgun = new MailgunService();
                            $result = $mailgun->sendRewardEmail($referrer, $reward, $customer);
                            
                            if ($result['success']) {
                                // Als versendet markieren
                                $stmt_log = $pdo->prepare("
                                    INSERT INTO reward_emails_sent 
                                    (lead_id, reward_id, sent_at, mailgun_id)
                                    VALUES (?, ?, NOW(), ?)
                                ");
                                $stmt_log->execute([
                                    $verification['referrer_id'], 
                                    $reward['id'],
                                    $result['message_id']
                                ]);
                            }
                        }
                    }
                }
            }
            
            $pdo->commit();
            $success = true;
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.';
        error_log("Email Verification Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail Verifizierung | Opt-in Pilot</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
            <h1>E-Mail verifiziert!</h1>
            <div class="success-box">
                <h3>Dein Konto wurde aktiviert</h3>
                <p>Du kannst jetzt alle Funktionen des Empfehlungsprogramms nutzen.</p>
            </div>
            <p>
                Deine erfolgreichen Empfehlungen zählen ab sofort und du kannst 
                Belohnungen verdienen!
            </p>
            <a href="/lead_dashboard.php" class="btn">Zum Dashboard</a>
        <?php elseif ($error): ?>
            <div class="icon"><?php echo $expired ? '⏰' : '❌'; ?></div>
            <h1><?php echo $expired ? 'Link abgelaufen' : 'Verifizierung fehlgeschlagen'; ?></h1>
            <div class="error-box">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
            <?php if ($expired): ?>
                <p>Bitte fordere einen neuen Verifizierungs-Link an.</p>
            <?php endif; ?>
            <a href="https://app.mehr-infos-jetzt.de" class="btn">Zur Startseite</a>
        <?php else: ?>
            <div class="icon">📧</div>
            <h1>E-Mail Verifizierung</h1>
            <p>Bitte klicke auf den Link in der E-Mail, die wir dir gesendet haben.</p>
        <?php endif; ?>
        
        <div class="footer">
            © <?php echo date('Y'); ?> Opt-in Pilot · 
            <a href="/datenschutz-programm.php" style="color: #999;">Datenschutz</a>
        </div>
    </div>
</body>
</html>
