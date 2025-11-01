<?php
/**
 * EINFACHES Setup-Skript für Kundengesteuerte Freebie-Erstellung
 * 
 * Einfach im Browser aufrufen: https://deine-domain.de/setup/easy-setup.php
 * 
 * ⚠️ WICHTIG: Diese Datei nach erfolgreichem Setup SOFORT löschen!
 */

session_start();

// Setup-Status in Session speichern
$setupDone = $_SESSION['setup_done'] ?? false;
$setupPassword = 'setup2024'; // Standard-Passwort (wird beim ersten Aufruf gesetzt)

// Schritt 1: Passwort-Eingabe
if (!isset($_POST['password']) && !$setupDone) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup - Kundengesteuerte Freebies</title>
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
                border-radius: 20px;
                padding: 50px;
                max-width: 500px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
            }
            h1 {
                font-size: 32px;
                color: #1a1a2e;
                margin-bottom: 10px;
            }
            .emoji { font-size: 64px; margin-bottom: 20px; }
            p {
                color: #64748b;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            .form-group {
                margin-bottom: 25px;
                text-align: left;
            }
            label {
                display: block;
                font-weight: 600;
                color: #1a1a2e;
                margin-bottom: 8px;
                font-size: 14px;
            }
            input[type="password"] {
                width: 100%;
                padding: 15px;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                font-size: 16px;
                transition: all 0.2s;
            }
            input[type="password"]:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            }
            .btn {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
            .info-box {
                background: #f1f5f9;
                padding: 20px;
                border-radius: 10px;
                margin-top: 25px;
                text-align: left;
            }
            .info-box h3 {
                font-size: 14px;
                color: #1a1a2e;
                margin-bottom: 10px;
                font-weight: 600;
            }
            .info-box p {
                font-size: 13px;
                color: #64748b;
                margin-bottom: 0;
                line-height: 1.5;
            }
            code {
                background: #1e293b;
                color: #60a5fa;
                padding: 3px 8px;
                border-radius: 4px;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="emoji">🎁</div>
            <h1>Setup starten</h1>
            <p>Gib ein Passwort ein, um das Setup zu starten. Dieses Passwort schützt den Setup-Prozess.</p>
            
            <form method="POST">
                <div class="form-group">
                    <label for="password">Setup-Passwort</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="Mindestens 6 Zeichen" 
                           required 
                           minlength="6"
                           autocomplete="off">
                </div>
                
                <button type="submit" class="btn">🚀 Setup starten</button>
            </form>
            
            <div class="info-box">
                <h3>💡 Was passiert beim Setup?</h3>
                <p>
                    • Datenbank-Tabellen werden erstellt<br>
                    • Beispiel-Konfigurationen werden eingefügt<br>
                    • System wird automatisch eingerichtet<br>
                    • Du erhältst die Webhook-URL für Digistore24
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Schritt 2: Setup durchführen
if (isset($_POST['password']) && !$setupDone) {
    $password = $_POST['password'];
    
    if (strlen($password) < 6) {
        die('Passwort muss mindestens 6 Zeichen lang sein!');
    }
    
    // Passwort in Session speichern
    $_SESSION['setup_password'] = password_hash($password, PASSWORD_DEFAULT);
    
    // Datenbank-Setup durchführen
    require_once __DIR__ . '/../config/database.php';
    
    try {
        $pdo = getDBConnection();
        $errors = [];
        $warnings = [];
        $success = [];
        
        // Transaktion starten
        $pdo->beginTransaction();
        
        // 1. Tabelle customer_freebie_limits erstellen
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS customer_freebie_limits (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT NOT NULL,
                freebie_limit INT DEFAULT 0,
                product_id VARCHAR(100),
                product_name VARCHAR(255),
                granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_customer (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($sql);
            $success[] = "Tabelle 'customer_freebie_limits' erstellt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $warnings[] = "Tabelle 'customer_freebie_limits' existiert bereits";
            } else {
                throw $e;
            }
        }
        
        // 2. Spalte freebie_type hinzufügen
        try {
            $sql = "
            ALTER TABLE customer_freebies 
            ADD COLUMN freebie_type ENUM('template', 'custom') DEFAULT 'template' AFTER customer_id
            ";
            $pdo->exec($sql);
            $success[] = "Spalte 'freebie_type' hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $warnings[] = "Spalte 'freebie_type' existiert bereits";
            } else {
                throw $e;
            }
        }
        
        // 3. Index für freebie_type
        try {
            $sql = "CREATE INDEX idx_freebie_type ON customer_freebies(freebie_type)";
            $pdo->exec($sql);
            $success[] = "Index 'idx_freebie_type' erstellt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false) {
                $warnings[] = "Index 'idx_freebie_type' existiert bereits";
            }
        }
        
        // 4. Index für customer_id + freebie_type
        try {
            $sql = "CREATE INDEX idx_customer_type ON customer_freebies(customer_id, freebie_type)";
            $pdo->exec($sql);
            $success[] = "Index 'idx_customer_type' erstellt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') !== false) {
                $warnings[] = "Index 'idx_customer_type' existiert bereits";
            }
        }
        
        // 5. Tabelle product_freebie_config erstellen
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS product_freebie_config (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id VARCHAR(100) NOT NULL UNIQUE,
                product_name VARCHAR(255),
                freebie_limit INT DEFAULT 5,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $pdo->exec($sql);
            $success[] = "Tabelle 'product_freebie_config' erstellt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $warnings[] = "Tabelle 'product_freebie_config' existiert bereits";
            } else {
                throw $e;
            }
        }
        
        // 6. Beispiel-Konfigurationen einfügen
        try {
            $sql = "
            INSERT INTO product_freebie_config (product_id, product_name, freebie_limit, is_active) VALUES
            ('STARTER_001', 'Starter Paket', 5, 1),
            ('PROFESSIONAL_002', 'Professional Paket', 10, 1),
            ('ENTERPRISE_003', 'Enterprise Paket', 25, 1),
            ('UNLIMITED_004', 'Unlimited Paket', 999, 1)
            ON DUPLICATE KEY UPDATE 
                product_name = VALUES(product_name),
                freebie_limit = VALUES(freebie_limit)
            ";
            $pdo->exec($sql);
            $success[] = "Beispiel-Produkt-Konfigurationen eingefügt";
        } catch (PDOException $e) {
            $warnings[] = "Beispiel-Konfigurationen: " . $e->getMessage();
        }
        
        // Transaktion abschließen
        $pdo->commit();
        
        // Setup als erledigt markieren
        $_SESSION['setup_done'] = true;
        
        // Statistiken sammeln
        $stmt = $pdo->query("SELECT COUNT(*) FROM product_freebie_config");
        $productCount = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM customer_freebie_limits");
        $customerCount = $stmt->fetchColumn();
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $errors[] = "Kritischer Fehler: " . $e->getMessage();
    }
}

// Schritt 3: Erfolgs-Seite anzeigen
if ($setupDone || $_SESSION['setup_done'] ?? false) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup erfolgreich!</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 40px 20px;
            }
            .container {
                max-width: 900px;
                margin: 0 auto;
                background: white;
                border-radius: 20px;
                overflow: hidden;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            .header {
                background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
                padding: 40px;
                text-align: center;
                color: white;
            }
            .header .emoji { font-size: 64px; margin-bottom: 10px; }
            .header h1 { font-size: 32px; margin-bottom: 10px; }
            .header p { opacity: 0.9; }
            .content { padding: 40px; }
            .message-box {
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 10px;
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }
            .success-box {
                background: rgba(34, 197, 94, 0.1);
                border-left: 4px solid #22c55e;
                color: #15803d;
            }
            .warning-box {
                background: rgba(251, 191, 36, 0.1);
                border-left: 4px solid #fbbf24;
                color: #b45309;
            }
            .error-box {
                background: rgba(239, 68, 68, 0.1);
                border-left: 4px solid #ef4444;
                color: #b91c1c;
            }
            .section {
                margin-bottom: 30px;
            }
            .section h2 {
                font-size: 20px;
                color: #1a1a2e;
                margin-bottom: 15px;
            }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 25px;
                border-radius: 12px;
                color: white;
                text-align: center;
            }
            .stat-value {
                font-size: 36px;
                font-weight: 700;
                margin-bottom: 8px;
            }
            .stat-label {
                font-size: 14px;
                opacity: 0.9;
            }
            .next-steps {
                background: #f8fafc;
                padding: 30px;
                border-radius: 12px;
            }
            .next-steps h3 {
                font-size: 20px;
                color: #1a1a2e;
                margin-bottom: 20px;
            }
            .step {
                padding: 20px 0;
                border-bottom: 1px solid #e2e8f0;
                display: flex;
                gap: 20px;
            }
            .step:last-child { border-bottom: none; }
            .step-number {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 700;
                font-size: 18px;
                flex-shrink: 0;
            }
            .step-content { flex: 1; }
            .step-title {
                font-weight: 600;
                color: #1a1a2e;
                margin-bottom: 8px;
                font-size: 16px;
            }
            .step-desc {
                color: #64748b;
                line-height: 1.6;
                font-size: 14px;
            }
            .code-box {
                background: #1e293b;
                color: #e2e8f0;
                padding: 15px;
                border-radius: 8px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                margin-top: 10px;
                overflow-x: auto;
            }
            .warning-banner {
                background: rgba(239, 68, 68, 0.1);
                border: 2px solid #ef4444;
                padding: 20px;
                border-radius: 12px;
                margin: 30px 0;
                text-align: center;
            }
            .warning-banner h3 {
                color: #b91c1c;
                margin-bottom: 10px;
            }
            .warning-banner p {
                color: #dc2626;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 14px 28px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 10px;
                font-weight: 600;
                margin-top: 15px;
                transition: transform 0.2s;
            }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="emoji">🎉</div>
                <h1>Setup erfolgreich abgeschlossen!</h1>
                <p>Das System ist jetzt einsatzbereit</p>
            </div>
            
            <div class="content">
                <?php if (!empty($errors)): ?>
                    <div class="section">
                        <h2>❌ Fehler</h2>
                        <?php foreach ($errors as $error): ?>
                            <div class="message-box error-box">
                                <span>❌</span>
                                <span><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($warnings)): ?>
                    <div class="section">
                        <h2>⚠️ Hinweise</h2>
                        <?php foreach ($warnings as $warning): ?>
                            <div class="message-box warning-box">
                                <span>⚠️</span>
                                <span><?php echo htmlspecialchars($warning); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="section">
                        <h2>✅ Erfolgreich</h2>
                        <?php foreach ($success as $msg): ?>
                            <div class="message-box success-box">
                                <span>✅</span>
                                <span><?php echo htmlspecialchars($msg); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $productCount ?? 0; ?></div>
                        <div class="stat-label">Produkt-Konfigurationen</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $customerCount ?? 0; ?></div>
                        <div class="stat-label">Kunden mit Limits</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">✅</div>
                        <div class="stat-label">System bereit</div>
                    </div>
                </div>
                
                <div class="next-steps">
                    <h3>🚀 Nächste Schritte</h3>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Admin-Panel öffnen</div>
                            <div class="step-desc">
                                Passe die Produkt-IDs an deine Digistore24-Produkte an
                                <div class="code-box"><?php echo $_SERVER['HTTP_HOST']; ?>/admin/freebie-limits.php</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Webhook in Digistore24 einrichten</div>
                            <div class="step-desc">
                                Trage diese URL in Digistore24 ein (Einstellungen → IPN/Webhook)
                                <div class="code-box">https://<?php echo $_SERVER['HTTP_HOST']; ?>/webhook/digistore24.php</div>
                                Aktiviere Events: payment.success, subscription.created, refund.created
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">Produkt-IDs anpassen</div>
                            <div class="step-desc">
                                Ändere im Admin-Panel die Beispiel-IDs (STARTER_001, etc.) zu deinen echten Digistore24-Produkt-IDs
                            </div>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <div class="step-title">System testen</div>
                            <div class="step-desc">
                                Führe einen Test-Kauf durch und prüfe die Webhook-Logs in webhook/webhook-logs.txt
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="warning-banner">
                    <h3>🔒 WICHTIG: Setup-Datei löschen!</h3>
                    <p>
                        Lösche diese Datei SOFORT aus Sicherheitsgründen:<br>
                        <code style="background: rgba(0,0,0,0.2); padding: 5px 10px; border-radius: 5px; color: #1a1a2e;">setup/easy-setup.php</code>
                    </p>
                </div>
                
                <div style="text-align: center;">
                    <a href="/admin/freebie-limits.php" class="btn">
                        → Zum Admin-Panel
                    </a>
                    <a href="/customer/dashboard.php?page=freebies" class="btn" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                        → Zum Kunden-Dashboard
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
