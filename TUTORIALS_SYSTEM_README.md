# Tutorial-System - Anleitungen & Tutorials

Vollständiges Video-Tutorial-System mit Kategorien-Verwaltung für Admin und Customer.

## 📋 Features

### Admin-Bereich
- ✅ Video-Verwaltung (Erstellen, Bearbeiten, Löschen)
- ✅ Kategorie-Verwaltung (Erstellen, Bearbeiten, Löschen)
- ✅ Vimeo-Integration
- ✅ Sortierung und Aktivierung von Videos
- ✅ Icon-Auswahl für Kategorien (Font Awesome)
- ✅ Responsive Design

### Customer-Bereich
- ✅ Übersichtliche Darstellung nach Kategorien
- ✅ Video-Popup-Player (Vimeo)
- ✅ Nur aktive Videos sichtbar
- ✅ Responsive Design
- ✅ Keyboard-Navigation (ESC zum Schließen)

## 🚀 Installation

### 1. Datenbank-Setup

Führe die SQL-Datei aus, um die Tabellen zu erstellen:

```bash
mysql -u dein_user -p deine_datenbank < setup/tutorials-system-setup.sql
```

Oder im Admin-Panel unter phpMyAdmin:
1. Öffne phpMyAdmin
2. Wähle deine Datenbank aus
3. Gehe zu "Importieren"
4. Wähle `setup/tutorials-system-setup.sql`
5. Klicke auf "OK"

Die Datenbank erstellt automatisch:
- `tutorial_categories` - Kategorie-Tabelle
- `tutorials` - Video-Tabelle mit Vimeo-URLs
- 5 Standard-Kategorien mit Beispiel-Videos

### 2. Menü-Einträge

Die Menü-Einträge sind bereits an den richtigen Positionen:

**Admin-Dashboard** (Position 3):
- Übersicht
- Kunden
- **Anleitungen & Tutorials** ← HIER
- Kurs-Freebies
- ...

**Customer-Dashboard** (Position 2):
- Dashboard
- **Anleitungen & Tutorials** ← HIER
- Kurse
- Templates
- ...

## 📖 Nutzung

### Admin-Bereich

#### Videos hinzufügen
1. Gehe zu **Admin Dashboard** → **Anleitungen & Tutorials**
2. Klicke auf **"Neues Video hinzufügen"**
3. Fülle das Formular aus:
   - **Titel**: Name des Videos
   - **Beschreibung**: Kurze Beschreibung (optional)
   - **Vimeo Video URL**: z.B. `https://player.vimeo.com/video/123456789`
   - **Kategorie**: Wähle eine Kategorie aus
   - **Sortierung**: Niedrigere Zahlen = höhere Priorität
   - **Aktiv**: Haken setzen, um Video für Kunden sichtbar zu machen
4. Klicke auf **"Video speichern"**

#### Kategorien erstellen
1. Gehe zum Tab **"Kategorien verwalten"**
2. Klicke auf **"Neue Kategorie"**
3. Fülle das Formular aus:
   - **Name**: z.B. "Erste Schritte"
   - **Slug**: Wird automatisch generiert (z.B. "erste-schritte")
   - **Beschreibung**: Kurze Beschreibung der Kategorie
   - **Icon**: Font Awesome Icon-Name (z.B. "rocket", "star", "book")
   - **Sortierung**: Bestimmt die Reihenfolge der Kategorien
4. Klicke auf **"Kategorie speichern"**

### Customer-Bereich

Kunden können:
1. Alle aktiven Video-Tutorials nach Kategorien sortiert sehen
2. Videos durch Klick auf die Kachel im Popup abspielen
3. Videos per ESC-Taste oder Klick außerhalb schließen

## 🎥 Vimeo-Integration

### Vimeo-URL Format

Die URL muss das **Vimeo Player-Format** haben:
```
https://player.vimeo.com/video/DEINE_VIDEO_ID
```

**Beispiel:**
```
https://player.vimeo.com/video/987654321
```

### Vimeo-URL finden

1. Gehe zu deinem Vimeo-Video
2. Klicke auf **"Teilen"**
3. Kopiere die **"Player-URL"** (nicht die normale Video-URL!)

**Richtig:** `https://player.vimeo.com/video/123456789`
**Falsch:** `https://vimeo.com/123456789`

## 📁 Dateistruktur

```
admin/
├── dashboard.php                          # Menüeintrag hinzugefügt
├── sections/
│   └── tutorials.php                      # Admin-Interface
└── api/
    └── tutorials/
        ├── create-video.php               # Video erstellen
        ├── update-video.php               # Video bearbeiten
        ├── delete-video.php               # Video löschen
        ├── get-video.php                  # Video-Details abrufen
        ├── create-category.php            # Kategorie erstellen
        ├── update-category.php            # Kategorie bearbeiten
        ├── delete-category.php            # Kategorie löschen
        └── get-category.php               # Kategorie-Details abrufen

customer/
├── tutorials.php                          # Customer-Ansicht
└── includes/
    └── navigation.php                     # Menüeintrag hinzugefügt

setup/
└── tutorials-system-setup.sql             # Datenbank-Schema
```

## 🎨 Anpassungen

### Icons ändern

Du kannst alle Font Awesome Icons verwenden:
- Besuche: https://fontawesome.com/icons
- Suche ein Icon
- Verwende den Icon-Namen **ohne** "fa-" Präfix

**Beispiele:**
- `rocket` → 🚀
- `graduation-cap` → 🎓
- `star` → ⭐
- `video` → 🎥
- `book` → 📚

### Farben anpassen

Die Farben sind im Code anpassbar:
- **Hauptfarbe**: Purple/Lila (`from-purple-600 to-pink-600`)
- **Hintergrund**: Tailwind CSS Klassen

## 🔧 Fehlerbehebung

### Videos werden nicht angezeigt

**Problem:** Keine Videos sichtbar im Customer-Bereich

**Lösung:**
1. Prüfe, ob Videos als "Aktiv" markiert sind (Admin-Bereich)
2. Stelle sicher, dass die Kategorie Videos enthält
3. Überprüfe die Datenbank-Verbindung

### Vimeo-Video lädt nicht

**Problem:** Video-Player bleibt schwarz

**Lösung:**
1. Verwende die korrekte **Player-URL** (siehe oben)
2. Stelle sicher, dass das Video auf Vimeo **öffentlich** oder **unlisted** ist
3. Prüfe die Vimeo-Datenschutzeinstellungen

### Kategorie kann nicht gelöscht werden

**Problem:** Fehlermeldung beim Löschen

**Lösung:**
- Kategorien mit Videos können nicht gelöscht werden
- Lösche oder verschiebe erst alle Videos aus der Kategorie
- Dann kann die Kategorie gelöscht werden

## 📊 Datenbank-Schema

### `tutorial_categories`
| Feld | Typ | Beschreibung |
|------|-----|--------------|
| id | INT | Primärschlüssel |
| name | VARCHAR(100) | Kategorie-Name |
| slug | VARCHAR(100) | URL-freundlicher Slug |
| description | TEXT | Beschreibung |
| icon | VARCHAR(50) | Font Awesome Icon |
| sort_order | INT | Sortierung |
| created_at | TIMESTAMP | Erstellungsdatum |
| updated_at | TIMESTAMP | Änderungsdatum |

### `tutorials`
| Feld | Typ | Beschreibung |
|------|-----|--------------|
| id | INT | Primärschlüssel |
| category_id | INT | Fremdschlüssel zu Kategorie |
| title | VARCHAR(255) | Video-Titel |
| description | TEXT | Video-Beschreibung |
| vimeo_url | VARCHAR(500) | Vimeo Player-URL |
| thumbnail_url | VARCHAR(500) | Thumbnail (optional) |
| duration | VARCHAR(20) | Videolänge (optional) |
| sort_order | INT | Sortierung |
| is_active | TINYINT(1) | Aktiv/Inaktiv |
| created_at | TIMESTAMP | Erstellungsdatum |
| updated_at | TIMESTAMP | Änderungsdatum |

## 🛡️ Sicherheit

- ✅ Session-basierte Admin-Authentifizierung
- ✅ SQL-Injection-Schutz (PDO Prepared Statements)
- ✅ XSS-Schutz (htmlspecialchars)
- ✅ URL-Validierung für Vimeo-Links

## 📝 Best Practices

1. **Kategorien sinnvoll benennen**: z.B. "Erste Schritte", "Fortgeschritten"
2. **Kurze Video-Titel**: Maximal 50 Zeichen für beste Darstellung
3. **Sortierung nutzen**: 0 = höchste Priorität
4. **Videos aktivieren**: Nur fertige Videos auf "Aktiv" setzen
5. **Thumbnails hinzufügen**: Für bessere Übersicht (optional)

## 🎯 Roadmap / Mögliche Erweiterungen

- [ ] Thumbnail-Upload-Funktion
- [ ] Video-Fortschritt-Tracking
- [ ] Video-Bewertungssystem
- [ ] Suche/Filter-Funktion
- [ ] YouTube-Integration
- [ ] Video-Statistiken (Views)
- [ ] Multi-Language Support

## 💡 Support

Bei Fragen oder Problemen:
- 📧 E-Mail: support@ki-leadsystem.com
- 📖 Dokumentation: Siehe diese README

---

**Version:** 1.0.0
**Erstellt:** November 2025
**Lizenz:** Proprietär
