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
        
        button {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin: 5px;
        }
        
        button:hover {
            background: #5568d3;
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
        <h2>📡 Test 3: Database Check</h2>
        <p>Direkte Datenbankabfrage...</p>
        <?php
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->query("SELECT id, name FROM freebies ORDER BY created_at DESC");
            $allTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p class='success'>✓ " . count($allTemplates) . " Templates gefunden</p>";
            
            $stmt = $pdo->prepare("SELECT product_id FROM customer_freebie_limits WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p class='success'>✓ " . count($products) . " Produktkäufe: " . implode(', ', $products) . "</p>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test-box">
        <h2>⚡ Test 4: ALTE API (check-freebie-unlock-status.php)</h2>
        <button onclick="testOldAPI()">🔄 Alte API testen</button>
        <div id="oldApiResult"></div>
    </div>
    
    <div class="test-box">
        <h2>🆕 Test 5: NEUE API (template-unlock-status.php)</h2>
        <button onclick="testNewAPI()">🔄 Neue API testen</button>
        <div id="newApiResult"></div>
    </div>
    
    <div class="test-box">
        <h2>🎯 Test 6: Status-Punkte Visualisierung</h2>
        <button onclick="loadDots()">🟢 Punkte laden</button>
        <div id="dynamicDots"></div>
    </div>
    
    <script>
        async function testOldAPI() {
            const result = document.getElementById('oldApiResult');
            result.innerHTML = '<p class="info">⏳ Teste alte API...</p>';
            
            try {
                const response = await fetch('/customer/api/check-freebie-unlock-status.php');
                result.innerHTML += `<p class="info">📊 HTTP Status: ${response.status}</p>`;
                
                if (!response.ok) {
                    const text = await response.text();
                    result.innerHTML += `<p class="error">❌ API Fehler: ${response.status}</p>`;
                    result.innerHTML += `<pre>${text}</pre>`;
                    return;
                }
                
                const data = await response.json();
                result.innerHTML += `<p class="success">✓ API funktioniert!</p>`;
                result.innerHTML += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                
            } catch (error) {
                result.innerHTML += `<p class="error">❌ JavaScript Fehler: ${error.message}</p>`;
            }
        }
        
        async function testNewAPI() {
            const result = document.getElementById('newApiResult');
            result.innerHTML = '<p class="info">⏳ Teste neue API...</p>';
            
            try {
                const response = await fetch('/customer/api/template-unlock-status.php');
                result.innerHTML += `<p class="info">📊 HTTP Status: ${response.status}</p>`;
                
                if (!response.ok) {
                    const text = await response.text();
                    result.innerHTML += `<p class="error">❌ API Fehler: ${response.status}</p>`;
                    result.innerHTML += `<pre>${text}</pre>`;
                    return;
                }
                
                const data = await response.json();
                result.innerHTML += `<p class="success">✓ API funktioniert!</p>`;
                result.innerHTML += `<p class="success">✓ ${data.total_templates} Templates, ${data.unlocked_count} freigeschaltet</p>`;
                result.innerHTML += `<pre>${JSON.stringify(data, null, 2)}</pre>`;
                
            } catch (error) {
                result.innerHTML += `<p class="error">❌ JavaScript Fehler: ${error.message}</p>`;
            }
        }
        
        async function loadDots() {
            const container = document.getElementById('dynamicDots');
            container.innerHTML = '<p class="info">⏳ Lade Status...</p>';
            
            try {
                const response = await fetch('/customer/api/template-unlock-status.php');
                const data = await response.json();
                
                if (!data.success) {
                    container.innerHTML = `<p class="error">❌ API Fehler: ${data.error}</p>`;
                    return;
                }
                
                container.innerHTML = '<h3>Status-Punkte für alle Templates:</h3>';
                
                for (const [key, status] of Object.entries(data.statuses)) {
                    if (key.startsWith('template_')) {
                        const templateId = key.replace('template_', '');
                        const isUnlocked = status.unlock_status === 'unlocked';
                        const isLocked = status.unlock_status === 'locked';
                        const dotClass = isUnlocked ? 'status-unlocked' : (isLocked ? 'status-locked' : '');
                        const emoji = isUnlocked ? '🟢' : (isLocked ? '🔴' : '⚪');
                        
                        if (status.unlock_status !== 'no_course') {
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
