-- Tabelle für Angebots-Laufschrift
CREATE TABLE IF NOT EXISTS offers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    button_text VARCHAR(100) NOT NULL DEFAULT 'Jetzt ansehen',
    button_link VARCHAR(500) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Angebot einfügen
INSERT INTO offers (title, description, button_text, button_link, is_active) VALUES
('Neu: KI Avatar Business Masterclass', 
 'Lerne, wie du mit KI-Avataren automatisierte Geschäfte aufbaust. Jetzt 50% Rabatt für Mitglieder!',
 'Jetzt starten',
 'https://mehr-infos-jetzt.de',
 1);
