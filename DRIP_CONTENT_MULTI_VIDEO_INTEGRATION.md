# 🎓 Drip Content & Multi-Video Integration Guide

## Übersicht
Diese Anleitung zeigt, wie du **Drip Content** (zeitbasierte Freischaltung) und **mehrere Videos pro Lektion** in dein Videokurs-System integrierst.

---

## 📋 Schritt 1: Datenbank-Migration

Rufe folgende URL auf:
```
https://deine-domain.de/database/run-drip-multi-video-migration.php
```

Dies erstellt:
- ✅ `unlock_after_days` Spalte in `lessons` Tabelle
- ✅ `lesson_videos` Tabelle für mehrere Videos
- ✅ `course_enrollments` Tabelle für Einschreibungs-Tracking

---

## 🔧 Schritt 2: Admin-Interface erweitern (admin/course-edit.php)

### 2.1 Lektionen mit zusätzlichen Videos laden

Füge nach Zeile ~35 (nach `$lessons_by_module[$module['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);`) hinzu:

```php
        // Zusätzliche Videos pro Lektion laden
        foreach ($lessons_by_module[$module['id']] as $key => $lesson) {
            $stmt = $conn->prepare("SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY sort_order");
            $stmt->execute([$lesson['id']]);
            $lessons_by_module[$module['id']][$key]['additional_videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
```

### 2.2 Lektion-Hinzufügen erweitern

Ersetze den Abschnitt "// Lektion hinzufügen" (ca. Zeile 130-160):

```php
// Lektion hinzufügen
if (isset($_POST['add_lesson'])) {
    $module_id = (int)$_POST['module_id'];
    $lesson_title = $_POST['lesson_title'];
    $lesson_description = $_POST['lesson_description'];
    $vimeo_url = $_POST['vimeo_url'] ?? '';
    $unlock_after_days = !empty($_POST['unlock_after_days']) ? (int)$_POST['unlock_after_days'] : null;
    
    // PDF hochladen (bestehender Code bleibt)
    $pdf_file = '';
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $upload_dir = '../uploads/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $new_filename = uniqid() . '_' . time() . '.pdf';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                $pdf_file = $new_filename;
            }
        }
    }
    
    $stmt = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 as next_order FROM lessons WHERE module_id = ?");
    $stmt->execute([$module_id]);
    $next_order = $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    
    // NEU: unlock_after_days hinzugefügt
    $stmt = $conn->prepare("
        INSERT INTO lessons (module_id, title, description, vimeo_url, pdf_file, unlock_after_days, sort_order) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$module_id, $lesson_title, $lesson_description, $vimeo_url, $pdf_file, $unlock_after_days, $next_order]);
    $lesson_id = $conn->lastInsertId();
    
    // NEU: Mehrere Videos hinzufügen
    if (!empty($_POST['video_urls']) && is_array($_POST['video_urls'])) {
        $video_titles = $_POST['video_titles'] ?? [];
        foreach ($_POST['video_urls'] as $index => $video_url) {
            if (!empty($video_url)) {
                $video_title = $video_titles[$index] ?? "Video " . ($index + 1);
                $stmt = $conn->prepare("
                    INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$lesson_id, $video_title, $video_url, $index + 1]);
            }
        }
    }
    
    header('Location: course-edit.php?id=' . $course_id . '&lesson_added=1');
    exit;
}
```

### 2.3 Lektion-Bearbeiten erweitern

Ersetze den Abschnitt "// Lektion bearbeiten" (ca. Zeile 165-200):

```php
// Lektion bearbeiten
if (isset($_POST['edit_lesson'])) {
    $lesson_id = (int)$_POST['lesson_id'];
    $lesson_title = $_POST['lesson_title'];
    $lesson_description = $_POST['lesson_description'];
    $vimeo_url = $_POST['vimeo_url'] ?? '';
    $unlock_after_days = !empty($_POST['unlock_after_days']) ? (int)$_POST['unlock_after_days'] : null;
    
    // Aktuelle PDF-Datei laden (bestehender Code bleibt)
    $stmt = $conn->prepare("SELECT pdf_file FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    $current_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    $pdf_file = $current_lesson['pdf_file'];
    
    // Neue PDF hochladen (falls vorhanden) - bestehender Code bleibt
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === 0) {
        $upload_dir = '../uploads/pdfs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        if (strtolower($file_ext) === 'pdf') {
            $new_filename = uniqid() . '_' . time() . '.pdf';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                if ($pdf_file && file_exists($upload_dir . $pdf_file)) {
                    unlink($upload_dir . $pdf_file);
                }
                $pdf_file = $new_filename;
            }
        }
    }
    
    // NEU: unlock_after_days hinzugefügt
    $stmt = $conn->prepare("
        UPDATE lessons 
        SET title = ?, description = ?, vimeo_url = ?, pdf_file = ?, unlock_after_days = ? 
        WHERE id = ?
    ");
    $stmt->execute([$lesson_title, $lesson_description, $vimeo_url, $pdf_file, $unlock_after_days, $lesson_id]);
    
    // NEU: Zusätzliche Videos aktualisieren
    // Zuerst alte Videos löschen
    $stmt = $conn->prepare("DELETE FROM lesson_videos WHERE lesson_id = ?");
    $stmt->execute([$lesson_id]);
    
    // Dann neue Videos hinzufügen
    if (!empty($_POST['video_urls']) && is_array($_POST['video_urls'])) {
        $video_titles = $_POST['video_titles'] ?? [];
        foreach ($_POST['video_urls'] as $index => $video_url) {
            if (!empty($video_url)) {
                $video_title = $video_titles[$index] ?? "Video " . ($index + 1);
                $stmt = $conn->prepare("
                    INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$lesson_id, $video_title, $video_url, $index + 1]);
            }
        }
    }
    
    header('Location: course-edit.php?id=' . $course_id . '&lesson_updated=1');
    exit;
}
```

### 2.4 Lektion-Anzeige erweitern

Ersetze die Lektion-Anzeige (ca. Zeile 650-670):

```php
<div class="font-semibold text-white"><?= htmlspecialchars($lesson['title']) ?></div>
<div class="text-sm mt-1" style="color: #a0a0a0;"><?= htmlspecialchars($lesson['description']) ?></div>

<!-- NEU: Drip Content Anzeige -->
<?php if ($lesson['unlock_after_days'] !== null && $lesson['unlock_after_days'] > 0): ?>
    <div class="text-xs mt-2 inline-flex items-center gap-2 px-3 py-1.5 rounded-full" 
         style="background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24;">
        <i class="fas fa-clock"></i>
        <span>Freischaltung nach <?= $lesson['unlock_after_days'] ?> Tag<?= $lesson['unlock_after_days'] != 1 ? 'en' : '' ?></span>
    </div>
<?php endif; ?>

<div class="text-xs mt-2 flex items-center gap-4" style="color: #c084fc;">
    <?php if ($lesson['vimeo_url']): ?>
        <span><i class="fas fa-video mr-1"></i> Hauptvideo</span>
    <?php endif; ?>
    
    <!-- NEU: Zusätzliche Videos anzeigen -->
    <?php if (!empty($lesson['additional_videos'])): ?>
        <span><i class="fas fa-film mr-1"></i> <?= count($lesson['additional_videos']) ?> weitere Video<?= count($lesson['additional_videos']) != 1 ? 's' : '' ?></span>
    <?php endif; ?>
    
    <?php if ($lesson['pdf_file']): ?>
        <a href="../uploads/pdfs/<?= htmlspecialchars($lesson['pdf_file']) ?>" 
           target="_blank" class="hover:underline">
            <i class="fas fa-file-pdf mr-1"></i> PDF vorhanden
        </a>
    <?php endif; ?>
</div>
```

### 2.5 Lektion-Bearbeitungsformular erweitern

Ersetze das Bearbeitungsformular (ca. Zeile 710-750):

```php
<div>
    <label class="custom-label block mb-2">Lektionstitel *</label>
    <input type="text" name="lesson_title" 
           value="<?= htmlspecialchars($lesson['title']) ?>" 
           class="custom-input w-full px-4 py-2.5 rounded-lg" required>
</div>

<div>
    <label class="custom-label block mb-2">Beschreibung</label>
    <textarea name="lesson_description" rows="2" 
              class="custom-input w-full px-4 py-2.5 rounded-lg"><?= htmlspecialchars($lesson['description']) ?></textarea>
</div>

<!-- NEU: Drip Content Feld -->
<div>
    <label class="custom-label block mb-2">
        <i class="fas fa-clock"></i> Freischaltung nach X Tagen (optional)
    </label>
    <input type="number" name="unlock_after_days" 
           value="<?= htmlspecialchars($lesson['unlock_after_days'] ?? '') ?>" 
           class="custom-input w-full px-4 py-2.5 rounded-lg" 
           min="0"
           placeholder="Leer = sofort verfügbar, 7 = nach 7 Tagen">
    <p class="text-xs mt-1" style="color: #a0a0a0;">
        Gib die Anzahl der Tage an, nach denen die Lektion freigeschaltet wird. 
        Leer oder 0 = sofort verfügbar.
    </p>
</div>

<div>
    <label class="custom-label block mb-2">
        Hauptvideo (Vimeo oder YouTube URL)
    </label>
    <input type="url" name="vimeo_url" 
           value="<?= htmlspecialchars($lesson['vimeo_url']) ?>" 
           class="custom-input w-full px-4 py-2.5 rounded-lg"
           placeholder="https://vimeo.com/123456789">
    <p class="text-xs mt-1" style="color: #a0a0a0;">
        Optional: Du kannst auch nur zusätzliche Videos hinzufügen (siehe unten)
    </p>
</div>

<!-- NEU: Zusätzliche Videos -->
<div id="additional-videos-edit-<?= $lesson['id'] ?>">
    <label class="custom-label block mb-2">
        <i class="fas fa-film"></i> Zusätzliche Videos (optional)
    </label>
    
    <div class="space-y-3" id="video-list-edit-<?= $lesson['id'] ?>">
        <?php if (!empty($lesson['additional_videos'])): ?>
            <?php foreach ($lesson['additional_videos'] as $idx => $video): ?>
                <div class="flex gap-2 video-row">
                    <input type="text" name="video_titles[]" 
                           value="<?= htmlspecialchars($video['video_title']) ?>"
                           placeholder="Video Titel" 
                           class="custom-input px-4 py-2.5 rounded-lg flex-shrink-0" 
                           style="width: 200px;">
                    <input type="url" name="video_urls[]" 
                           value="<?= htmlspecialchars($video['video_url']) ?>"
                           placeholder="https://vimeo.com/..." 
                           class="custom-input px-4 py-2.5 rounded-lg flex-1">
                    <button type="button" onclick="this.parentElement.remove()" 
                            class="btn-danger px-3 py-2.5 rounded-lg text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <button type="button" 
            onclick="addVideoField('edit-<?= $lesson['id'] ?>')" 
            class="btn-secondary px-4 py-2 rounded-lg text-sm mt-3 inline-flex items-center gap-2">
        <i class="fas fa-plus"></i> Weiteres Video hinzufügen
    </button>
</div>

<div>
    <label class="custom-label block mb-2">
        PDF hochladen (optional - leer lassen um beizubehalten)
    </label>
    <input type="file" name="pdf_file" accept=".pdf" 
           class="custom-input w-full px-4 py-2.5 rounded-lg">
    <?php if ($lesson['pdf_file']): ?>
        <div class="text-xs mt-2" style="color: #a0a0a0;">
            Aktuell: <?= htmlspecialchars($lesson['pdf_file']) ?>
        </div>
    <?php endif; ?>
</div>
```

### 2.6 Neue Lektion Formular erweitern

Ersetze das "Neue Lektion hinzufügen" Formular (ca. Zeile 800-830):

```php
<details class="mt-4">
    <summary class="cursor-pointer font-semibold">
        <i class="fas fa-plus mr-2"></i> Neue Lektion hinzufügen
    </summary>
    <form method="POST" enctype="multipart/form-data" class="mt-4 card-deep p-4 rounded-lg">
        <input type="hidden" name="module_id" value="<?= $module['id'] ?>">
        <div class="space-y-3">
            <input type="text" name="lesson_title" placeholder="Lektionstitel *" 
                   class="custom-input w-full px-4 py-2.5 rounded-lg" required>
            
            <textarea name="lesson_description" placeholder="Beschreibung" rows="2" 
                      class="custom-input w-full px-4 py-2.5 rounded-lg"></textarea>
            
            <!-- NEU: Drip Content -->
            <div>
                <input type="number" name="unlock_after_days" 
                       placeholder="Freischaltung nach X Tagen (leer = sofort)" 
                       class="custom-input w-full px-4 py-2.5 rounded-lg" 
                       min="0">
                <p class="text-xs mt-1" style="color: #a0a0a0;">
                    <i class="fas fa-info-circle"></i> 
                    Gib die Anzahl der Tage nach Kursstart an. Leer = sofort verfügbar.
                </p>
            </div>
            
            <input type="url" name="vimeo_url" placeholder="Hauptvideo URL (optional)" 
                   class="custom-input w-full px-4 py-2.5 rounded-lg">
            
            <!-- NEU: Zusätzliche Videos -->
            <div>
                <label class="custom-label block mb-2">
                    <i class="fas fa-film"></i> Zusätzliche Videos (optional)
                </label>
                <div class="space-y-2" id="video-list-new-<?= $module['id'] ?>">
                    <!-- Initial empty -->
                </div>
                <button type="button" 
                        onclick="addVideoField('new-<?= $module['id'] ?>')" 
                        class="btn-secondary px-4 py-2 rounded-lg text-sm mt-2 inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i> Video hinzufügen
                </button>
            </div>
            
            <div>
                <label class="custom-label block mb-2">PDF hochladen (optional)</label>
                <input type="file" name="pdf_file" accept=".pdf" 
                       class="custom-input w-full px-4 py-2.5 rounded-lg">
            </div>
            
            <button type="submit" name="add_lesson" 
                    class="btn-primary px-5 py-2.5 rounded-lg font-semibold inline-flex items-center gap-2">
                <i class="fas fa-plus"></i> Lektion hinzufügen
            </button>
        </div>
    </form>
</details>
```

### 2.7 JavaScript für dynamische Video-Felder

Füge vor dem schließenden `</script>` Tag (ganz am Ende) hinzu:

```javascript
// Funktion zum Hinzufügen weiterer Video-Felder
function addVideoField(listId) {
    const list = document.getElementById('video-list-' + listId);
    const row = document.createElement('div');
    row.className = 'flex gap-2 video-row';
    row.innerHTML = `
        <input type="text" name="video_titles[]" 
               placeholder="Video Titel" 
               class="custom-input px-4 py-2.5 rounded-lg flex-shrink-0" 
               style="width: 200px;">
        <input type="url" name="video_urls[]" 
               placeholder="https://vimeo.com/..." 
               class="custom-input px-4 py-2.5 rounded-lg flex-1"
               required>
        <button type="button" onclick="this.parentElement.remove()" 
                class="btn-danger px-3 py-2.5 rounded-lg text-sm">
            <i class="fas fa-trash"></i>
        </button>
    `;
    list.appendChild(row);
}
```

---

## 📱 Schritt 3: Customer-View erweitern

Die Customer-View muss erweitert werden, um:
1. Gesperrte Lektionen anzuzeigen
2. Verbleibende Tage bis zur Freischaltung zu zeigen
3. Mehrere Videos pro Lektion anzuzeigen

Siehe separate Datei: `CUSTOMER_VIEW_DRIP_CONTENT.md`

---

## ✅ Testing-Checkliste

Nach der Integration teste folgendes:

- [ ] Kurs erstellen/bearbeiten funktioniert noch
- [ ] Modul erstellen/bearbeiten funktioniert noch
- [ ] Lektion mit Hauptvideo hinzufügen
- [ ] Lektion mit mehreren Videos hinzufügen
- [ ] Lektion mit Drip-Content (z.B. 7 Tage) hinzufügen
- [ ] Lektion ohne Drip-Content hinzufügen
- [ ] Lektion bearbeiten - Videos hinzufügen/entfernen
- [ ] Lektion löschen
- [ ] Customer-View: Gesperrte Lektion anzeigen
- [ ] Customer-View: Verbleibende Tage anzeigen
- [ ] Customer-View: Mehrere Videos abspielen

---

## 🐛 Häufige Probleme & Lösungen

### Problem: "Unknown column 'unlock_after_days'"
**Lösung:** Migration wurde nicht ausgeführt. Rufe `run-drip-multi-video-migration.php` auf.

### Problem: Videos werden nicht gespeichert
**Lösung:** Prüfe ob die `lesson_videos` Tabelle existiert. Prüfe Browser-Konsole auf JavaScript-Fehler.

### Problem: Alte Lektionen zeigen keine Videos
**Lösung:** Das ist normal. Nur neue Lektionen oder bearbeitete Lektionen nutzen das neue System.

---

## 📞 Support

Bei Problemen:
1. Prüfe Browser-Konsole auf JavaScript-Fehler
2. Prüfe PHP-Error-Log
3. Prüfe ob alle Datenbank-Tabellen existieren
4. Erstelle ein Backup vor weiteren Änderungen

---

**Viel Erfolg! 🚀**
