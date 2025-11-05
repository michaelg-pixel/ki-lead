<?php
/**
 * 🔧 Browser-Migration Script
 * Führt die Bullet Icon Style Migration aus
 * 
 * Einfach im Browser aufrufen: https://deine-domain.de/database/run-migration.php
 */

// Sicherheitscheck: Nur im Development-Modus erlauben (kann später entfernt werden)
$ALLOW_BROWSER_MIGRATION = true; // Setze auf false nach der Migration!

if (!$ALLOW_BROWSER_MIGRATION) {
    die('🔒 Migration-Script ist deaktiviert. Setze $ALLOW_BROWSER_MIGRATION = true in der Datei.');
}

require_once __DIR__ . '/../config/database.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Datenbank Migration</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #1a1a2e;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .info-box h3 {
            color: #1e40af;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .info-box p {
            color: #1e3a8a;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .status-box {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            min-height: 100px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .status-icon {
            font-size: 20px;
        }
        
        .success {
            color: #059669;
        }
        
        .error {
            color: #dc2626;
        }
        
        .warning {
            color: #d97706;
        }
        
        .button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
        
        .code-block {
            background: #1f2937;
            color: #f3f4f6;
            padding: 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 16px 0;
        }
        
        .warning-box {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .warning-box h3 {
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .warning-box p {
            color: #78350f;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Datenbank Migration</h1>
        <p class="subtitle">Bullet Icon Style Feature - Migration</p>
        
        <div class="info-box">
            <h3>ℹ️ Was macht diese Migration?</h3>
            <p>
                Diese Migration fügt das neue Feld <code>bullet_icon_style</code> zur Tabelle 
                <code>customer_freebies</code> hinzu. Damit können Benutzer zwischen 
                Standard-Checkmarken und eigenen Icons für Bulletpoints wählen.
            </p>
        </div>
        
        <?php if (isset($_GET['run']) && $_GET['run'] === 'true'): ?>
            
            <div class="status-box">
                <?php
                $results = [];
                $hasError = false;
                
                try {
                    $pdo = getDBConnection();
                    
                    // 1. Prüfen ob Tabelle existiert
                    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
                    if ($stmt->rowCount() === 0) {
                        $results[] = ['icon' => '❌', 'message' => 'Tabelle customer_freebies nicht gefunden!', 'class' => 'error'];
                        $hasError = true;
                    } else {
                        $results[] = ['icon' => '✓', 'message' => 'Tabelle customer_freebies gefunden', 'class' => 'success'];
                    }
                    
                    if (!$hasError) {
                        // 2. Prüfen ob Spalte bereits existiert
                        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'bullet_icon_style'");
                        if ($stmt->rowCount() > 0) {
                            $results[] = ['icon' => '⚠️', 'message' => 'Spalte bullet_icon_style existiert bereits', 'class' => 'warning'];
                        } else {
                            // 3. Spalte hinzufügen
                            $sql = "ALTER TABLE customer_freebies 
                                    ADD COLUMN bullet_icon_style VARCHAR(20) DEFAULT 'standard' 
                                    COMMENT 'Bullet point style: standard (checkmarks) oder custom (eigene Icons/Emojis)'";
                            $pdo->exec($sql);
                            $results[] = ['icon' => '✓', 'message' => 'Spalte bullet_icon_style erfolgreich hinzugefügt', 'class' => 'success'];
                            
                            // 4. Index hinzufügen
                            try {
                                $pdo->exec("CREATE INDEX idx_bullet_icon_style ON customer_freebies(bullet_icon_style)");
                                $results[] = ['icon' => '✓', 'message' => 'Index idx_bullet_icon_style erfolgreich erstellt', 'class' => 'success'];
                            } catch (PDOException $e) {
                                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                                    $results[] = ['icon' => '⚠️', 'message' => 'Index idx_bullet_icon_style existiert bereits', 'class' => 'warning'];
                                } else {
                                    throw $e;
                                }
                            }
                        }
                        
                        // 5. Verifizierung
                        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'bullet_icon_style'");
                        if ($stmt->rowCount() > 0) {
                            $column = $stmt->fetch(PDO::FETCH_ASSOC);
                            $results[] = ['icon' => '✓', 'message' => 'Verifizierung erfolgreich: Spalte ist vorhanden', 'class' => 'success'];
                            $results[] = ['icon' => 'ℹ️', 'message' => 'Default-Wert: ' . $column['Default'], 'class' => 'success'];
                        }
                        
                        $results[] = ['icon' => '🎉', 'message' => 'Migration erfolgreich abgeschlossen!', 'class' => 'success'];
                    }
                    
                } catch (PDOException $e) {
                    $results[] = ['icon' => '❌', 'message' => 'Fehler: ' . $e->getMessage(), 'class' => 'error'];
                    $hasError = true;
                }
                
                // Ergebnisse anzeigen
                foreach ($results as $result) {
                    echo '<div class="status-item ' . $result['class'] . '">';
                    echo '<span class="status-icon">' . $result['icon'] . '</span>';
                    echo '<span>' . htmlspecialchars($result['message']) . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
            
            <?php if (!$hasError): ?>
                <div class="warning-box">
                    <h3>🔒 Wichtig: Sicherheitshinweis</h3>
                    <p>
                        Die Migration wurde erfolgreich durchgeführt! Bitte setze jetzt 
                        <code>$ALLOW_BROWSER_MIGRATION = false;</code> in dieser Datei 
                        (<code>database/run-migration.php</code>), um das Script zu deaktivieren.
                    </p>
                </div>
                
                <a href="/customer/dashboard.php" class="button">
                    ✅ Zum Dashboard
                </a>
            <?php else: ?>
                <a href="?run=true" class="button">
                    🔄 Migration erneut versuchen
                </a>
            <?php endif; ?>
            
        <?php else: ?>
            
            <div class="warning-box">
                <h3>⚠️ Wichtig vor der Ausführung</h3>
                <p>
                    <strong>Empfehlung:</strong> Erstelle ein Backup deiner Datenbank, bevor du die Migration ausführst.
                    Die Migration ist sicher und ändert nur die Tabellenstruktur, nicht die vorhandenen Daten.
                </p>
            </div>
            
            <div class="info-box">
                <h3>📋 Migration-Details</h3>
                <p><strong>Tabelle:</strong> customer_freebies</p>
                <p><strong>Neue Spalte:</strong> bullet_icon_style</p>
                <p><strong>Typ:</strong> VARCHAR(20)</p>
                <p><strong>Default:</strong> 'standard'</p>
                <p><strong>Index:</strong> idx_bullet_icon_style</p>
            </div>
            
            <a href="?run=true" class="button">
                🚀 Migration jetzt ausführen
            </a>
            
        <?php endif; ?>
        
        <div class="footer">
            <p>💡 Bei Problemen siehe: <code>docs/BULLET_ICON_FEATURE.md</code></p>
        </div>
    </div>
</body>
</html>
