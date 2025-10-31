<?php
/**
 * Setup Script für das Kursverwaltungssystem
 * Erstellt alle benötigten Tabellen
 */

require_once '../config/database.php';

try {
    // 1. courses Tabelle aktualisieren/erweitern
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            type ENUM('video', 'pdf') NOT NULL DEFAULT 'video',
            additional_info TEXT,
            mockup_url VARCHAR(500),
            pdf_file VARCHAR(500),
            is_freebie BOOLEAN DEFAULT FALSE,
            digistore_product_id VARCHAR(100),
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 2. course_modules Tabelle (nur für Video-Kurse)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 3. course_lessons Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_lessons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            video_url VARCHAR(500),
            description TEXT,
            pdf_attachment VARCHAR(500),
            sort_order INT DEFAULT 0,
            duration INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (module_id) REFERENCES course_modules(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 4. course_access Tabelle (Freischaltungen)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_access (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            course_id INT NOT NULL,
            access_granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            access_source ENUM('freebie', 'digistore', 'manual') DEFAULT 'manual',
            digistore_order_id VARCHAR(100),
            UNIQUE KEY unique_access (user_id, course_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 5. course_progress Tabelle
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            lesson_id INT NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            completed_at TIMESTAMP NULL,
            UNIQUE KEY unique_progress (user_id, lesson_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 6. uploads Ordner erstellen
    $uploadDirs = [
        '../uploads/courses/mockups',
        '../uploads/courses/pdfs',
        '../uploads/courses/attachments'
    ];
    
    foreach ($uploadDirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    echo "✅ Kursverwaltungssystem erfolgreich installiert!\n\n";
    echo "Erstellt:\n";
    echo "- courses (Haupt-Tabelle)\n";
    echo "- course_modules (Module für Video-Kurse)\n";
    echo "- course_lessons (Lektionen)\n";
    echo "- course_access (Freischaltungen)\n";
    echo "- course_progress (Fortschritt)\n";
    echo "- Upload-Ordner\n\n";
    echo "👉 Gehe zu: /admin/dashboard.php?page=templates\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage();
}
?>