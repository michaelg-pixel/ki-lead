<?php
/**
 * Admin-Vorschau für Kurse - KOMPLETT NEU ERSTELLT
 * Zeigt, wie der Kurs im Customer-Dashboard aussehen wird
 * Version 3.0 - 2025-11-07
 */
session_start();
require_once '../config/database.php';

// Cache-Busting Headers
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
    header('Location: dashboard.php?page=templates');
    exit;
}

// Kurs laden
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Kurs nicht gefunden!");
}

// PDF-Kurs: Direkt PDF anzeigen
if ($course['type'] === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vorschau: <?php echo htmlspecialchars($course['title']); ?> (PDF)</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #0a0a16;
                color: #e5e7eb;
            }
            .admin-banner {
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: white;
                padding: 16px 24px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-weight: 600;
                box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
            }
            .admin-banner a {
                color: white;
                text-decoration: none;
                background: rgba(255, 255, 255, 0.2);
                padding: 8px 16px;
                border-radius: 6px;
            }
            .pdf-container {
                width: 100%;
                height: calc(100vh - 60px);
            }
            .pdf-container embed { width: 100%; height: 100%; border: none; }
        </style>
    </head>
    <body>
        <div class="admin-banner">
            <span>🔍 Admin-Vorschau – PDF-Kurs</span>
            <a href="dashboard.php?page=templates">← Zurück</a>
        </div>
        <div class="pdf-container">
            <?php if ($course['pdf_file']): ?>
                <embed src="<?php echo htmlspecialchars($course['pdf_file']); ?>" type="application/pdf">
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #9ca3af;">Keine PDF-Datei hochgeladen</p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Video-Kurs: Module & Lektionen laden
$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lektionen für jedes Modul laden (ALLE Lektionen in Admin-Vorschau)
foreach ($modules as &$module) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$module['id']]);
    $module['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Zusätzliche Videos für jede Lektion laden
    foreach ($module['lessons'] as &$lesson) {
        $stmt = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order ASC");
        $stmt->execute([$lesson['id']]);
        $lesson['additional_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Erste Lektion oder ausgewählte Lektion
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

// Video URL parsen
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

// Aktuelles Video auswählen
$selected_video_index = isset($_GET['video']) ? (int)$_GET['video'] : 0;
$current_video_url = null;
$current_video_title = null;

if ($current_lesson) {
    if ($selected_video_index === 0 && $current_lesson['video_url']) {
        $current_video_url = parseVideoUrl($current_lesson['video_url']);
        $current_video_title = "Hauptvideo";
    } elseif ($selected_video_index > 0 && !empty($current_lesson['additional_videos'])) {
        $video_key = $selected_video_index - 1;
        if (isset($current_lesson['additional_videos'][$video_key])) {
            $additional_video = $current_lesson['additional_videos'][$video_key];
            $current_video_url = parseVideoUrl($additional_video['video_url']);
            $current_video_title = $additional_video['video_title'] ?: "Video " . $selected_video_index;
        }
    }
    
    if (!$current_video_url && $current_lesson['video_url']) {
        $current_video_url = parseVideoUrl($current_lesson['video_url']);
    } elseif (!$current_video_url && !empty($current_lesson['additional_videos'])) {
        $current_video_url = parseVideoUrl($current_lesson['additional_videos'][0]['video_url']);
    }
}

$total_lessons = 0;
foreach ($modules as $module) {
    $total_lessons += count($module['lessons']);
}

$cache_bust = time();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Vorschau: <?php echo htmlspecialchars($course['title']); ?></title>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #a855f7;
            --primary-dark: #8b40d1;
            --primary-light: #c084fc;
            --bg-primary: #0a0a16;
            --bg-secondary: #1a1532;
            --bg-tertiary: #252041;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --border: rgba(168, 85, 247, 0.2);
            --admin-blue: #3b82f6;
            --warning: #f59e0b;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        .admin-banner {
            background: linear-gradient(135deg, var(--admin-blue), #2563eb);
            color: white;
            padding: 14px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        }
        
        .admin-banner-text {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .admin-badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
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
            margin-bottom: 16px;
        }
        
        .progress-container { margin-top: 16px; }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .progress-bar {
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .modules-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        .module { margin-bottom: 20px; }
        
        .module-header {
            padding: 16px;
            background: rgba(168, 85, 247, 0.05);
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid rgba(168, 85, 247, 0.15);
        }
        
        .module-header h3 {
            font-size: 16px;
            color: white;
            margin-bottom: 6px;
            font-weight: 700;
        }
        
        .module-header p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .module-empty {
            padding: 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 13px;
            font-style: italic;
        }
        
        .drip-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--warning);
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            margin-left: 8px;
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
        .lesson-meta { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
        
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
        
        .video-switcher {
            background: rgba(26, 26, 46, 0.95);
            border-top: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
        }
        
        .video-switch-btn {
            padding: 10px 20px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: 8px;
            white-space: nowrap;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .video-switch-btn:hover {
            background: rgba(168, 85, 247, 0.2);
            transform: translateY(-2px);
        }
        
        .video-switch-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: white;
        }
        
        .lesson-content { padding: 40px; }
        
        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 20px;
        }
        
        .lesson-header h1 {
            font-size: 32px;
            color: white;
            line-height: 1.3;
        }
        
        .preview-badge {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        
        .lesson-description {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.8;
            margin-bottom: 24px;
        }
        
        .no-lessons {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 40px;
        }
        
        .no-lessons h2 {
            font-size: 28px;
            color: white;
            margin: 20px 0 12px;
        }
    </style>
</head>
<body>
    <div class="admin-banner">
        <div class="admin-banner-text">
            <span class="admin-badge">Vorschau</span>
            <span>🔍 Admin-Vorschau (alle Lektionen sichtbar, inkl. zeitverzögerte)</span>
        </div>
        <a href="dashboard.php?page=templates">← Zurück zur Verwaltung</a>
    </div>

    <div class="course-view">
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Fortschritt</span>
                        <span><strong>0</strong> / <?php echo $total_lessons; ?> Lektionen</span>
                    </div>
                    <div class="progress-bar">
                        <div style="height: 100%; background: linear-gradient(90deg, #4ade80, #22c55e); width: 0%;"></div>
                    </div>
                </div>
            </div>
            
            <div class="modules-container">
                <?php if (count($modules) > 0): ?>
                    <?php foreach ($modules as $module_index => $module): ?>
                        <div class="module">
                            <div class="module-header">
                                <h3>#<?php echo ($module_index + 1); ?> <?php echo htmlspecialchars($module['title']); ?></h3>
                                <?php if ($module['description']): ?>
                                    <p><?php echo htmlspecialchars($module['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (count($module['lessons']) > 0): ?>
                                <div class="lessons">
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>&_t=<?php echo $cache_bust; ?>" 
                                           class="lesson-item <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?>">
                                            <div class="lesson-icon">
                                                <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? '▶️' : '⚪'; ?>
                                            </div>
                                            <div class="lesson-info">
                                                <div class="lesson-title">
                                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                                    <?php if ($lesson['unlock_after_days'] !== null && $lesson['unlock_after_days'] > 0): ?>
                                                        <span class="drip-badge">🕐 Tag <?php echo $lesson['unlock_after_days']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php
                                                $video_count = 0;
                                                if ($lesson['video_url']) $video_count++;
                                                if (!empty($lesson['additional_videos'])) $video_count += count($lesson['additional_videos']);
                                                ?>
                                                <?php if ($video_count > 0): ?>
                                                    <div class="lesson-meta">🎥 <?php echo $video_count; ?> Video<?php echo $video_count > 1 ? 's' : ''; ?></div>
                                                <?php endif; ?>
                                                <?php if ($lesson['pdf_attachment']): ?>
                                                    <div class="lesson-meta">📄 PDF verfügbar</div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="module-empty">📝 Noch keine Lektionen in diesem Modul</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px 20px; text-align: center; color: var(--text-secondary);">
                        <span style="font-size: 48px; display: block; margin-bottom: 16px;">📚</span>
                        <p>Noch keine Module erstellt</p>
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
                
                <?php
                $has_main_video = !empty($current_lesson['video_url']);
                $has_additional_videos = !empty($current_lesson['additional_videos']);
                $total_videos = ($has_main_video ? 1 : 0) + ($has_additional_videos ? count($current_lesson['additional_videos']) : 0);
                ?>
                
                <?php if ($total_videos > 1): ?>
                <div class="video-switcher">
                    <?php if ($has_main_video): ?>
                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $current_lesson['id']; ?>&video=0&_t=<?php echo $cache_bust; ?>" 
                           class="video-switch-btn <?php echo $selected_video_index === 0 ? 'active' : ''; ?>">
                            🎬 Hauptvideo
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($has_additional_videos): ?>
                        <?php foreach ($current_lesson['additional_videos'] as $index => $video): ?>
                            <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $current_lesson['id']; ?>&video=<?php echo $index + 1; ?>&_t=<?php echo $cache_bust; ?>" 
                               class="video-switch-btn <?php echo $selected_video_index === ($index + 1) ? 'active' : ''; ?>">
                                📹 <?php echo htmlspecialchars($video['video_title'] ?: 'Video ' . ($index + 1)); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="lesson-content">
                    <div class="lesson-header">
                        <h1>
                            <?php echo htmlspecialchars($current_lesson['title']); ?>
                            <?php if ($current_lesson['unlock_after_days'] !== null && $current_lesson['unlock_after_days'] > 0): ?>
                                <span class="drip-badge" style="font-size: 14px; padding: 4px 12px;">
                                    🕐 Wird nach <?php echo $current_lesson['unlock_after_days']; ?> Tag<?php echo $current_lesson['unlock_after_days'] > 1 ? 'en' : ''; ?> freigeschaltet
                                </span>
                            <?php endif; ?>
                        </h1>
                        <div class="preview-badge">👁️ Admin-Vorschau</div>
                    </div>
                    
                    <?php if ($current_lesson['description']): ?>
                        <div class="lesson-description">
                            <?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($current_lesson['pdf_attachment']): ?>
                        <div style="margin-top: 24px;">
                            <a href="<?php echo htmlspecialchars($current_lesson['pdf_attachment']); ?>" 
                               target="_blank" 
                               style="display: inline-flex; align-items: center; gap: 8px; padding: 14px 24px; background: rgba(168, 85, 247, 0.1); border: 1px solid var(--border); color: var(--primary-light); text-decoration: none; border-radius: 10px; font-weight: 600;">
                                📄 PDF-Arbeitsblatt herunterladen
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-lessons">
                    <span style="font-size: 80px;">📚</span>
                    <h2>Keine Lektionen vorhanden</h2>
                    <p style="color: var(--text-secondary);">Füge dem Kurs Lektionen hinzu, um sie hier anzuzeigen.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
    if (performance.navigation.type === 2) {
        location.reload(true);
    }
    </script>
</body>
</html>