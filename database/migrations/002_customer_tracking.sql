-- ===================================================
-- CUSTOMER TRACKING SYSTEM
-- Tabelle für echtes Benutzer-Tracking
-- KORRIGIERT: Verwendet user_id statt customer_id
-- ===================================================

CREATE TABLE IF NOT EXISTS `customer_tracking` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type` ENUM('page_view', 'click', 'event', 'time_spent') NOT NULL,
  `page` VARCHAR(255) DEFAULT NULL,
  `element` VARCHAR(255) DEFAULT NULL,
  `target` VARCHAR(500) DEFAULT NULL,
  `event_name` VARCHAR(100) DEFAULT NULL,
  `event_data` TEXT DEFAULT NULL,
  `duration` INT(11) DEFAULT NULL COMMENT 'Zeit in Sekunden',
  `referrer` VARCHAR(500) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_user_type` (`user_id`, `type`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_date` (`user_id`, `created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- BEISPIEL-ABFRAGEN FÜR REPORTING
-- ===================================================

-- Gesamte Seitenaufrufe pro User (Letzte 30 Tage)
-- SELECT user_id, COUNT(*) as page_views
-- FROM customer_tracking
-- WHERE type = 'page_view' 
-- AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
-- GROUP BY user_id;

-- Top 10 meistbesuchte Seiten
-- SELECT page, COUNT(*) as visits
-- FROM customer_tracking
-- WHERE type = 'page_view'
-- GROUP BY page
-- ORDER BY visits DESC
-- LIMIT 10;

-- Durchschnittliche Verweildauer pro Seite
-- SELECT page, AVG(duration) as avg_duration
-- FROM customer_tracking
-- WHERE type = 'time_spent'
-- GROUP BY page;

-- Tägliche Aktivität eines Kunden
-- SELECT DATE(created_at) as date, COUNT(*) as activities
-- FROM customer_tracking
-- WHERE user_id = ?
-- AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
-- GROUP BY DATE(created_at)
-- ORDER BY date ASC;

-- Meistgeklickte Elemente
-- SELECT element, COUNT(*) as clicks
-- FROM customer_tracking
-- WHERE type = 'click'
-- GROUP BY element
-- ORDER BY clicks DESC
-- LIMIT 20;
