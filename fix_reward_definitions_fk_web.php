<?php
/**
 * Browser-Fix für Reward Definitions Foreign Key
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/fix_reward_definitions_fk_web.php
 * 
 * SICHERHEIT: Nach erfolgreicher Ausführung sollte diese Datei gelöscht werden!
 */

// Einfacher Passwortschutz (ÄNDERE DAS PASSWORT!)
$PASSWORT = 'fix2024'; // <-- HIER DEIN PASSWORT EINTRAGEN

// Passwort-Check
if (!isset($_GET['pass']) || $_GET['pass'] !== $PASSWORT) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Zugriff verweigert</title>
        <style>
            body { font-family: Arial; background: #1a1a1a; color: white; padding: 40px; text-align: center; }
            .box { background: #2a2a2a; padding: 30px; border-radius: 10px; max-width: 400px; margin: 0 auto; }
            input { padding: 10px; width: 100%; margin: 10px 0; border-radius: 5px; border: none; }
            button { background: #667eea; color: white; padding: 10px 30px; border: none; border-radius: 5px; cursor: pointer; }
        </style>
    </head>
    <body>
        <div class="box">
            <h2>🔒 Passwort erforderlich</h2>
            <form method="GET">
                <input type="password" name="pass" placeholder="Passwort eingeben" autofocus>
                <button type="submit">Ausführen</button>
            </form>
        </div>
    </body>
    </html>
    ');
}

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreign Key Fix</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .header p {
            opacity: 0.8;
            font-size: 14px;
        }
        .content {
            padding: 30px;
        }
        .step {
            background: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .step-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .step-title {
            font-weight: 600;
            font-size: 16px;
            color: #1f2937;
        }
        .step-content {
            padding-left: 40px;
            color: #6b7280;
            line-height: 1.6;
        }
        .success {
            background: #d1fae5;
            border-left-color: #10b981;
        }
        .success .step-number {
            background: #10b981;
        }
        .error {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        .error .step-number {
            background: #ef4444;
        }
        .warning {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }
        .warning .step-number {
            background: #f59e0b;
        }
        .info {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }
        .info .step-number {
            background: #3b82f6;
        }
        .code {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 5px 0;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .final-box {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-top: 30px;
        }
        .final-box h2 {
            font-size: 24px;
            margin-bottom: 15px;
        }
        .final-box ul {
            list-style: none;
            margin-top: 20px;
        }
        .final-box li {
            padding: 10px;
            background: rgba(255,255,255,0.1);
            margin: 5px 0;
            border-radius: 5px;
        }
        .delete-notice {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            font-weight: 600;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Reward Definitions Foreign Key Fix</h1>
            <p>Automatische Reparatur des Datenbank-Foreign-Keys</p>
        </div>
        
        <div class="content">
            <?php
            $step = 1;
            $errors = [];
            
            try {
                $pdo = getDBConnection();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // SCHRITT 1: Aktuellen FK prüfen
                echo '<div class="step info">';
                echo '<div class="step-header">';
                echo '<div class="step-number">' . $step++ . '</div>';
                echo '<div class="step-title">Prüfe aktuellen Foreign Key...</div>';
                echo '</div>';
                echo '<div class="step-content">';
                
                $stmt = $pdo->query("
                    SELECT 
                        CONSTRAINT_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'reward_definitions'
                        AND COLUMN_NAME = 'freebie_id'
                        AND CONSTRAINT_NAME LIKE 'fk_%'
                ");
                
                $existing_fk = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_fk) {
                    echo '<span class="status-badge badge-info">Foreign Key gefunden</span>';
                    echo '<div class="code">';
                    echo 'Name: ' . $existing_fk['CONSTRAINT_NAME'] . '<br>';
                    echo 'Verweist auf: ' . $existing_fk['REFERENCED_TABLE_NAME'] . '(' . $existing_fk['REFERENCED_COLUMN_NAME'] . ')';
                    echo '</div>';
                    
                    if ($existing_fk['REFERENCED_TABLE_NAME'] === 'customer_freebies') {
                        echo '<span class="status-badge badge-success">✓ Foreign Key ist bereits korrekt!</span>';
                        $already_fixed = true;
                    } else {
                        echo '<span class="status-badge badge-warning">⚠ Foreign Key verweist auf falsche Tabelle</span>';
                        $needs_fix = true;
                    }
                } else {
                    echo '<span class="status-badge badge-warning">Kein Foreign Key gefunden</span>';
                    $needs_fix = true;
                }
                
                echo '</div></div>';
                
                // SCHRITT 2: FK entfernen (falls nötig)
                if (isset($needs_fix) && $existing_fk) {
                    echo '<div class="step warning">';
                    echo '<div class="step-header">';
                    echo '<div class="step-number">' . $step++ . '</div>';
                    echo '<div class="step-title">Entferne fehlerhaften Foreign Key...</div>';
                    echo '</div>';
                    echo '<div class="step-content">';
                    
                    $pdo->exec("ALTER TABLE reward_definitions DROP FOREIGN KEY {$existing_fk['CONSTRAINT_NAME']}");
                    echo '<span class="status-badge badge-success">✓ Foreign Key entfernt</span>';
                    
                    echo '</div></div>';
                }
                
                // SCHRITT 3: Neuen FK erstellen
                if (isset($needs_fix)) {
                    echo '<div class="step success">';
                    echo '<div class="step-header">';
                    echo '<div class="step-number">' . $step++ . '</div>';
                    echo '<div class="step-title">Erstelle korrekten Foreign Key...</div>';
                    echo '</div>';
                    echo '<div class="step-content">';
                    
                    try {
                        $pdo->exec("
                            ALTER TABLE reward_definitions 
                            ADD CONSTRAINT fk_reward_def_customer_freebie
                            FOREIGN KEY (freebie_id) 
                            REFERENCES customer_freebies(id) 
                            ON DELETE SET NULL
                            ON UPDATE CASCADE
                        ");
                        echo '<span class="status-badge badge-success">✓ Foreign Key erfolgreich erstellt</span>';
                        echo '<div class="code">fk_reward_def_customer_freebie → customer_freebies(id)</div>';
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate') !== false) {
                            echo '<span class="status-badge badge-info">ℹ Foreign Key existiert bereits</span>';
                        } else {
                            throw $e;
                        }
                    }
                    
                    echo '</div></div>';
                }
                
                // SCHRITT 4: Verifizierung
                echo '<div class="step success">';
                echo '<div class="step-header">';
                echo '<div class="step-number">' . $step++ . '</div>';
                echo '<div class="step-title">Verifiziere neuen Foreign Key...</div>';
                echo '</div>';
                echo '<div class="step-content">';
                
                $stmt = $pdo->query("
                    SELECT 
                        CONSTRAINT_NAME,
                        REFERENCED_TABLE_NAME,
                        REFERENCED_COLUMN_NAME
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME = 'reward_definitions'
                        AND COLUMN_NAME = 'freebie_id'
                        AND CONSTRAINT_NAME = 'fk_reward_def_customer_freebie'
                ");
                
                $new_fk = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($new_fk && $new_fk['REFERENCED_TABLE_NAME'] === 'customer_freebies') {
                    echo '<span class="status-badge badge-success">✓ Foreign Key korrekt erstellt!</span>';
                    echo '<div class="code">';
                    echo $new_fk['CONSTRAINT_NAME'] . ' → ' . $new_fk['REFERENCED_TABLE_NAME'] . '(' . $new_fk['REFERENCED_COLUMN_NAME'] . ')';
                    echo '</div>';
                } else {
                    throw new Exception("Foreign Key konnte nicht verifiziert werden!");
                }
                
                echo '</div></div>';
                
                // SCHRITT 5: Daten-Integrität
                echo '<div class="step info">';
                echo '<div class="step-header">';
                echo '<div class="step-number">' . $step++ . '</div>';
                echo '<div class="step-title">Prüfe Daten-Integrität...</div>';
                echo '</div>';
                echo '<div class="step-content">';
                
                $stmt = $pdo->query("
                    SELECT 
                        COUNT(*) as total,
                        COUNT(freebie_id) as with_freebie,
                        COUNT(*) - COUNT(freebie_id) as without_freebie
                    FROM reward_definitions
                ");
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo '<div class="code">';
                echo 'Gesamt Belohnungen: ' . $stats['total'] . '<br>';
                echo 'Mit Freebie: ' . $stats['with_freebie'] . '<br>';
                echo 'Ohne Freebie (allgemein): ' . $stats['without_freebie'];
                echo '</div>';
                
                // Prüfe ungültige Referenzen
                $stmt = $pdo->query("
                    SELECT COUNT(*) as invalid_count
                    FROM reward_definitions rd
                    LEFT JOIN customer_freebies cf ON rd.freebie_id = cf.id
                    WHERE rd.freebie_id IS NOT NULL 
                        AND cf.id IS NULL
                ");
                $invalid = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($invalid['invalid_count'] > 0) {
                    echo '<span class="status-badge badge-warning">⚠ ' . $invalid['invalid_count'] . ' ungültige Referenzen gefunden</span>';
                } else {
                    echo '<span class="status-badge badge-success">✓ Alle Referenzen sind gültig</span>';
                }
                
                echo '</div></div>';
                
                // ERFOLG
                echo '<div class="final-box">';
                echo '<h2>✅ Fix erfolgreich abgeschlossen!</h2>';
                echo '<p>Der Foreign Key wurde erfolgreich korrigiert.</p>';
                echo '<ul>';
                echo '<li>✓ Foreign Key verweist nun auf customer_freebies(id)</li>';
                echo '<li>✓ Alle Daten sind intakt</li>';
                echo '<li>✓ Belohnungsstufen können jetzt gespeichert werden</li>';
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="delete-notice">';
                echo '⚠️ WICHTIG: Lösche diese Datei jetzt aus Sicherheitsgründen!<br>';
                echo '<code>rm fix_reward_definitions_fk_web.php</code>';
                echo '</div>';
                
            } catch (PDOException $e) {
                echo '<div class="step error">';
                echo '<div class="step-header">';
                echo '<div class="step-number">❌</div>';
                echo '<div class="step-title">Datenbankfehler</div>';
                echo '</div>';
                echo '<div class="step-content">';
                echo '<span class="status-badge badge-error">Fehler</span>';
                echo '<div class="code">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div></div>';
            } catch (Exception $e) {
                echo '<div class="step error">';
                echo '<div class="step-header">';
                echo '<div class="step-number">❌</div>';
                echo '<div class="step-title">Fehler</div>';
                echo '</div>';
                echo '<div class="step-content">';
                echo '<span class="status-badge badge-error">Fehler</span>';
                echo '<div class="code">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '</div></div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
