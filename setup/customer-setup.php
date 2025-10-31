<?php
/**
 * Setup-Skript für Kundenverwaltung
 * Führt automatisch alle Datenbank-Änderungen durch
 * 
 * ACHTUNG: Nach Ausführung diese Datei löschen!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

$pdo = getDBConnection();
$messages = [];
$errors = [];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Kundenverwaltung</title>
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
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .step {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .step h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Setup: Kundenverwaltung</h1>
        <p class="subtitle">Digistore24 Integration & Admin-Dashboard</p>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            
            <?php
            // Setup ausführen
            try {
                // 1. Users-Tabelle erweitern
                $messages[] = "✅ Erweitere Users-Tabelle...";
                
                $queries = [
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS raw_code VARCHAR(50) UNIQUE AFTER email",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS digistore_order_id VARCHAR(100) AFTER raw_code",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS digistore_product_id VARCHAR(100) AFTER digistore_order_id",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS digistore_product_name VARCHAR(255) AFTER digistore_product_id",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'manual' AFTER digistore_product_name",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS refund_date DATETIME NULL AFTER source",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER role",
                    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL AFTER created_at",
                ];
                
                foreach ($queries as $query) {
                    try {
                        $pdo->exec($query);
                    } catch (PDOException $e) {
                        // Spalte existiert bereits - OK
                        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                            throw $e;
                        }
                    }
                }
                
                $messages[] = "✅ Users-Tabelle erfolgreich erweitert!";
                
                // 2. Indexes hinzufügen
                $messages[] = "✅ Erstelle Indexes...";
                
                try {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_raw_code ON users(raw_code)");
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_digistore_order ON users(digistore_order_id)");
                } catch (PDOException $e) {
                    // Index existiert bereits - OK
                }
                
                // 3. Freebie Templates Tabelle erstellen (falls nicht vorhanden)
                $messages[] = "✅ Erstelle freebie_templates Tabelle (falls nicht vorhanden)...";
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS freebie_templates (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        title VARCHAR(255) NOT NULL,
                        description TEXT NULL,
                        content LONGTEXT NULL,
                        thumbnail VARCHAR(500) NULL,
                        category VARCHAR(100) NULL,
                        is_active TINYINT(1) DEFAULT 1,
                        created_by INT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_category (category),
                        INDEX idx_active (is_active),
                        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $messages[] = "✅ freebie_templates Tabelle erstellt!";
                
                // 4. user_freebies Tabelle erstellen
                $messages[] = "✅ Erstelle user_freebies Tabelle...";
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_freebies (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        freebie_id INT NOT NULL,
                        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        assigned_by INT NULL,
                        completed TINYINT(1) DEFAULT 0,
                        completed_at DATETIME NULL,
                        INDEX idx_user (user_id),
                        INDEX idx_freebie (freebie_id),
                        INDEX idx_assigned (assigned_at),
                        UNIQUE KEY unique_assignment (user_id, freebie_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (freebie_id) REFERENCES freebie_templates(id) ON DELETE CASCADE,
                        FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $messages[] = "✅ user_freebies Tabelle erstellt!";
                
                // 5. user_progress Tabelle erstellen
                $messages[] = "✅ Erstelle user_progress Tabelle...";
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS user_progress (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        content_type ENUM('course', 'tutorial', 'freebie') NOT NULL,
                        content_id INT NOT NULL,
                        progress INT DEFAULT 0,
                        last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        completed TINYINT(1) DEFAULT 0,
                        completed_at DATETIME NULL,
                        INDEX idx_user_progress (user_id, content_type, content_id),
                        INDEX idx_last_accessed (last_accessed),
                        UNIQUE KEY unique_progress (user_id, content_type, content_id),
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $messages[] = "✅ user_progress Tabelle erstellt!";
                
                // 6. Webhook-Logs Tabelle
                $messages[] = "✅ Erstelle webhook_logs Tabelle...";
                
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS webhook_logs (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        event_type VARCHAR(100) NOT NULL,
                        webhook_data JSON NOT NULL,
                        ip_address VARCHAR(45) NULL,
                        user_agent TEXT NULL,
                        processed TINYINT(1) DEFAULT 0,
                        error_message TEXT NULL,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_event_type (event_type),
                        INDEX idx_created (created_at),
                        INDEX idx_processed (processed)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
                
                $messages[] = "✅ webhook_logs Tabelle erstellt!";
                
                // 7. RAW-Codes für existierende Kunden
                $messages[] = "✅ Generiere RAW-Codes für existierende Kunden...";
                
                $stmt = $pdo->query("SELECT id FROM users WHERE role = 'customer' AND (raw_code IS NULL OR raw_code = '')");
                $usersWithoutCode = $stmt->fetchAll();
                
                $generatedCodes = 0;
                foreach ($usersWithoutCode as $user) {
                    // Sicherstellen dass der RAW-Code einzigartig ist
                    $attempts = 0;
                    while ($attempts < 10) {
                        $rawCode = 'RAW-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                        
                        // Prüfen ob Code bereits existiert
                        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE raw_code = ?");
                        $checkStmt->execute([$rawCode]);
                        
                        if (!$checkStmt->fetch()) {
                            // Code ist einzigartig, verwenden
                            $updateStmt = $pdo->prepare("UPDATE users SET raw_code = ? WHERE id = ?");
                            $updateStmt->execute([$rawCode, $user['id']]);
                            $generatedCodes++;
                            break;
                        }
                        $attempts++;
                    }
                }
                
                $messages[] = "✅ " . $generatedCodes . " RAW-Codes generiert!";
                
                // 8. Source setzen
                $pdo->exec("UPDATE users SET source = 'manual' WHERE role = 'customer' AND (source IS NULL OR source = '')");
                
                $messages[] = "✅ Source für existierende Kunden gesetzt!";
                
                // 9. is_active auf 1 setzen für alle die NULL haben
                $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL");
                
                $messages[] = "✅ is_active Status aktualisiert!";
                
                $messages[] = "🎉 Setup erfolgreich abgeschlossen!";
                
            } catch (Exception $e) {
                $errors[] = "❌ Fehler: " . $e->getMessage();
            }
            
            // Statistiken abrufen (mit Try-Catch für robustheit)
            $stats = [
                'total_users' => 0,
                'customers' => 0,
                'admins' => 0,
                'active' => 0,
                'freebies' => 0,
                'assignments' => 0,
            ];
            
            try {
                $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                $stats['customers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
                $stats['admins'] = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
                $stats['active'] = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn();
            } catch (Exception $e) {
                // Tabelle existiert nicht
            }
            
            try {
                $stats['freebies'] = $pdo->query("SELECT COUNT(*) FROM freebie_templates")->fetchColumn();
            } catch (Exception $e) {
                // Tabelle existiert nicht
            }
            
            try {
                $stats['assignments'] = $pdo->query("SELECT COUNT(*) FROM user_freebies")->fetchColumn();
            } catch (Exception $e) {
                // Tabelle existiert nicht
            }
            
            ?>
            
            <!-- Ergebnis anzeigen -->
            <?php foreach ($messages as $msg): ?>
                <div class="step success">
                    <p><?php echo $msg; ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php foreach ($errors as $err): ?>
                <div class="step error">
                    <p><?php echo $err; ?></p>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($errors) === 0): ?>
                <div class="step success">
                    <h3>📊 Datenbank-Status</h3>
                    <div class="status-grid">
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Gesamt Users</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['customers']; ?></div>
                            <div class="stat-label">Kunden</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['admins']; ?></div>
                            <div class="stat-label">Admins</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['active']; ?></div>
                            <div class="stat-label">Aktiv</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['freebies']; ?></div>
                            <div class="stat-label">Freebies</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value"><?php echo $stats['assignments']; ?></div>
                            <div class="stat-label">Zuweisungen</div>
                        </div>
                    </div>
                </div>
                
                <div class="step warning">
                    <h3>⚠️ Nächste Schritte</h3>
                    <ol style="margin-left: 20px; margin-top: 10px;">
                        <li>Webhook in Digistore24 einrichten:<br>
                            <code>https://app.mehr-infos-jetzt.de/webhook/digistore24.php</code></li>
                        <li>Events aktivieren: payment.success, subscription.created, refund.created</li>
                        <li>DIESE DATEI LÖSCHEN aus Sicherheitsgründen!</li>
                        <li>Zum Admin-Dashboard gehen: <a href="/admin/dashboard.php?page=users">Kundenverwaltung öffnen</a></li>
                    </ol>
                </div>
                
                <button class="btn btn-danger" onclick="if(confirm('Diese Datei wirklich löschen?')) window.location='?delete=1'">
                    🗑️ Setup-Datei löschen
                </button>
                
                <a href="/admin/dashboard.php?page=users">
                    <button class="btn">
                        ➡️ Zur Kundenverwaltung
                    </button>
                </a>
            <?php endif; ?>
            
        <?php else: ?>
            
            <!-- Setup-Formular -->
            <div class="step">
                <h3>📋 Was wird gemacht?</h3>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>✅ Erweitert die <code>users</code> Tabelle um Digistore24-Felder</li>
                    <li>✅ Erstellt <code>freebie_templates</code> Tabelle (falls nicht vorhanden)</li>
                    <li>✅ Erstellt <code>user_freebies</code> Tabelle für Zuweisungen</li>
                    <li>✅ Erstellt <code>user_progress</code> Tabelle für Fortschritte</li>
                    <li>✅ Erstellt <code>webhook_logs</code> Tabelle für Debugging</li>
                    <li>✅ Generiert RAW-Codes für existierende Kunden</li>
                    <li>✅ Setzt notwendige Indexes</li>
                </ul>
            </div>
            
            <div class="step warning">
                <h3>⚠️ Wichtig</h3>
                <p>Das Setup kann nur einmal ausgeführt werden. Bereits vorhandene Daten bleiben erhalten.</p>
            </div>
            
            <form method="POST">
                <button type="submit" class="btn">
                    🚀 Setup jetzt starten
                </button>
            </form>
            
        <?php endif; ?>
        
        <?php
        // Datei löschen wenn angefordert
        if (isset($_GET['delete']) && $_GET['delete'] === '1') {
            if (unlink(__FILE__)) {
                echo '<div class="step success"><p>✅ Setup-Datei erfolgreich gelöscht!</p></div>';
                echo '<meta http-equiv="refresh" content="2;url=/admin/dashboard.php?page=users">';
            } else {
                echo '<div class="step error"><p>❌ Fehler beim Löschen. Bitte manuell löschen: ' . __FILE__ . '</p></div>';
            }
        }
        ?>
    </div>
</body>
</html>
