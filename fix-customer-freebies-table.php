<?php
/**
 * Fix: Erstelle fehlende customer_freebies Tabelle
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-customer-freebies-table.php
 */

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
            max-width: 800px;
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
            align-items: center;
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
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
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
    
    // Prüfe ob Tabelle existiert
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
    } else {
        echo '<div class="status info">';
        echo '📝 Erstelle customer_freebies Tabelle...';
        echo '</div>';
        
        $pdo->exec("
            CREATE TABLE customer_freebies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
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
                freebie_type ENUM('template', 'custom') DEFAULT 'template',
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
        ");
        
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebies erfolgreich erstellt';
        echo '</div>';
    }
    
    // Prüfe customer_freebie_limits
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebie_limits'");
    if ($stmt->rowCount() === 0) {
        echo '<div class="status info">';
        echo '📝 Erstelle customer_freebie_limits Tabelle...';
        echo '</div>';
        
        $pdo->exec("
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
        ");
        
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebie_limits erfolgreich erstellt';
        echo '</div>';
    }
    
    // Prüfe product_freebie_config
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_freebie_config'");
    if ($stmt->rowCount() === 0) {
        echo '<div class="status info">';
        echo '📝 Erstelle product_freebie_config Tabelle...';
        echo '</div>';
        
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
    }
    
    echo '<div class="status success" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
    echo '<span style="font-size: 24px;">🎉</span>';
    echo '<div>';
    echo '<strong style="color: #065f46; font-size: 18px;">Tabellen erfolgreich erstellt!</strong><br>';
    echo 'Alle benötigten Tabellen sind jetzt vorhanden.';
    echo '</div>';
    echo '</div>';
    
    // Zeige Tabellenstruktur
    echo '<div class="status info">';
    echo '<span style="font-size: 24px;">📊</span>';
    echo '<div>';
    echo '<strong>Tabellen-Übersicht:</strong>';
    
    $tables = ['customer_freebies', 'customer_freebie_limits', 'product_freebie_config'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0 ? '✅' : '❌';
        echo "<br>$exists $table";
    }
    echo '</div>';
    echo '</div>';
    
    echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div>';
    echo '<strong>Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
    
    if (isset($e)) {
        echo '<pre>Stack Trace:' . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
}

echo '</div></body></html>';
?>
