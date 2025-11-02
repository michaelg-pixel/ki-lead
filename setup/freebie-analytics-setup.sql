-- ==========================================
-- FREEBIE CLICK ANALYTICS SYSTEM
-- Historisches Tracking für echte Analytics
-- ==========================================

-- Tabelle für tägliche Click-Analytics
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
  
  -- Indizes für Performance
  INDEX `idx_customer_date` (`customer_id`, `click_date`),
  INDEX `idx_freebie_date` (`freebie_id`, `click_date`),
  INDEX `idx_click_date` (`click_date`),
  
  -- Unique Constraint: Nur ein Eintrag pro Freebie pro Tag
  UNIQUE KEY `unique_freebie_date` (`freebie_id`, `click_date`),
  
  -- Foreign Keys
  FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`freebie_id`) REFERENCES `customer_freebies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für detaillierte Click-Logs (optional, für erweiterte Analytics)
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

-- View für einfache Abfragen
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

-- Stored Procedure zum Inkrementieren der Klicks
DELIMITER $$

CREATE PROCEDURE `sp_track_freebie_click`(
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
    
    -- Optional: Detailliertes Log speichern
    INSERT INTO freebie_click_logs 
        (freebie_id, customer_id, ip_address, user_agent, referrer, session_id, is_unique)
    VALUES 
        (p_freebie_id, p_customer_id, p_ip_address, p_user_agent, p_referrer, p_session_id, p_is_unique);
    
END$$

DELIMITER ;

-- Trigger für automatische Bereinigung alter Logs (älter als 90 Tage)
DELIMITER $$

CREATE EVENT IF NOT EXISTS `cleanup_old_click_logs`
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO
BEGIN
    DELETE FROM freebie_click_logs 
    WHERE click_timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);
END$$

DELIMITER ;

-- Aktiviere Events
SET GLOBAL event_scheduler = ON;

-- ==========================================
-- INFO & USAGE
-- ==========================================
-- 
-- Verwendung:
-- CALL sp_track_freebie_click(freebie_id, customer_id, is_unique, ip, user_agent, referrer, session_id);
--
-- Beispiel:
-- CALL sp_track_freebie_click(1, 2, 1, '192.168.1.1', 'Mozilla/5.0...', 'https://google.com', 'sess_abc123');
--
-- ==========================================
