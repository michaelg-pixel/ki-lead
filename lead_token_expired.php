<?php
/**
 * Token Expired / Invalid - Fehlerseite
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugang abgelaufen</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 600px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .icon {
            font-size: 80px;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
        }
        
        p {
            font-size: 18px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        
        .button {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #8B5CF6, #6d28d9);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.4);
        }
        
        .help-text {
            margin-top: 24px;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏰</div>
        <h1>Zugangslink abgelaufen</h1>
        <p>
            Dein Zugangslink ist leider abgelaufen oder wurde bereits verwendet. 
            Bitte überprüfe deine E-Mails für einen neuen Link oder kontaktiere den Support.
        </p>
        <a href="/lead_login.php" class="button">Zum Login</a>
        <p class="help-text">
            Benötigst du Hilfe? Kontaktiere unseren Support.
        </p>
    </div>
</body>
</html>
