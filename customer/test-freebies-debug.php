<?php
session_start();
require_once '../config/database.php';

// Prüfe Session
if (!isset($_SESSION['user_id'])) {
    die('❌ Nicht eingeloggt! Bitte zuerst einloggen.');
}

$customer_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Freebies Debug Test</title>
    <style>
        body {
            background: #1a1a2e;
            color: white;
            font-family: Arial, sans-serif;
            padding: 40px;
        }
        
        .test-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        
        .status-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 3px solid white;
            display: inline-block;
            margin: 0 10px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.5);
        }
        
        .status-unlocked {
            background: #22c55e;
        }
        
        .status-locked {
            background: #ef4444;
        }
        
        pre {
            background: rgba(0, 0, 0, 0.5);
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Freebies Debug Test</h1>
    
    <div class="test-box">
        <h2>✓ Test 1: Session Check</h2>
        <p class="success">✓ Du bist eingeloggt als Customer ID: <?php echo $customer_id; ?></p>
    </div>
    
    <div class="test-box">
        <h2>🎨 Test 2: CSS Status-Punkte</h2>
        <p>Statische Test-Punkte (sollten sofort sichtbar sein):</p>
        <div class="status-dot status-unlocked"></div> Grüner Punkt (freigeschaltet)
        <div class="status-dot status-locked"></div> Roter Punkt (gesperrt)
    </div>
    
    <div class="test-box">
        <h2>📡 Test 3: API Direkt-Aufruf</h2>
        <p>Rufe API auf...</p>
        <?php
        try {
            $pdo = getDBConnection();
            
            // Direkt die Unlock-Logik ausführen
            $stmt = $pdo->query("SELECT id, name FROM freebies ORDER BY created_at DESC");
            $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p class='success'>✓ {count($allTemplates)} Templates gefunden:</p>";
            echo "<ul>";
            foreach ($allTemplates as $t) {
                echo "<li>Template {$t['id']}: {$t['name']}</li>";
            }
            echo "</ul>";
            
            // Prüfe welche Templates freigeschaltet sind
            $stmt = $pdo->prepare("
                SELECT DISTINCT f.id as template_id, f.name
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
            
            echo "<p class='success'>✓ {count($unlockedTemplates)} Templates freigeschaltet:</p>";
            if (count($unlockedTemplates) > 0) {
                echo "<ul>";
                foreach ($unlockedTemplates as $t) {
                    echo "<li class='success'>🟢 Template {$t['template_id']}: {$t['name']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='error'>❌ Keine Templates freigeschaltet!</p>";
                echo "<p>Mögliche Gründe:</p>";
                echo "<ul>";
                echo "<li>Kein Produktkauf in customer_freebie_limits</li>";
                echo "<li>Keine webhook_configurations aktiv</li>";
                echo "<li>Keine webhook_course_access Verknüpfungen</li>";
                echo "</ul>";
            }
            
            // Zeige customer_freebie_limits
            $stmt = $pdo->prepare("SELECT * FROM customer_freebie_limits WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $limits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p class='info'>📦 Deine Produktkäufe ({count($limits)}):</p>";
            if (count($limits) > 0) {
                echo "<pre>" . print_r($limits, true) . "</pre>";
            } else {
                echo "<p class='error'>❌ Keine Produktkäufe gefunden in customer_freebie_limits!</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>⚡ Test 4: JavaScript API Call</h2>
        <button onclick="testAPI()" style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
            🔄 API testen
        </button>
        <div id="jsResult" style="margin-top: 20px;"></div>
    </div>
    
    <div class="test-box">
        <h2>🎯 Test 5: Dynamische Status-Punkte</h2>
        <div id="dynamicDots"></div>
        <button onclick="loadDots()" style="background: #10b981; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; margin-top: 10px;">
            🟢 Punkte laden
        </button>
    </div>
    
    <script>
        async function testAPI() {
            const result = document.getElementById('jsResult');
            result.innerHTML = '<p class="info">⏳ Lade...</p>';
            
            try {
                const response = await fetch('/customer/api/check-freebie-unlock-status.php');
                result.innerHTML += `<p class="info">📊 HTTP Status: ${response.status}</p>`;
                
                if (!response.ok) {
                    result.innerHTML += `<p class="error">❌ API Fehler: ${response.status}</p>`;
                    return;
                }
                
                const data = await response.json();
                result.innerHTML += `<p class="success">✓ JSON erfolgreich geparst</p>`;
                result.innerHTML += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                
            } catch (error) {
                result.innerHTML += `<p class="error">❌ JavaScript Fehler: ${error.message}</p>`;
            }
        }
        
        async function loadDots() {
            const container = document.getElementById('dynamicDots');
            container.innerHTML = '<p class="info">⏳ Lade Status...</p>';
            
            try {
                const response = await fetch('/customer/api/check-freebie-unlock-status.php');
                const data = await response.json();
                
                if (!data.success) {
                    container.innerHTML = '<p class="error">❌ API Fehler</p>';
                    return;
                }
                
                container.innerHTML = '<h3>Status-Punkte für alle Templates:</h3>';
                
                for (const [key, status] of Object.entries(data.statuses)) {
                    if (key.startsWith('template_')) {
                        const templateId = key.replace('template_', '');
                        const isUnlocked = status.unlock_status === 'unlocked';
                        const dotClass = isUnlocked ? 'status-unlocked' : 'status-locked';
                        const emoji = isUnlocked ? '🟢' : '🔴';
                        
                        container.innerHTML += `
                            <div style="margin: 15px 0; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                <div class="status-dot ${dotClass}"></div>
                                <strong>${status.name}</strong> ${emoji}
                                <span style="color: #888;">(Template ID: ${templateId})</span>
                                <br>
                                <span style="font-size: 14px;">Status: ${status.unlock_status}</span>
                            </div>
                        `;
                    }
                }
                
                container.innerHTML += `<p class="success">✅ ${data.total_templates} Templates, ${data.unlocked_count} freigeschaltet</p>`;
                
            } catch (error) {
                container.innerHTML = `<p class="error">❌ Fehler: ${error.message}</p>`;
            }
        }
    </script>
    
    <p style="margin-top: 40px; text-align: center;">
        <a href="dashboard.php?page=freebies" style="color: #667eea; text-decoration: none; font-weight: 600;">
            ← Zurück zu Freebies
        </a>
    </p>
</body>
</html>
