-- Setup für Rechtstexte-Tabelle (Legal Texts)
-- Diese Tabelle speichert Impressum und Datenschutzerklärung für jeden Kunden

-- Tabelle erstellen (falls nicht vorhanden)
CREATE TABLE IF NOT EXISTS `legal_texts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_id` INT(11) NOT NULL,
    `impressum` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `datenschutz` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_customer` (`customer_id`),
    KEY `idx_customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Füge customer_id Feld zu freebies Tabelle hinzu, falls nicht vorhanden
-- (Dies ermöglicht direkte Zuordnung von Freebies zu Kunden)
ALTER TABLE `freebies` 
ADD COLUMN IF NOT EXISTS `customer_id` INT(11) DEFAULT NULL AFTER `id`,
ADD KEY IF NOT EXISTS `idx_customer_id` (`customer_id`);

-- Hinweis: Die customer_id in freebies kann über customer_freebies ermittelt werden,
-- falls das Feld nicht existiert. Beide Ansätze werden unterstützt.

-- Erfolgsbestätigung
SELECT 'Legal Texts Tabelle erfolgreich eingerichtet!' as Status;