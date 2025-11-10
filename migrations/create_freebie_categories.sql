-- Migration: Freebie Template Kategorien für Marktplatz
-- Erstellt: 2024

-- Tabelle für Freebie-Kategorien
CREATE TABLE IF NOT EXISTS freebie_template_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standardkategorien einfügen
INSERT INTO freebie_template_categories (name, slug, description, icon, sort_order) VALUES
('Business & Marketing', 'business-marketing', 'Business-Strategien, Marketing und Unternehmensaufbau', '💼', 1),
('Persönliche Entwicklung', 'personal-development', 'Selbstverbesserung, Motivation und Mindset', '🌱', 2),
('Gesundheit & Fitness', 'health-fitness', 'Fitness, Ernährung und Wellness', '💪', 3),
('Finanzen & Investieren', 'finance-investing', 'Geld verdienen, Sparen und Investieren', '💰', 4),
('Technologie & Software', 'tech-software', 'Programmierung, Apps und digitale Tools', '💻', 5),
('Kreativität & Design', 'creativity-design', 'Grafik, Fotografie und kreative Skills', '🎨', 6),
('Bildung & Lernen', 'education-learning', 'Sprachen, Schulung und Weiterbildung', '📚', 7),
('Beziehungen & Familie', 'relationships-family', 'Partnerschaft, Familie und soziale Skills', '❤️', 8),
('Immobilien', 'real-estate', 'Immobilien, Vermietung und Hausbau', '🏠', 9),
('E-Commerce & Online Business', 'ecommerce-online', 'Online-Shops, Dropshipping und digitale Produkte', '🛒', 10),
('Spiritualität & Mindfulness', 'spirituality-mindfulness', 'Meditation, Achtsamkeit und innere Balance', '🧘', 11),
('Karriere & Job', 'career-job', 'Bewerbung, Karriereplanung und Berufswahl', '👔', 12)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Spalte category_id zu customer_freebies hinzufügen (falls noch nicht vorhanden)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS category_id INT NULL,
ADD CONSTRAINT fk_customer_freebies_category 
    FOREIGN KEY (category_id) REFERENCES freebie_template_categories(id) 
    ON DELETE SET NULL;

-- Index für bessere Performance
ALTER TABLE customer_freebies 
ADD INDEX IF NOT EXISTS idx_category (category_id);
