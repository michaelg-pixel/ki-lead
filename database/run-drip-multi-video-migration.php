<?php
/**
 * Migration Runner für Drip Content & Multi-Video Support
 * 
 * VORSICHT: Dieses Script führt Datenbank-Änderungen durch!
 * Erstelle IMMER ein Backup bevor du es ausführst!
 * 
 * Aufruf: https://deine-domain.de/database/run-drip-multi-video-migration.php
 */

session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Admin-Check
if (!isLoggedIn() || !isAdmin()) {
    die('Zugriff verweigert! Nur Administratoren können Migrationen ausführen.');
}

$conn = getDBConnection();
$errors = [];
$success = [];

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drip Content & Multi-Video Migration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #0f0f1e; color: #e0e0e0; font-family: system-ui; }
        .success { background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.4); color: #86efac; }
        .error { background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; }
        .info { background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #60a5fa; }
        .warning { background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2">🚀 Drip Content & Multi-Video Migration</h1>
        <p class="text-gray-400 mb-8">Erweitert das Videokurs-System um zeitbasierte Freischaltung und mehrere Videos pro Lektion</p>';

// Bestätigung erforderlich
if (!isset($_GET['confirm'])) {
    echo '<div class="warning p-6 rounded-lg mb-6">
            <h2 class="text-xl font-bold mb-4">⚠️ WARNUNG</h2>
            <ul class="list-disc list-inside space-y-2 mb-6">
                <li>Dieses Script führt Änderungen an der Datenbank durch</li>
                <li>Es wird dringend empfohlen, vorher ein Backup zu erstellen</li>
                <li>Teste die Migration zuerst auf einer Staging-Umgebung</li>
            </ul>
            <div class="space-y-3">
                <a href="?confirm=yes" class="inline-block bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-semibold">
                    ✅ Ja, Migration jetzt durchführen
                </a>
                <a href="../admin/courses.php" class="inline-block bg-gray-600 hover:bg-gray-700 px-6 py-3 rounded-lg font-semibold ml-4">
                    ❌ Abbrechen
                </a>
            </div>
          </div>';
    
    echo '<div class="info p-6 rounded-lg">
            <h2 class="text-xl font-bold mb-4">ℹ️ Was wird geändert?</h2>
            <ul class="list-disc list-inside space-y-2">
                <li><strong>lessons Tabelle:</strong> Neue Spalte <code>unlock_after_days</code> für zeitbasierte Freischaltung</li>
                <li><strong>lesson_videos Tabelle:</strong> Neue Tabelle für mehrere Videos pro Lektion</li>
                <li><strong>course_enrollments Tabelle:</strong> Neue Tabelle zum Tracking der Kurs-Einschreibungen</li>
            </ul>
          </div>';
    
    echo '</div></body></html>';
    exit;
}

// Migration durchführen
echo '<div class="info p-4 rounded-lg mb-6">
        <strong>Migration läuft...</strong>
      </div>';

// 1. unlock_after_days zu lessons hinzufügen
try {
    $sql = "ALTER TABLE lessons 
            ADD COLUMN IF NOT EXISTS unlock_after_days INT NULL DEFAULT NULL 
            COMMENT 'Tage bis zur Freischaltung (NULL = sofort verfügbar)' 
            AFTER vimeo_url";
    $conn->exec($sql);
    $success[] = "✅ Spalte 'unlock_after_days' zur lessons-Tabelle hinzugefügt";
    
    // Index hinzufügen
    $sql = "ALTER TABLE lessons ADD INDEX IF NOT EXISTS idx_unlock_after_days (unlock_after_days)";
    $conn->exec($sql);
    $success[] = "✅ Index für 'unlock_after_days' erstellt";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false || 
        strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "ℹ️ Spalte 'unlock_after_days' existiert bereits";
    } else {
        $errors[] = "❌ Fehler beim Hinzufügen von unlock_after_days: " . $e->getMessage();
    }
}

// 2. lesson_videos Tabelle erstellen
try {
    $sql = "CREATE TABLE IF NOT EXISTS lesson_videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT NOT NULL COMMENT 'Referenz zur lessons-Tabelle',
        video_title VARCHAR(255) NOT NULL COMMENT 'Titel des Videos',
        video_url VARCHAR(500) NOT NULL COMMENT 'Vimeo oder YouTube URL',
        sort_order INT DEFAULT 0 COMMENT 'Sortierung der Videos',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_lesson_id (lesson_id),
        INDEX idx_sort_order (sort_order),
        
        FOREIGN KEY (lesson_id) 
            REFERENCES lessons(id) 
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Mehrere Videos pro Lektion'";
    
    $conn->exec($sql);
    $success[] = "✅ Tabelle 'lesson_videos' erfolgreich erstellt";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "ℹ️ Tabelle 'lesson_videos' existiert bereits";
    } else {
        $errors[] = "❌ Fehler beim Erstellen von lesson_videos: " . $e->getMessage();
    }
}

// 3. course_enrollments Tabelle erstellen
try {
    $sql = "CREATE TABLE IF NOT EXISTS course_enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL COMMENT 'Referenz zur users-Tabelle',
        course_id INT NOT NULL COMMENT 'Referenz zur courses-Tabelle',
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Einschreibungsdatum',
        
        INDEX idx_user_id (user_id),
        INDEX idx_course_id (course_id),
        UNIQUE KEY unique_enrollment (user_id, course_id) COMMENT 'Ein User kann nur einmal pro Kurs eingeschrieben sein'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Tracking der Kurs-Einschreibungen für Drip Content'";
    
    $conn->exec($sql);
    $success[] = "✅ Tabelle 'course_enrollments' erfolgreich erstellt";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $success[] = "ℹ️ Tabelle 'course_enrollments' existiert bereits";
    } else {
        $errors[] = "❌ Fehler beim Erstellen von course_enrollments: " . $e->getMessage();
    }
}

// 4. Existierende Einschreibungen migrieren (aus customer_courses oder ähnlichen Tabellen)
try {
    // Prüfe ob courses Tabelle user_id Beziehung hat
    $stmt = $conn->query("SHOW COLUMNS FROM courses LIKE 'user_id'");
    if ($stmt->rowCount() > 0) {
        // Migriere existierende Kurs-Zuordnungen
        $sql = "INSERT IGNORE INTO course_enrollments (user_id, course_id, enrolled_at)
                SELECT DISTINCT user_id, id, created_at 
                FROM courses 
                WHERE user_id IS NOT NULL";
        $result = $conn->exec($sql);
        if ($result > 0) {
            $success[] = "✅ $result existierende Einschreibungen migriert";
        }
    }
} catch (PDOException $e) {
    // Keine Migration nötig oder Fehler - nicht kritisch
    $success[] = "ℹ️ Keine existierenden Einschreibungen zum Migrieren gefunden";
}

// Ergebnisse anzeigen
if (!empty($success)) {
    echo '<div class="success p-6 rounded-lg mb-6">
            <h2 class="text-xl font-bold mb-4">✅ Migration erfolgreich!</h2>
            <ul class="list-disc list-inside space-y-2">';
    foreach ($success as $msg) {
        echo "<li>$msg</li>";
    }
    echo '</ul></div>';
}

if (!empty($errors)) {
    echo '<div class="error p-6 rounded-lg mb-6">
            <h2 class="text-xl font-bold mb-4">❌ Fehler aufgetreten</h2>
            <ul class="list-disc list-inside space-y-2">';
    foreach ($errors as $msg) {
        echo "<li>$msg</li>";
    }
    echo '</ul></div>';
}

echo '<div class="info p-6 rounded-lg">
        <h2 class="text-xl font-bold mb-4">📋 Nächste Schritte</h2>
        <ol class="list-decimal list-inside space-y-2">
            <li>Gehe zu <a href="../admin/courses.php" class="underline">Kurse verwalten</a></li>
            <li>Bearbeite einen Kurs und füge Module/Lektionen hinzu</li>
            <li>Bei jeder Lektion kannst du jetzt:
                <ul class="list-disc list-inside ml-6 mt-2">
                    <li>Mehrere Videos hinzufügen</li>
                    <li>Freischaltungstage festlegen (z.B. "7" für 7 Tage nach Kursstart)</li>
                </ul>
            </li>
        </ol>
      </div>';

echo '<div class="mt-8">
        <a href="../admin/courses.php" class="inline-block bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg font-semibold">
            🎓 Zu den Kursen
        </a>
      </div>';

echo '</div></body></html>';
?>
