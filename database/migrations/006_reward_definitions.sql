-- =====================================================
-- Reward Definitions System
-- Konfigurierbare Belohnungsstufen für User
-- =====================================================

-- Belohnungs-Definitionen Tabelle
CREATE TABLE IF NOT EXISTS reward_definitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Welcher User diese Belohnungen definiert hat',
    
    -- Stufen-Info
    tier_level INT NOT NULL COMMENT 'Stufe: 1, 2, 3, etc.',
    tier_name VARCHAR(100) NOT NULL COMMENT 'z.B. Bronze, Silber, Gold',
    tier_description TEXT DEFAULT NULL,
    
    -- Erforderliche Empfehlungen
    required_referrals INT NOT NULL COMMENT 'Anzahl benötigter erfolgreicher Empfehlungen',
    
    -- Belohnung
    reward_type VARCHAR(50) NOT NULL COMMENT 'ebook, pdf, consultation, course, voucher, etc.',
    reward_title VARCHAR(255) NOT NULL,
    reward_description TEXT DEFAULT NULL,
    reward_value VARCHAR(100) DEFAULT NULL COMMENT 'z.B. 50€, 1h Beratung',
    
    -- Zugriff/Lieferung
    reward_download_url TEXT DEFAULT NULL,
    reward_access_code VARCHAR(100) DEFAULT NULL,
    reward_instructions TEXT DEFAULT NULL COMMENT 'Wie die Belohnung eingelöst wird',
    
    -- Visuals
    reward_icon VARCHAR(100) DEFAULT 'fa-gift' COMMENT 'Font Awesome Icon',
    reward_color VARCHAR(20) DEFAULT '#667eea',
    reward_badge_image VARCHAR(255) DEFAULT NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_featured BOOLEAN DEFAULT FALSE,
    auto_deliver BOOLEAN DEFAULT FALSE COMMENT 'Automatisch zusenden',
    
    -- Email-Benachrichtigung
    notification_subject VARCHAR(255) DEFAULT NULL,
    notification_body TEXT DEFAULT NULL,
    
    -- Sortierung
    sort_order INT DEFAULT 0,
    
    -- Zeitstempel
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indizes
    INDEX idx_user (user_id),
    INDEX idx_tier_level (tier_level),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order),
    
    UNIQUE KEY unique_user_tier (user_id, tier_level),
    
    CONSTRAINT fk_reward_def_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
        
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Konfigurierbare Belohnungsstufen';

-- =====================================================
-- Beispiel-Daten für Demo
-- HINWEIS: Diese müssen für jeden User individuell erstellt werden
-- =====================================================

-- View: Belohnungsstufen mit Statistiken
CREATE OR REPLACE VIEW view_reward_definitions_stats AS
SELECT 
    rd.id,
    rd.user_id,
    rd.tier_level,
    rd.tier_name,
    rd.required_referrals,
    rd.reward_title,
    rd.is_active,
    COUNT(DISTINCT rrt.lead_id) as leads_achieved,
    COUNT(DISTINCT rcr.id) as times_claimed
FROM reward_definitions rd
LEFT JOIN referral_reward_tiers rrt ON rd.tier_level = rrt.tier_id AND rd.user_id = rrt.lead_id
LEFT JOIN referral_claimed_rewards rcr ON rd.id = rcr.reward_id
GROUP BY rd.id;

-- =====================================================
-- Migration erfolgreich!
-- =====================================================
