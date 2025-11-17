-- Reward Deliveries Tabelle
-- Tracking aller ausgelieferten Belohnungen mit vollständigen Details

CREATE TABLE IF NOT EXISTS `reward_deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) NOT NULL,
  `reward_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'Customer/Freebie-Ersteller ID',
  `reward_type` varchar(50) DEFAULT NULL COMMENT 'download, code, link, custom',
  `reward_title` varchar(255) NOT NULL,
  `reward_value` text DEFAULT NULL COMMENT 'Wert/Beschreibung der Belohnung',
  `delivery_url` text DEFAULT NULL COMMENT 'Download-Link oder Zugangs-URL',
  `access_code` varchar(255) DEFAULT NULL COMMENT 'Zugriffscode falls benötigt',
  `delivery_instructions` text DEFAULT NULL COMMENT 'Einlöse-Anweisungen für Lead',
  `delivered_at` datetime NOT NULL,
  `delivery_status` enum('delivered','claimed','expired') DEFAULT 'delivered',
  `email_sent` tinyint(1) DEFAULT 0 COMMENT 'Email-Benachrichtigung gesendet',
  `email_sent_at` datetime DEFAULT NULL,
  `claimed_at` datetime DEFAULT NULL COMMENT 'Wann Lead Belohnung abgeholt hat',
  `notes` text DEFAULT NULL COMMENT 'Admin-Notizen',
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `reward_id` (`reward_id`),
  KEY `user_id` (`user_id`),
  KEY `delivered_at` (`delivered_at`),
  UNIQUE KEY `unique_delivery` (`lead_id`, `reward_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indizes für Performance
CREATE INDEX idx_reward_deliveries_lead_status ON reward_deliveries(lead_id, delivery_status);
CREATE INDEX idx_reward_deliveries_user_delivered ON reward_deliveries(user_id, delivered_at);
