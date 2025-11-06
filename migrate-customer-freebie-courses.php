<?php
/**
 * Migration Script für Customer Freebie Kurse
 * Führt die Datenbank-Migration ohne Passwort-Abfrage durch
 */

require_once __DIR__ . '/config/database.php';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Freebie Kurse Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 800px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #1a1a2e;
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            color: #1e40af;
            font-size: 18px;
            margin-bottom: 10px;
        }
        .info-box p {
            color: #1e3a8a;
            line-height: 1.6;
        }
        .info-box ul {
            margin-top: 10px;
            margin-left: 20px;
        }
        .info-box li {
            color: #1e3a8a;
            margin-bottom: 8px;
        }
        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-bottom: 20px;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 10px;
            display: none;
        }
        .result.success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
            display: block;
        }
        .result.error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            display: block;
        }
        .result h3 {
            margin-bottom: 10px;
        }
        .result.success h3 {
            color: #065f46;
        }
        .result.error h3 {
            color: #991b1b;
        }
        .result p, .result li {
            color: #374151;
            line-height: 1.6;
        }
        .result ul {
            margin-top: 10px;
            margin-left: 20px;
        }
        .result li {
            margin-bottom: 8px;
        }
        .loader {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Customer Freebie Kurse Migration</h1>
        <p class="subtitle">Einrichtung der Datenbank für Videokurse in Customer Freebies</p>
        
        <div class="info-box">
            <h3>📋 Was macht diese Migration?</h3>
            <p>Diese Migration erstellt die notwendigen Datenbanktabellen, damit Kunden ihre eigenen Videokurse für Freebies erstellen können:</p>
            <ul>
                <li><strong>freebie_courses:</strong> Haupttabelle für Freebie-Kurse</li>
                <li><strong>freebie_course_modules:</strong> Module innerhalb der Kurse</li>
                <li><strong>freebie_course_lessons:</strong> Lektionen mit Videos/PDFs</li>
                <li><strong>freebie_course_progress:</strong> Fortschrittsverfolgung für Leads</li>
                <li><strong>customer_freebies:</strong> Neue Spalten für Kursverknüpfung</li>
            </ul>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div class="loader" id="loader" style="display: block;"></div>
            <?php
            try {
                $pdo = getDBConnection();
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $results = [];
                $errors = [];
                
                // 1. customer_freebies Spalten hinzufügen
                try {
                    $pdo->exec("ALTER TABLE `customer_freebies` 
                                ADD COLUMN `has_course` BOOLEAN DEFAULT FALSE AFTER `video_format`,
                                ADD COLUMN `course_mockup_url` VARCHAR(500) AFTER `has_course`,
                                ADD INDEX `idx_has_course` (`has_course`)");
                    $results[] = "✅ Spalten zu customer_freebies hinzugefügt";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        $results[] = "ℹ️ Spalten existieren bereits in customer_freebies";
                    } else {
                        $errors[] = "❌ Fehler bei customer_freebies: " . $e->getMessage();
                    }
                }
                
                // 2. freebie_courses Tabelle
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `freebie_courses` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $results[] = "✅ Tabelle freebie_courses erstellt";
                } catch (PDOException $e) {
                    $errors[] = "❌ Fehler bei freebie_courses: " . $e->getMessage();
                }
                
                // 3. freebie_course_modules Tabelle
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `freebie_course_modules` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $results[] = "✅ Tabelle freebie_course_modules erstellt";
                } catch (PDOException $e) {
                    $errors[] = "❌ Fehler bei freebie_course_modules: " . $e->getMessage();
                }
                
                // 4. freebie_course_lessons Tabelle
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `freebie_course_lessons` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $results[] = "✅ Tabelle freebie_course_lessons erstellt";
                } catch (PDOException $e) {
                    $errors[] = "❌ Fehler bei freebie_course_lessons: " . $e->getMessage();
                }
                
                // 5. freebie_course_progress Tabelle
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `freebie_course_progress` (
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                    $results[] = "✅ Tabelle freebie_course_progress erstellt";
                } catch (PDOException $e) {
                    $errors[] = "❌ Fehler bei freebie_course_progress: " . $e->getMessage();
                }
                
                echo '<script>document.getElementById("loader").style.display = "none";</script>';
                
                if (empty($errors)) {
                    echo '<div class="result success">';
                    echo '<h3>🎉 Migration erfolgreich abgeschlossen!</h3>';
                    echo '<p>Alle Tabellen wurden erfolgreich erstellt:</p>';
                    echo '<ul>';
                    foreach ($results as $result) {
                        echo '<li>' . htmlspecialchars($result) . '</li>';
                    }
                    echo '</ul>';
                    echo '<p><strong>Nächste Schritte:</strong></p>';
                    echo '<ul>';
                    echo '<li>Öffne den Freebie-Editor</li>';
                    echo '<li>Wähle den Tab "Videokurs"</li>';
                    echo '<li>Erstelle Module und Lektionen</li>';
                    echo '</ul>';
                    echo '</div>';
                } else {
                    echo '<div class="result error">';
                    echo '<h3>⚠️ Migration mit Fehlern abgeschlossen</h3>';
                    if (!empty($results)) {
                        echo '<p><strong>Erfolgreich:</strong></p><ul>';
                        foreach ($results as $result) {
                            echo '<li>' . htmlspecialchars($result) . '</li>';
                        }
                        echo '</ul>';
                    }
                    echo '<p><strong>Fehler:</strong></p><ul>';
                    foreach ($errors as $error) {
                        echo '<li>' . htmlspecialchars($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
            } catch (Exception $e) {
                echo '<script>document.getElementById("loader").style.display = "none";</script>';
                echo '<div class="result error">';
                echo '<h3>❌ Migration fehlgeschlagen</h3>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            ?>
        <?php else: ?>
            <form method="POST">
                <button type="submit" class="button">🚀 Migration starten</button>
            </form>
            
            <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                <h3>⚠️ Wichtige Hinweise</h3>
                <p>
                    Diese Migration ist <strong>sicher</strong> und zerstört keine bestehenden Daten. 
                    Es werden nur neue Tabellen und Spalten hinzugefügt. Der Prozess dauert nur wenige Sekunden.
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
