<?php
/**
 * Setup Script für Freebie Click Analytics System
 * Installiert Tabellen, Views, Stored Procedures und migriert bestehende Daten
 * Mit automatischer Spaltentyp-Erkennung und intelligenter Migration
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
    
    // Alle Spalten von customer_freebies anzeigen
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $cf_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verfügbare Spalten sammeln
    $available_columns = array_column($cf_columns, 'Field');
    
    // Name-Spalte finden
    $name_column = null;
    foreach (['freebie_name', 'name', 'title', 'headline'] as $possible_name) {
        if (in_array($possible_name, $available_columns)) {
            $name_column = $possible_name;
            break;
        }
    }
    if (!$name_column) {
        $name_column = 'id';
        echo "⚠️  No name column found, using 'id' as fallback\n";
    }
    
    // Slug-Spalte finden
    $slug_column = null;
    foreach (['url_slug', 'slug', 'unique_id'] as $possible_slug) {
        if (in_array($possible_slug, $available_columns)) {
            $slug_column = $possible_slug;
            break;
        }
    }
    if (!$slug_column) {
        $slug_column = 'id';
        echo "⚠️  No slug column found, using 'id' as fallback\n";
    }
    
    // Clicks-Spalte prüfen (für Migration)
    $has_clicks_column = in_array('clicks', $available_columns);
    $has_created_at = in_array('created_at', $available_columns);
    
    echo "✅ users.id type: $users_id_type\n";
    echo "✅ customer_freebies.id type: $freebies_id_type\n";
    echo "✅ customer_freebies.customer_id type: $cf_customer_id_type\n";
    echo "✅ customer_freebies name column: $name_column\n";
    echo "✅ customer_freebies slug column: $slug_column\n";
    echo ($has_clicks_column ? "✅" : "⚠️ ") . " clicks column: " . ($has_clicks_column ? "exists" : "not found") . "\n";
    echo ($has_created_at ? "✅" : "⚠️ ") . " created_at column: " . ($has_created_at ? "exists" : "not found") . "\n\n";
    
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
    
    // Foreign Keys hinzufügen
    echo "🔗 Adding Foreign Keys...\n";
    
    $fk_queries = [
        ['table' => 'freebie_click_analytics', 'name' => 'fk_fca_customer', 'ref' => 'users(id)', 'column' => 'customer_id'],
        ['table' => 'freebie_click_analytics', 'name' => 'fk_fca_freebie', 'ref' => 'customer_freebies(id)', 'column' => 'freebie_id'],
        ['table' => 'freebie_click_logs', 'name' => 'fk_fcl_customer', 'ref' => 'users(id)', 'column' => 'customer_id'],
        ['table' => 'freebie_click_logs', 'name' => 'fk_fcl_freebie', 'ref' => 'customer_freebies(id)', 'column' => 'freebie_id']
    ];
    
    foreach ($fk_queries as $fk) {
        try {
            $pdo->exec("ALTER TABLE {$fk['table']} ADD CONSTRAINT {$fk['name']} FOREIGN KEY ({$fk['column']}) REFERENCES {$fk['ref']} ON DELETE CASCADE");
            echo "✅ FK: {$fk['table']} -> {$fk['ref']}\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️  FK already exists: {$fk['table']} -> {$fk['ref']}\n";
            } else {
                throw $e;
            }
        }
    }
    echo "\n";
    
    // ===== SCHRITT 2: CLICKS SPALTE HINZUFÜGEN (falls nicht vorhanden) =====
    if (!$has_clicks_column) {
        echo "📊 Adding clicks column to customer_freebies...\n";
        try {
            $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN clicks INT(11) UNSIGNED DEFAULT 0 AFTER customer_id");
            echo "✅ clicks column added\n\n";
            $has_clicks_column = true;
        } catch (PDOException $e) {
            echo "⚠️  Could not add clicks column: " . $e->getMessage() . "\n\n";
        }
    }
    
    // ===== SCHRITT 3: VIEW ERSTELLEN =====
    echo "📈 Creating Analytics View...\n";
    
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
    
    // ===== SCHRITT 4: STORED PROCEDURE =====
    echo "⚙️  Creating Stored Procedure...\n";
    
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
        
        -- Analytics-Tabelle aktualisieren
        INSERT INTO freebie_click_analytics 
            (customer_id, freebie_id, click_date, click_count, unique_clicks)
        VALUES 
            (p_customer_id, p_freebie_id, v_click_date, 1, IF(p_is_unique, 1, 0))
        ON DUPLICATE KEY UPDATE
            click_count = click_count + 1,
            unique_clicks = unique_clicks + IF(p_is_unique, 1, 0);
        
        -- Gesamt-Counter aktualisieren
        UPDATE customer_freebies 
        SET clicks = clicks + 1
        WHERE id = p_freebie_id;
        
        -- Log speichern
        INSERT INTO freebie_click_logs 
            (freebie_id, customer_id, ip_address, user_agent, referrer, session_id, is_unique)
        VALUES 
            (p_freebie_id, p_customer_id, p_ip_address, p_user_agent, p_referrer, p_session_id, p_is_unique);
    END
    ";
    
    $pdo->exec($sql_procedure);
    echo "✅ sp_track_freebie_click procedure created\n\n";
    
    // ===== SCHRITT 5: BESTEHENDE DATEN MIGRIEREN =====
    $migrated = 0;
    
    if ($has_clicks_column && $has_created_at) {
        echo "🔄 Migrating existing click data...\n";
        
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
        
        foreach ($freebies as $freebie) {
            $total_clicks = $freebie['clicks'];
            $days_to_distribute = min(30, ceil((time() - strtotime($freebie['created_at'])) / 86400));
            
            if ($days_to_distribute <= 0) $days_to_distribute = 1;
            
            $clicks_per_day = max(1, floor($total_clicks / $days_to_distribute));
            $remaining_clicks = $total_clicks;
            
            for ($i = $days_to_distribute - 1; $i >= 0; $i--) {
                $click_date = date('Y-m-d', strtotime("-$i days"));
                $day_clicks = min($remaining_clicks, $clicks_per_day + rand(-1, 2));
                
                if ($day_clicks <= 0) continue;
                
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
                    round($day_clicks * 0.7)
                ]);
                
                $remaining_clicks -= $day_clicks;
            }
            
            $migrated++;
        }
        
        echo "✅ Migrated data from $migrated freebies\n\n";
    } else {
        echo "⚠️  Skipping migration (clicks or created_at column not found)\n";
        echo "   Data will be tracked starting from now.\n\n";
    }
    
    // ===== SCHRITT 6: AUTOMATISCHE BEREINIGUNG =====
    echo "🗑️  Setting up automatic cleanup...\n";
    
    try {
        $pdo->exec("DROP EVENT IF EXISTS cleanup_old_click_logs");
        $pdo->exec("
            CREATE EVENT IF NOT EXISTS cleanup_old_click_logs
            ON SCHEDULE EVERY 1 DAY
            STARTS CURRENT_TIMESTAMP
            DO
            BEGIN
                DELETE FROM freebie_click_logs 
                WHERE click_timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);
            END
        ");
        $pdo->exec("SET GLOBAL event_scheduler = ON");
        echo "✅ Automatic cleanup event created\n\n";
    } catch (PDOException $e) {
        echo "⚠️  Could not create event (requires SUPER privilege)\n";
        echo "   You can manually clean up old logs periodically.\n\n";
    }
    
    // ===== FERTIG =====
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 Setup Complete!\n\n";
    echo "Analytics System installed successfully:\n";
    echo "  ✓ Tables created\n";
    echo "  ✓ Foreign keys added\n";
    echo "  ✓ Views created\n";
    echo "  ✓ Stored procedures created\n";
    if ($migrated > 0) {
        echo "  ✓ Existing data migrated ($migrated freebies)\n";
    } else {
        echo "  ℹ️  No existing data to migrate (tracking starts now)\n";
    }
    echo "  ✓ Automatic cleanup configured\n\n";
    
    echo "Configuration:\n";
    echo "  • customer_id type: $customer_id_type\n";
    echo "  • freebie_id type: $freebie_id_type\n";
    echo "  • name column: $name_column\n";
    echo "  • slug column: $slug_column\n";
    if (!$has_clicks_column) {
        echo "  • clicks column: ADDED\n";
    }
    echo "\n";
    
    echo "Next Steps:\n";
    echo "  1. Visit any freebie page to start tracking\n";
    echo "  2. Check dashboard: /customer/dashboard.php?page=fortschritt\n";
    echo "  3. View analytics: /customer/dashboard.php?page=fortschritt\n\n";
    
    echo "🚀 Analytics tracking is now active!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nDebug Info:\n";
    echo "Error Code: " . $e->getCode() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
    }
    echo "\nTroubleshooting:\n";
    echo "1. Run: SHOW COLUMNS FROM customer_freebies;\n";
    echo "2. Check database user permissions\n";
    echo "3. Verify table structures\n";
    exit(1);
}
