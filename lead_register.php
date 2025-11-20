<?php
/**
 * Lead Registrierung für Dashboard-Zugang
 * Lead gibt selbst seine E-Mail ein und bekommt Zugang zum Empfehlungsprogramm
 * UNTERSTÜTZT: customer_freebies UND freebies (Templates)
 * MULTI-FREEBIE: Lead kann Zugang zu mehreren Freebies haben
 * 🆕 AUTOMATISCHES REFERRAL TRACKING: Referral Code wird aus URL, Session oder Cookie gelesen
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/freebie/track-referral.php'; // 🆕 Tracking Helper

// Session nur starten wenn nicht bereits aktiv
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();

// Parameter aus URL
$freebie_id = isset($_GET['freebie']) ? (int)$_GET['freebie'] : 0;
$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;

// 🆕 REFERRAL CODE aus URL, Session oder Cookie holen
$ref = '';
if (isset($_GET['ref']) && !empty($_GET['ref'])) {
    $ref = trim($_GET['ref']);
} else {
    // Fallback: Aus Session/Cookie holen
    $ref = getReferralCode() ?? '';
}

$error = '';

// Freebie laden - ERST customer_freebies, DANN freebies (Templates)
$freebie = null;
try {
    // Zuerst in customer_freebies suchen
    $stmt = $pdo->prepare("
        SELECT cf.*, u.referral_enabled, u.company_name
        FROM customer_freebies cf
        LEFT JOIN users u ON cf.customer_id = u.id
        WHERE cf.id = ? AND cf.customer_id = ?
    ");
    $stmt->execute([$freebie_id, $customer_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Falls nicht gefunden: In freebies (Templates) suchen
    if (!$freebie) {
        $stmt = $pdo->prepare("
            SELECT f.*, u.referral_enabled, u.company_name
            FROM freebies f
            LEFT JOIN users u ON u.id = ?
            WHERE f.id = ?
        ");
        $stmt->execute([$customer_id, $freebie_id]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$freebie) {
        die('Freebie nicht gefunden (ID: ' . $freebie_id . ', Customer: ' . $customer_id . ')');
    }
} catch (PDOException $e) {
    die('Datenbankfehler beim Laden des Freebies: ' . $e->getMessage());
}

// Rechtstexte laden
$legal_texts = [];
try {
    $stmt = $pdo->prepare("SELECT impressum, datenschutz FROM legal_texts WHERE user_id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $legal_texts = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Fehler beim Laden der Rechtstexte: " . $e->getMessage());
}

// Form-Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $name = 'Lead'; // Standard-Name, da kein Eingabefeld mehr
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Bitte gib eine gültige E-Mail-Adresse ein.';
    } else {
        try {
            // lead_users Tabelle existiert?
            $stmt = $pdo->query("SHOW TABLES LIKE 'lead_users'");
            if ($stmt->rowCount() === 0) {
                // Tabelle mit ALLEN nötigen Spalten erstellen
                $pdo->exec("
                    CREATE TABLE lead_users (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(255),
                        email VARCHAR(255) NOT NULL,
                        password_hash VARCHAR(255) NULL,
                        user_id INT NOT NULL,
                        freebie_id INT NULL,
                        referral_code VARCHAR(50) UNIQUE NOT NULL,
                        referrer_id INT NULL,
                        successful_referrals INT DEFAULT 0,
                        total_referrals INT DEFAULT 0,
                        rewards_earned INT DEFAULT 0,
                        status VARCHAR(50) DEFAULT 'active',
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_email (email),
                        INDEX idx_user (user_id),
                        INDEX idx_referral_code (referral_code),
                        INDEX idx_freebie (freebie_id),
                        INDEX idx_referrer (referrer_id),
                        INDEX idx_status (status)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            } else {
                // Prüfen ob created_at Spalte existiert
                $stmt = $pdo->query("SHOW COLUMNS FROM lead_users LIKE 'created_at'");
                if ($stmt->rowCount() === 0) {
                    $pdo->exec("ALTER TABLE lead_users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
                }
                
                // 🆕 Prüfen ob successful_referrals, total_referrals, rewards_earned existieren
                $columns_to_add = [
                    'successful_referrals' => 'INT DEFAULT 0',
                    'total_referrals' => 'INT DEFAULT 0',
                    'rewards_earned' => 'INT DEFAULT 0'
                ];
                
                foreach ($columns_to_add as $column_name => $column_def) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM lead_users LIKE '{$column_name}'");
                    if ($stmt->rowCount() === 0) {
                        $pdo->exec("ALTER TABLE lead_users ADD COLUMN {$column_name} {$column_def}");
                    }
                }
                
                // password_hash auf NULL setzen (SEPARAT prüfen!)
                $stmt = $pdo->query("SHOW COLUMNS FROM lead_users LIKE 'password_hash'");
                if ($stmt->rowCount() > 0) {
                    $column = $stmt->fetch(PDO::FETCH_ASSOC);
                    // Nur ändern wenn NOT NULL
                    if ($column['Null'] === 'NO') {
                        try {
                            $pdo->exec("ALTER TABLE lead_users MODIFY COLUMN password_hash VARCHAR(255) NULL");
                        } catch (PDOException $e) {
                            // Fehler ignorieren
                        }
                    }
                }
            }
            
            // lead_freebie_access Tabelle existiert?
            $stmt = $pdo->query("SHOW TABLES LIKE 'lead_freebie_access'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("
                    CREATE TABLE lead_freebie_access (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        lead_id INT NOT NULL,
                        freebie_id INT NOT NULL,
                        granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_lead (lead_id),
                        INDEX idx_freebie (freebie_id),
                        UNIQUE KEY unique_access (lead_id, freebie_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
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
            } else {
                // Neuen Lead erstellen
                $referral_code = strtoupper(substr(md5($email . time()), 0, 8));
                
                // 🆕 Referrer-ID ermitteln (mit Logging)
                $referrer_id = null;
                if (!empty($ref)) {
                    $stmt = $pdo->prepare("
                        SELECT id, email FROM lead_users 
                        WHERE referral_code = ? AND user_id = ?
                    ");
                    $stmt->execute([$ref, $customer_id]);
                    $referrer = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($referrer) {
                        $referrer_id = $referrer['id'];
                        error_log("✅ REFERRAL TRACKING: Neuer Lead {$email} wurde von Lead #{$referrer_id} ({$referrer['email']}) empfohlen");
                    } else {
                        error_log("⚠️ REFERRAL CODE '{$ref}' nicht gefunden für Customer #{$customer_id}");
                    }
                }
                
                // Lead erstellen (mit NULL password)
                $stmt = $pdo->prepare("
                    INSERT INTO lead_users 
                    (name, email, password_hash, user_id, freebie_id, referral_code, referrer_id, status, created_at)
                    VALUES (?, ?, NULL, ?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([
                    $name,
                    $email,
                    $customer_id,
                    $freebie_id,
                    $referral_code,
                    $referrer_id
                ]);
                $lead_id = $pdo->lastInsertId();
                
                // 🆕 Referral-Eintrag erstellen + Counter erhöhen
                if ($referrer_id) {
                    try {
                        // lead_referrals Tabelle prüfen/erstellen
                        $stmt = $pdo->query("SHOW TABLES LIKE 'lead_referrals'");
                        if ($stmt->rowCount() === 0) {
                            $pdo->exec("
                                CREATE TABLE lead_referrals (
                                    id INT PRIMARY KEY AUTO_INCREMENT,
                                    referrer_id INT NOT NULL,
                                    referred_email VARCHAR(255) NOT NULL,
                                    referred_name VARCHAR(255),
                                    freebie_id INT NULL,
                                    status VARCHAR(50) DEFAULT 'pending',
                                    invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    INDEX idx_referrer (referrer_id),
                                    INDEX idx_email (referred_email),
                                    INDEX idx_status (status),
                                    INDEX idx_freebie (freebie_id)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                            ");
                        }
                        
                        // Referral-Eintrag erstellen
                        $stmt = $pdo->prepare("
                            INSERT INTO lead_referrals 
                            (referrer_id, referred_email, referred_name, freebie_id, status, invited_at)
                            VALUES (?, ?, ?, ?, 'active', NOW())
                        ");
                        $stmt->execute([
                            $referrer_id,
                            $email,
                            $name,
                            $freebie_id
                        ]);
                        
                        // 🆕 COUNTER ERHÖHEN
                        $stmt = $pdo->prepare("
                            UPDATE lead_users 
                            SET 
                                total_referrals = COALESCE(total_referrals, 0) + 1,
                                successful_referrals = COALESCE(successful_referrals, 0) + 1
                            WHERE id = ?
                        ");
                        $stmt->execute([$referrer_id]);
                        
                        error_log("✅ REFERRAL COUNTER ERHÖHT: Lead #{$referrer_id}");
                        
                    } catch (PDOException $e) {
                        error_log("❌ REFERRAL ERROR: " . $e->getMessage());
                    }
                }
                
                // 🆕 Referral Code aus Session/Cookie löschen nach erfolgreicher Registrierung
                clearReferralCode();
            }
            
            // FREEBIE-ZUGANG GEWÄHREN (auch wenn Lead bereits existiert!)
            try {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO lead_freebie_access 
                    (lead_id, freebie_id, granted_at)
                    VALUES (?, ?, NOW())
                ");
                $stmt->execute([$lead_id, $freebie_id]);
            } catch (PDOException $e) {
                // Fehler ignorieren
            }
            
            // Session setzen
            $_SESSION['lead_id'] = $lead_id;
            $_SESSION['lead_email'] = $email;
            $_SESSION['lead_customer_id'] = $customer_id;
            $_SESSION['lead_freebie_id'] = $freebie_id;
            
            // Redirect zum Dashboard
            header('Location: /lead_dashboard.php?freebie=' . $freebie_id);
            exit;
            
        } catch (PDOException $e) {
            $error = 'Ein Fehler ist aufgetreten. Bitte versuche es erneut.';
            error_log("Lead Registration Error: " . $e->getMessage());
        }
    }
}

$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$company_name = $freebie['company_name'] ?? 'Dashboard';

// 🆕 Debug-Info (nur für Entwicklung, später entfernen)
if (!empty($ref)) {
    error_log("🔍 REFERRAL CODE ERKANNT: {$ref}");
}
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
            flex-direction: column;
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
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .info-box h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1e40af;
            margin-bottom: 8px;
        }
        
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        
        .info-box li {
            font-size: 14px;
            color: #1e40af;
            line-height: 1.6;
            margin-bottom: 6px;
            padding-left: 20px;
            position: relative;
        }
        
        .info-box li:before {
            content: "✓";
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        /* 🆕 Referral Badge */
        .referral-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .referral-badge i {
            margin-right: 8px;
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
        
        input[type="email"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s;
        }
        
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
        
        .legal-footer {
            margin-top: 20px;
            padding-top: 16px;
            text-align: center;
        }
        
        .legal-links {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .legal-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .legal-link:hover {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: underline;
        }
        
        .legal-link i {
            font-size: 10px;
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
        
        <?php if (!empty($ref)): ?>
        <!-- 🆕 Referral Badge anzeigen -->
        <div class="referral-badge">
            <i class="fas fa-gift"></i>
            Du wurdest von einem Freund empfohlen!
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3><i class="fas fa-info-circle"></i> Wichtiger Hinweis:</h3>
            <ul>
                <li>Verwende die E-Mail-Adresse, mit der du dich zukünftig einloggen möchtest</li>
                <li>Speichere diese Seite als Lesezeichen für einfachen Zugang</li>
                <li>Du erhältst keinen Link per E-Mail</li>
                <li>Mit deiner E-Mail bekommst Du Zugang zu deinen Freebie Kursen</li>
            </ul>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
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
    
    <!-- Rechtstexte Footer -->
    <?php if (!empty($legal_texts['impressum']) || !empty($legal_texts['datenschutz'])): ?>
    <div class="legal-footer">
        <div class="legal-links">
            <?php if (!empty($legal_texts['impressum'])): ?>
            <a href="#" onclick="window.open('/legal-pages/impressum.php?customer=<?php echo $customer_id; ?>', '_blank'); return false;" class="legal-link">
                <i class="fas fa-info-circle"></i> Impressum
            </a>
            <?php endif; ?>
            
            <?php if (!empty($legal_texts['datenschutz'])): ?>
            <a href="#" onclick="window.open('/legal-pages/datenschutz.php?customer=<?php echo $customer_id; ?>', '_blank'); return false;" class="legal-link">
                <i class="fas fa-shield-alt"></i> Datenschutzerklärung
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
