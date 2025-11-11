<?php
/**
 * Migration: Lead-Registrierung für Videokurse tracken
 * 
 * Fügt Tabelle hinzu, um zu tracken, wann ein Lead Zugriff auf einen Kurs erhalten hat
 * Notwendig für Drip-Content (unlock_after_days)
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Starte Migration: Lead-Registrierung für Videokurse tracken\n\n";
    
    // Prüfe ob Tabelle bereits existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_course_lead_access'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabelle 'freebie_course_lead_access' existiert bereits\n";
        exit;
    }
    
    // Erstelle neue Tabelle für Lead-Zugriff
    $sql = "
    CREATE TABLE IF NOT EXISTS freebie_course_lead_access (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        lead_email VARCHAR(255) NOT NULL,
        access_granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_course_email (course_id, lead_email),
        INDEX idx_email (lead_email),
        UNIQUE KEY unique_course_lead (course_id, lead_email),
        FOREIGN KEY (course_id) REFERENCES freebie_courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
    echo "✅ Tabelle 'freebie_course_lead_access' erstellt\n\n";
    
    // Prüfe ob freebie_course_progress Tabelle existiert und erstelle sie falls nicht
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebie_course_progress'");
    if ($stmt->rowCount() === 0) {
        echo "📝 Erstelle auch 'freebie_course_progress' Tabelle...\n";
        
        $sql_progress = "
        CREATE TABLE IF NOT EXISTS freebie_course_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lesson_id INT NOT NULL,
            lead_email VARCHAR(255) NOT NULL,
            completed BOOLEAN DEFAULT FALSE,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_lesson_email (lesson_id, lead_email),
            INDEX idx_email (lead_email),
            UNIQUE KEY unique_lesson_lead (lesson_id, lead_email),
            FOREIGN KEY (lesson_id) REFERENCES freebie_course_lessons(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql_progress);
        echo "✅ Tabelle 'freebie_course_progress' erstellt\n\n";
    }
    
    echo "✅ Migration erfolgreich abgeschlossen!\n";
    echo "ℹ️  Die Tabelle 'freebie_course_lead_access' trackt nun, wann ein Lead Zugriff auf einen Kurs erhalten hat.\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}
