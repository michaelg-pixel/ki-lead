<?php
/**
 * DEBUG: Test ob unlock-status.php geladen wird
 */
session_start();

$customer_id = $_SESSION['user_id'] ?? 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Unlock Status Debug</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #1a1a2e;
            color: white;
        }
        .test-box {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
        }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .status-dot {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid white;
            display: inline-block;
            margin-left: 10px;
        }
        .unlocked { background: #22c55e; }
        .locked { background: #ef4444; }
    </style>
</head>
<body>
    <h1>🔍 Unlock Status Debug</h1>
    
    <div class="test-box">
        <h3>Session Status:</h3>
        <p>Customer ID: <strong><?php echo $customer_id; ?></strong></p>
        <p>Eingeloggt: <strong class="<?php echo $customer_id > 0 ? 'success' : 'error'; ?>">
            <?php echo $customer_id > 0 ? '✓ Ja' : '✗ Nein'; ?>
        </strong></p>
    </div>
    
    <div class="test-box">
        <h3>JavaScript Test:</h3>
        <button onclick="testAPI()">API Testen</button>
        <div id="apiResult"></div>
    </div>
    
    <div class="test-box">
        <h3>Status-Punkte Test:</h3>
        <p>Freigeschaltet: <span class="status-dot unlocked"></span></p>
        <p>Gesperrt: <span class="status-dot locked"></span></p>
    </div>
    
    <div class="test-box">
        <h3>Datenbankprüfung:</h3>
        <?php
        require_once '../../config/database.php';
        $pdo = getDBConnection();
        
        try {
            // Prüfe Templates
            $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
            $templateCount = $stmt->fetchColumn();
            echo "<p>✓ Templates in DB: <strong>$templateCount</strong></p>";
            
            // Prüfe Webhooks
            $stmt = $pdo->query("SELECT COUNT(*) FROM webhook_configurations WHERE is_active = 1");
            $webhookCount = $stmt->fetchColumn();
            echo "<p>✓ Aktive Webhooks: <strong>$webhookCount</strong></p>";
            
            // Prüfe Produktkäufe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebie_limits WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $purchaseCount = $stmt->fetchColumn();
            echo "<p>✓ Produktkäufe: <strong>$purchaseCount</strong></p>";
            
            // Prüfe freigeschaltete Templates
            if ($customer_id > 0) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT f.id, f.name
                    FROM freebies f
                    INNER JOIN courses c ON c.is_active = 1
                    INNER JOIN webhook_course_access wca ON c.id = wca.course_id
                    INNER JOIN webhook_configurations wc ON wca.webhook_id = wc.id AND wc.is_active = 1
                    INNER JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
                    WHERE EXISTS (
                        SELECT 1 FROM customer_freebie_limits cfl 
                        WHERE cfl.customer_id = :customer_id 
                        AND cfl.product_id = wpi.product_id
                    )
                ");
                $stmt->execute(['customer_id' => $customer_id]);
                $unlockedTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p><strong>Freigeschaltete Templates:</strong></p>";
                if (empty($unlockedTemplates)) {
                    echo "<p class='error'>✗ Keine Templates freigeschaltet</p>";
                } else {
                    echo "<ul>";
                    foreach ($unlockedTemplates as $template) {
                        echo "<li class='success'>✓ " . htmlspecialchars($template['name']) . " (ID: " . $template['id'] . ")</li>";
                    }
                    echo "</ul>";
                }
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <script>
    async function testAPI() {
        const result = document.getElementById('apiResult');
        result.innerHTML = '<p>⏳ Lade...</p>';
        
        try {
            const response = await fetch('/customer/api/check-freebie-unlock-status.php');
            const data = await response.json();
            
            result.innerHTML = '<pre style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; overflow-x: auto;">' + 
                JSON.stringify(data, null, 2) + 
                '</pre>';
        } catch (error) {
            result.innerHTML = '<p class="error">✗ Fehler: ' + error.message + '</p>';
        }
    }
    </script>
</body>
</html>