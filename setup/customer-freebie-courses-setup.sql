-- =====================================================
-- CUSTOMER FREEBIE KURSE: DATENBANK-SETUP
-- Ermöglicht Kunden, eigene Videokurse für Freebies zu erstellen
-- =====================================================

-- 1. Spalte in customer_freebies hinzufügen für Verknüpfung
ALTER TABLE `customer_freebies` 
ADD COLUMN `has_course` BOOLEAN DEFAULT FALSE AFTER `video_format`,
ADD COLUMN `course_mockup_url` VARCHAR(500) AFTER `has_course`,
ADD INDEX `idx_has_course` (`has_course`);

-- 2. FREEBIE_COURSES Tabelle (Kurse speziell für Freebies)
CREATE TABLE IF NOT EXISTS `freebie_courses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `freebie_id` INT(11) NOT NULL,
    `customer_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`freebie_id`) REFERENCES `customer_freebies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_freebie` (`freebie_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. FREEBIE_COURSE_MODULES Tabelle (Module für Freebie-Kurse)
CREATE TABLE IF NOT EXISTS `freebie_course_modules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`course_id`) REFERENCES `freebie_courses`(`id`) ON DELETE CASCADE,
    INDEX `idx_course` (`course_id`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. FREEBIE_COURSE_LESSONS Tabelle (Lektionen für Freebie-Kurse)
CREATE TABLE IF NOT EXISTS `freebie_course_lessons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `module_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `video_url` VARCHAR(500),
    `pdf_url` VARCHAR(500),
    `sort_order` INT(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`module_id`) REFERENCES `freebie_course_modules`(`id`) ON DELETE CASCADE,
    INDEX `idx_module` (`module_id`),
    INDEX `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. FREEBIE_COURSE_PROGRESS Tabelle (Fortschritt für Freebie-Kurse)
CREATE TABLE IF NOT EXISTS `freebie_course_progress` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `lead_email` VARCHAR(255) NOT NULL,
    `lesson_id` INT(11) NOT NULL,
    `completed` BOOLEAN DEFAULT FALSE,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_lead_lesson` (`lead_email`, `lesson_id`),
    FOREIGN KEY (`lesson_id`) REFERENCES `freebie_course_lessons`(`id`) ON DELETE CASCADE,
    INDEX `idx_lead` (`lead_email`),
    INDEX `idx_lesson` (`lesson_id`),
    INDEX `idx_completed` (`completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ERFOLGS-MELDUNG
-- =====================================================
SELECT 'Customer Freebie Kurse System erfolgreich eingerichtet!' AS Status;
