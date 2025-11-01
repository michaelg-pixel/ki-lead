<?php
/**
 * Kursansicht für Kunden UND Leads
 * Video-Player + Lektionen + Fortschritt
 * Leads (nicht eingeloggt) können Freebie-Kurse sehen
 */

session_start();
require_once '../config/database.php';

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

// Kurs laden + Zugangs-Check
if ($is_logged_in) {
    // Eingeloggte User: Mit Zugangs-Check
    $stmt = $pdo->prepare("
        SELECT c.*, ca.access_source
        FROM courses c
        LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
        WHERE c.id = ? AND (c.is_freebie = TRUE OR ca.id IS NOT NULL)
    ");
    $stmt->execute([$user_id, $course_id]);
} else {
    // Leads (nicht eingeloggt): Nur Freebie-Kurse
    $stmt = $pdo->prepare("
        SELECT * FROM courses 
        WHERE id = ? AND is_freebie = TRUE
    ");
    $stmt->execute([$course_id]);
}

$course = $stmt->fetch();

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
            .pdf-header h1 {
                font-size: 24px;
                color: white;
            }
            .back-link {
                color: #a855f7;
                text-decoration: none;
                font-weight: 600;
            }
            .back-link:hover {
                color: #c084fc;
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
                <p style="text-align: center; padding: 40px; color: #9ca3af;">
                    Keine PDF-Datei verfügbar
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

// Lektionen für jedes Modul laden mit Fortschritt (nur wenn eingeloggt)
foreach ($modules as &$module) {
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
        $stmt->execute([$user_id, $module['id']]);
    } else {
        // Leads: Ohne Fortschritt
        $stmt = $pdo->prepare("
            SELECT * FROM course_lessons 
            WHERE module_id = ?
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$module['id']]);
    }
    $module['lessons'] = $stmt->fetchAll();
}

// Erste nicht-abgeschlossene Lektion oder erste Lektion
$current_lesson = null;
$selected_lesson_id = $_GET['lesson'] ?? null;

if ($selected_lesson_id) {
    // Ausgewählte Lektion suchen
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
    // Für eingeloggte User: Erste nicht-abgeschlossene Lektion finden
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!$lesson['completed']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

if (!$current_lesson && count($modules) > 0 && count($modules[0]['lessons']) > 0) {
    // Wenn alle abgeschlossen oder Lead: erste Lektion
    $current_lesson = $modules[0]['lessons'][0];
}

// Video URL parsen
function parseVideoUrl($url) {
    if (empty($url)) {
        return null;
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)(?:\?h=([a-zA-Z0-9]+))?/', $url, $matches)) {
        $video_id = $matches[1];
        $hash = isset($matches[2]) ? '?h=' . $matches[2] : '';
        return "https://player.vimeo.com/video/{$video_id}{$hash}";
    }
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    
    return null;
}

$video_embed = $current_lesson ? parseVideoUrl($current_lesson['video_url']) : null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Kurs</title>
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
            --success: #4ade80;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow: hidden;
        }
        
        .course-view {
            display: flex;
            height: 100vh;
        }
        
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            color: white;
            line-height: 1.4;
            flex: 1;
        }
        
        .sidebar-toggle {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--primary-light);
            font-size: 18px;
            transition: all 0.2s;
            margin-left: 12px;
        }
        
        .sidebar-toggle:hover {
            background: rgba(168, 85, 247, 0.2);
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
        
        .lesson-item.completed {
            opacity: 0.7;
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
        
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
        }
        
        .btn-back {
            display: block;
            width: 100%;
            padding: 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            color: var(--text-primary);
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-back:hover {
            background: rgba(168, 85, 247, 0.1);
            border-color: var(--primary);
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
        
        .lesson-actions {
            flex-shrink: 0;
        }
        
        .btn-complete,
        .btn-incomplete {
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--success), #22c55e);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        
        .btn-incomplete {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border);
        }
        
        .btn-complete:hover,
        .btn-incomplete:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 222, 128, 0.3);
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
            margin-bottom: 32px;
        }
        
        .btn-primary {
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 1000;
            }
            
            .lesson-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="course-view">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <span id="toggleIcon">←</span>
                </button>
            </div>
            
            <div class="modules-container">
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
                                   class="lesson-item <?php echo $current_lesson && $current_lesson['id'] == $lesson['id'] ? 'active' : ''; ?> <?php echo ($is_logged_in && isset($lesson['completed']) && $lesson['completed']) ? 'completed' : ''; ?>">
                                    <div class="lesson-icon">
                                        <?php if ($is_logged_in && isset($lesson['completed']) && $lesson['completed']): ?>
                                            ✅
                                        <?php elseif ($current_lesson && $current_lesson['id'] == $lesson['id']): ?>
                                            ▶️
                                        <?php else: ?>
                                            ⚪
                                        <?php endif; ?>
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
            </div>
            
            <?php if ($is_logged_in): ?>
            <div class="sidebar-footer">
                <a href="dashboard.php?page=kurse" class="btn-back">← Zurück zu meinen Kursen</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="main-content">
            <?php if ($current_lesson): ?>
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
                            <p>Kein Video verfügbar</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="lesson-content">
                    <div class="lesson-header">
                        <h1><?php echo htmlspecialchars($current_lesson['title']); ?></h1>
                        
                        <?php if ($is_logged_in): ?>
                        <div class="lesson-actions">
                            <?php if (!$current_lesson['completed']): ?>
                                <button onclick="markAsComplete(<?php echo $current_lesson['id']; ?>)" 
                                        class="btn-complete">
                                    ✓ Als abgeschlossen markieren
                                </button>
                            <?php else: ?>
                                <button onclick="markAsIncomplete(<?php echo $current_lesson['id']; ?>)" 
                                        class="btn-incomplete">
                                    ↺ Als nicht abgeschlossen markieren
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
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
                    <h2>Keine Lektionen verfügbar</h2>
                    <p>Dieser Kurs enthält noch keine Lektionen.</p>
                    <?php if ($is_logged_in): ?>
                        <a href="dashboard.php?page=kurse" class="btn-primary">Zurück zur Übersicht</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('toggleIcon');
            sidebar.classList.toggle('hidden');
            icon.textContent = sidebar.classList.contains('hidden') ? '→' : '←';
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
