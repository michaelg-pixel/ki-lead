# 🎓 Videokurs-System - Vollständige Dokumentation

Ein komplettes System für Video-Kurse und PDF-Kurse mit Admin-Vorschau, Customer-Dashboard, Fortschritts-Tracking und Digistore24-Integration.

---

## 📋 Inhaltsverzeichnis

1. [Überblick](#überblick)
2. [Installation](#installation)
3. [Funktionen](#funktionen)
4. [Admin-Bereich](#admin-bereich)
5. [Customer-Bereich](#customer-bereich)
6. [Datenbank-Struktur](#datenbank-struktur)
7. [Digistore24 Integration](#digistore24-integration)
8. [API-Endpoints](#api-endpoints)

---

## 🎯 Überblick

Das Videokurs-System ermöglicht:
- **Video-Kurse** mit Modulen und Lektionen (YouTube/Vimeo)
- **PDF-Kurse** als Download
- **Freebie-Kurse** (kostenlos für alle)
- **Premium-Kurse** (über Digistore24 kaufbar)
- **Fortschritts-Tracking** pro Lektion
- **Admin-Vorschau** vor Veröffentlichung
- **Responsives Design** für alle Geräte

---

## 🚀 Installation

### 1. Datenbank einrichten

**Option A: Automatisches Setup (empfohlen)**
```
https://app.mehr-infos-jetzt.de/setup/setup-course-system.php
```

**Option B: Manuell über phpMyAdmin**
```bash
# SQL-Datei importieren
setup/course-system-setup.sql
```

### 2. Konfiguration überprüfen

Stelle sicher, dass folgende Dateien korrekt konfiguriert sind:
- `config/database.php` - Datenbankverbindung
- `webhook/digistore24.php` - Webhook-URL

### 3. Ordner-Berechtigungen

```bash
chmod 755 uploads/mockups/
chmod 755 uploads/pdfs/
```

---

## ✨ Funktionen

### Video-Kurse
- ✅ Unbegrenzte Module pro Kurs
- ✅ Unbegrenzte Lektionen pro Modul
- ✅ YouTube & Vimeo Integration
- ✅ PDF-Anhänge pro Lektion
- ✅ Fortschrittsbalken
- ✅ "Als abgeschlossen markieren"

### PDF-Kurse
- ✅ Direkte PDF-Anzeige
- ✅ Download-Funktion
- ✅ Mockup/Vorschaubild

### Zugriffskontrolle
- ✅ Freebie-Kurse (kostenlos für alle)
- ✅ Premium-Kurse (Digistore24)
- ✅ Manueller Admin-Zugang
- ✅ Automatische Freischaltung per Webhook

---

## 🔧 Admin-Bereich

### Kurse verwalten

**URL:** `https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=templates`

#### Kurs erstellen:
1. Klicke auf **"+ Neuer Kurs"**
2. Fülle folgende Felder aus:
   - **Kurstitel** (Pflicht)
   - **Kurstyp**: Video oder PDF
   - **Zugang**: Kostenlos (Freebie) oder Premium
   - **Beschreibung**
   - **Mockup-URL** oder Upload
   - **Digistore24 Produkt-ID** (für Premium-Kurse)
3. Klicke auf **"Kurs erstellen"**

#### Kurs bearbeiten:
**URL:** `https://app.mehr-infos-jetzt.de/admin/dashboard.php?page=course-edit&id={COURSE_ID}`

1. Module hinzufügen:
   - Klicke auf **"+ Modul hinzufügen"**
   - Titel und Beschreibung eingeben
   
2. Lektionen hinzufügen:
   - Klicke bei einem Modul auf **"+ Lektion hinzufügen"**
   - Titel, Beschreibung eingeben
   - Video-URL (YouTube/Vimeo)
   - Optional: PDF-Anhang hochladen

3. Reihenfolge ändern:
   - Drag & Drop (falls implementiert)
   - Oder manuell Sort-Order setzen

#### Vorschau anzeigen:
**URL:** `https://app.mehr-infos-jetzt.de/admin/preview_course.php?id={COURSE_ID}`

- Zeigt exakt wie der Kurs für Kunden aussieht
- Banner oben: "Admin-Vorschau"
- Alle Module und Lektionen navigierbar
- Video-Player funktioniert
- Keine Fortschritte werden gespeichert

---

## 👥 Customer-Bereich

### Kursübersicht

**URL:** `https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=kurse`

Zeigt zwei Bereiche:

#### ✅ Verfügbare Kurse
- Alle Freebie-Kurse
- Alle gekauften Premium-Kurse
- Mit Fortschrittsbalken (0-100%)
- Button: **"Kurs starten"** oder **"Weiter lernen (X%)"**

#### 🔒 Weitere Premium-Kurse
- Noch nicht gekaufte Kurse
- Mit 🔒 Badge
- Button: **"Jetzt kaufen"** → Link zu Digistore24
- Hover-Effekt: Schloss-Icon

### Kursansicht

**URL:** `https://app.mehr-infos-jetzt.de/customer/course-view.php?id={COURSE_ID}`

**Linke Sidebar:**
- Kurstitel
- Fortschrittsbalken (Gesamt)
- Module mit Lektionen
- ✅ Abgeschlossene Lektionen
- ▶️ Aktuelle Lektion
- ⚪ Noch nicht begonnen

**Hauptbereich:**
- Video-Player (16:9 responsive)
- Lektionstitel
- Beschreibung
- PDF-Download (falls vorhanden)
- Button: **"Als abgeschlossen markieren"**

---

## 💾 Datenbank-Struktur

### 1. `courses`
Haupttabelle für alle Kurse

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `title` | VARCHAR(255) | Kurstitel |
| `description` | TEXT | Kursbeschreibung |
| `type` | ENUM | 'video' oder 'pdf' |
| `mockup_url` | VARCHAR(500) | URL zum Vorschaubild |
| `pdf_file` | VARCHAR(500) | URL zur PDF-Datei |
| `is_freebie` | BOOLEAN | Kostenlos? |
| `is_active` | BOOLEAN | Aktiv? |
| `digistore_product_id` | VARCHAR(100) | Produkt-ID für Webhook |

### 2. `course_modules`
Module innerhalb eines Kurses

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `course_id` | INT | Fremdschlüssel → courses |
| `title` | VARCHAR(255) | Modultitel |
| `sort_order` | INT | Reihenfolge |

### 3. `course_lessons`
Lektionen innerhalb eines Moduls

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `module_id` | INT | Fremdschlüssel → course_modules |
| `title` | VARCHAR(255) | Lektionstitel |
| `video_url` | VARCHAR(500) | YouTube/Vimeo URL |
| `pdf_attachment` | VARCHAR(500) | PDF-Anhang |
| `sort_order` | INT | Reihenfolge |

### 4. `course_access`
Zugriffskontrolle

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `user_id` | INT | Fremdschlüssel → users |
| `course_id` | INT | Fremdschlüssel → courses |
| `access_source` | ENUM | 'freebie', 'purchase', 'admin', 'digistore24' |
| `granted_at` | TIMESTAMP | Zugang erteilt am |

### 5. `course_progress`
Fortschritt pro Lektion

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT | Primärschlüssel |
| `user_id` | INT | Fremdschlüssel → users |
| `lesson_id` | INT | Fremdschlüssel → course_lessons |
| `completed` | BOOLEAN | Abgeschlossen? |
| `completed_at` | TIMESTAMP | Abgeschlossen am |

---

## 🛒 Digistore24 Integration

### Webhook-URL
```
https://app.ki-leadsystem.com/webhook/digistore24.php
```

### Setup in Digistore24:
1. Gehe zu **Produkt-Einstellungen**
2. **IPN-URL** setzen auf obige Webhook-URL
3. **Produkt-ID** notieren
4. In Admin: Produkt-ID beim Kurs hinterlegen

### Automatischer Ablauf:
1. Kunde kauft Kurs über Digistore24
2. Digistore24 sendet IPN an Webhook
3. Webhook erstellt Zugang in `course_access`
4. Kunde sieht Kurs sofort in "Meine Kurse"

### Webhook-Payload Beispiel:
```json
{
  "event": "payment",
  "product_id": "12345",
  "buyer_email": "kunde@example.com",
  "order_id": "DS24-ABC123"
}
```

---

## 🔌 API-Endpoints

### 1. Kurse erstellen
**POST** `/admin/api/courses/create.php`

```javascript
const formData = new FormData();
formData.append('title', 'Mein Kurs');
formData.append('type', 'video');
formData.append('is_freebie', true);

fetch('/admin/api/courses/create.php', {
  method: 'POST',
  body: formData
});
```

**Response:**
```json
{
  "success": true,
  "course_id": 123
}
```

### 2. Kurs aktualisieren
**POST** `/admin/api/courses/update.php`

### 3. Kurs löschen
**POST** `/admin/api/courses/delete.php`

### 4. Modul erstellen
**POST** `/admin/api/courses/modules/create.php`

### 5. Lektion erstellen
**POST** `/admin/api/courses/lessons/create.php`

### 6. Fortschritt speichern
**POST** `/customer/api/mark-lesson-complete.php`

```javascript
fetch('/customer/api/mark-lesson-complete.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    lesson_id: 456,
    completed: true
  })
});
```

**Response:**
```json
{
  "success": true,
  "message": "Lektion als abgeschlossen markiert"
}
```

---

## 🎨 Design-System

### Farben
```css
--primary: #a855f7         /* Lila */
--primary-dark: #8b40d1    /* Dunkel-Lila */
--primary-light: #c084fc   /* Hell-Lila */
--success: #4ade80         /* Grün */
--error: #fb7185           /* Rot */
--bg-primary: #0a0a16      /* Dunkel */
--bg-secondary: #1a1532    /* Mittel */
--text-primary: #e5e7eb    /* Hell */
```

### Komponenten
- **Cards**: 16px Radius, Gradient Hintergrund
- **Buttons**: 10px Radius, Hover-Effekte
- **Progress Bar**: 8px Höhe, Gradient Fill
- **Video Player**: 16:9 Aspect Ratio

---

## 📱 Responsive Design

### Breakpoints:
- **Desktop**: > 1024px (3 Spalten Grid)
- **Tablet**: 768-1024px (2 Spalten Grid)
- **Mobile**: < 768px (1 Spalte)

### Mobile Optimierungen:
- Sidebar wird zu Full-Screen Overlay
- Video bleibt 16:9 responsive
- Touch-optimierte Buttons
- Reduzierte Abstände

---

## 🔒 Sicherheit

### Zugriffskontrolle:
- ✅ Session-basierte Authentifizierung
- ✅ Rollen-Check (Admin/Customer)
- ✅ SQL Injection Prevention (PDO Prepared Statements)
- ✅ XSS Prevention (htmlspecialchars)
- ✅ CSRF-Token (empfohlen)

### Best Practices:
```php
// ✅ Richtig
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);

// ❌ Falsch
$pdo->query("SELECT * FROM courses WHERE id = $course_id");
```

---

## 🐛 Troubleshooting

### Problem: Kurse werden nicht angezeigt
**Lösung:** 
- Prüfe `is_active = 1` in Datenbank
- Prüfe Zugriff in `course_access` Tabelle
- Überprüfe Session-Status

### Problem: Video lädt nicht
**Lösung:**
- YouTube URL Format: `https://www.youtube.com/watch?v=VIDEO_ID`
- Vimeo URL Format: `https://vimeo.com/VIDEO_ID`
- Prüfe Embed-Berechtigung des Videos

### Problem: Fortschritt wird nicht gespeichert
**Lösung:**
- Prüfe `course_progress` Tabelle existiert
- Session aktiv?
- Browser-Console auf JS-Fehler prüfen

---

## 📊 Beispiel-Workflow

### Kompletter Kurs-Setup (Video):

1. **Admin: Kurs erstellen**
   ```
   Titel: "KI Mastery für Anfänger"
   Typ: Video
   Kostenlos: Ja
   ```

2. **Admin: Module hinzufügen**
   ```
   Modul 1: "Einführung" (3 Lektionen)
   Modul 2: "Fortgeschritten" (5 Lektionen)
   ```

3. **Admin: Lektionen hinzufügen**
   ```
   Lektion 1.1: "Was ist KI?"
   Video: https://youtube.com/watch?v=xyz
   PDF: ki-basics.pdf
   ```

4. **Admin: Vorschau testen**
   ```
   Klick auf "Vorschau" Button
   → Alle Lektionen navigierbar
   → Video spielt ab
   ```

5. **Customer: Kurs starten**
   ```
   Dashboard → Meine Kurse
   → "KI Mastery" anklicken
   → Lektion 1.1 öffnet sich
   ```

6. **Customer: Lektion abschließen**
   ```
   Video anschauen
   → "Als abgeschlossen markieren"
   → Fortschritt 12.5% (1/8 Lektionen)
   ```

---

## 🚀 Performance-Tipps

1. **Video-Optimierung:**
   - Nutze YouTube/Vimeo statt Self-Hosting
   - Aktiviere Lazy-Loading

2. **Datenbank:**
   - Indizes auf häufig abgefragte Spalten
   - Regelmäßiges Vacuum/Optimize

3. **Caching:**
   - Kurs-Daten cachen (Redis/Memcached)
   - Browser-Cache für statische Assets

---

## 📞 Support

Bei Fragen oder Problemen:
- **Dokumentation:** Siehe diese README
- **GitHub Issues:** [Repository Issues](https://github.com/michaelg-pixel/ki-lead/issues)
- **E-Mail:** support@example.com

---

## 📝 Changelog

### Version 1.0.0 (2025-11-01)
- ✅ Initiales Release
- ✅ Video-Kurse mit Modulen & Lektionen
- ✅ PDF-Kurse
- ✅ Admin-Vorschau
- ✅ Customer-Dashboard
- ✅ Fortschritts-Tracking
- ✅ Digistore24 Integration

---

## 📄 Lizenz

Proprietary - Alle Rechte vorbehalten

---

**Viel Erfolg mit deinem Videokurs-System! 🎉**