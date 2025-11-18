<?php
/**
 * Webhook Test Tool - Simuliert Digistore24 Marketplace-Käufe
 * Verwendet die E-Mail des eingeloggten Users
 */

session_start();

// Prüfen ob User eingeloggt ist
if (!isset($_SESSION['user_id'])) {
    header('Location: /public/login.php');
    exit;
}

require_once '../config/database.php';
$pdo = getDBConnection();

// User-Daten laden
$stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    die('Fehler: User nicht gefunden');
}

$userEmail = $currentUser['email'];
$userName = $currentUser['name'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Test Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header h1 {
            color: white;
            font-size: 24px;
            font-weight: 600;
        }
        
        .user-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 8px;
            margin-left: auto;
            color: white;
            font-size: 14px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-text {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .test-data {
            background: #1f2937;
            color: #10b981;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-bottom: 20px;
            overflow-x: auto;
        }
        
        .test-data pre {
            margin: 0;
            white-space: pre-wrap;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        
        .success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }
        
        .error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .logs {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .checklist {
            margin-top: 20px;
        }
        
        .checklist h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .checklist ol {
            margin-left: 20px;
        }
        
        .checklist li {
            margin-bottom: 10px;
            color: #4b5563;
        }
        
        .links {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .links h4 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #6b7280;
        }
        
        .links a {
            color: #667eea;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        .loader {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: none;
            margin-left: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span style="font-size: 32px;">🔧</span>
            <h1>Webhook Test Tool</h1>
            <div class="user-info">
                👤 <?php echo htmlspecialchars($userName); ?><br>
                📧 <?php echo htmlspecialchars($userEmail); ?>
            </div>
        </div>
        
        <div class="card">
            <h2>📋 Test-Daten</h2>
            <div class="info-text">
                <strong>✅ Wichtig:</strong> Der Test verwendet automatisch DEINE E-Mail-Adresse (<?php echo htmlspecialchars($userEmail); ?>), damit das Freebie in DEINEM Account landet!
            </div>
            <div class="test-data">
                <pre>{
    "event": "payment.success",
    "order_id": "TEST-<?php echo time(); ?>",
    "product_id": "613818",
    "product_name": "Test Marketplace Produkt",
    "buyer": {
        "email": "<?php echo htmlspecialchars($userEmail); ?>",
        "first_name": "<?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>",
        "last_name": "<?php echo htmlspecialchars(explode(' ', $userName)[1] ?? 'Test'); ?>"
    }
}</pre>
            </div>
            
            <button class="btn" onclick="runTest()" id="testBtn">
                <span>🚀</span>
                <span>TEST STARTEN</span>
                <div class="loader" id="loader"></div>
            </button>
        </div>
        
        <div class="card">
            <h2>🎯 Test läuft...</h2>
            <div class="info-text">
                Nach erfolgreichem Test solltest du das Freebie hier sehen:<br>
                <strong><a href="/customer/dashboard.php?page=freebies" target="_blank">👉 Meine Freebies</a></strong>
            </div>
            <div id="result"></div>
        </div>
        
        <div class="card">
            <h2>📝 Webhook-Logs (letzte 50 Zeilen)</h2>
            <div class="logs" id="logs">
                Lade Logs...
            </div>
        </div>
        
        <div class="card">
            <h2>✅ Checkliste</h2>
            <div class="checklist">
                <h3>Nach dem Test prüfe:</h3>
                <ol>
                    <li>Hat der Webhook mit HTTP 200 geantwortet? ✅</li>
                    <li>Wurde ein Freebie erstellt? → Prüfe in den Logs</li>
                    <li>Ist das Freebie in deinem Dashboard sichtbar? → <a href="/customer/dashboard.php?page=freebies" target="_blank">Meine Freebies öffnen</a></li>
                    <li>Hat das Freebie alle Inhalte (Mockup, Texte, etc.)?</li>
                </ol>
            </div>
        </div>
    </div>
    
    <script>
        // Logs laden beim Laden der Seite
        loadLogs();
        
        // Auto-Refresh alle 5 Sekunden wenn Test läuft
        let autoRefresh = false;
        
        function runTest() {
            const btn = document.getElementById('testBtn');
            const loader = document.getElementById('loader');
            const result = document.getElementById('result');
            
            btn.disabled = true;
            loader.style.display = 'inline-block';
            result.style.display = 'none';
            
            autoRefresh = true;
            
            // Test-Daten mit DEINER E-Mail
            const testData = {
                event: "payment.success",
                order_id: "TEST-" + Date.now(),
                product_id: "613818",
                product_name: "Test Marketplace Produkt",
                buyer: {
                    email: "<?php echo htmlspecialchars($userEmail); ?>",
                    first_name: "<?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>",
                    last_name: "<?php echo htmlspecialchars(explode(' ', $userName)[1] ?? 'Test'); ?>"
                }
            };
            
            // Webhook aufrufen
            fetch('digistore24-v4.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(testData)
            })
            .then(response => {
                const status = response.status;
                return response.json().then(data => ({status, data}));
            })
            .then(({status, data}) => {
                btn.disabled = false;
                loader.style.display = 'none';
                result.style.display = 'block';
                
                if (status === 200) {
                    result.className = 'success';
                    result.innerHTML = `
                        <strong>✅ Webhook hat erfolgreich geantwortet (HTTP ${status})</strong><br><br>
                        <strong>Webhook Response:</strong><br>
                        ${JSON.stringify(data, null, 2)}<br><br>
                        <strong>👉 Nächster Schritt:</strong><br>
                        <a href="/customer/dashboard.php?page=freebies" target="_blank" style="color: #065f46; font-weight: bold; text-decoration: underline;">
                            Öffne jetzt "Meine Freebies" um das gekaufte Freebie zu sehen
                        </a>
                    `;
                } else {
                    result.className = 'error';
                    result.innerHTML = `
                        <strong>❌ Webhook hat mit Fehler geantwortet (HTTP ${status})</strong><br><br>
                        <strong>Webhook Response:</strong><br>
                        ${JSON.stringify(data, null, 2)}
                    `;
                }
                
                // Logs neu laden
                setTimeout(() => {
                    loadLogs();
                    autoRefresh = false;
                }, 1000);
            })
            .catch(error => {
                btn.disabled = false;
                loader.style.display = 'none';
                result.style.display = 'block';
                result.className = 'error';
                result.innerHTML = `<strong>❌ Fehler beim Test:</strong><br>${error.message}`;
                autoRefresh = false;
            });
        }
        
        function loadLogs() {
            fetch('webhook-logs.txt')
                .then(response => response.text())
                .then(text => {
                    const lines = text.split('\n');
                    const last50 = lines.slice(-50).join('\n');
                    document.getElementById('logs').textContent = last50 || 'Keine Logs vorhanden';
                    
                    if (autoRefresh) {
                        setTimeout(loadLogs, 2000);
                    }
                })
                .catch(error => {
                    document.getElementById('logs').textContent = 'Fehler beim Laden der Logs: ' + error.message;
                });
        }
    </script>
</body>
</html>