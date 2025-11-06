-- =====================================================
-- VIDEO COURSE DATABASE MIGRATION
-- Füge diese Tabellen deiner Datenbank hinzu
-- =====================================================

-- 1. has_course Spalte zu customer_freebies hinzufügen (falls nicht vorhanden)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS has_course TINYINT(1) DEFAULT 0 AFTER freebie_type;

-- 2. Tabelle für Videokurse
CREATE TABLE IF NOT EXISTS freebie_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    freebie_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_freebie_id (freebie_id),
    
    FOREIGN KEY (freebie_id) 
        REFERENCES customer_freebies(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabelle für Kurs-Module
CREATE TABLE IF NOT EXISTS freebie_course_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course_id (course_id),
    INDEX idx_sort_order (sort_order),
    
    FOREIGN KEY (course_id) 
        REFERENCES freebie_courses(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabelle für Kurs-Lektionen
CREATE TABLE IF NOT EXISTS freebie_course_lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    video_url VARCHAR(500),
    pdf_url VARCHAR(500),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_module_id (module_id),
    INDEX idx_sort_order (sort_order),
    
    FOREIGN KEY (module_id) 
        REFERENCES freebie_course_modules(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VERIFY INSTALLATION
-- Führe diese Queries aus um zu prüfen ob alles klappt
-- =====================================================

-- Prüfe ob alle Tabellen existieren
SELECT 
    'freebie_courses' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_courses'

UNION ALL

SELECT 
    'freebie_course_modules' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_course_modules'

UNION ALL

SELECT 
    'freebie_course_lessons' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_course_lessons';

-- Sollte 3x "1" zurückgeben!
