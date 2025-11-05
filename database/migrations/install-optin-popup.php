<?php
/**
 * E-Mail Optin Popup Feature - Browser Installation
 * 
 * Dieses Script führt die Datenbank-Migration im Browser aus.
 * Keine Passwörter nötig - nutzt bestehende Datenbankverbindung.
 * 
 * ANLEITUNG:
 * 1. Diese Datei auf den Server hochladen nach: /database/migrations/
 * 2. Im Browser aufrufen: https://deine-domain.de/database/migrations/install-optin-popup.php
 * 3. Auf "Jetzt installieren" klicken
 * 4. Fertig! 🎉
 */

// Sicherheitscheck: Nur im Browser ausführbar
if (php_sapi_name() === 'cli') {
    die("❌ Dieses Script muss im Browser ausgeführt werden!\n");
}

// Session für Sicherheit starten
session_start();

// CSRF-Token generieren
if (!isset($_SESSION['install_token'])) {
    $_SESSION['install_token'] = bin2hex(random_bytes(32));
}

// Prüfen ob Installation bereits erfolgt ist
$installationCompleted = false;
$errorMessage = '';
$successMessage = '';
$logMessages = [];

// Datenbank-Config laden
$configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($configPath)) {
    $errorMessage = 'Datenbankconfig nicht gefunden: ' . $configPath;
}

// Installation durchführen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installationCompleted) {
    // CSRF-Check
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['install_token']) {
        $errorMessage = 'Ungültiger Sicherheitstoken. Bitte Seite neu laden.';
    } else {
        try {
            // Datenbank-Verbindung aus Config laden
            require_once $configPath;
            $pdo = getDBConnection();
            
            $logMessages[] = '✅ Datenbankverbindung erfolgreich hergestellt';
            
            // Migration durchführen
            $logMessages[] = '📋 Starte Migration...';
            
            // 1. Felder zur customer_freebies Tabelle hinzufügen
            $logMessages[] = '➡️  Erweitere customer_freebies Tabelle...';
            
            $alterQueries = [
                [
                    'query' => "ALTER TABLE customer_freebies 
                                ADD COLUMN optin_display_mode ENUM('direct', 'popup') DEFAULT 'direct' 
                                COMMENT 'Anzeige-Modus für E-Mail Optin'",
                    'field' => 'optin_display_mode'
                ],
                [
                    'query' => "ALTER TABLE customer_freebies 
                                ADD COLUMN popup_message TEXT NULL 
                                COMMENT 'Benutzerdefinierte Nachricht im Popup'",
                    'field' => 'popup_message'
                ],
                [
                    'query' => "ALTER TABLE customer_freebies 
                                ADD COLUMN cta_animation VARCHAR(50) DEFAULT 'none' 
                                COMMENT 'Animation für CTA-Button'",
                    'field' => 'cta_animation'
                ]
            ];
            
            foreach ($alterQueries as $item) {
                try {
                    // Prüfen ob Spalte bereits existiert
                    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '{$item['field']}'");
                    if ($stmt->rowCount() > 0) {
                        $logMessages[] = "   ℹ️  Feld '{$item['field']}' existiert bereits";
                    } else {
                        $pdo->exec($item['query']);
                        $logMessages[] = "   ✅ Feld '{$item['field']}' hinzugefügt";
                    }
                } catch (PDOException $e) {
                    // Fehler nur loggen, nicht abbrechen
                    $logMessages[] = "   ⚠️  Warnung bei '{$item['field']}': " . $e->getMessage();
                }
            }
            
            // 2. Felder zur freebies Tabelle hinzufügen (für Templates)
            $logMessages[] = '➡️  Erweitere freebies Tabelle...';
            
            $alterTemplateQueries = [
                [
                    'query' => "ALTER TABLE freebies 
                                ADD COLUMN optin_display_mode ENUM('direct', 'popup') DEFAULT 'direct'",
                    'field' => 'optin_display_mode'
                ],
                [
                    'query' => "ALTER TABLE freebies 
                                ADD COLUMN popup_message TEXT NULL",
                    'field' => 'popup_message'
                ],
                [
                    'query' => "ALTER TABLE freebies 
                                ADD COLUMN cta_animation VARCHAR(50) DEFAULT 'none'",
                    'field' => 'cta_animation'
                ]
            ];
            
            foreach ($alterTemplateQueries as $item) {
                try {
                    // Prüfen ob Spalte bereits existiert
                    $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE '{$item['field']}'");
                    if ($stmt->rowCount() > 0) {
                        $logMessages[] = "   ℹ️  Feld '{$item['field']}' existiert bereits";
                    } else {
                        $pdo->exec($item['query']);
                        $logMessages[] = "   ✅ Feld '{$item['field']}' hinzugefügt";
                    }
                } catch (PDOException $e) {
                    $logMessages[] = "   ⚠️  Warnung bei '{$item['field']}': " . $e->getMessage();
                }
            }
            
            // 3. Default-Werte setzen für bestehende Einträge
            $logMessages[] = '➡️  Setze Default-Werte...';
            
            try {
                $stmt = $pdo->exec("
                    UPDATE customer_freebies 
                    SET optin_display_mode = 'direct',
                        popup_message = 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
                        cta_animation = 'none'
                    WHERE optin_display_mode IS NULL OR popup_message IS NULL
                ");
                $logMessages[] = "   ✅ customer_freebies aktualisiert ({$stmt} Einträge)";
            } catch (PDOException $e) {
                $logMessages[] = "   ⚠️  Warnung bei customer_freebies Update: " . $e->getMessage();
            }
            
            try {
                $stmt = $pdo->exec("
                    UPDATE freebies 
                    SET optin_display_mode = 'direct',
                        popup_message = 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
                        cta_animation = 'none'
                    WHERE optin_display_mode IS NULL OR popup_message IS NULL
                ");
                $logMessages[] = "   ✅ freebies aktualisiert ({$stmt} Einträge)";
            } catch (PDOException $e) {
                $logMessages[] = "   ⚠️  Warnung bei freebies Update: " . $e->getMessage();
            }
            
            $installationCompleted = true;
            $successMessage = '✨ E-Mail Optin Popup Feature erfolgreich installiert!';
            
            $logMessages[] = '';
            $logMessages[] = '🎉 Migration erfolgreich abgeschlossen!';
            $logMessages[] = '';
            $logMessages[] = '📝 Neue Funktionen:';
            $logMessages[] = '  • E-Mail Optin kann als Popup angezeigt werden';
            $logMessages[] = '  • CTA-Button kann animiert werden (Pulse, Shake, Bounce, Glow)';
            $logMessages[] = '  • Custom Popup-Nachricht einstellbar';
            $logMessages[] = '  • Komplett responsive';
            
        } catch (Exception $e) {
            $errorMessage = 'Fehler bei der Installation: ' . $e->getMessage();
            $logMessages[] = '❌ FEHLER: ' . $e->getMessage();
        }
    }
}

// Prüfen ob bereits installiert
if (!$installationCompleted && !$errorMessage) {
    try {
        require_once $configPath;
        $pdo = getDBConnection();
        
        // Prüfen ob Felder bereits existieren
        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'optin_display_mode'");
        if ($stmt->rowCount() > 0) {
            $installationCompleted = true;
            $successMessage = 'Installation bereits vorhanden';
            $logMessages[] = 'ℹ️  Die Datenbank-Felder sind bereits vorhanden.';
            $logMessages[] = 'ℹ️  Keine weitere Installation nötig.';
        }
    } catch (Exception $e) {
        // Ignorieren - wird beim Install versucht
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail Optin Popup - Installation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 800;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.95;
        }
        
        .content {
            padding: 40px;
        }
        
        .feature-list {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }
        
        .feature-list h3 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        .feature-item {
            display: flex;
            align-items: start;
            gap: 12px;
            margin-bottom: 12px;
            color: #374151;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .feature-icon {
            flex-shrink: 0;
            font-size: 20px;
        }
        
        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: start;
            gap: 16px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 2px solid rgba(59, 130, 246, 0.3);
            color: #1e40af;
        }
        
        .alert-icon {
            font-size: 24px;
            flex-shrink: 0;
        }
        
        .alert-content {
            flex: 1;
        }
        
        .alert-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .log-output {
            background: #1f2937;
            color: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.8;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 24px;
        }
        
        .log-output pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .install-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .install-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.4);
        }
        
        .install-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .next-steps {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
        }
        
        .next-steps h3 {
            font-size: 18px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        
        .next-steps ol {
            margin-left: 20px;
        }
        
        .next-steps li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 24px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: gap 0.2s;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 640px) {
            .header h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 E-Mail Optin Popup</h1>
            <p>Browser-Installation</p>
        </div>
        
        <div class="content">
            <?php if ($errorMessage): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">❌</div>
                    <div class="alert-content">
                        <div class="alert-title">Installationsfehler</div>
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($successMessage && $installationCompleted): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">✨</div>
                    <div class="alert-content">
                        <div class="alert-title"><?php echo htmlspecialchars($successMessage); ?></div>
                        <p>Die Datenbank wurde erfolgreich aktualisiert.</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$installationCompleted && !$errorMessage): ?>
                <div class="feature-list">
                    <h3>📋 Was wird installiert?</h3>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>3 neue Datenbank-Felder für customer_freebies</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>3 neue Datenbank-Felder für freebies (Templates)</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>Default-Werte für bestehende Einträge</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>E-Mail Optin Popup-Funktionalität</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">✅</span>
                        <span>CTA-Button-Animationen (Pulse, Shake, Bounce, Glow)</span>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <div class="alert-icon">💡</div>
                    <div class="alert-content">
                        <div class="alert-title">Sicherer Installationsprozess</div>
                        <p>Diese Installation nutzt deine bestehende Datenbank-Konfiguration. Es werden keine Daten gelöscht oder überschrieben. Die Installation kann jederzeit wiederholt werden.</p>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['install_token']); ?>">
                    <button type="submit" class="install-button">
                        🚀 Jetzt installieren
                    </button>
                </form>
            <?php endif; ?>
            
            <?php if (!empty($logMessages)): ?>
                <div class="log-output">
                    <pre><?php echo htmlspecialchars(implode("\n", $logMessages)); ?></pre>
                </div>
            <?php endif; ?>
            
            <?php if ($installationCompleted): ?>
                <div class="next-steps">
                    <h3>🎯 Nächste Schritte</h3>
                    <ol>
                        <li><strong>Editor aktualisieren:</strong> Folge der Anleitung in QUICK_START_GUIDE.md</li>
                        <li><strong>Frontend integrieren:</strong> Popup-Code in /freebie/index.php einfügen</li>
                        <li><strong>Testen:</strong> Freebie bearbeiten und "Als Popup" aktivieren</li>
                        <li><strong>Live gehen:</strong> Feature ist einsatzbereit! 🎉</li>
                    </ol>
                </div>
                
                <a href="/customer/dashboard.php?page=freebies" class="back-link">
                    ← Zurück zum Dashboard
                </a>
            <?php endif; ?>
            
            <?php if (!$installationCompleted && !$errorMessage): ?>
                <p style="text-align: center; color: #6b7280; font-size: 14px; margin-top: 24px;">
                    Die Installation dauert nur wenige Sekunden
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
