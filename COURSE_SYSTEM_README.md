# 📚 Kursverwaltungssystem - Installations- und Nutzungsanleitung

## 🚀 Installation

### 1. Datenbank einrichten

Führe das Setup-Script aus, um alle benötigten Tabellen zu erstellen:

```
https://app.mehr-infos-jetzt.de/admin/setup-courses-system.php
```

Das Script erstellt automatisch:
- ✅ `courses` - Haupt-Tabelle für Kurse
- ✅ `course_modules` - Module für Video-Kurse
- ✅ `course_lessons` - Lektionen innerhalb der Module
- ✅ `course_access` - Freischaltungen für Kunden
- ✅ `course_progress` - Fortschrittsverfolgung
- ✅ Upload-Ordner für Dateien

### 2. Upload-Ordner Berechtigungen

Stelle sicher, dass die Upload-Ordner beschreibbar sind:
```bash
chmod -R 755 uploads/courses/
```

## 📖 Nutzung

### Admin-Bereich

#### Kurse verwalten
1. Gehe zu: `/admin/dashboard.php?page=templates`
2. Klicke auf **"+ Neuer Kurs"**
3. Fülle das Formular aus:
   - **Titel**: Name des Kurses
   - **Kurstyp**: Video-Kurs oder PDF-Kurs
   - **Beschreibung**: Kurze Beschreibung
   - **Mockup**: Vorschaubild hochladen oder URL eingeben
   - **PDF**: Nur bei PDF-Kursen - Dokument hochladen
   - **Kostenlos**: Checkbox für Freebie-Kurse
   - **Digistore24 ID**: Für automatische Freischaltung

#### Video-Kurs mit Modulen & Lektionen

1. **Kurs erstellen**
   - Wähle "Video-Kurs" als Typ
   - Speichere den Kurs

2. **Kurs bearbeiten**
   - Klicke auf "✏️ Bearbeiten" beim Kurs
   - Rechts siehst du: "📚 Module & Lektionen"

3. **Modul hinzufügen**
   - Klicke "📚 + Modul hinzufügen"
   - Gib Titel und Beschreibung ein
   - Speichere

4. **Lektionen hinzufügen**
   - Unter jedem Modul: "🎥 + Lektion hinzufügen"
   - Fülle aus:
     * Lektionstitel
     * Videolink (Vimeo oder YouTube)
     * Beschreibung (optional)
     * PDF-Anhang (optional, z.B. Arbeitsblatt)

5. **Reihenfolge ändern**
   - Nutze die ⋮⋮ Handle zum Drag & Drop (geplant)

#### PDF-Kurs erstellen

1. **Kurs erstellen**
   - Wähle "PDF-Kurs" als Typ
   - Lade das PDF-Dokument hoch
   - Speichere

2. **Fertig!**
   - PDF-Kurse haben keine Module/Lektionen
   - Das PDF wird direkt angezeigt

### Freischaltung

#### Option 1: Kostenloser Kurs (Freebie)
- Setze Checkbox "🎁 Kostenlos (Freebie)" beim Erstellen
- Kurs ist für ALLE Kunden sichtbar

#### Option 2: Digistore24 Webhook
1. Trage die **Digistore24 Produkt-ID** ein
2. Richte Webhook ein in Digistore24:
   - URL: `https://app.mehr-infos-jetzt.de/webhook/digistore24.php`
   - Events: Purchase Completed
3. Bei Kauf wird Kunde automatisch freigeschaltet

#### Option 3: Manuelle Freischaltung
Füge manuell einen Eintrag in `course_access` ein:
```sql
INSERT INTO course_access (user_id, course_id, access_source)
VALUES (123, 456, 'manual');
```

### Kunden-Bereich

Kunden finden ihre Kurse unter:
- **Übersicht**: `/customer/my-courses.php`
- **Kurs ansehen**: `/customer/course-view.php?id=X`

#### Funktionen für Kunden:
- ✅ Nur freigeschaltete Kurse sehen
- ✅ Fortschrittsbalken
- ✅ Video-Player (Vimeo/YouTube)
- ✅ PDF-Viewer (für PDF-Kurse)
- ✅ Lektionen als abgeschlossen markieren
- ✅ Fortschritt wird gespeichert
- ✅ PDF-Arbeitsblätter herunterladen

## 🎨 Design-Features

### Responsive Design
- ✅ Desktop-optimiert
- ✅ Tablet-freundlich
- ✅ Mobile-optimiert

### Dark Mode Theme
- Violett/Lila Farbschema
- Moderne Glasmorphism-Effekte
- Smooth Animationen

## 📁 Dateistruktur

```
admin/
├── setup-courses-system.php      # Installation
├── sections/
│   ├── templates.php              # Kursverwaltung
│   └── course-edit.php            # Kurs bearbeiten
└── api/courses/
    ├── create.php                 # Kurs erstellen
    ├── update.php                 # Kurs aktualisieren
    ├── delete.php                 # Kurs löschen
    ├── modules/
    │   ├── create.php             # Modul erstellen
    │   └── delete.php             # Modul löschen
    └── lessons/
        ├── create.php             # Lektion erstellen
        └── delete.php             # Lektion löschen

customer/
├── my-courses.php                 # Kursübersicht
├── course-view.php                # Kurs ansehen
└── api/
    └── mark-lesson-complete.php   # Fortschritt speichern

uploads/courses/
├── mockups/                       # Kurs-Vorschaubilder
├── pdfs/                          # PDF-Kurse
└── attachments/                   # Lektions-Anhänge
```

## 🔧 Technische Details

### Datenbank-Schema

#### courses
- `id` - Primärschlüssel
- `title` - Kurstitel
- `description` - Beschreibung
- `type` - ENUM('video', 'pdf')
- `mockup_url` - Vorschaubild
- `pdf_file` - PDF-Datei (nur bei PDF-Kursen)
- `is_freebie` - Kostenlos ja/nein
- `digistore_product_id` - Für Webhook

#### course_modules
- `id` - Primärschlüssel
- `course_id` - Foreign Key → courses
- `title` - Modultitel
- `sort_order` - Reihenfolge

#### course_lessons
- `id` - Primärschlüssel
- `module_id` - Foreign Key → course_modules
- `title` - Lektionstitel
- `video_url` - Vimeo/YouTube Link
- `pdf_attachment` - Optionales PDF
- `sort_order` - Reihenfolge

#### course_access
- `user_id` - Foreign Key → users
- `course_id` - Foreign Key → courses
- `access_source` - ENUM('freebie', 'digistore', 'manual')

#### course_progress
- `user_id` - Foreign Key → users
- `lesson_id` - Foreign Key → course_lessons
- `completed` - Boolean
- `completed_at` - Timestamp

## 🎯 Best Practices

### Video-URLs
- ✅ Vimeo: `https://vimeo.com/123456789`
- ✅ YouTube: `https://youtube.com/watch?v=ABC123`
- ✅ YouTube Short: `https://youtu.be/ABC123`

### Dateigröße
- Mockups: Max. 2MB empfohlen
- PDFs: Max. 10MB empfohlen
- Anhänge: Max. 5MB empfohlen

### Sicherheit
- ✅ Session-Prüfung für alle Admin-Bereiche
- ✅ Zugriffskontrolle für Kunden
- ✅ SQL-Injection-Schutz durch Prepared Statements
- ✅ File-Upload-Validierung

## 🐛 Troubleshooting

### Problem: Upload funktioniert nicht
**Lösung**: Prüfe Ordner-Berechtigungen
```bash
chmod -R 755 uploads/courses/
```

### Problem: Videos werden nicht angezeigt
**Lösung**: Prüfe Video-URL Format (siehe Best Practices)

### Problem: Fortschritt wird nicht gespeichert
**Lösung**: Prüfe ob `course_progress` Tabelle existiert
```sql
SHOW TABLES LIKE 'course_progress';
```

### Problem: Kunde sieht Kurs nicht
**Prüfe**:
1. Ist Kurs als Freebie markiert? ODER
2. Hat Kunde Zugang in `course_access`?

## 📞 Support

Bei Fragen oder Problemen:
- GitHub Issues: https://github.com/michaelg-pixel/ki-lead/issues
- Dokumentation: Siehe diese README

## 🎉 Fertig!

Das Kursverwaltungssystem ist jetzt einsatzbereit!

**Nächste Schritte:**
1. ✅ Setup-Script ausführen
2. ✅ Ersten Kurs erstellen
3. ✅ Module & Lektionen hinzufügen
4. ✅ Testen im Kunden-Bereich

Viel Erfolg! 🚀