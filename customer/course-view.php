<?php
/**
 * Kursansicht für Kunden - OPTIMIERTE VERSION
 * Video-Player + Lektionen + Fortschritt + AKTIVES Drip-Content
 * Version 4.0 - Kompakter Player, sichtbare Tabs, funktionierendes Drip-Content
 */

session_start();
require_once '../config/database.php';

// CACHE-BUSTING Headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$pdo = getDBConnection();

// Prüfen ob User eingeloggt ist
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']);
$is_customer = $is_logged_in && $_SESSION['role'] === 'customer';
$is_admin = $is_logged_in && $_SESSION['role'] === 'admin';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    if ($is_logged_in) {
        header('Location: dashboard.php?page=kurse');
    } else {
        die('Kurs nicht gefunden');
    }
    exit;
}

// Kurs laden + Zugangs-Check + Access Date für Drip-Content
$access_date = null;
if ($is_logged_in) {
    // Eingeloggte User: Mit Zugangs-Check und granted_at
    $stmt = $pdo->prepare("
        SELECT c.*, ca.access_source, ca.granted_at as access_date
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.id = ? AND (c.is_freebie = TRUE OR ca.id IS NOT NULL)
    ");
    $stmt->execute([$user_id, $course_id]);
    $course = $stmt->fetch();
    
    if ($course && isset($course['access_date']) && $course['access_date']) {
        try {
            $access_date = new DateTime($course['access_date']);
        } catch (Exception $e) {
            // Fallback wenn Datum ungültig
            $access_date = new DateTime();
        }
    } else if ($course) {
        // Fallback: Wenn kein granted_at, dann jetzt
        $access_date = new DateTime();
    }
} else {
    // Leads (nicht eingeloggt): Nur Freebie-Kurse
    $stmt = $pdo->prepare("
        SELECT * FROM courses 
        WHERE id = ? AND is_freebie = TRUE
    ");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
}

if (!$course) {
    if ($is_logged_in) {
        echo "Kein Zugang zu diesem Kurs!";
    } else {
        echo "Dieser Kurs ist nicht öffentlich verfügbar.";
    }
    exit;
}

// PDF-Kurs: Direkt PDF anzeigen
if ($course['type'] === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($course['title']); ?> - PDF</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #0a0a16;
                color: #e5e7eb;
            }
            .pdf-header {
                background: #1a1532;
                border-bottom: 1px solid rgba(168, 85, 247, 0.2);
                padding: 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .pdf-header h1 { font-size: 24px; color: white; }
            .back-link {
                color: #a855f7;
                text-decoration: none;
                font-weight: 600;
            }
            .pdf-container {
                width: 100%;
                height: calc(100vh - 80px);
            }
            .pdf-container embed,
            .pdf-container iframe {
                width: 100%;
                height: 100%;
                border: none;
            }
        </style>
    </head>
    <body>
        <div class="pdf-header">
            <h1>📄 <?php echo htmlspecialchars($course['title']); ?></h1>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php?page=kurse" class="back-link">← Zurück zu meinen Kursen</a>
            <?php endif; ?>
        </div>
        <div class="pdf-container">
            <?php if ($course['pdf_file']): ?>
                <embed src="<?php echo htmlspecialchars($course['pdf_file']); ?>" type="application/pdf">
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #9ca3af;">Keine PDF-Datei verfügbar</p>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Video-Kurs: Module & Lektionen laden
$stmt = $pdo->prepare("
    SELECT * FROM course_modules 
    WHERE course_id = ? 
    ORDER BY sort_order ASC
");
$stmt->execute([$course_id]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lektionen für jedes Modul laden - OHNE & Referenzen!
$now = new DateTime();
for ($i = 0; $i < count($modules); $i++) {
    if ($is_logged_in) {
        $stmt = $pdo->prepare("
            SELECT cl.*, 
                   cp.completed,
                   cp.completed_at
            FROM course_lessons cl
            LEFT JOIN course_progress cp ON cl.id = cp.lesson_id AND cp.user_id = ?
            WHERE cl.module_id = ?
            ORDER BY cl.sort_order ASC
        ");
        $stmt->execute([$user_id, $modules[$i]['id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM course_lessons 
            WHERE module_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$modules[$i]['id']]);
    }
    $modules[$i]['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Drip-Content Check & zusätzliche Videos für jede Lektion
    for ($j = 0; $j < count($modules[$i]['lessons']); $j++) {
        // AKTIVES Drip-Content: Ist die Lektion schon freigeschaltet?
        $lesson_unlocked = true;
        $unlock_in_days = 0;
        
        if ($is_logged_in && $access_date && isset($modules[$i]['lessons'][$j]['unlock_after_days'])) {
            $unlock_after_days = (int)$modules[$i]['lessons'][$j]['unlock_after_days'];
            if ($unlock_after_days > 0) {
                $unlock_date = clone $access_date;
                $unlock_date->modify("+{$unlock_after_days} days");
                
                if ($now < $unlock_date) {
                    $lesson_unlocked = false;
                    $interval = $now->diff($unlock_date);
                    $unlock_in_days = $interval->days + 1;
                }
            }
        }
        
        $modules[$i]['lessons'][$j]['is_locked'] = !$lesson_unlocked;
        $modules[$i]['lessons'][$j]['unlock_in_days'] = $unlock_in_days;
        
        // Zusätzliche Videos laden
        $stmt = $pdo->prepare("
            SELECT * FROM lesson_videos 
            WHERE lesson_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$modules[$i]['lessons'][$j]['id']]);
        $modules[$i]['lessons'][$j]['additional_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Erste zugängliche Lektion oder erste Lektion
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

if (!$current_lesson && $is_logged_in) {
    // Erste nicht-abgeschlossene UND freigeschaltete Lektion
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!$lesson['completed'] && !$lesson['is_locked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

if (!$current_lesson && count($modules) > 0) {
    // Erste Lektion im ersten Modul
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

// Wenn Lektion gesperrt ist, Video blockieren
$video_blocked = $current_lesson && isset($current_lesson['is_locked']) && $current_lesson['is_locked'];

if ($current_lesson && !$video_blocked) {
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
        $current_video_title = "Hauptvideo";
    } elseif (!$current_video_url && !empty($current_lesson['additional_videos'])) {
        $current_video_url = parseVideoUrl($current_lesson['additional_videos'][0]['video_url']);
        $current_video_title = $current_lesson['additional_videos'][0]['video_title'] ?: "Video 1";
    }
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
    <title><?php echo htmlspecialchars($course['title']); ?> - Kurs</title>
    
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
            --success: #4ade80;
            --warning: #fbbf24;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        .course-layout {
            display: flex;
            height: 100vh;
        }
        
        /* MAIN CONTENT (Video links) */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: var(--bg-primary);
        }
        
        .video-section {
            background: #000;
            position: relative;
        }
        
        .video-container {
            width: 100%;
            position: relative;
            padding-top: 42%; /* KOMPAKTER: 16:9 = 56.25%, jetzt 42% */
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .video-locked {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(10, 10, 22, 0.95), rgba(26, 21, 50, 0.95));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            z-index: 10;
        }
        
        .video-locked .lock-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .video-locked h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: white;
        }
        
        .video-locked p {
            color: var(--text-secondary);
            font-size: 15px;
        }
        
        .unlock-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(251, 191, 36, 0.15);
            border: 2px solid rgba(251, 191, 36, 0.3);
            border-radius: 12px;
            color: var(--warning);
            font-weight: 700;
            font-size: 16px;
            margin-top: 20px;
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
            background: #000;
        }
        
        /* VIDEO TABS - IMMER SICHTBAR & PROMINENT */
        .video-tabs {
            background: linear-gradient(180deg, #0a0a16, #1a1532);
            border-top: 2px solid var(--border);
            padding: 20px;
            display: flex;
            gap: 12px;
            overflow-x: auto;
            flex-wrap: wrap;
            min-height: 80px; /* Fixe Höhe für Sichtbarkeit */
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
        }
        
        .video-tab:hover {
            background: rgba(168, 85, 247, 0.15);
            border-color: rgba(168, 85, 247, 0.4);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(168, 85, 247, 0.2);
        }
        
        .video-tab.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-color: var(--primary);
            color: white;
            box-shadow: 0 6px 24px rgba(168, 85, 247, 0.4);
        }
        
        .video-tab-icon {
            font-size: 20px;
        }
        
        /* LESSON CONTENT */
        .lesson-detail {
            padding: 32px;
            max-width: 1200px;
        }
        
        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            gap: 20px;
        }
        
        .lesson-header h1 {
            font-size: 28px;
            color: white;
            line-height: 1.3;
        }
        
        .btn-complete {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--success), #22c55e);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            white-space: nowrap;
        }
        
        .btn-complete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 222, 128, 0.4);
        }
        
        .btn-incomplete {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--border);
        }
        
        .lesson-description {
            font-size: 16px;
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 24px;
        }
        
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            background: rgba(168, 85, 247, 0.1);
            border: 2px solid var(--border);
            color: var(--primary-light);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            background: rgba(168, 85, 247, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(168, 85, 247, 0.3);
        }
        
        /* SIDEBAR (Rechts) */
        .sidebar {
            width: 380px;
            background: var(--bg-secondary);
            border-left: 2px solid var(--border);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 2px solid var(--border);
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.05), rgba(26, 21, 50, 0.8));
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            color: white;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .back-link {
            display: inline-block;
            color: var(--primary-light);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            transition: color 0.2s;
        }
        
        .back-link:hover {
            color: white;
        }
        
        .modules-list {
            padding: 16px;
        }
        
        .module {
            margin-bottom: 20px;
        }
        
        .module-header {
            padding: 16px;
            background: rgba(168, 85, 247, 0.08);
            border: 2px solid rgba(168, 85, 247, 0.2);
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .module-header h3 {
            font-size: 16px;
            color: white;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .module-header p {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .lessons-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .lesson-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            background: var(--bg-tertiary);
            border: 2px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .lesson-item:hover {
            background: rgba(168, 85, 247, 0.1);
            border-color: rgba(168, 85, 247, 0.3);
            transform: translateX(3px);
        }
        
        .lesson-item.active {
            background: rgba(168, 85, 247, 0.2);
            border-color: var(--primary);
        }
        
        .lesson-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .lesson-item.locked:hover {
            transform: none;
            border-color: transparent;
        }
        
        .lesson-item.completed {
            opacity: 0.7;
        }
        
        .lesson-icon {
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .lesson-info {
            flex: 1;
        }
        
        .lesson-title {
            font-size: 14px;
            color: white;
            font-weight: 600;
            margin-bottom: 3px;
        }
        
        .lesson-meta {
            font-size: 11px;
            color: var(--text-muted);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .drip-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            background: rgba(251, 191, 36, 0.15);
            border: 1px solid rgba(251, 191, 36, 0.4);
            color: var(--warning);
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        
        @media (max-width: 1280px) {
            .sidebar { width: 340px; }
            .video-container { padding-top: 45%; }
        }
        
        @media (max-width: 1024px) {
            .course-layout { flex-direction: column; }
            .sidebar { 
                width: 100%;
                border-left: none;
                border-top: 2px solid var(--border);
                max-height: 50vh;
            }
            .video-container { padding-top: 56.25%; }
        }
    </style>
</head>
<body>
    <div class="course-layout">
        <!-- MAIN CONTENT (Links) -->
        <div class="main-content">
            <?php if ($current_lesson): ?>
                <div class="video-section">
                    <div class="video-container">
                        <?php if ($video_blocked): ?>
                            <div class="video-locked">
                                <div class="lock-icon">🔒</div>
                                <h2>Diese Lektion ist noch gesperrt</h2>
                                <p>Diese Lektion wird in <?php echo $current_lesson['unlock_in_days']; ?> Tag<?php echo $current_lesson['unlock_in_days'] > 1 ? 'en' : ''; ?> freigeschaltet</p>
                                <span class="unlock-badge">
                                    🕐 Freischaltung: Tag <?php echo $current_lesson['unlock_after_days']; ?>
                                </span>
                            </div>
                        <?php elseif ($current_video_url): ?>
                            <iframe src="<?php echo $current_video_url; ?>" 
                                    frameborder="0" 
                                    allow="autoplay; fullscreen; picture-in-picture" 
                                    allowfullscreen>
                            </iframe>
                        <?php else: ?>
                            <div class="no-video">
                                <span style="font-size: 64px;">🎥</span>
                                <p style="margin-top: 14px; font-size: 15px;">Kein Video verfügbar</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$video_blocked): ?>
                    <?php
                    $has_main_video = !empty($current_lesson['video_url']);
                    $has_additional_videos = !empty($current_lesson['additional_videos']);
                    $total_videos = ($has_main_video ? 1 : 0) + ($has_additional_videos ? count($current_lesson['additional_videos']) : 0);
                    ?>
                    
                    <?php if ($total_videos > 1): ?>
                    <div class="video-tabs">
                        <?php if ($has_main_video): ?>
                            <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $current_lesson['id']; ?>&video=0&_t=<?php echo $cache_bust; ?>" 
                               class="video-tab <?php echo $selected_video_index === 0 ? 'active' : ''; ?>">
                                <span class="video-tab-icon">🎬</span>
                                <span>Hauptvideo</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($has_additional_videos): ?>
                            <?php foreach ($current_lesson['additional_videos'] as $index => $video): ?>
                                <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $current_lesson['id']; ?>&video=<?php echo $index + 1; ?>&_t=<?php echo $cache_bust; ?>" 
                                   class="video-tab <?php echo $selected_video_index === ($index + 1) ? 'active' : ''; ?>">
                                    <span class="video-tab-icon">📹</span>
                                    <span><?php echo htmlspecialchars($video['video_title'] ?: 'Video ' . ($index + 1)); ?></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="lesson-detail">
                    <div class="lesson-header">
                        <h1><?php echo htmlspecialchars($current_lesson['title']); ?></h1>
                        
                        <?php if ($is_logged_in && !$video_blocked): ?>
                            <?php if (!$current_lesson['completed']): ?>
                                <button onclick="markAsComplete(<?php echo $current_lesson['id']; ?>)" class="btn-complete">
                                    ✓ Als abgeschlossen markieren
                                </button>
                            <?php else: ?>
                                <button onclick="markAsIncomplete(<?php echo $current_lesson['id']; ?>)" class="btn-complete btn-incomplete">
                                    ↺ Als nicht abgeschlossen markieren
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($current_lesson['description']): ?>
                        <div class="lesson-description">
                            <?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($current_lesson['pdf_attachment']): ?>
                        <a href="<?php echo htmlspecialchars($current_lesson['pdf_attachment']); ?>" 
                           target="_blank" 
                           class="btn-download">
                            <span>📄</span>
                            <span>PDF-Arbeitsblatt herunterladen</span>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 60px; text-align: center;">
                    <span style="font-size: 80px;">📚</span>
                    <h2 style="font-size: 28px; margin: 20px 0 10px; color: white;">Keine Lektionen verfügbar</h2>
                    <p style="color: var(--text-secondary); font-size: 16px;">Dieser Kurs enthält noch keine Lektionen.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- SIDEBAR (Rechts) -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php?page=kurse" class="back-link">← Zurück zu meinen Kursen</a>
                <?php endif; ?>
            </div>
            
            <div class="modules-list">
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
                                <div class="lessons-list">
                                    <?php foreach ($module['lessons'] as $lesson): ?>
                                        <?php
                                        $is_current = $current_lesson && $current_lesson['id'] == $lesson['id'];
                                        $is_completed = $is_logged_in && isset($lesson['completed']) && $lesson['completed'];
                                        $is_locked = isset($lesson['is_locked']) && $lesson['is_locked'];
                                        ?>
                                        
                                        <a href="<?php echo !$is_locked ? "?id={$course_id}&lesson={$lesson['id']}&_t={$cache_bust}" : 'javascript:void(0)'; ?>" 
                                           class="lesson-item <?php echo $is_current ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_locked ? 'locked' : ''; ?>"
                                           <?php if ($is_locked): ?>
                                               onclick="alert('🔒 Diese Lektion ist noch gesperrt.\n\nFreischaltung in <?php echo $lesson['unlock_in_days']; ?> Tag<?php echo $lesson['unlock_in_days'] > 1 ? 'en' : ''; ?>!'); return false;"
                                           <?php endif; ?>>
                                            <div class="lesson-icon">
                                                <?php if ($is_locked): ?>
                                                    🔒
                                                <?php elseif ($is_completed): ?>
                                                    ✅
                                                <?php elseif ($is_current): ?>
                                                    ▶️
                                                <?php else: ?>
                                                    ⚪
                                                <?php endif; ?>
                                            </div>
                                            <div class="lesson-info">
                                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                                <div class="lesson-meta">
                                                    <?php
                                                    $video_count = 0;
                                                    if ($lesson['video_url']) $video_count++;
                                                    if (!empty($lesson['additional_videos'])) $video_count += count($lesson['additional_videos']);
                                                    if ($video_count > 0): ?>
                                                        <span>🎥 <?php echo $video_count; ?> Video<?php echo $video_count > 1 ? 's' : ''; ?></span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($lesson['pdf_attachment']): ?>
                                                        <span>📄 PDF</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (isset($lesson['unlock_after_days']) && $lesson['unlock_after_days'] > 0): ?>
                                                        <span class="drip-badge">
                                                            🕐 Tag <?php echo $lesson['unlock_after_days']; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="padding: 14px; text-align: center; color: var(--text-muted); font-size: 12px; font-style: italic;">
                                    📝 Keine Lektionen in diesem Modul
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 50px 20px; text-align: center; color: var(--text-secondary);">
                        <span style="font-size: 56px; display: block; margin-bottom: 14px;">📚</span>
                        <p>Noch keine Module erstellt</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Cache-Busting
        if (performance.navigation.type === 2) {
            location.reload(true);
        }
        
        <?php if ($is_logged_in): ?>
        async function markAsComplete(lessonId) {
            try {
                const response = await fetch('/customer/api/mark-lesson-complete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lesson_id: lessonId, completed: true })
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + data.error);
                }
            } catch (error) {
                alert('Fehler beim Markieren: ' + error.message);
            }
        }
        
        async function markAsIncomplete(lessonId) {
            try {
                const response = await fetch('/customer/api/mark-lesson-complete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ lesson_id: lessonId, completed: false })
                });
                
                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Fehler: ' + data.error);
                }
            } catch (error) {
                alert('Fehler beim Markieren: ' + error.message);
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>