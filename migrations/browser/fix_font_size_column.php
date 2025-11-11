<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Nur für Admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('❌ Keine Berechtigung');
}

$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute'])) {
    try {
        $pdo = getDBConnection();
        
        // Spalte zu TEXT ändern
        $pdo->exec("ALTER TABLE customer_freebies MODIFY COLUMN font_size TEXT");
        
        $success = true;
        
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Font Size Column Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            border-radius: 16px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .info-box h3 {
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #1e3a8a;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .code-box {
            background: #1a1a2e;
            color: #00ff00;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-bottom: 24px;
            overflow-x: auto;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Font Size Column Migration</h1>
        <p class="subtitle">Datenbank-Update für Pixel-basierte Schriftgrößen</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ <strong>Migration erfolgreich!</strong><br>
                Die font_size Spalte wurde zu TEXT geändert und kann jetzt JSON-Daten speichern.
            </div>
            <a href="/customer/dashboard.php" class="back-link">← Zurück zum Dashboard</a>
        <?php elseif ($error): ?>
            <div class="alert alert-error">
                ❌ <strong>Fehler:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>ℹ️ Was macht diese Migration?</h3>
                <p>
                    Diese Migration ändert die <code>font_size</code> Spalte in der <code>customer_freebies</code> Tabelle 
                    von einem kleinen VARCHAR zu TEXT, um JSON-Daten für die individuellen Pixel-Werte zu speichern.
                </p>
            </div>
            
            <div class="code-box">
                ALTER TABLE customer_freebies<br>
                MODIFY COLUMN font_size TEXT;
            </div>
            
            <form method="POST">
                <button type="submit" name="execute" class="btn btn-primary">
                    ▶️ Migration jetzt ausführen
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
