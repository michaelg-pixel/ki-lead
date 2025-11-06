<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// ========================================
// SICHERHEIT: TOKEN-VALIDIERUNG
// ========================================
$freebie_id = $_GET['id'] ?? null;
$token = $_GET['token'] ?? null;

if (!$freebie_id || !$token) {
    die('Ungültiger Zugang. Bitte nutze den Link aus deiner E-Mail.');
}

// Token validieren (SHA256 aus freebie_id + unique_id)
$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ? AND has_course = 1");
$stmt->execute([$freebie_id]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    die('Freebie nicht gefunden oder kein Videokurs verfügbar.');
}

// Token-Validierung
$valid_token = hash('sha256', $freebie['id'] . $freebie['unique_id']);
if ($token !== $valid_token) {
    die('Ungültiger Zugangs-Token. Bitte nutze den originalen Link.');
}

// ========================================
// KURS & MODULE LADEN
// ========================================
$stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
$stmt->execute([$freebie_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die('Kurs nicht gefunden.');
}

// Module mit Lektionen laden
$stmt = $pdo->prepare("
    SELECT * FROM freebie_course_modules 
    WHERE course_id = ? 
    ORDER BY sort_order ASC
");
$stmt->execute([$course['id']]);
$modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Alle Lektionen in ein flaches Array für Navigation
$all_lessons = [];
foreach ($modules as &$module) {
    $stmt = $pdo->prepare("
        SELECT * FROM freebie_course_lessons 
        WHERE module_id = ? 
        ORDER BY sort_order ASC
    ");
    $stmt->execute([$module['id']]);
    $module['lessons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($module['lessons'] as $lesson) {
        $all_lessons[] = [
            'lesson_id' => $lesson['id'],
            'module_id' => $module['id'],
            'module_title' => $module['title'],
            'lesson_title' => $lesson['title'],
            'video_url' => $lesson['video_url'],
            'content' => $lesson['content'],
            'pdf_url' => $lesson['pdf_url']
        ];
    }
}

if (empty($all_lessons)) {
    die('Keine Lektionen verfügbar. Der Kurs wird gerade erstellt.');
}

// ========================================
// AKTUELLE LEKTION ERMITTELN
// ========================================
$current_lesson_id = $_GET['lesson'] ?? $all_lessons[0]['lesson_id'];
$current_index = 0;

foreach ($all_lessons as $index => $lesson) {
    if ($lesson['lesson_id'] == $current_lesson_id) {
        $current_index = $index;
        break;
    }
}

$current_lesson = $all_lessons[$current_index];

// ========================================
// FORTSCHRITT IN SESSION SPEICHERN
// ========================================
$session_key = 'course_progress_' . $course['id'];
if (!isset($_SESSION[$session_key])) {
    $_SESSION[$session_key] = [];
}

// Lektion als angesehen markieren (bei Aufruf)
if (!in_array($current_lesson_id, $_SESSION[$session_key])) {
    $_SESSION[$session_key][] = $current_lesson_id;
}

$completed_count = count($_SESSION[$session_key]);
$total_count = count($all_lessons);
$progress_percent = round(($completed_count / $total_count) * 100);

// ========================================
// VIDEO EMBED URL GENERIEREN
// ========================================
function getVideoEmbedUrl($url) {
    if (empty($url)) return null;
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $match)) {
        return "https://www.youtube.com/embed/{$match[1]}?autoplay=0&rel=0";
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $match)) {
        return "https://player.vimeo.com/video/{$match[1]}";
    }
    
    return $url; // Fallback
}

$video_embed = getVideoEmbedUrl($current_lesson['video_url']);

// ========================================
// NAVIGATION: VORHERIGE/NÄCHSTE
// ========================================
$has_previous = $current_index > 0;
$has_next = $current_index < (count($all_lessons) - 1);

$previous_url = $has_previous ? "?id={$freebie_id}&token={$token}&lesson={$all_lessons[$current_index - 1]['lesson_id']}" : '#';
$next_url = $has_next ? "?id={$freebie_id}&token={$token}&lesson={$all_lessons[$current_index + 1]['lesson_id']}" : '#';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Videokurs</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f0f0f;
            color: #ffffff;
            min-height: 100vh;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }
        
        .header-content {
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .course-title {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .course-icon {
            font-size: 28px;
        }
        
        .progress-bar-container {
            flex: 1;
            max-width: 400px;
            margin: 0 40px;
        }
        
        .progress-label {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* MAIN LAYOUT */
        .player-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            max-width: 1800px;
            margin: 0 auto;
            min-height: calc(100vh - 81px);
        }
        
        /* VIDEO SECTION */
        .video-section {
            background: #000;
            padding: 40px;
        }
        
        .video-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
            background: #1a1a1a;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        
        .video-wrapper iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .lesson-info {
            margin-bottom: 32px;
        }
        
        .lesson-title {
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        
        .lesson-meta {
            font-size: 14px;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .lesson-content {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            line-height: 1.8;
            color: #ddd;
        }
        
        .lesson-content h3 {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
        }
        
        .pdf-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .pdf-download:hover {
            transform: translateY(-2px);
        }
        
        /* NAVIGATION BUTTONS */
        .navigation {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }
        
        .nav-button {
            flex: 1;
            padding: 16px 24px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: transparent;
            color: white;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .nav-button:hover:not(:disabled) {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }
        
        .nav-button:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .nav-button.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        
        .nav-button.primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4);
        }
        
        /* SIDEBAR */
        .sidebar {
            background: #1a1a1a;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
            max-height: calc(100vh - 81px);
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            background: #1a1a1a;
            z-index: 10;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }
        
        .sidebar-subtitle {
            font-size: 13px;
            color: #888;
        }
        
        /* MODULE */
        .module {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .module-header {
            padding: 20px 24px;
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .module-header:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .module-title-text {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .module-icon {
            font-size: 20px;
        }
        
        .module-toggle {
            font-size: 20px;
            color: #666;
            transition: transform 0.3s;
        }
        
        .module.expanded .module-toggle {
            transform: rotate(180deg);
        }
        
        /* LESSONS */
        .lesson-list {
            display: none;
        }
        
        .module.expanded .lesson-list {
            display: block;
        }
        
        .lesson-item {
            padding: 16px 24px 16px 48px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            color: inherit;
        }
        
        .lesson-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .lesson-item.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
            border-left: 3px solid #667eea;
        }
        
        .lesson-item-content {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .lesson-icon {
            font-size: 18px;
        }
        
        .lesson-name {
            font-size: 14px;
            font-weight: 500;
            color: #ddd;
        }
        
        .lesson-item.active .lesson-name {
            color: #fff;
            font-weight: 600;
        }
        
        .lesson-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }
        
        .lesson-item.completed .lesson-check {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1200px) {
            .player-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                max-height: 600px;
                border-left: none;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .progress-bar-container {
                display: none;
            }
            
            .header {
                padding: 16px 20px;
            }
            
            .video-section {
                padding: 20px;
            }
            
            .lesson-title {
                font-size: 24px;
            }
        }
        
        @media (max-width: 768px) {
            .course-title {
                font-size: 16px;
            }
            
            .course-icon {
                font-size: 20px;
            }
            
            .navigation {
                flex-direction: column;
            }
            
            .lesson-title {
                font-size: 20px;
            }
        }
        
        /* SCROLLBAR STYLING */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: #0f0f0f;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 4px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #444;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-content">
            <div class="course-title">
                <span class="course-icon">🎓</span>
                <?php echo htmlspecialchars($course['title']); ?>
            </div>
            
            <div class="progress-bar-container">
                <div class="progress-label">
                    <span>Dein Fortschritt</span>
                    <span><?php echo $completed_count; ?> von <?php echo $total_count; ?> Lektionen</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- MAIN PLAYER -->
    <div class="player-container">
        <!-- VIDEO SECTION -->
        <div class="video-section">
            <!-- Video Player -->
            <div class="video-wrapper">
                <?php if ($video_embed): ?>
                    <iframe src="<?php echo htmlspecialchars($video_embed); ?>" 
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                            allowfullscreen></iframe>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #666;">
                        Video nicht verfügbar
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Lesson Info -->
            <div class="lesson-info">
                <h1 class="lesson-title"><?php echo htmlspecialchars($current_lesson['lesson_title']); ?></h1>
                <div class="lesson-meta">
                    <span>📚 <?php echo htmlspecialchars($current_lesson['module_title']); ?></span>
                    <span>•</span>
                    <span>Lektion <?php echo $current_index + 1; ?> von <?php echo $total_count; ?></span>
                </div>
            </div>
            
            <!-- Lesson Content -->
            <?php if (!empty($current_lesson['content'])): ?>
            <div class="lesson-content">
                <h3>📝 Über diese Lektion</h3>
                <?php echo nl2br(htmlspecialchars($current_lesson['content'])); ?>
            </div>
            <?php endif; ?>
            
            <!-- PDF Download -->
            <?php if (!empty($current_lesson['pdf_url'])): ?>
            <a href="<?php echo htmlspecialchars($current_lesson['pdf_url']); ?>" target="_blank" class="pdf-download">
                📄 Zusatzmaterial herunterladen
            </a>
            <?php endif; ?>
            
            <!-- Navigation -->
            <div class="navigation">
                <?php if ($has_previous): ?>
                    <a href="<?php echo $previous_url; ?>" class="nav-button">
                        ← Vorherige Lektion
                    </a>
                <?php else: ?>
                    <button class="nav-button" disabled>
                        ← Vorherige Lektion
                    </button>
                <?php endif; ?>
                
                <?php if ($has_next): ?>
                    <a href="<?php echo $next_url; ?>" class="nav-button primary">
                        Nächste Lektion →
                    </a>
                <?php else: ?>
                    <button class="nav-button primary" disabled>
                        🎉 Kurs abgeschlossen!
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SIDEBAR -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-title">Kursinhalt</div>
                <div class="sidebar-subtitle">
                    <?php echo count($modules); ?> Module • <?php echo $total_count; ?> Lektionen
                </div>
            </div>
            
            <!-- Modules & Lessons -->
            <?php foreach ($modules as $module_index => $module): ?>
            <div class="module <?php echo ($module['id'] == $current_lesson['module_id']) ? 'expanded' : ''; ?>">
                <div class="module-header" onclick="toggleModule(this)">
                    <div class="module-title-text">
                        <span class="module-icon">📚</span>
                        <?php echo htmlspecialchars($module['title']); ?>
                    </div>
                    <span class="module-toggle">▼</span>
                </div>
                
                <div class="lesson-list">
                    <?php foreach ($module['lessons'] as $lesson_index => $lesson): ?>
                    <?php 
                        $is_current = ($lesson['id'] == $current_lesson_id);
                        $is_completed = in_array($lesson['id'], $_SESSION[$session_key]);
                        $lesson_url = "?id={$freebie_id}&token={$token}&lesson={$lesson['id']}";
                    ?>
                    <a href="<?php echo $lesson_url; ?>" 
                       class="lesson-item <?php echo $is_current ? 'active' : ''; ?> <?php echo $is_completed ? 'completed' : ''; ?>">
                        <div class="lesson-item-content">
                            <span class="lesson-icon">🎬</span>
                            <span class="lesson-name"><?php echo htmlspecialchars($lesson['title']); ?></span>
                        </div>
                        <div class="lesson-check">
                            <?php if ($is_completed): ?>✓<?php endif; ?>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        // Toggle Module Expansion
        function toggleModule(element) {
            const module = element.closest('.module');
            module.classList.toggle('expanded');
        }
        
        // Auto-expand current module on load
        document.addEventListener('DOMContentLoaded', function() {
            const activeLesson = document.querySelector('.lesson-item.active');
            if (activeLesson) {
                const module = activeLesson.closest('.module');
                if (module) {
                    module.classList.add('expanded');
                }
            }
        });
        
        // Smooth scroll to top on lesson change
        if (window.location.search.includes('lesson=')) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>
