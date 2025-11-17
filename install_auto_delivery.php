<?php
/**
 * Auto-Delivery System Installation
 * Richtet das komplette automatische Belohnungsauslieferungssystem ein
 * 
 * Verwendung:
 * 1. Datei im Browser öffnen: https://app.mehr-infos-jetzt.de/install_auto_delivery.php
 * 2. Installation durchführen
 * 3. Datei danach löschen oder umbenennen
 */

require_once __DIR__ . '/config/database.php';

// Sicherheitscheck
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['confirm'])) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Auto-Delivery System Installation</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
                padding: 40px;
                max-width: 600px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #1a1a1a;
                margin-bottom: 20px;
            }
            .feature {
                padding: 16px;
                background: #f5f7fa;
                border-radius: 8px;
                margin-bottom: 12px;
            }
            .feature-title {
                font-weight: 700;
                margin-bottom: 4px;
            }
            .feature-desc {
                font-size: 14px;
                color: #6b7280;
            }
            .btn {
                display: block;
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                text-decoration: none;
                text-align: center;
                margin-top: 24px;
            }
            .btn:hover {
                transform: scale(1.02);
            }
            .warning {
                background: #fef3c7;
                border: 2px solid #f59e0b;
                padding: 16px;
                border-radius: 8px;
                margin-top: 24px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎁 Auto-Delivery System Installation</h1>
            <p style="color: #6b7280; margin-bottom: 24px;">
                Installiert das vollständige automatische Belohnungsauslieferungssystem.
            </p>
            
            <div class="feature">
                <div class="feature-title">✨ Automatische Belohnungsprüfung</div>
                <div class="feature-desc">Prüft bei jeder Conversion automatisch ob Belohnungen erreicht wurden</div>
            </div>
            
            <div class="feature">
                <div class="feature-title">📧 Email-Benachrichtigungen</div>
                <div class="feature-desc">Sendet automatische Emails mit Download-Links, Codes und Anweisungen</div>
            </div>
            
            <div class="feature">
                <div class="feature-title">📊 Auslieferungs-Tracking</div>
                <div class="feature-desc">Verfolgt alle ausgelieferten Belohnungen mit vollständigen Details</div>
            </div>
            
            <div class="feature">
                <div class="feature-title">🎯 Lead-Dashboard Integration</div>
                <div class="feature-desc">Zeigt Leads ihre erhaltenen Belohnungen mit allen Zugriffsdaten</div>
            </div>
            
            <div class="feature">
                <div class="feature-title">🔧 Admin-Dashboard</div>
                <div class="feature-desc">Übersicht aller Auslieferungen mit Filter- und Suchfunktionen</div>
            </div>
            
            <a href="?confirm=1" class="btn">
                🚀 Jetzt installieren
            </a>
            
            <div class="warning">
                <strong>⚠️ Wichtig:</strong> Diese Datei sollte nach der Installation gelöscht oder umbenannt werden.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Installation durchführen
$pdo = getDBConnection();
$errors = [];
$success = [];

try {
    // 1. Tabelle erstellen
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `reward_deliveries` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `lead_id` int(11) NOT NULL,
          `reward_id` int(11) NOT NULL,
          `user_id` int(11) NOT NULL COMMENT 'Customer/Freebie-Ersteller ID',
          `reward_type` varchar(50) DEFAULT NULL COMMENT 'download, code, link, custom',
          `reward_title` varchar(255) NOT NULL,
          `reward_value` text DEFAULT NULL COMMENT 'Wert/Beschreibung der Belohnung',
          `delivery_url` text DEFAULT NULL COMMENT 'Download-Link oder Zugangs-URL',
          `access_code` varchar(255) DEFAULT NULL COMMENT 'Zugriffscode falls benötigt',
          `delivery_instructions` text DEFAULT NULL COMMENT 'Einlöse-Anweisungen für Lead',
          `delivered_at` datetime NOT NULL,
          `delivery_status` enum('delivered','claimed','expired') DEFAULT 'delivered',
          `email_sent` tinyint(1) DEFAULT 0 COMMENT 'Email-Benachrichtigung gesendet',
          `email_sent_at` datetime DEFAULT NULL,
          `claimed_at` datetime DEFAULT NULL COMMENT 'Wann Lead Belohnung abgeholt hat',
          `notes` text DEFAULT NULL COMMENT 'Admin-Notizen',
          PRIMARY KEY (`id`),
          KEY `lead_id` (`lead_id`),
          KEY `reward_id` (`reward_id`),
          KEY `user_id` (`user_id`),
          KEY `delivered_at` (`delivered_at`),
          UNIQUE KEY `unique_delivery` (`lead_id`, `reward_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $success[] = "✅ Tabelle 'reward_deliveries' erstellt";
    
    // 2. Indizes erstellen
    try {
        $pdo->exec("CREATE INDEX idx_reward_deliveries_lead_status ON reward_deliveries(lead_id, delivery_status)");
        $success[] = "✅ Index 'idx_reward_deliveries_lead_status' erstellt";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_reward_deliveries_user_delivered ON reward_deliveries(user_id, delivered_at)");
        $success[] = "✅ Index 'idx_reward_deliveries_user_delivered' erstellt";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
    // 3. Spalten in reward_definitions prüfen/hinzufügen
    $columns_to_add = [
        'auto_deliver' => "ALTER TABLE reward_definitions ADD COLUMN auto_deliver TINYINT(1) DEFAULT 1 COMMENT 'Automatische Auslieferung aktiviert'",
        'delivery_url' => "ALTER TABLE reward_definitions ADD COLUMN delivery_url TEXT NULL COMMENT 'Download-Link oder Zugangs-URL'",
        'access_code' => "ALTER TABLE reward_definitions ADD COLUMN access_code VARCHAR(255) NULL COMMENT 'Zugriffscode'",
        'delivery_instructions' => "ALTER TABLE reward_definitions ADD COLUMN delivery_instructions TEXT NULL COMMENT 'Einlöse-Anweisungen'"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            $success[] = "✅ Spalte '$column' zu 'reward_definitions' hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                $errors[] = "⚠️ Fehler bei Spalte '$column': " . $e->getMessage();
            }
        }
    }
    
    // 4. Test-Daten prüfen
    $stmt = $pdo->query("SELECT COUNT(*) FROM reward_definitions");
    $reward_count = $stmt->fetchColumn();
    $success[] = "ℹ️ Gefunden: $reward_count Belohnungsdefinitionen";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM lead_users");
    $lead_count = $stmt->fetchColumn();
    $success[] = "ℹ️ Gefunden: $lead_count Leads";
    
} catch (Exception $e) {
    $errors[] = "❌ Kritischer Fehler: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Abgeschlossen</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
            padding: 40px;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a1a;
            margin-bottom: 20px;
        }
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .success {
            background: #d1fae5;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
        }
        .next-steps {
            background: #e0e7ff;
            padding: 20px;
            border-radius: 8px;
            margin-top: 24px;
        }
        .next-steps h3 {
            margin-bottom: 12px;
            color: #4f46e5;
        }
        .next-steps ol {
            margin-left: 20px;
        }
        .next-steps li {
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            margin-right: 12px;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo empty($errors) ? '✅ Installation erfolgreich!' : '⚠️ Installation mit Warnungen'; ?></h1>
        
        <?php foreach ($success as $msg): ?>
            <div class="message success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $msg): ?>
            <div class="message error"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        
        <div class="next-steps">
            <h3>📋 Nächste Schritte:</h3>
            <ol>
                <li><strong>Diese Datei löschen</strong> - Aus Sicherheitsgründen sollte install_auto_delivery.php gelöscht werden</li>
                <li><strong>Admin-Dashboard öffnen</strong> - Gehe zu <code>/admin/reward_deliveries.php</code> für die Übersicht</li>
                <li><strong>Belohnungen konfigurieren</strong> - Stelle sicher dass deine reward_definitions die neuen Felder haben:
                    <ul>
                        <li><code>delivery_url</code> - Download-Link oder Zugangs-URL</li>
                        <li><code>access_code</code> - Zugriffscode falls benötigt</li>
                        <li><code>delivery_instructions</code> - Einlöse-Anweisungen</li>
                    </ul>
                </li>
                <li><strong>Webhook einrichten</strong> (optional) - Verbinde deinen Conversion-Webhook mit:
                    <code>/webhook/referral_conversion.php</code>
                </li>
                <li><strong>Lead-Dashboard testen</strong> - Öffne <code>/lead_dashboard.php</code> als Lead um die Belohnungsanzeige zu sehen</li>
            </ol>
        </div>
        
        <div style="margin-top: 32px;">
            <a href="/admin/reward_deliveries.php" class="btn">📊 Zum Admin-Dashboard</a>
            <a href="/admin/dashboard.php" class="btn">🏠 Zur Hauptseite</a>
        </div>
        
        <div style="margin-top: 32px; padding: 16px; background: #fef3c7; border-radius: 8px;">
            <strong>🔧 API-Endpoints:</strong>
            <ul style="margin-top: 8px; line-height: 1.8;">
                <li><code>POST /api/reward_delivery.php</code> - Manuelle Prüfung/Auslieferung</li>
                <li><code>POST /webhook/referral_conversion.php</code> - Conversion-Webhook</li>
            </ul>
        </div>
    </div>
</body>
</html>
