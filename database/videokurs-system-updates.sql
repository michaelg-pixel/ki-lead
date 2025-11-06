-- ========================================
-- VIDEOKURS-SYSTEM: DATENBANK-UPDATES
-- ========================================
-- Dieses Script fügt fehlende Felder hinzu und erstellt optionale Tabellen
-- für erweiterte Features wie persistentes Fortschritts-Tracking

-- ========================================
-- 1. FEHLENDE FELDER IN BESTEHENDEN TABELLEN
-- ========================================

-- customer_freebies: has_course Flag (falls noch nicht vorhanden)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS has_course TINYINT(1) DEFAULT 0 COMMENT 'Gibt an, ob ein Videokurs existiert';

-- customer_freebies: Timestamps (falls noch nicht vorhanden)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- freebie_courses: customer_id (KRITISCHER FIX!)
-- Falls die Spalte fehlt, wird sie hinzugefügt
ALTER TABLE freebie_courses 
ADD COLUMN IF NOT EXISTS customer_id INT(11) NOT NULL AFTER freebie_id,
ADD INDEX idx_customer_id (customer_id);

-- freebie_courses: Timestamps
ALTER TABLE freebie_courses 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- freebie_course_modules: Timestamps
ALTER TABLE freebie_course_modules 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- freebie_course_lessons: Timestamps
ALTER TABLE freebie_course_lessons 
ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ========================================
-- 2. INDIZES FÜR BESSERE PERFORMANCE
-- ========================================

-- Optimierung für häufige Abfragen
ALTER TABLE freebie_courses 
ADD INDEX IF NOT EXISTS idx_freebie_id (freebie_id);

ALTER TABLE freebie_course_modules 
ADD INDEX IF NOT EXISTS idx_course_id (course_id),
ADD INDEX IF NOT EXISTS idx_sort_order (sort_order);

ALTER TABLE freebie_course_lessons 
ADD INDEX IF NOT EXISTS idx_module_id (module_id),
ADD INDEX IF NOT EXISTS idx_sort_order (sort_order);

-- ========================================
-- 3. OPTIONAL: PERSISTENTES FORTSCHRITTS-TRACKING
-- ========================================
-- Diese Tabelle ist optional und kann verwendet werden, um den Lernfortschritt
-- persistent in der Datenbank zu speichern (Alternative zur Session-Speicherung)

CREATE TABLE IF NOT EXISTS freebie_course_progress (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    
    -- Nutzer-Identifikation (E-Mail-basiert, da keine User-Accounts)
    email VARCHAR(255) NOT NULL COMMENT 'E-Mail des Teilnehmers',
    
    -- Kurs-Zuordnung
    course_id INT(11) NOT NULL COMMENT 'Referenz zu freebie_courses',
    lesson_id INT(11) NOT NULL COMMENT 'Referenz zu freebie_course_lessons',
    
    -- Fortschritts-Daten
    completed TINYINT(1) DEFAULT 1 COMMENT 'Lektion abgeschlossen',
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Zeitpunkt des Abschlusses',
    
    -- Zusätzliche Tracking-Daten
    watch_time_seconds INT(11) DEFAULT 0 COMMENT 'Angesehene Sekunden (optional)',
    last_position_seconds INT(11) DEFAULT 0 COMMENT 'Letzte Position im Video (optional)',
    
    -- Zeitstempel
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indizes
    INDEX idx_email (email),
    INDEX idx_course_id (course_id),
    INDEX idx_lesson_id (lesson_id),
    UNIQUE KEY unique_progress (email, lesson_id) COMMENT 'Ein Nutzer kann eine Lektion nur einmal abschließen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Optionale Tabelle für persistentes Fortschritts-Tracking';

-- ========================================
-- 4. OPTIONAL: VIDEOKURS-ZERTIFIKATE
-- ========================================
-- Diese Tabelle kann verwendet werden, um Zertifikate für abgeschlossene Kurse auszustellen

CREATE TABLE IF NOT EXISTS freebie_course_certificates (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    
    -- Nutzer & Kurs
    email VARCHAR(255) NOT NULL COMMENT 'E-Mail des Teilnehmers',
    course_id INT(11) NOT NULL COMMENT 'Referenz zu freebie_courses',
    
    -- Zertifikats-Daten
    certificate_code VARCHAR(32) NOT NULL UNIQUE COMMENT 'Eindeutiger Zertifikats-Code',
    completion_date DATE NOT NULL COMMENT 'Abschlussdatum',
    
    -- Download-Tracking
    download_count INT(11) DEFAULT 0 COMMENT 'Anzahl der Downloads',
    last_downloaded_at TIMESTAMP NULL COMMENT 'Letzter Download',
    
    -- Zeitstempel
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indizes
    INDEX idx_email (email),
    INDEX idx_course_id (course_id),
    INDEX idx_certificate_code (certificate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Optionale Tabelle für Kurs-Zertifikate';

-- ========================================
-- 5. OPTIONAL: VIDEOKURS-BEWERTUNGEN
-- ========================================
-- Diese Tabelle kann verwendet werden, um Feedback zu Lektionen zu sammeln

CREATE TABLE IF NOT EXISTS freebie_course_ratings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    
    -- Nutzer & Lektion
    email VARCHAR(255) NOT NULL COMMENT 'E-Mail des Teilnehmers',
    lesson_id INT(11) NOT NULL COMMENT 'Referenz zu freebie_course_lessons',
    
    -- Bewertung
    rating TINYINT(1) NOT NULL COMMENT 'Bewertung 1-5 Sterne',
    comment TEXT NULL COMMENT 'Optional: Kommentar zur Bewertung',
    
    -- Zeitstempel
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Indizes
    INDEX idx_lesson_id (lesson_id),
    UNIQUE KEY unique_rating (email, lesson_id) COMMENT 'Ein Nutzer kann eine Lektion nur einmal bewerten'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Optionale Tabelle für Lektions-Bewertungen';

-- ========================================
-- 6. DATENBANK-WARTUNG & CLEANUP
-- ========================================

-- Alte Fortschritte bereinigen (z.B. nach 90 Tagen Inaktivität)
-- Dies sollte als Cronjob ausgeführt werden:
-- DELETE FROM freebie_course_progress WHERE updated_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- ========================================
-- 7. BEISPIEL-ABFRAGEN FÜR STATISTIKEN
-- ========================================

-- Anzahl der Nutzer pro Kurs
-- SELECT course_id, COUNT(DISTINCT email) as total_students
-- FROM freebie_course_progress
-- GROUP BY course_id;

-- Durchschnittliche Abschlussrate pro Kurs
-- SELECT 
--     c.id,
--     c.title,
--     COUNT(DISTINCT p.email) as total_students,
--     COUNT(DISTINCT CASE WHEN p.completed = 1 THEN p.email END) as completed_students,
--     ROUND(COUNT(DISTINCT CASE WHEN p.completed = 1 THEN p.email END) / COUNT(DISTINCT p.email) * 100, 2) as completion_rate
-- FROM freebie_courses c
-- LEFT JOIN freebie_course_progress p ON c.id = p.course_id
-- GROUP BY c.id;

-- Beliebteste Lektionen (nach Abschlüssen)
-- SELECT 
--     l.id,
--     l.title,
--     COUNT(*) as total_completions
-- FROM freebie_course_lessons l
-- JOIN freebie_course_progress p ON l.id = p.lesson_id
-- WHERE p.completed = 1
-- GROUP BY l.id
-- ORDER BY total_completions DESC
-- LIMIT 10;

-- ========================================
-- 8. PERFORMANCE-OPTIMIERUNG
-- ========================================

-- Composite-Indizes für häufige Join-Operationen
ALTER TABLE freebie_course_progress
ADD INDEX IF NOT EXISTS idx_email_course (email, course_id),
ADD INDEX IF NOT EXISTS idx_course_completed (course_id, completed);

-- Volltextsuche für Kurs-Inhalte (optional)
-- ALTER TABLE freebie_course_lessons
-- ADD FULLTEXT INDEX ft_content (title, content);

-- ========================================
-- WICHTIGE HINWEISE
-- ========================================

-- 1. Session vs. Database Storage:
--    - Session-Speicherung (Standard): Schnell, keine DB-Last, aber temporär
--    - DB-Speicherung (Optional): Persistent, ermöglicht Statistiken, aber mehr DB-Queries
--    
-- 2. E-Mail als User-ID:
--    - Da keine User-Accounts existieren, wird die E-Mail als Identifikator verwendet
--    - DSGVO-konform: E-Mails sollten gehasht oder verschlüsselt gespeichert werden (optional)
--
-- 3. Backup vor Änderungen:
--    - IMMER ein Backup der Datenbank erstellen vor dem Ausführen von ALTER TABLE
--    - Testen auf einer Staging-Umgebung
--
-- 4. Migration bestehender Daten:
--    - Falls customer_id in freebie_courses fehlt, müssen bestehende Einträge aktualisiert werden:
--    
--    UPDATE freebie_courses fc
--    JOIN customer_freebies cf ON fc.freebie_id = cf.id
--    SET fc.customer_id = cf.customer_id
--    WHERE fc.customer_id IS NULL OR fc.customer_id = 0;

-- ========================================
-- ENDE DES SCRIPTS
-- ========================================
