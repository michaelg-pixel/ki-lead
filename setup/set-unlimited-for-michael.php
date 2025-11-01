<?php
/**
 * Quick-Fix: Unbegrenztes Freebie-Limit für michael.gllluska@gmail.com
 * 
 * Einfach im Browser aufrufen: https://deine-domain.de/setup/set-unlimited-for-michael.php
 * 
 * ⚠️ Diese Datei nach Verwendung SOFORT löschen!
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    $email = 'michael.gllluska@gmail.com';
    
    // User finden
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User mit E-Mail {$email} nicht gefunden!");
    }
    
    // Unbegrenztes Limit setzen (999)
    $stmt = $pdo->prepare("
        INSERT INTO customer_freebie_limits (customer_id, freebie_limit, product_id, product_name)
        VALUES (?, 999, 'UNLIMITED_ADMIN', 'Unlimited (Admin gesetzt)')
        ON DUPLICATE KEY UPDATE 
            freebie_limit = 999,
            product_id = 'UNLIMITED_ADMIN',
            product_name = 'Unlimited (Admin gesetzt)',
            updated_at = NOW()
    ");
    $stmt->execute([$user['id']]);
    
    // Überprüfen
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$user['id']]);
    $limit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $success = true;
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unbegrenztes Limit gesetzt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .emoji { font-size: 64px; margin-bottom: 20px; }
        h1 {
            font-size: 32px;
            color: #1a1a2e;
            margin-bottom: 16px;
        }
        .info-box {
            background: #f8fafc;
            padding: 24px;
            border-radius: 12px;
            margin: 24px 0;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            color: #64748b;
        }
        .info-value {
            color: #1a1a2e;
            font-weight: 700;
        }
        .success-value {
            color: #16a34a;
            font-size: 24px;
        }
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            padding: 20px;
            border-radius: 12px;
            color: #dc2626;
            margin: 20px 0;
        }
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            padding: 20px;
            border-radius: 12px;
            margin-top: 24px;
        }
        .warning h3 {
            color: #b91c1c;
            margin-bottom: 8px;
        }
        .warning p {
            color: #dc2626;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        code {
            background: rgba(0,0,0,0.1);
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="emoji">🎉</div>
            <h1>Unbegrenztes Limit gesetzt!</h1>
            
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">User-ID:</span>
                    <span class="info-value"><?php echo $user['id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">E-Mail:</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Freebie-Limit:</span>
                    <span class="info-value success-value">∞ <?php echo $limit['freebie_limit']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Quelle:</span>
                    <span class="info-value"><?php echo htmlspecialchars($limit['product_name']); ?></span>
                </div>
            </div>
            
            <p style="color: #16a34a; font-weight: 600;">
                ✅ Der User kann jetzt unbegrenzt eigene Freebies erstellen!
            </p>
            
            <div class="warning">
                <h3>🔒 WICHTIG: Datei löschen!</h3>
                <p>Lösche diese Datei SOFORT aus Sicherheitsgründen:<br>
                <code>setup/set-unlimited-for-michael.php</code></p>
            </div>
            
            <a href="/admin/users.php" class="btn">
                → Zur User-Verwaltung
            </a>
            
        <?php else: ?>
            <div class="emoji">❌</div>
            <h1>Fehler</h1>
            <div class="error-box">
                <?php echo htmlspecialchars($error ?? 'Unbekannter Fehler'); ?>
            </div>
            <a href="/admin/dashboard.php" class="btn">
                → Zum Dashboard
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
