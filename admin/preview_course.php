<?php
/**
 * Admin-Vorschau für Kurse
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = getDBConnection();
$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    header('Location: dashboard.php?page=templates');
    exit;
}

// Kurs laden
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    echo "Kurs nicht gefunden!";
    exit;
}

// Module & Lektionen laden
$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

foreach ($modules as &$module) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$module['id']]);
    $module['lessons'] = $stmt->fetchAll();
}

// Erste Lektion
$current_lesson = null;
$selected_lesson_id = $_GET['lesson'] ?? null;

if ($selected_lesson_id) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['id'] == $selected_lesson_id) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

if (!$current_lesson && count($modules) > 0 && count($modules[0]['lessons']) > 0) {
    $current_lesson = $modules[0]['lessons'][0];
}

// Video URL parsen
$video_embed = null;
if ($current_lesson && $current_lesson['video_url']) {
    $url = $current_lesson['video_url'];
    
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        $video_embed = "https://player.vimeo.com/video/" . $matches[1];
    }
    elseif (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
        $video_embed = "https://www.youtube.com/embed/" . $matches[1];
    }
    elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
        $video_embed = "https://www.youtube.com/embed/" . $matches[1];
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorschau: <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../customer/styles/course-view.css">
</head>
<body>
    <div class="admin-banner">
        🔍 Admin-Vorschau – So sehen Kunden diesen Kurs
        <a href="dashboard.php?page=templates">← Zurück</a>
    </div>
    <div class="course-view">
        <div class="sidebar">
            <h2><?php echo htmlspecialchars($course['title']); ?></h2>
            <?php foreach ($modules as $module): ?>
                <div class="module">
                    <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                    <?php foreach ($module['lessons'] as $lesson): ?>
                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>" 
                           class="<?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($lesson['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="main-content">
            <?php if ($video_embed): ?>
                <iframe src="<?php echo $video_embed; ?>" frameborder="0" allowfullscreen></iframe>
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($current_lesson['title'] ?? 'Keine Lektion'); ?></h1>
        </div>
    </div>
</body>
</html>
