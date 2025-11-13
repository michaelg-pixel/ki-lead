<?php
/**
 * Neue Danke-Seite mit One-Click-Login zum vereinten Lead-Dashboard
 * Zeigt nur noch einen Button zum neuen Lead-Dashboard
 */

require_once __DIR__ . '/../config/database.php';

// Parameter aus URL
$freebie_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id_from_url = isset($_GET['customer']) ? (int)$_GET['customer'] : null;
$lead_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$lead_name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($freebie_id <= 0) {
    die('Ungültige Freebie-ID');
}

if (empty($lead_email) || !filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    die('Keine gültige E-Mail-Adresse angegeben');
}

$pdo = getDBConnection();

// Freebie und Customer-Info laden
$customer_id = $customer_id_from_url;
$is_customer_freebie = false;
$referral_enabled = 0;
$ref_code = '';

try {
    // Customer-Freebie prüfen
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            cf.customer_id,
            u.referral_enabled,
            u.ref_code,
            u.company_name
        FROM customer_freebies cf
        JOIN users u ON cf.customer_id = u.id
        WHERE cf.id = ?
    ");
    $stmt->execute([$freebie_id]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'];
        $is_customer_freebie = true;
        $referral_enabled = (int)$freebie['referral_enabled'];
        $ref_code = $freebie['ref_code'] ?? '';
    } else {
        // Template-Freebie
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE id = ?");
        $stmt->execute([$freebie_id]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$freebie) {
            die('Freebie nicht gefunden');
        }
        
        // Customer-Info laden wenn vorhanden
        if ($customer_id_from_url) {
            $customer_id = $customer_id_from_url;
            $stmt = $pdo->prepare("SELECT referral_enabled, ref_code, company_name FROM users WHERE id = ?");
            $stmt->execute([$customer_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $referral_enabled = (int)$user['referral_enabled'];
                $ref_code = $user['ref_code'];
            }
        }
    }
} catch (PDOException $e) {
    die('Datenbankfehler: ' . $e->getMessage());
}

// One-Click-Login-Token generieren
$login_token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

try {
    // Token in Datenbank speichern
    $stmt = $pdo->prepare("
        INSERT INTO lead_login_tokens 
        (token, email, name, customer_id, freebie_id, expires_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        token = VALUES(token),
        expires_at = VALUES(expires_at)
    ");
    $stmt->execute([$login_token, $lead_email, $lead_name, $customer_id, $freebie_id, $expires_at]);
} catch (PDOException $e) {
    // Wenn Tabelle nicht existiert, erstelle sie
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lead_login_tokens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                token VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) NOT NULL,
                name VARCHAR(255),
                customer_id INT,
                freebie_id INT,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                INDEX idx_token (token),
                INDEX idx_email (email),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Nochmal versuchen
        $stmt = $pdo->prepare("
            INSERT INTO lead_login_tokens 
            (token, email, name, customer_id, freebie_id, expires_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$login_token, $lead_email, $lead_name, $customer_id, $freebie_id, $expires_at]);
    } catch (PDOException $e2) {
        die('Fehler beim Erstellen des Login-Tokens: ' . $e2->getMessage());
    }
}

// One-Click-Login URL generieren
$login_url = '/lead-dashboard-unified.php?token=' . $login_token;

// Styling
$primary_color = $freebie['primary_color'] ?? '#8B5CF6';
$background_color = $freebie['background_color'] ?? '#FFFFFF';
$headline_font = $freebie['headline_font'] ?? 'Poppins';
$body_font = $freebie['body_font'] ?? 'Poppins';

$company_name = $freebie['company_name'] ?? 'Dein Freebie';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vielen Dank! Dein Zugang ist bereit</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: <?php echo $primary_color; ?>;
            --primary-dark: color-mix(in srgb, <?php echo $primary_color; ?> 80%, black);
            --primary-light: color-mix(in srgb, <?php echo $primary_color; ?> 20%, white);
        }
        
        body {
            font-family: '<?php echo $body_font; ?>', sans-serif;
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
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 24px;
            animation: bounce 1s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        h1 {
            font-family: '<?php echo $headline_font; ?>', sans-serif;
            font-size: 36px;
            font-weight: 900;
            color: #1a1a1a;
            margin-bottom: 16px;
            line-height: 1.2;
        }
        
        .subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .success-message {
            background: #f0fdf4;
            border: 2px solid #86efac;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 32px;
        }
        
        .success-message p {
            color: #166534;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .success-message small {
            color: #15803d;
            font-size: 13px;
        }
        
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 20px 48px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 800;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .cta-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .button-icon {
            font-size: 24px;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid #e5e7eb;
        }
        
        .feature {
            text-align: center;
        }
        
        .feature-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }
        
        .feature-text {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }
        
        .security-note {
            margin-top: 32px;
            padding: 16px;
            background: #f3f4f6;
            border-radius: 12px;
            font-size: 13px;
            color: #6b7280;
            line-height: 1.6;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 32px 24px;
            }
            
            h1 {
                font-size: 28px;
            }
            
            .subtitle {
                font-size: 16px;
            }
            
            .cta-button {
                width: 100%;
                padding: 18px 32px;
                font-size: 18px;
            }
            
            .features {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-icon">🎉</div>
        
        <h1>Vielen Dank, <?php echo htmlspecialchars($lead_name ?: 'für deine Anmeldung'); ?>!</h1>
        
        <p class="subtitle">
            Dein Zugang ist erfolgreich aktiviert. Klicke auf den Button unten, 
            um direkt zu deinem persönlichen Dashboard zu gelangen.
        </p>
        
        <div class="success-message">
            <p>✓ Deine E-Mail wurde bestätigt</p>
            <small><?php echo htmlspecialchars($lead_email); ?></small>
        </div>
        
        <a href="<?php echo htmlspecialchars($login_url); ?>" class="cta-button">
            <span class="button-icon">🚀</span>
            <span>Zum Dashboard</span>
        </a>
        
        <div class="features">
            <div class="feature">
                <div class="feature-icon">📚</div>
                <div class="feature-text">Sofortiger Zugang zu allen Kursen</div>
            </div>
            
            <?php if ($referral_enabled): ?>
            <div class="feature">
                <div class="feature-icon">🎁</div>
                <div class="feature-text">Empfehlungsprogramm mit Belohnungen</div>
            </div>
            <?php endif; ?>
            
            <div class="feature">
                <div class="feature-icon">📊</div>
                <div class="feature-text">Fortschritt verfolgen</div>
            </div>
        </div>
        
        <div class="security-note">
            🔒 <strong>Sicherer Zugang:</strong> Dieser Link ist 24 Stunden gültig und nur für deine E-Mail-Adresse bestimmt. 
            Speichere diese Seite als Lesezeichen für zukünftigen Zugriff.
        </div>
    </div>
</body>
</html>