<?php
/**
 * 🔧 MIGRATIONS-DEBUG-TOOL
 * Zeigt Session-Variablen an um herauszufinden welche Admin-Check verwendet wird
 */

session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Session Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #00ff00;
            padding: 40px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #0f0f1e;
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #00ff00;
            text-shadow: 0 0 10px #00ff00;
        }
        .section {
            background: #16162e;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 3px solid #00ff00;
        }
        .section h2 {
            color: #00ffff;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .var {
            padding: 10px;
            margin: 5px 0;
            background: #0a0a1a;
            border-radius: 3px;
        }
        .var-name {
            color: #ffaa00;
            font-weight: bold;
        }
        .var-value {
            color: #00ff00;
        }
        .empty {
            color: #ff0000;
            font-style: italic;
        }
        .success {
            color: #00ff00;
        }
        .error {
            color: #ff0000;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            background: #00ff00;
            color: #0f0f1e;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }
        .recommendation {
            background: #2a2a4e;
            border: 2px solid #ffaa00;
            border-left: 5px solid #ffaa00;
            padding: 20px;
            margin-top: 30px;
            border-radius: 5px;
        }
        .recommendation h3 {
            color: #ffaa00;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 SESSION DEBUG TOOL</h1>
        
        <div class="section">
            <h2>📋 AKTUELLE SESSION-VARIABLEN</h2>
            <?php if (empty($_SESSION)): ?>
                <div class="var empty">❌ Keine Session-Variablen gefunden!</div>
            <?php else: ?>
                <?php foreach ($_SESSION as $key => $value): ?>
                    <div class="var">
                        <span class="var-name">$_SESSION['<?php echo $key; ?>']</span> = 
                        <span class="var-value">
                            <?php 
                                if (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } elseif (is_array($value)) {
                                    echo json_encode($value, JSON_PRETTY_PRINT);
                                } else {
                                    echo htmlspecialchars($value);
                                }
                            ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2>🔍 ADMIN-CHECK ANALYSE</h2>
            
            <div class="var">
                <span class="var-name">Login-Check:</span>
                <span class="var-value <?php echo (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) ? 'success' : 'error'; ?>">
                    <?php echo (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) ? '✅ Eingeloggt' : '❌ Nicht eingeloggt'; ?>
                </span>
            </div>
            
            <div class="var">
                <span class="var-name">Admin-Check (is_admin):</span>
                <span class="var-value <?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ? 'success' : 'error'; ?>">
                    <?php echo (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) ? '✅ Admin' : '❌ Kein Admin'; ?>
                </span>
            </div>
            
            <div class="var">
                <span class="var-name">Admin-Check (role):</span>
                <span class="var-value <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? 'success' : 'error'; ?>">
                    <?php echo (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') ? '✅ Admin' : '❌ Kein Admin'; ?>
                </span>
            </div>
        </div>
        
        <?php
        // Empfehlung ausgeben
        $isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['customer_id']);
        $isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) || 
                   (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
        ?>
        
        <div class="recommendation">
            <h3>💡 EMPFEHLUNG:</h3>
            <?php if (!$isLoggedIn): ?>
                <p class="error">
                    ❌ Du bist nicht eingeloggt!<br>
                    → Bitte logge dich zuerst im Admin-Bereich ein.
                </p>
                <a href="/admin/dashboard.php" class="btn">Zum Admin-Login</a>
            <?php elseif (!$isAdmin): ?>
                <p class="error">
                    ❌ Du bist eingeloggt, aber kein Administrator!<br>
                    → Nur Admins können die Migration durchführen.
                </p>
            <?php else: ?>
                <p class="success">
                    ✅ Perfekt! Du hast Admin-Rechte.<br>
                    → Du kannst jetzt das Migrations-Tool verwenden.
                </p>
                <a href="/migrate-browser.php" class="btn">Zum Migrations-Tool →</a>
            <?php endif; ?>
        </div>
        
        <div class="section" style="margin-top: 30px;">
            <h2>📖 WAS SIND DIE RICHTIGEN SESSION-VARIABLEN?</h2>
            <p style="color: #fff; line-height: 1.8;">
                Das Migrations-Tool sucht nach folgenden Session-Variablen:<br><br>
                
                <strong style="color: #00ffff;">Für Login:</strong><br>
                • $_SESSION['user_id'] ODER<br>
                • $_SESSION['customer_id']<br><br>
                
                <strong style="color: #00ffff;">Für Admin-Rechte:</strong><br>
                • $_SESSION['is_admin'] = true ODER<br>
                • $_SESSION['role'] = 'admin'<br><br>
                
                <?php if (isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])): ?>
                    ✅ Login-Variable gefunden!<br>
                <?php else: ?>
                    ❌ Keine Login-Variable gefunden!<br>
                <?php endif; ?>
                
                <?php if ((isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) || 
                          (isset($_SESSION['role']) && $_SESSION['role'] === 'admin')): ?>
                    ✅ Admin-Variable gefunden!<br>
                <?php else: ?>
                    ❌ Keine Admin-Variable gefunden!<br>
                <?php endif; ?>
            </p>
        </div>
    </div>
</body>
</html>
