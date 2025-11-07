<?php
/**
 * Course Player - Videokurs Ansicht mit Multi-Video & Drip-Content Support
 * Features: Tabs für mehrere Videos, zeitbasierte Freischaltung, Fortschritt-Tracking
 * ÖFFENTLICHER ZUGANG: Wenn via Freebie-Link mit access_token zugegriffen wird
 */

session_start();

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$access_token = isset($_GET['access_token']) ? trim($_GET['access_token']) : '';
$freebie_id = isset($_GET['freebie_id']) ? (int)$_GET['freebie_id'] : 0;

if ($course_id <= 0) {
    die('Ungültige Kurs-ID');
}

// ÖFFENTLICHER ZUGANG: Token validieren
$is_public_access = false;
$customer_id = null;

if (!empty($access_token) && $freebie_id > 0) {
    // Token validieren: MD5(freebie_id_course_id_freebie_access)
    $expected_token = md5($freebie_id . '_' . $course_id . '_freebie_access');
    
    if ($access_token === $expected_token) {
        $is_public_access = true;
        // Für öffentlichen Zugang keine user_id erforderlich
        $customer_id = 0;
    }
}

// REGULÄRER ZUGANG: Login-Check (nur wenn nicht öffentlicher Zugang)
if (!$is_public_access) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        header('Location: /public/login.php');
        exit;
    }
    $customer_id = $_SESSION['user_id'];
}

// Kurs laden mit Zugriffsprüfung (nur für eingeloggte Benutzer)
if (!$is_public_access) {
    $stmt = $pdo->prepare("
        SELECT c.*, ca.id as has_access
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.id = ?
        AND (c.is_freebie = 1 OR ca.id IS NOT NULL)
    ");
    $stmt->execute([$customer_id, $course_id]);
    $course = $stmt->fetch();
} else {
    // Öffentlicher Zugang: Kurs direkt laden
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
}

if (!$course) {
    die('Kurs nicht gefunden oder kein Zugriff');
}

// Enrollment-Datum für Drip-Content prüfen/erstellen (nur für eingeloggte Benutzer)
$enrolled_at = new DateTime();
$days_enrolled = 9999; // Öffentlicher Zugang: Alle Lektionen freischalten

if (!$is_public_access) {
    $stmt = $pdo->prepare("
        SELECT enrolled_at FROM course_enrollments 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->execute([$customer_id, $course_id]);
    $enrollment = $stmt->fetch();

    if (!$enrollment) {
        // Erste Einschreibung - Datum setzen
        $stmt = $pdo->prepare("
            INSERT INTO course_enrollments (user_id, course_id, enrolled_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$customer_id, $course_id]);
        $enrolled_at = new DateTime();
    } else {
        $enrolled_at = new DateTime($enrollment['enrolled_at']);
    }

    // Tage seit Einschreibung
    $now = new DateTime();
    $days_enrolled = $now->diff($enrolled_at)->days;
}

// Module mit Lektionen laden
$stmt = $pdo->prepare("
    SELECT 
        cm.*,
        (SELECT COUNT(*) FROM course_lessons WHERE module_id = cm.id) as lesson_count
    FROM course_modules cm
    WHERE cm.course_id = ?
    ORDER BY cm.sort_order ASC
");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll();

// Alle Lektionen mit Fortschritt, Drip-Status und Videos laden
$lessons = [];
foreach ($modules as $module) {
    if (!$is_public_access) {
        $stmt = $pdo->prepare("
            SELECT 
                cl.*,
                cp.completed,
                cp.completed_at,
                CASE 
                    WHEN cl.unlock_after_days IS NULL THEN 1
                    WHEN cl.unlock_after_days <= ? THEN 1
                    ELSE 0
                END as is_unlocked,
                CASE 
                    WHEN cl.unlock_after_days IS NOT NULL AND cl.unlock_after_days > ?
                    THEN (cl.unlock_after_days - ?)
                    ELSE 0
                END as days_until_unlock
            FROM course_lessons cl
            LEFT JOIN course_progress cp ON cl.id = cp.lesson_id AND cp.user_id = ?
            WHERE cl.module_id = ?
            ORDER BY cl.sort_order ASC
        ");
        $stmt->execute([$days_enrolled, $days_enrolled, $days_enrolled, $customer_id, $module['id']]);
    } else {
        // Öffentlicher Zugang: Alle Lektionen freischalten, kein Fortschritt
        $stmt = $pdo->prepare("
            SELECT 
                cl.*,
                0 as completed,
                NULL as completed_at,
                1 as is_unlocked,
                0 as days_until_unlock
            FROM course_lessons cl
            WHERE cl.module_id = ?
            ORDER BY cl.sort_order ASC
        ");
        $stmt->execute([$module['id']]);
    }
    $lessons[$module['id']] = $stmt->fetchAll();
}

// Erste verfügbare Lektion finden
$selected_lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : null;
if (!$selected_lesson_id) {
    foreach ($modules as $module) {
        if (!empty($lessons[$module['id']])) {
            foreach ($lessons[$module['id']] as $lesson) {
                if ($lesson['is_unlocked']) {
                    $selected_lesson_id = $lesson['id'];
                    break 2;
                }
            }
        }
    }
}

// Aktuelle Lektion laden
$current_lesson = null;
$current_videos = [];
if ($selected_lesson_id) {
    if (!$is_public_access) {
        $stmt = $pdo->prepare("
            SELECT 
                cl.*,
                cm.title as module_title,
                cp.completed,
                CASE 
                    WHEN cl.unlock_after_days IS NULL THEN 1
                    WHEN cl.unlock_after_days <= ? THEN 1
                    ELSE 0
                END as is_unlocked,
                CASE 
                    WHEN cl.unlock_after_days IS NOT NULL AND cl.unlock_after_days > ?
                    THEN (cl.unlock_after_days - ?)
                    ELSE 0
                END as days_until_unlock
            FROM course_lessons cl
            JOIN course_modules cm ON cl.module_id = cm.id
            LEFT JOIN course_progress cp ON cl.id = cp.lesson_id AND cp.user_id = ?
            WHERE cl.id = ?
        ");
        $stmt->execute([$days_enrolled, $days_enrolled, $days_enrolled, $customer_id, $selected_lesson_id]);
    } else {
        // Öffentlicher Zugang
        $stmt = $pdo->prepare("
            SELECT 
                cl.*,
                cm.title as module_title,
                0 as completed,
                1 as is_unlocked,
                0 as days_until_unlock
            FROM course_lessons cl
            JOIN course_modules cm ON cl.module_id = cm.id
            WHERE cl.id = ?
        ");
        $stmt->execute([$selected_lesson_id]);
    }
    $current_lesson = $stmt->fetch();
    
    // Videos für diese Lektion laden
    if ($current_lesson) {
        $stmt = $pdo->prepare("
            SELECT * FROM lesson_videos 
            WHERE lesson_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$selected_lesson_id]);
        $current_videos = $stmt->fetchAll();
        
        // Fallback: Wenn keine Videos in lesson_videos, aber video_url vorhanden
        if (empty($current_videos) && !empty($current_lesson['video_url'])) {
            $current_videos = [[
                'id' => 0,
                'video_title' => 'Hauptvideo',
                'video_url' => $current_lesson['video_url'],
                'sort_order' => 1
            ]];
        }
    }
}

// URL für Lesson-Links generieren (Token mitgeben wenn öffentlicher Zugang)
$lesson_url_params = '';
if ($is_public_access) {
    $lesson_url_params = '&access_token=' . urlencode($access_token) . '&freebie_id=' . $freebie_id;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - KI Leadsystem</title>
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
            --locked: #ef4444;
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

        .player-container {
            display: flex;
            height: 100vh;
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

        .back-button {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(168, 85, 247, 0.1);
            border: 2px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: rgba(168, 85, 247, 0.2);
            border-color: var(--accent);
            transform: translateX(-4px);
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

        .progress-badge {
            padding: 10px 20px;
            background: rgba(34, 197, 94, 0.1);
            border: 2px solid rgba(34, 197, 94, 0.3);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: var(--success);
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

        /* Locked Overlay */
        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .locked-icon {
            font-size: 120px;
            margin-bottom: 32px;
            animation: shake 2s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }

        .locked-title {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin-bottom: 16px;
        }

        .locked-text {
            font-size: 18px;
            color: var(--text-secondary);
            text-align: center;
            max-width: 500px;
            line-height: 1.6;
        }

        .unlock-countdown {
            margin-top: 24px;
            padding: 16px 32px;
            background: rgba(239, 68, 68, 0.2);
            border: 2px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #ef4444;
        }

        /* Lesson Info */
        .lesson-info {
            background: var(--bg-secondary);
            border-top: 2px solid var(--border);
            padding: 24px 32px;
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

        .complete-button {
            margin-top: 16px;
            padding: 12px 32px;
            background: linear-gradient(135deg, var(--success), #16a34a);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .complete-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(34, 197, 94, 0.4);
        }

        .complete-button.completed {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
            cursor: default;
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

        .lesson-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .lesson-item.completed {
            opacity: 0.7;
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

        .lesson-status {
            font-size: 18px;
        }

        /* Mobile */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                right: -380px;
                top: 0;
                height: 100vh;
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
    <div class="player-container">
        <!-- Video Area -->
        <div class="video-area">
            <!-- Header -->
            <div class="player-header">
                <div class="header-left">
                    <?php if (!$is_public_access): ?>
                    <a href="/customer/dashboard.php?page=kurse" class="back-button">
                        ← Zurück
                    </a>
                    <?php endif; ?>
                    <div class="course-info">
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p><?php echo htmlspecialchars($current_lesson['module_title'] ?? 'Wähle eine Lektion'); ?></p>
                    </div>
                </div>
                <?php if (!$is_public_access): ?>
                <div class="header-right">
                    <?php
                    $total_lessons = 0;
                    $completed_lessons = 0;
                    foreach ($lessons as $module_lessons) {
                        foreach ($module_lessons as $lesson) {
                            $total_lessons++;
                            if ($lesson['completed']) $completed_lessons++;
                        }
                    }
                    $progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
                    ?>
                    <div class="progress-badge">
                        <?php echo $completed_lessons; ?> / <?php echo $total_lessons; ?> (<?php echo $progress_percent; ?>%)
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Video Container -->
            <div class="video-container">
                <?php if ($current_lesson && $current_lesson['is_unlocked']): ?>
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
                        $video_url = $video['video_url'];
                        
                        // YouTube Embed
                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                            if (!empty($matches[1])) {
                                $video_url = 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=0&rel=0';
                            }
                        }
                        // Vimeo Embed
                        elseif (strpos($video_url, 'vimeo.com') !== false) {
                            preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
                            if (!empty($matches[1])) {
                                $video_url = 'https://player.vimeo.com/video/' . $matches[1];
                            }
                        }
                    ?>
                        <div class="video-player <?php echo $index === 0 ? 'active' : ''; ?>" id="video-<?php echo $index; ?>">
                            <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                                    allow="autoplay; fullscreen; picture-in-picture" 
                                    allowfullscreen></iframe>
                        </div>
                    <?php endforeach; ?>
                    
                <?php elseif ($current_lesson && !$current_lesson['is_unlocked']): ?>
                    <!-- Locked Overlay -->
                    <div class="locked-overlay">
                        <div class="locked-icon">🔒</div>
                        <h2 class="locked-title">Diese Lektion ist noch gesperrt</h2>
                        <p class="locked-text">
                            Diese Lektion wird automatisch freigeschaltet, sobald du lange genug im Kurs bist.
                        </p>
                        <div class="unlock-countdown">
                            Freischaltung in <?php echo $current_lesson['days_until_unlock']; ?> Tag<?php echo $current_lesson['days_until_unlock'] != 1 ? 'en' : ''; ?>
                        </div>
                    </div>
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
            <?php if ($current_lesson && $current_lesson['is_unlocked']): ?>
            <div class="lesson-info">
                <h2><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <div class="lesson-meta">
                    <span>📂 <?php echo htmlspecialchars($current_lesson['module_title']); ?></span>
                    <?php if ($current_lesson['duration']): ?>
                        <span>⏱️ <?php echo htmlspecialchars($current_lesson['duration']); ?></span>
                    <?php endif; ?>
                    <?php if (count($current_videos) > 1): ?>
                        <span>🎥 <?php echo count($current_videos); ?> Videos</span>
                    <?php endif; ?>
                    <?php if (!$is_public_access && $current_lesson['completed']): ?>
                        <span style="color: var(--success);">✅ Abgeschlossen</span>
                    <?php endif; ?>
                </div>
                <?php if ($current_lesson['description']): ?>
                    <p class="lesson-description"><?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?></p>
                <?php endif; ?>
                
                <?php if (!$is_public_access): ?>
                <button class="complete-button <?php echo $current_lesson['completed'] ? 'completed' : ''; ?>"
                        onclick="markAsComplete(<?php echo $current_lesson['id']; ?>)"
                        <?php echo $current_lesson['completed'] ? 'disabled' : ''; ?>>
                    <?php echo $current_lesson['completed'] ? '✅ Lektion abgeschlossen' : '✓ Als abgeschlossen markieren'; ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>📚 Kursinhalt</h3>
            </div>
            <div class="modules-list">
                <?php foreach ($modules as $module): ?>
                    <div class="module open">
                        <div class="module-header" onclick="toggleModule(this)">
                            <span class="module-title"><?php echo htmlspecialchars($module['title']); ?></span>
                            <span class="module-icon">▼</span>
                        </div>
                        <div class="lessons-list">
                            <?php if (!empty($lessons[$module['id']])): ?>
                                <?php foreach ($lessons[$module['id']] as $lesson): ?>
                                    <?php if ($lesson['is_unlocked']): ?>
                                        <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id'] . $lesson_url_params; ?>" 
                                           class="lesson-item <?php echo $lesson['id'] == $selected_lesson_id ? 'active' : ''; ?> <?php echo !$is_public_access && $lesson['completed'] ? 'completed' : ''; ?>">
                                            <span class="lesson-icon">🎥</span>
                                            <span class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                            <?php if (!$is_public_access && $lesson['completed']): ?>
                                                <span class="lesson-status">✅</span>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <div class="lesson-item locked">
                                            <span class="lesson-icon">🔒</span>
                                            <span class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                            <span class="lesson-status" title="Noch <?php echo $lesson['days_until_unlock']; ?> Tag(e)">
                                                <?php echo $lesson['days_until_unlock']; ?>d
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 12px; text-align: center; color: var(--text-secondary); font-size: 14px;">
                                    Keine Lektionen verfügbar
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
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

        <?php if (!$is_public_access): ?>
        function markAsComplete(lessonId) {
            fetch('/api/mark-lesson-complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ lesson_id: lessonId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }
        <?php endif; ?>

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