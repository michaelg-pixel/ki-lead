# 🚀 Customer Freebie Videokurs - Quick Start Guide

## ✅ Was wurde bereits erstellt:

### 1. Datenbank-Migration
- ✅ `setup/customer-freebie-courses-setup.sql` - SQL-Schema
- ✅ `migrate-customer-freebie-courses.php` - Browser-Migration ohne Passwort

### 2. API
- ✅ `customer/api/freebie-course-api.php` - Vollständige API für:
  - Kurse erstellen/aktualisieren
  - Module erstellen/bearbeiten/löschen/sortieren
  - Lektionen erstellen/bearbeiten/löschen/sortieren
  - Fortschritt speichern (E-Mail-basiert)
  - Mockup-URL aktualisieren

### 3. Dokumentation
- ✅ `CUSTOMER_FREEBIE_VIDEOKURS_README.md` - Vollständige Dokumentation
- ✅ Dieser Quick-Start-Guide

## 📝 Noch zu erstellen:

### 1. Custom Freebie Editor mit Tabs
**Datei:** `customer/custom-freebie-editor-tabs.php`

**Features:**
- Tab 1: Einstellungen (bestehende Funktion aus `custom-freebie-editor.php`)
- Tab 2: Videokurs (neuer Tab für Module/Lektionen)

**Implementierung:**
```php
// Tab-Navigation oben
<div class="tabs">
    <button onclick="switchTab('settings')">⚙️ Einstellungen</button>
    <button onclick="switchTab('course')">🎓 Videokurs</button>
</div>

// Tab-Inhalte
<div id="tab-settings">
    <!-- Bestehende Einstellungen -->
</div>

<div id="tab-course" style="display: none;">
    <!-- Videokurs-Editor -->
    <div class="course-editor">
        <!-- Mockup-URL für Danke-Seite -->
        <!-- Module-Liste mit Add-Button -->
        <!-- Für jedes Modul: Lektionen-Liste mit Add-Button -->
    </div>
</div>
```

**JavaScript-Funktionen:**
- `switchTab(tabName)` - Tabs wechseln
- `addModule()` - Modul hinzufügen
- `editModule(moduleId)` - Modul bearbeiten
- `deleteModule(moduleId)` - Modul löschen
- `addLesson(moduleId)` - Lektion hinzufügen
- `editLesson(lessonId)` - Lektion bearbeiten
- `deleteLesson(lessonId)` - Lektion löschen

### 2. Videokurs-Player für Leads
**Datei:** `customer/freebie-course-player.php`

**URL:** `/customer/freebie-course-player.php?freebie_id=123&email=lead@example.com`

**Layout:**
```
┌─────────────────────────────────────────────────┐
│ Kurstitel                                       │
│ ━━━━━━━━━━━━━━━━━ 25% ━━━━━━━━━━━━━━━━━━━━━━ │
├────────────────────────────────┬────────────────┤
│                                │ 📚 Module      │
│  Video Player (16:9)           │  ▸ Modul 1    │
│  oder                          │   ✓ Lektion 1 │
│  📄 PDF Viewer                 │   ▶️ Lektion 2 │
│                                │   ○ Lektion 3 │
│  Lektionstitel                │  ▸ Modul 2    │
│  Beschreibung...              │   ○ Lektion 4 │
│                                │               │
│  [✓ Als abgeschlossen]         │               │
│  [📥 PDF herunterladen]       │               │
└────────────────────────────────┴────────────────┘
```

**PHP-Logik:**
```php
// 1. Freebie und Kurs laden
$stmt = $pdo->prepare("
    SELECT c.* FROM freebie_courses c
    JOIN customer_freebies f ON c.freebie_id = f.id
    WHERE f.id = ?
");

// 2. Module und Lektionen laden
$stmt = $pdo->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM freebie_course_lessons WHERE module_id = m.id) as lesson_count
    FROM freebie_course_modules m
    WHERE m.course_id = ?
    ORDER BY m.sort_order
");

// 3. Fortschritt für diesen Lead laden
$stmt = $pdo->prepare("
    SELECT lesson_id, completed 
    FROM freebie_course_progress
    WHERE lead_email = ?
");

// 4. Aktuelle Lektion bestimmen
$current_lesson_id = $_GET['lesson_id'] ?? $first_incomplete_lesson_id;
```

**JavaScript-Funktionen:**
- `loadLesson(lessonId)` - Lektion laden
- `markAsComplete()` - Als abgeschlossen markieren
- `nextLesson()` - Nächste Lektion
- `previousLesson()` - Vorherige Lektion
- `toggleSidebar()` - Sidebar ein/ausblenden (Mobile)

### 3. Danke-Seite Aktualisierung
**Datei:** `freebie/thankyou.php` (bereits vorhanden, muss erweitert werden)

**Zu ergänzen:**
```php
// Nach Zeile ~50: Kurs-Daten laden
$course = null;
$course_url = '';
if ($freebie && $freebie['has_course']) {
    $stmt = $pdo->prepare("
        SELECT fc.id, fc.title, fc.description
        FROM freebie_courses fc
        WHERE fc.freebie_id = ?
    ");
    $stmt->execute([$freebie_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($course) {
        // URL zum Player mit E-Mail (falls verfügbar)
        $lead_email = $_GET['email'] ?? '';
        $course_url = "/customer/freebie-course-player.php?freebie_id={$freebie_id}";
        if ($lead_email) {
            $course_url .= "&email=" . urlencode($lead_email);
        }
    }
}

// Im HTML (vor dem Empfehlungsprogramm-Button):
<?php if ($course_url): ?>
    <a href="<?php echo htmlspecialchars($course_url); ?>" class="cta-button">
        <span class="cta-icon">🎓</span>
        <span>Zum Videokurs</span>
    </a>
<?php endif; ?>
```

## 🔧 Implementierungsschritte:

### Schritt 1: Migration ausführen
```
1. Öffne im Browser: https://app.mehr-infos-jetzt.de/migrate-customer-freebie-courses.php
2. Klicke auf "Migration starten"
3. Warte auf Erfolgs-Meldung
```

### Schritt 2: Dateien erstellen
Die oben genannten 3 Dateien müssen noch erstellt werden:
1. `customer/custom-freebie-editor-tabs.php`
2. `customer/freebie-course-player.php`
3. `freebie/thankyou.php` (Update)

### Schritt 3: Dashboard-Link aktualisieren
**Datei:** `customer/dashboard.php` oder entsprechende Include-Datei

**Ändern:**
```php
// Alt:
$editor_url = "/customer/custom-freebie-editor.php?id={$freebie_id}";

// Neu:
$editor_url = "/customer/custom-freebie-editor-tabs.php?id={$freebie_id}&tab=settings";
```

### Schritt 4: Testen
```
1. Als Kunde einloggen
2. Freebie erstellen oder bearbeiten
3. Tab "Videokurs" öffnen
4. Modul erstellen
5. Lektion mit Video-URL hinzufügen
6. Speichern
7. Freebie-URL aufrufen und Optin ausfüllen
8. Danke-Seite → "Zum Videokurs" Button sollte erscheinen
9. Videokurs öffnen und testen
```

## 📋 Code-Templates

### Template: Modul-Formular (für custom-freebie-editor-tabs.php)
```html
<div class="module-form" id="module-form-{id}">
    <input type="text" placeholder="Modultitel" id="module-title-{id}">
    <textarea placeholder="Beschreibung (optional)" id="module-desc-{id}"></textarea>
    <button onclick="saveModule({id})">💾 Speichern</button>
    <button onclick="cancelModule({id})">❌ Abbrechen</button>
</div>
```

### Template: Lektions-Formular
```html
<div class="lesson-form" id="lesson-form-{id}">
    <input type="text" placeholder="Lektionstitel" id="lesson-title-{id}">
    <textarea placeholder="Beschreibung" id="lesson-desc-{id}"></textarea>
    <input type="url" placeholder="Video-URL (YouTube/Vimeo)" id="lesson-video-{id}">
    <input type="url" placeholder="PDF-URL (optional)" id="lesson-pdf-{id}">
    <button onclick="saveLesson({id})">💾 Speichern</button>
    <button onclick="cancelLesson({id})">❌ Abbrechen</button>
</div>
```

### Template: AJAX-Call
```javascript
async function apiCall(action, data) {
    const response = await fetch('/customer/api/freebie-course-api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action, ...data})
    });
    return await response.json();
}

// Beispiel: Modul erstellen
async function addModule() {
    const result = await apiCall('create_module', {
        course_id: currentCourseId,
        title: document.getElementById('new-module-title').value,
        description: document.getElementById('new-module-desc').value
    });
    
    if (result.success) {
        alert('Modul erstellt!');
        reloadModules();
    } else {
        alert('Fehler: ' + result.error);
    }
}
```

## 🎨 CSS-Klassen (einheitlich verwenden)

```css
.tabs { /* Tab-Navigation */ }
.tab-button { /* Tab-Button */ }
.tab-button.active { /* Aktiver Tab */ }
.tab-content { /* Tab-Inhalt */ }
.module-card { /* Modul-Karte */ }
.lesson-item { /* Lektions-Item */ }
.lesson-completed { /* Abgeschlossene Lektion */ }
.lesson-current { /* Aktuelle Lektion */ }
.video-player-container { /* Video-Container */ }
.course-sidebar { /* Sidebar mit Modulen */ }
```

## ⚠️ Wichtige Hinweise

1. **Video-URL Normalisierung:**
   - Alle URLs werden in Embed-Format konvertiert
   - YouTube: `https://www.youtube.com/embed/VIDEO_ID`
   - Vimeo: `https://player.vimeo.com/video/VIDEO_ID`

2. **Fortschritts-Tracking:**
   - Basiert auf E-Mail (kein Login nötig für Leads)
   - Lead-E-Mail muss in URL übergeben werden
   - Sollte auf Danke-Seite automatisch geschehen

3. **Mockup-Logik:**
   - Priorität: `course_mockup_url` > `mockup_image_url` (Freebie)
   - Falls kein Mockup: Placeholder-Icon anzeigen

4. **Responsive Design:**
   - Breakpoints: 1024px (Desktop/Tablet), 768px (Tablet/Mobile)
   - Mobile: Sidebar als Dropdown/Akkordeon
   - Touch-optimierte Buttons (min. 44x44px)

## 🔍 Debugging-Tipps

1. **Browser Console:**
   ```javascript
   console.log('API Response:', result);
   console.log('Current Lesson:', currentLessonId);
   ```

2. **PHP Errors:**
   ```php
   error_log('Freebie ID: ' . $freebie_id);
   error_log('Course Data: ' . print_r($course, true));
   ```

3. **Datenbank prüfen:**
   ```sql
   SELECT * FROM freebie_courses WHERE freebie_id = 123;
   SELECT * FROM freebie_course_modules WHERE course_id = 456;
   SELECT * FROM freebie_course_lessons WHERE module_id = 789;
   SELECT * FROM freebie_course_progress WHERE lead_email = 'test@example.com';
   ```

## 📞 Support

Bei Fragen:
- Siehe `CUSTOMER_FREEBIE_VIDEOKURS_README.md` für Details
- Prüfe Browser Console und Server Logs
- GitHub Issues für Bugs/Feature-Requests

---

**Status:** Migration ✅ | API ✅ | Editor ⏳ | Player ⏳ | Danke-Seite ⏳

**Nächster Schritt:** Erstelle die 3 fehlenden Dateien nach den obigen Templates
