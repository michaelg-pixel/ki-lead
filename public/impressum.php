<?php
/**
 * Impressum-Anzeige für Freebie-Verkäufer
 * URL-Format: /impressum.php?user=USER_ID
 */

require_once __DIR__ . '/../config/database.php';

$userId = $_GET['user'] ?? null;

if (!$userId) {
    die('Fehler: Keine Benutzer-ID angegeben');
}

try {
    $pdo = getDBConnection();
    
    // Rechtstexte laden
    $stmt = $pdo->prepare("SELECT impressum FROM legal_texts WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result || empty(trim($result['impressum']))) {
        die('Impressum nicht gefunden');
    }
    
    $impressum = $result['impressum'];
    
} catch (Exception $e) {
    error_log("Impressum Load Error: " . $e->getMessage());
    die('Fehler beim Laden des Impressums');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 32px;
            text-align: center;
            color: white;
        }
        
        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 32px;
            color: #1a1a2e;
        }
        
        .impressum-text {
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.8;
        }
        
        .back-button {
            display: inline-block;
            margin-top: 32px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 640px) {
            body {
                padding: 12px;
            }
            
            .container {
                margin: 20px auto;
            }
            
            .header {
                padding: 32px 24px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .content {
                padding: 32px 24px;
            }
            
            .impressum-text {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 Impressum</h1>
            <p>Angaben gemäß § 5 TMG</p>
        </div>
        
        <div class="content">
            <div class="impressum-text"><?php echo nl2br(htmlspecialchars($impressum)); ?></div>
            
            <a href="javascript:history.back()" class="back-button">
                ← Zurück
            </a>
        </div>
    </div>
</body>
</html>