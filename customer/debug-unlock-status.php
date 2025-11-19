<?php
/**
 * DEBUG: Test ob unlock-status.php geladen wird
 */
session_start();

$customer_id = $_SESSION['user_id'] ?? 0;

// Direkt API testen wenn Button geklickt
if (isset($_GET['test_api'])) {
    require_once '../config/database.php';
    $pdo = getDBConnection();
    
    header('Content-Type: application/json');
    
    try {
        $statusMap = [];
        
        // Alle Templates holen
        $stmt = $pdo->query("SELECT id, name FROM freebies");
        $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Prüfe freigeschaltete Templates
        $stmt = $pdo->prepare("
            SELECT DISTINCT f.id as template_id
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
        $unlockedTemplates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($allTemplates as $template) {
            $isUnlocked = in_array($template['id'], $unlockedTemplates);
            $statusMap['template_' . $template['id']] = [
                'unlock_status' => $isUnlocked ? 'unlocked' : 'locked',
                'name' => $template['name']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'customer_id' => $customer_id,
            'total_templates' => count($allTemplates),
            'unlocked_count' => count($unlockedTemplates),
            'statuses' => $statusMap
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'error' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Unlock Status Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
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
            vertical-align: middle;
        }
        .unlocked { background: #22c55e; }
        .locked { background: #ef4444; }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        button:hover {
            background: #5568d3;
        }
        pre {
            background: rgba(0,0,0,0.5);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <h1>🔍 Unlock Status Debug</h1>
    
    <div class="test-box">
        <h3>Session Status:</h3>
        <p>Customer ID: <strong><?php echo $customer_id; ?></strong></p>
        <p>Eingeloggt: <strong class="<?php echo $customer_id > 0 ? 'success' : 'error'; ?>">
            <?php echo $customer_id > 0 ? '✓ Ja' : '✗ Nein - BITTE EINLOGGEN!'; ?>
        </strong></p>
    </div>
    
    <?php if ($customer_id > 0): ?>
    <div class="test-box">
        <h3>🧪 API Test:</h3>
        <p><a href="?test_api=1" target="_blank"><button>🚀 API direkt testen (neuer Tab)</button></a></p>
        <p style="color: #888; font-size: 12px;">Öffnet die API-Antwort in einem neuen Tab</p>
    </div>
    
    <div class="test-box">
        <h3>Status-Punkte Beispiel:</h3>
        <p>Freigeschaltet <span class="status-dot unlocked"></span> (Grün)</p>
        <p>Gesperrt <span class="status-dot locked"></span> (Rot)</p>
    </div>
    
    <div class="test-box">
        <h3>Datenbankprüfung:</h3>
        <?php
        require_once '../config/database.php';
        $pdo = getDBConnection();
        
        try {
            // Prüfe Templates
            $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
            $templateCount = $stmt->fetchColumn();
            echo "<p class='success'>✓ Templates in DB: <strong>$templateCount</strong></p>";
            
            // Prüfe Webhooks
            $stmt = $pdo->query("SELECT COUNT(*) FROM webhook_configurations WHERE is_active = 1");
            $webhookCount = $stmt->fetchColumn();
            echo "<p class='success'>✓ Aktive Webhooks: <strong>$webhookCount</strong></p>";
            
            // Prüfe Produktkäufe
            $stmt = $pdo->prepare("SELECT product_id, product_name FROM customer_freebie_limits WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p class='success'>✓ Deine Produktkäufe: <strong>" . count($purchases) . "</strong></p>";
            
            if (!empty($purchases)) {
                echo "<ul>";
                foreach ($purchases as $purchase) {
                    echo "<li>" . htmlspecialchars($purchase['product_name']) . " (ID: " . htmlspecialchars($purchase['product_id']) . ")</li>";
                }
                echo "</ul>";
            }
            
            // Prüfe Webhook-Verknüpfungen
            $stmt = $pdo->query("
                SELECT 
                    wc.name as webhook_name,
                    GROUP_CONCAT(wpi.product_id) as product_ids,
                    COUNT(DISTINCT wca.course_id) as course_count
                FROM webhook_configurations wc
                LEFT JOIN webhook_product_ids wpi ON wc.id = wpi.webhook_id
                LEFT JOIN webhook_course_access wca ON wc.id = wca.webhook_id
                WHERE wc.is_active = 1
                GROUP BY wc.id
            ");
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>🔗 Aktive Webhook-Konfigurationen:</strong></p>";
            if (empty($webhooks)) {
                echo "<p class='error'>✗ Keine aktiven Webhooks konfiguriert!</p>";
            } else {
                echo "<ul>";
                foreach ($webhooks as $webhook) {
                    echo "<li class='success'>";
                    echo htmlspecialchars($webhook['webhook_name']);
                    echo " - Produkt-IDs: " . htmlspecialchars($webhook['product_ids']);
                    echo " - Kurse: " . $webhook['course_count'];
                    echo "</li>";
                }
                echo "</ul>";
            }
            
            // Prüfe freigeschaltete Templates
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
            
            echo "<p><strong>🟢 Für dich freigeschaltete Templates:</strong></p>";
            if (empty($unlockedTemplates)) {
                echo "<p class='error'>✗ Keine Templates freigeschaltet</p>";
                echo "<p style='color: #fbbf24; font-size: 14px;'>💡 Das bedeutet: Entweder hast du kein Produkt gekauft, oder die Webhooks sind nicht richtig mit Kursen verknüpft.</p>";
            } else {
                echo "<ul>";
                foreach ($unlockedTemplates as $template) {
                    echo "<li class='success'>✓ " . htmlspecialchars($template['name']) . " (ID: " . $template['id'] . ") <span class='status-dot unlocked'></span></li>";
                }
                echo "</ul>";
            }
            
            // Zeige ALLE Templates
            $stmt = $pdo->query("SELECT id, name FROM freebies");
            $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $unlockedIds = array_column($unlockedTemplates, 'id');
            
            echo "<p><strong>📚 Alle Templates (mit Status):</strong></p>";
            echo "<ul>";
            foreach ($allTemplates as $template) {
                $isUnlocked = in_array($template['id'], $unlockedIds);
                $statusClass = $isUnlocked ? 'success' : 'error';
                $statusIcon = $isUnlocked ? '✓' : '✗';
                $dotClass = $isUnlocked ? 'unlocked' : 'locked';
                
                echo "<li class='$statusClass'>$statusIcon " . htmlspecialchars($template['name']) . " <span class='status-dot $dotClass'></span></li>";
            }
            echo "</ul>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Datenbankfehler: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h3>🔧 Problemlösung:</h3>
        <ol>
            <li>Stelle sicher dass im <strong>Admin Dashboard</strong> ein Webhook mit Videokurs verknüpft ist</li>
            <li>Der Webhook muss eine <strong>Produkt-ID</strong> haben</li>
            <li>Diese Produkt-ID muss in deiner <strong>customer_freebie_limits</strong> Tabelle stehen</li>
            <li>Der Videokurs muss <strong>aktiv</strong> sein (is_active = 1)</li>
        </ol>
    </div>
    <?php else: ?>
    <div class="test-box">
        <p class="error" style="font-size: 18px;">⚠️ Du musst eingeloggt sein um die Tests durchzuführen!</p>
        <p><a href="/customer/login.php" style="color: #667eea;">→ Zum Login</a></p>
    </div>
    <?php endif; ?>
</body>
</html>