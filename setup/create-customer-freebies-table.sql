-- Erstelle customer_freebies Tabelle
CREATE TABLE IF NOT EXISTS customer_freebies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    template_id INT DEFAULT NULL,
    headline VARCHAR(255) NOT NULL,
    subheadline VARCHAR(500),
    preheadline VARCHAR(255),
    bullet_points TEXT,
    cta_text VARCHAR(255) NOT NULL,
    layout VARCHAR(50) DEFAULT 'hybrid',
    background_color VARCHAR(20) DEFAULT '#FFFFFF',
    primary_color VARCHAR(20) DEFAULT '#8B5CF6',
    raw_code TEXT,
    unique_id VARCHAR(100) NOT NULL,
    url_slug VARCHAR(255),
    mockup_image_url VARCHAR(500),
    freebie_clicks INT DEFAULT 0,
    thank_you_clicks INT DEFAULT 0,
    freebie_type ENUM('template', 'custom') DEFAULT 'template',
    thank_you_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customer (customer_id),
    INDEX idx_template (template_id),
    INDEX idx_unique (unique_id),
    INDEX idx_freebie_type (freebie_type),
    INDEX idx_customer_type (customer_id, freebie_type),
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
