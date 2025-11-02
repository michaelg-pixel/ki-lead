<?php
/**
 * Setup Script für Freebie Click Analytics System
 * Installiert Tabellen, Views, Stored Procedures und migriert bestehende Daten
 */

require_once __DIR__ . '/../config/database.php';

echo "🚀 Starting Freebie Analytics Setup...\n\n";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ===== SCHRITT 1: TABELLEN ERSTELLEN =====
    echo "📊 Creating Analytics Tables...\n";
    
    // Main Analytics Table
    $sql_analytics_table = "
    CREATE TABLE IF NOT EXISTS `freebie_click_analytics` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `customer_id` INT(11) UNSIGNED NOT NULL,
      `freebie_id` INT(11) UNSIGNED NOT NULL,
      `click_date` DATE NOT NULL,
      `click_count` INT(11) UNSIGNED DEFAULT 0,
      `unique_clicks` INT(11) UNSIGNED DEFAULT 0,
      `conversion_count` INT(11) UNSIGNED DEFAULT 0,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      
      INDEX `idx_customer_date` (`customer_id`, `click_date`),
      INDEX `idx_freebie_date` (`freebie_id`, `click_date`),
      INDEX `idx_click_date` (`click_date`),
      
      UNIQUE KEY `unique_freebie_date` (`freebie_id`, `click_date`),
      
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`freebie_id`) REFERENCES `customer_freebies`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_analytics_table);
    echo "✅ freebie_click_analytics table created\n";
    
    // Detailed Logs Table
    $sql_logs_table = "
    CREATE TABLE IF NOT EXISTS `freebie_click_logs` (
      `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      `freebie_id` INT(11) UNSIGNED NOT NULL,
      `customer_id` INT(11) UNSIGNED NOT NULL,
      `ip_address` VARCHAR(45),
      `user_agent` VARCHAR(255),
      `referrer` VARCHAR(500),
      `click_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `session_id` VARCHAR(100),
      `is_unique` TINYINT(1) DEFAULT 1,
      `converted` TINYINT(1) DEFAULT 0,
      
      INDEX `idx_freebie_timestamp` (`freebie_id`, `click_timestamp`),
      INDEX `idx_customer_timestamp` (`customer_id`, `click_timestamp`),
      INDEX `idx_session` (`session_id`),
      
      FOREIGN KEY (`freebie_id`) REFERENCES `customer_freebies`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql_logs_table);
    echo "✅ freebie_click_logs table created\n\n";
    
    // ===== SCHRITT 2: VIEW ERSTELLEN =====
    echo "📈 Creating Analytics View...\n";
    
    $sql_view = "
    CREATE OR REPLACE VIEW `v_freebie_analytics_summary` AS
    SELECT 
        ca.customer_id,
        ca.freebie_id,
        cf.freebie_name,
        cf.url_slug,
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
    echo "⚙️ Creating Stored Procedure...\n";
    
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
        SET clicks = clicks + 1,
            updated_at = NOW()
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
    echo "🗑️ Setting up automatic cleanup...\n";
    
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
    
    // ===== FERTIG =====
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 Setup Complete!\n\n";
    echo "Analytics System installed successfully:\n";
    echo "  ✓ Tables created\n";
    echo "  ✓ Views created\n";
    echo "  ✓ Stored procedures created\n";
    echo "  ✓ Existing data migrated\n";
    echo "  ✓ Automatic cleanup configured\n\n";
    echo "You can now track real-time analytics!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Please check your database connection and permissions.\n";
    exit(1);
}
