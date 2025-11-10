<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank Migration - Videokurs Button & Freischaltung</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }
        .info-box h3 {
            color: #0c4a6e;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .info-box ul {
            margin-left: 20px;
            color: #0c4a6e;
            line-height: 1.8;
        }
        .btn {
            display: inline-block;
            padding: 16px 32px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        #result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            display: none;
        }
        #result.success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        #result.error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        .log-entry {
            padding: 8px 12px;
            margin: 5px 0;
            background: rgba(255,255,255,0.5);
            border-radius: 4px;
            font-size: 14px;
        }
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Datenbank Migration</h1>
        <p class="subtitle">Videokurs Button & Freischaltungs-Felder hinzufügen</p>
        
        <div class="info-box">
            <h3>📋 Diese Migration fügt folgende Felder hinzu:</h3>
            <ul>
                <li><strong>button_text</strong> - Text für den Call-to-Action Button unter Videos</li>
                <li><strong>button_url</strong> - Ziel-URL für den Button</li>
                <li><strong>unlock_after_days</strong> - Zeitgesteuerte Freischaltung (0 = sofort verfügbar)</li>
            </ul>
        </div>
        
        <div class="info-box" style="background: #fef3c7; border-color: #f59e0b;">
            <h3 style="color: #92400e;">⚠️ Wichtig:</h3>
            <ul style="color: #92400e;">
                <li>Diese Migration ist sicher und verändert keine bestehenden Daten</li>
                <li>Falls die Felder bereits existieren, werden sie übersprungen</li>
                <li>Die Migration kann mehrfach ausgeführt werden</li>
            </ul>
        </div>
        
        <button id="migrateBtn" class="btn" onclick="runMigration()">
            🚀 Migration starten
        </button>
        
        <div id="result"></div>
    </div>

    <script>
        async function runMigration() {
            const btn = document.getElementById('migrateBtn');
            const result = document.getElementById('result');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Migration läuft...';
            result.style.display = 'none';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'migrate' })
                });
                
                const data = await response.json();
                
                result.className = data.success ? 'success' : 'error';
                result.style.display = 'block';
                
                let html = '<h3>' + (data.success ? '✅ Migration erfolgreich!' : '❌ Fehler bei der Migration') + '</h3>';
                
                if (data.logs && data.logs.length > 0) {
                    html += '<div style="margin-top: 15px;">';
                    data.logs.forEach(log => {
                        html += '<div class="log-entry">' + log + '</div>';
                    });
                    html += '</div>';
                }
                
                if (data.error) {
                    html += '<div class="log-entry" style="background: rgba(239,68,68,0.2);">Fehler: ' + data.error + '</div>';
                }
                
                result.innerHTML = html;
                
                btn.disabled = false;
                btn.innerHTML = data.success ? '✅ Migration abgeschlossen' : '🔄 Erneut versuchen';
                
            } catch (error) {
                result.className = 'error';
                result.style.display = 'block';
                result.innerHTML = '<h3>❌ Verbindungsfehler</h3><div class="log-entry">' + error.message + '</div>';
                
                btn.disabled = false;
                btn.innerHTML = '🔄 Erneut versuchen';
            }
        }
    </script>
</body>
</html>

<?php
// Migration Backend
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'migrate') {
        require_once __DIR__ . '/../../config/database.php';
        
        $logs = [];
        $success = true;
        
        try {
            $pdo = getDBConnection();
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $logs[] = "✅ Datenbankverbindung hergestellt";
            
            // Prüfen ob Tabelle existiert
            $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_course_lessons'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("Tabelle 'freebie_course_lessons' nicht gefunden!");
            }
            
            $logs[] = "✅ Tabelle 'freebie_course_lessons' gefunden";
            
            // Aktuelle Spalten abrufen
            $stmt = $pdo->query("DESCRIBE freebie_course_lessons");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $logs[] = "📋 Aktuelle Spalten: " . implode(', ', $columns);
            
            // Felder hinzufügen (falls nicht vorhanden)
            $fieldsToAdd = [
                'button_text' => "VARCHAR(255) DEFAULT NULL COMMENT 'Button-Text für CTA'",
                'button_url' => "TEXT DEFAULT NULL COMMENT 'Button-Ziel-URL'",
                'unlock_after_days' => "INT DEFAULT 0 COMMENT 'Freischaltung nach X Tagen'"
            ];
            
            foreach ($fieldsToAdd as $field => $definition) {
                if (!in_array($field, $columns)) {
                    $sql = "ALTER TABLE freebie_course_lessons ADD COLUMN $field $definition";
                    $pdo->exec($sql);
                    $logs[] = "✅ Feld '$field' hinzugefügt";
                } else {
                    $logs[] = "ℹ️ Feld '$field' existiert bereits";
                }
            }
            
            $logs[] = "✅ Migration erfolgreich abgeschlossen!";
            
        } catch (Exception $e) {
            $success = false;
            $logs[] = "❌ Fehler: " . $e->getMessage();
        }
        
        echo json_encode([
            'success' => $success,
            'logs' => $logs,
            'error' => $success ? null : end($logs)
        ]);
        exit;
    }
}
?>
