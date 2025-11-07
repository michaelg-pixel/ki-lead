<?php
/**
 * Admin-Vorschau für Kurse - Mit modernem Kursplayer-Design
 * Version 3.0 - Neuer Dateiname für Cache-Fix
 */
session_start();
require_once '../config/database.php';

// Cache-Busting
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

// Kurs laden
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Kurs nicht gefunden!");
}

// Module laden
$stmt = $pdo->prepare("SELECT * FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lektionen laden
for ($i = 0; $i < count($modules); $i++) {
    $stmt = $pdo->prepare("SELECT * FROM course_lessons WHERE module_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$modules[$i]['id']]);
    $modules[$i]['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Zusätzliche Videos laden
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

// Alle Videos für die aktuelle Lektion sammeln
$current_videos = [];
if ($current_lesson) {
    // Hauptvideo
    if (!empty($current_lesson['video_url'])) {
        $current_videos[] = [
            'id' => 0,
            'video_title' => 'Hauptvideo',
            'video_url' => $current_lesson['video_url'],
            'sort_order' => 0
        ];
    }
    // Zusätzliche Videos
    if (!empty($current_lesson['additional_videos'])) {
        foreach ($current_lesson['additional_videos'] as $video) {
            $current_videos[] = $video;
        }
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
    <title>Admin-Vorschau: <?php echo htmlspecialchars($course['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0a16;
            --bg-secondary: #1a1532;
            --bg-tertiary: #2a2550;
            --border: rgba(168, 85, 247, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a0a0c0;
            --accent: #a855f7;
            --accent-hover: #9333ea;
            --success: #22c55e;
            --warning: #f59e0b;
            --admin-green: #10b981;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }

        /* Admin Banner */
        .admin-banner {
            background: linear-gradient(135deg, var(--admin-green), #059669);
            color: white;
            padding: 12px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
            z-index: 1000;
        }

        .admin-banner a {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .admin-banner a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .player-container {
            display: flex;
            height: calc(100vh - 48px);
            width: 100vw;
        }

        .video-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: var(--bg-primary);
        }

        /* Header */
        .player-header {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-bottom: 2px solid var(--border);
            padding: 20px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .course-info h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .course-info p {
            font-size: 14px;
            color: var(--text-secondary);
        }

        .preview-badge {
            padding: 10px 20px;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--admin-green);
        }

        /* Video Container */
        .video-container {
            flex: 1;
            background: #000;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* VIDEO TABS */
        .video-tabs {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-top: 2px solid var(--border);
            padding: 20px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            flex-wrap: nowrap;
        }

        .video-tab {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 24px;
            background: rgba(168, 85, 247, 0.08);
            border: 2px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            flex-shrink: 0;
        }

        .video-tab:hover {
            background: rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.4);
            transform: translateY(-2px);
        }

        .video-tab.active {
            background: linear-gradient(135deg, #a855f7, #9333ea);
            border-color: #a855f7;
            box-shadow: 0 4px 16px rgba(168, 85, 247, 0.4);
        }

        .video-tab-icon {
            font-size: 18px;
        }

        .video-player {
            flex: 1;
            display: none;
        }

        .video-player.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-player iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .video-placeholder {
            text-align: center;
            color: var(--text-secondary);
        }

        .video-placeholder-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Lesson Info */
        .lesson-info {
            background: var(--bg-secondary);
            border-top: 2px solid var(--border);
            padding: 24px 32px;
        }

        .drip-notice {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid rgba(245, 158, 11, 0.3);
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            color: var(--warning);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .drip-notice strong {
            font-weight: 700;
        }

        .lesson-info h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .lesson-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .lesson-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .lesson-description {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        /* Sidebar */
        .sidebar {
            width: 380px;
            background: var(--bg-secondary);
            border-left: 2px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 2px solid var(--border);
        }

        .sidebar-header h3 {
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-meta {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        .modules-list {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }

        .module {
            margin-bottom: 16px;
            background: var(--bg-tertiary);
            border: 2px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .module-header {
            padding: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s;
        }

        .module-header:hover {
            background: rgba(168, 85, 247, 0.1);
        }

        .module-title {
            font-size: 16px;
            font-weight: 600;
        }

        .module-icon {
            font-size: 20px;
            transition: transform 0.3s;
        }

        .module.open .module-icon {
            transform: rotate(180deg);
        }

        .lessons-list {
            display: none;
            padding: 8px;
            border-top: 1px solid var(--border);
        }

        .module.open .lessons-list {
            display: block;
        }

        .lesson-item {
            padding: 12px 16px;
            margin-bottom: 4px;
            background: rgba(168, 85, 247, 0.05);
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .lesson-item:hover {
            background: rgba(168, 85, 247, 0.15);
            border-color: var(--border);
        }

        .lesson-item.active {
            background: rgba(168, 85, 247, 0.2);
            border-color: var(--accent);
        }

        .lesson-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .lesson-title {
            flex: 1;
            font-size: 14px;
            font-weight: 500;
        }

        .drip-badge {
            padding: 4px 8px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            color: var(--warning);
        }

        .video-count {
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 4px;
        }

        /* Mobile */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                right: -380px;
                top: 48px;
                height: calc(100vh - 48px);
                z-index: 1000;
                transition: right 0.3s;
            }

            .sidebar.open {
                right: 0;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 56px;
                height: 56px;
                background: var(--accent);
                border: none;
                border-radius: 50%;
                color: white;
                font-size: 24px;
                cursor: pointer;
                box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
                z-index: 999;
            }
        }

        @media (max-width: 768px) {
            .player-header {
                padding: 16px;
            }

            .course-info h1 {
                font-size: 18px;
            }

            .lesson-info {
                padding: 20px 16px;
            }

            .sidebar {
                width: 100%;
                right: -100%;
            }

            .video-tabs {
                padding: 16px;
                gap: 8px;
            }

            .video-tab {
                padding: 12px 20px;
                font-size: 14px;
            }
        }

        /* Scrollbar */
        .modules-list::-webkit-scrollbar,
        .video-tabs::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .modules-list::-webkit-scrollbar-track,
        .video-tabs::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .modules-list::-webkit-scrollbar-thumb,
        .video-tabs::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .modules-list::-webkit-scrollbar-thumb:hover,
        .video-tabs::-webkit-scrollbar-thumb:hover {
            background: var(--accent);
        }
    </style>
</head>
<body>
    <div class="admin-banner">
        <span>✅ ADMIN-VORSCHAU (alle Lektionen sichtbar) - <?php echo date('H:i:s', $timestamp); ?></span>
        <a href="dashboard.php?page=templates">← Zurück zum Dashboard</a>
    </div>

    <div class="player-container">
        <!-- Video Area -->
        <div class="video-area">
            <!-- Header -->
            <div class="player-header">
                <div class="header-left">
                    <div class="course-info">
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p><?php echo $current_lesson ? htmlspecialchars($current_lesson['title']) : 'Wähle eine Lektion'; ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="preview-badge">
                        👁️ Admin-Vorschau
                    </div>
                </div>
            </div>

            <!-- Video Container -->
            <div class="video-container">
                <?php if ($current_lesson): ?>
                    <!-- Video Tabs -->
                    <?php if (count($current_videos) > 1): ?>
                        <div class="video-tabs">
                            <?php foreach ($current_videos as $index => $video): ?>
                                <div class="video-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="switchVideo(<?php echo $index; ?>)">
                                    <span class="video-tab-icon">🎥</span>
                                    <span><?php echo htmlspecialchars($video['video_title']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Video Players -->
                    <?php foreach ($current_videos as $index => $video): 
                        $video_url = parseVideoUrl($video['video_url']);
                    ?>
                        <div class="video-player <?php echo $index === 0 ? 'active' : ''; ?>" id="video-<?php echo $index; ?>">
                            <?php if ($video_url): ?>
                                <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                                        allow="autoplay; fullscreen; picture-in-picture" 
                                        allowfullscreen></iframe>
                            <?php else: ?>
                                <div class="video-placeholder">
                                    <div class="video-placeholder-icon">🎥</div>
                                    <p>Ungültige Video-URL</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
                        <div class="video-placeholder">
                            <div class="video-placeholder-icon">🎥</div>
                            <p>Wähle eine Lektion aus der Seitenleiste</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lesson Info -->
            <?php if ($current_lesson): ?>
            <div class="lesson-info">
                <?php if (isset($current_lesson['unlock_after_days']) && $current_lesson['unlock_after_days'] > 0): ?>
                    <div class="drip-notice">
                        <span style="font-size: 20px;">⚠️</span>
                        <span>
                            <strong>Drip-Content:</strong> Diese Lektion wird für Kunden erst nach <?php echo $current_lesson['unlock_after_days']; ?> Tag<?php echo $current_lesson['unlock_after_days'] > 1 ? 'en' : ''; ?> freigeschaltet.
                        </span>
                    </div>
                <?php endif; ?>

                <h2><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <div class="lesson-meta">
                    <?php if ($current_lesson['duration']): ?>
                        <span>⏱️ <?php echo htmlspecialchars($current_lesson['duration']); ?></span>
                    <?php endif; ?>
                    <?php if (count($current_videos) > 1): ?>
                        <span>🎥 <?php echo count($current_videos); ?> Videos</span>
                    <?php endif; ?>
                    <?php if (isset($current_lesson['unlock_after_days']) && $current_lesson['unlock_after_days'] > 0): ?>
                        <span style="color: var(--warning);">🔒 Drip: Tag <?php echo $current_lesson['unlock_after_days']; ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($current_lesson['description']): ?>
                    <p class="lesson-description"><?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>📚 Kursinhalt</h3>
                <div class="sidebar-meta">
                    <?php echo count($modules); ?> Module
                </div>
            </div>
            <div class="modules-list">
                <?php if (count($modules) > 0): ?>
                    <?php foreach ($modules as $module): ?>
                        <div class="module open">
                            <div class="module-header" onclick="toggleModule(this)">
                                <span class="module-title"><?php echo htmlspecialchars($module['title']); ?></span>
                                <span class="module-icon">▼</span>
                            </div>
                            <div class="lessons-list">
                                <?php if (!empty($module['lessons'])): ?>
                                    <?php foreach ($module['lessons'] as $lesson): 
                                        $video_count = (!empty($lesson['video_url']) ? 1 : 0) + (!empty($lesson['additional_videos']) ? count($lesson['additional_videos']) : 0);
                                    ?>
                                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>&t=<?php echo $timestamp; ?>" 
                                           class="lesson-item <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?>">
                                            <span class="lesson-icon">🎥</span>
                                            <div style="flex: 1;">
                                                <div class="lesson-title">
                                                    <?php echo htmlspecialchars($lesson['title']); ?>
                                                    <?php if (isset($lesson['unlock_after_days']) && $lesson['unlock_after_days'] > 0): ?>
                                                        <span class="drip-badge">🕐 Tag <?php echo $lesson['unlock_after_days']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($video_count > 0): ?>
                                                    <div class="video-count">
                                                        🎥 <?php echo $video_count; ?> Video<?php echo $video_count > 1 ? 's' : ''; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="padding: 12px; text-align: center; color: var(--text-secondary); font-size: 14px;">
                                        Keine Lektionen verfügbar
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px 20px; text-align: center;">
                        <span style="font-size: 48px;">📚</span>
                        <p style="margin-top: 16px; color: var(--text-secondary);">Keine Module vorhanden</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <button class="mobile-toggle" onclick="toggleSidebar()" style="display: none;">
        📚
    </button>

    <script>
        // Video Tab Switching
        function switchVideo(index) {
            // Alle Tabs & Videos deaktivieren
            document.querySelectorAll('.video-tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.video-player').forEach(player => player.classList.remove('active'));
            
            // Gewählten Tab & Video aktivieren
            document.querySelectorAll('.video-tab')[index].classList.add('active');
            document.getElementById('video-' + index).classList.add('active');
        }

        function toggleModule(element) {
            element.parentElement.classList.toggle('open');
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function checkMobile() {
            const mobileToggle = document.querySelector('.mobile-toggle');
            if (window.innerWidth <= 1024) {
                mobileToggle.style.display = 'block';
            } else {
                mobileToggle.style.display = 'none';
                document.getElementById('sidebar').classList.remove('open');
            }
        }

        window.addEventListener('resize', checkMobile);
        checkMobile();
    </script>
</body>
</html>