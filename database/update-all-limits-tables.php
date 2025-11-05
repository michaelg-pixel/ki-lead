<?php
/**
 * KOMPLETTES Update: Beide Limits-Tabellen aktualisieren
 * Fügt alle fehlenden Spalten für Source-Tracking hinzu
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Limits-Tabellen - Komplettes Update</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            h2 { color: #764ba2; margin-top: 30px; margin-bottom: 15px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .error { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
            table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            th { background: #f3f4f6; padding: 10px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600; }
            td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
            .new-column { background: #d1fae5; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 Limits-Tabellen - Komplettes Update</h1>
            <p>Aktualisiert beide Tabellen für vollständiges Source-Tracking.</p>";
    
    $totalUpdates = 0;
    $errors = [];
    
    // ==========================================
    // TABELLE 1: customer_freebie_limits
    // ==========================================
    
    echo "<h2>📊 Tabelle 1: customer_freebie_limits</h2>";
    
    $table1Exists = $pdo->query("SHOW TABLES LIKE 'customer_freebie_limits'")->rowCount() > 0;
    
    if (!$table1Exists) {
        echo "<div class='warning'>⚠️ Tabelle <code>customer_freebie_limits</code> existiert nicht. Wird erstellt...</div>";
        
        try {
            $pdo->exec("
                CREATE TABLE customer_freebie_limits (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    freebie_limit INT NOT NULL DEFAULT 0 COMMENT 'Anzahl erlaubter Freebies',
                    product_id VARCHAR(100) NULL DEFAULT NULL COMMENT 'Digistore24 Produkt-ID',
                    product_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'Name des Produkts/Tarifs',
                    source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' COMMENT 'Quelle der Limits',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_customer (customer_id),
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_customer_id (customer_id),
                    INDEX idx_source (source)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Freebie-Limits pro Kunde mit Source-Tracking'
            ");
            
            echo "<div class='success'>✅ Tabelle <code>customer_freebie_limits</code> wurde erstellt!</div>";
            $totalUpdates++;
        } catch (Exception $e) {
            echo "<div class='error'>❌ Fehler beim Erstellen: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors[] = "customer_freebie_limits: " . $e->getMessage();
        }
    } else {
        echo "<div class='info'>✅ Tabelle gefunden. Prüfe Spalten...</div>";
        
        $columns1 = $pdo->query("SHOW COLUMNS FROM customer_freebie_limits")->fetchAll(PDO::FETCH_COLUMN);
        
        // product_id
        if (!in_array('product_id', $columns1)) {
            try {
                $pdo->exec("ALTER TABLE customer_freebie_limits ADD COLUMN product_id VARCHAR(100) NULL DEFAULT NULL COMMENT 'Digistore24 Produkt-ID' AFTER freebie_limit");
                echo "<div class='success'>✅ Spalte <code>product_id</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei product_id: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>product_id</code> existiert bereits.</div>";
        }
        
        // product_name
        if (!in_array('product_name', $columns1)) {
            try {
                $pdo->exec("ALTER TABLE customer_freebie_limits ADD COLUMN product_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'Name des Produkts/Tarifs' AFTER product_id");
                echo "<div class='success'>✅ Spalte <code>product_name</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei product_name: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>product_name</code> existiert bereits.</div>";
        }
        
        // source
        if (!in_array('source', $columns1)) {
            try {
                $pdo->exec("ALTER TABLE customer_freebie_limits ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' COMMENT 'Quelle der Limits' AFTER product_name");
                echo "<div class='success'>✅ Spalte <code>source</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei source: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>source</code> existiert bereits.</div>";
        }
    }
    
    // ==========================================
    // TABELLE 2: customer_referral_slots
    // ==========================================
    
    echo "<h2>🚀 Tabelle 2: customer_referral_slots</h2>";
    
    $table2Exists = $pdo->query("SHOW TABLES LIKE 'customer_referral_slots'")->rowCount() > 0;
    
    if (!$table2Exists) {
        echo "<div class='warning'>⚠️ Tabelle <code>customer_referral_slots</code> existiert nicht. Wird erstellt...</div>";
        
        try {
            $pdo->exec("
                CREATE TABLE customer_referral_slots (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    total_slots INT NOT NULL DEFAULT 1 COMMENT 'Gesamtanzahl verfügbarer Slots',
                    used_slots INT NOT NULL DEFAULT 0 COMMENT 'Anzahl bereits genutzter Slots',
                    product_id VARCHAR(100) NULL DEFAULT NULL COMMENT 'Digistore24 Produkt-ID',
                    product_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'Name des Produkts/Tarifs',
                    source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' COMMENT 'Quelle der Limits',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_customer (customer_id),
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_customer_id (customer_id),
                    INDEX idx_source (source)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Empfehlungsprogramm-Slots pro Kunde mit Source-Tracking'
            ");
            
            echo "<div class='success'>✅ Tabelle <code>customer_referral_slots</code> wurde erstellt!</div>";
            $totalUpdates++;
        } catch (Exception $e) {
            echo "<div class='error'>❌ Fehler beim Erstellen: " . htmlspecialchars($e->getMessage()) . "</div>";
            $errors[] = "customer_referral_slots: " . $e->getMessage();
        }
    } else {
        echo "<div class='info'>✅ Tabelle gefunden. Prüfe Spalten...</div>";
        
        $columns2 = $pdo->query("SHOW COLUMNS FROM customer_referral_slots")->fetchAll(PDO::FETCH_COLUMN);
        
        // product_id
        if (!in_array('product_id', $columns2)) {
            try {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN product_id VARCHAR(100) NULL DEFAULT NULL COMMENT 'Digistore24 Produkt-ID' AFTER used_slots");
                echo "<div class='success'>✅ Spalte <code>product_id</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei product_id: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>product_id</code> existiert bereits.</div>";
        }
        
        // product_name
        if (!in_array('product_name', $columns2)) {
            try {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN product_name VARCHAR(255) NULL DEFAULT NULL COMMENT 'Name des Produkts/Tarifs' AFTER product_id");
                echo "<div class='success'>✅ Spalte <code>product_name</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei product_name: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>product_name</code> existiert bereits.</div>";
        }
        
        // source
        if (!in_array('source', $columns2)) {
            try {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' COMMENT 'Quelle der Limits' AFTER product_name");
                echo "<div class='success'>✅ Spalte <code>source</code> hinzugefügt!</div>";
                $totalUpdates++;
            } catch (Exception $e) {
                echo "<div class='error'>❌ Fehler bei source: " . htmlspecialchars($e->getMessage()) . "</div>";
                $errors[] = $e->getMessage();
            }
        } else {
            echo "<div class='info'>ℹ️ Spalte <code>source</code> existiert bereits.</div>";
        }
    }
    
    // ==========================================
    // ZUSAMMENFASSUNG
    // ==========================================
    
    echo "<h2>📋 Zusammenfassung</h2>";
    
    if (count($errors) > 0) {
        echo "<div class='error'>
            <strong>⚠️ Es gab " . count($errors) . " Fehler:</strong>
            <ul style='margin: 10px 0;'>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>
        </div>";
    }
    
    if ($totalUpdates > 0) {
        echo "<div class='success'>
            <strong>✅ Update erfolgreich!</strong>
            <p style='margin: 10px 0 0 0;'>
                $totalUpdates Änderung(en) wurden durchgeführt. Die Limits-Verwaltung sollte jetzt funktionieren!
            </p>
        </div>";
    } else {
        echo "<div class='info'>
            <strong>ℹ️ Keine Updates erforderlich</strong>
            <p style='margin: 10px 0 0 0;'>
                Alle erforderlichen Spalten sind bereits vorhanden.
            </p>
        </div>";
    }
    
    echo "
    <div style='text-align: center; margin-top: 30px;'>
        <a href='/admin/dashboard.php?page=users' class='btn'>→ Zur Kundenverwaltung</a>
        <a href='/admin/dashboard.php?page=digistore' class='btn'>→ Zu Digistore24</a>
    </div>
    
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Kritischer Fehler</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Trace:</strong></p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 11px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>
    </div>
    </body>
    </html>";
}
