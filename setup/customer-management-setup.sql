-- ========================================
-- DATENBANK-SETUP FÜR KUNDENVERWALTUNG
-- Mit Digistore24 Integration
-- ========================================

-- 1. Users-Tabelle erweitern (falls Spalten fehlen)
-- ========================================

-- RAW-Code Spalte hinzufügen
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS raw_code VARCHAR(50) UNIQUE AFTER email,
ADD INDEX idx_raw_code (raw_code);

-- Digistore24 Felder hinzufügen
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS digistore_order_id VARCHAR(100) AFTER raw_code,
ADD COLUMN IF NOT EXISTS digistore_product_id VARCHAR(100) AFTER digistore_order_id,
ADD COLUMN IF NOT EXISTS digistore_product_name VARCHAR(255) AFTER digistore_product_id,
ADD COLUMN IF NOT EXISTS source VARCHAR(50) DEFAULT 'manual' AFTER digistore_product_name,
ADD COLUMN IF NOT EXISTS refund_date DATETIME NULL AFTER source,
ADD INDEX idx_digistore_order (digistore_order_id);

-- is_active Spalte hinzufügen (falls nicht vorhanden)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER role;

-- updated_at Spalte hinzufügen
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL AFTER created_at;

-- 2. User-Freebies Zuweisungs-Tabelle erstellen
-- ========================================

CREATE TABLE IF NOT EXISTS user_freebies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    freebie_id INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL COMMENT 'Admin User ID der die Zuweisung vorgenommen hat',
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME NULL,
    INDEX idx_user (user_id),
    INDEX idx_freebie (freebie_id),
    INDEX idx_assigned (assigned_at),
    UNIQUE KEY unique_assignment (user_id, freebie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (freebie_id) REFERENCES freebie_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Progress Tabelle (für Kurse etc.)
-- ========================================

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content_type ENUM('course', 'tutorial', 'freebie') NOT NULL,
    content_id INT NOT NULL,
    progress INT DEFAULT 0 COMMENT 'Fortschritt in Prozent (0-100)',
    last_accessed DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME NULL,
    INDEX idx_user_progress (user_id, content_type, content_id),
    INDEX idx_last_accessed (last_accessed),
    UNIQUE KEY unique_progress (user_id, content_type, content_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Webhook-Logs Tabelle (Optional für Debugging)
-- ========================================

CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    webhook_data JSON NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    processed TINYINT(1) DEFAULT 0,
    error_message TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at),
    INDEX idx_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. RAW-Codes für existierende Kunden generieren
-- ========================================

UPDATE users 
SET raw_code = CONCAT('RAW-', YEAR(CURDATE()), '-', LPAD(FLOOR(RAND() * 999) + 1, 3, '0'))
WHERE role = 'customer' 
AND (raw_code IS NULL OR raw_code = '');

-- 6. Source für existierende Kunden setzen
-- ========================================

UPDATE users 
SET source = 'manual'
WHERE role = 'customer' 
AND (source IS NULL OR source = '');

-- ========================================
-- FERTIG! ✅
-- ========================================

-- Tabellen-Status prüfen
SELECT 
    'users' as table_name,
    COUNT(*) as total_records,
    SUM(CASE WHEN role = 'customer' THEN 1 ELSE 0 END) as customers,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
FROM users

UNION ALL

SELECT 
    'user_freebies' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT freebie_id) as unique_freebies,
    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed_assignments
FROM user_freebies

UNION ALL

SELECT 
    'freebie_templates' as table_name,
    COUNT(*) as total_records,
    NULL as col2,
    NULL as col3,
    NULL as col4
FROM freebie_templates;
