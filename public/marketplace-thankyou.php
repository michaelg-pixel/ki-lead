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

// DEBUG MODE - kann aktiviert werden mit ?debug=1
$debugMode = isset($_GET['debug']) && $_GET['debug'] == '1';
$debugInfo = [];

// Verkäufer-Informationen und Rechtstexte laden
$sellerUserId = null;
$impressumLink = null;
$datenschutzLink = null;
$hasLegalTexts = false;

try {
    $pdo = getDBConnection();
    
    $debugInfo[] = "DigiStore24 product_id: " . ($productId ?: 'NICHT VORHANDEN');
    
    // Versuche den Verkäufer anhand der DigiStore-Produkt-ID zu finden
    if ($productId) {
        // Methode 1: Exakte ID-Übereinstimmung
        $stmt = $pdo->prepare("
            SELECT customer_id, headline, digistore_product_id
            FROM customer_freebies 
            WHERE digistore_product_id = ?
            AND marketplace_enabled = 1
            LIMIT 1
        ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $debugInfo[] = "Methode 1 (exakte ID): " . ($result ? "Gefunden - Customer ID: " . $result['customer_id'] : "Nicht gefunden");
        
        // Methode 2: Falls keine exakte Übereinstimmung, nach ID in URL suchen
        if (!$result) {
            $stmt = $pdo->prepare("
                SELECT customer_id, headline, digistore_product_id
                FROM customer_freebies 
                WHERE digistore_product_id LIKE ?
                AND marketplace_enabled = 1
                LIMIT 1
            ");
            $stmt->execute(['%' . $productId . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $debugInfo[] = "Methode 2 (LIKE %ID%): " . ($result ? "Gefunden - Customer ID: " . $result['customer_id'] : "Nicht gefunden");
        }
        
        // Methode 3: Nach /produkt-id am Ende der URL suchen
        if (!$result) {
            $stmt = $pdo->prepare("
                SELECT customer_id, headline, digistore_product_id
                FROM customer_freebies 
                WHERE digistore_product_id LIKE ?
                AND marketplace_enabled = 1
                LIMIT 1
            ");
            $stmt->execute(['%/' . $productId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $debugInfo[] = "Methode 3 (endet mit /ID): " . ($result ? "Gefunden - Customer ID: " . $result['customer_id'] : "Nicht gefunden");
        }
        
        if ($result) {
            $sellerUserId = $result['customer_id'];
            $debugInfo[] = "Verkäufer gefunden: Customer ID " . $sellerUserId;
            $debugInfo[] = "Freebie: " . $result['headline'];
            $debugInfo[] = "DigiStore Link im Freebie: " . $result['digistore_product_id'];
        } else {
            $debugInfo[] = "FEHLER: Kein Freebie mit dieser product_id gefunden!";
        }
    } else {
        $debugInfo[] = "FEHLER: Keine product_id von DigiStore24 übergeben!";
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
        
        $debugInfo[] = "Legal Texts Query: " . ($legalTexts ? "Gefunden" : "Nicht gefunden");
        
        if ($legalTexts) {
            $impressumEmpty = empty(trim($legalTexts['impressum']));
            $datenschutzEmpty = empty(trim($legalTexts['datenschutz']));
            
            $debugInfo[] = "Impressum vorhanden: " . ($impressumEmpty ? "NEIN (leer)" : "JA");
            $debugInfo[] = "Datenschutz vorhanden: " . ($datenschutzEmpty ? "NEIN (leer)" : "JA");
            
            // Nur Links erstellen wenn Inhalte vorhanden sind
            if (!$impressumEmpty) {
                $impressumLink = $protocol . '://' . $domain . '/impressum.php?user=' . $sellerUserId;
                $hasLegalTexts = true;
                $debugInfo[] = "Impressum-Link erstellt: " . $impressumLink;
            }
            
            if (!$datenschutzEmpty) {
                $datenschutzLink = $protocol . '://' . $domain . '/datenschutz.php?user=' . $sellerUserId;
                $hasLegalTexts = true;
                $debugInfo[] = "Datenschutz-Link erstellt: " . $datenschutzLink;
            }
        } else {
            $debugInfo[] = "FEHLER: Keine Legal Texts für user_id " . $sellerUserId . " gefunden!";
        }
    }
    
    $debugInfo[] = "hasLegalTexts: " . ($hasLegalTexts ? "TRUE (Footer wird angezeigt)" : "FALSE (Footer wird NICHT angezeigt)");
    
} catch (Exception $e) {
    // Fehler loggen, aber Seite trotzdem anzeigen
    error_log("Marketplace Thank-You Page Error: " . $e->getMessage());
    $debugInfo[] = "EXCEPTION: " . $e->getMessage();
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
        
        /* Debug Box */
        .debug-box {
            background: #fef2f2;
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 20px;
            margin: 24px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .debug-title {
            font-size: 16px;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 12px;
        }
        
        .debug-item {
            padding: 4px 0;
            color: #7f1d1d;
            line-height: 1.6;
        }
        
        /* Dezenter Footer mit Rechtstexten */
        .legal-footer {
            text-align: center;
            padding: 20px;
            margin-top: 20px;
        }
        
        .legal-links {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .legal-links a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            margin: 0 8px;
            transition: color 0.2s;
        }
        
        .legal-links a:hover {
            color: white;
            text-decoration: underline;
        }
        
        .legal-separator {
            margin: 0 4px;
            color: rgba(255, 255, 255, 0.6);
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
                
                <?php if ($debugMode): ?>
                <!-- Debug Info -->
                <div class="debug-box">
                    <div class="debug-title">🐛 DEBUG INFORMATION</div>
                    <?php foreach ($debugInfo as $info): ?>
                        <div class="debug-item">• <?php echo htmlspecialchars($info); ?></div>
                    <?php endforeach; ?>
                    <div class="debug-item" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #fca5a5;">
                        Hint: Füge ?debug=1 zur URL hinzu, um diese Info zu sehen
                    </div>
                </div>
                <?php endif; ?>
                
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
    
    <!-- Dezenter Footer mit Rechtstexten -->
    <?php if ($hasLegalTexts): ?>
    <div class="legal-footer">
        <div class="legal-links">
            <?php if ($impressumLink && $datenschutzLink): ?>
                <a href="<?php echo htmlspecialchars($impressumLink); ?>" target="_blank" rel="noopener noreferrer">Impressum</a>
                <span class="legal-separator">|</span>
                <a href="<?php echo htmlspecialchars($datenschutzLink); ?>" target="_blank" rel="noopener noreferrer">Datenschutz</a>
            <?php elseif ($impressumLink): ?>
                <a href="<?php echo htmlspecialchars($impressumLink); ?>" target="_blank" rel="noopener noreferrer">Impressum</a>
            <?php elseif ($datenschutzLink): ?>
                <a href="<?php echo htmlspecialchars($datenschutzLink); ?>" target="_blank" rel="noopener noreferrer">Datenschutz</a>
            <?php endif; ?>
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