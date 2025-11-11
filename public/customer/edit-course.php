<?php
/**
 * Videokurs Editor
 * Module, Lektionen, Drip Content
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

// Freebie laden
if (!isset($_GET['id'])) {
    die('Keine Freebie-ID angegeben');
}

$stmt = $pdo->prepare("
    SELECT * FROM customer_freebies 
    WHERE id = ? AND customer_id = ? AND freebie_type = 'custom'
");
$stmt->execute([$_GET['id'], $customer_id]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$freebie) {
    die('Freebie nicht gefunden');
}

// Kurs erstellen falls nicht vorhanden
$course = null;
$stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE freebie_id = ?");
$stmt->execute([$freebie['id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    $stmt = $pdo->prepare("
        INSERT INTO freebie_courses (freebie_id, title, description, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $freebie['id'], 
        $freebie['headline'] . ' - Videokurs',
        'Videokurs für ' . $freebie['headline']
    ]);
    $course_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM freebie_courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // has_course Flag setzen
    $stmt = $pdo->prepare("UPDATE customer_freebies SET has_course = 1 WHERE id = ?");
    $stmt->execute([$freebie['id']]);
}

// Module mit Lektionen laden
function loadModules($pdo, $course_id) {
    $modules = [];
    $stmt = $pdo->prepare("
        SELECT m.*, 
               l.id as lesson_id, l.title as lesson_title, 
               l.description as lesson_description,
               l.video_url, l.pdf_url, l.sort_order as lesson_order,
               l.button_text, l.button_url, l.unlock_after_days
        FROM freebie_course_modules m
        LEFT JOIN freebie_course_lessons l ON m.id = l.module_id
        WHERE m.course_id = ?
        ORDER BY m.sort_order, l.sort_order
    ");
    $stmt->execute([$course_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $mid = $row['id'];
        if (!isset($modules[$mid])) {
            $modules[$mid] = [
                'id' => $mid,
                'title' => $row['title'],
                'description' => $row['description'],
                'sort_order' => $row['sort_order'],
                'lessons' => []
            ];
        }
        if ($row['lesson_id']) {
            $modules[$mid]['lessons'][] = [
                'id' => $row['lesson_id'],
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'video_url' => $row['video_url'],
                'pdf_url' => $row['pdf_url'],
                'button_text' => $row['button_text'],
                'button_url' => $row['button_url'],
                'unlock_after_days' => $row['unlock_after_days'],
                'sort_order' => $row['lesson_order']
            ];
        }
    }
    return $modules;
}

$modules = loadModules($pdo, $course['id']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videokurs bearbeiten - <?php echo htmlspecialchars($freebie['headline']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .header h1 { color: #1a1a2e; font-size: 28px; margin-bottom: 8px; }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 16px;
        }
        
        /* Navigation */
        .nav-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }
        .nav-tab {
            flex: 1;
            padding: 16px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #666;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nav-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        /* Main Panel */
        .panel {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .panel-title { font-size: 24px; font-weight: 700; color: #1a1a2e; }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        .btn:hover { transform: translateY(-2px); }
        
        /* Module Card */
        .module-card {
            background: #f9fafb;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }
        .module-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .module-title {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            flex: 1;
        }
        .module-actions { display: flex; gap: 8px; }
        
        /* Lesson Item */
        .lessons-container { margin-top: 16px; }
        .lesson-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .lesson-info { flex: 1; }
        .lesson-title {
            font-weight: 600;
            color: #1a1a2e;
            margin-bottom: 6px;
            font-size: 15px;
        }
        .lesson-meta {
            font-size: 12px;
            color: #9ca3af;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .lesson-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-video { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        .badge-pdf { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
        .badge-button { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
        .badge-drip { background: rgba(251, 146, 60, 0.1); color: #ea580c; }
        
        .lesson-actions { display: flex; gap: 8px; }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-state-icon { font-size: 64px; margin-bottom: 16px; }
        .empty-state-text { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .empty-state-hint { font-size: 14px; }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #1a1a2e;
        }
        .modal-body { margin-bottom: 24px; }
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        /* Form */
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #8B5CF6;
        }
        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* Alert */
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            color: #047857;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        @media (max-width: 768px) {
            .module-header, .lesson-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <a href="/customer/dashboard.php?page=freebies" class="back-link">← Zurück</a>
        <h1>🎓 Videokurs bearbeiten</h1>
        <p><?php echo htmlspecialchars($freebie['headline']); ?></p>
    </div>
    
    <!-- Navigation -->
    <div class="nav-tabs">
        <a href="edit-freebie.php?id=<?php echo $freebie['id']; ?>" class="nav-tab">⚙️ Einstellungen</a>
        <a href="edit-course.php?id=<?php echo $freebie['id']; ?>" class="nav-tab active">🎓 Videokurs</a>
    </div>
    
    <div id="alertContainer"></div>
    
    <!-- Main Panel -->
    <div class="panel">
        <div class="panel-header">
            <h2 class="panel-title">Module & Lektionen</h2>
            <button class="btn btn-primary" onclick="openModuleModal()">
                ➕ Modul hinzufügen
            </button>
        </div>
        
        <div id="modulesContainer">
            <?php if (empty($modules)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📚</div>
                    <div class="empty-state-text">Noch keine Module vorhanden</div>
                    <div class="empty-state-hint">Erstelle dein erstes Modul, um Lektionen hinzuzufügen</div>
                </div>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                <div class="module-card" data-module-id="<?php echo $module['id']; ?>">
                    <div class="module-header">
                        <div class="module-title">
                            📖 <?php echo htmlspecialchars($module['title']); ?>
                        </div>
                        <div class="module-actions">
                            <button class="btn btn-secondary btn-small" onclick="openLessonModal(<?php echo $module['id']; ?>)">
                                ➕ Lektion
                            </button>
                            <button class="btn btn-secondary btn-small" onclick="editModule(<?php echo $module['id']; ?>, '<?php echo addslashes($module['title']); ?>', '<?php echo addslashes($module['description'] ?? ''); ?>')">
                                ✏️
                            </button>
                            <button class="btn btn-secondary btn-small" onclick="deleteModule(<?php echo $module['id']; ?>)">
                                🗑️
                            </button>
                        </div>
                    </div>
                    
                    <?php if (!empty($module['description'])): ?>
                        <p style="color: #6b7280; margin-bottom: 16px; font-size: 14px;">
                            <?php echo htmlspecialchars($module['description']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <div class="lessons-container">
                        <?php if (empty($module['lessons'])): ?>
                            <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 14px;">
                                Keine Lektionen vorhanden
                            </div>
                        <?php else: ?>
                            <?php foreach ($module['lessons'] as $lesson): ?>
                            <div class="lesson-item" data-lesson-id="<?php echo $lesson['id']; ?>">
                                <div class="lesson-info">
                                    <div class="lesson-title">
                                        <?php echo htmlspecialchars($lesson['title']); ?>
                                    </div>
                                    <div class="lesson-meta">
                                        <?php if (!empty($lesson['video_url'])): ?>
                                            <span class="lesson-badge badge-video">🎥 Video</span>
                                        <?php endif; ?>
                                        <?php if (!empty($lesson['pdf_url'])): ?>
                                            <span class="lesson-badge badge-pdf">📄 PDF</span>
                                        <?php endif; ?>
                                        <?php if (!empty($lesson['button_text'])): ?>
                                            <span class="lesson-badge badge-button">🔘 Button</span>
                                        <?php endif; ?>
                                        <?php if ($lesson['unlock_after_days'] > 0): ?>
                                            <span class="lesson-badge badge-drip">🔒 Freischaltung: <?php echo $lesson['unlock_after_days']; ?> Tage</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="lesson-actions">
                                    <button class="btn btn-secondary btn-small" 
                                            onclick='editLesson(<?php echo json_encode($lesson); ?>)'>
                                        ✏️
                                    </button>
                                    <button class="btn btn-secondary btn-small" 
                                            onclick="deleteLesson(<?php echo $lesson['id']; ?>)">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Modul erstellen/bearbeiten -->
<div id="moduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="moduleModalTitle">Neues Modul</div>
        <div class="modal-body">
            <input type="hidden" id="moduleId" value="">
            <div class="form-group">
                <label class="form-label">Modul-Titel *</label>
                <input type="text" id="moduleTitle" class="form-input" 
                       placeholder="z.B. Modul 1: Grundlagen" required>
            </div>
            <div class="form-group">
                <label class="form-label">Beschreibung (optional)</label>
                <textarea id="moduleDescription" class="form-textarea" 
                          placeholder="Kurze Beschreibung des Moduls"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModuleModal()">Abbrechen</button>
            <button class="btn btn-primary" onclick="saveModule()">Speichern</button>
        </div>
    </div>
</div>

<!-- Modal: Lektion erstellen/bearbeiten -->
<div id="lessonModal" class="modal">
    <div class="modal-content">
        <div class="modal-header" id="lessonModalTitle">Neue Lektion</div>
        <div class="modal-body">
            <input type="hidden" id="lessonId" value="">
            <input type="hidden" id="lessonModuleId" value="">
            
            <div class="form-group">
                <label class="form-label">Lektions-Titel *</label>
                <input type="text" id="lessonTitle" class="form-input" 
                       placeholder="z.B. Lektion 1: Einführung" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Beschreibung (optional)</label>
                <textarea id="lessonDescription" class="form-textarea" 
                          placeholder="Beschreibung der Lektion"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Video URL (optional)</label>
                <input type="url" id="lessonVideoUrl" class="form-input" 
                       placeholder="https://www.youtube.com/watch?v=...">
                <div class="form-hint">YouTube, Vimeo, oder andere Video-Plattformen</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">PDF URL (optional)</label>
                <input type="url" id="lessonPdfUrl" class="form-input" 
                       placeholder="https://example.com/dokument.pdf">
                <div class="form-hint">Link zu einem PDF-Dokument</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Button Text (optional)</label>
                <input type="text" id="lessonButtonText" class="form-input" 
                       placeholder="z.B. Jetzt kaufen">
            </div>
            
            <div class="form-group">
                <label class="form-label">Button URL (optional)</label>
                <input type="url" id="lessonButtonUrl" class="form-input" 
                       placeholder="https://...">
            </div>
            
            <div class="form-group">
                <label class="form-label">Freischaltung nach X Tagen</label>
                <input type="number" id="lessonUnlockDays" class="form-input" 
                       value="0" min="0" max="365">
                <div class="form-hint">0 = sofort verfügbar, 1+ = Drip Content (verzögerte Freischaltung)</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeLessonModal()">Abbrechen</button>
            <button class="btn btn-primary" onclick="saveLesson()">Speichern</button>
        </div>
    </div>
</div>

<script>
const courseId = <?php echo $course['id']; ?>;

// Module Modal
function openModuleModal(id = null, title = '', description = '') {
    document.getElementById('moduleId').value = id || '';
    document.getElementById('moduleTitle').value = title;
    document.getElementById('moduleDescription').value = description;
    document.getElementById('moduleModalTitle').textContent = id ? 'Modul bearbeiten' : 'Neues Modul';
    document.getElementById('moduleModal').classList.add('active');
}

function closeModuleModal() {
    document.getElementById('moduleModal').classList.remove('active');
}

function editModule(id, title, description) {
    openModuleModal(id, title, description);
}

async function saveModule() {
    const id = document.getElementById('moduleId').value;
    const title = document.getElementById('moduleTitle').value.trim();
    const description = document.getElementById('moduleDescription').value.trim();
    
    if (!title) {
        showAlert('Bitte Titel eingeben', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/course-modules.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: id ? 'update' : 'create',
                id: id || undefined,
                course_id: courseId,
                title,
                description
            })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert(result.message, 'success');
            closeModuleModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Fehler beim Speichern: ' + error.message, 'error');
    }
}

async function deleteModule(id) {
    if (!confirm('Modul wirklich löschen? Alle Lektionen werden ebenfalls gelöscht!')) return;
    
    try {
        const response = await fetch('/api/course-modules.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Fehler beim Löschen: ' + error.message, 'error');
    }
}

// Lesson Modal
function openLessonModal(moduleId, lesson = null) {
    document.getElementById('lessonId').value = lesson?.id || '';
    document.getElementById('lessonModuleId').value = moduleId;
    document.getElementById('lessonTitle').value = lesson?.title || '';
    document.getElementById('lessonDescription').value = lesson?.description || '';
    document.getElementById('lessonVideoUrl').value = lesson?.video_url || '';
    document.getElementById('lessonPdfUrl').value = lesson?.pdf_url || '';
    document.getElementById('lessonButtonText').value = lesson?.button_text || '';
    document.getElementById('lessonButtonUrl').value = lesson?.button_url || '';
    document.getElementById('lessonUnlockDays').value = lesson?.unlock_after_days || 0;
    document.getElementById('lessonModalTitle').textContent = lesson ? 'Lektion bearbeiten' : 'Neue Lektion';
    document.getElementById('lessonModal').classList.add('active');
}

function closeLessonModal() {
    document.getElementById('lessonModal').classList.remove('active');
}

function editLesson(lesson) {
    openLessonModal(lesson.module_id || document.getElementById('lessonModuleId').value, lesson);
}

async function saveLesson() {
    const id = document.getElementById('lessonId').value;
    const moduleId = document.getElementById('lessonModuleId').value;
    const title = document.getElementById('lessonTitle').value.trim();
    const description = document.getElementById('lessonDescription').value.trim();
    const videoUrl = document.getElementById('lessonVideoUrl').value.trim();
    const pdfUrl = document.getElementById('lessonPdfUrl').value.trim();
    const buttonText = document.getElementById('lessonButtonText').value.trim();
    const buttonUrl = document.getElementById('lessonButtonUrl').value.trim();
    const unlockDays = parseInt(document.getElementById('lessonUnlockDays').value) || 0;
    
    if (!title) {
        showAlert('Bitte Titel eingeben', 'error');
        return;
    }
    
    try {
        const response = await fetch('/api/course-lessons.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: id ? 'update' : 'create',
                id: id || undefined,
                module_id: moduleId,
                title,
                description,
                video_url: videoUrl,
                pdf_url: pdfUrl,
                button_text: buttonText,
                button_url: buttonUrl,
                unlock_after_days: unlockDays
            })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert(result.message, 'success');
            closeLessonModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Fehler beim Speichern: ' + error.message, 'error');
    }
}

async function deleteLesson(id) {
    if (!confirm('Lektion wirklich löschen?')) return;
    
    try {
        const response = await fetch('/api/course-lessons.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const result = await response.json();
        if (result.success) {
            showAlert(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(result.message, 'error');
        }
    } catch (error) {
        showAlert('Fehler beim Löschen: ' + error.message, 'error');
    }
}

// Alert System
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <span style="font-size: 20px;">${type === 'success' ? '✅' : '❌'}</span>
        <span>${message}</span>
    `;
    
    const container = document.getElementById('alertContainer');
    container.appendChild(alert);
    
    setTimeout(() => alert.remove(), 5000);
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}
</script>
</body>
</html>