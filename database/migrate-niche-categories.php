<?php
// PHP Backend für die Migration - MUSS GANZ OBEN SEIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    // Fehlerbehandlung aktivieren
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    // JSON Header SOFORT setzen
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        // Prüfe ob database.php existiert
        $db_config_path = __DIR__ . '/../config/database.php';
        if (!file_exists($db_config_path)) {
            throw new Exception('Datenbankonfiguration nicht gefunden: ' . $db_config_path);
        }
        
        // Lade Datenbankverbindung
        require_once $db_config_path;
        
        // Prüfe ob getDBConnection Funktion existiert
        if (!function_exists('getDBConnection')) {
            throw new Exception('getDBConnection Funktion nicht gefunden in database.php');
        }
        
        $pdo = getDBConnection();
        
        if (!$pdo) {
            throw new Exception('Datenbankverbindung konnte nicht hergestellt werden');
        }
        
        $messages = [];
        $errors = [];
        
        // SCHRITT 1: Prüfen und Hinzufügen der niche-Spalte in freebies
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'niche'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("
                    ALTER TABLE freebies 
                    ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER name
                ");
                $messages[] = "✓ Spalte 'niche' zur Tabelle 'freebies' hinzugefügt";
            } else {
                $messages[] = "ℹ️ Spalte 'niche' existiert bereits in 'freebies'";
            }
        } catch (PDOException $e) {
            $errors[] = "Fehler bei freebies.niche: " . $e->getMessage();
        }
        
        // SCHRITT 2: Prüfen und Hinzufügen der niche-Spalte in customer_freebies
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'niche'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("
                    ALTER TABLE customer_freebies 
                    ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER customer_id
                ");
                $messages[] = "✓ Spalte 'niche' zur Tabelle 'customer_freebies' hinzugefügt";
            } else {
                $messages[] = "ℹ️ Spalte 'niche' existiert bereits in 'customer_freebies'";
            }
        } catch (PDOException $e) {
            $errors[] = "Fehler bei customer_freebies.niche: " . $e->getMessage();
        }
        
        // SCHRITT 3: Standard-Wert für bestehende Einträge setzen
        try {
            $stmt = $pdo->exec("UPDATE freebies SET niche = 'sonstiges' WHERE niche IS NULL OR niche = ''");
            $messages[] = "✓ Standard-Werte für {$stmt} bestehende Freebies gesetzt";
            
            $stmt = $pdo->exec("UPDATE customer_freebies SET niche = 'sonstiges' WHERE niche IS NULL OR niche = ''");
            $messages[] = "✓ Standard-Werte für {$stmt} Customer-Freebies gesetzt";
        } catch (PDOException $e) {
            $errors[] = "Fehler beim Setzen der Standard-Werte: " . $e->getMessage();
        }
        
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'error' => implode("\n", $errors),
                'messages' => $messages
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
        }
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Datenbankfehler: ' . $e->getMessage(),
            'details' => [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nischen-Kategorie Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #1f2937;
            font-size: 32px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 32px;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .info-box h3 {
            color: #1e40af;
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #1e40af;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .niche-list {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .niche-list h3 {
            color: #1f2937;
            font-size: 18px;
            margin-bottom: 16px;
        }
        
        .niche-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        
        .niche-item {
            background: white;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 14px;
            color: #374151;
        }
        
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .result {
            margin-top: 24px;
            padding: 20px;
            border-radius: 8px;
            font-size: 14px;
            line-height: 1.8;
        }
        
        .result.success {
            background: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
        }
        
        .result.error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        .result pre {
            background: rgba(0, 0, 0, 0.05);
            padding: 12px;
            border-radius: 6px;
            overflow-x: auto;
            margin-top: 12px;
            font-size: 12px;
        }
        
        .steps {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
        }
        
        .steps h3 {
            color: #92400e;
            font-size: 16px;
            margin-bottom: 12px;
        }
        
        .steps ol {
            margin-left: 20px;
            color: #92400e;
        }
        
        .steps li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        @media (max-width: 640px) {
            .container {
                padding: 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .niche-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            🎯 Nischen-Kategorie Migration
        </h1>
        <p class="subtitle">
            Fügt die Nischen-Kategorie Spalte zu den Freebie-Tabellen hinzu
        </p>
        
        <div class="info-box">
            <h3>ℹ️ Was macht dieses Script?</h3>
            <p>
                Dieses Script fügt automatisch die <strong>niche</strong>-Spalte zu beiden Freebie-Tabellen hinzu 
                (<code>freebies</code> und <code>customer_freebies</code>). Alle bestehenden Einträge erhalten 
                die Standard-Kategorie "Sonstiges".
            </p>
        </div>
        
        <div class="niche-list">
            <h3>📋 Verfügbare Nischen (15 + Sonstiges)</h3>
            <div class="niche-grid">
                <div class="niche-item">💼 Online Business & Marketing</div>
                <div class="niche-item">💪 Gesundheit & Fitness</div>
                <div class="niche-item">🧠 Persönliche Entwicklung</div>
                <div class="niche-item">💰 Finanzen & Investment</div>
                <div class="niche-item">🏠 Immobilien</div>
                <div class="niche-item">🛒 E-Commerce & Dropshipping</div>
                <div class="niche-item">📈 Affiliate Marketing</div>
                <div class="niche-item">📱 Social Media Marketing</div>
                <div class="niche-item">🤖 KI & Automation</div>
                <div class="niche-item">👔 Coaching & Consulting</div>
                <div class="niche-item">✨ Spiritualität & Mindfulness</div>
                <div class="niche-item">❤️ Beziehungen & Dating</div>
                <div class="niche-item">👨‍👩‍👧 Eltern & Familie</div>
                <div class="niche-item">🎯 Karriere & Beruf</div>
                <div class="niche-item">🎨 Hobbys & Freizeit</div>
                <div class="niche-item">📂 Sonstiges</div>
            </div>
        </div>
        
        <div class="steps">
            <h3>⚠️ Wichtige Hinweise</h3>
            <ol>
                <li>Dieses Script ändert die Datenbankstruktur</li>
                <li>Es wird automatisch geprüft, ob die Spalten bereits existieren</li>
                <li>Bestehende Daten werden <strong>nicht</strong> gelöscht</li>
                <li>Bei Fehlern wird eine detaillierte Fehlermeldung angezeigt</li>
                <li>Nach erfolgreicher Migration kannst du das Script löschen</li>
            </ol>
        </div>
        
        <button class="btn" onclick="runMigration()" id="migrateBtn">
            🚀 Migration starten
        </button>
        
        <div id="result"></div>
    </div>
    
    <script>
        async function runMigration() {
            const btn = document.getElementById('migrateBtn');
            const resultDiv = document.getElementById('result');
            
            btn.disabled = true;
            btn.textContent = '⏳ Migration läuft...';
            resultDiv.innerHTML = '';
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=migrate'
                });
                
                // Prüfe ob Response OK ist
                if (!response.ok) {
                    throw new Error(`HTTP Fehler: ${response.status} ${response.statusText}`);
                }
                
                // Hole Response Text
                const responseText = await response.text();
                
                // Versuche JSON zu parsen
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    // Zeige die ersten 500 Zeichen der Response für Debugging
                    throw new Error('Ungültige JSON-Antwort. Server-Antwort:\n\n' + responseText.substring(0, 500));
                }
                
                if (data.success) {
                    resultDiv.className = 'result success';
                    resultDiv.innerHTML = `
                        <strong>✅ Migration erfolgreich!</strong><br><br>
                        ${data.messages.join('<br>')}
                        <br><br>
                        <strong>Nächste Schritte:</strong><br>
                        1. Gehe zum Admin-Dashboard → Freebies → Template erstellen<br>
                        2. Wähle die passende Nische aus dem Dropdown<br>
                        3. Die Nische wird automatisch im Customer Dashboard angezeigt<br>
                        <br>
                        <em>Du kannst dieses Migrations-Script jetzt löschen.</em>
                    `;
                } else {
                    throw new Error(data.error || 'Unbekannter Fehler');
                }
            } catch (error) {
                resultDiv.className = 'result error';
                let errorDetails = error.message;
                
                // Füge Stack-Trace hinzu falls vorhanden
                if (error.stack) {
                    errorDetails += '\n\nStack Trace:\n' + error.stack;
                }
                
                resultDiv.innerHTML = `
                    <strong>❌ Fehler bei der Migration</strong><br><br>
                    <pre>${errorDetails}</pre>
                    <br>
                    <strong>Mögliche Lösungen:</strong><br>
                    1. Prüfe die Datenbankverbindung in config/database.php<br>
                    2. Stelle sicher, dass die Datenbank erreichbar ist<br>
                    3. Prüfe ob die Tabellen 'freebies' und 'customer_freebies' existieren<br>
                    4. Kontaktiere den Support mit dem obigen Fehler
                `;
            } finally {
                btn.disabled = false;
                btn.textContent = '🔄 Erneut versuchen';
            }
        }
    </script>
</body>
</html>