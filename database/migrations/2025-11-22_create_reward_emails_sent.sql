-- Migration: reward_emails_sent Tabelle
-- Tracking von versendeten Belohnungs-Emails via Mailgun
-- Datum: 2025-11-22

CREATE TABLE IF NOT EXISTS reward_emails_sent (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lead_id INT NOT NULL,
    reward_id INT NOT NULL,
    mailgun_id VARCHAR(255) NULL COMMENT 'Mailgun Message-ID für Tracking',
    email_type VARCHAR(50) DEFAULT 'reward_unlocked' COMMENT 'reward_unlocked, welcome, verification, reminder',
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opened_at DATETIME NULL,
    clicked_at DATETIME NULL,
    failed_at DATETIME NULL,
    error_message TEXT NULL,
    
    INDEX idx_lead (lead_id),
    INDEX idx_reward (reward_id),
    INDEX idx_mailgun_id (mailgun_id),
    INDEX idx_email_type (email_type),
    INDEX idx_sent_at (sent_at),
    UNIQUE KEY unique_reward (lead_id, reward_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beispiel-Abfragen:
-- Alle versendeten Belohnungs-Emails für einen Lead:
-- SELECT * FROM reward_emails_sent WHERE lead_id = 1 ORDER BY sent_at DESC;

-- Alle noch nicht geöffneten Emails:
-- SELECT * FROM reward_emails_sent WHERE opened_at IS NULL;

-- Fehlgeschlagene Email-Versuche:
-- SELECT * FROM reward_emails_sent WHERE failed_at IS NOT NULL;
