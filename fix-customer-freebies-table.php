<?php
/**
 * Fix: Erstelle fehlende customer_freebies Tabelle
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-customer-freebies-table.php
 */

// Error Reporting aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Customer Freebies Tabelle</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            max-width: 900px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
            margin-top: 10px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .step {
            margin: 16px 0;
            padding: 12px;
            background: #f9fafb;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔧 Fix Customer Freebies Tabelle</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Erstellt die fehlende customer_freebies Tabelle</p>';

try {
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung konnte nicht hergestellt werden');
    }
    
    echo '<div class="status success">';
    echo '✅ Datenbankverbindung erfolgreich';
    echo '</div>';
    
    // Schritt 1: Prüfe users Tabelle
    echo '<div class="step">Schritt 1: Prüfe users Tabelle...</div>';
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $usersTableExists = $stmt->rowCount() > 0;
    
    if ($usersTableExists) {
        echo '<div class="status success">✅ users Tabelle existiert</div>';
    } else {
        echo '<div class="status warning">⚠️ users Tabelle nicht gefunden - Foreign Keys werden übersprungen</div>';
    }
    
    // Schritt 2: customer_freebies Tabelle
    echo '<div class="step">Schritt 2: Erstelle customer_freebies Tabelle...</div>';
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo '<div class="status info">';
        echo '<span style="font-size: 24px;">ℹ️</span>';
        echo '<div>';
        echo '<strong>Tabelle existiert bereits</strong><br>';
        echo 'Die Tabelle customer_freebies ist bereits vorhanden.';
        echo '</div>';
        echo '</div>';
        
        // Prüfe Spalten
        echo '<div class="step">Schritt 2a: Prüfe Spalten...</div>';
        $requiredColumns = ['freebie_type', 'thank_you_message'];
        $missingColumns = [];
        
        foreach ($requiredColumns as $col) {
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '$col'");
            if ($stmt->rowCount() === 0) {
                $missingColumns[] = $col;
            }
        }
        
        if (!empty($missingColumns)) {
            echo '<div class="status info">📝 Fehlende Spalten werden hinzugefügt...</div>';
            
            if (in_array('freebie_type', $missingColumns)) {
                try {
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN freebie_type ENUM('template', 'custom') DEFAULT 'template' AFTER customer_id");
                    echo '<div class="status success">✅ Spalte freebie_type hinzugefügt</div>';
                } catch (PDOException $e) {
                    echo '<div class="status error">❌ Fehler bei freebie_type: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            
            if (in_array('thank_you_message', $missingColumns)) {
                try {
                    $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN thank_you_message TEXT AFTER freebie_type");
                    echo '<div class="status success">✅ Spalte thank_you_message hinzugefügt</div>';
                } catch (PDOException $e) {
                    echo '<div class="status error">❌ Fehler bei thank_you_message: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        } else {
            echo '<div class="status success">✅ Alle Spalten vorhanden</div>';
        }
        
    } else {
        echo '<div class="status info">📝 Erstelle customer_freebies Tabelle...</div>';
        
        // Erstelle Tabelle OHNE Foreign Key wenn users nicht existiert
        if ($usersTableExists) {
            $sql = "
                CREATE TABLE customer_freebies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    freebie_type ENUM('template', 'custom') DEFAULT 'template',
                    template_id INT DEFAULT NULL,
                    headline VARCHAR(255) NOT NULL,
                    subheadline VARCHAR(500),
                    preheadline VARCHAR(255),
                    bullet_points TEXT,
                    cta_text VARCHAR(255) NOT NULL,
                    layout VARCHAR(50) DEFAULT 'hybrid',
                    background_color VARCHAR(20) DEFAULT '#FFFFFF',
                    primary_color VARCHAR(20) DEFAULT '#8B5CF6',
                    raw_code TEXT,
                    unique_id VARCHAR(100) NOT NULL,
                    url_slug VARCHAR(255),
                    mockup_image_url VARCHAR(500),
                    freebie_clicks INT DEFAULT 0,
                    thank_you_clicks INT DEFAULT 0,
                    thank_you_message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_customer (customer_id),
                    INDEX idx_template (template_id),
                    INDEX idx_unique (unique_id),
                    INDEX idx_freebie_type (freebie_type),
                    INDEX idx_customer_type (customer_id, freebie_type),
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        } else {
            $sql = "
                CREATE TABLE customer_freebies (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    freebie_type ENUM('template', 'custom') DEFAULT 'template',
                    template_id INT DEFAULT NULL,
                    headline VARCHAR(255) NOT NULL,
                    subheadline VARCHAR(500),
                    preheadline VARCHAR(255),
                    bullet_points TEXT,
                    cta_text VARCHAR(255) NOT NULL,
                    layout VARCHAR(50) DEFAULT 'hybrid',
                    background_color VARCHAR(20) DEFAULT '#FFFFFF',
                    primary_color VARCHAR(20) DEFAULT '#8B5CF6',
                    raw_code TEXT,
                    unique_id VARCHAR(100) NOT NULL,
                    url_slug VARCHAR(255),
                    mockup_image_url VARCHAR(500),
                    freebie_clicks INT DEFAULT 0,
                    thank_you_clicks INT DEFAULT 0,
                    thank_you_message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_customer (customer_id),
                    INDEX idx_template (template_id),
                    INDEX idx_unique (unique_id),
                    INDEX idx_freebie_type (freebie_type),
                    INDEX idx_customer_type (customer_id, freebie_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }
        
        $pdo->exec($sql);
        
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebies erfolgreich erstellt';
        echo '</div>';
    }
    
    // Schritt 3: customer_freebie_limits
    echo '<div class="step">Schritt 3: Prüfe customer_freebie_limits Tabelle...</div>';
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebie_limits'");
    if ($stmt->rowCount() === 0) {
        echo '<div class="status info">📝 Erstelle customer_freebie_limits Tabelle...</div>';
        
        if ($usersTableExists) {
            $sql = "
                CREATE TABLE customer_freebie_limits (
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
        } else {
            $sql = "
                CREATE TABLE customer_freebie_limits (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    customer_id INT NOT NULL,
                    freebie_limit INT DEFAULT 0,
                    product_id VARCHAR(100),
                    product_name VARCHAR(255),
                    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_customer (customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
        }
        
        $pdo->exec($sql);
        
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebie_limits erfolgreich erstellt';
        echo '</div>';
    } else {
        echo '<div class="status success">✅ Tabelle customer_freebie_limits existiert bereits</div>';
    }
    
    // Schritt 4: product_freebie_config
    echo '<div class="step">Schritt 4: Prüfe product_freebie_config Tabelle...</div>';
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_freebie_config'");
    if ($stmt->rowCount() === 0) {
        echo '<div class="status info">📝 Erstelle product_freebie_config Tabelle...</div>';
        
        $pdo->exec("
            CREATE TABLE product_freebie_config (
                id INT PRIMARY KEY AUTO_INCREMENT,
                product_id VARCHAR(100) NOT NULL UNIQUE,
                product_name VARCHAR(255),
                freebie_limit INT DEFAULT 5,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Beispiel-Konfigurationen einfügen
        $pdo->exec("
            INSERT INTO product_freebie_config (product_id, product_name, freebie_limit) VALUES
            ('STARTER_001', 'Starter Paket', 5),
            ('PROFESSIONAL_002', 'Professional Paket', 10),
            ('ENTERPRISE_003', 'Enterprise Paket', 25),
            ('UNLIMITED_004', 'Unlimited Paket', 999)
            ON DUPLICATE KEY UPDATE 
                product_name = VALUES(product_name),
                freebie_limit = VALUES(freebie_limit)
        ");
        
        echo '<div class="status success">';
        echo '✅ Tabelle product_freebie_config erfolgreich erstellt';
        echo '</div>';
    } else {
        echo '<div class="status success">✅ Tabelle product_freebie_config existiert bereits</div>';
    }
    
    echo '<div class="status success" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
    echo '<span style="font-size: 24px;">🎉</span>';
    echo '<div>';
    echo '<strong style="color: #065f46; font-size: 18px;">Setup erfolgreich abgeschlossen!</strong><br>';
    echo 'Alle benötigten Tabellen sind jetzt vorhanden.';
    echo '</div>';
    echo '</div>';
    
    // Zeige Tabellenstruktur
    echo '<div class="status info">';
    echo '<span style="font-size: 24px;">📊</span>';
    echo '<div>';
    echo '<strong>Tabellen-Übersicht:</strong><br>';
    
    $tables = ['customer_freebies', 'customer_freebie_limits', 'product_freebie_config'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0 ? '✅' : '❌';
        
        if ($exists === '✅') {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            $count = $result['count'];
            echo "$exists <strong>$table</strong> ($count Einträge)<br>";
        } else {
            echo "$exists <strong>$table</strong><br>";
        }
    }
    echo '</div>';
    echo '</div>';
    
    echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies →</a>';
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div>';
    echo '<strong>Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
    
    echo '<pre>Stack Trace:' . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</div></body></html>';
?>
