-- Migration: AV-Vertrags-Zustimmungen Tracking
-- Erstellt eine Tabelle zur DSGVO-konformen Nachweispflicht
-- Datum: 2025-01-20

CREATE TABLE IF NOT EXISTS `av_contract_acceptances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `accepted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) NOT NULL COMMENT 'IPv4 oder IPv6 Adresse',
  `user_agent` text NOT NULL COMMENT 'Browser und Betriebssystem Info',
  `av_contract_version` varchar(20) DEFAULT '1.0' COMMENT 'Version des AV-Vertrags',
  `acceptance_type` enum('registration','update','renewal') NOT NULL DEFAULT 'registration',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_accepted_at` (`accepted_at`),
  KEY `idx_acceptance_type` (`acceptance_type`),
  CONSTRAINT `fk_av_acceptance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='DSGVO-konforme Speicherung von AV-Vertrags-Zustimmungen';

-- Index für schnelle Abfragen nach User
CREATE INDEX idx_user_acceptance ON av_contract_acceptances(user_id, accepted_at DESC);
