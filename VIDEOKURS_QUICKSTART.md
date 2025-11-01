# 🚀 Videokurs-System - Schnellstart-Anleitung

## ⚡ 5-Minuten Setup

### 1️⃣ Datenbank einrichten (1 Minute)
Öffne im Browser:
```
https://app.mehr-infos-jetzt.de/setup/setup-course-system.php
```
✅ Alle Tabellen werden automatisch erstellt!

---

### 2️⃣ Ersten Kurs erstellen (2 Minuten)

**Admin-Login:**
```
https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=templates
```

1. Klicke auf **"+ Neuer Kurs"**
2. Fülle aus:
   - ✏️ Titel: "Mein erster Videokurs"
   - 🎥 Typ: Video
   - 🎁 Kostenlos: Ja (aktiviert)
   - 📝 Beschreibung: "Ein Testkurs"
3. Klicke **"Kurs erstellen"**

---

### 3️⃣ Module & Lektionen hinzufügen (2 Minuten)

**Kurs bearbeiten:**
```
Klicke auf "✏️ Bearbeiten" bei deinem Kurs
```

**Modul erstellen:**
1. Klicke **"+ Modul hinzufügen"**
2. Titel: "Einführung"
3. Speichern

**Lektion erstellen:**
1. Bei deinem Modul: **"+ Lektion hinzufügen"**
2. Titel: "Willkommen"
3. Video-URL: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`
4. Speichern

---

### 4️⃣ Vorschau testen

**Admin-Vorschau:**
```
Klicke auf "👁️ Vorschau" bei deinem Kurs
```
✅ Du siehst jetzt, wie Kunden den Kurs sehen!

---

### 5️⃣ Als Customer ansehen

**Customer-Login:**
```
https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=kurse
```
✅ Dein Freebie-Kurs erscheint automatisch!

---

## 📋 Wichtige URLs - Übersicht

### Admin-Bereich
| Funktion | URL |
|----------|-----|
| Kurse verwalten | `/admin/dashboard.php?page=templates` |
| Kurs bearbeiten | `/admin/dashboard.php?page=course-edit&id=[ID]` |
| Kurs-Vorschau | `/admin/preview_course.php?id=[ID]` |

### Customer-Bereich
| Funktion | URL |
|----------|-----|
| Meine Kurse | `/customer/dashboard.php?page=kurse` |
| Kurs anschauen | `/customer/course-view.php?id=[ID]` |

### Setup
| Funktion | URL |
|----------|-----|
| Auto-Setup | `/setup/setup-course-system.php` |

---

## 🎯 Typische Use-Cases

### Use-Case 1: Freebie-Kurs erstellen
```
1. Admin → Kurse → + Neuer Kurs
2. ✅ "Kostenlos" aktivieren
3. Module & Lektionen hinzufügen
4. Fertig! Alle Customer sehen ihn automatisch
```

### Use-Case 2: Premium-Kurs mit Digistore24
```
1. Admin → Kurse → + Neuer Kurs
2. ❌ "Kostenlos" deaktiviert lassen
3. Digistore24 Produkt-ID eintragen: "12345"
4. Webhook ist bereits konfiguriert!
5. Bei Kauf → Automatische Freischaltung
```

### Use-Case 3: PDF-Kurs erstellen
```
1. Admin → Kurse → + Neuer Kurs
2. Typ: PDF
3. PDF hochladen
4. Customer kann PDF direkt im Browser öffnen
```

---

## 🛠️ Digistore24 Webhook Setup

### Webhook-URL (bereits konfiguriert):
```
https://app.ki-leadsystem.com/webhook/digistore24.php
```

### In Digistore24 einstellen:
1. Gehe zu **Produkt → Einstellungen**
2. **IPN-URL** = obige Webhook-URL
3. **Produkt-ID** notieren
4. Im Admin: Produkt-ID beim Kurs eintragen

### Testen:
1. Testkauf über Digistore24
2. Webhook wird automatisch ausgelöst
3. Customer erhält sofort Zugang
4. Kurs erscheint unter "Meine Kurse"

---

## ✅ Checkliste für Live-Gang

- [ ] Datenbank-Setup ausgeführt
- [ ] Erster Testkurs erstellt
- [ ] Admin-Vorschau funktioniert
- [ ] Customer kann Kurs sehen
- [ ] Video spielt ab (YouTube/Vimeo)
- [ ] Fortschritt wird gespeichert
- [ ] Digistore24 Webhook getestet (falls Premium)
- [ ] Mockups hochgeladen
- [ ] Mobile Ansicht getestet

---

## 🎨 Design-Anpassungen

### Farben ändern:
Öffne die CSS-Dateien und ändere:
```css
:root {
    --primary: #a855f7;         /* Deine Hauptfarbe */
    --primary-dark: #8b40d1;    /* Dunklere Variante */
    --success: #4ade80;         /* Erfolgsfarbe */
}
```

### Logo hinzufügen:
In `customer/course-view.php` und `admin/preview_course.php`:
```html
<div class="sidebar-header">
    <img src="/assets/logo.png" alt="Logo" style="height: 40px;">
    <h2><?php echo $course['title']; ?></h2>
</div>
```

---

## 🐛 Häufige Probleme & Lösungen

### Problem: "Tabelle nicht gefunden"
**Lösung:** Setup nicht ausgeführt
```
→ https://app.mehr-infos-jetzt.de/setup/setup-course-system.php
```

### Problem: Video wird nicht angezeigt
**Lösung:** Falsche URL-Format
```
❌ https://youtu.be/VIDEO_ID
✅ https://www.youtube.com/watch?v=VIDEO_ID

❌ https://vimeo.com/video/VIDEO_ID
✅ https://vimeo.com/VIDEO_ID
```

### Problem: Fortschritt wird nicht gespeichert
**Lösung:** Browser-Console prüfen
```
F12 → Console → Fehlermeldungen?
→ Session abgelaufen?
→ course_progress Tabelle fehlt?
```

### Problem: Kunde sieht Premium-Kurs nicht
**Lösung:** Zugang fehlt
```
Admin → Prüfe course_access Tabelle
→ Webhook funktioniert?
→ Manuell Zugang gewähren über Admin
```

---

## 📊 Fortschritts-System

### Wie funktioniert es?

1. **Customer klickt auf Lektion**
   → Lektion öffnet sich
   
2. **Customer schaut Video**
   → Klickt "Als abgeschlossen markieren"
   
3. **API-Call an Backend**
   ```javascript
   POST /customer/api/mark-lesson-complete.php
   {
     "lesson_id": 123,
     "completed": true
   }
   ```

4. **Datenbank wird aktualisiert**
   ```sql
   INSERT INTO course_progress 
   (user_id, lesson_id, completed, completed_at)
   VALUES (1, 123, TRUE, NOW())
   ```

5. **Fortschrittsbalken aktualisiert sich**
   → 1/10 Lektionen = 10%
   → Grüner Balken wächst

---

## 🎯 Nächste Schritte

1. **Teste das System:**
   - Erstelle mehrere Kurse
   - Teste als Admin
   - Teste als Customer

2. **Füge echte Inhalte hinzu:**
   - Eigene Videos hochladen (YouTube)
   - Mockups erstellen
   - Beschreibungen schreiben

3. **Digistore24 aktivieren:**
   - Webhook einrichten
   - Testkauf durchführen
   - Automatische Freischaltung prüfen

4. **Design anpassen:**
   - Logo einfügen
   - Farben anpassen
   - Texte übersetzen

---

## 📞 Support & Hilfe

**Dokumentation:**
- Vollständige Doku: `VIDEOKURS_SYSTEM_README.md`
- Schnellstart (diese Datei): `VIDEOKURS_QUICKSTART.md`

**Bei Problemen:**
1. Prüfe diese Schnellstart-Anleitung
2. Schaue in die vollständige README
3. Browser-Console auf Fehler prüfen
4. Kontaktiere Support

---

## 🎉 Fertig!

Dein Videokurs-System ist jetzt einsatzbereit!

**Erste Schritte:**
1. ✅ Setup ausführen
2. ✅ Ersten Kurs erstellen
3. ✅ Testen, testen, testen
4. ✅ Live gehen!

**Viel Erfolg! 🚀**