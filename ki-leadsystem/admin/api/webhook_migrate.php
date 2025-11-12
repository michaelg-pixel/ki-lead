<?php
/**
 * Webhook Migration API
 * WICHTIG: Nach erfolgreicher Migration diese Datei löschen!
 * 
 * SICHERHEITSHINWEIS:
 * Diese Datei führt Datenbank-Änderungen durch und sollte NUR einmalig
 * zur Migration verwendet und danach SOFORT gelöscht werden!
 */

// KRITISCH: Migrations-Script deaktivieren nach 24 Stunden
$scriptCreated = filemtime(__FILE__);
$hoursSinceCreation = (time() - $scriptCreated) / 3600;
if ($hoursSinceCreation > 24) {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Migrations-Script ist abgelaufen. Bitte kontaktiere den Support.'
    ]));
}

// Admin-Session prüfen (optional aber empfohlen)
session_start();
$requireAdminLogin = false; // Auf true setzen für höhere Sicherheit

if ($requireAdminLogin) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(401);
        die(json_encode([
            'success' => false,
            'error' => 'Nicht autorisiert. Bitte im Admin-Bereich einloggen.'
        ]));
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Datenbank-Verbindung
require_once __DIR__ . '/../../config/database.php';

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Nur POST erlaubt']);
    exit;
}

// JSON-Input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'create_tables':
            createTables($conn);
            break;
            
        case 'migrate_webhooks':
            migrateWebhooks($conn);
            break;
            
        case 'validate':
            validateMigration($conn);
            break;
            
        default:
            throw new Exception('Ungültige Aktion');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function createTables($conn) {
    // Prüfen ob Tabellen bereits existieren
    $check = $conn->query("SHOW TABLES LIKE 'webhooks'");
    if ($check->num_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Tabellen existieren bereits'
        ]);
        return;
    }
    
    // Transaktion starten
    $conn->begin_transaction();
    
    try {
        // Webhooks Tabelle
        $sql1 = "CREATE TABLE IF NOT EXISTS webhooks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            webhook_token VARCHAR(64) UNIQUE NOT NULL,
            digistore_product_ids TEXT COMMENT 'JSON Array mit Produkt-IDs',
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_token (webhook_token),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($sql1)) {
            throw new Exception("Fehler bei webhooks Tabelle: " . $conn->error);
        }
        
        // Webhook Resources Tabelle
        $sql2 = "CREATE TABLE IF NOT EXISTS webhook_resources (
            id INT PRIMARY KEY AUTO_INCREMENT,
            webhook_id INT NOT NULL,
            resource_type ENUM('freebie', 'own_freebie', 'video_course', 'referral_slot') NOT NULL,
            resource_id INT DEFAULT NULL COMMENT 'ID des Freebies/Kurses (NULL bei referral_slot)',
            quantity INT DEFAULT 1 COMMENT 'Anzahl (z.B. bei referral_slots)',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE,
            INDEX idx_webhook (webhook_id),
            INDEX idx_type (resource_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($sql2)) {
            throw new Exception("Fehler bei webhook_resources Tabelle: " . $conn->error);
        }
        
        // Webhook Logs Tabelle
        $sql3 = "CREATE TABLE IF NOT EXISTS webhook_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id INT DEFAULT NULL,
            webhook_id INT DEFAULT NULL,
            product_id VARCHAR(100) DEFAULT NULL,
            transaction_id VARCHAR(255) DEFAULT NULL,
            webhook_data TEXT COMMENT 'JSON mit kompletten Webhook-Daten',
            status ENUM('success', 'error') DEFAULT 'success',
            error_message TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_customer (customer_id),
            INDEX idx_webhook (webhook_id),
            INDEX idx_transaction (transaction_id),
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($sql3)) {
            throw new Exception("Fehler bei webhook_logs Tabelle: " . $conn->error);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tabellen erfolgreich erstellt'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function migrateWebhooks($conn) {
    // Prüfen ob alte webhook_config Tabelle existiert
    $check = $conn->query("SHOW TABLES LIKE 'webhook_config'");
    if ($check->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'migrated' => 0,
            'message' => 'Keine alte Konfiguration gefunden'
        ]);
        return;
    }
    
    $conn->begin_transaction();
    
    try {
        // Alte Konfiguration laden
        $stmt = $conn->prepare("SELECT * FROM webhook_config LIMIT 1");
        $stmt->execute();
        $config = $stmt->get_result()->fetch_assoc();
        
        if (!$config) {
            echo json_encode([
                'success' => true,
                'migrated' => 0,
                'message' => 'Keine Konfiguration zum Migrieren'
            ]);
            return;
        }
        
        // Standard-Webhook erstellen
        $webhookName = "Legacy Webhook (migriert)";
        $webhookToken = bin2hex(random_bytes(32));
        
        // Digistore IDs sammeln (falls in config vorhanden)
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
        
        if (!$stmt->execute()) {
            throw new Exception("Fehler beim Erstellen des Webhooks: " . $stmt->error);
        }
        
        $webhookId = $conn->insert_id;
        $resourcesCreated = 0;
        
        // Freebies migrieren
        if (!empty($config['freebie_ids'])) {
            $freebieIds = json_decode($config['freebie_ids'], true) ?: [];
            foreach ($freebieIds as $freebieId) {
                $stmt = $conn->prepare(
                    "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id) 
                     VALUES (?, 'freebie', ?)"
                );
                $stmt->bind_param("ii", $webhookId, $freebieId);
                $stmt->execute();
                $resourcesCreated++;
            }
        }
        
        // Eigene Freebies migrieren
        if (!empty($config['own_freebie_ids'])) {
            $ownFreebieIds = json_decode($config['own_freebie_ids'], true) ?: [];
            foreach ($ownFreebieIds as $ownFreebieId) {
                $stmt = $conn->prepare(
                    "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id) 
                     VALUES (?, 'own_freebie', ?)"
                );
                $stmt->bind_param("ii", $webhookId, $ownFreebieId);
                $stmt->execute();
                $resourcesCreated++;
            }
        }
        
        // Empfehlungs-Slots migrieren
        if (!empty($config['referral_slots'])) {
            $referralSlots = (int)$config['referral_slots'];
            $stmt = $conn->prepare(
                "INSERT INTO webhook_resources (webhook_id, resource_type, resource_id, quantity) 
                 VALUES (?, 'referral_slot', NULL, ?)"
            );
            $stmt->bind_param("ii", $webhookId, $referralSlots);
            $stmt->execute();
            $resourcesCreated++;
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'migrated' => 1,
            'resources' => $resourcesCreated,
            'webhook_id' => $webhookId,
            'webhook_token' => $webhookToken,
            'message' => 'Migration erfolgreich'
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function validateMigration($conn) {
    // Webhooks zählen
    $webhooksResult = $conn->query("SELECT COUNT(*) as count FROM webhooks");
    $webhooksCount = $webhooksResult->fetch_assoc()['count'];
    
    // Resources zählen
    $resourcesResult = $conn->query("SELECT COUNT(*) as count FROM webhook_resources");
    $resourcesCount = $resourcesResult->fetch_assoc()['count'];
    
    // Datenintegrität prüfen
    $integrityCheck = $conn->query(
        "SELECT w.id, w.name, COUNT(wr.id) as resource_count 
         FROM webhooks w 
         LEFT JOIN webhook_resources wr ON w.id = wr.webhook_id 
         GROUP BY w.id"
    );
    
    $webhooks = [];
    while ($row = $integrityCheck->fetch_assoc()) {
        $webhooks[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'webhooks' => $webhooksCount,
        'resources' => $resourcesCount,
        'details' => $webhooks,
        'message' => 'Validierung erfolgreich'
    ]);
}

function getDBConnection() {
    $host = 'localhost';
    $dbname = 'u424939085_ki_leadsystem';
    $username = 'u424939085_leadgen';
    $password = 'Werni2020$';
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Datenbankverbindung fehlgeschlagen: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    return $conn;
}
