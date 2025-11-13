<?php
/**
 * Lead Registrierung für Dashboard-Zugang
 * Lead gibt selbst seine E-Mail ein und bekommt Zugang zum Empfehlungsprogramm
 */

require_once __DIR__ . '/config/database.php';

session_start();

$pdo = getDBConnection();

// Parameter aus URL
$freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : 0;
$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : ''; // Referral Code

$error = '';
$debug = '';

// Freebie laden
$freebie = null;
try {
    $stmt = $pdo->prepare("
        SELECT cf.*, u.referral_enabled, u.company_name
        FROM customer_freebies cf
        LEFT JOIN users u ON cf.customer_id = u.id
        WHERE cf.id = ? AND cf.customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        die('Freebie nicht gefunden (ID: ' . $freebie_id . ', Customer: ' . $customer_id . ')');
    }
} catch (PDOException $e) {
    die('Datenbankfehler beim Laden des Freebies: ' . $e->getMessage());
}

// Form-Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $name = trim($_POST['name'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    } else {
        try {
            // lead_users Tabelle existiert?
            $stmt = $pdo->query("SHOW TABLES LIKE 'lead_users'");
            if ($stmt->rowCount() === 0) {
                // Tabelle erstellen
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS lead_users (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(255),
                        email VARCHAR(255) NOT NULL,
                        user_id INT NOT NULL,
                        freebie_id INT NULL,
                        referral_code VARCHAR(50) UNIQUE NOT NULL,
                        referrer_id INT NULL,
                        status VARCHAR(50) DEFAULT 'active',
                        created_at DATETIME NOT NULL,
                        INDEX idx_email (email),
                        INDEX idx_user (user_id),
                        INDEX idx_referral_code (referral_code),
                        INDEX idx_freebie (freebie_id),
                        INDEX idx_referrer (referrer_id),
                        INDEX idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                $debug .= "✓ lead_users Tabelle erstellt. ";
            }
            
            // Prüfen ob Lead bereits existiert
            $stmt = $pdo->prepare("
                SELECT id FROM lead_users 
                WHERE email = ? AND user_id = ?
            ");
            $stmt->execute([$email, $customer_id]);
            $existing_lead = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_lead) {
                $lead_id = $existing_lead['id'];
                $debug .= "✓ Lead existiert bereits (ID: $lead_id). ";
            } else {
                // Neuen Lead erstellen
                $referral_code = strtoupper(substr(md5($email . time()), 0, 8));
                
                // Referrer-ID ermitteln
                $referrer_id = null;
                if (!empty($ref)) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM lead_users 
                        WHERE referral_code = ? AND user_id = ?
                    ");
                    $stmt->execute([$ref, $customer_id]);
                    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($referrer) {
                        $referrer_id = $referrer['id'];
                        $debug .= "✓ Referrer gefunden (ID: $referrer_id). ";
                    }
                }
                
                // Lead erstellen
                $stmt = $pdo->prepare("
                    INSERT INTO lead_users 
                    (name, email, user_id, freebie_id, referral_code, referrer_id, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([
                    $name ?: 'Lead',
                    $email,
                    $customer_id,
                    $freebie_id,
                    $referral_code,
                    $referrer_id
                ]);
                $lead_id = $pdo->lastInsertId();
                $debug .= "✓ Lead erstellt (ID: $lead_id, Code: $referral_code). ";
                
                // Referral-Eintrag erstellen
                if ($referrer_id) {
                    try {
                        // lead_referrals Tabelle prüfen
                        $stmt = $pdo->query("SHOW TABLES LIKE 'lead_referrals'");
                        if ($stmt->rowCount() === 0) {
                            $pdo->exec("
                                CREATE TABLE IF NOT EXISTS lead_referrals (
                                    id INT PRIMARY KEY AUTO_INCREMENT,
                                    referrer_id INT NOT NULL,
                                    referred_email VARCHAR(255) NOT NULL,
                                    referred_name VARCHAR(255),
                                    freebie_id INT NULL,
                                    status VARCHAR(50) DEFAULT 'pending',
                                    invited_at DATETIME NOT NULL,
                                    INDEX idx_referrer (referrer_id),
                                    INDEX idx_email (referred_email),
                                    INDEX idx_status (status),
                                    INDEX idx_freebie (freebie_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ");
                        }
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO lead_referrals 
                            (referrer_id, referred_email, referred_name, freebie_id, status, invited_at)
                            VALUES (?, ?, ?, ?, 'active', NOW())
                        ");
                        $stmt->execute([
                            $referrer_id,
                            $email,
                            $name ?: 'Lead',
                            $freebie_id
                        ]);
                        $debug .= "✓ Referral-Eintrag erstellt. ";
                    } catch (PDOException $e) {
                        $debug .= "⚠ Referral-Fehler: " . $e->getMessage() . " ";
                    }
                }
            }
            
            // Session setzen
            $_SESSION['lead_id'] = $lead_id;
            $_SESSION['lead_email'] = $email;
            $_SESSION['lead_customer_id'] = $customer_id;
            $_SESSION['lead_freebie_id'] = $freebie_id;
            
            $debug .= "✓ Session gesetzt. ";
            
            // Redirect zum Dashboard
            $redirect_url = '/lead_dashboard.php?freebie=' . $freebie_id;
            $debug .= "→ Redirect zu: $redirect_url";
            
            header('Location: ' . $redirect_url);
            exit;
            
        } catch (PDOException $e) {
            $error = 'Datenbankfehler: ' . $e->getMessage();
            $debug .= "❌ " . $e->getMessage();
        }
    }
}

$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$company_name = $freebie['company_name'] ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugang freischalten - <?php echo htmlspecialchars($company_name); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, color-mix(in srgb, <?php echo $primary_color; ?> 80%, black));
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
        }
        
        .icon {
            font-size: 60px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
            text-align: center;
        }
        
        p {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 32px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
        }
        
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: <?php echo $primary_color; ?>;
            box-shadow: 0 0 0 3px color-mix(in srgb, <?php echo $primary_color; ?> 20%, white);
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, <?php echo $primary_color; ?>, color-mix(in srgb, <?php echo $primary_color; ?> 80%, black));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
        }
        
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .debug {
            background: #f0f9ff;
            color: #1e40af;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 12px;
            font-family: monospace;
        }
        
        .benefits {
            background: #f9fafb;
            padding: 24px;
            border-radius: 12px;
            margin-top: 32px;
        }
        
        .benefits h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        .benefit {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .benefit i {
            color: <?php echo $primary_color; ?>;
            font-size: 20px;
            margin-top: 2px;
        }
        
        .benefit span {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
        }
        
        .privacy-note {
            margin-top: 24px;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 40px 24px;
            }
            
            h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🚀</div>
        <h1>Zugang freischalten</h1>
        <p>
            Gib deine E-Mail-Adresse ein, um Zugang zu deinen Kursen und dem 
            <?php if ($freebie['referral_enabled']): ?>Empfehlungsprogramm<?php else: ?>Dashboard<?php endif; ?> zu erhalten.
        </p>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($debug): ?>
            <div class="debug">
                🔍 Debug: <?php echo htmlspecialchars($debug); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">E-Mail-Adresse *</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       placeholder="deine@email.de" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="name">Name (optional)</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       placeholder="Max Mustermann">
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-arrow-right"></i> Jetzt freischalten
            </button>
        </form>
        
        <?php if ($freebie['referral_enabled']): ?>
        <div class="benefits">
            <h3>✨ Das erwartet dich:</h3>
            <div class="benefit">
                <i class="fas fa-video"></i>
                <span>Zugang zu exklusiven Videokursen</span>
            </div>
            <div class="benefit">
                <i class="fas fa-gift"></i>
                <span>Verdiene Belohnungen durch Empfehlungen</span>
            </div>
            <div class="benefit">
                <i class="fas fa-chart-line"></i>
                <span>Verfolge deinen Fortschritt im Dashboard</span>
            </div>
        </div>
        <?php endif; ?>
        
        <p class="privacy-note">
            🔒 Deine Daten sind bei uns sicher. Wir geben sie nicht weiter.
        </p>
    </div>
</body>
</html>
