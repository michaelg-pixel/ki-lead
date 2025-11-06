# 🎓 Videokurs-Feature für Custom Freebies - Installation

## ✅ Was bereits hochgeladen wurde:

✅ `/api/activate-course.php` - API: Kurs aktivieren
✅ `/api/course-modules.php` - API: Module verwalten  
✅ `/api/course-lessons.php` - API: Lektionen verwalten
✅ `/api/.htaccess` - URL-Routing
✅ `/database/video-course-migration.sql` - Datenbank-Tabellen

---

## 📝 Was DU noch tun musst:

### 1️⃣ Editor-Datei via FTP hochladen

Die **custom-freebie-editor-FIXED.php** muss noch hochgeladen werden:

```
Upload: custom-freebie-editor-FIXED.php
Nach:   /customer/custom-freebie-editor.php
```

⚠️ **Wichtig:** Mache vorher ein Backup der alten Datei!

---

### 2️⃣ Datenbank-Migration ausführen

1. Öffne **phpMyAdmin**
2. Wähle deine Datenbank
3. Klicke auf **"SQL"** Tab
4. Kopiere den Inhalt von `/database/video-course-migration.sql`
5. Füge ihn ein und klicke **"OK"**

**Was wird erstellt:**
- Spalte `has_course` in `customer_freebies`
- Tabelle `freebie_courses`
- Tabelle `freebie_course_modules`
- Tabelle `freebie_course_lessons`

---

### 3️⃣ Testen

1. Öffne einen Freebie im Editor:
   ```
   /customer/custom-freebie-editor.php?id=FREEBIE_ID
   ```

2. Du solltest sehen:
   - ⚙️ Tab "Eigenschaften"
   - 🎓 Tab "Videokurs" (NEU!)

3. Klicke auf "Videokurs" → "✨ Videokurs jetzt aktivieren"

4. Erstelle ein Modul → Füge eine Lektion hinzu

5. **Fertig!** ✅

---

## 🎯 Features:

✅ **2-Tab-System**
- Eigenschaften (unverändert)
- Videokurs (NEU)

✅ **Kurs-Management**
- Module erstellen/bearbeiten/löschen
- Lektionen mit YouTube/Vimeo Videos
- Optional PDFs pro Lektion
- Automatische Sortierung

✅ **3 REST-API Endpoints**
- `POST /api/freebies/{id}/activate-course`
- `GET/POST/PUT/DELETE /api/course-modules`
- `GET/POST/PUT/DELETE /api/course-lessons`

✅ **Sicherheit**
- Session-Authentifizierung
- Ownership-Checks
- SQL-Injection-Schutz
- XSS-Schutz

---

## 🐛 Troubleshooting

### Tab "Videokurs" nicht sichtbar?
→ Nur bei bestehenden Freebies, nicht bei "Neu erstellen"

### Button macht nichts?
1. F12 drücken
2. Console-Tab öffnen
3. Fehlermeldung ablesen

### 500 Error?
→ PHP Error Log checken oder Network-Tab Response lesen

### Module/Lektionen können nicht erstellt werden?
1. Browser-Konsole (F12) prüfen
2. Network-Tab → Response bei 500-Fehler lesen
3. Datenbank-Migration korrekt ausgeführt?

---

## 📊 Datenbank-Verifikation

Nach Migration diese Query ausführen:

```sql
SELECT 
    'freebie_courses' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_courses'

UNION ALL

SELECT 
    'freebie_course_modules' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_course_modules'

UNION ALL

SELECT 
    'freebie_course_lessons' as table_name,
    COUNT(*) as exists_check
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
AND table_name = 'freebie_course_lessons';
```

**Ergebnis:** Sollte 3x "1" zurückgeben!

---

## 🚀 Installation in 3 Schritten:

```bash
# 1. Editor hochladen (FTP)
custom-freebie-editor-FIXED.php → /customer/custom-freebie-editor.php

# 2. Datenbank (phpMyAdmin)
SQL Tab → video-course-migration.sql ausführen

# 3. Testen
Editor öffnen → Tab "Videokurs" → Aktivieren → Modul erstellen
```

**Zeit:** ~5 Minuten  
**Schwierigkeit:** Einfach

---

## ✅ Checkliste

- [ ] SQL-Migration in phpMyAdmin ausgeführt
- [ ] custom-freebie-editor-FIXED.php via FTP hochgeladen
- [ ] Editor geöffnet
- [ ] Tab "Videokurs" ist sichtbar
- [ ] Kurs aktiviert
- [ ] Modul erstellt
- [ ] Lektion erstellt
- [ ] **FERTIG!** 🎉

---

## 📚 API-Dokumentation

### Videokurs aktivieren
```
POST /api/freebies/{freebie_id}/activate-course
```

### Module verwalten
```
GET    /api/course-modules?course_id=123
POST   /api/course-modules
PUT    /api/course-modules/{id}
DELETE /api/course-modules/{id}
```

### Lektionen verwalten
```
GET    /api/course-lessons?module_id=456
POST   /api/course-lessons
PUT    /api/course-lessons/{id}
DELETE /api/course-lessons/{id}
```

---

**Erstellt am:** 2025-11-06  
**Version:** 1.0  
**Status:** ✅ Bereit für Produktion

Bei Fragen oder Problemen: Error-Logs prüfen! 🔍
