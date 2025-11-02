-- ===================================================
-- CUSTOMER CHECKLIST PROGRESS
-- Speichert den individuellen Fortschritt jedes Kunden
-- ===================================================

CREATE TABLE IF NOT EXISTS `customer_checklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `task_id` VARCHAR(50) NOT NULL,
  `completed` TINYINT(1) DEFAULT 0,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_task` (`user_id`, `task_id`),
  INDEX `idx_user_id` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Tasks für neue Benutzer
-- Diese können per PHP dynamisch eingefügt werden
