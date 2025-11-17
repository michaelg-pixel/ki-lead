<?php
/**
 * Datenschutzerklärung-Seite für Lead-Dashboard
 */

require_once __DIR__ . '/../config/database.php';

$customer_id = isset($_GET['customer']) ? (int)$_GET['customer'] : 0;

if (!$customer_id) {
    die('Ungültige Anfrage');
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("SELECT datenschutz, company_name FROM legal_texts lt LEFT JOIN users u ON lt.user_id = u.id WHERE lt.user_id = ? LIMIT 1");
    $stmt->execute([$customer_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data || empty($data['datenschutz'])) {
        die('Datenschutzerklärung nicht gefunden');
    }
    
    $company_name = $data['company_name'] ?? 'Unternehmen';
    $datenschutz = $data['datenschutz'];
} catch (PDOException $e) {
    die('Fehler beim Laden der Datenschutzerklärung');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenschutzerklärung - <?php echo htmlspecialchars($company_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f1e;
            color: #e0e0e0;
            line-height: 1.6;
        }
        
        .legal-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .legal-content h1 {
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.3);
        }
        
        .legal-content h2 {
            color: #8b9bff;
            font-size: 24px;
            font-weight: 600;
            margin-top: 32px;
            margin-bottom: 16px;
        }
        
        .legal-content h3 {
            color: #a0a8ff;
            font-size: 20px;
            font-weight: 600;
            margin-top: 24px;
            margin-bottom: 12px;
        }
        
        .legal-content p {
            margin-bottom: 16px;
            color: #c0c0c0;
        }
        
        .legal-content ul, .legal-content ol {
            margin-bottom: 16px;
            padding-left: 24px;
        }
        
        .legal-content li {
            margin-bottom: 8px;
            color: #c0c0c0;
        }
        
        .legal-content a {
            color: #667eea;
            text-decoration: underline;
        }
        
        .legal-content a:hover {
            color: #8b9bff;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 24px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="legal-content">
        <a href="javascript:window.close();" class="back-btn">← Fenster schließen</a>
        
        <h1>Datenschutzerklärung</h1>
        
        <div class="content">
            <?php echo nl2br(htmlspecialchars($datenschutz)); ?>
        </div>
    </div>
</body>
</html>
