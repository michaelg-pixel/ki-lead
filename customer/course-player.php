<?php
/**
 * Course Player - Videokurs Ansicht für Kunden
 * Komplett NEU erstellt - ohne Probleme der alten Datei
 */

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($course_id <= 0) {
    die('Ungültige Kurs-ID');
}

// Kurs laden mit Zugriffsprüfung
$stmt = $pdo->prepare("
    SELECT c.*, ca.id as has_access
    FROM courses c
    LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
    WHERE c.id = ?
    AND (c.is_freebie = 1 OR ca.id IS NOT NULL)
");
$stmt->execute([$customer_id, $course_id]);
$course = $stmt->fetch();

if (!$course) {
    die('Kurs nicht gefunden oder kein Zugriff');
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

// Alle Lektionen mit Fortschritt laden
$lessons = [];
foreach ($modules as $module) {
    $stmt = $pdo->prepare("
        SELECT 
            cl.*,
            cp.completed,
            cp.completed_at
        FROM course_lessons cl
        LEFT JOIN course_progress cp ON cl.id = cp.lesson_id AND cp.user_id = ?
        WHERE cl.module_id = ?
        ORDER BY cl.sort_order ASC
    ");
    $stmt->execute([$customer_id, $module['id']]);
    $lessons[$module['id']] = $stmt->fetchAll();
}

// Erste Video-Lektion finden (falls keine spezifische gewählt)
$selected_lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : null;
if (!$selected_lesson_id) {
    foreach ($modules as $module) {
        if (!empty($lessons[$module['id']])) {
            $selected_lesson_id = $lessons[$module['id']][0]['id'];
            break;
        }
    }
}

// Aktuelle Lektion laden
$current_lesson = null;
if ($selected_lesson_id) {
    $stmt = $pdo->prepare("
        SELECT 
            cl.*,
            cm.title as module_title,
            cp.completed
        FROM course_lessons cl
        JOIN course_modules cm ON cl.module_id = cm.id
        LEFT JOIN course_progress cp ON cl.id = cp.lesson_id AND cp.user_id = ?
        WHERE cl.id = ?
    ");
    $stmt->execute([$customer_id, $selected_lesson_id]);
    $current_lesson = $stmt->fetch();
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

        /* Layout Container */
        .player-container {
            display: flex;
            height: 100vh;
            width: 100vw;
        }

        /* Video Area */
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

        .header-right {
            display: flex;
            gap: 12px;
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
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .video-container iframe {
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

        .lesson-info h2 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .lesson-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 16px;
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

        .complete-button.completed:hover {
            transform: none;
            box-shadow: none;
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
            color: var(--text-primary);
        }

        /* Module List */
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
            color: var(--text-primary);
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

        /* Mobile Responsive */
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
        }

        /* Scrollbar Styling */
        .modules-list::-webkit-scrollbar {
            width: 8px;
        }

        .modules-list::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .modules-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px;
        }

        .modules-list::-webkit-scrollbar-thumb:hover {
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
                    <a href="/customer/dashboard.php?page=kurse" class="back-button">
                        ← Zurück zu Kursen
                    </a>
                    <div class="course-info">
                        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p><?php echo htmlspecialchars($current_lesson['module_title'] ?? 'Wähle eine Lektion'); ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <?php
                    // Fortschritt berechnen
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
                        <?php echo $completed_lessons; ?> / <?php echo $total_lessons; ?> Lektionen (<?php echo $progress_percent; ?>%)
                    </div>
                </div>
            </div>

            <!-- Video Container -->
            <div class="video-container">
                <?php if ($current_lesson && !empty($current_lesson['video_url'])): ?>
                    <?php
                    $video_url = $current_lesson['video_url'];
                    
                    // YouTube Embed URL erstellen
                    if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                        if (!empty($matches[1])) {
                            $video_url = 'https://www.youtube.com/embed/' . $matches[1] . '?autoplay=0&rel=0';
                        }
                    }
                    // Vimeo Embed URL erstellen
                    elseif (strpos($video_url, 'vimeo.com') !== false) {
                        preg_match('/vimeo\.com\/(\d+)/', $video_url, $matches);
                        if (!empty($matches[1])) {
                            $video_url = 'https://player.vimeo.com/video/' . $matches[1];
                        }
                    }
                    ?>
                    <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                            allow="autoplay; fullscreen; picture-in-picture" 
                            allowfullscreen></iframe>
                <?php else: ?>
                    <div class="video-placeholder">
                        <div class="video-placeholder-icon">🎥</div>
                        <p>Wähle eine Lektion aus der Seitenleiste</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lesson Info -->
            <?php if ($current_lesson): ?>
            <div class="lesson-info">
                <h2><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <div class="lesson-meta">
                    <span>📂 <?php echo htmlspecialchars($current_lesson['module_title']); ?></span>
                    <?php if ($current_lesson['duration']): ?>
                        <span>⏱️ <?php echo htmlspecialchars($current_lesson['duration']); ?></span>
                    <?php endif; ?>
                    <?php if ($current_lesson['completed']): ?>
                        <span style="color: var(--success);">✅ Abgeschlossen</span>
                    <?php endif; ?>
                </div>
                <?php if ($current_lesson['description']): ?>
                    <p class="lesson-description"><?php echo nl2br(htmlspecialchars($current_lesson['description'])); ?></p>
                <?php endif; ?>
                
                <button class="complete-button <?php echo $current_lesson['completed'] ? 'completed' : ''; ?>"
                        onclick="markAsComplete(<?php echo $current_lesson['id']; ?>)"
                        <?php echo $current_lesson['completed'] ? 'disabled' : ''; ?>>
                    <?php echo $current_lesson['completed'] ? '✅ Lektion abgeschlossen' : '✓ Als abgeschlossen markieren'; ?>
                </button>
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
                                    <a href="?id=<?php echo $course_id; ?>&lesson=<?php echo $lesson['id']; ?>" 
                                       class="lesson-item <?php echo $lesson['id'] == $selected_lesson_id ? 'active' : ''; ?> <?php echo $lesson['completed'] ? 'completed' : ''; ?>">
                                        <span class="lesson-icon">🎥</span>
                                        <span class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                        <?php if ($lesson['completed']): ?>
                                            <span class="lesson-status">✅</span>
                                        <?php endif; ?>
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
            </div>
        </div>
    </div>

    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()" style="display: none;">
        📚
    </button>

    <script>
        // Toggle Modul auf/zu
        function toggleModule(element) {
            element.parentElement.classList.toggle('open');
        }

        // Mobile Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Lektion als abgeschlossen markieren
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

        // Responsive: Mobile Toggle Button anzeigen
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