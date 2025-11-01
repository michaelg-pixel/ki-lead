-- Tabelle für Freebie-Limits pro Kunde
CREATE TABLE IF NOT EXISTS customer_freebie_limits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    freebie_limit INT DEFAULT 0,
    product_id VARCHAR(100),
    product_name VARCHAR(255),
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Erweitere customer_freebies Tabelle um Typ (template-based oder custom)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS freebie_type ENUM('template', 'custom') DEFAULT 'template' AFTER customer_id;

-- Index für bessere Performance
CREATE INDEX IF NOT EXISTS idx_freebie_type ON customer_freebies(freebie_type);
CREATE INDEX IF NOT EXISTS idx_customer_type ON customer_freebies(customer_id, freebie_type);

-- Produkt-ID zu Freebie-Limit Mapping (Konfiguration)
CREATE TABLE IF NOT EXISTS product_freebie_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id VARCHAR(100) NOT NULL UNIQUE,
    product_name VARCHAR(255),
    freebie_limit INT DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beispiel-Konfigurationen einfügen
INSERT INTO product_freebie_config (product_id, product_name, freebie_limit) VALUES
('STARTER_001', 'Starter Paket', 5),
('PROFESSIONAL_002', 'Professional Paket', 10),
('ENTERPRISE_003', 'Enterprise Paket', 25),
('UNLIMITED_004', 'Unlimited Paket', 999)
ON DUPLICATE KEY UPDATE 
    product_name = VALUES(product_name),
    freebie_limit = VALUES(freebie_limit);
