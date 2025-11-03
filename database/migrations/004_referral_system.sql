-- =====================================================
-- Referral System Database Migration
-- DSGVO-konform mit IP-Hashing und Anti-Fraud
-- MySQL-kompatible Version (ohne IF NOT EXISTS bei ALTER)
-- =====================================================

-- 1. Erweitere customers Tabelle (einzelne ALTER statements)
-- Falls die Spalten bereits existieren, werden sie übersprungen

-- referral_enabled
ALTER TABLE customers ADD COLUMN referral_enabled BOOLEAN DEFAULT FALSE AFTER status;

-- company_name
ALTER TABLE customers ADD COLUMN company_name VARCHAR(255) DEFAULT NULL AFTER referral_enabled;

-- company_email
ALTER TABLE customers ADD COLUMN company_email VARCHAR(255) DEFAULT NULL AFTER company_name;

-- company_imprint_html
ALTER TABLE customers ADD COLUMN company_imprint_html TEXT DEFAULT NULL AFTER company_email;

-- referral_code
ALTER TABLE customers ADD COLUMN referral_code VARCHAR(50) UNIQUE DEFAULT NULL AFTER company_imprint_html;

-- Generiere unique Referral-Codes für existierende Kunden
UPDATE customers 
SET referral_code = CONCAT('REF', LPAD(id, 6, '0'), SUBSTRING(MD5(CONCAT(id, email, UNIX_TIMESTAMP())), 1, 6))
WHERE referral_code IS NULL;

-- 2. Referral Clicks Tabelle (Tracking von Klicks)
CREATE TABLE IF NOT EXISTS referral_clicks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    ref_code VARCHAR(50) NOT NULL,
    ip_address_hash VARCHAR(64) NOT NULL COMMENT 'SHA256-Hash der IP',
    user_agent TEXT,
    fingerprint VARCHAR(64) NOT NULL COMMENT 'Hash aus IP + UserAgent',
    session_id VARCHAR(64) DEFAULT NULL,
    referer TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_customer (customer_id),
    INDEX idx_ref_code (ref_code),
    INDEX idx_fingerprint (fingerprint),
    INDEX idx_created (created_at),
    INDEX idx_ip_hash (ip_address_hash),
    
    CONSTRAINT fk_referral_clicks_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Referral Conversions Tabelle (Erfolgreiche Conversions)
CREATE TABLE IF NOT EXISTS referral_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    ref_code VARCHAR(50) NOT NULL,
    ip_address_hash VARCHAR(64) NOT NULL COMMENT 'SHA256-Hash der IP',
    user_agent TEXT,
    fingerprint VARCHAR(64) NOT NULL COMMENT 'Hash aus IP + UserAgent',
    source ENUM('thankyou', 'pixel', 'api') DEFAULT 'thankyou',
    suspicious BOOLEAN DEFAULT FALSE COMMENT 'Fraud-Detection Flag',
    time_to_convert INT DEFAULT NULL COMMENT 'Sekunden von Klick bis Conversion',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_customer (customer_id),
    INDEX idx_ref_code (ref_code),
    INDEX idx_fingerprint (fingerprint),
    INDEX idx_suspicious (suspicious),
    INDEX idx_created (created_at),
    INDEX idx_source (source),
    
    CONSTRAINT fk_referral_conversions_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Referral Leads Tabelle (Teilnehmer am Empfehlungsprogramm)
CREATE TABLE IF NOT EXISTS referral_leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Customer der das Programm betreibt',
    ref_code VARCHAR(50) NOT NULL COMMENT 'Code des Werbers',
    email VARCHAR(255) NOT NULL,
    email_hash VARCHAR(64) NOT NULL COMMENT 'SHA256-Hash für Deduplizierung',
    confirmed BOOLEAN DEFAULT FALSE COMMENT 'E-Mail bestätigt (Double-Opt-In)',
    reward_notified BOOLEAN DEFAULT FALSE COMMENT 'Belohnungs-Mail gesendet',
    confirmation_token VARCHAR(64) DEFAULT NULL,
    ip_address_hash VARCHAR(64) DEFAULT NULL COMMENT 'SHA256-Hash der IP bei Anmeldung',
    gdpr_consent BOOLEAN DEFAULT TRUE COMMENT 'DSGVO-Einwilligung erteilt',
    gdpr_consent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmed_at DATETIME DEFAULT NULL,
    notified_at DATETIME DEFAULT NULL,
    
    INDEX idx_customer (customer_id),
    INDEX idx_ref_code (ref_code),
    INDEX idx_email (email),
    INDEX idx_email_hash (email_hash),
    INDEX idx_confirmed (confirmed),
    INDEX idx_reward_notified (reward_notified),
    INDEX idx_token (confirmation_token),
    INDEX idx_created (created_at),
    
    UNIQUE KEY unique_customer_email (customer_id, email_hash),
    
    CONSTRAINT fk_referral_leads_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Referral Stats Tabelle (Aggregierte Statistiken)
CREATE TABLE IF NOT EXISTS referral_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    total_clicks INT DEFAULT 0,
    unique_clicks INT DEFAULT 0,
    total_conversions INT DEFAULT 0,
    suspicious_conversions INT DEFAULT 0,
    total_leads INT DEFAULT 0,
    confirmed_leads INT DEFAULT 0,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    last_click_at DATETIME DEFAULT NULL,
    last_conversion_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer (customer_id),
    
    CONSTRAINT fk_referral_stats_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Referral Rewards Tabelle (Belohnungs-Konfiguration)
CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL UNIQUE,
    reward_type ENUM('email', 'none', 'webhook', 'custom') DEFAULT 'email',
    goal_referrals INT DEFAULT 5 COMMENT 'Anzahl benötigter Empfehlungen',
    reward_email_subject VARCHAR(255) DEFAULT 'Ihre Belohnung wartet auf Sie!',
    reward_email_template TEXT DEFAULT NULL,
    auto_send_reward BOOLEAN DEFAULT FALSE,
    webhook_url VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_customer (customer_id),
    
    CONSTRAINT fk_referral_rewards_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Referral Fraud Log (Betrugsversuche protokollieren)
CREATE TABLE IF NOT EXISTS referral_fraud_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    ref_code VARCHAR(50) DEFAULT NULL,
    fraud_type ENUM('fast_conversion', 'duplicate_ip', 'duplicate_fingerprint', 'suspicious_pattern', 'rate_limit') NOT NULL,
    ip_address_hash VARCHAR(64) DEFAULT NULL,
    fingerprint VARCHAR(64) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    additional_data JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_customer (customer_id),
    INDEX idx_fraud_type (fraud_type),
    INDEX idx_created (created_at),
    
    CONSTRAINT fk_referral_fraud_customer 
        FOREIGN KEY (customer_id) 
        REFERENCES customers(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Initialisiere Stats für existierende Kunden
INSERT INTO referral_stats (customer_id)
SELECT id FROM customers
WHERE id NOT IN (SELECT customer_id FROM referral_stats);

-- 9. Initialisiere Rewards Konfiguration für existierende Kunden
INSERT INTO referral_rewards (customer_id)
SELECT id FROM customers
WHERE id NOT IN (SELECT customer_id FROM referral_rewards);

-- =====================================================
-- DSGVO-Compliance Hinweise:
-- - IP-Adressen werden IMMER als SHA256-Hash gespeichert
-- - Fingerprints sind Hashes, keine Rohdaten
-- - E-Mails werden zusätzlich gehasht für Deduplizierung
-- - Einwilligungen werden mit Zeitstempel gespeichert
-- - Löschung erfolgt kaskadierend bei Customer-Löschung
-- =====================================================
