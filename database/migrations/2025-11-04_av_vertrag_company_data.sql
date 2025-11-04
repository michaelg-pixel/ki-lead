-- Migration: AV-Vertrag Firmendaten
-- Erstellt: 2025-11-04
-- Zweck: Speichert Firmendaten für personalisierte AV-Verträge

CREATE TABLE IF NOT EXISTS user_company_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_name VARCHAR(255) NOT NULL,
    company_address VARCHAR(255) NOT NULL,
    company_zip VARCHAR(10) NOT NULL,
    company_city VARCHAR(100) NOT NULL,
    company_country VARCHAR(100) DEFAULT 'Deutschland',
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index für schnelle Abfragen
CREATE INDEX idx_user_company_data_user_id ON user_company_data(user_id);
