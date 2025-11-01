<?php
/**
 * Automatisches Setup-Skript für Kundengesteuerte Freebie-Erstellung
 * 
 * WICHTIG: Lösche diese Datei nach erfolgreicher Ausführung aus Sicherheitsgründen!
 * 
 * Aufruf: https://deine-domain.de/setup/setup-customer-freebies-auto.php
 */

// Sicherheits-Token (ändere dies vor der Verwendung!)
define('SETUP_TOKEN', 'dein-geheimer-token-2024');

// Token-Check
$token = $_GET['token'] ?? '';
if ($token !== SETUP_TOKEN) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Setup - Token erforderlich</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #0f0f1e;
                color: white;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                background: rgba(255,255,255,0.1);
                padding: 40px;
                border-radius: 16px;
                text-align: center;
                max-width: 600px;
            }
            h1 { color: #f87171; margin-bottom: 20px; }
            p { line-height: 1.6; margin-bottom: 20px; }
            code {
                background: rgba(0,0,0,0.5);
                padding: 4px 8px;
                border-radius: 4px;
                color: #60a5fa;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔒 Sicherheits-Token erforderlich</h1>
            <p>Bitte öffne diese Datei und ändere das <code>SETUP_TOKEN</code> in Zeile 9.</p>
            <p>Dann rufe die URL auf mit: <code>?token=dein-token</code></p>
            <p><strong>Beispiel:</strong><br>
            <code>setup-customer-freebies-auto.php?token=dein-geheimer-token-2024</code></p>
        </div>
    </body>
    </html>
    ');
}

// Datenbank-Verbindung
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $errors = [];
    $warnings = [];
    $success = [];
    
    // Transaktion starten
    $pdo->beginTransaction();
    
    // Schritt 1: Tabelle customer_freebie_limits erstellen
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
    
    // Schritt 2: Spalte freebie_type zur customer_freebies Tabelle hinzufügen
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
    
    // Schritt 3: Index für freebie_type erstellen
    try {
        $sql = "CREATE INDEX idx_freebie_type ON customer_freebies(freebie_type)";
        $pdo->exec($sql);
        $success[] = "Index 'idx_freebie_type' erstellt";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            $warnings[] = "Index 'idx_freebie_type' existiert bereits";
        } else {
            throw $e;
        }
    }
    
    // Schritt 4: Index für customer_id + freebie_type erstellen
    try {
        $sql = "CREATE INDEX idx_customer_type ON customer_freebies(customer_id, freebie_type)";
        $pdo->exec($sql);
        $success[] = "Index 'idx_customer_type' erstellt";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            $warnings[] = "Index 'idx_customer_type' existiert bereits";
        } else {
            throw $e;
        }
    }
    
    // Schritt 5: Tabelle product_freebie_config erstellen
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
    
    // Schritt 6: Beispiel-Konfigurationen einfügen
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
        $errors[] = "Fehler beim Einfügen der Beispiel-Konfigurationen: " . $e->getMessage();
    }
    
    // Transaktion abschließen
    $pdo->commit();
    
    // Statistiken sammeln
    $stats = [];
    
    // Anzahl Tabellen prüfen
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebie_limits'");
    $stats['table_customer_limits'] = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_freebie_config'");
    $stats['table_product_config'] = $stmt->rowCount() > 0;
    
    // Anzahl Produkt-Konfigurationen
    $stmt = $pdo->query("SELECT COUNT(*) FROM product_freebie_config");
    $stats['product_configs'] = $stmt->fetchColumn();
    
    // Anzahl Kunden mit Limits
    $stmt = $pdo->query("SELECT COUNT(*) FROM customer_freebie_limits");
    $stats['customers_with_limits'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    $errors[] = "Kritischer Fehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Kundengesteuerte Freebie-Erstellung</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
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
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
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
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .message-box {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }
        
        .message-box .icon {
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .message-box .text {
            flex: 1;
            line-height: 1.5;
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
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
            margin-top: 30px;
        }
        
        .next-steps h3 {
            font-size: 18px;
            color: #1a1a2e;
            margin-bottom: 20px;
        }
        
        .step {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            gap: 15px;
        }
        
        .step:last-child {
            border-bottom: none;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 5px;
        }
        
        .step-desc {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
        
        .warning-banner {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
            padding: 20px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
        }
        
        .warning-banner h3 {
            color: #b91c1c;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .warning-banner p {
            color: #dc2626;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Setup - Kundengesteuerte Freebie-Erstellung</h1>
            <p>Automatische Datenbank-Einrichtung</p>
        </div>
        
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="section">
                    <h2>❌ Fehler</h2>
                    <?php foreach ($errors as $error): ?>
                        <div class="message-box error-box">
                            <span class="icon">❌</span>
                            <span class="text"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($warnings)): ?>
                <div class="section">
                    <h2>⚠️ Warnungen</h2>
                    <?php foreach ($warnings as $warning): ?>
                        <div class="message-box warning-box">
                            <span class="icon">⚠️</span>
                            <span class="text"><?php echo htmlspecialchars($warning); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="section">
                    <h2>✅ Erfolgreich abgeschlossen</h2>
                    <?php foreach ($success as $msg): ?>
                        <div class="message-box success-box">
                            <span class="icon">✅</span>
                            <span class="text"><?php echo htmlspecialchars($msg); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats)): ?>
                <div class="section">
                    <h2>📊 Statistiken</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['table_customer_limits'] ? '✅' : '❌'; ?></div>
                            <div class="stat-label">customer_freebie_limits</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['table_product_config'] ? '✅' : '❌'; ?></div>
                            <div class="stat-label">product_freebie_config</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['product_configs']; ?></div>
                            <div class="stat-label">Produkt-Konfigurationen</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $stats['customers_with_limits']; ?></div>
                            <div class="stat-label">Kunden mit Limits</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="next-steps">
                <h3>🚀 Nächste Schritte</h3>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <div class="step-title">Admin-Panel öffnen</div>
                        <div class="step-desc">
                            Passe die Produkt-Konfigurationen an deine Digistore24-Produkte an
                            <div class="code-block"><?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/freebie-limits.php</div>
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <div class="step-title">Webhook in Digistore24 einrichten</div>
                        <div class="step-desc">
                            Trage diese URL in Digistore24 ein (Einstellungen → IPN/Webhook)
                            <div class="code-block">https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/webhook/digistore24.php</div>
                            Events aktivieren: payment.success, subscription.created, refund.created
                        </div>
                    </div>
                </div>
                
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <div class="step-title">Produkt-IDs anpassen</div>
                        <div class="step-desc">
                            Öffne das Admin-Panel und ändere die Beispiel-Produkt-IDs (STARTER_001, etc.) 
                            zu deinen echten Digistore24-Produkt-IDs
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
                
                <div class="step">
                    <div class="step-number">5</div>
                    <div class="step-content">
                        <div class="step-title">Dokumentation lesen</div>
                        <div class="step-desc">
                            Lies die vollständige Dokumentation: CUSTOMER_FREEBIES_README.md
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="warning-banner">
                <h3>🔒 WICHTIGER SICHERHEITSHINWEIS</h3>
                <p>
                    <strong>Lösche diese Setup-Datei SOFORT nach erfolgreicher Ausführung!</strong><br>
                    Diese Datei ermöglicht Änderungen an der Datenbank und sollte nicht öffentlich zugänglich sein.
                </p>
                <div class="code-block" style="margin-top: 15px;">rm setup/setup-customer-freebies-auto.php</div>
            </div>
            
            <div style="text-align: center;">
                <a href="/admin/freebie-limits.php" class="btn">
                    → Zum Admin-Panel
                </a>
            </div>
        </div>
    </div>
</body>
</html>
