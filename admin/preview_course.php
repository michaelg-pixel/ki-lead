<?php
/**
 * Admin-Vorschau für Kurse - VOLLSTÄNDIGES MODERNES LAYOUT
 * Zeigt, wie der Kurs im Customer-Dashboard aussehen wird
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

// Für PDF-Kurse: Direkt PDF anzeigen
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
                transition: background 0.2s;
            }
            .admin-banner a:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            .pdf-container {
                width: 100%;
                height: calc(100vh - 60px);
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
        <div class="admin-banner">
            <span>🔍 Admin-Vorschau – PDF-Kurs</span>
            <a href="dashboard.php?page=templates">← Zurück</a>
        </div>
        <div class="pdf-container">
            <?php if ($course['pdf_file']): ?>
                <embed src="<?php echo htmlspecialchars($course['pdf_file']); ?>" type="application/pdf">
            <?php else: ?>
                <p style="text-align: center; padding: 40px; color: #9ca3af;">
                    Keine PDF-Datei hochgeladen
                </p>
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
$modules = $stmt->fetchAll();

// Lektionen für jedes Modul laden (ohne Fortschritt in Vorschau)
foreach ($modules as &$module) {
    $stmt = $pdo->prepare("
        SELECT * FROM course_lessons 
        WHERE module_id = ? 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$module['id']]);
    $module['lessons'] = $stmt->fetchAll();
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

if (!$current_lesson && count($modules) > 0 && count($modules[0]['lessons']) > 0) {
    $current_lesson = $modules[0]['lessons'][0];
}

// Video URL parsen
$video_embed = null;
if ($current_lesson && $current_lesson['video_url']) {
    $url = $current_lesson['video_url'];
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        $video_embed = "https://player.vimeo.com/video/" . $matches[1];
    }
    // YouTube
    elseif (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $url, $matches)) {
        $video_embed = "https://www.youtube.com/embed/" . $matches[1];
    }
    elseif (preg_match('/youtu\.be\/([^?]+)/', $url, $matches)) {
        $video_embed = "https://www.youtube.com/embed/" . $matches[1];
    }
}

// Fortschritt simulieren (für Demo-Zwecke)
$total_lessons = 0;
$completed_lessons = 0;
foreach ($modules as $module) {
    $total_lessons += count($module['lessons']);
}
$progress_percentage = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vorschau: <?php echo htmlspecialchars($course['title']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #a855f7;
            --primary-dark: #8b40d1;
            --primary-light: #c084fc;
            --bg-primary: #0a0a16;
            --bg-secondary: #1a1532;
            --bg-tertiary: #252041;
            --bg-card: #2a2550;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --border: rgba(168, 85, 247, 0.2);
            --border-light: rgba(168, 85, 247, 0.1);
            --success: #4ade80;
            --admin-blue: #3b82f6;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        /* Admin Banner */
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
            z-index: 1000;
            position: relative;
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
            letter-spacing: 0.5px;
        }
        
        .admin-banner a {
            color: white;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .admin-banner a:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-1px);
        }
        
        /* Course View */
        .course-view {
            display: flex;
            height: calc(100vh - 60px);
        }
        
        /* Sidebar */
        .sidebar {
            width: 380px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            color: white;
            line-height: 1.4;
            margin-bottom: 16px;
        }
        
        /* Fortschrittsbalken */
        .progress-container {
            margin-top: 16px;
        }
        
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
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #22c55e);
            width: <?php echo $progress_percentage; ?>%;
            transition: width 0.3s ease;
        }
        
        .modules-container {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        
        /* Module */
        .module {
            margin-bottom: 20px;
        }
        
        .module-header {
            padding: 16px;
            background: rgba(168, 85, 247, 0.05);
            border-radius: 10px;
            margin-bottom: 8px;
        }
        
        .module-header h3 {
            font-size: 16px;
            color: white;
            margin-bottom: 6px;
        }
        
        .module-header p {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        /* Lessons */
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
        
        .lesson-icon {
            font-size: 18px;
        }
        
        .lesson-info {
            flex: 1;
        }
        
        .lesson-title {
            font-size: 14px;
            color: white;
            font-weight: 500;
        }
        
        .lesson-meta {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        
        /* Video Container */
        .video-container {
            width: 100%;
            background: #000;
            position: relative;
            padding-top: 56.25%; /* 16:9 */
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
            gap: 16px;
        }
        
        /* Lesson Content */
        .lesson-content {
            padding: 40px;
        }
        
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
        
        .lesson-attachment {
            margin-top: 24px;
        }
        
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            color: var(--primary-light);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-download:hover {
            background: rgba(168, 85, 247, 0.2);
            transform: translateY(-2px);
        }
        
        /* No Lessons */
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
        
        .no-lessons p {
            color: var(--text-secondary);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 60px;
                height: calc(100vh - 60px);
                z-index: 999;
            }
            
            .course-view {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .admin-banner {
                padding: 12px 16px;
                font-size: 13px;
            }
            
            .admin-banner-text {
                gap: 8px;
            }
            
            .admin-badge {
                padding: 3px 8px;
                font-size: 10px;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .lesson-content {
                padding: 24px 16px;
            }
            
            .lesson-header {
                flex-direction: column;
            }
            
            .lesson-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Banner -->
    <div class="admin-banner">
        <div class="admin-banner-text">
            <span class="admin-badge">Vorschau</span>
            <span>🔍 So sehen Kunden diesen Kurs</span>
        </div>
        <a href="dashboard.php?page=templates">← Zurück zur Verwaltung</a>
    </div>

    <!-- Course View -->
    <div class="course-view">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                
                <!-- Fortschrittsanzeige -->
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Fortschritt</span>
                        <span><strong><?php echo $completed_lessons; ?></strong> / <?php echo $total_lessons; ?> Lektionen</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                </div>
            </div>
            
            <div class="modules-container">
                <?php if (count($modules) > 0): ?>
                    <?php foreach ($modules as $module): ?>
                        <div class="module">
                            <div class="module-header">
                                <h3><?php echo htmlspecialchars($module['title']); ?></h3>
                                <?php if ($module['description']): ?>
                                    <p><?php echo htmlspecialchars($module['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="lessons">
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                    <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>" 
                                       class="lesson-item <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?>">
                                        <div class="lesson-icon">
                                            <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? '▶️' : '⚪'; ?>
                                        </div>
                                        <div class="lesson-info">
                                            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                            <?php if ($lesson['pdf_attachment']): ?>
                                                <div class="lesson-meta">📄 PDF verfügbar</div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
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
        
        <!-- Main Content -->
        <div class="main-content">
            <?php if ($current_lesson): ?>
                <!-- Video Player -->
                <div class="video-container">
                    <?php if ($video_embed): ?>
                        <iframe src="<?php echo $video_embed; ?>" 
                                frameborder="0" 
                                allow="autoplay; fullscreen; picture-in-picture" 
                                allowfullscreen>
                        </iframe>
                    <?php else: ?>
                        <div class="no-video">
                            <span style="font-size: 64px;">🎥</span>
                            <p>Kein Video verknüpft</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Lesson Info -->
                <div class="lesson-content">
                    <div class="lesson-header">
                        <h1><?php echo htmlspecialchars($current_lesson['title']); ?></h1>
                        <div class="preview-badge">
                            👁️ Vorschau-Modus
                        </div>
                    </div>
                    
                    <?php if ($current_lesson['description']): ?>
                        <div class="lesson-description">
                            <?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($current_lesson['pdf_attachment']): ?>
                        <div class="lesson-attachment">
                            <a href="<?php echo htmlspecialchars($current_lesson['pdf_attachment']); ?>" 
                               target="_blank" 
                               class="btn-download">
                                📄 PDF-Arbeitsblatt herunterladen
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="no-lessons">
                    <span style="font-size: 80px;">📚</span>
                    <h2>Keine Lektionen vorhanden</h2>
                    <p>Füge dem Kurs Lektionen hinzu, um sie hier anzuzeigen.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>