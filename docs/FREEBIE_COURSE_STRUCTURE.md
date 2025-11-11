# Freebie & Videokurs Editor - Dokumentation

## 📁 Neue Dateistruktur

Die große Freebie-Editor-Datei wurde in kleinere, wartbare Dateien aufgeteilt:

### 1. **edit-freebie.php** - Freebie Einstellungen
**Pfad:** `/public/customer/edit-freebie.php`

**Features:**
- Optin-Seite bearbeiten
- Texte (Headline, Subheadline, Bullet Points)
- Video & Mockup-Bild
- Design (Layout, Farben, Fonts)
- E-Mail Optin Code Integration
- Custom Tracking Code
- Marktplatz-Kategorie Auswahl
- Live-Vorschau

**Navigation:**
- Tab "⚙️ Einstellungen" (diese Seite)
- Tab "🎓 Videokurs" (Link zu edit-course.php)

---

### 2. **edit-course.php** - Videokurs Editor
**Pfad:** `/public/customer/edit-course.php`

**Features:**
- Module erstellen, bearbeiten, löschen
- Lektionen erstellen, bearbeiten, löschen
- Alle Lektions-Features:
  - Video URL (YouTube, Vimeo, etc.)
  - PDF URL (Download-Links)
  - Button (Text + URL für Affiliate/Kauf-Links)
  - Drip Content (Freischaltung nach X Tagen)

**Navigation:**
- Tab "⚙️ Einstellungen" (Link zu edit-freebie.php)
- Tab "🎓 Videokurs" (diese Seite)

---

### 3. **course-modules.php** - Module API
**Pfad:** `/public/api/course-modules.php`

**Endpunkte:**
```javascript
// Modul erstellen
POST /api/course-modules.php
{
  "action": "create",
  "course_id": 123,
  "title": "Modul 1: Grundlagen",
  "description": "Einführung in..."
}

// Modul bearbeiten
POST /api/course-modules.php
{
  "action": "update",
  "id": 456,
  "title": "Neuer Titel",
  "description": "Neue Beschreibung"
}

// Modul löschen
POST /api/course-modules.php
{
  "action": "delete",
  "id": 456
}
```

---

### 4. **course-lessons.php** - Lektionen API
**Pfad:** `/public/api/course-lessons.php`

**Endpunkte:**
```javascript
// Lektion erstellen
POST /api/course-lessons.php
{
  "action": "create",
  "module_id": 789,
  "title": "Lektion 1: Einführung",
  "description": "In dieser Lektion...",
  "video_url": "https://youtube.com/watch?v=...",
  "pdf_url": "https://example.com/dokument.pdf",
  "button_text": "Jetzt kaufen",
  "button_url": "https://digistore24.com/...",
  "unlock_after_days": 0
}

// Lektion bearbeiten
POST /api/course-lessons.php
{
  "action": "update",
  "id": 999,
  "title": "Neuer Titel",
  // ... alle anderen Felder
}

// Lektion löschen
POST /api/course-lessons.php
{
  "action": "delete",
  "id": 999
}
```

---

## 🎯 Vorteile der neuen Struktur

### ✅ Wartbarkeit
- Jede Datei hat eine klare Aufgabe
- Code ist übersichtlicher und leichter zu debuggen
- Änderungen sind schneller umsetzbar

### ✅ Performance
- Kleinere Dateien laden schneller
- Trennung von Frontend und Backend (API)
- AJAX für bessere UX ohne Page Reload

### ✅ Skalierbarkeit
- Neue Features können einfach hinzugefügt werden
- API-Struktur erlaubt zukünftige Mobile-App
- Modulares System für weitere Funktionen

---

## 🗄️ Datenbank-Tabellen

Die Struktur nutzt die bestehenden Tabellen:

### `customer_freebies`
- Haupt-Freebie Einstellungen
- `category_id` → Nischen-Kategorie für Marktplatz
- `has_course` → Boolean für Videokurs

### `freebie_courses`
- Kurs-Informationen
- `freebie_id` → Verknüpfung zum Freebie

### `freebie_course_modules`
- Module des Kurses
- `course_id` → Verknüpfung zum Kurs
- `sort_order` → Reihenfolge

### `freebie_course_lessons`
- Lektionen innerhalb von Modulen
- `module_id` → Verknüpfung zum Modul
- `video_url` → YouTube/Vimeo Link
- `pdf_url` → PDF Download
- `button_text` + `button_url` → CTA Button
- `unlock_after_days` → Drip Content (0 = sofort)
- `sort_order` → Reihenfolge

### `freebie_template_categories`
- Marktplatz-Kategorien/Nischen

---

## 🚀 Verwendung

### 1. Neues Freebie erstellen
1. Gehe zu `/customer/dashboard.php?page=freebies`
2. Klicke "Neues Freebie erstellen"
3. Öffnet `edit-freebie.php` im Erstellungs-Modus
4. Nach dem ersten Speichern → Zugriff auf Videokurs-Tab

### 2. Videokurs hinzufügen
1. Öffne bestehendes Freebie
2. Wechsel zum Tab "🎓 Videokurs"
3. Erstelle Module
4. Füge Lektionen zu Modulen hinzu
5. Konfiguriere Drip Content

### 3. Marktplatz vorbereiten
1. Wähle Nischen-Kategorie in Einstellungen
2. Fülle alle relevanten Felder aus
3. Erstelle professionellen Videokurs
4. Freebie ist bereit für Marktplatz

---

## 🔧 Technische Details

### Frontend
- Vanilla JavaScript (kein jQuery)
- Fetch API für AJAX-Requests
- CSS Grid für Layouts
- Mobile-responsive

### Backend
- PHP 7.4+
- PDO für Datenbank
- JSON API Responses
- Session-basierte Auth

### Security
- Session-Checks in allen Dateien
- Prepared Statements (SQL Injection Prevention)
- Customer-ID Validierung bei allen DB-Operationen
- JSON Input Validation

---

## 📝 Zukünftige Erweiterungen

Mögliche Features:
- Drag & Drop für Modul/Lektions-Reihenfolge
- Video-Upload direkt (statt nur URLs)
- Fortschritts-Tracking für Kunden
- Quiz/Tests nach Lektionen
- Zertifikate nach Kurs-Abschluss
- Community/Kommentar-Funktion

---

## ⚠️ Wichtige Hinweise

1. **Migration erforderlich**: Die Tabelle `freebie_template_categories` muss existieren
2. **Berechtigungen**: Nur Eigentümer können ihre Freebies bearbeiten
3. **Drip Content**: 0 = sofort verfügbar, 1+ = Tage nach Anmeldung

---

## 🐛 Troubleshooting

### Problem: Kategorien werden nicht angezeigt
**Lösung:** Führe die Migration aus unter `/tools/run-migrations.php`

### Problem: Module/Lektionen laden nicht
**Lösung:** Prüfe Browser-Console auf JavaScript-Fehler
**Lösung:** Prüfe, ob API-Dateien vorhanden sind

### Problem: Speichern funktioniert nicht
**Lösung:** Prüfe Session (eingeloggt?)
**Lösung:** Prüfe Browser-Console für API-Fehler

---

## 📞 Support

Bei Fragen oder Problemen:
1. Browser DevTools öffnen (F12)
2. Console-Tab prüfen
3. Network-Tab für API-Requests prüfen

---

**Erstellt:** November 2025  
**Version:** 1.0