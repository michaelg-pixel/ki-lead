<?php
/**
 * Multi-Video & Drip-Content Migration Runner
 * Fügt Unterstützung für mehrere Videos und zeitverzögerte Freischaltung hinzu
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    $results = [];
    
    // 1. unlock_after_days Feld zu course_lessons hinzufügen
    try {
        // Erst prüfen ob Spalte existiert
        $stmt = $pdo->query("SHOW COLUMNS FROM course_lessons LIKE 'unlock_after_days'");
        if ($stmt->rowCount() == 0) {
            // Spalte existiert nicht, hinzufügen
            $pdo->exec("
                ALTER TABLE course_lessons 
                ADD COLUMN unlock_after_days INT NULL DEFAULT NULL 
                COMMENT 'Tage bis zur Freischaltung (NULL = sofort verfügbar)'
            ");
            $results[] = "✅ unlock_after_days Feld hinzugefügt";
        } else {
            $results[] = "ℹ️ unlock_after_days Feld existiert bereits";
        }
    } catch (Exception $e) {
        $results[] = "⚠️ unlock_after_days: " . $e->getMessage();
    }
    
    // Index für Performance
    try {
        // Prüfen ob Index existiert
        $stmt = $pdo->query("SHOW INDEX FROM course_lessons WHERE Key_name = 'idx_unlock_after_days'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                ALTER TABLE course_lessons 
                ADD INDEX idx_unlock_after_days (unlock_after_days)
            ");
            $results[] = "✅ Index für unlock_after_days erstellt";
        } else {
            $results[] = "ℹ️ Index existiert bereits";
        }
    } catch (Exception $e) {
        $results[] = "ℹ️ Index: " . $e->getMessage();
    }
    
    // 2. lesson_videos Tabelle für mehrere Videos pro Lektion
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lesson_videos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL COMMENT 'Referenz zur course_lessons-Tabelle',
            video_title VARCHAR(255) NOT NULL COMMENT 'Titel des Videos',
            video_url VARCHAR(500) NOT NULL COMMENT 'Video URL (YouTube, Vimeo, etc.)',
            sort_order INT DEFAULT 0 COMMENT 'Sortierung der Videos',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_lesson_id (lesson_id),
            INDEX idx_sort_order (sort_order),
            
            FOREIGN KEY (lesson_id) 
                REFERENCES course_lessons(id) 
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Mehrere Videos pro Lektion'
    ");
    $results[] = "✅ lesson_videos Tabelle erstellt";
    
    // 3. course_enrollments Tabelle für Drip-Content Tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS course_enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL COMMENT 'Referenz zur users-Tabelle',
            course_id INT NOT NULL COMMENT 'Referenz zur courses-Tabelle',
            enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Einschreibungsdatum',
            
            INDEX idx_user_id (user_id),
            INDEX idx_course_id (course_id),
            UNIQUE KEY unique_enrollment (user_id, course_id) 
                COMMENT 'Ein User kann nur einmal pro Kurs eingeschrieben sein',
            
            FOREIGN KEY (user_id) 
                REFERENCES users(id) 
                ON DELETE CASCADE
                ON UPDATE CASCADE,
            FOREIGN KEY (course_id) 
                REFERENCES courses(id) 
                ON DELETE CASCADE
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Tracking der Kurs-Einschreibungen für Drip Content'
    ");
    $results[] = "✅ course_enrollments Tabelle erstellt";
    
    // 4. Bestehende Enrollments automatisch erstellen (für User mit course_access)
    try {
        $stmt = $pdo->query("
            INSERT IGNORE INTO course_enrollments (user_id, course_id, enrolled_at)
            SELECT user_id, course_id, created_at
            FROM course_access
            WHERE created_at IS NOT NULL
        ");
        $enrolled = $stmt->rowCount();
        $results[] = "✅ $enrolled bestehende Enrollments migriert";
    } catch (Exception $e) {
        $results[] = "ℹ️ Enrollments: " . $e->getMessage();
    }
    
    // Erfolg!
    echo json_encode([
        'success' => true,
        'message' => 'Migration erfolgreich abgeschlossen!',
        'details' => implode("\n", $results)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Migration fehlgeschlagen',
        'error' => $e->getMessage()
    ]);
}
?>