-- ===================================================
-- CUSTOMER CHECKLIST PROGRESS (NO FOREIGN KEY)
-- Alternative Version ohne Foreign Key Constraint
-- Verwende diese Version wenn die customers-Tabelle nicht existiert
-- ===================================================

CREATE TABLE IF NOT EXISTS `customer_checklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'Referenz zur User-Tabelle (ohne FK)',
  `task_id` VARCHAR(50) NOT NULL COMMENT 'z.B. videos, rechtstexte, freebie, template, lead',
  `completed` TINYINT(1) DEFAULT 0 COMMENT '0 = nicht erledigt, 1 = erledigt',
  `completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Zeitpunkt der Fertigstellung',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_task` (`user_id`, `task_id`) COMMENT 'Ein User kann eine Task nur einmal haben',
  INDEX `idx_user_id` (`user_id`) COMMENT 'Schneller Zugriff auf User-Fortschritte',
  INDEX `idx_task_id` (`task_id`) COMMENT 'Schneller Zugriff auf Task-Statistiken',
  INDEX `idx_completed` (`completed`) COMMENT 'Filter nach erledigten Tasks'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Speichert Checklist-Fortschritt ohne Foreign Key';

-- Beispiel-Abfragen für Testing:
-- 
-- Fortschritt eines Users abrufen:
-- SELECT task_id, completed, completed_at FROM customer_checklist WHERE user_id = 1;
--
-- Fortschritt speichern:
-- INSERT INTO customer_checklist (user_id, task_id, completed, completed_at) 
-- VALUES (1, 'videos', 1, NOW())
-- ON DUPLICATE KEY UPDATE completed = VALUES(completed), completed_at = VALUES(completed_at);
--
-- Alle abgeschlossenen Tasks zählen:
-- SELECT COUNT(*) FROM customer_checklist WHERE user_id = 1 AND completed = 1;
--
-- Fortschritt in Prozent:
-- SELECT 
--   ROUND((SUM(completed) / COUNT(*)) * 100) as progress_percentage
-- FROM customer_checklist 
-- WHERE user_id = 1;
