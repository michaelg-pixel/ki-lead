# 🚀 Quickstart: Drip Content & Multi-Video Setup

## ⚡ In 5 Minuten fertig!

### Schritt 1: Datenbank-Migration (2 Minuten)

1. Öffne im Browser:
   ```
   https://deine-domain.de/database/run-drip-multi-video-migration.php
   ```

2. Klicke auf **"Ja, Migration jetzt durchführen"**

3. Warte auf Erfolgsmeldung ✅

---

### Schritt 2: Test-Kurs erstellen (3 Minuten)

1. Gehe zu **Admin → Kurse → Kurs bearbeiten**

2. **Öffne die Datei** `admin/course-edit.php` in einem Code-Editor

3. **Suche und ersetze** folgende Code-Abschnitte:

#### 🔍 Suche nach: `INSERT INTO lessons (module_id, title, description, vimeo_url, pdf_file, sort_order)`

#### ✏️ Ersetze mit:
```sql
INSERT INTO lessons (module_id, title, description, vimeo_url, pdf_file, unlock_after_days, sort_order)
```

#### 🔍 Suche nach: `VALUES (?, ?, ?, ?, ?, ?)`

#### ✏️ Ersetze mit:
```sql
VALUES (?, ?, ?, ?, ?, ?, ?)
```

#### 🔍 Suche nach die Zeile mit `$stmt->execute([$module_id, $lesson_title, $lesson_description, $vimeo_url, $pdf_file, $next_order]);`

#### ✏️ Ersetze mit:
```php
$unlock_after_days = !empty($_POST['unlock_after_days']) ? (int)$_POST['unlock_after_days'] : null;
$stmt->execute([$module_id, $lesson_title, $lesson_description, $vimeo_url, $pdf_file, $unlock_after_days, $next_order]);
$lesson_id = $conn->lastInsertId();

// Mehrere Videos hinzufügen
if (!empty($_POST['video_urls']) && is_array($_POST['video_urls'])) {
    $video_titles = $_POST['video_titles'] ?? [];
    foreach ($_POST['video_urls'] as $index => $video_url) {
        if (!empty($video_url)) {
            $video_title = $video_titles[$index] ?? "Video " . ($index + 1);
            $stmt = $conn->prepare("INSERT INTO lesson_videos (lesson_id, video_title, video_url, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$lesson_id, $video_title, $video_url, $index + 1]);
        }
    }
}
```

4. **Füge die Formular-Felder hinzu**:

Suche nach dem Input-Feld für `vimeo_url` und füge **DANACH** hinzu:

```html
<!-- Drip Content -->
<div>
    <label class="custom-label block mb-2">
        <i class="fas fa-clock"></i> Freischaltung nach X Tagen (optional)
    </label>
    <input type="number" name="unlock_after_days" 
           placeholder="Leer = sofort, 7 = nach 7 Tagen" 
           class="custom-input w-full px-4 py-2.5 rounded-lg" 
           min="0">
</div>

<!-- Mehrere Videos -->
<div>
    <label class="custom-label block mb-2">
        <i class="fas fa-film"></i> Zusätzliche Videos
    </label>
    <div id="video-list" class="space-y-2 mb-2"></div>
    <button type="button" onclick="addVideoField()" 
            class="btn-secondary px-4 py-2 rounded-lg text-sm">
        <i class="fas fa-plus"></i> Video hinzufügen
    </button>
</div>

<script>
function addVideoField() {
    const list = document.getElementById('video-list');
    const row = document.createElement('div');
    row.className = 'flex gap-2';
    row.innerHTML = `
        <input type="text" name="video_titles[]" placeholder="Video Titel" 
               class="custom-input px-4 py-2.5 rounded-lg" style="width: 200px;">
        <input type="url" name="video_urls[]" placeholder="https://vimeo.com/..." 
               class="custom-input px-4 py-2.5 rounded-lg flex-1" required>
        <button type="button" onclick="this.parentElement.remove()" 
                class="btn-danger px-3 py-2.5 rounded-lg text-sm">
            <i class="fas fa-trash"></i>
        </button>
    `;
    list.appendChild(row);
}
</script>
```

5. **Speichere** die Datei und lade sie hoch

---

### Schritt 3: Testen! 🎉

1. Erstelle einen **Test-Kurs** im Admin

2. Füge ein **Modul** hinzu

3. Füge eine **Lektion** hinzu mit:
   - ✅ Titel: "Tag 1: Einführung"
   - ✅ Freischaltung: `0` (sofort)
   - ✅ 1 Video hinzufügen

4. Füge eine zweite **Lektion** hinzu mit:
   - ✅ Titel: "Tag 7: Fortgeschritten"
   - ✅ Freischaltung: `7` (nach 7 Tagen)
   - ✅ 2 Videos hinzufügen

5. **Prüfe** im Customer-View:
   - Lektion 1 sollte sofort sichtbar sein ✅
   - Lektion 2 sollte gesperrt sein mit Countdown ⏳

---

## 📝 Weitere Anpassungen

### Option A: Nur Drip Content (ohne Multi-Video)

Wenn du **nur** die zeitbasierte Freischaltung willst, ohne mehrere Videos:

- Füge nur das `unlock_after_days` Feld hinzu
- Überspringe den Multi-Video Code

### Option B: Nur Multi-Video (ohne Drip Content)

Wenn du **nur** mehrere Videos willst, ohne zeitbasierte Freischaltung:

- Füge nur die Video-Felder hinzu
- Überspringe den Drip Content Code

---

## 🎨 Bonus: Schönere Darstellung

Füge in deine Customer-View CSS hinzu:

```css
.locked-lesson {
    opacity: 0.6;
    position: relative;
}

.locked-lesson::before {
    content: '🔒';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    z-index: 10;
}

.countdown {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.2));
    border: 2px solid rgba(239, 68, 68, 0.4);
    padding: 1rem;
    border-radius: 0.5rem;
    text-align: center;
}

.countdown-days {
    font-size: 2.5rem;
    font-weight: bold;
    color: #f87171;
}
```

---

## ❓ Häufige Fragen

**Q: Kann ich die Freischaltung nachträglich ändern?**  
A: Ja! Bearbeite einfach die Lektion und ändere die Anzahl der Tage.

**Q: Was passiert mit alten Lektionen?**  
A: Alte Lektionen bleiben unverändert. Neue Features gelten nur für neue oder bearbeitete Lektionen.

**Q: Kann ich Videos später hinzufügen?**  
A: Ja! Bearbeite die Lektion und füge weitere Videos hinzu.

**Q: Funktioniert es mit YouTube UND Vimeo?**  
A: Ja! Beide werden unterstützt.

---

## 📚 Weiterführende Dokumentation

- **Vollständige Integration:** `DRIP_CONTENT_MULTI_VIDEO_INTEGRATION.md`
- **Customer View Setup:** `CUSTOMER_VIEW_DRIP_CONTENT.md`
- **Datenbank Details:** `database/add-drip-and-multi-video-support.sql`

---

## 🆘 Support

Bei Problemen:

1. ✅ Prüfe ob Migration erfolgreich war
2. ✅ Prüfe Browser-Konsole auf Fehler
3. ✅ Prüfe PHP Error-Log
4. ✅ Erstelle ein Backup vor weiteren Änderungen

---

**🎉 Fertig! Viel Erfolg mit deinem Drip Content System!**
