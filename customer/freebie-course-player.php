<?php
/**
 * Freebie Course Player für Leads
 * Videokurs-Player ohne Login-Erfordernis mit DRIP-CONTENT
 * Fortschritt wird per E-Mail getrackt
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

// Parameter aus URL - ID ist die Course-ID aus freebie_courses
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lead_email = isset($_GET['email']) ? trim($_GET['email']) : '';
$lesson_id = isset($_GET['lesson']) ? (int)$_GET['lesson'] : 0;

if ($course_id <= 0) {
    die('Ungültige Kurs-ID');
}

// E-Mail ist optional - wenn nicht vorhanden, wird kein Fortschritt getrackt
if (!empty($lead_email) && !filter_var($lead_email, FILTER_VALIDATE_EMAIL)) {
    $lead_email = ''; // Ungültige E-Mail ignorieren
}

// Kurs und Freebie laden + Customer-Info für Empfehlungsprogramm-Check
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
        die('❌ Freebie nicht gefunden oder kein Zugriff');
    }
    
    $freebie_id = $data['freebie_id'];
    $course_title = $data['course_title'];
    $course_description = $data['course_description'];
    $primary_color = $data['primary_color'] ?? '#8B5CF6';
    $customer_id = $data['customer_id'];
    $referral_enabled = (int)$data['referral_enabled'];
    $ref_code = $data['ref_code'] ?? '';
    
} catch (PDOException $e) {
    die('Fehler beim Laden des Kurses: ' . $e->getMessage());
}

// 🔥 DRIP-CONTENT: Registrierungszeitpunkt ermitteln
$days_since_registration = 0;
$registration_date = null;

if (!empty($lead_email)) {
    try {
        // Prüfe wann Lead Zugriff erhalten hat
        $stmt = $pdo->prepare("
            SELECT access_granted_at 
            FROM freebie_course_lead_access 
            WHERE course_id = ? AND lead_email = ?
        ");
        $stmt->execute([$course_id, $lead_email]);
        $access = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$access) {
            // Ersten Zugriff registrieren
            $stmt = $pdo->prepare("
                INSERT INTO freebie_course_lead_access (course_id, lead_email, access_granted_at) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$course_id, $lead_email]);
            $days_since_registration = 0;
            $registration_date = date('Y-m-d H:i:s');
        } else {
            $registration_date = $access['access_granted_at'];
            $reg_time = strtotime($registration_date);
            $now = time();
            $days_since_registration = floor(($now - $reg_time) / 86400);
        }
    } catch (PDOException $e) {
        // Bei Fehler: Kein Drip-Content (alles freigeschaltet)
        $days_since_registration = 9999;
    }
}

// Module und Lektionen laden - MIT Unlock-Status!
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
            $unlock_days = intval($row['unlock_after_days']);
            $is_unlocked = ($unlock_days <= $days_since_registration);
            $days_until_unlock = max(0, $unlock_days - $days_since_registration);
            
            $modules[$mid]['lessons'][] = [
                'id' => $row['lesson_id'],
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'video_url' => $row['video_url'],
                'pdf_url' => $row['pdf_url'],
                'button_text' => $row['button_text'],
                'button_url' => $row['button_url'],
                'unlock_after_days' => $unlock_days,
                'is_unlocked' => $is_unlocked,
                'days_until_unlock' => $days_until_unlock
            ];
        }
    }
    
} catch (PDOException $e) {
    die('Fehler beim Laden der Lektionen: ' . $e->getMessage());
}

// Fortschritt für Lead laden (nur wenn E-Mail vorhanden)
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
        // Fortschritt-Fehler ignorieren
    }
}

// 🔥 DRIP-CONTENT: Erste FREIGESCHALTETE Lektion finden
$current_lesson = null;

// Wenn spezifische Lektion übergeben wurde
if ($lesson_id > 0) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['id'] == $lesson_id && $lesson['is_unlocked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

// Fallback: Erste unvollständige UND freigeschaltete Lektion
if (!$current_lesson && !empty($lead_email)) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if (!in_array($lesson['id'], $completed_lessons) && $lesson['is_unlocked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

// Fallback: Erste freigeschaltete Lektion überhaupt
if (!$current_lesson && !empty($modules)) {
    foreach ($modules as $module) {
        foreach ($module['lessons'] as $lesson) {
            if ($lesson['is_unlocked']) {
                $current_lesson = $lesson;
                break 2;
            }
        }
    }
}

if (!$current_lesson) {
    die('Keine Lektionen in diesem Kurs verfügbar oder noch nicht freigeschaltet');
}

// Video URL für Embedding vorbereiten
function getEmbedUrl($url) {
    if (empty($url)) return '';
    
    // YouTube
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    
    // Vimeo
    if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
        return 'https://player.vimeo.com/video/' . $matches[1];
    }
    
    // Falls bereits embed URL
    return $url;
}

$current_video_embed = getEmbedUrl($current_lesson['video_url']);

// Gesamtfortschritt berechnen
$total_lessons = 0;
$unlocked_lessons = 0;
$completed_count = count($completed_lessons);
foreach ($modules as $module) {
    foreach ($module['lessons'] as $lesson) {
        $total_lessons++;
        if ($lesson['is_unlocked']) $unlocked_lessons++;
    }
}
$progress_percent = $total_lessons > 0 ? round(($completed_count / $total_lessons) * 100) : 0;

// Empfehlungsprogramm-Button soll nur angezeigt werden wenn NICHT aktiviert
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
        
        /* ============================================
           SPEKTAKULÄRER CTA BANNER MIT EFFEKTEN
           ============================================ */
        
        .referral-cta-banner {
            background: linear-gradient(135deg, #ff6b35 0%, #f7931e 50%, #ff6b35 100%);
            background-size: 200% 200%;
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
            animation: gradient-shift 4s ease infinite, mega-pulse 3s ease-in-out infinite;
            box-shadow: 
                0 0 40px rgba(255, 107, 53, 0.6),
                0 0 80px rgba(255, 107, 53, 0.4),
                0 20px 60px rgba(255, 107, 53, 0.3);
        }
        
        /* Gradient Animation */
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        /* Mega Pulse Animation */
        @keyframes mega-pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 
                    0 0 40px rgba(255, 107, 53, 0.6),
                    0 0 80px rgba(255, 107, 53, 0.4),
                    0 20px 60px rgba(255, 107, 53, 0.3);
            }
            50% { 
                transform: scale(1.02);
                box-shadow: 
                    0 0 60px rgba(255, 107, 53, 0.8),
                    0 0 120px rgba(255, 107, 53, 0.6),
                    0 30px 80px rgba(255, 107, 53, 0.5);
            }
        }
        
        /* Glühender Rand-Effekt */
        .referral-cta-banner::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, 
                #ff0000, #ff7300, #fffb00, #48ff00, 
                #00ffd5, #002bff, #7a00ff, #ff00c8, #ff0000);
            background-size: 400% 400%;
            border-radius: 20px;
            z-index: -1;
            filter: blur(8px);
            opacity: 0.7;
            animation: rainbow-glow 8s linear infinite;
        }
        
        @keyframes rainbow-glow {
            0% { background-position: 0% 50%; }
            100% { background-position: 400% 50%; }
        }
        
        /* Shine-Effekt über den Banner */
        .referral-cta-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -100%;
            width: 100%;
            height: 200%;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            animation: shine-sweep 4s ease-in-out infinite;
        }
        
        @keyframes shine-sweep {
            0% { left: -100%; }
            20%, 100% { left: 200%; }
        }
        
        .referral-cta-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        
        /* Animiertes Raketen-Icon */
        .referral-cta-icon {
            font-size: 56px;
            flex-shrink: 0;
            animation: rocket-float 2s ease-in-out infinite;
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.8));
        }
        
        @keyframes rocket-float {
            0%, 100% { 
                transform: translateY(0) rotate(-5deg);
            }
            50% { 
                transform: translateY(-15px) rotate(5deg);
            }
        }
        
        .referral-cta-text {
            flex: 1;
            min-width: 280px;
        }
        
        .referral-cta-title {
            color: white;
            font-size: 26px;
            font-weight: 900;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            animation: title-pulse 2s ease-in-out infinite;
        }
        
        @keyframes title-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }
        
        .referral-cta-badge {
            background: rgba(255, 255, 255, 0.95);
            color: #ff6b35;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: badge-bounce 1s ease-in-out infinite;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        @keyframes badge-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        
        .referral-cta-description {
            color: rgba(255, 255, 255, 0.98);
            font-size: 16px;
            line-height: 1.7;
            margin-bottom: 20px;
            font-weight: 500;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
        }
        
        /* MEGA CTA BUTTON */
        .referral-cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 36px;
            background: white;
            color: #ff6b35;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 900;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 8px 24px rgba(0, 0, 0, 0.3),
                0 0 40px rgba(255, 255, 255, 0.5),
                inset 0 0 0 2px rgba(255, 107, 53, 0.2);
            position: relative;
            overflow: hidden;
            animation: button-glow 2s ease-in-out infinite;
        }
        
        @keyframes button-glow {
            0%, 100% {
                box-shadow: 
                    0 8px 24px rgba(0, 0, 0, 0.3),
                    0 0 40px rgba(255, 255, 255, 0.5),
                    inset 0 0 0 2px rgba(255, 107, 53, 0.2);
            }
            50% {
                box-shadow: 
                    0 12px 32px rgba(0, 0, 0, 0.4),
                    0 0 60px rgba(255, 255, 255, 0.8),
                    inset 0 0 0 3px rgba(255, 107, 53, 0.4);
            }
        }
        
        .referral-cta-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 107, 53, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .referral-cta-button:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 
                0 16px 40px rgba(0, 0, 0, 0.4),
                0 0 80px rgba(255, 255, 255, 0.9),
                inset 0 0 0 3px rgba(255, 107, 53, 0.4);
        }
        
        .referral-cta-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .referral-cta-button:active {
            transform: translateY(-2px) scale(1.02);
        }
        
        .referral-cta-button-icon {
            font-size: 22px;
            animation: icon-spin 3s linear infinite;
        }
        
        @keyframes icon-spin {
            0%, 90% { transform: rotate(0deg); }
            95% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }
        
        .referral-cta-button-arrow {
            font-size: 24px;
            transition: transform 0.3s;
        }
        
        .referral-cta-button:hover .referral-cta-button-arrow {
            transform: translateX(8px);
        }
        
        /* Features Grid */
        .referral-cta-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        
        .referral-cta-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 14px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }
        
        .referral-cta-feature:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateX(5px);
        }
        
        .referral-cta-feature-icon {
            font-size: 24px;
            animation: feature-bounce 2s ease-in-out infinite;
        }
        
        .referral-cta-feature:nth-child(1) .referral-cta-feature-icon {
            animation-delay: 0s;
        }
        
        .referral-cta-feature:nth-child(2) .referral-cta-feature-icon {
            animation-delay: 0.2s;
        }
        
        .referral-cta-feature:nth-child(3) .referral-cta-feature-icon {
            animation-delay: 0.4s;
        }
        
        @keyframes feature-bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        /* ============================================
           ENDE CTA BANNER
           ============================================ */
        
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
        
        /* ============================================
           LEKTIONS-BUTTON STYLING
           ============================================ */
        
        .lesson-button-container {
            margin-bottom: 24px;
        }
        
        .lesson-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 4px 20px rgba(139, 92, 246, 0.4),
                0 0 30px rgba(139, 92, 246, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .lesson-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }
        
        .lesson-button:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 
                0 8px 30px rgba(139, 92, 246, 0.6),
                0 0 50px rgba(139, 92, 246, 0.4);
        }
        
        .lesson-button:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .lesson-button:active {
            transform: translateY(-1px) scale(1.01);
        }
        
        .lesson-button-icon {
            font-size: 20px;
        }
        
        .lesson-button-arrow {
            font-size: 20px;
            transition: transform 0.3s;
        }
        
        .lesson-button:hover .lesson-button-arrow {
            transform: translateX(5px);
        }
        
        /* ============================================
           ENDE LEKTIONS-BUTTON
           ============================================ */
        
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
            text-decoration: none;
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
            position: relative;
        }
        
        .lesson-item:hover:not(.locked) {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .lesson-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
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
        
        /* 🔥 LOCK BADGE für gesperrte Lektionen */
        .lesson-lock-badge {
            background: rgba(255, 152, 0, 0.2);
            color: #ffa726;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
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
            
            .lesson-button {
                width: 100%;
                padding: 14px 24px;
                font-size: 15px;
            }
            
            /* Responsive CTA Banner */
            .referral-cta-banner {
                padding: 20px;
                border-radius: 16px;
            }
            
            .referral-cta-content {
                flex-direction: column;
                text-align: center;
                gap: 16px;
            }
            
            .referral-cta-icon {
                font-size: 48px;
            }
            
            .referral-cta-title {
                font-size: 22px;
                justify-content: center;
            }
            
            .referral-cta-description {
                font-size: 15px;
            }
            
            .referral-cta-button {
                width: 100%;
                padding: 16px 24px;
                font-size: 16px;
            }
            
            .referral-cta-features {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .referral-cta-banner {
                padding: 16px;
            }
            
            .referral-cta-icon {
                font-size: 40px;
            }
            
            .referral-cta-title {
                font-size: 18px;
            }
            
            .referral-cta-description {
                font-size: 14px;
            }
            
            .referral-cta-button {
                font-size: 14px;
                padding: 14px 20px;
            }
            
            .referral-cta-badge {
                font-size: 11px;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1 class="course-title"><?php echo htmlspecialchars($course_title); ?></h1>
            <?php if (!empty($lead_email)): ?>
            <div class="progress-container">
                <div class="progress-bar"></div>
            </div>
            <div class="progress-text">
                <?php echo $completed_count; ?> von <?php echo $total_lessons; ?> Lektionen abgeschlossen (<?php echo $progress_percent; ?>%)
                <?php if ($unlocked_lessons < $total_lessons): ?>
                    <span style="color: #ffa726; margin-left: 8px;">
                        • <?php echo $unlocked_lessons; ?> von <?php echo $total_lessons; ?> freigeschaltet
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Container -->
    <div class="main-container">
        <!-- Video Section -->
        <div class="video-section">
            <div class="video-container" id="videoContainer">
                <?php if (!empty($current_video_embed)): ?>
                    <iframe 
                        src="<?php echo htmlspecialchars($current_video_embed); ?>"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                <?php else: ?>
                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary); position: absolute; width: 100%; top: 0;">
                        📹 Kein Video verfügbar
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Empfehlungsprogramm CTA Banner (nur wenn NICHT aktiviert) -->
            <?php if ($show_referral_cta): ?>
            <div class="referral-cta-banner">
                <div class="referral-cta-content">
                    <div class="referral-cta-icon">🚀</div>
                    <div class="referral-cta-text">
                        <div class="referral-cta-title">
                            Verdiene mit Empfehlungen!
                            <span class="referral-cta-badge">NEU ✨</span>
                        </div>
                        <div class="referral-cta-description">
                            Aktiviere jetzt dein Empfehlungsprogramm und erhalte automatisch Belohnungen für jeden Lead, den du vermittelst. Starte noch heute!
                        </div>
                        <a href="https://app.mehr-infos-jetzt.de/lead_login.php" class="referral-cta-button">
                            <span class="referral-cta-button-icon">✨</span>
                            <span>Jetzt aktivieren</span>
            <span class="referral-cta-button-arrow">→</span>
                        </a>
                        <div class="referral-cta-features">
                            <div class="referral-cta-feature">
                                <span class="referral-cta-feature-icon">✅</span>
                                <span>Automatische Belohnungen</span>
                            </div>
                            <div class="referral-cta-feature">
                                <span class="referral-cta-feature-icon">📊</span>
                                <span>Live-Tracking Dashboard</span>
                            </div>
                            <div class="referral-cta-feature">
                                <span class="referral-cta-feature-icon">🎁</span>
                                <span>Individuelle Prämien</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="lesson-info">
                <h2 class="lesson-title" id="lessonTitle"><?php echo htmlspecialchars($current_lesson['title']); ?></h2>
                <?php if (!empty($current_lesson['description'])): ?>
                    <p class="lesson-description" id="lessonDescription"><?php echo htmlspecialchars($current_lesson['description']); ?></p>
                <?php endif; ?>
                
                <!-- Lektions-Button (falls vorhanden) -->
                <?php if (!empty($current_lesson['button_text']) && !empty($current_lesson['button_url'])): ?>
                <div class="lesson-button-container" id="lessonButtonContainer">
                    <a href="<?php echo htmlspecialchars($current_lesson['button_url']); ?>" 
                       class="lesson-button" 
                       target="_blank"
                       id="lessonButton">
                        <span class="lesson-button-icon">🎯</span>
                        <span id="lessonButtonText"><?php echo htmlspecialchars($current_lesson['button_text']); ?></span>
                        <span class="lesson-button-arrow">→</span>
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="lesson-actions">
                    <?php if (!empty($lead_email)): ?>
                    <button class="btn btn-primary" id="btnComplete" onclick="toggleComplete()">
                        <span id="completeIcon">✓</span>
                        <span id="completeText">Als abgeschlossen markieren</span>
                    </button>
                    <?php endif; ?>
                    
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
                            $is_locked = !$lesson['is_unlocked'];
                            $class = '';
                            if ($is_completed) $class .= ' completed';
                            if ($is_current) $class .= ' current';
                            if ($is_locked) $class .= ' locked';
                        ?>
                            <div class="lesson-item<?php echo $class; ?>" 
                                 data-lesson-id="<?php echo $lesson['id']; ?>"
                                 data-video-url="<?php echo htmlspecialchars(getEmbedUrl($lesson['video_url'])); ?>"
                                 data-title="<?php echo htmlspecialchars($lesson['title']); ?>"
                                 data-description="<?php echo htmlspecialchars($lesson['description'] ?? ''); ?>"
                                 data-pdf-url="<?php echo htmlspecialchars($lesson['pdf_url'] ?? ''); ?>"
                                 data-button-text="<?php echo htmlspecialchars($lesson['button_text'] ?? ''); ?>"
                                 data-button-url="<?php echo htmlspecialchars($lesson['button_url'] ?? ''); ?>"
                                 data-unlocked="<?php echo $lesson['is_unlocked'] ? '1' : '0'; ?>"
                                 onclick="<?php echo $is_locked ? 'return false;' : 'loadLesson(' . $lesson['id'] . ')'; ?>">
                                <span class="lesson-icon">
                                    <?php if ($is_locked): ?>
                                        🔒
                                    <?php elseif ($is_completed): ?>
                                        ✓
                                    <?php elseif ($is_current): ?>
                                        ▶️
                                    <?php else: ?>
                                        ○
                                    <?php endif; ?>
                                </span>
                                <span class="lesson-name"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                <?php if ($is_locked && $lesson['days_until_unlock'] > 0): ?>
                                    <span class="lesson-lock-badge">
                                        In <?php echo $lesson['days_until_unlock']; ?> Tag<?php echo $lesson['days_until_unlock'] > 1 ? 'en' : ''; ?>
                                    </span>
                                <?php endif; ?>
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
        const courseId = <?php echo $course_id; ?>;
        const leadEmail = <?php echo json_encode($lead_email); ?>;
        let currentLessonId = <?php echo $current_lesson['id']; ?>;
        let completedLessons = <?php echo json_encode($completed_lessons); ?>;
        
        // Module toggle
        function toggleModule(header) {
            header.parentElement.classList.toggle('open');
        }
        
        // Sidebar toggle (mobile)
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        // Lektion laden (nur wenn nicht gesperrt)
        function loadLesson(lessonId) {
            const lessonItem = document.querySelector(`[data-lesson-id="${lessonId}"]`);
            if (!lessonItem) return;
            
            // Prüfe ob gesperrt
            if (lessonItem.dataset.unlocked !== '1') {
                return; // Gesperrte Lektionen können nicht geladen werden
            }
            
            currentLessonId = lessonId;
            
            const videoUrl = lessonItem.dataset.videoUrl;
            const title = lessonItem.dataset.title;
            const description = lessonItem.dataset.description;
            const pdfUrl = lessonItem.dataset.pdfUrl;
            const buttonText = lessonItem.dataset.buttonText;
            const buttonUrl = lessonItem.dataset.buttonUrl;
            
            // Video aktualisieren
            const videoContainer = document.getElementById('videoContainer');
            if (videoUrl) {
                videoContainer.innerHTML = `
                    <iframe 
                        src="${videoUrl}"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                `;
            } else {
                videoContainer.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--text-secondary); position: absolute; width: 100%; top: 0;">📹 Kein Video verfügbar</div>';
            }
            
            // Lektions-Info aktualisieren
            document.getElementById('lessonTitle').textContent = title;
            const descEl = document.getElementById('lessonDescription');
            if (descEl) {
                descEl.textContent = description;
            }
            
            // Button aktualisieren
            const buttonContainer = document.getElementById('lessonButtonContainer');
            if (buttonText && buttonUrl) {
                if (!buttonContainer) {
                    // Container erstellen
                    const newContainer = document.createElement('div');
                    newContainer.id = 'lessonButtonContainer';
                    newContainer.className = 'lesson-button-container';
                    newContainer.innerHTML = `
                        <a href="${buttonUrl}" class="lesson-button" target="_blank" id="lessonButton">
                            <span class="lesson-button-icon">🎯</span>
                            <span id="lessonButtonText">${buttonText}</span>
                            <span class="lesson-button-arrow">→</span>
                        </a>
                    `;
                    const descriptionEl = document.getElementById('lessonDescription');
                    if (descriptionEl) {
                        descriptionEl.after(newContainer);
                    } else {
                        document.querySelector('.lesson-info').insertBefore(newContainer, document.querySelector('.lesson-actions'));
                    }
                } else {
                    // Button aktualisieren
                    const button = document.getElementById('lessonButton');
                    button.href = buttonUrl;
                    document.getElementById('lessonButtonText').textContent = buttonText;
                    buttonContainer.style.display = 'block';
                }
            } else if (buttonContainer) {
                // Button verstecken
                buttonContainer.style.display = 'none';
            }
            
            // Complete-Button aktualisieren
            if (leadEmail) {
                updateCompleteButton();
            }
            
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
        
        // Complete-Status toggle (nur wenn Email vorhanden)
        async function toggleComplete() {
            if (!leadEmail) return;
            
            const isCompleted = completedLessons.includes(currentLessonId);
            const newStatus = !isCompleted;
            
            try {
                const response = await fetch('/customer/api/freebie-course-public-api.php', {
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
                } else {
                    alert('Fehler beim Speichern: ' + result.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Verbindungsfehler');
            }
        }
        
        function updateCompleteButton() {
            const btn = document.getElementById('btnComplete');
            if (!btn) return;
            
            const isCompleted = completedLessons.includes(currentLessonId);
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
                const isLocked = item.dataset.unlocked !== '1';
                
                // Klassen neu setzen
                item.className = 'lesson-item';
                if (isCompleted) item.classList.add('completed');
                if (isCurrent) item.classList.add('current');
                if (isLocked) item.classList.add('locked');
                
                const icon = item.querySelector('.lesson-icon');
                if (isLocked) {
                    icon.textContent = '🔒';
                } else if (isCompleted) {
                    icon.textContent = '✓';
                } else if (isCurrent) {
                    icon.textContent = '▶️';
                } else {
                    icon.textContent = '○';
                }
            });
        }
        
        function updateProgress() {
            const allLessons = document.querySelectorAll('.lesson-item');
            const total = allLessons.length;
            const completed = completedLessons.length;
            const percent = Math.round((completed / total) * 100);
            
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.querySelector('.progress-text');
            
            if (progressBar) progressBar.style.width = percent + '%';
            if (progressText) {
                // Zähle freigeschaltete Lektionen
                let unlocked = 0;
                allLessons.forEach(item => {
                    if (item.dataset.unlocked === '1') unlocked++;
                });
                
                let text = `${completed} von ${total} Lektionen abgeschlossen (${percent}%)`;
                if (unlocked < total) {
                    text += ` <span style="color: #ffa726; margin-left: 8px;">• ${unlocked} von ${total} freigeschaltet</span>`;
                }
                progressText.innerHTML = text;
            }
        }
        
        // Initial setup
        if (leadEmail) {
            updateCompleteButton();
        }
        updateSidebar();
    </script>
</body>
</html>