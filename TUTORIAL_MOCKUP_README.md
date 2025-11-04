# Tutorial Mockup-Feature 📱

## Übersicht
Das Tutorial-System wurde erweitert, um Mockup-Bilder für Videos zu unterstützen. Diese Mockups werden sowohl im Admin- als auch im Customer-Dashboard angezeigt und machen die Videos visuell ansprechender.

## Features

### Admin-Bereich
- **Mockup-Upload**: Beim Erstellen oder Bearbeiten von Videos kann ein Mockup-Bild hochgeladen werden
- **Vorschau**: Live-Vorschau des hochgeladenen Mockups im Formular
- **Verwaltung**: Mockups können jederzeit gelöscht oder ersetzt werden
- **Visuelle Kennzeichnung**: Videos mit Mockups werden mit einem Badge gekennzeichnet

### Customer-Bereich
- **Ansprechende Darstellung**: Mockups werden als Thumbnail-Hintergrund angezeigt
- **Professionelles Design**: Play-Button überlagert das Mockup mit schönem Shadow-Effekt
- **Responsive**: Optimiert für Desktop und Mobile

## Installation

### 1. Datenbank-Migration ausführen

Rufe im Browser auf:
```
https://app.mehr-infos-jetzt.de/admin/migrate-add-mockup-field.php
```

Das Script:
- ✅ Fügt das `mockup_image` Feld zur `tutorials` Tabelle hinzu
- ✅ Erstellt das `/uploads/mockups/` Verzeichnis
- ✅ Setzt die korrekten Berechtigungen
- ✅ Erstellt .htaccess für sicheren Zugriff

### 2. Verzeichnis-Struktur
```
uploads/
└── mockups/
    ├── .htaccess              (Automatisch erstellt)
    └── mockup_*.{jpg,png,...} (Hochgeladene Bilder)
```

## Verwendung

### Als Admin: Video mit Mockup erstellen

1. **Navigiere zu**: Admin Dashboard → Anleitungen & Tutorials
2. **Klicke auf**: "Neues Video hinzufügen"
3. **Fülle die Felder aus**:
   - Titel *
   - Beschreibung (optional)
   - Vimeo Video URL *
   - **Mockup-Bild (optional)** 📱 ← Neu!
   - Kategorie *
   - Sortierung
   - Status (Aktiv/Inaktiv)

4. **Mockup hochladen**:
   - Klicke auf "Durchsuchen" beim Mockup-Feld
   - Wähle ein Bild (JPG, PNG, GIF, WebP)
   - Siehst sofort eine Vorschau
   - Optional: Klicke "Mockup entfernen" um es zu löschen

5. **Speichern**: Klicke "Video speichern"

### Video mit Mockup bearbeiten

1. **Öffne ein bestehendes Video** (Bearbeiten-Button)
2. **Optionen**:
   - **Neues Mockup hochladen**: Ersetzt das alte automatisch
   - **Mockup entfernen**: Klicke "Mockup entfernen"
   - **Behalten**: Einfach nichts ändern

### Als Customer: Videos ansehen

- Videos mit Mockups zeigen das Bild als attraktiver Hintergrund
- Der Play-Button überlagert das Mockup professionell
- Beim Hover wird das Mockup leicht abgedunkelt für besseren Kontrast

## Technische Details

### Datenbankstruktur
```sql
ALTER TABLE tutorials 
ADD COLUMN mockup_image VARCHAR(500) NULL AFTER thumbnail_url;
```

### Datei-Upload
- **Erlaubte Formate**: JPG, JPEG, PNG, GIF, WebP
- **Speicherort**: `/uploads/mockups/`
- **Namensschema**: `mockup_{timestamp}_{uniqid}.{ext}`
- **Automatische Bereinigung**: Alte Mockups werden beim Ersetzen gelöscht

### API-Endpunkte

#### Erstellen (POST)
```
/admin/api/tutorials/create-video.php
```
**Parameter**:
- `title` (required)
- `vimeo_url` (required)
- `category_id` (required)
- `mockup_image` (file, optional)
- `description` (optional)
- `sort_order` (optional)
- `is_active` (optional)

#### Aktualisieren (POST)
```
/admin/api/tutorials/update-video.php
```
**Zusätzliche Parameter**:
- `id` (required)
- `mockup_image` (file, optional) - Ersetzt altes Mockup
- `delete_mockup` (1/0) - Löscht Mockup ohne Ersatz

### Sicherheit

1. **Datei-Validierung**:
   - Nur Bild-Formate erlaubt
   - Eindeutige Dateinamen verhindern Überschreibungen

2. **.htaccess Schutz**:
   ```apache
   # Allow access to images
   <FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
       Require all granted
   </FilesMatch>
   ```

3. **Automatische Bereinigung**:
   - Alte Mockups werden beim Update gelöscht
   - Verhindert Speicherplatz-Verschwendung

## Empfohlene Bild-Spezifikationen

- **Format**: PNG oder JPG
- **Seitenverhältnis**: 16:9 (z.B. 1920x1080)
- **Dateigröße**: < 2 MB
- **Inhalt**: 
  - Screenshots der App/Website
  - Mockups auf Geräten (Phone, Tablet, Laptop)
  - Branded Content mit Logo

## Fehlerbehandlung

### Upload-Fehler
```php
// Fehler werden als JSON zurückgegeben:
{
    "success": false,
    "message": "Ungültiges Dateiformat. Erlaubt: JPG, PNG, GIF, WebP"
}
```

### Bei Fehler
1. Überprüfe Dateiformat
2. Stelle sicher, dass `/uploads/mockups/` existiert und beschreibbar ist (755)
3. Prüfe PHP upload_max_filesize und post_max_size

## Beispiel-Workflow

```
1. Admin erstellt neues Video:
   ├── Titel: "Dashboard-Tour"
   ├── Vimeo URL: https://player.vimeo.com/video/123456
   ├── Mockup: dashboard_mockup.png hochladen
   └── Kategorie: "Erste Schritte"

2. System:
   ├── Validiert Bild-Format ✓
   ├── Generiert eindeutigen Namen: mockup_1699123456_abc123.png
   ├── Speichert in /uploads/mockups/
   ├── Speichert Pfad in DB: /uploads/mockups/mockup_1699123456_abc123.png
   └── Zeigt Erfolgsmeldung

3. Customer sieht:
   ├── Video-Karte mit Mockup als Hintergrund
   ├── Play-Button im Vordergrund
   └── Professionelle Darstellung
```

## Migration rückgängig machen

Falls nötig, kann die Migration rückgängig gemacht werden:

```sql
-- Spalte entfernen
ALTER TABLE tutorials DROP COLUMN mockup_image;

-- Index entfernen (falls vorhanden)
DROP INDEX idx_mockup ON tutorials;
```

## Support

Bei Problemen oder Fragen:
- Überprüfe die Browser-Konsole auf Fehler
- Schaue in die PHP-Error-Logs
- Stelle sicher, dass alle Dateien korrekt hochgeladen wurden

## Changelog

### Version 1.0.0 (2025-11-04)
- ✨ Mockup-Upload für Tutorial-Videos hinzugefügt
- 🎨 Visuelle Darstellung in Admin- und Customer-Bereich
- 🔒 Sicherheits-Validierung für Uploads
- 📱 Responsive Design für alle Bildschirmgrößen
- 🗑️ Automatische Bereinigung alter Mockups
