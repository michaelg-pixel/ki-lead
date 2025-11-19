<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>🔍 Punkte Sichtbarkeits-Test</title>
    <style>
        body {
            background: #1a1a2e;
            color: white;
            font-family: Arial, sans-serif;
            padding: 40px;
        }
        
        .test-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .mockup {
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            position: relative;
            margin: 20px 0;
        }
        
        /* GENAU DER GLEICHE CSS WIE IN freebies.php */
        .unlock-status-dot {
            position: absolute;
            bottom: 16px;
            right: 16px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.6);
            z-index: 10;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .unlock-status-dot.loaded {
            opacity: 1;
        }
        
        .status-unlocked {
            background: #22c55e;
            animation: pulse-green 2s ease-in-out infinite;
        }
        
        .status-locked {
            background: #ef4444;
            animation: pulse-red 2s ease-in-out infinite;
        }
        
        @keyframes pulse-green {
            0%, 100% { box-shadow: 0 4px 16px rgba(34, 197, 94, 0.6); }
            50% { box-shadow: 0 4px 24px rgba(34, 197, 94, 0.9); }
        }
        
        @keyframes pulse-red {
            0%, 100% { box-shadow: 0 4px 16px rgba(239, 68, 68, 0.6); }
            50% { box-shadow: 0 4px 24px rgba(239, 68, 68, 0.9); }
        }
        
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        
        #log {
            background: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Punkte Sichtbarkeits-Test</h1>
    
    <div class="test-card">
        <h2>Test 1: Statischer grüner Punkt (sofort sichtbar)</h2>
        <div class="mockup">
            <div class="unlock-status-dot status-unlocked loaded" style="opacity: 1;"></div>
        </div>
        <p class="success">✓ Siehst du einen grünen Punkt rechts unten?</p>
    </div>
    
    <div class="test-card">
        <h2>Test 2: Statischer roter Punkt (sofort sichtbar)</h2>
        <div class="mockup">
            <div class="unlock-status-dot status-locked loaded" style="opacity: 1;"></div>
        </div>
        <p class="error">✓ Siehst du einen roten Punkt rechts unten?</p>
    </div>
    
    <div class="test-card">
        <h2>Test 3: Unsichtbarer Punkt (wird per JavaScript sichtbar)</h2>
        <div class="mockup">
            <div class="unlock-status-dot" id="testDot"></div>
        </div>
        <button onclick="showDot()" style="background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
            ⚡ Punkt anzeigen
        </button>
        <p id="test3result" style="margin-top: 10px;"></p>
    </div>
    
    <div class="test-card">
        <h2>Test 4: API Call & Echte Daten</h2>
        <button onclick="testAPI()" style="background: #10b981; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
            🔄 API testen
        </button>
        <div id="apiResult" style="margin-top: 20px;"></div>
    </div>
    
    <div class="test-card">
        <h2>Test 5: Echte Templates mit Punkten</h2>
        <button onclick="loadRealTemplates()" style="background: #8b5cf6; color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600;">
            🎯 Templates laden
        </button>
        <div id="templates" style="margin-top: 20px;"></div>
    </div>
    
    <div id="log">
        <strong>📋 Console Log:</strong><br>
    </div>
    
    <script>
        const logDiv = document.getElementById('log');
        
        function log(msg, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const className = type;
            logDiv.innerHTML += `<div class="${className}">[${time}] ${msg}</div>`;
            console.log(msg);
        }
        
        log('✓ Seite geladen', 'success');
        
        function showDot() {
            const dot = document.getElementById('testDot');
            dot.classList.add('status-unlocked');
            dot.classList.add('loaded');
            document.getElementById('test3result').innerHTML = '<span class="success">✓ Punkt sollte jetzt sichtbar sein!</span>';
            log('✓ Test 3: Punkt per JavaScript angezeigt', 'success');
        }
        
        async function testAPI() {
            const result = document.getElementById('apiResult');
            result.innerHTML = '<p class="info">⏳ Lade API...</p>';
            log('📡 API Call startet...', 'info');
            
            try {
                const response = await fetch('/customer/api/template-unlock-status.php');
                log(`📊 HTTP Status: ${response.status}`, response.ok ? 'success' : 'error');
                
                if (!response.ok) {
                    result.innerHTML = `<p class="error">❌ API Fehler: ${response.status}</p>`;
                    log('❌ API Fehler', 'error');
                    return;
                }
                
                const data = await response.json();
                log(`✓ JSON erhalten: ${data.total_templates} Templates, ${data.unlocked_count} freigeschaltet`, 'success');
                
                result.innerHTML = `
                    <p class="success">✓ API funktioniert!</p>
                    <p><strong>Total Templates:</strong> ${data.total_templates}</p>
                    <p><strong>Freigeschaltet:</strong> ${data.unlocked_count}</p>
                    <details>
                        <summary>Details anzeigen</summary>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </details>
                `;
                
            } catch (error) {
                result.innerHTML = `<p class="error">❌ JavaScript Fehler: ${error.message}</p>`;
                log(`❌ Fehler: ${error.message}`, 'error');
            }
        }
        
        async function loadRealTemplates() {
            const container = document.getElementById('templates');
            container.innerHTML = '<p class="info">⏳ Lade Templates...</p>';
            log('🎯 Lade echte Templates...', 'info');
            
            try {
                const response = await fetch('/customer/api/template-unlock-status.php');
                const data = await response.json();
                
                if (!data.success) {
                    container.innerHTML = '<p class="error">❌ API Fehler</p>';
                    return;
                }
                
                container.innerHTML = '<h3>Templates mit Status-Punkten:</h3>';
                
                let htmlContent = '';
                for (const [key, status] of Object.entries(data.statuses)) {
                    if (key.startsWith('template_')) {
                        const templateId = key.replace('template_', '');
                        const isUnlocked = status.unlock_status === 'unlocked';
                        const isLocked = status.unlock_status === 'locked';
                        
                        if (status.unlock_status !== 'no_course') {
                            const dotClass = isUnlocked ? 'status-unlocked' : 'status-locked';
                            const emoji = isUnlocked ? '🟢' : '🔴';
                            
                            htmlContent += `
                                <div style="margin: 15px 0;">
                                    <div class="mockup" style="height: 150px;">
                                        <div class="unlock-status-dot ${dotClass} loaded" style="opacity: 1;"></div>
                                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                            <strong>${status.name}</strong><br>
                                            <span style="font-size: 14px;">Template ${templateId} - ${status.unlock_status} ${emoji}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            log(`${emoji} Template ${templateId}: ${status.unlock_status}`, isUnlocked ? 'success' : 'error');
                        }
                    }
                }
                
                container.innerHTML += htmlContent;
                log('✅ Alle Templates geladen', 'success');
                
            } catch (error) {
                container.innerHTML = `<p class="error">❌ Fehler: ${error.message}</p>`;
                log(`❌ Fehler: ${error.message}`, 'error');
            }
        }
    </script>
    
    <p style="margin-top: 40px; text-align: center;">
        <a href="dashboard.php?page=freebies" style="color: #667eea; text-decoration: none; font-weight: 600;">
            🎁 Zu den Freebies
        </a>
    </p>
</body>
</html>
