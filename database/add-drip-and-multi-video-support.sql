-- ========================================
-- VIDEOKURS-ERWEITERUNG: DRIP CONTENT & MEHRERE VIDEOS
-- ========================================
-- Dieses Script fügt Unterstützung für:
-- 1. Drip Content (zeitbasierte Freischaltung)
-- 2. Mehrere Videos pro Lektion
-- ========================================

-- ========================================
-- 1. DRIP CONTENT: unlock_after_days zu lessons hinzufügen
-- ========================================

ALTER TABLE lessons 
ADD COLUMN IF NOT EXISTS unlock_after_days INT NULL DEFAULT NULL COMMENT 'Tage bis zur Freischaltung (NULL = sofort verfügbar)' 
AFTER vimeo_url;

-- Index für Performance-Optimierung
ALTER TABLE lessons 
ADD INDEX IF NOT EXISTS idx_unlock_after_days (unlock_after_days);

-- ========================================
-- 2. MEHRERE VIDEOS: Neue Tabelle lesson_videos erstellen
-- ========================================

CREATE TABLE IF NOT EXISTS lesson_videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL COMMENT 'Referenz zur lessons-Tabelle',
    video_title VARCHAR(255) NOT NULL COMMENT 'Titel des Videos',
    video_url VARCHAR(500) NOT NULL COMMENT 'Vimeo oder YouTube URL',
    sort_order INT DEFAULT 0 COMMENT 'Sortierung der Videos',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indizes
    INDEX idx_lesson_id (lesson_id),
    INDEX idx_sort_order (sort_order),
    
    -- Foreign Key Constraint
    FOREIGN KEY (lesson_id) 
        REFERENCES lessons(id) 
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Mehrere Videos pro Lektion';

-- ========================================
-- 3. CUSTOMER ENROLLMENT TRACKING
-- ========================================
-- Tabelle um zu tracken, wann ein Kunde sich für einen Kurs eingeschrieben hat
-- Dies wird benötigt um unlock_after_days zu berechnen

CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Referenz zur users-Tabelle',
    course_id INT NOT NULL COMMENT 'Referenz zur courses-Tabelle',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Einschreibungsdatum',
    
    -- Indizes
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    UNIQUE KEY unique_enrollment (user_id, course_id) COMMENT 'Ein User kann nur einmal pro Kurs eingeschrieben sein'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracking der Kurs-Einschreibungen für Drip Content';

-- ========================================
-- 4. DATENMIGRATION (OPTIONAL)
-- ========================================
-- Falls bereits Lektionen mit vimeo_url existieren, können diese
-- automatisch in die lesson_videos Tabelle migriert werden:

-- Kommentiere dies aus, wenn du existierende Videos migrieren möchtest:
/*
INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order)
SELECT 
    id as lesson_id,
    CONCAT('Video ', title) as video_title,
    vimeo_url as video_url,
    1 as sort_order
FROM lessons
WHERE vimeo_url IS NOT NULL 
AND vimeo_url != ''
AND NOT EXISTS (
    SELECT 1 FROM lesson_videos WHERE lesson_videos.lesson_id = lessons.id
);
*/

-- ========================================
-- 5. HILFSFUNKTIONEN FÜR QUERIES
-- ========================================

-- Beispiel: Prüfen ob eine Lektion für einen User freigeschaltet ist
/*
SELECT 
    l.id,
    l.title,
    l.unlock_after_days,
    ce.enrolled_at,
    DATEDIFF(NOW(), ce.enrolled_at) as days_since_enrollment,
    CASE 
        WHEN l.unlock_after_days IS NULL THEN 1
        WHEN DATEDIFF(NOW(), ce.enrolled_at) >= l.unlock_after_days THEN 1
        ELSE 0
    END as is_unlocked,
    CASE 
        WHEN l.unlock_after_days IS NOT NULL 
        AND DATEDIFF(NOW(), ce.enrolled_at) < l.unlock_after_days 
        THEN (l.unlock_after_days - DATEDIFF(NOW(), ce.enrolled_at))
        ELSE 0
    END as days_until_unlock
FROM lessons l
JOIN modules m ON l.module_id = m.id
JOIN course_enrollments ce ON m.course_id = ce.course_id
WHERE ce.user_id = ? -- User ID hier einsetzen
AND m.course_id = ?  -- Course ID hier einsetzen
ORDER BY l.sort_order;
*/

-- ========================================
-- WICHTIGE HINWEISE
-- ========================================

-- 1. vimeo_url Spalte:
--    - Die alte vimeo_url Spalte bleibt bestehen für Rückwärtskompatibilität
--    - Neue Lektionen sollten lesson_videos verwenden
--    - Die Admin-Oberfläche zeigt beide Optionen

-- 2. Drip Content Berechnung:
--    - unlock_after_days = NULL bedeutet sofort verfügbar
--    - unlock_after_days = 0 bedeutet sofort verfügbar
--    - unlock_after_days = 7 bedeutet 7 Tage nach Einschreibung
--    - Das Einschreibungsdatum wird in course_enrollments gespeichert

-- 3. Performance:
--    - Indizes sind gesetzt für optimale Query-Performance
--    - Bei großen Datenmengen sollte ein Cache verwendet werden

-- 4. Backup:
--    - IMMER ein Backup vor Migration erstellen!
--    - Teste auf einer Staging-Umgebung zuerst

-- ========================================
-- ENDE DES MIGRATIONS-SCRIPTS
-- ========================================
