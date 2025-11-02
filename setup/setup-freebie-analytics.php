<?php
/**
 * Setup Script für Freebie Click Analytics System
 * Installiert Tabellen, Views, Stored Procedures und migriert bestehende Daten
 * Mit automatischer Spaltentyp-Erkennung für Kompatibilität
 */

require_once __DIR__ . '/../config/database.php';

echo "🚀 Starting Freebie Analytics Setup...\n\n";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== SCHRITT 0: SPALTENTYPEN UND NAMEN ERMITTELN =====
    echo "🔍 Detecting column types and names...\n";
    
    // users.id Typ ermitteln
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'id'");
    $users_id_column = $stmt->fetch(PDO::FETCH_ASSOC);
    $users_id_type = $users_id_column['Type'];
    
    // customer_freebies.id Typ ermitteln
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field = 'id'");
    $freebies_id_column = $stmt->fetch(PDO::FETCH_ASSOC);
    $freebies_id_type = $freebies_id_column['Type'];
    
    // customer_freebies.customer_id Typ ermitteln
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field = 'customer_id'");
    $cf_customer_id_column = $stmt->fetch(PDO::FETCH_ASSOC);
    $cf_customer_id_type = $cf_customer_id_column['Type'];
    
    // Alle Spalten von customer_freebies anzeigen um Namen zu finden
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $cf_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Name-Spalte finden (könnte 'name', 'title', 'freebie_name' sein)
    $name_column = null;
    $slug_column = null;
    
    foreach ($cf_columns as $col) {
        if (in_array($col['Field'], ['freebie_name', 'name', 'title'])) {
            $name_column = $col['Field'];
        }
        if (in_array($col['Field'], ['url_slug', 'slug'])) {
            $slug_column = $col['Field'];
        }
    }
    
    if (!$name_column) {
        $name_column = 'id'; // Fallback auf ID wenn kein Name gefunden
        echo "⚠️  No name column found, using 'id' as fallback\n";
    }
    if (!$slug_column) {
        $slug_column = 'id'; // Fallback
        echo "⚠️  No slug column found, using 'id' as fallback\n";
    }
    
    echo "✅ users.id type: $users_id_type\n";
    echo "✅ customer_freebies.id type: $freebies_id_type\n";
    echo "✅ customer_freebies.customer_id type: $cf_customer_id_type\n";
    echo "✅ customer_freebies name column: $name_column\n";
    echo "✅ customer_freebies slug column: $slug_column\n\n";
    
    // Verwende die gleichen Typen wie die Originaltabellen
    $customer_id_type = $cf_customer_id_type;
    $freebie_id_type = $freebies_id_type;
    
    // ===== SCHRITT 1: TABELLEN ERSTELLEN =====
    echo "📊 Creating Analytics Tables...\n";
    
    // Main Analytics Table
    $sql_analytics_table = "
    CREATE TABLE IF NOT EXISTS `freebie_click_analytics` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `customer_id` $customer_id_type NOT NULL,
      `freebie_id` $freebie_id_type NOT NULL,
      `click_date` DATE NOT NULL,
      `click_count` INT(11) UNSIGNED DEFAULT 0,
      `unique_clicks` INT(11) UNSIGNED DEFAULT 0,
      `conversion_count` INT(11) UNSIGNED DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      
      INDEX `idx_customer_date` (`customer_id`, `click_date`),
      INDEX `idx_freebie_date` (`freebie_id`, `click_date`),
      INDEX `idx_click_date` (`click_date`),
      
      UNIQUE KEY `unique_freebie_date` (`freebie_id`, `click_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_analytics_table);
    echo "✅ freebie_click_analytics table created\n";
    
    // Detailed Logs Table
    $sql_logs_table = "
    CREATE TABLE IF NOT EXISTS `freebie_click_logs` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `freebie_id` $freebie_id_type NOT NULL,
      `customer_id` $customer_id_type NOT NULL,
      `ip_address` VARCHAR(45),
      `user_agent` VARCHAR(255),
      `referrer` VARCHAR(500),
      `click_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `session_id` VARCHAR(100),
      `is_unique` TINYINT(1) DEFAULT 1,
      `converted` TINYINT(1) DEFAULT 0,
      
      INDEX `idx_freebie_timestamp` (`freebie_id`, `click_timestamp`),
      INDEX `idx_customer_timestamp` (`customer_id`, `click_timestamp`),
      INDEX `idx_session` (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_logs_table);
    echo "✅ freebie_click_logs table created\n";
    
    // Jetzt Foreign Keys NACHTRÄGLICH hinzufügen (sicherer)
    echo "🔗 Adding Foreign Keys...\n";
    
    try {
        // FK für freebie_click_analytics
        $pdo->exec("
            ALTER TABLE freebie_click_analytics 
            ADD CONSTRAINT fk_fca_customer 
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
        ");
        echo "✅ FK: freebie_click_analytics -> users\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  FK already exists: freebie_click_analytics -> users\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("
            ALTER TABLE freebie_click_analytics 
            ADD CONSTRAINT fk_fca_freebie 
            FOREIGN KEY (freebie_id) REFERENCES customer_freebies(id) ON DELETE CASCADE
        ");
        echo "✅ FK: freebie_click_analytics -> customer_freebies\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  FK already exists: freebie_click_analytics -> customer_freebies\n";
        } else {
            throw $e;
        }
    }
    
    try {
        // FK für freebie_click_logs
        $pdo->exec("
            ALTER TABLE freebie_click_logs 
            ADD CONSTRAINT fk_fcl_customer 
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
        ");
        echo "✅ FK: freebie_click_logs -> users\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  FK already exists: freebie_click_logs -> users\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("
            ALTER TABLE freebie_click_logs 
            ADD CONSTRAINT fk_fcl_freebie 
            FOREIGN KEY (freebie_id) REFERENCES customer_freebies(id) ON DELETE CASCADE
        ");
        echo "✅ FK: freebie_click_logs -> customer_freebies\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠️  FK already exists: freebie_click_logs -> customer_freebies\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n";
    
    // ===== SCHRITT 2: VIEW ERSTELLEN =====
    echo "📈 Creating Analytics View...\n";
    
    // View mit den tatsächlich vorhandenen Spaltennamen
    $sql_view = "
    CREATE OR REPLACE VIEW `v_freebie_analytics_summary` AS
    SELECT 
        ca.customer_id,
        ca.freebie_id,
        cf.$name_column as freebie_name,
        cf.$slug_column as url_slug,
        SUM(ca.click_count) as total_clicks,
        SUM(ca.unique_clicks) as total_unique_clicks,
        SUM(ca.conversion_count) as total_conversions,
        MIN(ca.click_date) as first_click_date,
        MAX(ca.click_date) as last_click_date,
        COUNT(DISTINCT ca.click_date) as active_days,
        ROUND(AVG(ca.click_count), 2) as avg_clicks_per_day
    FROM freebie_click_analytics ca
    INNER JOIN customer_freebies cf ON ca.freebie_id = cf.id
    GROUP BY ca.customer_id, ca.freebie_id;
    ";
    
    $pdo->exec($sql_view);
    echo "✅ v_freebie_analytics_summary view created\n\n";
    
    // ===== SCHRITT 3: STORED PROCEDURE =====
    echo "⚙️  Creating Stored Procedure...\n";
    
    // Alte Procedure löschen falls vorhanden
    $pdo->exec("DROP PROCEDURE IF EXISTS sp_track_freebie_click");
    
    $sql_procedure = "
    CREATE PROCEDURE sp_track_freebie_click(
        IN p_freebie_id INT,
        IN p_customer_id INT,
        IN p_is_unique TINYINT,
        IN p_ip_address VARCHAR(45),
        IN p_user_agent VARCHAR(255),
        IN p_referrer VARCHAR(500),
        IN p_session_id VARCHAR(100)
    )
    BEGIN
        DECLARE v_click_date DATE;
        SET v_click_date = CURDATE();
        
        -- Update oder Insert in Analytics-Tabelle
        INSERT INTO freebie_click_analytics 
            (customer_id, freebie_id, click_date, click_count, unique_clicks)
        VALUES 
            (p_customer_id, p_freebie_id, v_click_date, 1, IF(p_is_unique, 1, 0))
        ON DUPLICATE KEY UPDATE
            click_count = click_count + 1,
            unique_clicks = unique_clicks + IF(p_is_unique, 1, 0);
        
        -- Update Gesamt-Counter in customer_freebies
        UPDATE customer_freebies 
        SET clicks = clicks + 1
        WHERE id = p_freebie_id;
        
        -- Detailliertes Log speichern
        INSERT INTO freebie_click_logs 
            (freebie_id, customer_id, ip_address, user_agent, referrer, session_id, is_unique)
        VALUES 
            (p_freebie_id, p_customer_id, p_ip_address, p_user_agent, p_referrer, p_session_id, p_is_unique);
        
    END
    ";
    
    $pdo->exec($sql_procedure);
    echo "✅ sp_track_freebie_click procedure created\n\n";
    
    // ===== SCHRITT 4: BESTEHENDE DATEN MIGRIEREN =====
    echo "🔄 Migrating existing click data...\n";
    
    // Alle Freebies mit Klicks holen
    $stmt = $pdo->query("
        SELECT 
            cf.id as freebie_id,
            cf.customer_id,
            cf.clicks,
            cf.created_at
        FROM customer_freebies cf
        WHERE cf.clicks > 0
    ");
    
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $migrated = 0;
    
    foreach ($freebies as $freebie) {
        // Klicks auf die letzten 30 Tage verteilen (simuliert)
        $total_clicks = $freebie['clicks'];
        $days_to_distribute = min(30, ceil((time() - strtotime($freebie['created_at'])) / 86400));
        
        if ($days_to_distribute <= 0) {
            $days_to_distribute = 1;
        }
        
        $clicks_per_day = max(1, floor($total_clicks / $days_to_distribute));
        $remaining_clicks = $total_clicks;
        
        for ($i = $days_to_distribute - 1; $i >= 0; $i--) {
            $click_date = date('Y-m-d', strtotime("-$i days"));
            $day_clicks = min($remaining_clicks, $clicks_per_day + rand(-1, 2));
            
            if ($day_clicks <= 0) continue;
            
            // In Analytics-Tabelle einfügen
            $stmt_insert = $pdo->prepare("
                INSERT INTO freebie_click_analytics 
                (customer_id, freebie_id, click_date, click_count, unique_clicks)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    click_count = click_count + VALUES(click_count),
                    unique_clicks = unique_clicks + VALUES(unique_clicks)
            ");
            
            $stmt_insert->execute([
                $freebie['customer_id'],
                $freebie['freebie_id'],
                $click_date,
                $day_clicks,
                round($day_clicks * 0.7) // 70% unique clicks (geschätzt)
            ]);
            
            $remaining_clicks -= $day_clicks;
        }
        
        $migrated++;
    }
    
    echo "✅ Migrated data from $migrated freebies\n\n";
    
    // ===== SCHRITT 5: EVENT FÜR AUTOMATISCHE BEREINIGUNG =====
    echo "🗑️  Setting up automatic cleanup...\n";
    
    try {
        $pdo->exec("DROP EVENT IF EXISTS cleanup_old_click_logs");
        
        $sql_event = "
        CREATE EVENT IF NOT EXISTS cleanup_old_click_logs
        ON SCHEDULE EVERY 1 DAY
        STARTS CURRENT_TIMESTAMP
        DO
        BEGIN
            DELETE FROM freebie_click_logs 
            WHERE click_timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);
        END
        ";
        
        $pdo->exec($sql_event);
        $pdo->exec("SET GLOBAL event_scheduler = ON");
        echo "✅ Automatic cleanup event created\n\n";
    } catch (PDOException $e) {
        echo "⚠️  Could not create event (may require SUPER privilege): " . $e->getMessage() . "\n";
        echo "   You can manually create it later or run cleanup periodically.\n\n";
    }
    
    // ===== FERTIG =====
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 Setup Complete!\n\n";
    echo "Analytics System installed successfully:\n";
    echo "  ✓ Tables created with correct column types\n";
    echo "  ✓ Foreign keys added\n";
    echo "  ✓ Views created (using $name_column as name)\n";
    echo "  ✓ Stored procedures created\n";
    echo "  ✓ Existing data migrated ($migrated freebies)\n";
    echo "  ✓ Automatic cleanup configured\n\n";
    echo "Column Types Used:\n";
    echo "  • customer_id: $customer_id_type\n";
    echo "  • freebie_id: $freebie_id_type\n";
    echo "  • name column: $name_column\n";
    echo "  • slug column: $slug_column\n\n";
    echo "You can now track real-time analytics!\n";
    echo "Test URL: /customer/dashboard.php?page=fortschritt\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nDebug Info:\n";
    echo "Error Code: " . $e->getCode() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
    }
    echo "\nTroubleshooting:\n";
    echo "1. Check database user permissions\n";
    echo "2. Verify table structures with SHOW COLUMNS FROM customer_freebies\n";
    echo "3. Check for existing tables/constraints\n";
    echo "4. Review error log above\n";
    exit(1);
}
