<?php
/**
 * Kurs bearbeiten - Mit Modulen & Lektionen
 * ERWEITERT: Drip Content & Multi-Video Support
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
    foreach ($modules as $key => $module) {
        $stmt = $pdo->prepare("
            SELECT * FROM course_lessons 
            WHERE module_id = ? 
            ORDER BY sort_order ASC
        ");
        $stmt->execute([$module['id']]);
        $modules[$key]['lessons'] = $stmt->fetchAll();
        
        // NEU: Zusätzliche Videos für jede Lektion laden
        foreach ($modules[$key]['lessons'] as $lkey => $lesson) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as video_count 
                FROM lesson_videos 
                WHERE lesson_id = ?
            ");
            $stmt->execute([$lesson['id']]);
            $videoCount = $stmt->fetchColumn();
            $modules[$key]['lessons'][$lkey]['additional_video_count'] = $videoCount;
        }
    }
}

// Debug-Ausgabe (optional)
if (isset($_GET['debug'])) {
    echo '<div style="background: #1a1a2e; padding: 20px; border-radius: 8px; margin: 20px 0; font-family: monospace; color: #0f0; border: 2px solid #a855f7;">';
    echo '<h3 style="color: #a855f7; margin: 0 0 15px 0;">🔍 DEBUG INFO</h3>';
    echo '<strong>Kurs-ID:</strong> ' . $course_id . '<br>';
    echo '<strong>Kurs-Typ:</strong> ' . $course['type'] . '<br>';
    echo '<strong>Anzahl geladene Module:</strong> ' . count($modules) . '<br><br>';
    
    // Direkte Datenbankabfrage ohne JOIN
    $directStmt = $pdo->prepare("SELECT id, title, description, sort_order, created_at FROM course_modules WHERE course_id = ? ORDER BY sort_order ASC");
    $directStmt->execute([$course_id]);
    $directModules = $directStmt->fetchAll();
    
    echo '<strong>Module in Datenbank (direkte Abfrage):</strong><br>';
    echo '<table style="width: 100%; color: #0f0; margin-top: 10px; border-collapse: collapse;">';
    echo '<tr style="background: rgba(168, 85, 247, 0.2);"><th style="padding: 8px; text-align: left;">ID</th><th style="padding: 8px; text-align: left;">Titel</th><th style="padding: 8px; text-align: left;">Sort Order</th><th style="padding: 8px; text-align: left;">Erstellt am</th></tr>';
    foreach ($directModules as $dm) {
        echo '<tr><td style="padding: 8px;">' . $dm['id'] . '</td><td style="padding: 8px;">' . htmlspecialchars($dm['title']) . '</td><td style="padding: 8px;">' . $dm['sort_order'] . '</td><td style="padding: 8px;">' . $dm['created_at'] . '</td></tr>';
    }
    echo '</table><br>';
    
    echo '<strong>Module im PHP-Array ($modules):</strong><br>';
    echo '<pre style="background: #0a0a0a; padding: 10px; border-radius: 4px; overflow: auto; max-height: 300px;">';
    print_r($modules);
    echo '</pre>';
    echo '</div>';
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
            <?php if (!isset($_GET['debug'])): ?>
                <a href="<?php echo $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'debug=1'; ?>" 
                   style="display: inline-block; margin-left: 10px; padding: 6px 12px; background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 6px; text-decoration: none; font-size: 13px;">
                    🔍 Debug-Modus
                </a>
            <?php endif; ?>
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
                                 style="width: 100%; max-width: 200px; margin-top: 12px; border-radius: 8px; border: 1px solid rgba(168, 85, 247, 0.3);">
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($course['type'] === 'pdf'): ?>
                    <div class="form-group">
                        <label>PDF-Dokument</label>
                        <input type="file" name="pdf_file" accept=".pdf">
                        <?php if ($course['pdf_file']): ?>
                            <a href="<?php echo htmlspecialchars($course['pdf_file']); ?>" 
                               target="_blank" 
                               style="display: block; margin-top: 8px; color: #c084fc; text-decoration: none;">
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
                        <h3>📚 Module & Lektionen (<?php echo count($modules); ?>)</h3>
                        <button onclick="showAddModuleModal()" class="btn-secondary">
                            + Modul hinzufügen
                        </button>
                    </div>
                    
                    <div id="modulesList" class="modules-list">
                        <?php if (count($modules) > 0): ?>
                            <?php foreach ($modules as $index => $module): ?>
                                <div class="module-card" data-module-id="<?php echo $module['id']; ?>" data-index="<?php echo $index; ?>">
                                    <div class="module-header">
                                        <div class="module-info">
                                            <h4>
                                                <span style="color: #c084fc; margin-right: 8px;">#<?php echo ($index + 1); ?></span>
                                                <?php echo htmlspecialchars($module['title']); ?>
                                                <span style="font-size: 11px; color: #a0a0a0; margin-left: 8px;">(ID: <?php echo $module['id']; ?>)</span>
                                            </h4>
                                            <?php if ($module['description']): ?>
                                                <p><?php echo htmlspecialchars($module['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="module-actions">
                                            <button onclick='showEditModuleModal(<?php echo json_encode($module); ?>)' class="btn-icon" title="Bearbeiten">✏️</button>
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
                                                            <!-- NEU: Drip Content Badge -->
                                                            <?php if (isset($lesson['unlock_after_days']) && $lesson['unlock_after_days'] !== null && $lesson['unlock_after_days'] > 0): ?>
                                                                <span class="lesson-meta" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.4); color: #fbbf24;">
                                                                    🔒 Tag <?php echo $lesson['unlock_after_days']; ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($lesson['video_url']): ?>
                                                                <span class="lesson-meta">🎥 Video</span>
                                                            <?php endif; ?>
                                                            
                                                            <!-- NEU: Zusätzliche Videos Badge -->
                                                            <?php if ($lesson['additional_video_count'] > 0): ?>
                                                                <span class="lesson-meta">🎬 +<?php echo $lesson['additional_video_count']; ?> Videos</span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($lesson['pdf_attachment']): ?>
                                                                <span class="lesson-meta">📄 PDF</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="lesson-actions">
                                                        <button onclick='showEditLessonModal(<?php echo json_encode($lesson); ?>)' class="btn-icon" title="Bearbeiten">✏️</button>
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
                                <p style="color: #a0a0a0; margin-bottom: 16px;">Noch keine Module vorhanden</p>
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
                        <p style="color: #a0a0a0;">
                            Dieser Kurs enthält ein PDF-Dokument, das automatisch für freigeschaltete Nutzer verfügbar ist.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Module Modal -->
<div id="addModuleModal" class="modal hidden">
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

<!-- Edit Module Modal -->
<div id="editModuleModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Modul bearbeiten</h3>
            <button onclick="closeEditModuleModal()" class="modal-close">&times;</button>
        </div>
        <form id="editModuleForm" onsubmit="handleEditModule(event)">
            <input type="hidden" name="module_id" id="editModuleId">
            <div style="padding: 24px;">
                <div class="form-group">
                    <label>Modultitel *</label>
                    <input type="text" name="title" id="editModuleTitle" required placeholder="z.B. Einführung in KI">
                </div>
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="description" id="editModuleDescription" rows="3" placeholder="Optionale Beschreibung des Moduls..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeEditModuleModal()" class="btn-secondary">Abbrechen</button>
                <button type="submit" class="btn-primary">Änderungen speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Lesson Modal - ERWEITERT -->
<div id="addLessonModal" class="modal hidden">
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
                
                <!-- NEU: Drip Content -->
                <div class="form-group">
                    <label>⏰ Freischaltung nach X Tagen (optional)</label>
                    <input type="number" name="unlock_after_days" min="0" placeholder="Leer = sofort, 7 = nach 7 Tagen">
                    <small>Gib die Anzahl der Tage an (0 oder leer = sofort verfügbar)</small>
                </div>
                
                <div class="form-group">
                    <label>Hauptvideo (Vimeo oder YouTube)</label>
                    <input type="url" name="video_url" placeholder="https://vimeo.com/123456789">
                    <small>Unterstützt: Vimeo und YouTube Links</small>
                </div>
                
                <!-- NEU: Zusätzliche Videos -->
                <div class="form-group">
                    <label>🎬 Zusätzliche Videos (optional)</label>
                    <div id="additionalVideos" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Videos werden hier dynamisch hinzugefügt -->
                    </div>
                    <button type="button" onclick="addVideoField('add')" class="btn-secondary" style="margin-top: 8px; width: 100%;">
                        + Weiteres Video hinzufügen
                    </button>
                    <small>Du kannst beliebig viele Videos zu dieser Lektion hinzufügen</small>
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

<!-- Edit Lesson Modal - ERWEITERT -->
<div id="editLessonModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Lektion bearbeiten</h3>
            <button onclick="closeEditLessonModal()" class="modal-close">&times;</button>
        </div>
        <form id="editLessonForm" onsubmit="handleEditLesson(event)">
            <input type="hidden" name="lesson_id" id="editLessonId">
            <div style="padding: 24px;">
                <div class="form-group">
                    <label>Lektionstitel *</label>
                    <input type="text" name="title" id="editLessonTitle" required placeholder="z.B. Was ist künstliche Intelligenz?">
                </div>
                
                <!-- NEU: Drip Content -->
                <div class="form-group">
                    <label>⏰ Freischaltung nach X Tagen (optional)</label>
                    <input type="number" name="unlock_after_days" id="editLessonUnlockDays" min="0" placeholder="Leer = sofort">
                    <small>Gib die Anzahl der Tage an (0 oder leer = sofort verfügbar)</small>
                </div>
                
                <div class="form-group">
                    <label>Hauptvideo (Vimeo oder YouTube)</label>
                    <input type="url" name="video_url" id="editLessonVideoUrl" placeholder="https://vimeo.com/123456789">
                    <small>Unterstützt: Vimeo und YouTube Links</small>
                </div>
                
                <!-- NEU: Zusätzliche Videos -->
                <div class="form-group">
                    <label>🎬 Zusätzliche Videos (optional)</label>
                    <div id="editAdditionalVideos" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Videos werden hier dynamisch hinzugefügt -->
                    </div>
                    <button type="button" onclick="addVideoField('edit')" class="btn-secondary" style="margin-top: 8px; width: 100%;">
                        + Weiteres Video hinzufügen
                    </button>
                </div>
                
                <div class="form-group">
                    <label>Beschreibung</label>
                    <textarea name="description" id="editLessonDescription" rows="3" placeholder="Optionale Beschreibung der Lektion..."></textarea>
                </div>
                <div class="form-group">
                    <label>PDF-Anhang (z.B. Arbeitsblatt)</label>
                    <input type="file" name="pdf_attachment" accept=".pdf">
                    <small id="editLessonCurrentPdf" style="display: none; margin-top: 4px;">
                        Aktuell: <span id="editLessonPdfName"></span> (Leer lassen zum Behalten)
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeEditLessonModal()" class="btn-secondary">Abbrechen</button>
                <button type="submit" class="btn-primary">Änderungen speichern</button>
            </div>
        </form>
    </div>
</div>

<style>
.course-editor { max-width: 1600px; margin: 0 auto; }
.editor-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; flex-wrap: wrap; gap: 20px; }
.back-link { display: inline-block; color: #a0a0a0; text-decoration: none; margin-bottom: 12px; transition: color 0.2s; }
.back-link:hover { color: #c084fc; }
.course-type-badge-inline { display: inline-block; background: rgba(168, 85, 247, 0.2); border: 1px solid rgba(168, 85, 247, 0.4); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #c084fc; }
.editor-content { display: grid; grid-template-columns: 380px 1fr; gap: 24px; align-items: start; }
.editor-sidebar { position: sticky; top: 32px; }
.sidebar-section { background: rgba(26, 26, 46, 0.7); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 12px; padding: 24px; }
.sidebar-section h3 { font-size: 18px; color: white; font-weight: 700; margin: 0 0 20px 0; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 13px; font-weight: 600; color: #c084fc; margin-bottom: 8px; letter-spacing: 0.3px; }
.form-group input[type="text"], .form-group input[type="url"], .form-group input[type="number"], .form-group input[type="file"], .form-group textarea, .form-group select { width: 100%; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px; font-family: inherit; transition: all 0.2s ease; }
.form-group input::placeholder, .form-group textarea::placeholder { color: #a0a0a0; }
.form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; background: rgba(26, 26, 46, 0.95); border-color: rgba(168, 85, 247, 0.6); box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.2); }
.form-group small { display: block; font-size: 12px; color: #a0a0a0; margin-top: 4px; }
.form-group input[type="checkbox"] { width: 18px; height: 18px; accent-color: #a855f7; cursor: pointer; }
.editor-main { background: rgba(26, 26, 46, 0.7); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 12px; padding: 24px; min-height: 400px; }
.modules-section .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.modules-section .section-header h3 { font-size: 18px; color: white; font-weight: 700; margin: 0; }
.modules-list { display: flex; flex-direction: column; gap: 20px; }
.module-card { background: rgba(22, 33, 62, 0.8); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 12px; overflow: hidden; transition: all 0.2s ease; }
.module-card:hover { border-color: rgba(168, 85, 247, 0.4); }
.module-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 20px; background: rgba(168, 85, 247, 0.08); border-bottom: 1px solid rgba(168, 85, 247, 0.2); }
.module-info { flex: 1; }
.module-info h4 { font-size: 16px; color: white; font-weight: 700; margin: 0 0 6px 0; }
.module-info p { font-size: 13px; color: #b0b0b0; margin: 0; }
.module-actions { display: flex; gap: 8px; }
.lessons-list { padding: 16px; }
.lesson-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px; background: rgba(15, 15, 30, 0.9); border: 1px solid rgba(168, 85, 247, 0.2); border-radius: 8px; margin-bottom: 8px; transition: all 0.2s ease; }
.lesson-item:hover { border-color: rgba(168, 85, 247, 0.4); background: rgba(15, 15, 30, 1); }
.lesson-info { flex: 1; }
.lesson-info strong { color: #e0e0e0; font-size: 14px; font-weight: 600; display: block; margin-bottom: 6px; }
.lesson-meta-row { display: flex; gap: 8px; flex-wrap: wrap; }
.lesson-meta { font-size: 11px; color: #c084fc; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); padding: 3px 10px; border-radius: 12px; font-weight: 600; }
.lesson-actions { display: flex; gap: 8px; }
.btn-icon { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(168, 85, 247, 0.3); font-size: 16px; cursor: pointer; padding: 8px; border-radius: 6px; transition: all 0.2s; }
.btn-icon:hover { background: rgba(168, 85, 247, 0.2); border-color: rgba(168, 85, 247, 0.5); transform: translateY(-2px); }
.btn-add-lesson { width: 100%; padding: 12px; background: rgba(168, 85, 247, 0.1); border: 2px dashed rgba(168, 85, 247, 0.4); border-radius: 8px; color: #c084fc; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 8px; }
.btn-add-lesson:hover { background: rgba(168, 85, 247, 0.2); border-color: rgba(168, 85, 247, 0.6); color: #e9d5ff; }
.btn-primary { background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%); color: white; padding: 12px 24px; border: 1px solid rgba(168, 85, 247, 0.5); border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3); }
.btn-primary:hover { background: linear-gradient(135deg, #9333ea 0%, #db2777 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(168, 85, 247, 0.5); }
.btn-secondary { background: rgba(59, 130, 246, 0.2); color: #60a5fa; padding: 10px 20px; border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { background: rgba(59, 130, 246, 0.3); border-color: rgba(59, 130, 246, 0.6); transform: translateY(-2px); }
.empty-state-inline { text-align: center; padding: 60px 20px; }
.modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px); z-index: 10000; padding: 40px 20px; overflow-y: auto; display: flex; align-items: flex-start; justify-content: center; }
.modal.hidden { display: none !important; }
.modal-content { background: rgba(26, 26, 46, 0.95); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 16px; width: 100%; max-width: 600px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5); margin-top: 60px; }
.modal-header { padding: 24px; border-bottom: 1px solid rgba(168, 85, 247, 0.2); display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { font-size: 20px; color: white; font-weight: 700; margin: 0; }
.modal-close { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(168, 85, 247, 0.3); font-size: 28px; color: #a0a0a0; cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s; }
.modal-close:hover { background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #f87171; }
.modal-footer { padding: 24px; border-top: 1px solid rgba(168, 85, 247, 0.2); display: flex; gap: 12px; justify-content: flex-end; }
@media (max-width: 1024px) { .editor-content { grid-template-columns: 1fr; } .editor-sidebar { position: static; } }
</style>

<script>
// NEU: Globaler Counter für Video-Felder
let videoFieldCounter = 0;

// NEU: Video-Feld hinzufügen
function addVideoField(context) {
    videoFieldCounter++;
    const containerId = context === 'add' ? 'additionalVideos' : 'editAdditionalVideos';
    const container = document.getElementById(containerId);
    
    const videoField = document.createElement('div');
    videoField.className = 'video-field-row';
    videoField.style.cssText = 'display: flex; gap: 8px; align-items: center;';
    videoField.innerHTML = `
        <input type="text" 
               name="video_titles[]" 
               placeholder="Video Titel" 
               style="width: 180px; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;">
        <input type="url" 
               name="video_urls[]" 
               placeholder="https://vimeo.com/..." 
               style="flex: 1; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;">
        <button type="button" 
                onclick="this.parentElement.remove()" 
                class="btn-icon" 
                style="padding: 10px 12px;">
            🗑️
        </button>
    `;
    container.appendChild(videoField);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function reloadWithCacheBust() {
    const url = new URL(window.location.href);
    url.searchParams.delete('t');
    url.searchParams.set('t', Date.now().toString());
    window.location.href = url.toString();
}

async function saveCourseDetails() {
    const formData = new FormData(document.getElementById('courseDetailsForm'));
    try {
        const response = await fetch('/admin/api/courses/update.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            alert('✅ Kurs erfolgreich gespeichert!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler beim Speichern: ' + error.message);
    }
}

function showAddModuleModal() { document.getElementById('addModuleModal').classList.remove('hidden'); }
function closeAddModuleModal() { document.getElementById('addModuleModal').classList.add('hidden'); document.getElementById('addModuleForm').reset(); }
function showEditModuleModal(module) { document.getElementById('editModuleId').value = module.id; document.getElementById('editModuleTitle').value = module.title; document.getElementById('editModuleDescription').value = module.description || ''; document.getElementById('editModuleModal').classList.remove('hidden'); }
function closeEditModuleModal() { document.getElementById('editModuleModal').classList.add('hidden'); document.getElementById('editModuleForm').reset(); }

async function handleAddModule(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    console.log('📝 Modul wird erstellt...', 'Course ID:', formData.get('course_id'), 'Title:', formData.get('title'));
    try {
        const response = await fetch('/admin/api/courses/modules/create.php', { method: 'POST', body: formData });
        const data = await response.json();
        console.log('📦 API Response:', data);
        if (data.success) {
            console.log('✅ Modul erstellt! ID:', data.module_id, 'Sort:', data.sort_order, 'Total:', data.total_modules);
            alert('✅ Modul erfolgreich erstellt!\n\nModule ID: ' + data.module_id + '\nSort Order: ' + data.sort_order + '\nTotal: ' + data.total_modules);
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function handleEditModule(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
        const response = await fetch('/admin/api/courses/modules/update.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            alert('✅ Modul erfolgreich aktualisiert!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function deleteModule(moduleId) {
    if (!confirm('Möchtest du dieses Modul wirklich löschen? Alle Lektionen werden ebenfalls gelöscht.')) return;
    try {
        const response = await fetch('/admin/api/courses/modules/delete.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ module_id: moduleId }) });
        const data = await response.json();
        if (data.success) {
            alert('✅ Modul gelöscht!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

function showAddLessonModal(moduleId) { 
    document.getElementById('lessonModuleId').value = moduleId; 
    document.getElementById('additionalVideos').innerHTML = ''; // Reset videos
    document.getElementById('addLessonModal').classList.remove('hidden'); 
}

function closeAddLessonModal() { 
    document.getElementById('addLessonModal').classList.add('hidden'); 
    document.getElementById('addLessonForm').reset(); 
    document.getElementById('additionalVideos').innerHTML = '';
}

// NEU: Erweiterte showEditLessonModal Funktion
function showEditLessonModal(lesson) { 
    document.getElementById('editLessonId').value = lesson.id; 
    document.getElementById('editLessonTitle').value = lesson.title; 
    document.getElementById('editLessonVideoUrl').value = lesson.video_url || ''; 
    document.getElementById('editLessonDescription').value = lesson.description || ''; 
    
    // NEU: Drip Content Wert setzen
    document.getElementById('editLessonUnlockDays').value = lesson.unlock_after_days || '';
    
    const pdfInfo = document.getElementById('editLessonCurrentPdf'); 
    const pdfName = document.getElementById('editLessonPdfName'); 
    if (lesson.pdf_attachment) { 
        pdfName.textContent = lesson.pdf_attachment; 
        pdfInfo.style.display = 'block'; 
    } else { 
        pdfInfo.style.display = 'none'; 
    } 
    
    // NEU: Zusätzliche Videos laden
    const editVideosContainer = document.getElementById('editAdditionalVideos');
    editVideosContainer.innerHTML = '';
    
    // Lade zusätzliche Videos aus Datenbank
    fetch('/admin/api/courses/lessons/get-videos.php?lesson_id=' + lesson.id)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.videos && data.videos.length > 0) {
                data.videos.forEach(video => {
                    videoFieldCounter++;
                    const videoField = document.createElement('div');
                    videoField.className = 'video-field-row';
                    videoField.style.cssText = 'display: flex; gap: 8px; align-items: center;';
                    videoField.innerHTML = `
                        <input type="text" 
                               name="video_titles[]" 
                               value="${escapeHtml(video.video_title)}" 
                               placeholder="Video Titel" 
                               style="width: 180px; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;">
                        <input type="url" 
                               name="video_urls[]" 
                               value="${escapeHtml(video.video_url)}" 
                               placeholder="https://vimeo.com/..." 
                               style="flex: 1; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;">
                        <button type="button" 
                                onclick="this.parentElement.remove()" 
                                class="btn-icon" 
                                style="padding: 10px 12px;">
                            🗑️
                        </button>
                    `;
                    editVideosContainer.appendChild(videoField);
                });
            }
        })
        .catch(error => console.error('Fehler beim Laden der Videos:', error));
    
    document.getElementById('editLessonModal').classList.remove('hidden'); 
}

function closeEditLessonModal() { 
    document.getElementById('editLessonModal').classList.add('hidden'); 
    document.getElementById('editLessonForm').reset(); 
    document.getElementById('editAdditionalVideos').innerHTML = '';
}

async function handleAddLesson(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
        const response = await fetch('/admin/api/courses/lessons/create.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            alert('✅ Lektion erfolgreich erstellt!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function handleEditLesson(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    try {
        const response = await fetch('/admin/api/courses/lessons/update.php', { method: 'POST', body: formData });
        const data = await response.json();
        if (data.success) {
            alert('✅ Lektion erfolgreich aktualisiert!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

async function deleteLesson(lessonId) {
    if (!confirm('Möchtest du diese Lektion wirklich löschen?')) return;
    try {
        const response = await fetch('/admin/api/courses/lessons/delete.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ lesson_id: lessonId }) });
        const data = await response.json();
        if (data.success) {
            alert('✅ Lektion gelöscht!');
            reloadWithCacheBust();
        } else {
            alert('❌ Fehler: ' + data.error);
        }
    } catch (error) {
        alert('❌ Fehler: ' + error.message);
    }
}

document.querySelectorAll('.modal').forEach(modal => { modal.addEventListener('click', function(e) { if (e.target === this) this.classList.add('hidden'); }); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') document.querySelectorAll('.modal').forEach(modal => modal.classList.add('hidden')); });
</script>
