<?php
/**
 * Kursverwaltung - Templates Seite
 * Admin-Interface für Video-Kurse und PDF-Kurse
 */

// Kurse aus Datenbank holen
$courses = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count,
           (SELECT COUNT(*) FROM course_access WHERE course_id = c.id) as enrolled_users
    FROM courses c 
    ORDER BY c.sort_order ASC, c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="courses-container">
    <!-- Header mit Action Button -->
    <div class="courses-header">
        <div>
            <h2 style="margin: 0; font-size: 28px; color: white;">📚 Kursverwaltung</h2>
            <p style="margin: 8px 0 0; color: var(--text-secondary);">
                Verwalte deine Video-Kurse und PDF-Kurse
            </p>
        </div>
        <button onclick="showCreateCourseModal()" class="btn-primary">
            <span style="font-size: 18px;">+</span> Neuer Kurs
        </button>
    </div>

    <!-- Kurse Grid -->
    <div class="courses-grid">
        <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
                <div class="course-card" data-course-id="<?php echo $course['id']; ?>">
                    <!-- Course Thumbnail -->
                    <div class="course-thumbnail">
                        <?php if ($course['mockup_url']): ?>
                            <img src="<?php echo htmlspecialchars($course['mockup_url']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>">
                        <?php else: ?>
                            <div class="course-placeholder">
                                <span style="font-size: 48px;"><?php echo $course['type'] === 'pdf' ? '📄' : '🎥'; ?></span>
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
                        <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p class="course-description">
                            <?php echo htmlspecialchars(substr($course['description'] ?? 'Keine Beschreibung', 0, 100)); ?>
                            <?php echo strlen($course['description'] ?? '') > 100 ? '...' : ''; ?>
                        </p>
                        
                        <!-- Stats -->
                        <div class="course-stats">
                            <?php if ($course['type'] === 'video'): ?>
                                <span>📂 <?php echo $course['module_count']; ?> Module</span>
                            <?php else: ?>
                                <span>📄 PDF-Kurs</span>
                            <?php endif; ?>
                            <span>👥 <?php echo $course['enrolled_users']; ?> Nutzer</span>
                        </div>
                        
                        <?php if ($course['digistore_product_id']): ?>
                            <div class="course-digistore">
                                🛒 Digistore: <code><?php echo htmlspecialchars($course['digistore_product_id']); ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Actions -->
                    <div class="course-actions">
                        <button onclick="editCourse(<?php echo $course['id']; ?>)" class="btn-action btn-edit">
                            ✏️ Bearbeiten
                        </button>
                        <button onclick="deleteCourse(<?php echo $course['id']; ?>)" class="btn-action btn-delete">
                            🗑️ Löschen
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state-courses">
                <div style="font-size: 64px; margin-bottom: 24px;">📚</div>
                <h3 style="color: white; margin-bottom: 12px;">Noch keine Kurse vorhanden</h3>
                <p style="color: var(--text-secondary); margin-bottom: 24px;">
                    Erstelle deinen ersten Video- oder PDF-Kurs
                </p>
                <button onclick="showCreateCourseModal()" class="btn-primary">
                    + Ersten Kurs erstellen
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Course Modal -->
<div id="createCourseModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>📚 Neuer Kurs erstellen</h3>
            <button onclick="closeCreateCourseModal()" class="modal-close">&times;</button>
        </div>
        
        <form id="createCourseForm" onsubmit="handleCreateCourse(event)">
            <div class="form-grid">
                <!-- Titel -->
                <div class="form-group col-span-2">
                    <label>Kurstitel *</label>
                    <input type="text" name="title" required placeholder="z.B. KI Mastery für Anfänger">
                </div>
                
                <!-- Kurstyp -->
                <div class="form-group">
                    <label>Kurstyp *</label>
                    <select name="type" id="courseType" onchange="toggleCourseTypeFields()" required>
                        <option value="video">🎥 Video-Kurs</option>
                        <option value="pdf">📄 PDF-Kurs</option>
                    </select>
                </div>
                
                <!-- Kostenlos -->
                <div class="form-group">
                    <label>Zugang</label>
                    <div class="checkbox-wrapper">
                        <input type="checkbox" name="is_freebie" id="isFreebie">
                        <label for="isFreebie">🎁 Kostenlos (Freebie)</label>
                    </div>
                </div>
                
                <!-- Beschreibung -->
                <div class="form-group col-span-2">
                    <label>Beschreibung</label>
                    <textarea name="description" rows="4" placeholder="Beschreibe deinen Kurs..."></textarea>
                </div>
                
                <!-- Zusätzliche Informationen -->
                <div class="form-group col-span-2">
                    <label>Zusätzliche Informationen</label>
                    <textarea name="additional_info" rows="3" placeholder="Weitere Details zum Kurs..."></textarea>
                </div>
                
                <!-- Mockup URL -->
                <div class="form-group col-span-2">
                    <label>Mockup/Vorschaubild URL</label>
                    <input type="url" name="mockup_url" placeholder="https://example.com/image.jpg">
                    <small>Oder lade ein Bild hoch:</small>
                    <input type="file" name="mockup_file" accept="image/*" style="margin-top: 8px;">
                </div>
                
                <!-- PDF Upload (nur bei PDF-Kurs) -->
                <div class="form-group col-span-2" id="pdfUploadField" style="display: none;">
                    <label>PDF-Dokument hochladen *</label>
                    <input type="file" name="pdf_file" accept=".pdf">
                    <small>Das PDF wird den Kursteilnehmern zur Verfügung gestellt</small>
                </div>
                
                <!-- Digistore Product ID -->
                <div class="form-group col-span-2">
                    <label>Digistore24 Produkt-ID</label>
                    <input type="text" name="digistore_product_id" placeholder="z.B. 12345">
                    <small>Für automatische Freischaltung über Webhook</small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeCreateCourseModal()" class="btn-secondary">
                    Abbrechen
                </button>
                <button type="submit" class="btn-primary">
                    Kurs erstellen
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Courses Container */
.courses-container {
    max-width: 1400px;
    margin: 0 auto;
}

.courses-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    flex-wrap: wrap;
    gap: 16px;
}

/* Courses Grid */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
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
    overflow: hidden;
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
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.2), rgba(139, 64, 209, 0.1));
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
    padding: 20px;
    flex: 1;
}

.course-title {
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin: 0 0 12px 0;
    line-height: 1.3;
}

.course-description {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.6;
    margin: 0 0 16px 0;
}

.course-stats {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--text-secondary);
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-light);
}

.course-digistore {
    margin-top: 12px;
    font-size: 12px;
    color: var(--text-secondary);
}

.course-digistore code {
    background: rgba(168, 85, 247, 0.1);
    padding: 2px 8px;
    border-radius: 4px;
    color: var(--primary-light);
    font-family: 'Courier New', monospace;
}

/* Course Actions */
.course-actions {
    padding: 16px 20px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid var(--border-light);
    display: flex;
    gap: 12px;
}

.btn-action {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-edit {
    background: rgba(168, 85, 247, 0.1);
    color: var(--primary-light);
    border: 1px solid var(--border);
}

.btn-edit:hover {
    background: rgba(168, 85, 247, 0.2);
}

.btn-delete {
    background: rgba(251, 113, 133, 0.1);
    color: #fb7185;
    border: 1px solid rgba(251, 113, 133, 0.2);
}

.btn-delete:hover {
    background: rgba(251, 113, 133, 0.2);
}

/* Empty State */
.empty-state-courses {
    grid-column: 1 / -1;
    text-align: center;
    padding: 80px 20px;
    background: var(--bg-card);
    border: 2px dashed var(--border);
    border-radius: 16px;
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
}

.btn-secondary {
    background: var(--bg-tertiary);
    color: var(--text-primary);
    padding: 12px 24px;
    border: 1px solid var(--border);
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: var(--bg-card);
}

/* Modal */
.modal {
    display: none;
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
}

.modal.active {
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
}

.modal-large {
    max-width: 800px;
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

/* Form Styles */
.form-grid {
    padding: 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.col-span-2 {
    grid-column: span 2;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group input,
.form-group select,
.form-group textarea {
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 12px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

.form-group small {
    font-size: 12px;
    color: var(--text-muted);
}

.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-wrapper input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

/* Responsive */
@media (max-width: 768px) {
    .courses-grid {
        grid-template-columns: 1fr;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .col-span-2 {
        grid-column: span 1;
    }
}
</style>

<script>
// Modal Functions
function showCreateCourseModal() {
    document.getElementById('createCourseModal').classList.add('active');
}

function closeCreateCourseModal() {
    document.getElementById('createCourseModal').classList.remove('active');
    document.getElementById('createCourseForm').reset();
}

// Toggle fields based on course type
function toggleCourseTypeFields() {
    const courseType = document.getElementById('courseType').value;
    const pdfField = document.getElementById('pdfUploadField');
    
    if (courseType === 'pdf') {
        pdfField.style.display = 'block';
    } else {
        pdfField.style.display = 'none';
    }
}

// Create Course
async function handleCreateCourse(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch('/admin/api/courses/create.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Kurs erfolgreich erstellt!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler beim Erstellen: ' + error.message);
    }
}

// Edit Course
function editCourse(courseId) {
    window.location.href = '?page=course-edit&id=' + courseId;
}

// Delete Course
async function deleteCourse(courseId) {
    if (!confirm('Möchtest du diesen Kurs wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    try {
        const response = await fetch('/admin/api/courses/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: courseId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ Kurs erfolgreich gelöscht!');
            location.reload();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler beim Löschen: ' + error.message);
    }
}

// Close modal on outside click
document.getElementById('createCourseModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateCourseModal();
    }
});
</script>