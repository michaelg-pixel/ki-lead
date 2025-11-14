<?php
session_start();
require_once '../config/database.php';
require_once '../config/quentn_config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $agb_accepted = isset($_POST['agb_accepted']);
    
    // Validierung
    if (empty($vorname) || empty($nachname) || empty($email) || empty($password)) {
        $error = 'Bitte fülle alle Felder aus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    } elseif (strlen($password) < 8) {
        $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } elseif (!$agb_accepted) {
        $error = 'Bitte akzeptiere die AGB und Datenschutzbestimmungen.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Prüfe ob Email bereits existiert
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Diese E-Mail-Adresse ist bereits registriert.';
            } else {
                // Registriere neuen Benutzer
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $name = $vorname . ' ' . $nachname;
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, created_at) 
                    VALUES (?, ?, ?, 'customer', NOW())
                ");
                $stmt->execute([$name, $email, $hashed_password]);
                
                $user_id = $pdo->lastInsertId();
                
                // QUENTN INTEGRATION: Kontakt zu Quentn senden
                sendToQuentn($email, $vorname, $nachname);
                
                // Auto-Login nach Registrierung
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';
                $_SESSION['logged_in'] = true;
                
                // Weiterleitung zum Dashboard
                header('Location: /customer/dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Registrierung fehlgeschlagen. Bitte versuche es später erneut.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}

/**
 * Sendet Kontakt zu Quentn bei Registrierung
 */
function sendToQuentn($email, $firstName, $lastName) {
    try {
        // Kontakt-Daten für Quentn
        $contactData = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'skip_double_opt_in' => true, // Wichtig: Kunde hat bereits bei Registrierung zugestimmt
            'tags' => ['registration', 'customer'] // Tags für Segmentierung
        ];
        
        // API Request zu Quentn
        $ch = curl_init(QUENTN_API_BASE_URL . 'contacts');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($contactData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . QUENTN_API_KEY
            ],
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Quentn: Contact created successfully for $email");
            return true;
        } else {
            error_log("Quentn: Failed to create contact for $email - HTTP $httpCode - $response");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Quentn API error during registration: " . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung - KI Leadsystem</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        /* Header */
        .header {
            max-width: 800px;
            margin: 0 auto 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .login-link {
            color: #8b5cf6;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        /* Container */
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        /* Success Badge */
        .success-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
        }
        
        .success-icon {
            width: 20px;
            height: 20px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }
        
        /* Main Title */
        .main-title {
            text-align: center;
            margin-bottom: 8px;
        }
        
        .main-title h1 {
            font-size: 36px;
            color: #1f2937;
            font-weight: 700;
        }
        
        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 48px;
        }
        
        /* Card */
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            margin-bottom: 32px;
        }
        
        .card-header {
            background: #f9fafb;
            padding: 24px 32px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-header h2 {
            font-size: 20px;
            color: #1f2937;
            font-weight: 600;
        }
        
        /* Steps */
        .step {
            padding: 32px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .step:last-child {
            border-bottom: none;
        }
        
        .step-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .step-content h3 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .step-content p {
            color: #6b7280;
            line-height: 1.6;
        }
        
        /* Form */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #8b5cf6;
            background: white;
        }
        
        .form-group small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-top: 4px;
        }
        
        /* Checkbox */
        .checkbox-group {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 24px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-top: 3px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .checkbox-group a {
            color: #8b5cf6;
            text-decoration: none;
        }
        
        .checkbox-group a:hover {
            text-decoration: underline;
        }
        
        /* Button */
        .btn-primary {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        /* Info Boxes */
        .info-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .info-box-content p {
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
        }
        
        .info-box-content small {
            color: #6b7280;
            font-size: 13px;
        }
        
        /* Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c00;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 32px 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 16px;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
        }
        
        .footer-links a:hover {
            color: #8b5cf6;
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 16px;
            }
            
            .step {
                padding: 24px 20px;
            }
            
            .main-title h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <div class="logo-icon">🌟</div>
            <span class="logo-text">Member-Bereich</span>
        </div>
        <a href="/public/login.php" class="login-link">Bereits Kunde? Anmelden</a>
    </div>
    
    <div class="container">
        <div style="text-align: center;">
            <div class="success-badge">
                <div class="success-icon">✓</div>
                Bestellung erfolgreich!
            </div>
        </div>
        
        <div class="main-title">
            <h1>Danke für deine Bestellung!</h1>
        </div>
        <p class="subtitle">Die Abbuchung erfolgt durch <strong>digistore24.com</strong></p>
        
        <div class="card">
            <div class="card-header">
                <h2>Wie geht es jetzt weiter?</h2>
            </div>
            
            <!-- Step 1: Registration -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Registriere dich jetzt für deinen sofortigen Zugang</h3>
                        <p>Nach der Registrierung erhältst du sofort Zugang zu deinem Member-Bereich.</p>
                    </div>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vorname</label>
                            <input type="text" name="vorname" placeholder="Ihr Vorname" required value="<?php echo htmlspecialchars($_POST['vorname'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Nachname</label>
                            <input type="text" name="nachname" placeholder="Ihr Nachname" required value="<?php echo htmlspecialchars($_POST['nachname'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>E-Mail-Adresse *</label>
                        <input type="email" name="email" placeholder="cyber@web.de" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Passwort * (mind. 8 Zeichen)</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                        <small>Wähle ein sicheres Passwort mit mindestens 8 Zeichen</small>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="agb_accepted" id="agb" required>
                        <label for="agb">
                            Ich stimme dem <a href="/public/av-vertrag.php" target="_blank">AV-Vertrag</a>, der 
                            <a href="https://info-xxl.de/datenschutz/" target="_blank">Datenschutzerklärung</a> und den 
                            <a href="https://app.mehr-infos-jetzt.de/public/agb.php" target="_blank">AGBs</a> zu. *
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Zum Member-Bereich
                        <span>→</span>
                    </button>
                    
                    <p style="text-align: center; margin-top: 16px; font-size: 13px; color: #6b7280;">
                        Nach der Registrierung erhalten Sie sofortigen Zugang ohne E-Mail-Bestätigung.
                    </p>
                </form>
            </div>
            
            <!-- Step 2: Video -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Schaue dir das Video in deinem Member-Bereich an</h3>
                        <p>Um alle nächsten Schritte zu verstehen und das Maximum aus deinem Zugang herauszuholen.</p>
                    </div>
                </div>
                
                <div class="info-box">
                    <div class="info-icon">▶</div>
                    <div class="info-box-content">
                        <p>Willkommensvideo verfügbar nach der Registrierung</p>
                        <small>Schaue dir das Video direkt nach dem Login an</small>
                    </div>
                </div>
            </div>
            
            <!-- Step 3: Autoresponder -->
            <div class="step">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Trage deinen Autoresponder-Code ein</h3>
                        <p>Um dein System zu aktivieren und automatisierte Empfehlungslinks zu generieren.</p>
                    </div>
                </div>
                
                <div class="info-box">
                    <div class="info-icon">⚙</div>
                    <div class="info-box-content">
                        <p>Raw-Code Eingabe</p>
                        <small>Verfügbar in Ihrem Member-Bereich nach der Anmeldung</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; 2025 KI Leadsystem. Alle Rechte vorbehalten.</p>
        <div class="footer-links">
            <a href="https://info-xxl.de/impressum/" target="_blank">Impressum</a>
            <a href="https://info-xxl.de/datenschutz/" target="_blank">Datenschutz</a>
            <a href="/public/av-vertrag.php">AV-Vertrag</a>
            <a href="/public/agb.php" target="_blank">AGB</a>
        </div>
    </div>
</body>
</html>
