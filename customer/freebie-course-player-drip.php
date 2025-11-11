<?php
/**
 * Freebie Course Player - MIT DRIP CONTENT
 * Lektionen werden basierend auf unlock_after_days gesperrt/entsperrt
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// Parameter aus URL
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lead_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 0;

if ($course_id <= 0) {
    die('Ungültige Kurs-ID');
}

// Kurs und Freebie laden
try {
    $stmt = $pdo->prepare("
        SELECT 
            fc.id as course_id,
            fc.title as course_title,
            fc.description as course_description,
            cf.id as freebie_id,
            cf.headline,
            cf.primary_color,
            cf.customer_id,
            cf.created_at as freebie_created_at,
            u.referral_enabled,
            u.ref_code
        FROM freebie_courses fc
        JOIN customer_freebies cf ON fc.freebie_id = cf.id
        JOIN users u ON cf.customer_id = u.id
        WHERE fc.id = ?
    ");
    $stmt->execute([$course_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$data) {
        die('❌ Kurs nicht gefunden');
    }
    
    $freebie_id = $data['freebie_id'];
    $course_title = $data['course_title'];
    $primary_color = $data['primary_color'] ?? '#8B5CF6';
    $customer_id = $data['customer_id'];
    $referral_enabled = (int)$data['referral_enabled'];
    $ref_code = $data['ref_code'] ?? '';
    
    // WICHTIG: Verwende das Erstellungsdatum des Freebies als Basis für Drip Content
    $registration_date = $data['freebie_created_at'];
    
} catch (PDOException $e) {
    die('Fehler beim Laden: ' . $e->getMessage());
}

// Berechne wie viele Tage seit Registrierung vergangen sind
$now = new DateTime();
$reg_date = new DateTime($registration_date);
$days_since_registration = $now->diff($reg_date)->days;

// Module und Lektionen laden - MIT DRIP CHECK
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
            l.button_text,
            l.button_url,
            l.unlock_after_days,
            l.sort_order as lesson_order
        FROM freebie_course_modules m
        LEFT JOIN freebie_course_lessons l ON m.id = l.module_id
        WHERE m.course_id = ?
        ORDER BY m.sort_order, l.sort_order
    ");
    $stmt->execute([$course_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Module strukturieren + Drip Content prüfen
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
            $unlock_days = (int)$row['unlock_after_days'];
            $is_locked = ($unlock_days > $days_since_registration);
            
            $modules[$mid]['lessons'][] = [
                'id' => $row['lesson_id'],
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'video_url' => $row['video_url'],
                'pdf_url' => $row['pdf_url'],
                'button_text' => $row['button_text'],
                'button_url' => $row['button_url'],
                'unlock_after_days' => $unlock_days,
                'is_locked' => $is_locked,
                'days_remaining' => max(0, $unlock_days - $days_since_registration)
            ];
        }
    }
    
} catch (PDOException $e) {
    die('Fehler beim Laden der Lektionen: ' . $e->getMessage());
}

// Fortschritt laden (optional)
$completed_lessons = [];
if (!empty($lead_email)) {
    try {
        $stmt = $pdo->prepare("
            SELECT lesson_id 
            FROM freebie_course_progress 
            WHERE lead_email = ? AND completed = TRUE
        ");
        $stmt->execute([$lead_email]);
        $completed_lessons = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        // Ignore
    }
}

// Erste FREIGESCHALTETE Lektion finden
$current_lesson = null;
if ($lesson_id > 0) {
    // Spezifische Lektion - nur wenn freigeschaltet
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['id'] == $lesson_id && !$lesson['is_locked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

// Fallback: Erste nicht gesperrte Lektion
if (!$current_lesson) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!$lesson['is_locked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

if (!$current_lesson) {
    die('<div style="text-align: center; padding: 60px; color: white;">
        <h2>🔒 Noch keine Lektionen freigeschaltet</h2>
        <p style="margin-top: 20px; color: #9ca3af;">Die ersten Lektionen werden in Kürze für dich freigeschaltet.</p>
    </div>');
}

// Video URL
function getEmbedUrl($url) {
    if (empty($url)) return '';
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    return $url;
}

$current_video_embed = getEmbedUrl($current_lesson['video_url']);

// Fortschritt
$total_lessons = 0;
foreach ($modules as $module) {
    $total_lessons += count($module['lessons']);
}
$completed_count = count($completed_lessons);
$progress_percent = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;

$show_referral_cta = ($referral_enabled == 0);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: <?php echo $primary_color; ?>;
            --bg-dark: #0a0a16;
            --bg-secondary: #1a1532;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-dark);
            color: #e5e7eb;
            min-height: 100vh;
        }
        
        .header {
            background: var(--bg-secondary);
            padding: 20px;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        
        .course-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .main-container {
            display: grid;
            grid-template-columns: 1fr 380px;
            max-width: 1600px;
            margin: 0 auto;
            gap: 0;
        }
        
        .video-section {
            padding: 24px;
        }
        
        .video-container {
            position: relative;
            padding-bottom: 56.25%;
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
        }
        
        .sidebar {
            background: var(--bg-secondary);
            padding: 24px;
            overflow-y: auto;
            border-left: 1px solid rgba(139, 92, 246, 0.2);
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
        }
        
        .module-title {
            font-size: 15px;
            font-weight: 600;
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
            font-size: 14px;
        }
        
        .lesson-item:not(.locked):hover {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .lesson-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .lesson-item.current {
            background: rgba(139, 92, 246, 0.2);
            border-left: 3px solid var(--primary);
        }
        
        .lesson-icon {
            flex-shrink: 0;
            width: 20px;
        }
        
        .lesson-name {
            flex: 1;
        }
        
        .lock-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="course-title"><?php echo htmlspecialchars($course_title); ?></h1>
    </div>
    
    <div class="main-container">
        <div class="video-section">
            <div class="video-container">
                <?php if (!empty($current_video_embed)): ?>
                    <iframe src="<?php echo htmlspecialchars($current_video_embed); ?>" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe>
                <?php endif; ?>
            </div>
            
            <div class="lesson-info">
                <h2 class="lesson-title"><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <?php if (!empty($current_lesson['description'])): ?>
                    <p style="color: #9ca3af; margin-bottom: 20px;"><?php echo htmlspecialchars($current_lesson['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="sidebar">
            <h3 style="margin-bottom: 20px;">📚 Kurs-Inhalt</h3>
            
            <?php foreach ($modules as $module): ?>
                <div class="module open">
                    <div class="module-header" onclick="this.parentElement.classList.toggle('open')">
                        <span class="module-title"><?php echo htmlspecialchars($module['title']); ?></span>
                        <span>▸</span>
                    </div>
                    <div class="module-lessons">
                        <?php foreach ($module['lessons'] as $lesson): 
                            $is_current = $lesson['id'] == $current_lesson['id'];
                            $class = $lesson['is_locked'] ? 'lesson-item locked' : 'lesson-item';
                            if ($is_current) $class .= ' current';
                        ?>
                            <div class="<?php echo $class; ?>" 
                                 onclick="<?php echo $lesson['is_locked'] ? '' : "window.location='?id=$course_id&lesson={$lesson['id']}'"; ?>">
                                <span class="lesson-icon">
                                    <?php if ($lesson['is_locked']): ?>
                                        🔒
                                    <?php elseif ($is_current): ?>
                                        ▶️
                                    <?php else: ?>
                                        ○
                                    <?php endif; ?>
                                </span>
                                <span class="lesson-name"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                <?php if ($lesson['is_locked']): ?>
                                    <span class="lock-badge">
                                        <?php if ($lesson['days_remaining'] == 0): ?>
                                            Morgen
                                        <?php elseif ($lesson['days_remaining'] == 1): ?>
                                            In 1 Tag
                                        <?php else: ?>
                                            In <?php echo $lesson['days_remaining']; ?> Tagen
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
