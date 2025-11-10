<?php
/**
 * Marktplatz Danke-Seite für DigiStore24
 * Diese Seite wird nach erfolgreichem Kauf angezeigt
 * 
 * URL für DigiStore24 Thank-You-Page:
 * https://app.mehr-infos-jetzt.de/public/marketplace-thankyou.php
 */

require_once __DIR__ . '/../config/database.php';

// Basis-Informationen
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];
$loginUrl = $protocol . '://' . $domain . '/public/login.php';

// DigiStore24 Parameter auslesen
$buyerEmail = $_GET['buyer_email'] ?? '';
$productName = $_GET['product_name'] ?? 'Dein Freebie';
$orderId = $_GET['order_id'] ?? '';
$productId = $_GET['product_id'] ?? '';

// Verkäufer-Informationen und Rechtstexte laden
$sellerUserId = null;
$impressumLink = null;
$datenschutzLink = null;
$hasLegalTexts = false;

try {
    $pdo = getDBConnection();
    
    // Versuche den Verkäufer anhand der DigiStore-Produkt-ID zu finden
    if ($productId) {
        // Methode 1: Exakte ID-Übereinstimmung
        $stmt = $pdo->prepare("
            SELECT customer_id 
            FROM customer_freebies 
            WHERE digistore_product_id = ?
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Methode 2: Falls keine exakte Übereinstimmung, nach ID in URL suchen
        if (!$result) {
            $stmt = $pdo->prepare("
                SELECT customer_id 
                FROM customer_freebies 
                WHERE digistore_product_id LIKE ?
                LIMIT 1
            ");
            $stmt->execute(['%/' . $productId . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($result) {
            $sellerUserId = $result['customer_id'];
        }
    }
    
    // Falls gefunden, Rechtstexte-Links generieren
    if ($sellerUserId) {
        // Prüfen ob Rechtstexte existieren und nicht leer sind
        $stmt = $pdo->prepare("
            SELECT impressum, datenschutz 
            FROM legal_texts 
            WHERE user_id = ?
        ");
        $stmt->execute([$sellerUserId]);
        $legalTexts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($legalTexts) {
            // Nur Links erstellen wenn Inhalte vorhanden sind
            if (!empty(trim($legalTexts['impressum']))) {
                $impressumLink = $protocol . '://' . $domain . '/impressum.php?user=' . $sellerUserId;
                $hasLegalTexts = true;
            }
            
            if (!empty(trim($legalTexts['datenschutz']))) {
                $datenschutzLink = $protocol . '://' . $domain . '/datenschutz.php?user=' . $sellerUserId;
                $hasLegalTexts = true;
            }
        }
    }
} catch (Exception $e) {
    // Fehler loggen, aber Seite trotzdem anzeigen
    error_log("Marketplace Thank-You Page Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vielen Dank für deinen Kauf! 🎉</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        
        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 48px 32px;
            text-align: center;
            color: white;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 16px;
            animation: bounce 1s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        .header h1 {
            font-size: 36px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.95;
        }
        
        .content {
            padding: 40px 32px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 32px;
        }
        
        .info-box-title {
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-box-text {
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.6;
        }
        
        .steps {
            margin: 32px 0;
        }
        
        .steps-title {
            font-size: 24px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .step {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .step:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }
        
        .step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        
        .step-description {
            font-size: 14px;
            color: #64748b;
            line-height: 1.6;
        }
        
        .login-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            margin: 32px 0;
        }
        
        .login-box-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
        }
        
        .login-btn {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 16px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .login-hint {
            margin-top: 16px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .copy-section {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
        }
        
        .copy-title {
            font-size: 16px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .copy-input-wrapper {
            display: flex;
            gap: 8px;
        }
        
        .copy-input {
            flex: 1;
            padding: 12px;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Courier New', monospace;
            background: white;
        }
        
        .copy-btn {
            padding: 12px 24px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .copy-btn:hover {
            background: #d97706;
        }
        
        /* Footer Styles */
        .footer {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .footer-title {
            color: white;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            opacity: 0.95;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .footer-link {
            color: white;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .footer-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        .footer-note {
            color: rgba(255, 255, 255, 0.85);
            font-size: 13px;
            line-height: 1.5;
        }
        
        @media (max-width: 640px) {
            body {
                padding: 16px;
            }
            
            .header {
                padding: 32px 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .content {
                padding: 32px 20px;
            }
            
            .success-icon {
                font-size: 60px;
            }
            
            .step {
                flex-direction: column;
                gap: 16px;
            }
            
            .copy-input-wrapper {
                flex-direction: column;
            }
            
            .footer {
                padding: 20px 16px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 12px;
            }
            
            .footer-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="success-icon">🎉</div>
                <h1>Vielen Dank für deinen Kauf!</h1>
                <p>Dein Freebie wurde erfolgreich in deinen Account kopiert</p>
            </div>
            
            <!-- Content -->
            <div class="content">
                <!-- Payment Info -->
                <div class="info-box">
                    <div class="info-box-title">
                        <span>💳</span>
                        <span>Zahlungsinformation</span>
                    </div>
                    <div class="info-box-text">
                        Die Abbuchung erfolgt durch <strong>digistore24.com</strong><br>
                        <?php if ($orderId): ?>
                            Deine Bestellnummer: <strong><?php echo htmlspecialchars($orderId); ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Steps -->
                <div class="steps">
                    <h2 class="steps-title">🚀 So geht es weiter</h2>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Login-Daten per E-Mail</div>
                            <div class="step-description">
                                Du erhältst in wenigen Minuten eine E-Mail mit deinen Zugangsdaten 
                                (E-Mail-Adresse, Passwort und RAW-Code) an 
                                <?php if ($buyerEmail): ?>
                                    <strong><?php echo htmlspecialchars($buyerEmail); ?></strong>
                                <?php else: ?>
                                    deine E-Mail-Adresse
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Login ins Dashboard</div>
                            <div class="step-description">
                                Nutze die Login-Daten aus der E-Mail, um dich im KI Leadsystem Dashboard anzumelden. 
                                Das Freebie wurde automatisch in deinen Account kopiert!
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">Freebie anpassen</div>
                            <div class="step-description">
                                Passe das Freebie nach deinen Wünschen an - Texte, Farben, E-Mail-Integration, 
                                und vieles mehr. Der Videokurs bleibt beim ursprünglichen Verkäufer.
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <div class="step-title">Links nutzen</div>
                            <div class="step-description">
                                Nutze deine personalisierten Freebie-Links in deinem Marketing, auf Social Media, 
                                oder in deinen E-Mail-Kampagnen.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Login Box -->
                <div class="login-box">
                    <div class="login-box-title">🔐 Bereit zum Start?</div>
                    <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="login-btn">
                        Jetzt einloggen
                    </a>
                    <div class="login-hint">
                        Nutze die Zugangsdaten aus deiner E-Mail
                    </div>
                </div>
                
                <!-- Copy Login Link -->
                <div class="copy-section">
                    <div class="copy-title">
                        <span>📋</span>
                        <span>Login-Link für später speichern:</span>
                    </div>
                    <div class="copy-input-wrapper">
                        <input 
                            type="text" 
                            class="copy-input" 
                            value="<?php echo htmlspecialchars($loginUrl); ?>" 
                            readonly 
                            id="loginLink">
                        <button class="copy-btn" onclick="copyLoginLink()">
                            Link kopieren
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer mit Rechtstexten des Verkäufers -->
    <?php if ($hasLegalTexts): ?>
    <div class="footer">
        <div class="footer-title">
            <span>⚖️</span>
            <span>Rechtliche Informationen</span>
        </div>
        <div class="footer-links">
            <?php if ($impressumLink): ?>
                <a href="<?php echo htmlspecialchars($impressumLink); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="footer-link">
                    <span>📋</span>
                    <span>Impressum</span>
                </a>
            <?php endif; ?>
            
            <?php if ($datenschutzLink): ?>
                <a href="<?php echo htmlspecialchars($datenschutzLink); ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="footer-link">
                    <span>🔒</span>
                    <span>Datenschutz</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="footer-note">
            Diese Rechtstexte stammen vom Verkäufer des Freebies
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function copyLoginLink() {
            const input = document.getElementById('loginLink');
            const button = event.target;
            
            input.select();
            input.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                const originalText = button.textContent;
                button.textContent = '✓ Kopiert!';
                button.style.background = '#22c55e';
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.style.background = '';
                }, 2000);
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(input.value);
                }
            } catch (err) {
                alert('Bitte manuell kopieren: ' + input.value);
            }
        }
        
        // Smooth fade-in animation
        window.addEventListener('load', function() {
            document.body.style.opacity = '0';
            setTimeout(() => {
                document.body.style.transition = 'opacity 0.5s';
                document.body.style.opacity = '1';
            }, 100);
        });
    </script>
</body>
</html>