<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Zugriff verweigert!');
}

$pdo = getDBConnection();
$errors = [];
$success = [];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Migration</title>
    <style>
        body { background: #0f0f1e; color: #e0e0e0; font-family: sans-serif; padding: 40px; }
        .box { background: rgba(26, 26, 46, 0.9); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 12px; padding: 2rem; margin: 1rem 0; }
        .success { border-color: rgba(34, 197, 94, 0.4); color: #86efac; }
        .error { border-color: rgba(239, 68, 68, 0.4); color: #f87171; }
        .btn { display: inline-block; background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); color: white; padding: 1rem 2rem; border-radius: 8px; text-decoration: none; margin: 1rem 0.5rem 0 0; }
    </style>
</head>
<body>
    <h1>🚀 Datenbank-Migration</h1>

    <?php if (!isset($_GET['run'])): ?>
        <div class="box">
            <h2>⚠️ Migration starten?</h2>
            <p>Diese Migration fügt unlock_after_days zu den Lektionen hinzu.</p>
            <a href="?run=1" class="btn">✅ Starten</a>
        </div>
    <?php else: ?>
        <?php
        // Prüfe welche Tabellen existieren
        $stmt = $pdo->query("SHOW TABLES LIKE 'lessons'");
        $hasLessons = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'course_lessons'");
        $hasCourseLessons = $stmt->rowCount() > 0;

        // unlock_after_days zu lessons
        if ($hasLessons) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM lessons LIKE 'unlock_after_days'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE lessons ADD COLUMN unlock_after_days INT NULL");
                    $success[] = "✅ unlock_after_days zu lessons hinzugefügt";
                } else {
                    $success[] = "ℹ️ lessons.unlock_after_days existiert bereits";
                }
            } catch (PDOException $e) {
                $errors[] = "❌ lessons: " . $e->getMessage();
            }
        }

        // unlock_after_days zu course_lessons
        if ($hasCourseLessons) {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM course_lessons LIKE 'unlock_after_days'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("ALTER TABLE course_lessons ADD COLUMN unlock_after_days INT NULL");
                    $success[] = "✅ unlock_after_days zu course_lessons hinzugefügt";
                } else {
                    $success[] = "ℹ️ course_lessons.unlock_after_days existiert bereits";
                }
            } catch (PDOException $e) {
                $errors[] = "❌ course_lessons: " . $e->getMessage();
            }
        }

        // lesson_videos existiert bereits, überspringen
        $success[] = "ℹ️ lesson_videos existiert bereits (wird nicht geändert)";

        // course_enrollments
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS course_enrollments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                course_id INT NOT NULL,
                enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_course_id (course_id),
                UNIQUE KEY unique_enrollment (user_id, course_id)
            )");
            $success[] = "✅ course_enrollments erstellt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = "❌ course_enrollments: " . $e->getMessage();
            } else {
                $success[] = "ℹ️ course_enrollments existiert bereits";
            }
        }
        ?>

        <?php if (!empty($success)): ?>
            <div class="box success">
                <h2>✅ Erfolgreich!</h2>
                <?php foreach ($success as $msg): ?>
                    <p><?= $msg ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="box error">
                <h2>❌ Fehler</h2>
                <?php foreach ($errors as $msg): ?>
                    <p><?= $msg ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="/admin/dashboard.php?page=templates" class="btn">Zu den Kursen</a>
    <?php endif; ?>
</body>
</html>
