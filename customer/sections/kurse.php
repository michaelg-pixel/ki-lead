<?php
// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}
?>

<style>
    .courses-container {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 8px;
    }
    
    .page-subtitle {
        font-size: 14px;
        color: #888;
    }
    
    .courses-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
    }
    
    .course-card {
        background: linear-gradient(135deg, #1e1e3f 0%, #2a2a4f 100%);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 16px;
        padding: 24px;
        transition: transform 0.2s;
        cursor: pointer;
    }
    
    .course-card:hover {
        transform: translateY(-4px);
        border-color: rgba(102, 126, 234, 0.4);
    }
    
    .course-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        margin-bottom: 16px;
    }
    
    .course-title {
        font-size: 18px;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
    }
    
    .course-description {
        font-size: 14px;
        color: #aaa;
        line-height: 1.6;
        margin-bottom: 16px;
    }
    
    .course-meta {
        display: flex;
        gap: 16px;
        font-size: 12px;
        color: #888;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-icon {
        font-size: 64px;
        margin-bottom: 16px;
        opacity: 0.5;
    }
    
    .empty-title {
        font-size: 20px;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
    }
    
    .empty-text {
        font-size: 14px;
        color: #888;
    }
</style>

<div class="courses-container">
    <div class="page-header">
        <h1 class="page-title">🎓 Meine Kurse</h1>
        <p class="page-subtitle">Greifen Sie auf alle verfügbaren Kurse zu</p>
    </div>
    
    <?php
    try {
        // Verfügbare Kurse abrufen
        $stmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY created_at DESC");
        $courses = $stmt->fetchAll();
        
        if (count($courses) > 0): ?>
            <div class="courses-grid">
                <?php foreach ($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-icon">📚</div>
                        <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
                        <div class="course-description"><?php echo htmlspecialchars($course['description'] ?? 'Keine Beschreibung verfügbar'); ?></div>
                        <div class="course-meta">
                            <span>📅 <?php echo date('d.m.Y', strtotime($course['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📚</div>
                <div class="empty-title">Noch keine Kurse verfügbar</div>
                <div class="empty-text">Es wurden noch keine Kurse erstellt.</div>
            </div>
        <?php endif;
        
    } catch (PDOException $e) {
        echo '<div class="empty-state">';
        echo '<div class="empty-icon">⚠️</div>';
        echo '<div class="empty-title">Fehler beim Laden der Kurse</div>';
        echo '<div class="empty-text">Bitte versuchen Sie es später erneut.</div>';
        echo '</div>';
    }
    ?>
</div>