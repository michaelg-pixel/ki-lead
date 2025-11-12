<?php
/**
 * Einfache Webhook-System Installation
 * Direkter Browser-Zugriff - Keine externe API benötigt
 */

// Config laden
require_once __DIR__ . '/../config/database.php';

$results = [];
$hasErrors = false;

// Nur bei POST ausführen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'install') {
    
    try {
        $pdo = getDBConnection();
        
        // SQL-Statements als Array
        $statements = [
            'webhook_configurations' => "
                CREATE TABLE IF NOT EXISTS `webhook_configurations` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL COMMENT 'Interner Name für den Webhook',
                  `description` TEXT DEFAULT NULL COMMENT 'Beschreibung des Webhooks',
                  `is_active` TINYINT(1) DEFAULT 1 COMMENT 'Webhook aktiv?',
                  `own_freebies_limit` INT(11) DEFAULT 0 COMMENT 'Eigene Freebies die Kunden erstellen können',
                  `ready_freebies_count` INT(11) DEFAULT 0 COMMENT 'Fertige Template-Freebies',
                  `referral_slots` INT(11) DEFAULT 0 COMMENT 'Empfehlungsprogramm-Slots',
                  `is_upsell` TINYINT(1) DEFAULT 0 COMMENT 'Ist dies ein Upsell?',
                  `upsell_behavior` ENUM('add', 'upgrade', 'replace') DEFAULT 'add',
                  `created_by` INT(11) DEFAULT NULL,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'webhook_product_ids' => "
                CREATE TABLE IF NOT EXISTS `webhook_product_ids` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `webhook_id` INT(11) NOT NULL,
                  `product_id` VARCHAR(100) NOT NULL COMMENT 'Digistore24 Produkt-ID',
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_webhook_product` (`webhook_id`, `product_id`),
                  KEY `idx_product_id` (`product_id`),
                  KEY `idx_webhook_product_lookup` (`product_id`, `webhook_id`),
                  CONSTRAINT `fk_webhook_products` 
                    FOREIGN KEY (`webhook_id`) 
                    REFERENCES `webhook_configurations` (`id`) 
                    ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'webhook_course_access' => "
                CREATE TABLE IF NOT EXISTS `webhook_course_access` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `webhook_id` INT(11) NOT NULL,
                  `course_id` INT(11) NOT NULL,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_webhook_course` (`webhook_id`, `course_id`),
                  KEY `idx_course_id` (`course_id`),
                  CONSTRAINT `fk_webhook_courses` 
                    FOREIGN KEY (`webhook_id`) 
                    REFERENCES `webhook_configurations` (`id`) 
                    ON DELETE CASCADE,
                  CONSTRAINT `fk_webhook_courses_course` 
                    FOREIGN KEY (`course_id`) 
                    REFERENCES `courses` (`id`) 
                    ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'webhook_ready_freebies' => "
                CREATE TABLE IF NOT EXISTS `webhook_ready_freebies` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `webhook_id` INT(11) NOT NULL,
                  `freebie_template_id` INT(11) NOT NULL COMMENT 'ID des Template-Freebies',
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_webhook_freebie` (`webhook_id`, `freebie_template_id`),
                  CONSTRAINT `fk_webhook_freebies` 
                    FOREIGN KEY (`webhook_id`) 
                    REFERENCES `webhook_configurations` (`id`) 
                    ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'webhook_activity_log' => "
                CREATE TABLE IF NOT EXISTS `webhook_activity_log` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `webhook_id` INT(11) DEFAULT NULL,
                  `product_id` VARCHAR(100) DEFAULT NULL,
                  `customer_email` VARCHAR(255) DEFAULT NULL,
                  `customer_id` INT(11) DEFAULT NULL,
                  `event_type` VARCHAR(50) DEFAULT NULL COMMENT 'purchase, upsell, refund, etc.',
                  `resources_granted` TEXT DEFAULT NULL COMMENT 'JSON mit gewährten Ressourcen',
                  `is_upsell` TINYINT(1) DEFAULT 0,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_webhook_id` (`webhook_id`),
                  KEY `idx_customer_email` (`customer_email`),
                  KEY `idx_created_at` (`created_at`),
                  CONSTRAINT `fk_webhook_activity` 
                    FOREIGN KEY (`webhook_id`) 
                    REFERENCES `webhook_configurations` (`id`) 
                    ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        // Statements ausführen
        foreach ($statements as $table => $sql) {
            try {
                $pdo->exec($sql);
                $results[] = [
                    'table' => $table,
                    'success' => true,
                    'message' => 'Erfolgreich erstellt'
                ];
            } catch (PDOException $e) {
                $results[] = [
                    'table' => $table,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
                $hasErrors = true;
            }
        }
        
    } catch (Exception $e) {
        $results[] = [
            'table' => 'SYSTEM',
            'success' => false,
            'message' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()
        ];
        $hasErrors = true;
    }
}

// Status prüfen
$status = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check') {
    try {
        $pdo = getDBConnection();
        $tables = ['webhook_configurations', 'webhook_product_ids', 'webhook_course_access', 'webhook_ready_freebies', 'webhook_activity_log'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->fetch() !== false;
            $status[] = [
                'table' => $table,
                'exists' => $exists
            ];
        }
    } catch (Exception $e) {
        $status[] = [
            'table' => 'ERROR',
            'exists' => false,
            'message' => $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook-System Installation</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .subtitle {
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 12px;
            font-size: 18px;
        }
        
        .info-box ul {
            color: #1e40af;
            padding-left: 20px;
            line-height: 1.8;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .results {
            margin-top: 30px;
        }
        
        .result-item {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .result-item.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .result-item.error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .result-item .icon {
            font-size: 24px;
        }
        
        .result-item .content {
            flex: 1;
        }
        
        .result-item .table-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .result-item .message {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .success-message {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .success-message h3 {
            color: #065f46;
            margin-bottom: 10px;
        }
        
        .success-message p {
            color: #047857;
        }
        
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .error-message h3 {
            color: #991b1b;
            margin-bottom: 10px;
        }
        
        .error-message p {
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Webhook-System Installation</h1>
        <p class="subtitle">Einfache Installation - Direkt aus dem Browser</p>
        
        <?php if (empty($results) && empty($status)): ?>
            <div class="info-box">
                <h3>📋 Was wird installiert:</h3>
                <ul>
                    <li><strong>webhook_configurations</strong> - Haupttabelle für Webhooks</li>
                    <li><strong>webhook_product_ids</strong> - Mehrere Produkt-IDs pro Webhook</li>
                    <li><strong>webhook_course_access</strong> - Flexible Kurszuweisungen</li>
                    <li><strong>webhook_ready_freebies</strong> - Spezifische Freebie-Templates</li>
                    <li><strong>webhook_activity_log</strong> - Aktivitäts-Tracking</li>
                </ul>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="install">
                <button type="submit" class="btn">✨ Jetzt installieren</button>
            </form>
            
            <form method="POST">
                <input type="hidden" name="action" value="check">
                <button type="submit" class="btn btn-secondary">🔍 Status prüfen</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($results)): ?>
            <div class="results">
                <?php foreach ($results as $result): ?>
                    <div class="result-item <?php echo $result['success'] ? 'success' : 'error'; ?>">
                        <span class="icon"><?php echo $result['success'] ? '✅' : '❌'; ?></span>
                        <div class="content">
                            <div class="table-name"><?php echo htmlspecialchars($result['table']); ?></div>
                            <div class="message"><?php echo htmlspecialchars($result['message']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!$hasErrors): ?>
                <div class="success-message">
                    <h3>✅ Installation erfolgreich!</h3>
                    <p>Das Webhook-System wurde erfolgreich installiert. Du kannst jetzt neue Webhooks im Admin-Bereich erstellen.</p>
                </div>
                <a href="/admin/dashboard.php?page=webhooks" class="btn" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">
                    🚀 Zur Webhook-Verwaltung
                </a>
            <?php else: ?>
                <div class="error-message">
                    <h3>⚠️ Es gab Fehler bei der Installation</h3>
                    <p>Bitte prüfe die Fehlermeldungen oben und versuche es erneut.</p>
                </div>
                <a href="?" class="btn btn-secondary" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">
                    🔄 Erneut versuchen
                </a>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!empty($status)): ?>
            <div class="results">
                <h3 style="margin-bottom: 16px; color: #1f2937;">📊 Tabellen-Status:</h3>
                <?php foreach ($status as $item): ?>
                    <div class="result-item <?php echo $item['exists'] ? 'success' : 'error'; ?>">
                        <span class="icon"><?php echo $item['exists'] ? '✅' : '❌'; ?></span>
                        <div class="content">
                            <div class="table-name"><?php echo htmlspecialchars($item['table']); ?></div>
                            <div class="message">
                                <?php 
                                if (isset($item['message'])) {
                                    echo htmlspecialchars($item['message']);
                                } else {
                                    echo $item['exists'] ? 'Existiert' : 'Fehlt';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="?" class="btn" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">
                ← Zurück zur Installation
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
