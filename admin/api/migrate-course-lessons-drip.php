<?php
/**
 * BROWSER-MIGRATION: Drip Content & Multi-Video für course_lessons
 * 
 * Aufruf: https://deine-domain.de/admin/api/migrate-course-lessons-drip.php
 * 
 * WICHTIG: Nur für Admins zugänglich, kein Password nötig!
 */

session_start();
require_once '../../config/database.php';

// Admin-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert! Bitte als Admin einloggen.');
}

$pdo = getDBConnection();
$errors = [];
$success = [];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datenbank-Migration: Drip Content</title>
    <style>
        body {
            background: linear-gradient(135deg, #0f0f1e 0%, #1a1a2e 100%);
            color: #e0e0e0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: #fff;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #a0a0a0;
            margin-bottom: 3rem;
        }
        .box {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .success {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
        .error {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.4);
            color: #f87171;
        }
        .warning {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.4);
            color: #fbbf24;
        }
        .info {
            background: rgba(59, 130, 246, 0.15);
            border-color: rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }
        .box h2 {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
        }
        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        li:last-child {
            border-bottom: none;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 1rem 0.5rem 0 0;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(168, 85, 247, 0.4);
        }
        .btn-secondary {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            color: #60a5fa;
        }
        .progress {
            margin: 2rem 0;
        }
        .progress-bar {
            height: 8px;
            background: rgba(168, 85, 247, 0.2);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #a855f7 0%, #ec4899 100%);
            width: 0%;
            transition: width 0.5s ease;
        }
        code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Datenbank-Migration</h1>
        <p class="subtitle">Drip Content & Multi-Video Support für course_lessons</p>

        <?php if (!isset($_GET['run'])): ?>
            <!-- Bestätigung -->
            <div class="box warning">
                <h2>⚠️ Warnung</h2>
                <ul>
                    <li>✓ Dieses Script führt Änderungen an der Datenbank durch</li>
                    <li>✓ Es wird dringend empfohlen, vorher ein Backup zu erstellen</li>
                    <li>✓ Die Migration ist sicher und kann mehrfach ausgeführt werden</li>
                </ul>
            </div>

            <div class="box info">
                <h2>ℹ️ Was wird geändert?</h2>
                <ul>
                    <li><strong>course_lessons:</strong> Neue Spalte <code>unlock_after_days</code></li>
                    <li><strong>lesson_videos:</strong> Neue Tabelle für mehrere Videos pro Lektion</li>
                    <li><strong>course_enrollments:</strong> Neue Tabelle für Einschreibungs-Tracking</li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="?run=1" class="btn">✅ Migration jetzt starten</a>
                <a href="/admin/dashboard.php" class="btn btn-secondary">❌ Abbrechen</a>
            </div>
        <?php else: ?>
            <!-- Migration durchführen -->
            <div class="progress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
            </div>

            <?php
            $totalSteps = 4;
            $currentStep = 0;

            // SCHRITT 1: unlock_after_days zu course_lessons hinzufügen
            try {
                $currentStep++;
                
                // Prüfen ob Spalte existiert
                $stmt = $pdo->query("SHOW COLUMNS FROM course_lessons LIKE 'unlock_after_days'");
                $exists = $stmt->rowCount() > 0;
                
                if (!$exists) {
                    $sql = "ALTER TABLE course_lessons 
                            ADD COLUMN unlock_after_days INT NULL DEFAULT NULL 
                            COMMENT 'Tage bis zur Freischaltung (NULL = sofort verfügbar)' 
                            AFTER video_url";
                    $pdo->exec($sql);
                    $success[] = "✅ Spalte 'unlock_after_days' zu course_lessons hinzugefügt";
                    
                    // Index hinzufügen
                    try {
                        $sql = "ALTER TABLE course_lessons ADD INDEX idx_unlock_after_days (unlock_after_days)";
                        $pdo->exec($sql);
                        $success[] = "✅ Index für 'unlock_after_days' erstellt";
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate') === false) {
                            throw $e;
                        }
                        $success[] = "ℹ️ Index existiert bereits";
                    }
                } else {
                    $success[] = "ℹ️ Spalte 'unlock_after_days' existiert bereits";
                }
            } catch (PDOException $e) {
                $errors[] = "❌ Fehler bei unlock_after_days: " . $e->getMessage();
            }

            // SCHRITT 2: lesson_videos Tabelle erstellen
            try {
                $currentStep++;
                
                $sql = "CREATE TABLE IF NOT EXISTS lesson_videos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    lesson_id INT NOT NULL COMMENT 'Referenz zur course_lessons-Tabelle',
                    video_title VARCHAR(255) NOT NULL COMMENT 'Titel des Videos',
                    video_url VARCHAR(500) NOT NULL COMMENT 'Vimeo oder YouTube URL',
                    sort_order INT DEFAULT 0 COMMENT 'Sortierung',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                    
                    INDEX idx_lesson_id (lesson_id),
                    INDEX idx_sort_order (sort_order),
                    
                    FOREIGN KEY (lesson_id) 
                        REFERENCES course_lessons(id) 
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $pdo->exec($sql);
                $success[] = "✅ Tabelle 'lesson_videos' erstellt";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $success[] = "ℹ️ Tabelle 'lesson_videos' existiert bereits";
                } else {
                    $errors[] = "❌ Fehler bei lesson_videos: " . $e->getMessage();
                }
            }

            // SCHRITT 3: course_enrollments Tabelle erstellen
            try {
                $currentStep++;
                
                $sql = "CREATE TABLE IF NOT EXISTS course_enrollments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL COMMENT 'Referenz zur users-Tabelle',
                    course_id INT NOT NULL COMMENT 'Referenz zur courses-Tabelle',
                    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Einschreibungsdatum',
                    
                    INDEX idx_user_id (user_id),
                    INDEX idx_course_id (course_id),
                    UNIQUE KEY unique_enrollment (user_id, course_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $pdo->exec($sql);
                $success[] = "✅ Tabelle 'course_enrollments' erstellt";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    $success[] = "ℹ️ Tabelle 'course_enrollments' existiert bereits";
                } else {
                    $errors[] = "❌ Fehler bei course_enrollments: " . $e->getMessage();
                }
            }

            // SCHRITT 4: Daten-Check
            try {
                $currentStep++;
                
                // Prüfe ob alles korrekt erstellt wurde
                $stmt = $pdo->query("SHOW COLUMNS FROM course_lessons LIKE 'unlock_after_days'");
                $hasColumn = $stmt->rowCount() > 0;
                
                $stmt = $pdo->query("SHOW TABLES LIKE 'lesson_videos'");
                $hasVideosTable = $stmt->rowCount() > 0;
                
                $stmt = $pdo->query("SHOW TABLES LIKE 'course_enrollments'");
                $hasEnrollmentsTable = $stmt->rowCount() > 0;
                
                if ($hasColumn && $hasVideosTable && $hasEnrollmentsTable) {
                    $success[] = "✅ Alle Änderungen erfolgreich verifiziert";
                } else {
                    $errors[] = "⚠️ Einige Änderungen konnten nicht verifiziert werden";
                }
            } catch (PDOException $e) {
                $errors[] = "⚠️ Verifikation fehlgeschlagen: " . $e->getMessage();
            }

            // Ergebnisse anzeigen
            if (!empty($success)): ?>
                <div class="box success">
                    <h2>✅ Migration erfolgreich!</h2>
                    <ul>
                        <?php foreach ($success as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="box error">
                    <h2>❌ Fehler aufgetreten</h2>
                    <ul>
                        <?php foreach ($errors as $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="box info">
                <h2>📋 Nächste Schritte</h2>
                <ul>
                    <li>1️⃣ Gehe zu einem Kurs im Admin-Bereich</li>
                    <li>2️⃣ Klicke auf "Neue Lektion hinzufügen"</li>
                    <li>3️⃣ Die neuen Felder sollten jetzt sichtbar sein:
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li>⏰ Freischaltung nach X Tagen</li>
                            <li>🎬 Zusätzliche Videos</li>
                        </ul>
                    </li>
                </ul>
            </div>

            <div style="text-align: center;">
                <a href="/admin/dashboard.php?page=templates" class="btn">🎓 Zu den Kursen</a>
                <a href="/admin/dashboard.php" class="btn btn-secondary">🏠 Zum Dashboard</a>
            </div>

            <script>
                // Progress Animation
                const progressBar = document.getElementById('progressBar');
                const targetProgress = <?= ($currentStep / $totalSteps) * 100 ?>;
                
                setTimeout(() => {
                    progressBar.style.width = targetProgress + '%';
                }, 100);
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
