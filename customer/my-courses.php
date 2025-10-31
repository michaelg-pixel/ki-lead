<?php
/**
 * Kundensicht: Meine Kurse
 * Zeigt alle freigeschalteten Kurse (Freebies + Digistore-Käufe)
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../public/login.php');
    exit;
}

$pdo = getDBConnection();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Kunde';

// Kurse holen zu denen der Nutzer Zugang hat
$stmt = $pdo->prepare("
    SELECT c.*, 
           ca.access_source,
           (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count,
           (SELECT COUNT(cl.id) FROM course_lessons cl 
            JOIN course_modules cm ON cl.module_id = cm.id 
            WHERE cm.course_id = c.id) as total_lessons,
           (SELECT COUNT(cp.id) FROM course_progress cp 
            JOIN course_lessons cl ON cp.lesson_id = cl.id
            JOIN course_modules cm ON cl.module_id = cm.id
            WHERE cm.course_id = c.id AND cp.user_id = ? AND cp.completed = TRUE) as completed_lessons
    FROM courses c
    LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
    WHERE c.is_freebie = TRUE OR ca.id IS NOT NULL
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meine Kurse - KI Lead-System</title>
    <link rel="stylesheet" href="styles/courses.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <span class="logo-icon">🎓</span>
                <span class="logo-text">KI Lead-System</span>
            </div>
            <div class="nav-links">
                <a href="dashboard.php">
                    <span class="nav-icon">🏠</span> Dashboard
                </a>
                <a href="my-courses.php" class="active">
                    <span class="nav-icon">📚</span> Meine Kurse
                </a>
                <a href="freebies.php">
                    <span class="nav-icon">🎁</span> Freebies
                </a>
                <a href="/logout.php" class="nav-logout">
                    <span class="nav-icon">🚪</span> Abmelden
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1>Meine Kurse</h1>
                <p>Willkommen zurück, <?php echo htmlspecialchars($user_name); ?>! 👋</p>
            </div>
        </div>

        <!-- Courses Grid -->
        <?php if (count($courses) > 0): ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): 
                    $progress = $course['total_lessons'] > 0 
                        ? round(($course['completed_lessons'] / $course['total_lessons']) * 100) 
                        : 0;
                ?>
                    <div class="course-card">
                        <!-- Thumbnail -->
                        <div class="course-thumbnail">
                            <?php if ($course['mockup_url']): ?>
                                <img src="<?php echo htmlspecialchars($course['mockup_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($course['title']); ?>">
                            <?php else: ?>
                                <div class="course-placeholder">
                                    <span class="placeholder-icon">
                                        <?php echo $course['type'] === 'pdf' ? '📄' : '🎥'; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Type Badge -->
                            <div class="course-type-badge">
                                <?php echo $course['type'] === 'pdf' ? '📄 PDF' : '🎥 Video'; ?>
                            </div>
                            
                            <!-- Freebie Badge -->
                            <?php if ($course['is_freebie']): ?>
                                <div class="course-freebie-badge">🎁 Kostenlos</div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Course Info -->
                        <div class="course-info">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p class="course-description">
                                <?php echo htmlspecialchars(substr($course['description'] ?? 'Kein Beschreibung verfügbar', 0, 100)); ?>
                                <?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                            </p>
                            
                            <?php if ($course['type'] === 'video'): ?>
                                <!-- Progress Bar -->
                                <div class="progress-container">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        <?php echo $progress; ?>% abgeschlossen 
                                        (<?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?> Lektionen)
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Course Stats -->
                            <div class="course-stats">
                                <?php if ($course['type'] === 'video'): ?>
                                    <span>📂 <?php echo $course['module_count']; ?> Module</span>
                                    <span>🎥 <?php echo $course['total_lessons']; ?> Lektionen</span>
                                <?php else: ?>
                                    <span>📄 PDF-Dokument</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action Button -->
                        <div class="course-footer">
                            <a href="course-view.php?id=<?php echo $course['id']; ?>" class="btn-start-course">
                                <?php if ($progress > 0): ?>
                                    ▶️ Fortsetzen
                                <?php else: ?>
                                    🚀 Kurs starten
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h2>Keine Kurse verfügbar</h2>
                <p>Du hast noch keinen Zugang zu Kursen. Schau dir unsere Freebies an oder kaufe einen Kurs!</p>
                <a href="freebies.php" class="btn-primary">Zu den Freebies</a>
            </div>
        <?php endif; ?>
    </div>

    <style>
        /* Base Styles */
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
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: white;
        }
        
        .logo-icon {
            font-size: 28px;
        }
        
        .nav-links {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .nav-links a {
            padding: 10px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            background: rgba(168, 85, 247, 0.1);
            color: var(--primary-light);
        }
        
        .nav-links a.active {
            background: rgba(168, 85, 247, 0.15);
            color: white;
        }
        
        .nav-logout {
            color: #fb7185 !important;
        }
        
        .nav-logout:hover {
            background: rgba(251, 113, 133, 0.1) !important;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 32px;
        }
        
        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 36px;
            color: white;
            margin-bottom: 8px;
        }
        
        .page-header p {
            font-size: 16px;
            color: var(--text-secondary);
        }
        
        /* Courses Grid */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 28px;
        }
        
        /* Course Card */
        .course-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .course-card:hover {
            transform: translateY(-8px);
            border-color: var(--primary);
            box-shadow: 0 20px 40px rgba(168, 85, 247, 0.3);
        }
        
        /* Course Thumbnail */
        .course-thumbnail {
            position: relative;
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(139, 64, 209, 0.05));
        }
        
        .course-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .course-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .placeholder-icon {
            font-size: 64px;
            opacity: 0.3;
        }
        
        /* Badges */
        .course-type-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .course-freebie-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(74, 222, 128, 0.9);
            backdrop-filter: blur(10px);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        /* Course Info */
        .course-info {
            padding: 24px;
            flex: 1;
        }
        
        .course-info h3 {
            font-size: 20px;
            color: white;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .course-description {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 16px;
        }
        
        /* Progress Bar */
        .progress-container {
            margin-bottom: 16px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Course Stats */
        .course-stats {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--text-secondary);
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        /* Course Footer */
        .course-footer {
            padding: 20px 24px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .btn-start-course {
            display: block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s;
        }
        
        .btn-start-course:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 20px;
            background: var(--bg-card);
            border: 2px dashed var(--border);
            border-radius: 16px;
        }
        
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 24px;
            opacity: 0.3;
        }
        
        .empty-state h2 {
            font-size: 28px;
            color: white;
            margin-bottom: 12px;
        }
        
        .empty-state p {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 32px;
        }
        
        .btn-primary {
            display: inline-block;
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-content {
                flex-direction: column;
                gap: 16px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 28px;
            }
        }
    </style>
</body>
</html>