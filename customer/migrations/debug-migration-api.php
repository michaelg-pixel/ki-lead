<?php
/**
 * Debug: Test Customer Migration API
 */
session_start();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration API Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #00ff00;
            padding: 20px;
        }
        .section {
            background: #16213e;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #00ff00;
        }
        .error { border-color: #ff0000; color: #ff0000; }
        .success { border-color: #00ff00; color: #00ff00; }
        .warning { border-color: #ffaa00; color: #ffaa00; }
        pre { 
            background: #0f0f1e; 
            padding: 10px; 
            border-radius: 4px; 
            overflow-x: auto;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 10px 5px;
        }
        button:hover { background: #5568d3; }
        #response {
            background: #0f0f1e;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>🔧 Migration API Debug Tool</h1>
    
    <div class="section <?php echo isset($_SESSION['user_id']) ? 'success' : 'error'; ?>">
        <h2>Session Status</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>

    <div class="section">
        <h2>Test API Endpoint</h2>
        <p>Teste die Customer Migration API mit einem einfachen SQL-Statement:</p>
        
        <button onclick="testAPI()">Test: SELECT 1</button>
        <button onclick="testCreateTable()">Test: CREATE TABLE</button>
        <button onclick="testAuth()">Test: Auth Check</button>
        
        <h3>API Response:</h3>
        <div id="response">Klicke auf einen Test-Button...</div>
    </div>

    <div class="section">
        <h2>Expected Endpoint</h2>
        <p>API URL: <code>/customer/api/execute-migration.php</code></p>
        <p>Full Path: <code><?php echo __DIR__ . '/api/execute-migration.php'; ?></code></p>
        <p>File exists: <?php echo file_exists(__DIR__ . '/api/execute-migration.php') ? '✅ YES' : '❌ NO'; ?></p>
    </div>

    <script>
        async function testAPI() {
            const response = document.getElementById('response');
            response.textContent = 'Testing...';
            
            try {
                const res = await fetch('/customer/api/execute-migration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sql: 'SELECT 1 as test'
                    })
                });
                
                const contentType = res.headers.get('content-type');
                response.textContent = `Status: ${res.status}\nContent-Type: ${contentType}\n\n`;
                
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();
                    response.textContent += JSON.stringify(data, null, 2);
                } else {
                    const text = await res.text();
                    response.textContent += 'Response (HTML/Text):\n' + text.substring(0, 1000);
                }
            } catch (error) {
                response.textContent = 'Error: ' + error.message;
            }
        }

        async function testCreateTable() {
            const response = document.getElementById('response');
            response.textContent = 'Testing...';
            
            try {
                const res = await fetch('/customer/api/execute-migration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sql: `CREATE TABLE IF NOT EXISTS test_migration_table (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            test_field VARCHAR(100)
                        )`
                    })
                });
                
                const contentType = res.headers.get('content-type');
                response.textContent = `Status: ${res.status}\nContent-Type: ${contentType}\n\n`;
                
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();
                    response.textContent += JSON.stringify(data, null, 2);
                } else {
                    const text = await res.text();
                    response.textContent += 'Response (HTML/Text):\n' + text.substring(0, 1000);
                }
            } catch (error) {
                response.textContent = 'Error: ' + error.message;
            }
        }

        async function testAuth() {
            const response = document.getElementById('response');
            response.textContent = 'Testing...';
            
            try {
                const res = await fetch('/customer/api/execute-migration.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sql: ''
                    })
                });
                
                const contentType = res.headers.get('content-type');
                response.textContent = `Status: ${res.status}\nContent-Type: ${contentType}\n\n`;
                
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();
                    response.textContent += JSON.stringify(data, null, 2);
                } else {
                    const text = await res.text();
                    response.textContent += 'Response (HTML/Text):\n' + text.substring(0, 1000);
                }
            } catch (error) {
                response.textContent = 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>