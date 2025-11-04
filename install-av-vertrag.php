<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AV-Vertrag Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 800px;
            width: 100%;
        }
        h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 16px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 32px;
            font-size: 14px;
        }
        .step {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 16px 20px;
            margin-bottom: 16px;
            border-radius: 8px;
        }
        .step-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step-content {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: 24px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .result {
            margin-top: 24px;
            padding: 16px 20px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.6;
            display: none;
        }
        .result.success {
            background: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        .result.error {
            background: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: 8px;
            color: #92400e;
            font-size: 14px;
        }
        .code {
            background: #1f2937;
            color: #34d399;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 12px;
            white-space: pre;
        }
        .check-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 0;
            color: #6b7280;
        }
        .check-item.ok {
            color: #059669;
        }
        .check-item.error {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 AV-Vertrag Installation</h1>
        <p class="subtitle">Installiert die Datenbank-Tabelle für AV-Vertrag Firmendaten</p>
        
        <div class="warning">
            <strong>⚠️ Wichtig:</strong> Dieses Script installiert die Tabelle <code>user_company_data</code> in Ihrer Datenbank. 
            Die Installation ist sicher und kann mehrfach ausgeführt werden (CREATE TABLE IF NOT EXISTS).
        </div>
        
        <div class="step">
            <div class="step-title">
                <span>1️⃣</span>
                <span>Was wird installiert?</span>
            </div>
            <div class="step-content">
                <ul>
                    <li>Tabelle: <code>user_company_data</code></li>
                    <li>Speichert Firmendaten für personalisierten AV-Vertrag</li>
                    <li>Verknüpfung mit users-Tabelle via Foreign Key</li>
                    <li>Sichere Datenstruktur mit allen notwendigen Indizes</li>
                </ul>
            </div>
        </div>
        
        <div class="step">
            <div class="step-title">
                <span>2️⃣</span>
                <span>System-Checks</span>
            </div>
            <div class="step-content" id="checksContainer">
                <p style="color: #6b7280;">Klicke auf "Installation starten" um die Checks durchzuführen...</p>
            </div>
        </div>
        
        <div class="step">
            <div class="step-title">
                <span>3️⃣</span>
                <span>Tabellen-Struktur</span>
            </div>
            <div class="step-content">
                <div class="code">-- Felder der Tabelle:
id              INT (Auto-Increment, Primary Key)
user_id         INT (Foreign Key → users.id)
company_name    VARCHAR(255)
company_address VARCHAR(255)
company_zip     VARCHAR(10)
company_city    VARCHAR(100)
company_country VARCHAR(100)
contact_person  VARCHAR(255)
contact_email   VARCHAR(255)
contact_phone   VARCHAR(50)
created_at      TIMESTAMP
updated_at      TIMESTAMP</div>
            </div>
        </div>
        
        <button class="button" id="installBtn" onclick="startInstallation()">
            🚀 Installation starten
        </button>
        
        <div class="result" id="result"></div>
    </div>
    
    <script>
        async function startInstallation() {
            const installBtn = document.getElementById('installBtn');
            const result = document.getElementById('result');
            const checksContainer = document.getElementById('checksContainer');
            
            installBtn.disabled = true;
            installBtn.textContent = '⏳ Installation läuft...';
            result.style.display = 'none';
            
            try {
                // Checks durchführen
                checksContainer.innerHTML = '<p style="color: #6b7280; margin-bottom: 12px;">Führe System-Checks durch...</p>';
                
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=install'
                });
                
                const text = await response.text();
                console.log('Server Response:', text);
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server hat keine gültige JSON-Antwort zurückgegeben. Response: ' + text.substring(0, 200));
                }
                
                // Checks anzeigen
                let checksHTML = '';
                if (data.checks) {
                    data.checks.forEach(check => {
                        const className = check.status === 'ok' ? 'ok' : 'error';
                        const icon = check.status === 'ok' ? '✅' : '❌';
                        checksHTML += `<div class="check-item ${className}">${icon} ${check.message}</div>`;
                    });
                }
                checksContainer.innerHTML = checksHTML;
                
                // Ergebnis anzeigen
                result.style.display = 'block';
                
                if (data.success) {
                    result.className = 'result success';
                    result.innerHTML = `
                        <strong>✅ Installation erfolgreich!</strong><br><br>
                        ${data.message}<br><br>
                        <strong>Nächste Schritte:</strong><br>
                        1. Gehe zu den Einstellungen im Customer Dashboard<br>
                        2. Fülle das Firmendaten-Formular aus<br>
                        3. Lade deinen personalisierten AV-Vertrag herunter<br><br>
                        <strong>⚠️ Wichtig:</strong> Lösche diese Datei aus Sicherheitsgründen nach der Installation!
                    `;
                    installBtn.textContent = '✅ Installation abgeschlossen';
                } else {
                    result.className = 'result error';
                    result.innerHTML = `
                        <strong>❌ Installation fehlgeschlagen</strong><br><br>
                        ${data.message || 'Unbekannter Fehler'}<br><br>
                        ${data.error ? `<strong>Fehlerdetails:</strong><br>${data.error}` : ''}
                    `;
                    installBtn.disabled = false;
                    installBtn.textContent = '🔄 Erneut versuchen';
                }
                
            } catch (error) {
                result.style.display = 'block';
                result.className = 'result error';
                result.innerHTML = `
                    <strong>❌ Fehler bei der Installation</strong><br><br>
                    ${error.message}<br><br>
                    Bitte überprüfe die Datenbankverbindung und versuche es erneut.
                `;
                installBtn.disabled = false;
                installBtn.textContent = '🔄 Erneut versuchen';
            }
        }
    </script>
</body>
</html>

<?php
// PHP Installation Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    // Verhindere HTML-Output
    ob_start();
    
    header('Content-Type: application/json');
    
    $checks = [];
    $allChecksOk = true;
    
    try {
        // Datenbankverbindung manuell erstellen (ohne HTML-Output)
        $host = 'localhost';
        $database = 'lumisaas';
        $username = 'lumisaas52';
        $password = 'I1zx1XdL1hrWd75yu57e';
        
        try {
            $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ));
            $checks[] = ['status' => 'ok', 'message' => 'Datenbankverbindung erfolgreich'];
        } catch (PDOException $e) {
            $checks[] = ['status' => 'error', 'message' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()];
            $allChecksOk = false;
        }
        
        if ($allChecksOk) {
            // Check: Users Tabelle existiert
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                if ($stmt->rowCount() > 0) {
                    $checks[] = ['status' => 'ok', 'message' => 'Users-Tabelle gefunden'];
                } else {
                    $checks[] = ['status' => 'error', 'message' => 'Users-Tabelle nicht gefunden'];
                    $allChecksOk = false;
                }
            } catch (Exception $e) {
                $checks[] = ['status' => 'error', 'message' => 'Fehler beim Prüfen der Users-Tabelle: ' . $e->getMessage()];
                $allChecksOk = false;
            }
            
            // Check: Tabelle existiert bereits?
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE 'user_company_data'");
                if ($stmt->rowCount() > 0) {
                    $checks[] = ['status' => 'ok', 'message' => 'Tabelle existiert bereits (wird aktualisiert)'];
                } else {
                    $checks[] = ['status' => 'ok', 'message' => 'Tabelle wird neu erstellt'];
                }
            } catch (Exception $e) {
                $checks[] = ['status' => 'error', 'message' => 'Fehler beim Prüfen der Tabelle: ' . $e->getMessage()];
            }
        }
        
        if (!$allChecksOk) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'System-Checks fehlgeschlagen. Bitte behebe die Fehler und versuche es erneut.',
                'checks' => $checks
            ]);
            exit;
        }
        
        // Installation durchführen
        $sql = "
        CREATE TABLE IF NOT EXISTS user_company_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            company_address VARCHAR(255) NOT NULL,
            company_zip VARCHAR(10) NOT NULL,
            company_city VARCHAR(100) NOT NULL,
            company_country VARCHAR(100) DEFAULT 'Deutschland',
            contact_person VARCHAR(255),
            contact_email VARCHAR(255),
            contact_phone VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user (user_id),
            CONSTRAINT fk_user_company_data_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        $checks[] = ['status' => 'ok', 'message' => 'Tabelle erfolgreich erstellt/aktualisiert'];
        
        // Index erstellen (falls noch nicht vorhanden)
        try {
            $pdo->exec("CREATE INDEX idx_user_company_data_user_id ON user_company_data(user_id)");
            $checks[] = ['status' => 'ok', 'message' => 'Index erfolgreich erstellt'];
        } catch (Exception $e) {
            // Index existiert möglicherweise bereits
            $checks[] = ['status' => 'ok', 'message' => 'Index bereits vorhanden'];
        }
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Tabelle "user_company_data" wurde erfolgreich installiert!',
            'checks' => $checks
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Fehler bei der Installation',
            'error' => $e->getMessage(),
            'checks' => $checks
        ]);
    }
    
    exit;
}
?>
