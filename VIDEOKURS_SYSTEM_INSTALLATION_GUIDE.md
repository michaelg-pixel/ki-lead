# 🎓 Videokurs-System für Freebie-Plattform

## 📋 Übersicht

Dieses System erweitert deine Freebie-Plattform um ein vollständiges Videokurs-Modul. Kunden können zu ihren Freebies professionelle Videokurse mit Modulen und Lektionen erstellen. Nach dem Opt-In erhalten Teilnehmer Zugang zu einem modernen, Netflix-ähnlichen Videoplayer.

---

## 🚀 Installation

### Schritt 1: Datenbank aktualisieren

```bash
# 1. Backup erstellen (WICHTIG!)
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql

# 2. SQL-Script ausführen  
mysql -u username -p database_name < database/videokurs-system-updates.sql
```

**Wichtig:** Falls `customer_id` in `freebie_courses` fehlt, führe nach dem Script folgendes aus:

```sql
UPDATE freebie_courses fc
JOIN customer_freebies cf ON fc.freebie_id = cf.id
SET fc.customer_id = cf.customer_id
WHERE fc.customer_id IS NULL OR fc.customer_id = 0;
```

### Schritt 2: Dateien sind bereits hochgeladen! ✅

Die folgenden Dateien wurden automatisch via GitHub deployed:

1. **`custom-freebie-editor.php`** → `/customer/custom-freebie-editor.php` ✅
2. **`videokurs-player.php`** → `/public/videokurs-player.php` ✅
3. **`freebie-danke.php`** → `/public/freebie-danke.php` ✅

### Schritt 3: Testen

1. Melde dich als Kunde an
2. Öffne ein bestehendes Freebie oder erstelle ein neues
3. Wechsle zum Tab "🎥 Videokurs"
4. Aktiviere den Videokurs
5. Erstelle ein Modul und eine Lektion
6. Teste den Player-Link

---

## 🎯 Features

### ✅ Für Kunden (Freebie-Ersteller)

- **Tab-Navigation:** Übersichtliche Trennung zwischen Freebie-Einstellungen und Videokurs
- **Einfache Aktivierung:** Mit einem Klick Videokurs aktivieren
- **Modul-Verwaltung:** Module erstellen, bearbeiten, löschen
- **Lektions-Verwaltung:** Lektionen mit Video-URL, Text und PDF-Downloads
- **Drag & Drop Sortierung:** (kann noch implementiert werden)
- **Live-Vorschau:** Direktes Feedback beim Bearbeiten

### ✅ Für Teilnehmer (Freebie-Nutzer)

- **Netflix-Style Player:** Modernes, intuitives Interface
- **Fortschritts-Anzeige:** Visueller Fortschrittsbalken
- **Navigation:** Vorherige/Nächste Buttons
- **Sidebar:** Klickbare Lektionen-Liste mit Checkmarken
- **Mobile-Responsive:** Funktioniert auf allen Geräten
- **Video-Embedding:** Automatische Unterstützung für YouTube, Vimeo, etc.
- **Zusatzinhalte:** Texte und PDF-Downloads pro Lektion

---

## 🔐 Sicherheit

### Token-basierter Zugang

Der Videoplayer nutzt einen SHA256-Token zur Validierung:

```php
$token = hash('sha256', $freebie['id'] . $freebie['unique_id']);
```

**Eigenschaften:**
- ✅ Keine Anmeldung erforderlich
- ✅ Einzigartiger Token pro Freebie
- ✅ Kann nicht erraten werden
- ✅ Läuft nicht ab (Link bleibt dauerhaft gültig)

**Wichtig:** Der Link wird auf der Danke-Seite angezeigt und sollte auch in die Bestätigungs-E-Mail eingefügt werden.

### Session-basierter Fortschritt

Der Lernfortschritt wird in der Session gespeichert:

```php
$_SESSION['course_progress_' . $course_id] = [lesson_ids...];
```

**Vorteile:**
- ✅ Keine zusätzlichen DB-Queries
- ✅ Schnell und performant
- ✅ DSGVO-konform (keine persistente Speicherung)

**Alternative:** Nutze die optionale `freebie_course_progress` Tabelle für persistente Speicherung (siehe SQL-Script).

---

## 📊 Datenbank-Struktur

### Bestehende Tabellen (erweitert)

#### `customer_freebies`
```sql
- has_course (TINYINT) - Flag ob Videokurs existiert
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `freebie_courses`
```sql
- id (INT) PRIMARY KEY
- freebie_id (INT) - Referenz zu customer_freebies
- customer_id (INT) - ⚠️ NEU! Referenz zu customers
- title (VARCHAR)
- description (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `freebie_course_modules`
```sql
- id (INT) PRIMARY KEY
- course_id (INT) - Referenz zu freebie_courses
- title (VARCHAR)
- description (TEXT)
- sort_order (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `freebie_course_lessons`
```sql
- id (INT) PRIMARY KEY
- module_id (INT) - Referenz zu freebie_course_modules
- title (VARCHAR)
- video_url (VARCHAR) - YouTube, Vimeo, etc.
- content (TEXT) - Zusatztext
- pdf_url (VARCHAR) - Optional: PDF-Download
- sort_order (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Optionale Tabellen

Das SQL-Script erstellt auch optionale Tabellen für erweiterte Features:

- **`freebie_course_progress`** - Persistentes Fortschritts-Tracking
- **`freebie_course_certificates`** - Zertifikate für abgeschlossene Kurse
- **`freebie_course_ratings`** - Bewertungen für Lektionen

Diese sind nicht erforderlich, können aber später aktiviert werden.

---

## 🐛 Troubleshooting

### Problem: "customer_id fehlt in freebie_courses"

**Fehler:**
```
Column 'customer_id' cannot be null
```

**Lösung:**
```sql
-- 1. Spalte hinzufügen (falls noch nicht vorhanden)
ALTER TABLE freebie_courses ADD COLUMN customer_id INT(11) NOT NULL AFTER freebie_id;

-- 2. Bestehende Daten updaten
UPDATE freebie_courses fc
JOIN customer_freebies cf ON fc.freebie_id = cf.id
SET fc.customer_id = cf.customer_id;
```

### Problem: "Token ungültig"

**Ursache:** Token stimmt nicht überein oder Freebie nicht gefunden.

**Lösung:**
```php
// Token neu generieren (in freebie-danke.php):
$token = hash('sha256', $freebie['id'] . $freebie['unique_id']);
echo "Debug Token: " . $token;

// Prüfen ob Freebie has_course = 1 hat:
SELECT id, has_course FROM customer_freebies WHERE id = ?;
```

### Problem: Video wird nicht angezeigt

**Ursachen:**
1. Ungültige Video-URL
2. Video ist privat/nicht einbettbar
3. CORS-Policy blockiert Embedding

**Lösung:**
```php
// Video-URL Format prüfen:
YouTube: https://www.youtube.com/watch?v=VIDEO_ID
Vimeo: https://vimeo.com/VIDEO_ID

// Nicht unterstützt:
YouTube Shorts, private Videos, DRM-geschützte Inhalte
```

---

## 📞 Support & Hilfe

### Häufige Fragen

**F: Kann ich mehrere Videokurse zu einem Freebie hinzufügen?**
A: Aktuell nicht, aber kann erweitert werden (has_course → course_count).

**F: Unterstützt das System andere Video-Plattformen als YouTube/Vimeo?**
A: Ja, jede URL die in einen iframe eingebettet werden kann.

**F: Kann ich Videos selbst hosten?**
A: Ja, nutze einen CDN-Link oder `/uploads/videos/` Ordner.

**F: Wie schütze ich Videos vor Download?**
A: Nutze DRM-Lösungen wie Vimeo Pro oder Cloudflare Stream.

---

## 🎉 Fertig!

Das Videokurs-System ist jetzt einsatzbereit. Viel Erfolg mit deiner Freebie-Plattform!

---

**Version:** 1.0.0  
**Letzte Aktualisierung:** November 2025  
**Lizenz:** Proprietär
