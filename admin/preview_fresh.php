<?php
/**
 * NEUE Admin-Vorschau für Kurse - BUGFIX VERSION
 * Version 2.1 - Fixed PHP Reference Bug
 */
session_start();
require_once '../config/database.php';

// EXTREME Cache-Busting
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = getDBConnection();
$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    die("Keine Kurs-ID angegeben");
}

// Kurs laden - DIREKT aus DB
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Kurs nicht gefunden!");
}

// Module laden - DIREKT aus DB
$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lektionen für jedes Modul laden - WICHTIG: KEIN & bei foreach!
for ($i = 0; $i < count($modules); $i++) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$modules[$i]['id']]);
    $modules[$i]['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Zusätzliche Videos - WICHTIG: KEIN & bei foreach!
    for ($j = 0; $j < count($modules[$i]['lessons']); $j++) {
        $stmt = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$modules[$i]['lessons'][$j]['id']]);
        $modules[$i]['lessons'][$j]['additional_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Erste Lektion auswählen
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

if (!$current_lesson && count($modules) > 0) {
    foreach ($modules as $module) {
        if (count($module['lessons']) > 0) {
            $current_lesson = $module['lessons'][0];
            break;
        }
    }
}

function parseVideoUrl($url) {
    if (empty($url)) return null;
    if (strpos($url, 'player.vimeo.com') !== false || strpos($url, 'youtube.com/embed') !== false) {
        return $url;
    }
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)(?:\?h=([a-zA-Z0-9]+))?/', $url, $matches)) {
        $video_id = $matches[1];
        $hash = isset($matches[2]) ? '?h=' . $matches[2] : '';
        return "https://player.vimeo.com/video/{$video_id}{$hash}";
    }
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return null;
}

$selected_video_index = isset($_GET['video']) ? (int)$_GET['video'] : 0;
$current_video_url = null;

if ($current_lesson) {
    if ($selected_video_index === 0 && $current_lesson['video_url']) {
        $current_video_url = parseVideoUrl($current_lesson['video_url']);
    } elseif ($selected_video_index > 0 && !empty($current_lesson['additional_videos'])) {
        $video_key = $selected_video_index - 1;
        if (isset($current_lesson['additional_videos'][$video_key])) {
            $current_video_url = parseVideoUrl($current_lesson['additional_videos'][$video_key]['video_url']);
        }
    }
    
    if (!$current_video_url && $current_lesson['video_url']) {
        $current_video_url = parseVideoUrl($current_lesson['video_url']);
    } elseif (!$current_video_url && !empty($current_lesson['additional_videos'])) {
        $current_video_url = parseVideoUrl($current_lesson['additional_videos'][0]['video_url']);
    }
}

$timestamp = time();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Vorschau: <?php echo htmlspecialchars($course['title']); ?> [FIXED <?php echo $timestamp; ?>]</title>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #a855f7;
            --bg-primary: #0a0a16;
            --bg-secondary: #1a1532;
            --bg-tertiary: #252041;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --border: rgba(168, 85, 247, 0.2);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        .admin-banner {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }
        
        .admin-banner a {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .course-view {
            display: flex;
            height: calc(100vh - 60px);
        }
        
        .sidebar {
            width: 380px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            color: white;
            margin-bottom: 8px;
        }
        
        .timestamp {
            font-size: 11px;
            color: var(--text-secondary);
            font-family: monospace;
        }
        
        .modules-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .module {
            margin-bottom: 20px;
        }
        
        .module-header {
            padding: 16px;
            background: rgba(168, 85, 247, 0.05);
            border: 1px solid rgba(168, 85, 247, 0.15);
            border-radius: 10px;
            margin-bottom: 8px;
        }
        
        .module-header h3 {
            font-size: 16px;
            color: white;
            font-weight: 700;
        }
        
        .module-empty {
            padding: 16px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 13px;
            font-style: italic;
        }
        
        .lessons {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .lesson-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .lesson-item:hover {
            background: rgba(168, 85, 247, 0.1);
            border-color: var(--border);
        }
        
        .lesson-item.active {
            background: rgba(168, 85, 247, 0.15);
            border-color: var(--primary);
        }
        
        .lesson-icon { font-size: 18px; }
        .lesson-info { flex: 1; }
        .lesson-title { font-size: 14px; color: white; font-weight: 500; }
        .lesson-meta { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }
        
        .drip-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        
        .video-container {
            width: 100%;
            background: #000;
            position: relative;
            padding-top: 56.25%;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .no-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
        }
        
        .lesson-content { padding: 40px; }
        
        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
        }
        
        .lesson-header h1 {
            font-size: 32px;
            color: white;
        }
        
        .preview-badge {
            background: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
    </style>
</head>
<body>
    <div class="admin-banner">
        <span>✅ BUGFIX VERSION - Geladen: <?php echo date('H:i:s', $timestamp); ?></span>
        <a href="dashboard.php?page=templates">← Zurück</a>
    </div>

    <div class="course-view">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <div class="timestamp">Version: FIXED</div>
                <div class="timestamp">Module: <?php echo count($modules); ?></div>
            </div>
            
            <div class="modules-container">
                <?php if (count($modules) > 0): ?>
                    <?php foreach ($modules as $idx => $module): ?>
                        <div class="module">
                            <div class="module-header">
                                <h3>
                                    #<?php echo ($idx + 1); ?> <?php echo htmlspecialchars($module['title']); ?>
                                </h3>
                                <?php if ($module['description']): ?>
                                    <p style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                                        <?php echo htmlspecialchars($module['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($module['lessons']) > 0): ?>
                                <div class="lessons">
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>&t=<?php echo $timestamp; ?>" 
                                           class="lesson-item <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?>">
                                            <div class="lesson-icon">
                                                <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? '▶️' : '⚪'; ?>
                                            </div>
                                            <div class="lesson-info">
                                                <div class="lesson-title">
                                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                                    <?php if (isset($lesson['unlock_after_days']) && $lesson['unlock_after_days'] > 0): ?>
                                                        <span class="drip-badge">🕐 Tag <?php echo $lesson['unlock_after_days']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                $video_count = ($lesson['video_url'] ? 1 : 0) + count($lesson['additional_videos']);
                                                if ($video_count > 0):
                                                ?>
                                                    <div class="lesson-meta">🎥 <?php echo $video_count; ?> Video<?php echo $video_count > 1 ? 's' : ''; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="module-empty">📝 Keine Lektionen</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px 20px; text-align: center;">
                        <span style="font-size: 48px;">📚</span>
                        <p>Keine Module</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="main-content">
            <?php if ($current_lesson): ?>
                <div class="video-container">
                    <?php if ($current_video_url): ?>
                        <iframe src="<?php echo $current_video_url; ?>" 
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <div class="no-video">
                            <span style="font-size: 64px;">🎥</span>
                            <p>Kein Video verfügbar</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="lesson-content">
                    <div class="lesson-header">
                        <h1><?php echo htmlspecialchars($current_lesson['title']); ?></h1>
                        <div class="preview-badge">✅ Bugfix</div>
                    </div>
                    
                    <?php if ($current_lesson['description']): ?>
                        <div style="color: var(--text-secondary); line-height: 1.8;">
                            <?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 40px; text-align: center;">
                    <span style="font-size: 80px;">📚</span>
                    <h2 style="font-size: 28px; margin: 20px 0;">Keine Lektionen</h2>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>