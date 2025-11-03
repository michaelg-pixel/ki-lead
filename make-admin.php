<?php
/**
 * 🔐 ADMIN-UPGRADE TOOL
 * Macht einen User temporär zum Admin für die Migration
 * 
 * WICHTIG: Nach der Migration wieder löschen!
 */

require_once __DIR__ . '/config/database.php';

// Sicherheits-Token (ändere dies!)
$SECURITY_TOKEN = 'migration2024secure'; // ÄNDERE DIES!

// Token-Check
$token = $_GET['token'] ?? '';

if ($token !== $SECURITY_TOKEN) {
    die('⛔ Ungültiger Security-Token! Bitte Token in der URL angeben: ?token=DEIN_TOKEN');
}

try {
    $pdo = getDBConnection();
    
    // Finde User mit deiner E-Mail
    $email = 'michael.gllluska@gmail.com';
    
    // Prüfe ob User existiert
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die("❌ User mit E-Mail $email nicht gefunden!");
    }
    
    // Aktualisiere zu Admin
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?");
    $stmt->execute([$email]);
    
    // Prüfe Erfolg
    $stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>✅ Admin-Upgrade erfolgreich</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                max-width: 600px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 32px;
                color: #1a1a2e;
                margin-bottom: 15px;
            }
            .info {
                background: #f0fdf4;
                border: 2px solid #10b981;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 10px 0;
                border-bottom: 1px solid #d1fae5;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .label {
                font-weight: 600;
                color: #065f46;
            }
            .value {
                color: #10b981;
                font-family: 'Courier New', monospace;
            }
            .steps {
                background: #fef3c7;
                border: 2px solid #f59e0b;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            }
            .steps h3 {
                color: #92400e;
                margin-bottom: 15px;
            }
            .steps ol {
                margin-left: 20px;
            }
            .steps li {
                margin: 10px 0;
                color: #78350f;
            }
            .btn {
                display: inline-block;
                padding: 15px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 10px;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .warning {
                background: #fee2e2;
                border: 2px solid #ef4444;
                border-radius: 10px;
                padding: 15px;
                margin-top: 20px;
                color: #991b1b;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✅</div>
            <h1>Admin-Upgrade erfolgreich!</h1>
            <p style="color: #6b7280; margin-bottom: 20px;">
                Dein Account wurde erfolgreich zum Admin hochgestuft.
            </p>
            
            <div class="info">
                <div class="info-row">
                    <span class="label">User-ID:</span>
                    <span class="value"><?php echo $updatedUser['id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="label">E-Mail:</span>
                    <span class="value"><?php echo htmlspecialchars($updatedUser['email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="label">Alte Rolle:</span>
                    <span class="value" style="text-decoration: line-through;">customer</span>
                </div>
                <div class="info-row">
                    <span class="label">Neue Rolle:</span>
                    <span class="value" style="color: #10b981; font-weight: 700;">admin ✓</span>
                </div>
            </div>
            
            <div class="steps">
                <h3>📋 Nächste Schritte:</h3>
                <ol>
                    <li><strong>Logout:</strong> Melde dich ab</li>
                    <li><strong>Login:</strong> Melde dich wieder an</li>
                    <li><strong>Prüfen:</strong> Rufe check-session.php auf</li>
                    <li><strong>Migration:</strong> Starte migrate-browser.php</li>
                    <li><strong>Cleanup:</strong> Lösche make-admin.php</li>
                </ol>
            </div>
            
            <a href="/logout.php" class="btn">1️⃣ Jetzt abmelden</a>
            <a href="/check-session.php" class="btn">2️⃣ Session prüfen</a>
            
            <div class="warning">
                ⚠️ WICHTIG: Lösche die Datei "make-admin.php" nach der Migration!
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>❌ Fehler</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                max-width: 600px;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            h1 {
                font-size: 32px;
                color: #1a1a2e;
                margin-bottom: 15px;
            }
            .error {
                background: #fee2e2;
                border: 2px solid #ef4444;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
                color: #991b1b;
                font-family: 'Courier New', monospace;
                text-align: left;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">❌</div>
            <h1>Fehler aufgetreten</h1>
            <div class="error">
                <?php echo htmlspecialchars($e->getMessage()); ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
