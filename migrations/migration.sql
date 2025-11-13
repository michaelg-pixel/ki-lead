-- ===================================================================
-- Lead Dashboard Migration - Pure SQL
-- Kann direkt in phpMyAdmin oder MySQL CLI ausgeführt werden
-- ===================================================================

-- Verwende die richtige Datenbank
-- USE deine_datenbank_name;

-- ===================================================================
-- 1. Lead Login Tokens Tabelle
-- ===================================================================

CREATE TABLE IF NOT EXISTS lead_login_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    customer_id INT,
    freebie_id INT,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at),
    INDEX idx_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 2. Lead Users Tabelle
-- ===================================================================

CREATE TABLE IF NOT EXISTS lead_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    referral_code VARCHAR(20) UNIQUE,
    total_referrals INT DEFAULT 0,
    successful_referrals INT DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_email (email),
    INDEX idx_user (user_id),
    INDEX idx_referral_code (referral_code),
    UNIQUE KEY unique_email_user (email, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spalten hinzufügen falls Tabelle bereits existiert
ALTER TABLE lead_users 
ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) UNIQUE,
ADD COLUMN IF NOT EXISTS total_referrals INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS successful_referrals INT DEFAULT 0;

-- ===================================================================
-- 3. Lead Referrals Tabelle
-- ===================================================================

CREATE TABLE IF NOT EXISTS lead_referrals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referrer_id INT NOT NULL,
    referred_name VARCHAR(255),
    referred_email VARCHAR(255) NOT NULL,
    status ENUM('pending', 'active', 'converted', 'cancelled') DEFAULT 'pending',
    invited_at DATETIME NOT NULL,
    converted_at DATETIME NULL,
    INDEX idx_referrer (referrer_id),
    INDEX idx_email (referred_email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 4. Reward Definitions Tabelle
-- ===================================================================

CREATE TABLE IF NOT EXISTS reward_definitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tier_level INT NOT NULL,
    tier_name VARCHAR(255),
    tier_description TEXT,
    required_referrals INT NOT NULL,
    reward_type VARCHAR(50),
    reward_title VARCHAR(255) NOT NULL,
    reward_description TEXT,
    reward_icon VARCHAR(100),
    reward_color VARCHAR(20),
    reward_value VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL,
    INDEX idx_user (user_id),
    INDEX idx_tier (tier_level),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- 5. Referral Claimed Rewards Tabelle
-- ===================================================================

CREATE TABLE IF NOT EXISTS referral_claimed_rewards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    reward_id INT NOT NULL,
    reward_name VARCHAR(255),
    claimed_at DATETIME NOT NULL,
    INDEX idx_lead (lead_id),
    INDEX idx_reward (reward_id),
    INDEX idx_claimed (claimed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================================
-- Optional: Bestehende Leads migrieren (falls vorhanden)
-- ===================================================================

-- Wenn du bereits eine freebie_registrations Tabelle hast:
/*
INSERT INTO lead_users (name, email, user_id, referral_code, created_at)
SELECT 
    name,
    email,
    customer_id,
    UPPER(SUBSTRING(MD5(CONCAT(email, id)), 1, 8)),
    created_at
FROM freebie_registrations
WHERE email NOT IN (SELECT email FROM lead_users)
ON DUPLICATE KEY UPDATE name = VALUES(name);
*/

-- ===================================================================
-- Verifikation: Tabellen prüfen
-- ===================================================================

-- Alle neuen Tabellen anzeigen
SHOW TABLES LIKE 'lead_%';
SHOW TABLES LIKE 'reward_%';
SHOW TABLES LIKE 'referral_%';

-- Struktur prüfen
DESCRIBE lead_login_tokens;
DESCRIBE lead_users;
DESCRIBE lead_referrals;
DESCRIBE reward_definitions;
DESCRIBE referral_claimed_rewards;

-- ===================================================================
-- Migration abgeschlossen!
-- ===================================================================

SELECT '✅ Migration erfolgreich abgeschlossen!' as Status;