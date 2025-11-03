<?php
/**
 * Freebie Thank You Page - Extended mit Empfehlungsprogramm
 * Zeigt nach Freebie-Download Empfehlungsprogramm an
 */

require_once __DIR__ . '/config/database.php';

// E-Mail aus GET oder POST
$email = $_GET['email'] ?? $_POST['email'] ?? '';
$freebie_name = $_GET['freebie'] ?? 'dein Freebie';

if (!$email) {
    header('Location: /');
    exit;
}

$db = getDBConnection();

// Prüfen ob bereits als Lead registriert
$stmt = $db->prepare("SELECT id, referral_code FROM referral_leads WHERE email = ?");
$stmt->execute([$email]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    // Als neuen Lead registrieren
    $name = $_POST['name'] ?? 'Neuer Lead';
    $referral_code = substr(md5($email . time()), 0, 10);
    $api_token = bin2hex(random_bytes(32));
    
    $stmt = $db->prepare("
        INSERT INTO referral_leads 
        (name, email, referral_code, api_token, registered_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $referral_code, $api_token]);
    $lead_id = $db->lastInsertId();
    
    $lead = [
        'id' => $lead_id,
        'referral_code' => $referral_code
    ];
}

$referral_link = 'https://app.mehr-infos-jetzt.de/lead_login.php?ref=' . $lead['referral_code'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vielen Dank! 🎉</title>
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
            max-width: 600px;
            width: 100%;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            margin-bottom: 20px;
        }
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 1s ease-in-out;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 15px;
        }
        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .info-text {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            text-align: left;
        }
        .info-text p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .referral-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .referral-section h2 {
            font-size: 28px;
            margin-bottom: 15px;
        }
        .referral-section p {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 25px;
        }
        .benefits {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .benefit {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
        }
        .benefit .icon {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .benefit .text {
            font-size: 14px;
            font-weight: 600;
        }
        .link-box {
            background: white;
            color: #333;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .link-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-align: left;
        }
        .link-input-group {
            display: flex;
            gap: 10px;
        }
        .link-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            background: #f8f9fa;
        }
        .copy-btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        .copy-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: white;
            color: #667eea;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255,255,255,0.3);
        }
        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,255,255,0.4);
        }
        .social-share {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .social-share p {
            font-size: 14px;
            margin-bottom: 15px;
        }
        .social-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .social-btn {
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 14px;
        }
        .social-btn:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">🎉</div>
            <h1>Vielen Dank!</h1>
            <div class="subtitle">
                <?php echo htmlspecialchars($freebie_name); ?> wurde erfolgreich heruntergeladen
            </div>
            <div class="info-text">
                <p>📧 Eine Bestätigungs-E-Mail wurde an <strong><?php echo htmlspecialchars($email); ?></strong> gesendet.</p>
                <p>💡 Prüfe auch deinen Spam-Ordner, falls du die E-Mail nicht findest.</p>
            </div>
        </div>
        
        <div class="referral-section">
            <h2>🎁 Noch mehr gratis erhalten!</h2>
            <p>
                Empfehle uns an deine Freunde weiter und erhalte exklusive Belohnungen!
            </p>
            
            <div class="benefits">
                <div class="benefit">
                    <div class="icon">📚</div>
                    <div class="text">Weitere E-Books</div>
                </div>
                <div class="benefit">
                    <div class="icon">💬</div>
                    <div class="text">1:1 Beratung</div>
                </div>
                <div class="benefit">
                    <div class="icon">🎓</div>
                    <div class="text">Kurs-Zugänge</div>
                </div>
                <div class="benefit">
                    <div class="icon">👑</div>
                    <div class="text">VIP Status</div>
                </div>
            </div>
            
            <div class="link-box">
                <div class="link-label">Dein persönlicher Empfehlungs-Link:</div>
                <div class="link-input-group">
                    <input type="text" class="link-input" id="referral-link" 
                           value="<?php echo $referral_link; ?>" readonly>
                    <button class="copy-btn" onclick="copyLink()">📋 Kopieren</button>
                </div>
            </div>
            
            <a href="lead_dashboard.php" class="cta-button">
                → Zu meinem Dashboard
            </a>
            
            <div class="social-share">
                <p>Oder direkt teilen:</p>
                <div class="social-buttons">
                    <a href="https://wa.me/?text=<?php echo urlencode('Schau dir das mal an: ' . $referral_link); ?>" 
                       class="social-btn" target="_blank">
                        WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($referral_link); ?>" 
                       class="social-btn" target="_blank">
                        Facebook
                    </a>
                    <a href="mailto:?subject=<?php echo urlencode('Tolles Freebie entdeckt!'); ?>&body=<?php echo urlencode('Schau dir das mal an: ' . $referral_link); ?>" 
                       class="social-btn">
                        E-Mail
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyLink() {
            const input = document.getElementById('referral-link');
            input.select();
            document.execCommand('copy');
            
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = '✅ Kopiert!';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '#667eea';
            }, 2000);
        }
    </script>
</body>
</html>
