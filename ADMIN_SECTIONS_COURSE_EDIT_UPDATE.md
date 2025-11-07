# 🎯 Drip Content & Multi-Video: Admin Sections Update

## Problem
Die Datei `admin/sections/course-edit.php` nutzt ein anderes System mit API-Endpoints und muss separat angepasst werden.

## Lösung: Manuelle Änderungen

Da diese Datei komplex ist und API-Endpoints verwendet, hier die notwendigen Änderungen:

---

### 1. Lektion-Anzeige erweitern (Zeile ~220)

**Suche nach:**
```php
<div class="lesson-meta-row">
    <?php if ($lesson['video_url']): ?>
        <span class="lesson-meta">🎥 Video</span>
    <?php endif; ?>
    <?php if ($lesson['pdf_attachment']): ?>
        <span class="lesson-meta">📄 PDF</span>
    <?php endif; ?>
</div>
```

**Ersetze mit:**
```php
<div class="lesson-meta-row">
    <?php if ($lesson['unlock_after_days'] !== null && $lesson['unlock_after_days'] > 0): ?>
        <span class="lesson-meta" style="background: rgba(245, 158, 11, 0.2); border-color: rgba(245, 158, 11, 0.4); color: #fbbf24;">
            🔒 Tag <?= $lesson['unlock_after_days'] ?>
        </span>
    <?php endif; ?>
    <?php if ($lesson['video_url']): ?>
        <span class="lesson-meta">🎥 Video</span>
    <?php endif; ?>
    <?php
    // Zusätzliche Videos zählen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_videos WHERE lesson_id = ?");
    $stmt->execute([$lesson['id']]);
    $extra_videos = $stmt->fetchColumn();
    if ($extra_videos > 0): ?>
        <span class="lesson-meta">🎬 +<?= $extra_videos ?> Videos</span>
    <?php endif; ?>
    <?php if ($lesson['pdf_attachment']): ?>
        <span class="lesson-meta">📄 PDF</span>
    <?php endif; ?>
</div>
```

---

### 2. Add Lesson Modal erweitern (Zeile ~300)

**Suche nach:**
```php
<div class="form-group">
    <label>PDF-Anhang (z.B. Arbeitsblatt)</label>
    <input type="file" name="pdf_attachment" accept=".pdf">
</div>
```

**Füge DAVOR ein:**
```php
<div class="form-group">
    <label>⏰ Freischaltung nach X Tagen (optional)</label>
    <input type="number" name="unlock_after_days" min="0" placeholder="Leer = sofort, 7 = nach 7 Tagen">
    <small>Gib die Anzahl der Tage an (0 oder leer = sofort verfügbar)</small>
</div>

<div class="form-group">
    <label>🎬 Zusätzliche Videos (optional)</label>
    <div id="additionalVideos" style="display: flex; flex-direction: column; gap: 8px;">
        <!-- Videos werden hier dynamisch hinzugefügt -->
    </div>
    <button type="button" onclick="addVideoField('add')" class="btn-secondary" style="margin-top: 8px;">
        + Weiteres Video hinzufügen
    </button>
    <small>Du kannst beliebig viele Videos zu dieser Lektion hinzufügen</small>
</div>
```

---

### 3. Edit Lesson Modal erweitern (Zeile ~350)

**Suche nach:**
```php
<div class="form-group">
    <label>PDF-Anhang (z.B. Arbeitsblatt)</label>
    <input type="file" name="pdf_attachment" accept=".pdf">
    <small id="editLessonCurrentPdf" style="display: none; margin-top: 4px;">
        Aktuell: <span id="editLessonPdfName"></span> (Leer lassen zum Behalten)
    </small>
</div>
```

**Füge DAVOR ein:**
```php
<div class="form-group">
    <label>⏰ Freischaltung nach X Tagen (optional)</label>
    <input type="number" name="unlock_after_days" id="editLessonUnlockDays" min="0" placeholder="Leer = sofort">
    <small>Gib die Anzahl der Tage an (0 oder leer = sofort verfügbar)</small>
</div>

<div class="form-group">
    <label>🎬 Zusätzliche Videos (optional)</label>
    <div id="editAdditionalVideos" style="display: flex; flex-direction: column; gap: 8px;">
        <!-- Videos werden hier dynamisch hinzugefügt -->
    </div>
    <button type="button" onclick="addVideoField('edit')" class="btn-secondary" style="margin-top: 8px;">
        + Weiteres Video hinzufügen
    </button>
</div>
```

---

### 4. JavaScript erweitern (Am Ende vor </script>)

**Füge vor dem letzten `</script>` Tag ein:**

```javascript
// Globaler Counter für Video-Felder
let videoFieldCounter = 0;

// Video-Feld hinzufügen
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
               style="flex: 1; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;"
               required>
        <button type="button" 
                onclick="this.parentElement.remove()" 
                class="btn-icon" 
                style="padding: 10px 12px;">
            🗑️
        </button>
    `;
    container.appendChild(videoField);
}

// Erweitere showEditLessonModal Funktion
const originalShowEditLessonModal = showEditLessonModal;
showEditLessonModal = function(lesson) {
    originalShowEditLessonModal(lesson);
    
    // Drip Content Wert setzen
    document.getElementById('editLessonUnlockDays').value = lesson.unlock_after_days || '';
    
    // Zusätzliche Videos laden
    const editVideosContainer = document.getElementById('editAdditionalVideos');
    editVideosContainer.innerHTML = '';
    
    // Lade zusätzliche Videos aus Datenbank
    fetch('/admin/api/courses/lessons/get-videos.php?lesson_id=' + lesson.id)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.videos) {
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
                               style="flex: 1; background: rgba(26, 26, 46, 0.8); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 10px 12px; color: #e0e0e0; font-size: 14px;"
                               required>
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
};

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

---

### 5. API Endpoint erstellen

Erstelle eine neue Datei: `admin/api/courses/lessons/get-videos.php`

```php
<?php
session_start();
require_once '../../../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

$lesson_id = $_GET['lesson_id'] ?? null;

if (!$lesson_id) {
    echo json_encode(['success' => false, 'error' => 'Lesson ID fehlt']);
    exit;
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order");
$stmt->execute([$lesson_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'videos' => $videos
]);
```

---

### 6. Bestehende API Endpoints erweitern

**Datei:** `admin/api/courses/lessons/create.php`

Füge nach dem INSERT Statement hinzu:

```php
$lesson_id = $pdo->lastInsertId();

// Zusätzliche Videos speichern
if (!empty($_POST['video_urls']) && is_array($_POST['video_urls'])) {
    $video_titles = $_POST['video_titles'] ?? [];
    foreach ($_POST['video_urls'] as $index => $video_url) {
        if (!empty($video_url)) {
            $video_title = $video_titles[$index] ?? "Video " . ($index + 1);
            $stmt = $pdo->prepare("
                INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$lesson_id, $video_title, $video_url, $index + 1]);
        }
    }
}
```

**Datei:** `admin/api/courses/lessons/update.php`

Füge nach dem UPDATE Statement hinzu:

```php
// Alte zusätzliche Videos löschen
$stmt = $pdo->prepare("DELETE FROM lesson_videos WHERE lesson_id = ?");
$stmt->execute([$lesson_id]);

// Neue Videos speichern
if (!empty($_POST['video_urls']) && is_array($_POST['video_urls'])) {
    $video_titles = $_POST['video_titles'] ?? [];
    foreach ($_POST['video_urls'] as $index => $video_url) {
        if (!empty($video_url)) {
            $video_title = $video_titles[$index] ?? "Video " . ($index + 1);
            $stmt = $pdo->prepare("
                INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$lesson_id, $video_title, $video_url, $index + 1]);
        }
    }
}
```

---

## ✅ Testing

Nach den Änderungen:
1. Cache leeren (Strg + F5)
2. Kurs öffnen und Lektion hinzufügen
3. Prüfe ob neue Felder sichtbar sind:
   - ⏰ Freischaltung nach X Tagen
   - 🎬 Button "+ Weiteres Video hinzufügen"

---

## 🆘 Wenn es nicht funktioniert

1. Prüfe Browser-Konsole auf JavaScript-Fehler (F12)
2. Prüfe ob Datenbank-Migration durchgelaufen ist
3. Prüfe ob API-Endpoints richtig erstellt wurden
4. Kontaktiere mich für weitere Hilfe!
