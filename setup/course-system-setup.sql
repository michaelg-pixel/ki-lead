-- =====================================================
-- VIDEOKURS-SYSTEM: DATENBANK-SETUP
-- Vollständiges Setup für Kurs-System mit Fortschritt
-- =====================================================

-- 1. COURSES Tabelle (Haupttabelle für Kurse)
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `additional_info` TEXT,
    `type` ENUM('video', 'pdf') NOT NULL DEFAULT 'video',
    `mockup_url` VARCHAR(500),
    `pdf_file` VARCHAR(500),
    `is_freebie` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `digistore_product_id` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_freebie` (`is_freebie`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_digistore` (`digistore_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. COURSE_MODULES Tabelle (Module innerhalb eines Kurses)
CREATE TABLE IF NOT EXISTS `course_modules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    INDEX `idx_course` (`course_id`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. COURSE_LESSONS Tabelle (Lektionen innerhalb eines Moduls)
CREATE TABLE IF NOT EXISTS `course_lessons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `module_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `video_url` VARCHAR(500),
    `pdf_attachment` VARCHAR(500),
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`module_id`) REFERENCES `course_modules`(`id`) ON DELETE CASCADE,
    INDEX `idx_module` (`module_id`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. COURSE_ACCESS Tabelle (Zugriffskontrolle für Kurse)
CREATE TABLE IF NOT EXISTS `course_access` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `course_id` INT(11) NOT NULL,
    `access_source` ENUM('freebie', 'purchase', 'admin', 'digistore24') DEFAULT 'freebie',
    `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_course` (`user_id`, `course_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_course` (`course_id`),
    INDEX `idx_source` (`access_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. COURSE_PROGRESS Tabelle (Fortschritt pro Lektion)
CREATE TABLE IF NOT EXISTS `course_progress` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `lesson_id` INT(11) NOT NULL,
    `completed` BOOLEAN DEFAULT FALSE,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_lesson` (`user_id`, `lesson_id`),
    FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_lesson` (`lesson_id`),
    INDEX `idx_completed` (`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- BEISPIEL-DATEN (Optional - Kommentiere aus zum Testen)
-- =====================================================

/*
-- Beispiel-Kurs einfügen
INSERT INTO `courses` (`title`, `description`, `type`, `is_freebie`, `is_active`) 
VALUES 
    ('KI Mastery für Anfänger', 'Lerne die Grundlagen der künstlichen Intelligenz', 'video', TRUE, TRUE),
    ('Advanced Marketing Strategien', 'Professionelle Marketing-Strategien für dein Business', 'video', FALSE, TRUE);

-- Beispiel-Module einfügen
INSERT INTO `course_modules` (`course_id`, `title`, `description`, `sort_order`)
VALUES
    (1, 'Einführung in KI', 'Grundlagen und Konzepte', 0),
    (1, 'Praktische Anwendungen', 'KI in der Praxis nutzen', 1);

-- Beispiel-Lektionen einfügen
INSERT INTO `course_lessons` (`module_id`, `title`, `description`, `video_url`, `sort_order`)
VALUES
    (1, 'Was ist künstliche Intelligenz?', 'Eine Einführung in KI', 'https://www.youtube.com/watch?v=example1', 0),
    (1, 'Geschichte der KI', 'Von den Anfängen bis heute', 'https://www.youtube.com/watch?v=example2', 1),
    (2, 'KI im Alltag', 'Wie KI unser Leben verändert', 'https://www.youtube.com/watch?v=example3', 0);
*/

-- =====================================================
-- ERFOLGS-MELDUNG
-- =====================================================
SELECT 'Videokurs-System erfolgreich eingerichtet!' AS Status;