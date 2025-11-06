<?php
/**
 * Freebie Course Player für Leads
 * Videokurs-Player ohne Login-Erfordernis
 * Fortschritt wird per E-Mail getrackt
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// Parameter aus URL
$freebie_id = isset($_GET['freebie_id']) ? (int)$_GET['freebie_id'] : 0;
$lead_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 0;

if ($freebie_id <= 0) {
    die('Ungültige Freebie-ID');
}

if (empty($lead_email) || !filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    die('Ungültige oder fehlende E-Mail-Adresse');
}

// Freebie und Kurs laden
try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.id as freebie_id,
            cf.headline,
            cf.primary_color,
            fc.id as course_id,
            fc.title as course_title,
            fc.description as course_description
        FROM customer_freebies cf
        JOIN freebie_courses fc ON cf.id = fc.freebie_id
        WHERE cf.id = ? AND fc.is_active = TRUE
    ");
    $stmt->execute([$freebie_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        die('Kurs nicht gefunden oder nicht aktiv');
    }
    
    $course_id = $data['course_id'];
    $course_title = $data['course_title'];
    $course_description = $data['course_description'];
    $primary_color = $data['primary_color'] ?? '#8B5CF6';
    
} catch (PDOException $e) {
    die('Fehler beim Laden des Kurses');
}

// Module und Lektionen laden
try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id as module_id,
            m.title as module_title,
            m.description as module_description,
            m.sort_order as module_order,
            l.id as lesson_id,
            l.title as lesson_title,
            l.description as lesson_description,
            l.video_url,
            l.pdf_url,
            l.sort_order as lesson_order
        FROM freebie_course_modules m
        LEFT JOIN freebie_course_lessons l ON m.id = l.module_id
        WHERE m.course_id = ?
        ORDER BY m.sort_order, l.sort_order
    ");
    $stmt->execute([$course_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Module strukturieren
    $modules = [];
    foreach ($results as $row) {
        $mid = $row['module_id'];
        if (!isset($modules[$mid])) {
            $modules[$mid] = [
                'id' => $mid,
                'title' => $row['module_title'],
                'description' => $row['module_description'],
                'lessons' => []
            ];
        }
        if ($row['lesson_id']) {
            $modules[$mid]['lessons'][] = [
                'id' => $row['lesson_id'],
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'video_url' => $row['video_url'],
                'pdf_url' => $row['pdf_url']
            ];
        }
    }
    
} catch (PDOException $e) {
    die('Fehler beim Laden der Lektionen');
}

// Fortschritt für Lead laden
$completed_lessons = [];
try {
    $stmt = $pdo->prepare("
        SELECT lesson_id 
        FROM freebie_course_progress 
        WHERE lead_email = ? AND completed = TRUE
    ");
    $stmt->execute([$lead_email]);
    $completed_lessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fortschritt-Fehler ignorieren
}

// Erste unvollständige Lektion oder übergebene Lektion finden
$current_lesson = null;
if ($lesson_id > 0) {
    // Spezifische Lektion wurde übergeben
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['id'] == $lesson_id) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

// Fallback: Erste unvollständige Lektion
if (!$current_lesson) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!in_array($lesson['id'], $completed_lessons)) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

// Fallback: Erste Lektion überhaupt
if (!$current_lesson && !empty($modules)) {
    $first_module = reset($modules);
    if (!empty($first_module['lessons'])) {
        $current_lesson = $first_module['lessons'][0];
    }
}

if (!$current_lesson) {
    die('Keine Lektionen in diesem Kurs verfügbar');
}

// Gesamtfortschritt berechnen
$total_lessons = 0;
$completed_count = count($completed_lessons);
foreach ($modules as $module) {
    $total_lessons += count($module['lessons']);
}
$progress_percent = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: <?php echo $primary_color; ?>;
            --primary-dark: color-mix(in srgb, <?php echo $primary_color; ?> 80%, black);
            --bg-dark: #0a0a16;
            --bg-secondary: #1a1532;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header {
            background: var(--bg-secondary);
            padding: 20px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .header-content {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .course-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--primary);
        }
        
        .progress-container {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
            height: 8px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            transition: width 0.3s;
            width: <?php echo $progress_percent; ?>%;
        }
        
        .progress-text {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        /* Main Layout */
        .main-container {
            flex: 1;
            display: grid;
            grid-template-columns: 1fr 380px;
            max-width: 1600px;
            margin: 0 auto;
            width: 100%;
            gap: 0;
        }
        
        /* Video Section */
        .video-section {
            padding: 24px;
            background: var(--bg-dark);
        }
        
        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 */
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .lesson-info {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 24px;
        }
        
        .lesson-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        
        .lesson-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        
        .lesson-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--bg-secondary);
            padding: 24px;
            overflow-y: auto;
            border-left: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .module {
            margin-bottom: 16px;
        }
        
        .module-header {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px 16px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        
        .module-header:hover {
            background: rgba(0, 0, 0, 0.4);
        }
        
        .module-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .module-toggle {
            font-size: 20px;
            transition: transform 0.2s;
        }
        
        .module.open .module-toggle {
            transform: rotate(90deg);
        }
        
        .module-lessons {
            padding: 8px 0 0 16px;
            display: none;
        }
        
        .module.open .module-lessons {
            display: block;
        }
        
        .lesson-item {
            padding: 10px 12px;
            margin: 4px 0;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background 0.2s;
            font-size: 14px;
        }
        
        .lesson-item:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .lesson-item.completed {
            color: #10b981;
        }
        
        .lesson-item.current {
            background: rgba(139, 92, 246, 0.2);
            border-left: 3px solid var(--primary);
        }
        
        .lesson-icon {
            flex-shrink: 0;
            width: 20px;
            text-align: center;
        }
        
        .lesson-name {
            flex: 1;
        }
        
        /* Mobile Sidebar Toggle */
        .mobile-sidebar-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
                position: fixed;
                top: 0;
                right: 0;
                width: 90%;
                max-width: 400px;
                height: 100vh;
                z-index: 999;
                box-shadow: -4px 0 12px rgba(0, 0, 0, 0.3);
            }
            
            .sidebar.open {
                display: block;
            }
            
            .mobile-sidebar-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .sidebar-close {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(255, 255, 255, 0.1);
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                cursor: pointer;
                font-size: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 16px;
            }
            
            .course-title {
                font-size: 20px;
            }
            
            .video-section {
                padding: 16px;
            }
            
            .lesson-title {
                font-size: 22px;
            }
            
            .lesson-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1 class="course-title"><?php echo htmlspecialchars($course_title); ?></h1>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            <div class="progress-text">
                <?php echo $completed_count; ?> von <?php echo $total_lessons; ?> Lektionen abgeschlossen (<?php echo $progress_percent; ?>%)
            </div>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Video Section -->
        <div class="video-section">
            <div class="video-container" id="videoContainer">
                <?php if (!empty($current_lesson['video_url'])): ?>
                    <iframe 
                        src="<?php echo htmlspecialchars($current_lesson['video_url']); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">
                        📹 Kein Video verfügbar
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="lesson-info">
                <h2 class="lesson-title" id="lessonTitle"><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <p class="lesson-description" id="lessonDescription"><?php echo htmlspecialchars($current_lesson['description'] ?? ''); ?></p>
                
                <div class="lesson-actions">
                    <button class="btn btn-primary" id="btnComplete" onclick="toggleComplete()">
                        <span id="completeIcon">✓</span>
                        <span id="completeText">Als abgeschlossen markieren</span>
                    </button>
                    
                    <?php if (!empty($current_lesson['pdf_url'])): ?>
                        <a href="<?php echo htmlspecialchars($current_lesson['pdf_url']); ?>" 
                           class="btn btn-secondary" 
                           target="_blank"
                           download>
                            📥 PDF herunterladen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <button class="sidebar-close" onclick="toggleSidebar()">✕</button>
            <div class="sidebar-title">
                📚 Kurs-Inhalt
            </div>
            
            <?php foreach ($modules as $module): ?>
                <div class="module open">
                    <div class="module-header" onclick="toggleModule(this)">
                        <span class="module-title"><?php echo htmlspecialchars($module['title']); ?></span>
                        <span class="module-toggle">▸</span>
                    </div>
                    <div class="module-lessons">
                        <?php foreach ($module['lessons'] as $lesson): 
                            $is_completed = in_array($lesson['id'], $completed_lessons);
                            $is_current = $lesson['id'] == $current_lesson['id'];
                            $class = '';
                            if ($is_completed) $class .= ' completed';
                            if ($is_current) $class .= ' current';
                        ?>
                            <div class="lesson-item<?php echo $class; ?>" 
                                 data-lesson-id="<?php echo $lesson['id']; ?>"
                                 onclick="loadLesson(<?php echo $lesson['id']; ?>)">
                                <span class="lesson-icon">
                                    <?php if ($is_completed): ?>
                                        ✓
                                    <?php elseif ($is_current): ?>
                                        ▶️
                                    <?php else: ?>
                                        ○
                                    <?php endif; ?>
                                </span>
                                <span class="lesson-name"><?php echo htmlspecialchars($lesson['title']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Mobile Sidebar Toggle -->
    <button class="mobile-sidebar-toggle" onclick="toggleSidebar()">
        📚
    </button>
    
    <script>
        const freebieId = <?php echo $freebie_id; ?>;
        const leadEmail = <?php echo json_encode($lead_email); ?>;
        let currentLessonId = <?php echo $current_lesson['id']; ?>;
        let completedLessons = <?php echo json_encode($completed_lessons); ?>;
        const allLessons = <?php echo json_encode(array_merge(...array_map(function($m) { return $m['lessons']; }, $modules))); ?>;
        
        // Module toggle
        function toggleModule(header) {
            header.parentElement.classList.toggle('open');
        }
        
        // Sidebar toggle (mobile)
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        // Lektion laden
        async function loadLesson(lessonId) {
            const lesson = allLessons.find(l => l.id == lessonId);
            if (!lesson) return;
            
            currentLessonId = lessonId;
            
            // Video aktualisieren
            const videoContainer = document.getElementById('videoContainer');
            if (lesson.video_url) {
                videoContainer.innerHTML = `
                    <iframe 
                        src="${lesson.video_url}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                `;
            } else {
                videoContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary);">📹 Kein Video verfügbar</div>';
            }
            
            // Lektions-Info aktualisieren
            document.getElementById('lessonTitle').textContent = lesson.title;
            document.getElementById('lessonDescription').textContent = lesson.description || '';
            
            // Complete-Button aktualisieren
            updateCompleteButton();
            
            // Sidebar aktualisieren
            updateSidebar();
            
            // URL aktualisieren
            const url = new URL(window.location);
            url.searchParams.set('lesson', lessonId);
            window.history.pushState({}, '', url);
            
            // Mobile: Sidebar schließen
            if (window.innerWidth <= 1024) {
                toggleSidebar();
            }
        }
        
        // Complete-Status toggle
        async function toggleComplete() {
            const isCompleted = completedLessons.includes(currentLessonId);
            const newStatus = !isCompleted;
            
            try {
                const response = await fetch('/customer/api/freebie-course-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'mark_complete',
                        lesson_id: currentLessonId,
                        email: leadEmail,
                        completed: newStatus
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (newStatus) {
                        completedLessons.push(currentLessonId);
                    } else {
                        completedLessons = completedLessons.filter(id => id != currentLessonId);
                    }
                    
                    updateCompleteButton();
                    updateSidebar();
                    updateProgress();
                    
                    // Nächste Lektion automatisch laden (nach 1.5s)
                    if (newStatus) {
                        const nextLesson = getNextLesson();
                        if (nextLesson) {
                            setTimeout(() => {
                                if (confirm('Möchtest du zur nächsten Lektion?')) {
                                    loadLesson(nextLesson.id);
                                }
                            }, 1500);
                        }
                    }
                } else {
                    alert('Fehler beim Speichern: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Verbindungsfehler');
            }
        }
        
        function updateCompleteButton() {
            const isCompleted = completedLessons.includes(currentLessonId);
            const btn = document.getElementById('btnComplete');
            const icon = document.getElementById('completeIcon');
            const text = document.getElementById('completeText');
            
            if (isCompleted) {
                icon.textContent = '↺';
                text.textContent = 'Als unerledigt markieren';
                btn.style.background = 'rgba(16, 185, 129, 0.2)';
            } else {
                icon.textContent = '✓';
                text.textContent = 'Als abgeschlossen markieren';
                btn.style.background = '';
            }
        }
        
        function updateSidebar() {
            document.querySelectorAll('.lesson-item').forEach(item => {
                const lessonId = parseInt(item.dataset.lessonId);
                const isCompleted = completedLessons.includes(lessonId);
                const isCurrent = lessonId === currentLessonId;
                
                item.className = 'lesson-item';
                if (isCompleted) item.classList.add('completed');
                if (isCurrent) item.classList.add('current');
                
                const icon = item.querySelector('.lesson-icon');
                if (isCompleted) {
                    icon.textContent = '✓';
                } else if (isCurrent) {
                    icon.textContent = '▶️';
                } else {
                    icon.textContent = '○';
                }
            });
        }
        
        function updateProgress() {
            const total = allLessons.length;
            const completed = completedLessons.length;
            const percent = Math.round((completed / total) * 100);
            
            document.querySelector('.progress-bar').style.width = percent + '%';
            document.querySelector('.progress-text').textContent = 
                `${completed} von ${total} Lektionen abgeschlossen (${percent}%)`;
        }
        
        function getNextLesson() {
            const currentIndex = allLessons.findIndex(l => l.id === currentLessonId);
            if (currentIndex >= 0 && currentIndex < allLessons.length - 1) {
                return allLessons[currentIndex + 1];
            }
            return null;
        }
        
        // Initial setup
        updateCompleteButton();
        updateSidebar();
    </script>
</body>
</html>
