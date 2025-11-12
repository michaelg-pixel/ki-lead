<?php
/**
 * Customer Kurse - Übersicht mit Zugriffskontrolle
 * Zeigt alle verfügbaren Kurse (Freebie + gekauft)
 */

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Alle Kurse mit Zugriffsinformationen laden
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        ca.id as has_access,
        ca.access_source,
        (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count,
        (SELECT COUNT(cl.id) 
         FROM course_lessons cl 
         JOIN course_modules cm ON cl.module_id = cm.id 
         WHERE cm.course_id = c.id) as total_lessons,
        (SELECT COUNT(cp.id) 
         FROM course_progress cp 
         JOIN course_lessons cl ON cp.lesson_id = cl.id 
         JOIN course_modules cm ON cl.module_id = cm.id 
         WHERE cm.course_id = c.id 
         AND cp.user_id = ? 
         AND cp.completed = 1) as completed_lessons
    FROM courses c
    LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.user_id = ?
    WHERE c.is_active = 1
    AND (c.is_freebie = 1 OR ca.id IS NOT NULL)
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$accessible_courses = $stmt->fetchAll();

// Nicht-zugängliche kostenpflichtige Kurse
$stmt = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count
    FROM courses c
    WHERE c.is_active = 1
    AND c.is_freebie = 0
    AND c.id NOT IN (
        SELECT course_id FROM course_access WHERE user_id = {$user_id}
    )
    ORDER BY c.created_at DESC
");
$locked_courses = $stmt->fetchAll();
?>

<style>
    .courses-container {
        padding: 32px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        margin-bottom: 40px;
    }
    
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
    }
    
    .page-subtitle {
        font-size: 15px;
        color: #9ca3af;
    }
    
    .section-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-divider {
        margin: 48px 0;
        border-top: 1px solid rgba(168, 85, 247, 0.2);
    }
    
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
    }
    
    .course-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(168, 85, 247, 0.2);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .course-card:hover {
        transform: translateY(-4px);
        border-color: rgba(168, 85, 247, 0.4);
        box-shadow: 0 20px 40px rgba(168, 85, 247, 0.3);
    }
    
    .course-card.locked {
        opacity: 0.7;
    }
    
    .course-card.locked:hover {
        transform: translateY(-2px);
    }
    
    /* VERBESSERTE THUMBNAIL DARSTELLUNG */
    .course-thumbnail {
        position: relative;
        width: 100%;
        height: 200px;
        background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(139, 64, 209, 0.05));
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    
    .course-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        object-position: center;
    }
    
    .course-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 64px;
    }
    
    /* Badges */
    .course-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        backdrop-filter: blur(10px);
        z-index: 10;
    }
    
    .badge-freebie {
        background: rgba(74, 222, 128, 0.9);
        color: white;
    }
    
    .badge-locked {
        background: rgba(251, 113, 133, 0.9);
        color: white;
    }
    
    .badge-type {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(10px);
        color: white;
        z-index: 10;
    }
    
    /* Content */
    .course-content {
        padding: 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .course-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
        margin-bottom: 12px;
        line-height: 1.3;
    }
    
    .course-description {
        font-size: 14px;
        color: #9ca3af;
        line-height: 1.6;
        margin-bottom: 16px;
        flex: 1;
    }
    
    /* Stats */
    .course-stats {
        display: flex;
        gap: 16px;
        font-size: 13px;
        color: #9ca3af;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    
    .course-stat {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    /* Progress */
    .course-progress {
        margin-bottom: 16px;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 12px;
        color: #9ca3af;
        margin-bottom: 8px;
    }
    
    .progress-bar {
        height: 6px;
        background: rgba(168, 85, 247, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4ade80, #22c55e);
        transition: width 0.3s ease;
    }
    
    /* Action Button */
    .course-action {
        display: block;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        text-decoration: none;
    }
    
    .btn-start {
        background: linear-gradient(135deg, #a855f7, #8b40d1);
        color: white;
    }
    
    .btn-start:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
    }
    
    .btn-continue {
        background: rgba(74, 222, 128, 0.15);
        color: #4ade80;
        border: 1px solid rgba(74, 222, 128, 0.3);
    }
    
    .btn-continue:hover {
        background: rgba(74, 222, 128, 0.25);
    }
    
    .btn-buy {
        background: rgba(251, 113, 133, 0.15);
        color: #fb7185;
        border: 1px solid rgba(251, 113, 133, 0.3);
    }
    
    .btn-buy:hover {
        background: rgba(251, 113, 133, 0.25);
    }
    
    /* Lock Overlay */
    .lock-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
    }
    
    .course-card.locked:hover .lock-overlay {
        opacity: 1;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 2px dashed rgba(168, 85, 247, 0.3);
        border-radius: 16px;
        grid-column: 1 / -1;
    }
    
    .empty-icon {
        font-size: 80px;
        margin-bottom: 24px;
        opacity: 0.5;
    }
    
    .empty-title {
        font-size: 24px;
        font-weight: 600;
        color: white;
        margin-bottom: 12px;
    }
    
    .empty-text {
        font-size: 15px;
        color: #9ca3af;
        max-width: 500px;
        margin: 0 auto;
    }
    
    /* Responsive */
    @media (max-width: 1400px) {
        .courses-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 1024px) {
        .courses-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
    }
    
    /* MOBILE OPTIMIERUNGEN - MEHR PLATZ AM UNTEREN RAND */
    @media (max-width: 768px) {
        .courses-container {
            padding: 20px 16px 80px 16px; /* 80px Platz am unteren Rand */
        }
        
        .page-header {
            margin-bottom: 28px;
        }
        
        .page-title {
            font-size: 24px;
        }
        
        .page-subtitle {
            font-size: 14px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 16px;
        }
        
        .section-divider {
            margin: 32px 0;
        }
        
        .courses-grid {
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 40px; /* Extra Platz nach jedem Grid */
        }
        
        .course-card {
            border-radius: 12px;
            margin-bottom: 8px; /* Extra Spacing zwischen Karten */
        }
        
        .course-thumbnail {
            height: 200px;
            padding: 12px;
        }
        
        .course-placeholder {
            font-size: 56px;
        }
        
        .course-badge {
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .badge-type {
            top: 10px;
            left: 10px;
            padding: 5px 10px;
            font-size: 11px;
        }
        
        .course-content {
            padding: 20px;
            padding-bottom: 24px; /* Mehr Platz am unteren Rand der Karte */
        }
        
        .course-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .course-description {
            font-size: 13px;
            margin-bottom: 14px;
            line-height: 1.5;
        }
        
        .course-stats {
            font-size: 12px;
            gap: 12px;
            margin-bottom: 14px;
        }
        
        .course-progress {
            margin-bottom: 16px; /* Mehr Abstand vor Button */
        }
        
        /* MOBILE BUTTONS */
        .course-action {
            padding: 16px 20px;
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            min-height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.3px;
            margin-bottom: 4px; /* Kleiner Puffer am unteren Rand */
        }
        
        /* Touch-Feedback für Mobile */
        .course-action:active {
            transform: scale(0.97);
        }
        
        .empty-state {
            padding: 60px 20px;
            margin-bottom: 40px;
        }
        
        .empty-icon {
            font-size: 64px;
        }
        
        .empty-title {
            font-size: 20px;
        }
        
        .empty-text {
            font-size: 14px;
        }
    }
    
    /* EXTRA KLEINE BILDSCHIRME - NOCH MEHR PLATZ */
    @media (max-width: 480px) {
        .courses-container {
            padding: 16px 12px 100px 12px; /* 100px Platz am unteren Rand */
        }
        
        .page-header {
            margin-bottom: 24px;
        }
        
        .page-title {
            font-size: 22px;
        }
        
        .page-subtitle {
            font-size: 13px;
        }
        
        .section-title {
            font-size: 16px;
        }
        
        .courses-grid {
            margin-bottom: 60px; /* Extra Platz nach Grid */
        }
        
        .course-thumbnail {
            height: 180px;
        }
        
        .course-placeholder {
            font-size: 48px;
        }
        
        .course-content {
            padding: 16px;
            padding-bottom: 20px;
        }
        
        .course-title {
            font-size: 17px;
        }
        
        .course-description {
            font-size: 12px;
            line-height: 1.4;
        }
        
        .course-stats {
            font-size: 11px;
            gap: 10px;
        }
        
        .course-action {
            padding: 18px 20px;
            font-size: 16px;
            font-weight: 700;
            min-height: 56px;
            margin-bottom: 8px;
        }
    }
    
    /* Touch-Geräte */
    @media (hover: none) and (pointer: coarse) {
        .course-card:hover {
            transform: none;
        }
        
        .course-action:active {
            opacity: 0.8;
            transform: scale(0.98);
        }
    }
</style>

<div class="courses-container">
    <div class="page-header">
        <h1 class="page-title">🎓 Meine Kurse</h1>
        <p class="page-subtitle">Greife auf deine Freebie-Kurse und gekauften Kurse zu</p>
    </div>
    
    <?php if (count($accessible_courses) > 0): ?>
        <h2 class="section-title">
            ✅ Verfügbare Kurse
        </h2>
        <div class="courses-grid">
            <?php foreach ($accessible_courses as $course): 
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
                                <?php echo $course['type'] === 'pdf' ? '📄' : '🎥'; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Type Badge -->
                        <div class="badge-type">
                            <?php echo $course['type'] === 'pdf' ? '📄 PDF' : '🎥 Video'; ?>
                        </div>
                        
                        <!-- Freebie Badge -->
                        <?php if ($course['is_freebie']): ?>
                            <div class="course-badge badge-freebie">
                                🎁 Kostenlos
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="course-content">
                        <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        
                        <p class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'] ?? 'Keine Beschreibung', 0, 120)); ?>
                            <?php echo strlen($course['description'] ?? '') > 120 ? '...' : ''; ?>
                        </p>
                        
                        <!-- Stats -->
                        <div class="course-stats">
                            <?php if ($course['type'] === 'video'): ?>
                                <div class="course-stat">
                                    <span>📂</span>
                                    <span><?php echo $course['module_count']; ?> Module</span>
                                </div>
                                <div class="course-stat">
                                    <span>📝</span>
                                    <span><?php echo $course['total_lessons']; ?> Lektionen</span>
                                </div>
                            <?php else: ?>
                                <div class="course-stat">
                                    <span>📄</span>
                                    <span>PDF-Dokument</span>
                                </div>
                            <?php endif; ?>
                            <?php if ($course['access_source']): ?>
                                <div class="course-stat">
                                    <span>✓</span>
                                    <span><?php echo ucfirst($course['access_source']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Progress (nur für Video-Kurse) -->
                        <?php if ($course['type'] === 'video' && $course['total_lessons'] > 0): ?>
                            <div class="course-progress">
                                <div class="progress-label">
                                    <span>Fortschritt</span>
                                    <span><strong><?php echo $course['completed_lessons']; ?></strong> / <?php echo $course['total_lessons']; ?></span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%;"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Action Button -->
                        <a href="/customer/course-player.php?id=<?php echo $course['id']; ?>" 
                           class="course-action <?php echo $progress > 0 ? 'btn-continue' : 'btn-start'; ?>">
                            <?php if ($progress > 0): ?>
                                <span>▶️</span>
                                <span>Weiter lernen (<?php echo $progress; ?>%)</span>
                            <?php else: ?>
                                <span>🚀</span>
                                <span>Kurs starten</span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($locked_courses) > 0): ?>
        <?php if (count($accessible_courses) > 0): ?>
            <div class="section-divider"></div>
        <?php endif; ?>
        
        <h2 class="section-title">
            🔒 Weitere Premium-Kurse
        </h2>
        <div class="courses-grid">
            <?php foreach ($locked_courses as $course): ?>
                <div class="course-card locked">
                    <!-- Lock Overlay -->
                    <div class="lock-overlay">🔒</div>
                    
                    <!-- Thumbnail -->
                    <div class="course-thumbnail">
                        <?php if ($course['mockup_url']): ?>
                            <img src="<?php echo htmlspecialchars($course['mockup_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <div class="course-placeholder">
                                <?php echo $course['type'] === 'pdf' ? '📄' : '🎥'; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Type Badge -->
                        <div class="badge-type">
                            <?php echo $course['type'] === 'pdf' ? '📄 PDF' : '🎥 Video'; ?>
                        </div>
                        
                        <!-- Locked Badge -->
                        <div class="course-badge badge-locked">
                            🔒 Premium
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div class="course-content">
                        <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        
                        <p class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'] ?? 'Keine Beschreibung', 0, 120)); ?>
                            <?php echo strlen($course['description'] ?? '') > 120 ? '...' : ''; ?>
                        </p>
                        
                        <!-- Stats -->
                        <div class="course-stats">
                            <?php if ($course['type'] === 'video'): ?>
                                <div class="course-stat">
                                    <span>📂</span>
                                    <span><?php echo $course['module_count']; ?> Module</span>
                                </div>
                            <?php else: ?>
                                <div class="course-stat">
                                    <span>📄</span>
                                    <span>PDF-Dokument</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Buy Button -->
                        <?php if ($course['digistore_product_id']): ?>
                            <a href="https://www.digistore24.com/product/<?php echo htmlspecialchars($course['digistore_product_id']); ?>" 
                               target="_blank"
                               class="course-action btn-buy">
                                <span>🛒</span>
                                <span>Jetzt freischalten</span>
                            </a>
                        <?php else: ?>
                            <button class="course-action btn-buy" disabled style="opacity: 0.5; cursor: not-allowed;">
                                <span>🔒</span>
                                <span>Nicht verfügbar</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (count($accessible_courses) === 0 && count($locked_courses) === 0): ?>
        <div class="empty-state">
            <div class="empty-icon">📚</div>
            <div class="empty-title">Noch keine Kurse verfügbar</div>
            <div class="empty-text">
                Es wurden noch keine Kurse erstellt. Schaue später wieder vorbei!
            </div>
        </div>
    <?php endif; ?>
</div>