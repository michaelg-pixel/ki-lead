-- Flexible Multi-Webhook System
-- Erlaubt unbegrenzt viele Webhooks mit mehreren Produkt-IDs und flexiblen Ressourcen

-- Haupttabelle für Webhook-Konfigurationen
CREATE TABLE IF NOT EXISTS `webhook_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Interner Name für den Webhook',
  `description` text DEFAULT NULL COMMENT 'Beschreibung des Webhooks',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Webhook aktiv?',
  
  -- Ressourcen-Limits
  `own_freebies_limit` int(11) DEFAULT 0 COMMENT 'Eigene Freebies die Kunden erstellen können',
  `ready_freebies_count` int(11) DEFAULT 0 COMMENT 'Fertige Template-Freebies',
  `referral_slots` int(11) DEFAULT 0 COMMENT 'Empfehlungsprogramm-Slots',
  
  -- Upsell-Unterstützung
  `is_upsell` tinyint(1) DEFAULT 0 COMMENT 'Ist dies ein Upsell (addiert zu bestehenden Ressourcen)?',
  `upsell_behavior` enum('add', 'upgrade', 'replace') DEFAULT 'add' COMMENT 'add=addieren, upgrade=nur höhere Werte, replace=ersetzen',
  
  -- Tracking
  `created_by` int(11) DEFAULT NULL COMMENT 'Admin User ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook -> Produkt-IDs (M:N)
-- Ein Webhook kann mehrere Digistore24 Produkt-IDs haben
CREATE TABLE IF NOT EXISTS `webhook_product_ids` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `product_id` varchar(100) NOT NULL COMMENT 'Digistore24 Produkt-ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_webhook_product` (`webhook_id`, `product_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_webhook_products` 
    FOREIGN KEY (`webhook_id`) 
    REFERENCES `webhook_configurations` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook -> Videokurse (M:N)
-- Ein Webhook kann Zugang zu mehreren Videokursen gewähren
CREATE TABLE IF NOT EXISTS `webhook_course_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_webhook_course` (`webhook_id`, `course_id`),
  KEY `idx_course_id` (`course_id`),
  CONSTRAINT `fk_webhook_courses` 
    FOREIGN KEY (`webhook_id`) 
    REFERENCES `webhook_configurations` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_webhook_courses_course` 
    FOREIGN KEY (`course_id`) 
    REFERENCES `courses` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook -> Fertige Freebies (M:N)
-- Ein Webhook kann spezifische fertige Freebies zuweisen
CREATE TABLE IF NOT EXISTS `webhook_ready_freebies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) NOT NULL,
  `freebie_template_id` int(11) NOT NULL COMMENT 'ID des Template-Freebies',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_webhook_freebie` (`webhook_id`, `freebie_template_id`),
  CONSTRAINT `fk_webhook_freebies` 
    FOREIGN KEY (`webhook_id`) 
    REFERENCES `webhook_configurations` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook-Aktivitätslog für Tracking
CREATE TABLE IF NOT EXISTS `webhook_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webhook_id` int(11) DEFAULT NULL,
  `product_id` varchar(100) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL COMMENT 'purchase, upsell, refund, etc.',
  `resources_granted` text DEFAULT NULL COMMENT 'JSON mit gewährten Ressourcen',
  `is_upsell` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_webhook_id` (`webhook_id`),
  KEY `idx_customer_email` (`customer_email`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_webhook_activity` 
    FOREIGN KEY (`webhook_id`) 
    REFERENCES `webhook_configurations` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index für schnelle Produkt-ID-Suche
CREATE INDEX idx_webhook_product_lookup ON webhook_product_ids(product_id, webhook_id);
