<?php
/**
 * Kurs bearbeiten - Mit Modulen & Lektionen
 */

$course_id = $_GET['id'] ?? null;

if (!$course_id) {
    echo '<div class="alert alert-danger">Kurs-ID fehlt!</div>';
    exit;
}

// Kurs laden
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    echo '<div class="alert alert-danger">Kurs nicht gefunden!</div>';
    exit;
}

// Module & Lektionen laden (nur für Video-Kurse)
$modules = [];
if ($course['type'] === 'video') {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               (SELECT COUNT(*) FROM course_lessons WHERE module_id = m.id) as lesson_count
        FROM course_modules m 
        WHERE m.course_id = ? 
        ORDER BY m.sort_order ASC
    ");
    $stmt->execute([$course_id]);
    $modules = $stmt->fetchAll();
    
    // Lektionen für jedes Modul laden
    foreach ($modules as &$module) {
        $stmt = $pdo->prepare("
            SELECT * FROM course_lessons 
            WHERE module_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$module['id']]);
        $module['lessons'] = $stmt->fetchAll();
    }
}
?>

<div class="course-editor">
    <!-- Header -->
    <div class="editor-header">
        <div>
            <a href="?page=templates" class="back-link">← Zurück zur Übersicht</a>
            <h2 style="margin: 10px 0 8px 0; color: white; font-size: 28px;"><?php echo htmlspecialchars($course['title']); ?></h2>
            <span class="course-type-badge-inline">
                <?php echo $course['type'] === 'pdf' ? '📄 PDF-Kurs' : '🎥 Video-Kurs'; ?>
            </span>
        </div>
        <button onclick="saveCourseDetails()" class="btn-primary">
            💾 Änderungen speichern
        </button>
    </div>

    <!-- Editor Content -->
    <div class="editor-content">
        <!-- Linke Seite: Kursdetails -->
        <div class="editor-sidebar">
            <div class="sidebar-section">
                <h3>📋 Kursdetails</h3>
                
                <form id="courseDetailsForm">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                    
                    <div class="form-group">
                        <label>Kurstitel</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($course['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Beschreibung</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Zusätzliche Informationen</label>
                        <textarea name="additional_info" rows="3"><?php echo htmlspecialchars($course['additional_info'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Mockup/Vorschaubild URL</label>
                        <input type="url" name="mockup_url" value="<?php echo htmlspecialchars($course['mockup_url'] ?? ''); ?>">
                        <small style="display: block; margin-top: 4px;">Oder lade ein neues Bild hoch:</small>
                        <input type="file" name="mockup_file" accept="image/*" style="margin-top: 8px;">
                        <?php if ($course['mockup_url']): ?>
                            <img src="<?php echo htmlspecialchars($course['mockup_url']); ?>" 
                                 style="width: 100%; max-width: 200px; margin-top: 12px; border-radius: 8px; border: 1px solid rgba(168, 85, 247, 0.2);">
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($course['type'] === 'pdf'): ?>
                    <div class="form-group">
                        <label>PDF-Dokument</label>
                        <input type="file" name="pdf_file" accept=".pdf">
                        <?php if ($course['pdf_file']): ?>
                            <a href="<?php echo htmlspecialchars($course['pdf_file']); ?>" 
                               target="_blank" 
                               style="display: block; margin-top: 8px; color: var(--primary-light); text-decoration: none;">
                                📄 Aktuelles PDF ansehen
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_freebie" <?php echo $course['is_freebie'] ? 'checked' : ''; ?>>
                            🎁 Kostenloser Kurs (Freebie)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Digistore24 Produkt-ID</label>
                        <input type="text" name="digistore_product_id" 
                               value="<?php echo htmlspecialchars($course['digistore_product_id'] ?? ''); ?>"
                               placeholder="z.B. 12345">
                        <small>Für automatische Freischaltung</small>
                    </div>
                </form>
            </div>
        </div>

        <!-- Rechte Seite: Module & Lektionen (nur Video-Kurse) -->
        <div class="editor-main">
            <?php if ($course['type'] === 'video'): ?>
                <div class="modules-section">
                    <div class="section-header">
                        <h3>📚 Module & Lektionen</h3>
                        <button onclick="showAddModuleModal()" class="btn-secondary">
                            + Modul hinzufügen
                        </button>
                    </div>
                    
                    <div id="modulesList" class="modules-list">
                        <?php if (count($modules) > 0): ?>
                            <?php foreach ($modules as $module): ?>
                                <div class="module-card" data-module-id="<?php echo $module['id']; ?>">
                                    <div class="module-header">
                                        <div class="module-info">
                                            <h4><?php echo htmlspecialchars($module['title']); ?></h4>
                                            <?php if ($module['description']): ?>
                                                <p><?php echo htmlspecialchars($module['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="module-actions">
                                            <button onclick="deleteModule(<?php echo $module['id']; ?>)" class="btn-icon" title="Löschen">🗑️</button>
                                        </div>
                                    </div>
                                    
                                    <div class="lessons-list">
                                        <?php if (count($module['lessons']) > 0): ?>
                                            <?php foreach ($module['lessons'] as $lesson): ?>
                                                <div class="lesson-item" data-lesson-id="<?php echo $lesson['id']; ?>">
                                                    <div class="lesson-info">
                                                        <strong><?php echo htmlspecialchars($lesson['title']); ?></strong>
                                                        <div class="lesson-meta-row">
                                                            <?php if ($lesson['video_url']): ?>
                                                                <span class="lesson-meta">🎥 Video</span>
                                                            <?php endif; ?>
                                                            <?php if ($lesson['pdf_attachment']): ?>
                                                                <span class="lesson-meta">📄 PDF</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="lesson-actions">
                                                        <button onclick="deleteLesson(<?php echo $lesson['id']; ?>)" class="btn-icon" title="Löschen">🗑️</button>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <button onclick="showAddLessonModal(<?php echo $module['id']; ?>)" class="btn-add-lesson">
                                            + Lektion hinzufügen
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state-inline">
                                <p style="color: var(--text-secondary); margin-bottom: 16px;">Noch keine Module vorhanden</p>
                                <button onclick="showAddModuleModal()" class="btn-primary">
                                    + Erstes Modul erstellen
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="pdf-course-info">
                    <div style="text-align: center; padding: 60px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">📄</div>
                        <h3 style="color: white; margin-bottom: 12px;">PDF-Kurs</h3>
                        <p style="color: var(--text-secondary);">
                            Dieser Kurs enthält ein PDF-Dokument, das automatisch für freigeschaltete Nutzer verfügbar ist.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Module Modal -->
<div id="addModuleModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📚 Modul hinzufügen</h3>
            <button onclick="closeAddModuleModal()" class="modal-close">&times;</button>
        </div>
        <form id="addModuleForm" onsubmit="handleAddModule(event)">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            <div style="padding: 24px;">
                <div class="form-group">
                    <label>Modultitel *</label>
                    <input type="text" name="title" required placeholder="z.B. Einführung in KI">
                </div>
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3" placeholder="Optionale Beschreibung des Moduls..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeAddModuleModal()" class="btn-secondary">Abbrechen</button>
                <button type="submit" class="btn-primary">Modul erstellen</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Lesson Modal -->
<div id="addLessonModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🎥 Lektion hinzufügen</h3>
            <button onclick="closeAddLessonModal()" class="modal-close">&times;</button>
        </div>
        <form id="addLessonForm" onsubmit="handleAddLesson(event)">
            <input type="hidden" name="module_id" id="lessonModuleId">
            <div style="padding: 24px;">
                <div class="form-group">
                    <label>Lektionstitel *</label>
                    <input type="text" name="title" required placeholder="z.B. Was ist künstliche Intelligenz?">
                </div>
                <div class="form-group">
                    <label>Videolink (Vimeo oder YouTube)</label>
                    <input type="url" name="video_url" placeholder="https://vimeo.com/123456789">
                    <small>Unterstützt: Vimeo und YouTube Links</small>
                </div>
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="3" placeholder="Optionale Beschreibung der Lektion..."></textarea>
                </div>
                <div class="form-group">
                    <label>PDF-Anhang (z.B. Arbeitsblatt)</label>
                    <input type="file" name="pdf_attachment" accept=".pdf">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeAddLessonModal()" class="btn-secondary">Abbrechen</button>
                <button type="submit" class="btn-primary">Lektion erstellen</button>
            </div>
        </form>
    </div>
</div>

<style>
/* Course Editor Layout */
.course-editor {
    max-width: 1600px;
    margin: 0 auto;
}

.editor-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 20px;
}

.back-link {
    display: inline-block;
    color: var(--text-secondary);
    text-decoration: none;
    margin-bottom: 12px;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--primary-light);
}

.course-type-badge-inline {
    display: inline-block;
    background: rgba(168, 85, 247, 0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: var(--primary-light);
}

.editor-content {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 24px;
    align-items: start;
}

/* Sidebar */
.editor-sidebar {
    position: sticky;
    top: 32px;
}

.sidebar-section {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
}

.sidebar-section h3 {
    font-size: 18px;
    color: white;
    margin: 0 0 20px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 8px;
}

.form-group input[type="text"],
.form-group input[type="url"],
.form-group input[type="file"],
.form-group textarea,
.form-group select {
    width: 100%;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 10px 12px;
    color: var(--text-primary);
    font-size: 14px;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.form-group small {
    display: block;
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 4px;
}

.form-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

/* Main Content */
.editor-main {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 24px;
    min-height: 400px;
}

/* Modules */
.modules-section .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.modules-section .section-header h3 {
    font-size: 18px;
    color: white;
    margin: 0;
}

.modules-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Module Card */
.module-card {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
}

.module-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px;
    background: rgba(168, 85, 247, 0.05);
    border-bottom: 1px solid var(--border-light);
}

.module-info {
    flex: 1;
}

.module-info h4 {
    font-size: 16px;
    color: white;
    margin: 0 0 6px 0;
}

.module-info p {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0;
}

.module-actions {
    display: flex;
    gap: 8px;
}

/* Lessons */
.lessons-list {
    padding: 16px;
}

.lesson-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    margin-bottom: 8px;
}

.lesson-info {
    flex: 1;
}

.lesson-info strong {
    color: white;
    font-size: 14px;
    display: block;
    margin-bottom: 6px;
}

.lesson-meta-row {
    display: flex;
    gap: 8px;
}

.lesson-meta {
    font-size: 11px;
    color: var(--text-secondary);
    background: rgba(255, 255, 255, 0.05);
    padding: 3px 8px;
    border-radius: 12px;
}

.lesson-actions {
    display: flex;
    gap: 8px;
}

.btn-icon {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
    padding: 6px;
    border-radius: 6px;
    transition: all 0.2s;
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.1);
}

.btn-add-lesson {
    width: 100%;
    padding: 12px;
    background: rgba(168, 85, 247, 0.1);
    border: 2px dashed var(--border);
    border-radius: 8px;
    color: var(--primary-light);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    margin-top: 8px;
}

.btn-add-lesson:hover {
    background: rgba(168, 85, 247, 0.2);
    border-color: var(--primary);
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    padding: 10px 20px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: rgba(168, 85, 247, 0.1);
}

/* Empty State */
.empty-state-inline {
    text-align: center;
    padding: 60px 20px;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    z-index: 10000;
    padding: 40px 20px;
    overflow-y: auto;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.modal-content {
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: 16px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    margin-top: 60px;
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 20px;
    color: white;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: var(--text-secondary);
    cursor: pointer;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.modal-footer {
    padding: 24px;
    border-top: 1px solid var(--border-light);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 1024px) {
    .editor-content {
        grid-template-columns: 1fr;
    }
    
    .editor-sidebar {
        position: static;
    }
}
</style>

<script>
// Save Course Details
async function saveCourseDetails() {
    const formData = new FormData(document.getElementById('courseDetailsForm'));
    
    try {
        const response = await fetch('/admin/api/courses/update.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Kurs erfolgreich gespeichert!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler beim Speichern: ' + error.message);
    }
}

// Module Functions
function showAddModuleModal() {
    document.getElementById('addModuleModal').style.display = 'flex';
}

function closeAddModuleModal() {
    document.getElementById('addModuleModal').style.display = 'none';
    document.getElementById('addModuleForm').reset();
}

async function handleAddModule(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('/admin/api/courses/modules/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Modul erfolgreich erstellt!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function deleteModule(moduleId) {
    if (!confirm('Möchtest du dieses Modul wirklich löschen? Alle Lektionen werden ebenfalls gelöscht.')) {
        return;
    }
    
    try {
        const response = await fetch('/admin/api/courses/modules/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ module_id: moduleId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Modul gelöscht!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

// Lesson Functions
function showAddLessonModal(moduleId) {
    document.getElementById('lessonModuleId').value = moduleId;
    document.getElementById('addLessonModal').style.display = 'flex';
}

function closeAddLessonModal() {
    document.getElementById('addLessonModal').style.display = 'none';
    document.getElementById('addLessonForm').reset();
}

async function handleAddLesson(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('/admin/api/courses/lessons/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Lektion erfolgreich erstellt!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function deleteLesson(lessonId) {
    if (!confirm('Möchtest du diese Lektion wirklich löschen?')) {
        return;
    }
    
    try {
        const response = await fetch('/admin/api/courses/lessons/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ lesson_id: lessonId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Lektion gelöscht!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

// Close modals on outside click
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});

// Close modals on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});
</script>