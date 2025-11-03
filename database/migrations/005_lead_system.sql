-- =====================================================
-- Lead System Database Migration
-- Erstellt alle benötigten Tabellen für Lead-Login & Empfehlungsprogramm
-- =====================================================

-- 1. Lead Users Tabelle (Login-System für Leads)
CREATE TABLE IF NOT EXISTS lead_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    referral_code VARCHAR(50) UNIQUE NOT NULL COMMENT 'Eigener Empfehlungscode',
    referrer_code VARCHAR(50) DEFAULT NULL COMMENT 'Code des Werbers',
    api_token VARCHAR(64) UNIQUE DEFAULT NULL,
    user_id INT DEFAULT NULL COMMENT 'Verknüpfung zu users Tabelle falls vorhanden',
    
    -- Statistiken
    total_referrals INT DEFAULT 0 COMMENT 'Gesamt eingeladene Personen',
    successful_referrals INT DEFAULT 0 COMMENT 'Bestätigte Empfehlungen',
    rewards_earned INT DEFAULT 0 COMMENT 'Anzahl erhaltener Belohnungen',
    
    -- Status
    status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(64) DEFAULT NULL,
    
    -- Zeitstempel
    registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    
    -- Indizes
    INDEX idx_email (email),
    INDEX idx_referral_code (referral_code),
    INDEX idx_referrer_code (referrer_code),
    INDEX idx_status (status),
    INDEX idx_registered (registered_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lead Users mit Login-System';

-- 2. Lead Referrals Tabelle (Tracking einzelner Empfehlungen)
CREATE TABLE IF NOT EXISTS lead_referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL COMMENT 'Lead der geworben hat',
    referred_email VARCHAR(255) NOT NULL,
    referred_name VARCHAR(255) DEFAULT NULL,
    referred_user_id INT DEFAULT NULL COMMENT 'ID des geworbenen Leads',
    
    -- Status
    status ENUM('pending', 'active', 'converted', 'cancelled') DEFAULT 'pending',
    conversion_type VARCHAR(50) DEFAULT NULL COMMENT 'Art der Conversion (signup, purchase, etc)',
    conversion_value DECIMAL(10,2) DEFAULT 0.00,
    
    -- Tracking
    ip_hash VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 Hash der IP',
    user_agent TEXT DEFAULT NULL,
    
    -- Zeitstempel
    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    converted_at DATETIME DEFAULT NULL,
    
    -- Indizes
    INDEX idx_referrer (referrer_id),
    INDEX idx_email (referred_email),
    INDEX idx_status (status),
    INDEX idx_invited (invited_at),
    
    CONSTRAINT fk_lead_referrals_referrer 
        FOREIGN KEY (referrer_id) 
        REFERENCES lead_users(id) 
        ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracking von Lead-Empfehlungen';

-- 3. Referral Reward Tiers Tabelle (Erreichte Belohnungsstufen)
CREATE TABLE IF NOT EXISTS referral_reward_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    tier_id INT NOT NULL COMMENT 'Welche Stufe (1=3 Refs, 2=5 Refs, etc)',
    tier_name VARCHAR(100) DEFAULT NULL,
    rewards_earned INT DEFAULT 1,
    current_referrals INT DEFAULT 0 COMMENT 'Anzahl Empfehlungen bei Erreichen',
    
    -- Status
    unlocked BOOLEAN DEFAULT TRUE,
    notified BOOLEAN DEFAULT FALSE COMMENT 'Benachrichtigung gesendet',
    
    -- Zeitstempel
    achieved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notified_at DATETIME DEFAULT NULL,
    
    -- Indizes
    INDEX idx_lead (lead_id),
    INDEX idx_tier (tier_id),
    INDEX idx_achieved (achieved_at),
    
    UNIQUE KEY unique_lead_tier (lead_id, tier_id),
    
    CONSTRAINT fk_reward_tiers_lead 
        FOREIGN KEY (lead_id) 
        REFERENCES lead_users(id) 
        ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Erreichte Belohnungsstufen';

-- 4. Referral Claimed Rewards Tabelle (Eingelöste Belohnungen)
CREATE TABLE IF NOT EXISTS referral_claimed_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    reward_id INT NOT NULL COMMENT 'ID der Belohnung',
    reward_name VARCHAR(255) DEFAULT NULL,
    reward_type VARCHAR(50) DEFAULT NULL COMMENT 'ebook, consultation, course, vip, etc',
    
    -- Details
    reward_value DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    
    -- Status
    delivered BOOLEAN DEFAULT FALSE,
    delivery_method VARCHAR(50) DEFAULT NULL COMMENT 'email, download, manual, etc',
    
    -- Zeitstempel
    claimed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    delivered_at DATETIME DEFAULT NULL,
    
    -- Indizes
    INDEX idx_lead (lead_id),
    INDEX idx_reward (reward_id),
    INDEX idx_claimed (claimed_at),
    INDEX idx_delivered (delivered),
    
    CONSTRAINT fk_claimed_rewards_lead 
        FOREIGN KEY (lead_id) 
        REFERENCES lead_users(id) 
        ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Eingelöste Belohnungen';

-- 5. Lead Activity Log Tabelle (Optional: Aktivitäts-Tracking)
CREATE TABLE IF NOT EXISTS lead_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL COMMENT 'login, referral, reward_claimed, etc',
    activity_data JSON DEFAULT NULL,
    ip_hash VARCHAR(64) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_lead (lead_id),
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at),
    
    CONSTRAINT fk_activity_log_lead 
        FOREIGN KEY (lead_id) 
        REFERENCES lead_users(id) 
        ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Lead Aktivitäts-Log';

-- =====================================================
-- Initiale Daten: Belohnungs-Konfiguration
-- =====================================================

-- Beispiel-Belohnungen (können später angepasst werden)
-- Diese könnten in einer separaten Tabelle 'reward_definitions' gespeichert werden

-- =====================================================
-- Hinweise:
-- - Alle Passwörter werden mit password_hash() gespeichert
-- - IP-Adressen nur als SHA256-Hash
-- - E-Mails können für Double-Opt-In verifiziert werden
-- - Referral-Codes sind unique pro Lead
-- - Cascading Delete: Wenn Lead gelöscht wird, werden alle Daten entfernt
-- =====================================================

-- =====================================================
-- Views für einfachere Abfragen (Optional)
-- =====================================================

-- View: Lead-Übersicht mit Statistiken
CREATE OR REPLACE VIEW view_lead_overview AS
SELECT 
    lu.id,
    lu.name,
    lu.email,
    lu.referral_code,
    lu.total_referrals,
    lu.successful_referrals,
    lu.rewards_earned,
    lu.status,
    lu.registered_at,
    lu.last_login_at,
    COUNT(DISTINCT lr.id) as active_referrals,
    COUNT(DISTINCT rrt.id) as unlocked_tiers,
    COUNT(DISTINCT rcr.id) as claimed_rewards
FROM lead_users lu
LEFT JOIN lead_referrals lr ON lu.id = lr.referrer_id AND lr.status = 'active'
LEFT JOIN referral_reward_tiers rrt ON lu.id = rrt.lead_id
LEFT JOIN referral_claimed_rewards rcr ON lu.id = rcr.lead_id
GROUP BY lu.id;

-- =====================================================
-- Migration erfolgreich abgeschlossen!
-- =====================================================
