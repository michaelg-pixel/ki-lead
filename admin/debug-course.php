<?php
/**
 * Debug Script für Kurs-Daten
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = getDBConnection();
$course_id = $_GET['id'] ?? 10;

echo "<h1>Debug: Kurs-Daten (ID: $course_id)</h1>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #fff; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #444; padding: 10px; text-align: left; }
    th { background: #2a2a4e; color: #a855f7; }
    tr:nth-child(even) { background: #252041; }
    h2 { color: #a855f7; margin-top: 30px; }
    pre { background: #0f0f1e; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

// Kurs laden
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

echo "<h2>Kurs-Details</h2>";
echo "<pre>" . print_r($course, true) . "</pre>";

// Module laden
$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

echo "<h2>Module (Anzahl: " . count($modules) . ")</h2>";
echo "<table>";
echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Sort Order</th></tr>";
foreach ($modules as $module) {
    echo "<tr>";
    echo "<td>" . $module['id'] . "</td>";
    echo "<td>" . htmlspecialchars($module['title']) . "</td>";
    echo "<td>" . htmlspecialchars($module['description']) . "</td>";
    echo "<td>" . $module['sort_order'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Lektionen für jedes Modul
foreach ($modules as $module) {
    echo "<h2>Lektionen für: " . htmlspecialchars($module['title']) . " (Modul ID: " . $module['id'] . ")</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY sort_order");
    $stmt->execute([$module['id']]);
    $lessons = $stmt->fetchAll();
    
    if (empty($lessons)) {
        echo "<p>Keine Lektionen vorhanden</p>";
        continue;
    }
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Description</th><th>Video URL</th><th>PDF</th><th>Unlock After Days</th><th>Sort Order</th></tr>";
    foreach ($lessons as $lesson) {
        echo "<tr>";
        echo "<td>" . $lesson['id'] . "</td>";
        echo "<td>" . htmlspecialchars($lesson['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($lesson['description'], 0, 50)) . "...</td>";
        echo "<td>" . ($lesson['video_url'] ? '✓' : '✗') . "</td>";
        echo "<td>" . ($lesson['pdf_attachment'] ? '✓' : '✗') . "</td>";
        echo "<td>" . ($lesson['unlock_after_days'] ?? 'sofort') . "</td>";
        echo "<td>" . $lesson['sort_order'] . "</td>";
        echo "</tr>";
        
        // Zusätzliche Videos
        $stmt_videos = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order");
        $stmt_videos->execute([$lesson['id']]);
        $additional_videos = $stmt_videos->fetchAll();
        
        if (!empty($additional_videos)) {
            echo "<tr><td colspan='7' style='background: #16213e; padding-left: 40px;'>";
            echo "<strong>Zusätzliche Videos:</strong><br>";
            foreach ($additional_videos as $video) {
                echo "- " . htmlspecialchars($video['video_title']) . " (" . $video['video_url'] . ")<br>";
            }
            echo "</td></tr>";
        }
    }
    echo "</table>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<a href='dashboard.php?page=course-edit&id=$course_id' style='color: #a855f7;'>← Zurück zur Kurs-Bearbeitung</a>";
echo " | ";
echo "<a href='preview_course.php?id=$course_id' style='color: #a855f7;'>Zur Vorschau →</a>";
?>