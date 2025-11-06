# 🎓 Customer Freebie Videokurs System

## Überblick

Dieses System ermöglicht es Kunden, ihre eigenen Videokurse und PDF-Kurse direkt in ihren Freebies zu erstellen.

## 📋 Features

### Für Kunden:
- **Zwei-Tab-System** im Freebie-Editor
  - Tab 1: Einstellungen (bisherige Freebie-Einstellungen)
  - Tab 2: Videokurs (Module und Lektionen erstellen)

### Videokurs-Tab:
- ✅ Module erstellen, bearbeiten, löschen
- ✅ Lektionen mit Videos (YouTube/Vimeo) oder PDFs
- ✅ Unterstützte Video-URL-Formate:
  - `https://www.youtube.com/watch?v=VIDEO_ID`
  - `https://youtu.be/VIDEO_ID`
  - `https://player.vimeo.com/video/VIDEO_ID`
  - `https://vimeo.com/VIDEO_ID`
- ✅ Reihenfolge ändern per Drag & Drop oder manuell
- ✅ Beschreibungen für Module und Lektionen
- ✅ Mockup für Danke-Seite (falls nicht bei Eigenschaften hinterlegt)

### Lead-Zugang:
- ✅ Responsiver Videoplayer
- ✅ Module und Lektionen Sidebar (rechts auf Desktop, oben auf Mobile)
- ✅ Fortschritts-Tracking per E-Mail
- ✅ "Lektion abschließen" Button
- ✅ Automatisches Laden der nächsten Lektion
- ✅ PDF-Download-Funktion

### Danke-Seite:
- ✅ Button "Zum Videokurs" über dem Empfehlungsprogramm-Button
- ✅ Direkte Verlinkung zum Kursplayer
- ✅ Mockup-Anzeige aus Kurs-Einstellungen

## 🗂️ Datenbank-Struktur

### Neue Tabellen:

1. **freebie_courses** - Haupttabelle für Freebie-Kurse
   - `id` - Kurs-ID
   - `freebie_id` - Verknüpfung zum Freebie
   - `customer_id` - Eigentümer
   - `title` - Kurstitel
   - `description` - Kursbeschreibung

2. **freebie_course_modules** - Module
   - `id` - Modul-ID
   - `course_id` - Verknüpfung zum Kurs
   - `title` - Modultitel
   - `sort_order` - Reihenfolge

3. **freebie_course_lessons** - Lektionen
   - `id` - Lektions-ID
   - `module_id` - Verknüpfung zum Modul
   - `title` - Lektionstitel
   - `video_url` - Video-URL (YouTube/Vimeo)
   - `pdf_url` - PDF-URL
   - `description` - Beschreibung
   - `sort_order` - Reihenfolge

4. **freebie_course_progress** - Fortschritt (Lead-basiert)
   - `lead_email` - E-Mail des Leads
   - `lesson_id` - Abgeschlossene Lektion
   - `completed` - Abgeschlossen ja/nein
   - `completed_at` - Zeitstempel

### Erweiterte Tabellen:

**customer_freebies:**
- `has_course` - Boolean (hat Videokurs?)
- `course_mockup_url` - Mockup für Danke-Seite

## 🚀 Installation

### 1. Migration ausführen:
```
https://app.mehr-infos-jetzt.de/migrate-customer-freebie-courses.php
```

### 2. Neue Dateien verwenden:
- `/customer/custom-freebie-editor-tabs.php` - Editor mit Tab-System
- `/customer/freebie-course-player.php` - Videokurs-Player für Leads
- `/customer/api/freebie-course-api.php` - API für Kurs-Management

### 3. Danke-Seite aktualisiert:
- `/freebie/thankyou.php` wurde erweitert
- Zeigt automatisch den "Zum Videokurs" Button wenn Kurs vorhanden

## 📝 Verwendung

### Als Kunde:

1. **Freebie erstellen/bearbeiten:**
   ```
   Dashboard → Freebies → Neues Freebie / Bearbeiten
   ```

2. **Tab "Einstellungen":**
   - Normale Freebie-Einstellungen (Texte, Farben, Layout, etc.)
   - Video-URL für Optin-Seite (optional)
   - Mockup für Optin-Seite (optional)

3. **Tab "Videokurs":**
   - Klicke "Modul hinzufügen"
   - Gib Modultitel und Beschreibung ein
   - Klicke "Lektion hinzufügen" beim Modul
   - Füge Video-URL oder PDF-URL ein
   - Gib Lektionstitel und Beschreibung ein
   - Speichere Lektion

4. **Danke-Seite konfigurieren:**
   - Falls kein Mockup in Einstellungen: Mockup-URL im Videokurs-Tab hinterlegen
   - Button-Text anpassen (optional)

### Als Lead:

1. **Freebie anfragen:**
   - Lead füllt Optin-Formular aus
   - Kommt zur Danke-Seite

2. **Kurs starten:**
   - Klickt auf "Zum Videokurs" Button
   - Wird zu Kursplayer weitergeleitet
   - E-Mail wird in URL übergeben für Fortschritts-Tracking

3. **Kurs absolvieren:**
   - Videos anschauen
   - PDFs herunterladen
   - Lektion als abgeschlossen markieren
   - Automatisch zur nächsten Lektion

## 🎨 Design

### Desktop:
```
┌─────────────────────────────────────────┐
│  Kurstitel                              │
│  ━━━━━━━━━━━━━━━━ 25% ━━━━━━━━━━━━━━━  │
├─────────────────────────┬───────────────┤
│                         │ 📚 Module     │
│  Video Player (16:9)    │  ▸ Modul 1   │
│                         │   ✓ Lektion 1│
│                         │   ▶ Lektion 2│
│                         │   ○ Lektion 3│
│  Lektionstitel         │  ▸ Modul 2   │
│  Beschreibung...       │   ○ Lektion 4│
│                         │   ○ Lektion 5│
│  [Als abgeschlossen]    │              │
│  [PDF herunterladen]    │              │
└─────────────────────────┴───────────────┘
```

### Mobile:
```
┌───────────────────────┐
│ Kurstitel            │
│ ━━━━━━━ 25% ━━━━━━  │
│                      │
│ 📚 Module ▼         │
│ ▸ Modul 1           │
│ ▸ Modul 2           │
│                      │
│ Video Player         │
│                      │
│ Lektionstitel       │
│ Beschreibung...     │
│                      │
│ [Als abgeschlossen] │
│ [PDF herunterladen] │
└───────────────────────┘
```

## 🔗 URL-Struktur

### Editor:
```
/customer/custom-freebie-editor-tabs.php?id=123&tab=settings
/customer/custom-freebie-editor-tabs.php?id=123&tab=course
```

### Player:
```
/customer/freebie-course-player.php?freebie_id=123&email=lead@example.com
```

### API:
```
POST /customer/api/freebie-course-api.php
{
  "action": "create_module",
  "course_id": 123,
  "title": "Modul 1",
  "description": "..."
}
```

## 🛠️ API Endpoints

### Module:
- `create_module` - Modul erstellen
- `update_module` - Modul aktualisieren
- `delete_module` - Modul löschen
- `reorder_modules` - Reihenfolge ändern

### Lektionen:
- `create_lesson` - Lektion erstellen
- `update_lesson` - Lektion aktualisieren
- `delete_lesson` - Lektion löschen
- `reorder_lessons` - Reihenfolge ändern

### Fortschritt:
- `mark_complete` - Lektion als abgeschlossen markieren
- `get_progress` - Fortschritt abrufen

## 🔒 Sicherheit

- ✅ Session-basierte Authentifizierung für Kunden
- ✅ E-Mail-basiertes Tracking für Leads (kein Login erforderlich)
- ✅ SQL Injection Prevention (PDO Prepared Statements)
- ✅ XSS Prevention (htmlspecialchars)
- ✅ Zugriffskontrolle: Kunde kann nur eigene Kurse bearbeiten

## 📱 Responsive Breakpoints

- **Desktop**: > 1024px (Sidebar rechts)
- **Tablet**: 768-1024px (Sidebar oben, collapsed)
- **Mobile**: < 768px (Sidebar als Dropdown)

## 🎯 Beispiel-Workflow

1. Kunde erstellt Freebie mit Optin-Formular
2. Kunde wechselt zu Tab "Videokurs"
3. Kunde erstellt Modul "Einführung"
4. Kunde fügt 3 Lektionen hinzu mit YouTube-Videos
5. Kunde speichert Freebie
6. Lead füllt Optin-Formular aus
7. Lead kommt zur Danke-Seite
8. Lead klickt "Zum Videokurs"
9. Lead schaut Lektionen an
10. Lead markiert Lektionen als abgeschlossen
11. System tracked Fortschritt per E-Mail

## 🐛 Troubleshooting

### Problem: Video wird nicht angezeigt
**Lösung:**
- Prüfe Video-URL Format
- Teste URL direkt im Browser
- Stelle sicher, dass Video nicht privat/eingebettet-blockiert ist

### Problem: Fortschritt wird nicht gespeichert
**Lösung:**
- Prüfe ob E-Mail in URL korrekt übergeben wird
- Prüfe Datenbank-Tabelle `freebie_course_progress`
- Browser-Console auf JavaScript-Fehler prüfen

### Problem: Module/Lektionen werden nicht angezeigt
**Lösung:**
- Prüfe `sort_order` in Datenbank
- Prüfe Fremdschlüssel-Beziehungen
- Cache leeren

## 📊 Performance

- Videos werden von YouTube/Vimeo gestreamt (kein Hosting-Traffic)
- Fortschritt wird asynchron via AJAX gespeichert
- Lazy Loading für Videoplayer
- Optimierte Datenbankabfragen mit Indizes

## 🔄 Migration von alten Freebies

Alte Freebies ohne Kurse:
- Funktionieren weiterhin normal
- Können jederzeit um einen Kurs erweitert werden
- Button auf Danke-Seite erscheint nur wenn Kurs vorhanden

## 📧 Support

Bei Fragen oder Problemen:
- GitHub Issues: [Repository](https://github.com/michaelg-pixel/ki-lead/issues)
- Dokumentation: Diese README

## 📝 Changelog

### Version 1.0.0 (2025-11-06)
- ✅ Initiales Release
- ✅ Tab-System im Editor
- ✅ Module und Lektionen Management
- ✅ Videoplayer für Leads
- ✅ Fortschritts-Tracking
- ✅ Danke-Seite Integration
- ✅ Mobile Responsive Design

---

**Made with 💜 for Customer Success**
