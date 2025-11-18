<?php
/**
 * WHOAMI - Zeigt aktuelle Session-Informationen
 */

session_start();

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 Wer bin ich?</title>
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
        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            font-size: 36px; 
            color: #1a1a2e; 
            margin-bottom: 24px;
            text-align: center;
        }
        .session-info {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            font-family: monospace;
            color: #1a1a2e;
            word-break: break-all;
        }
        .alert {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #dc2626;
            padding: 20px;
            border-radius: 12px;
            font-weight: 600;
            text-align: center;
        }
        .success {
            background: #dcfce7;
            border: 2px solid #22c55e;
            color: #15803d;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn:hover { transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="card">
        <h1>👤 Session Info</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="success">
                ✅ Du bist eingeloggt!
            </div>
            
            <div class="session-info">
                <div class="info-row">
                    <div class="info-label">User ID:</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['name'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Rolle:</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SESSION['role'] ?? 'N/A'); ?></div>
                </div>
                
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <div class="info-row">
                        <div class="info-label">Customer ID:</div>
                        <div class="info-value"><?php echo htmlspecialchars($_SESSION['customer_id']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="background: #f0f9ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 8px; margin: 20px 0;">
                <strong>📊 Erwartete Werte für Susi Sorglos:</strong><br>
                <ul style="margin-top: 12px; padding-left: 20px;">
                    <li>User ID: <strong>7</strong></li>
                    <li>Email: <strong>10@abnehmen-fitness.com</strong></li>
                    <li>Name: <strong>Susi Sorglos</strong></li>
                </ul>
            </div>
            
            <?php if ($_SESSION['user_id'] == 7): ?>
                <div class="success">
                    ✅ Korrekt! Du bist als Susi Sorglos (User ID 7) eingeloggt!
                </div>
            <?php else: ?>
                <div class="alert">
                    ⚠️ ACHTUNG: Du bist NICHT als Susi Sorglos eingeloggt!<br>
                    Du bist als User ID <?php echo $_SESSION['user_id']; ?> eingeloggt!
                </div>
            <?php endif; ?>
            
            <h3 style="margin: 24px 0 16px 0; color: #1a1a2e;">🔍 Alle Session-Variablen:</h3>
            <div class="session-info">
                <?php foreach ($_SESSION as $key => $value): ?>
                    <div class="info-row">
                        <div class="info-label"><?php echo htmlspecialchars($key); ?>:</div>
                        <div class="info-value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="dashboard.php?page=freebies" class="btn">
                🎁 Zu Meinen Freebies
            </a>
            
        <?php else: ?>
            <div class="alert">
                ❌ Du bist NICHT eingeloggt!
            </div>
            
            <a href="../public/login.php" class="btn">
                🔐 Zum Login
            </a>
        <?php endif; ?>
    </div>
</body>
</html>