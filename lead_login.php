<?php
/**
 * Lead Login & Registrierung System
 * Für Empfehlungsprogramm Teilnehmer
 * Automatische User-ID Ermittlung vom ref_code
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
    
    // User ID automatisch vom ref_code ermitteln
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE ref_code = ? LIMIT 1");
        $stmt->execute([$_GET['ref']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['referral_user_id'] = $user['id'];
        }
    } catch (Exception $e) {
        error_log("Error finding user_id from ref_code: " . $e->getMessage());
    }
    
    $success = 'Du wurdest eingeladen! Registriere dich jetzt und profitiere vom Empfehlungsprogramm.';
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
                $user_id = $_SESSION['referral_user_id'] ?? null;
                
                $stmt = $db->prepare("
                    INSERT INTO lead_users 
                    (name, email, password_hash, referral_code, api_token, referrer_code, user_id, registered_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $name,
                    $email,
                    $password_hash,
                    $referral_code,
                    $api_token,
                    $referrer_code,
                    $user_id
                ]);
                
                $lead_id = $db->lastInsertId();
                
                // Wenn Referrer vorhanden, tracken
                if ($referrer_code) {
                    // Referrer finden
                    $stmt = $db->prepare("SELECT id FROM lead_users WHERE referral_code = ?");
                    $stmt->execute([$referrer_code]);
                    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($referrer) {
                        // Zähler beim Referrer erhöhen
                        $stmt = $db->prepare("
                            UPDATE lead_users 
                            SET total_referrals = total_referrals + 1,
                                successful_referrals = successful_referrals + 1
                            WHERE id = ?
                        ");
                        $stmt->execute([$referrer['id']]);
                        
                        // Referral tracken
                        $stmt = $db->prepare("
                            INSERT INTO lead_referrals 
                            (referrer_id, referred_email, referred_name, referred_user_id, status, invited_at) 
                            VALUES (?, ?, ?, ?, 'active', NOW())
                        ");
                        $stmt->execute([$referrer['id'], $email, $name, $lead_id]);
                        
                        // Prüfe ob Belohnungs-Schwellen erreicht wurden
                        checkAndUnlockRewards($db, $referrer['id']);
                    }
                }
                
                // Auto-Login
                $_SESSION['lead_id'] = $lead_id;
                $_SESSION['lead_name'] = $name;
                unset($_SESSION['referral_code']);
                unset($_SESSION['referral_user_id']);
                
                header('Location: lead_dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registrierung fehlgeschlagen: ' . $e->getMessage();
        }
    }
}

/**
 * Prüft und schaltet Belohnungen frei wenn Schwellen erreicht wurden
 */
function checkAndUnlockRewards($db, $lead_id) {
    // Lead-Daten mit successful_referrals holen
    $stmt = $db->prepare("SELECT user_id, successful_referrals FROM lead_users WHERE id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead || !$lead['user_id']) {
        return;
    }
    
    // Alle Belohnungen für diesen User laden
    $stmt = $db->prepare("
        SELECT id, tier_level, required_referrals, reward_title 
        FROM reward_definitions 
        WHERE user_id = ? AND is_active = 1
        ORDER BY tier_level ASC
    ");
    $stmt->execute([$lead['user_id']]);
    $rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rewards as $reward) {
        // Prüfe ob diese Belohnung freigeschaltet werden sollte
        if ($lead['successful_referrals'] >= $reward['required_referrals']) {
            // Prüfe ob bereits freigeschaltet
            $stmt = $db->prepare("
                SELECT id FROM referral_reward_tiers 
                WHERE lead_id = ? AND tier_id = ?
            ");
            $stmt->execute([$lead_id, $reward['tier_level']]);
            
            if (!$stmt->fetch()) {
                // Belohnung freischalten
                $stmt = $db->prepare("
                    INSERT INTO referral_reward_tiers 
                    (lead_id, tier_id, tier_name, current_referrals, achieved_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $lead_id,
                    $reward['tier_level'],
                    $reward['reward_title'],
                    $lead['successful_referrals']
                ]);
                
                // Rewards_earned Zähler erhöhen
                $stmt = $db->prepare("
                    UPDATE lead_users 
                    SET rewards_earned = rewards_earned + 1 
                    WHERE id = ?
                ");
                $stmt->execute([$lead_id]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empfehlungsprogramm - Jetzt teilnehmen!</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }
        
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            align-items: start;
            flex: 1;
        }
        
        /* Info Section */
        .info-section {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .info-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .info-title {
            font-size: 36px;
            font-weight: 900;
            color: #111827;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .info-subtitle {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .benefits-list {
            margin-bottom: 32px;
        }
        
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
            padding: 16px;
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-radius: 12px;
            border-left: 4px solid #10b981;
        }
        
        .benefit-icon {
            font-size: 28px;
            flex-shrink: 0;
        }
        
        .benefit-content h4 {
            font-size: 16px;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 6px;
        }
        
        .benefit-content p {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        .how-it-works {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border-radius: 16px;
            padding: 24px;
            margin-top: 24px;
        }
        
        .how-it-works h3 {
            font-size: 20px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 16px;
        }
        
        .how-step {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            color: #1e3a8a;
            font-size: 14px;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        /* Form Section */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            position: sticky;
            top: 20px;
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
        
        /* Footer Styles */
        .footer {
            margin-top: 40px;
            padding: 20px;
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .footer-links a {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .footer-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        .footer-copyright {
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            margin-top: 15px;
        }
        
        /* Mobile */
        @media (max-width: 968px) {
            .page-container {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                position: relative;
                top: 0;
            }
            
            .info-title {
                font-size: 28px;
            }
            
            .footer-links {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Info Section -->
        <div class="info-section">
            <div class="info-badge">🎁 Exklusives Angebot</div>
            <h2 class="info-title">Verdiene mit jedem Empfohlenen!</h2>
            <p class="info-subtitle">
                Werde Teil unseres exklusiven Empfehlungsprogramms und profitiere von attraktiven Belohnungen für jeden Lead, den du uns bringst.
            </p>
            
            <div class="benefits-list">
                <div class="benefit-item">
                    <div class="benefit-icon">💰</div>
                    <div class="benefit-content">
                        <h4>Attraktive Belohnungen</h4>
                        <p>Verdiene wertvolle Prämien für jeden Lead, den du erfolgreich empfiehlst. Je mehr du empfiehlst, desto höher deine Belohnungen!</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <div class="benefit-content">
                        <h4>Einfach zu nutzen</h4>
                        <p>Erhalte deinen persönlichen Empfehlungslink und teile ihn einfach per E-Mail, WhatsApp oder Social Media.</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">📊</div>
                    <div class="benefit-content">
                        <h4>Live Tracking</h4>
                        <p>Behalte alle deine Klicks, Conversions und Verdienste in Echtzeit im Blick – transparent und übersichtlich.</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🏆</div>
                    <div class="benefit-content">
                        <h4>Bonus-System</h4>
                        <p>Steige in höhere Belohnungsstufen auf und profitiere von exklusiven Boni und Sonderaktionen.</p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">✅</div>
                    <div class="benefit-content">
                        <h4>100% Kostenlos</h4>
                        <p>Keine versteckten Kosten, keine Gebühren. Die Teilnahme ist für dich vollkommen kostenlos!</p>
                    </div>
                </div>
            </div>
            
            <div class="how-it-works">
                <h3><i class="fas fa-lightbulb"></i> So funktioniert's:</h3>
                <div class="how-step">
                    <div class="step-number">1</div>
                    <span><strong>Registrieren:</strong> Erstelle dein kostenloses Konto (rechts im Formular)</span>
                </div>
                <div class="how-step">
                    <div class="step-number">2</div>
                    <span><strong>Link erhalten:</strong> Du bekommst deinen persönlichen Empfehlungslink</span>
                </div>
                <div class="how-step">
                    <div class="step-number">3</div>
                    <span><strong>Teilen:</strong> Teile den Link mit Freunden, Familie und deinem Netzwerk</span>
                </div>
                <div class="how-step">
                    <div class="step-number">4</div>
                    <span><strong>Verdienen:</strong> Erhalte Belohnungen für jeden generierten Lead</span>
                </div>
            </div>
        </div>
        
        <!-- Form Section -->
        <div class="form-container">
            <h1>🚀 Jetzt starten!</h1>
            <div class="subtitle">Kostenlos registrieren und durchstarten</div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab <?php echo !isset($_POST['login']) ? 'active' : ''; ?>" onclick="switchTab('register')">Registrieren</button>
                <button class="tab <?php echo isset($_POST['login']) ? 'active' : ''; ?>" onclick="switchTab('login')">Login</button>
            </div>
            
            <!-- Register Form -->
            <div id="register-form" class="form-content <?php echo !isset($_POST['login']) ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Name</label>
                        <input type="text" name="name" required placeholder="Dein Name">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> E-Mail</label>
                        <input type="email" name="email" required placeholder="deine@email.de">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Passwort</label>
                        <input type="password" name="password" required placeholder="Mindestens 6 Zeichen">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Passwort bestätigen</label>
                        <input type="password" name="password_confirm" required placeholder="Passwort wiederholen">
                    </div>
                    <button type="submit" name="register" class="btn">
                        <i class="fas fa-rocket"></i> Kostenlos registrieren
                    </button>
                </form>
            </div>
            
            <!-- Login Form -->
            <div id="login-form" class="form-content <?php echo isset($_POST['login']) ? 'active' : ''; ?>">
                <form method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> E-Mail</label>
                        <input type="email" name="email" required placeholder="deine@email.de">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Passwort</label>
                        <input type="password" name="password" required placeholder="Dein Passwort">
                    </div>
                    <button type="submit" name="login" class="btn">
                        <i class="fas fa-sign-in-alt"></i> Einloggen
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-links">
            <a href="impressum.php" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-info-circle"></i> Impressum
            </a>
            <a href="datenschutz.php" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-shield-alt"></i> Datenschutz
            </a>
        </div>
        <div class="footer-copyright">
            © <?php echo date('Y'); ?> - Alle Rechte vorbehalten
        </div>
    </footer>
    
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