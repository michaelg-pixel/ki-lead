-- Admin-Profil Setup: Tabellen für Aktivitätsprotokoll, Sessions und Präferenzen

-- 1. Aktivitätsprotokoll für Admin-Aktionen
CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL COMMENT 'z.B. user_created, course_updated, freebie_deleted',
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Login-Sessions für Sicherheitsübersicht
CREATE TABLE IF NOT EXISTS login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    browser VARCHAR(100),
    device VARCHAR(100),
    location VARCHAR(255),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active),
    INDEX idx_session (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admin-Präferenzen
CREATE TABLE IF NOT EXISTS admin_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notifications_new_users BOOLEAN DEFAULT TRUE,
    notifications_course_purchases BOOLEAN DEFAULT TRUE,
    notifications_weekly_summary BOOLEAN DEFAULT TRUE,
    theme VARCHAR(20) DEFAULT 'dark' COMMENT 'dark, light',
    language VARCHAR(10) DEFAULT 'de' COMMENT 'de, en',
    timezone VARCHAR(50) DEFAULT 'Europe/Berlin',
    date_format VARCHAR(20) DEFAULT 'd.m.Y',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Spalte für Profilbild in users-Tabelle hinzufügen (falls nicht vorhanden)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL AFTER email;

-- Standard-Präferenzen für bestehende Admins erstellen
INSERT INTO admin_preferences (user_id, notifications_new_users, notifications_course_purchases, notifications_weekly_summary)
SELECT id, TRUE, TRUE, TRUE
FROM users 
WHERE role = 'admin'
ON DUPLICATE KEY UPDATE user_id = user_id;

-- Beispiel-Aktivitäten für Demo (optional)
INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
SELECT 
    id,
    'system_login',
    'Admin hat sich im System angemeldet',
    '127.0.0.1'
FROM users 
WHERE role = 'admin'
LIMIT 1;
