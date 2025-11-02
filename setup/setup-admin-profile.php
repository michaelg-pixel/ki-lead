<?php
/**
 * Setup-Script für Admin-Profil-Features
 * 
 * Erstellt die benötigten Datenbank-Tabellen für:
 * - Aktivitätsprotokoll
 * - Login-Sessions
 * - Admin-Präferenzen
 * - Profilbild-Spalte
 */

require_once '../config/database.php';

echo "<h1>Admin-Profil Setup</h1>";
echo "<pre>";

try {
    $pdo = getDBConnection();
    
    // 1. Admin Activity Log Tabelle
    echo "Erstelle admin_activity_log Tabelle...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL COMMENT 'z.B. user_created, course_updated, freebie_deleted',
            action_description TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_created (user_id, created_at),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ admin_activity_log erstellt\n\n";
    
    // 2. Login Sessions Tabelle
    echo "Erstelle login_sessions Tabelle...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            browser VARCHAR(100),
            device VARCHAR(100),
            location VARCHAR(255),
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_active (user_id, is_active),
            INDEX idx_session (session_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ login_sessions erstellt\n\n";
    
    // 3. Admin Preferences Tabelle
    echo "Erstelle admin_preferences Tabelle...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            notifications_new_users BOOLEAN DEFAULT TRUE,
            notifications_course_purchases BOOLEAN DEFAULT TRUE,
            notifications_weekly_summary BOOLEAN DEFAULT TRUE,
            theme VARCHAR(20) DEFAULT 'dark' COMMENT 'dark, light',
            language VARCHAR(10) DEFAULT 'de' COMMENT 'de, en',
            timezone VARCHAR(50) DEFAULT 'Europe/Berlin',
            date_format VARCHAR(20) DEFAULT 'd.m.Y',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ admin_preferences erstellt\n\n";
    
    // 4. Profilbild-Spalte hinzufügen
    echo "Füge profile_image Spalte zur users Tabelle hinzu...\n";
    try {
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER email
        ");
        echo "✓ profile_image Spalte hinzugefügt\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "⚠ profile_image Spalte existiert bereits\n\n";
        } else {
            throw $e;
        }
    }
    
    // 5. Standard-Präferenzen für Admin-Benutzer erstellen
    echo "Erstelle Standard-Präferenzen für Admin-Benutzer...\n";
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $count = 0;
    foreach ($admins as $adminId) {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO admin_preferences (user_id, notifications_new_users, notifications_course_purchases, notifications_weekly_summary)
            VALUES (?, TRUE, TRUE, TRUE)
        ");
        if ($stmt->execute([$adminId])) {
            $count++;
        }
    }
    echo "✓ Standard-Präferenzen für $count Admin(s) erstellt\n\n";
    
    // 6. Beispiel-Aktivität für Demo
    echo "Erstelle Beispiel-Aktivitäten...\n";
    foreach ($admins as $adminId) {
        $stmt = $pdo->prepare("
            INSERT INTO admin_activity_log (user_id, action_type, action_description, ip_address) 
            VALUES (?, 'system_setup', 'Admin-Profil-Features wurden eingerichtet', ?)
        ");
        $stmt->execute([$adminId, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    }
    echo "✓ Beispiel-Aktivitäten erstellt\n\n";
    
    // 7. Upload-Verzeichnis erstellen
    echo "Erstelle Upload-Verzeichnis für Profilbilder...\n";
    $uploadDir = '../uploads/profile-images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        echo "✓ Verzeichnis erstellt: $uploadDir\n\n";
    } else {
        echo "⚠ Verzeichnis existiert bereits\n\n";
    }
    
    // .htaccess für Upload-Verzeichnis
    $htaccessContent = "Options -Indexes\n";
    $htaccessContent .= "<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">\n";
    $htaccessContent .= "    Order Allow,Deny\n";
    $htaccessContent .= "    Allow from all\n";
    $htaccessContent .= "</FilesMatch>\n";
    file_put_contents($uploadDir . '.htaccess', $htaccessContent);
    echo "✓ .htaccess für Uploads erstellt\n\n";
    
    echo "\n";
    echo "═══════════════════════════════════════════════\n";
    echo "✅ SETUP ERFOLGREICH ABGESCHLOSSEN!\n";
    echo "═══════════════════════════════════════════════\n\n";
    echo "Die folgenden Features sind jetzt verfügbar:\n";
    echo "• Profil bearbeiten (Name, E-Mail, Profilbild)\n";
    echo "• Sicherheit (Login-Sessions, Aktivitäten)\n";
    echo "• Aktivitätsprotokoll (letzte 20 Aktionen)\n";
    echo "• Präferenzen (Benachrichtigungen, Theme)\n\n";
    echo "Rufen Sie die Profilseite auf:\n";
    echo "https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=profile\n\n";
    
} catch (Exception $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n\n";
    echo "Stack Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>

<style>
body {
    font-family: monospace;
    background: #1a1a2e;
    color: #eee;
    padding: 20px;
    margin: 0;
}
h1 {
    color: #a855f7;
    border-bottom: 2px solid #a855f7;
    padding-bottom: 10px;
}
pre {
    background: #0f0f1e;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #333;
    overflow-x: auto;
}
</style>
