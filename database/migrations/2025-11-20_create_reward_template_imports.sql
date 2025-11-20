-- Migration: reward_template_imports Tabelle erstellen
-- Datum: 2025-11-20
-- Zweck: Tracking von importierten Vendor-Belohnungen

CREATE TABLE IF NOT EXISTS reward_template_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL COMMENT 'ID aus vendor_reward_templates',
    customer_id INT NOT NULL COMMENT 'User der importiert hat',
    reward_definition_id INT NULL COMMENT 'Erstellte reward_definition',
    import_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    import_source VARCHAR(50) DEFAULT 'marketplace' COMMENT 'Quelle des Imports',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_template (template_id),
    INDEX idx_customer (customer_id),
    INDEX idx_reward_def (reward_definition_id),
    UNIQUE KEY unique_import (template_id, customer_id) COMMENT 'Ein Template kann nur einmal pro User importiert werden'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Tracking von importierten Vendor-Belohnungen';
