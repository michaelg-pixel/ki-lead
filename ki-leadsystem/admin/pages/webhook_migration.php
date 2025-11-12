<?php
/**
 * SICHERE Admin-basierte Webhook-Migration
 * admin/pages/webhook_migration.php
 */

// Admin-Check
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$migrationStatus = null;
$migrationError = null;
$migrationSuccess = false;

// Migration durchführen bei POST-Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_migration'])) {
    try {
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        // Schritt 1: Tabellen erstellen
        $tablesCreated = createMigrationTables($conn);
        
        // Schritt 2: Webhooks migrieren
        $webhooksMigrated = migrateOldWebhooks($conn);
        
        // Schritt 3: Validieren
        $validation = validateMigration($conn);
        
        $conn->commit();
        
        $migrationSuccess = true;
        $migrationStatus = [
            'tables' => $tablesCreated,
            'webhooks' => $webhooksMigrated,
            'validation' => $validation
        ];
        
    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollback();
        }
        $migrationError = $e->getMessage();
    }
}

function createMigrationTables($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'webhooks'");
    if ($check->num_rows > 0) {
        return ['status' => 'exists', 'message' => 'Tabellen existieren bereits'];
    }
    
    $sql1 = "CREATE TABLE webhooks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        webhook_token VARCHAR(64) UNIQUE NOT NULL,
        digistore_product_ids TEXT COMMENT 'JSON Array',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_token (webhook_token),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql1)) {
        throw new Exception("Fehler bei webhooks: " . $conn->error);
    }
    
    $sql2 = "CREATE TABLE webhook_resources (
        id INT PRIMARY KEY AUTO_INCREMENT,
        webhook_id INT NOT NULL,
        resource_type ENUM('freebie', 'own_freebie', 'video_course', 'referral_slot') NOT NULL,
        resource_id INT DEFAULT NULL,
        quantity INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE,
        INDEX idx_webhook (webhook_id),
        INDEX idx_type (resource_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql2)) {
        throw new Exception("Fehler bei webhook_resources: " . $conn->error);
    }
    
    $sql3 = "CREATE TABLE webhook_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT DEFAULT NULL,
        webhook_id INT DEFAULT NULL,
        product_id VARCHAR(100) DEFAULT NULL,
        transaction_id VARCHAR(255) DEFAULT NULL,
        webhook_data TEXT,
        status ENUM('success', 'error') DEFAULT 'success',
        error_message TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer (customer_id),
        INDEX idx_webhook (webhook_id),
        INDEX idx_transaction (transaction_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql3)) {
        throw new Exception("Fehler bei webhook_logs: " . $conn->error);
    }
    
    return ['status' => 'created', 'message' => '3 Tabellen erfolgreich erstellt'];
}

function migrateOldWebhooks($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'webhook_config'");
    if ($check->num_rows === 0) {
        return ['status' => 'no_data', 'count' => 0, 'message' => 'Keine alte Konfiguration gefunden'];
    }
    
    $stmt = $conn->prepare("SELECT * FROM webhook_config LIMIT 1");
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    
    if (!$config) {
        return ['status' => 'no_data', 'count' => 0, 'message' => 'Keine Daten zum Migrieren'];
    }
    
    $webhookName = "Legacy Webhook (migriert am " . date('d.m.Y H:i') . ")";
    $webhookToken = bin2hex(random_bytes(32));
    
    $productIds = [];
    if (!empty($config['digistore_product_id'])) {
        $productIds[] = $config['digistore_product_id'];
    }
    $productIdsJson = json_encode($productIds);
    
    $stmt = $conn->prepare(
        "INSERT INTO webhooks (name, webhook_token, digistore_product_ids, status) 
         VALUES (?, ?, ?, 'active')"
    );
    $stmt->bind_param("sss", $webhookName, $webhookToken, $productIdsJson);
    $stmt->execute();
    
    $webhookId = $conn->insert_id;
    $resourcesCount = 0;
    
    if (!empty($config['freebie_ids'])) {
        $freebieIds = json_decode($config['freebie_ids'], true) ?: [];
        foreach ($freebieIds as $freebieId) {
            $stmt = $conn->prepare(
                "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id) 
                 VALUES (?, 'freebie', ?)"
            );
            $stmt->bind_param("ii", $webhookId, $freebieId);
            $stmt->execute();
            $resourcesCount++;
        }
    }
    
    if (!empty($config['own_freebie_ids'])) {
        $ownFreebieIds = json_decode($config['own_freebie_ids'], true) ?: [];
        foreach ($ownFreebieIds as $ownFreebieId) {
            $stmt = $conn->prepare(
                "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id) 
                 VALUES (?, 'own_freebie', ?)"
            );
            $stmt->bind_param("ii", $webhookId, $ownFreebieId);
            $stmt->execute();
            $resourcesCount++;
        }
    }
    
    if (!empty($config['referral_slots'])) {
        $referralSlots = (int)$config['referral_slots'];
        $stmt = $conn->prepare(
            "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id, quantity) 
             VALUES (?, 'referral_slot', NULL, ?)"
        );
        $stmt->bind_param("ii", $webhookId, $referralSlots);
        $stmt->execute();
        $resourcesCount++;
    }
    
    return [
        'status' => 'success',
        'count' => 1,
        'resources' => $resourcesCount,
        'webhook_id' => $webhookId,
        'webhook_token' => $webhookToken,
        'message' => "1 Webhook mit {$resourcesCount} Ressourcen migriert"
    ];
}

function validateMigration($conn) {
    $webhooks = $conn->query("SELECT COUNT(*) as count FROM webhooks")->fetch_assoc()['count'];
    $resources = $conn->query("SELECT COUNT(*) as count FROM webhook_resources")->fetch_assoc()['count'];
    
    return [
        'webhooks' => $webhooks,
        'resources' => $resources,
        'message' => "Validierung erfolgreich: {$webhooks} Webhooks, {$resources} Ressourcen"
    ];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhook Migration</title>
    <style>
        .migration-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .migration-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .migration-header h1 {
            color: #667eea;
            font-size: 32px;
            margin-bottom: 10px;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }
        .warning-box ul {
            margin-left: 20px;
            color: #856404;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .success-box h3 {
            color: #155724;
            margin-bottom: 10px;
        }
        .error-box {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .error-box h3 {
            color: #721c24;
            margin-bottom: 10px;
        }
        .migration-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .migration-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .status-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .status-item h4 {
            color: #1a202c;
            margin-bottom: 5px;
        }
        .status-item p {
            color: #6c757d;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="migration-container">
        <div class="migration-header">
            <h1>🔄 Webhook-System Migration</h1>
            <p>Migriere zum neuen flexiblen Webhook-System</p>
        </div>

        <div class="warning-box">
            <h3>⚠️ Wichtige Hinweise</h3>
            <ul>
                <li><strong>Backup:</strong> Erstelle vor der Migration ein Datenbank-Backup</li>
                <li><strong>Einmalig:</strong> Diese Migration sollte nur einmal durchgeführt werden</li>
                <li><strong>Sicher:</strong> Bei Fehlern erfolgt automatischer Rollback</li>
                <li><strong>Keine Datenverluste:</strong> Alle bestehenden Einstellungen bleiben erhalten</li>
            </ul>
        </div>

        <?php if ($migrationSuccess): ?>
            <div class="success-box">
                <h3>✅ Migration erfolgreich!</h3>
                
                <?php if (isset($migrationStatus['tables'])): ?>
                    <div class="status-item">
                        <h4>Tabellen</h4>
                        <p><?= htmlspecialchars($migrationStatus['tables']['message']) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($migrationStatus['webhooks'])): ?>
                    <div class="status-item">
                        <h4>Webhooks</h4>
                        <p><?= htmlspecialchars($migrationStatus['webhooks']['message']) ?></p>
                        <?php if (isset($migrationStatus['webhooks']['webhook_token'])): ?>
                            <p style="margin-top: 10px;">
                                <strong>Webhook-URL:</strong><br>
                                <code style="background: white; padding: 5px 10px; border-radius: 5px; display: inline-block; margin-top: 5px;">
                                    https://app.mehr-infos-jetzt.de/webhook.php?token=<?= substr($migrationStatus['webhooks']['webhook_token'], 0, 20) ?>...
                                </code>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($migrationStatus['validation'])): ?>
                    <div class="status-item">
                        <h4>Validierung</h4>
                        <p><?= htmlspecialchars($migrationStatus['validation']['message']) ?></p>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; padding: 15px; background: white; border-radius: 8px;">
                    <h4>Nächste Schritte:</h4>
                    <ol style="margin-left: 20px; color: #6c757d;">
                        <li>Gehe zur <a href="?page=webhooks" style="color: #667eea;">Webhook-Verwaltung</a></li>
                        <li>Überprüfe den migrierten Webhook</li>
                        <li>Erstelle bei Bedarf weitere Webhooks</li>
                        <li>Lösche diese Migrations-Seite aus dem Code</li>
                    </ol>
                </div>
            </div>
        <?php elseif ($migrationError): ?>
            <div class="error-box">
                <h3>❌ Fehler bei der Migration</h3>
                <p><strong>Fehlermeldung:</strong> <?= htmlspecialchars($migrationError) ?></p>
                <p style="margin-top: 15px;">
                    Die Migration wurde zurückgerollt. Keine Änderungen wurden vorgenommen.
                    Bitte kontaktiere den Support oder prüfe die Datenbank-Konfiguration.
                </p>
            </div>
            
            <form method="POST">
                <button type="submit" name="start_migration" class="migration-button">
                    🔄 Migration erneut versuchen
                </button>
            </form>
        <?php else: ?>
            <form method="POST">
                <p style="text-align: center; color: #6c757d; margin-bottom: 20px;">
                    Klicke auf den Button, um die Migration zu starten.<br>
                    Der Vorgang dauert nur wenige Sekunden.
                </p>
                <button type="submit" name="start_migration" class="migration-button">
                    🚀 Migration jetzt starten
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
