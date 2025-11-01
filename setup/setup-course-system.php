<?php
/**
 * Automatisches Setup-Script für Videokurs-System
 * Führt alle notwendigen Datenbank-Operationen aus
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/setup/setup-course-system.php
 */

require_once '../config/database.php';

// Sicherheitscheck: Nur im Development-Modus ausführbar
$allow_setup = true; // Setze auf false in Production!

if (!$allow_setup) {
    die('Setup ist in Production deaktiviert. Bitte manuell über phpMyAdmin durchführen.');
}

try {
    $pdo = getDBConnection();
    echo "<h1>Videokurs-System Setup</h1>";
    echo "<style>
        body { font-family: system-ui; max-width: 800px; margin: 50px auto; padding: 20px; background: #0a0a16; color: #e5e7eb; }
        h1 { color: #a855f7; }
        .success { color: #4ade80; margin: 10px 0; }
        .error { color: #fb7185; margin: 10px 0; }
        .info { color: #60a5fa; margin: 10px 0; }
        code { background: rgba(168, 85, 247, 0.1); padding: 2px 8px; border-radius: 4px; }
    </style>";
    
    // 1. COURSES Tabelle
    echo "<h2>1. Erstelle COURSES Tabelle...</h2>";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ COURSES Tabelle erstellt</div>";
    
    // 2. COURSE_MODULES Tabelle
    echo "<h2>2. Erstelle COURSE_MODULES Tabelle...</h2>";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ COURSE_MODULES Tabelle erstellt</div>";
    
    // 3. COURSE_LESSONS Tabelle
    echo "<h2>3. Erstelle COURSE_LESSONS Tabelle...</h2>";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ COURSE_LESSONS Tabelle erstellt</div>";
    
    // 4. COURSE_ACCESS Tabelle
    echo "<h2>4. Erstelle COURSE_ACCESS Tabelle...</h2>";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ COURSE_ACCESS Tabelle erstellt</div>";
    
    // 5. COURSE_PROGRESS Tabelle
    echo "<h2>5. Erstelle COURSE_PROGRESS Tabelle...</h2>";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ COURSE_PROGRESS Tabelle erstellt</div>";
    
    // Tabellenübersicht
    echo "<h2>📊 Tabellen-Übersicht</h2>";
    $tables = [
        'courses' => 'Haupttabelle für alle Kurse (Video & PDF)',
        'course_modules' => 'Module innerhalb von Video-Kursen',
        'course_lessons' => 'Lektionen innerhalb von Modulen',
        'course_access' => 'Zugriffskontrolle (wer darf welchen Kurs sehen)',
        'course_progress' => 'Fortschritt pro Lektion und Benutzer'
    ];
    
    foreach ($tables as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<div class='success'>✅ <code>$table</code> - $description</div>";
        } else {
            echo "<div class='error'>❌ <code>$table</code> - Fehler beim Erstellen</div>";
        }
    }
    
    echo "<h2>✅ Setup erfolgreich abgeschlossen!</h2>";
    echo "<div class='info'>";
    echo "<h3>Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li>Gehe zu <a href='/admin/dashboard.php?page=templates' style='color: #a855f7;'>Admin → Kursverwaltung</a></li>";
    echo "<li>Erstelle deinen ersten Kurs</li>";
    echo "<li>Füge Module und Lektionen hinzu</li>";
    echo "<li>Kunden können die Kurse unter <code>/customer/dashboard.php?page=kurse</code> sehen</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔗 Wichtige Links:</h3>";
    echo "<ul>";
    echo "<li><strong>Admin Kursverwaltung:</strong> <code>/admin/dashboard.php?page=templates</code></li>";
    echo "<li><strong>Kurs bearbeiten:</strong> <code>/admin/dashboard.php?page=course-edit&id=[ID]</code></li>";
    echo "<li><strong>Kurs Vorschau (Admin):</strong> <code>/admin/preview_course.php?id=[ID]</code></li>";
    echo "<li><strong>Customer Kurse:</strong> <code>/customer/dashboard.php?page=kurse</code></li>";
    echo "<li><strong>Kursansicht (Customer):</strong> <code>/customer/course-view.php?id=[ID]</code></li>";
    echo "<li><strong>Digistore24 Webhook:</strong> <code>https://app.ki-leadsystem.com/webhook/digistore24.php</code></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>⚙️ Digistore24 Integration:</h3>";
    echo "<p>Der Webhook ist bereits konfiguriert unter:<br>";
    echo "<code>https://app.ki-leadsystem.com/webhook/digistore24.php</code></p>";
    echo "<p>Stelle sicher, dass in Digistore24 folgendes eingerichtet ist:</p>";
    echo "<ul>";
    echo "<li>IPN-URL auf den Webhook setzen</li>";
    echo "<li>Produkt-ID in der <code>courses</code> Tabelle hinterlegen</li>";
    echo "<li>Bei erfolgreichem Kauf wird automatisch Zugang gewährt</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
    echo "<div class='error'>Bitte stelle sicher, dass die Datenbank-Verbindung korrekt konfiguriert ist.</div>";
}
?>