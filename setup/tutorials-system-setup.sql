-- Tutorial-System: Kategorien und Videos mit Vimeo-Integration

-- Kategorien-Tabelle
CREATE TABLE IF NOT EXISTS tutorial_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'video',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tutorials-Tabelle erweitern
DROP TABLE IF EXISTS tutorials;
CREATE TABLE IF NOT EXISTS tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    vimeo_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500),
    duration VARCHAR(20),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES tutorial_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Kategorien einfügen
INSERT INTO tutorial_categories (name, slug, description, icon, sort_order) VALUES
('Erste Schritte', 'erste-schritte', 'Grundlegende Einführung in das System', 'rocket', 1),
('Freebie-Editor', 'freebie-editor', 'Wie du den Freebie-Editor verwendest', 'edit', 2),
('Kurse', 'kurse', 'Alles über die Kursverwaltung', 'graduation-cap', 3),
('Marketing', 'marketing', 'Marketing-Tipps und Strategien', 'chart-line', 4),
('Fortgeschritten', 'fortgeschritten', 'Erweiterte Funktionen und Tipps', 'star', 5);

-- Beispiel-Videos (optional)
INSERT INTO tutorials (category_id, title, description, vimeo_url, sort_order) VALUES
(1, 'Willkommen im System', 'Eine kurze Einführung in alle Funktionen', 'https://player.vimeo.com/video/EXAMPLE1', 1),
(1, 'Dashboard-Übersicht', 'Lerne dein Dashboard kennen', 'https://player.vimeo.com/video/EXAMPLE2', 2),
(2, 'Dein erstes Freebie erstellen', 'Schritt-für-Schritt Anleitung', 'https://player.vimeo.com/video/EXAMPLE3', 1);
