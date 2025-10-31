# Freebie Links & Tracking System

## 📋 Übersicht

Dieses System erstellt automatisch öffentliche Links für Freebie-Templates und Danke-Seiten mit integriertem Klick-Tracking und optionaler Link-Kürzung.

## ✨ Features

### 1. **Automatische Link-Generierung**
- Beim Erstellen eines Freebies werden automatisch Links generiert:
  - **Freebie-Link**: `/freebie/view.php?id={ID}`
  - **Danke-Seiten-Link**: `/freebie/thankyou.php?id={ID}`

### 2. **Link-Kürzung**
- Klick auf "🔗 Link kürzen" Button erstellt einen Short-Link
- Format: `/f/{6-stelliger-Code}` (z.B. `/f/aB3xY9`)
- Codes sind eindeutig und werden automatisch generiert

### 3. **Klick-Tracking**
- Jeder Aufruf wird automatisch gezählt
- Separate Zähler für:
  - **Freebie-Aufrufe** (freebie_clicks)
  - **Danke-Seiten-Aufrufe** (thank_you_clicks)
- Statistiken werden im Admin-Dashboard angezeigt

### 4. **Kopier-Funktion**
- Ein-Klick zum Kopieren der Links
- Visuelles Feedback nach erfolgreichem Kopieren

## 🚀 Installation

### Schritt 1: Datenbank erweitern

Führe das Migrations-Script aus:

```bash
php admin/extend-freebies-links.php
```

Oder führe manuell aus:

```sql
ALTER TABLE freebies ADD COLUMN public_link VARCHAR(255) DEFAULT NULL;
ALTER TABLE freebies ADD COLUMN short_link VARCHAR(100) DEFAULT NULL;
ALTER TABLE freebies ADD COLUMN thank_you_link VARCHAR(255) DEFAULT NULL;
ALTER TABLE freebies ADD COLUMN thank_you_short_link VARCHAR(100) DEFAULT NULL;
ALTER TABLE freebies ADD COLUMN freebie_clicks INT DEFAULT 0;
ALTER TABLE freebies ADD COLUMN thank_you_clicks INT DEFAULT 0;
ALTER TABLE freebies ADD COLUMN video_button_text VARCHAR(255) DEFAULT 'Zum Videokurs';
ALTER TABLE freebies ADD COLUMN video_course_url TEXT DEFAULT NULL;
ALTER TABLE freebies ADD COLUMN thank_you_headline VARCHAR(255) DEFAULT 'Vielen Dank!';
ALTER TABLE freebies ADD COLUMN thank_you_text TEXT DEFAULT NULL;
```

### Schritt 2: Vorhandene Templates aktualisieren

Das Migrations-Script generiert automatisch Links für alle existierenden Templates.

## 📁 Datei-Struktur

```
/freebie/
├── view.php              # Öffentliche Freebie-Anzeige
├── thankyou.php          # Danke-Seite mit Video-Button
└── .htaccess             # URL-Rewriting für Template

/f/
├── index.php             # Redirect-Handler für Short-Links
└── .htaccess             # URL-Rewriting für Short-Links

/api/
└── shorten-link.php      # API-Endpunkt für Link-Kürzung

/admin/sections/
└── freebies.php          # Admin-Dashboard mit Links & Tracking
```

## 🎨 Verwendung

### Im Admin-Dashboard

1. **Template erstellen/bearbeiten**
   - Links werden automatisch generiert

2. **Links anzeigen**
   - Unter jedem Template werden die Links angezeigt
   - Klick auf 📋 kopiert den Link
   - Klick auf "🔗 Link kürzen" erstellt einen Short-Link

3. **Tracking-Statistiken**
   - Werden direkt unter dem Template angezeigt
   - Updates erfolgen in Echtzeit bei jedem Aufruf

### Öffentliche Seiten

#### Freebie-Seite (`/freebie/view.php`)
- Zeigt das komplette Template mit:
  - Mockup-Bild
  - Headline, Subheadline, Preheadline
  - Bulletpoints
  - CTA-Button
  - Footer mit Impressum & Datenschutz
  - Responsive Design

#### Danke-Seite (`/freebie/thankyou.php`)
- Bestätigungs-Seite nach Freebie-Anforderung
- Zeigt:
  - Success-Animation
  - Dankeschön-Text
  - 3-Schritte-Anleitung
  - Video-Button (optional)
  - Footer

## 🔧 Konfiguration

### Danke-Seite anpassen

Im Freebie-Editor können folgende Felder konfiguriert werden:

```php
// Datenbankfelder
thank_you_headline    // Überschrift (Default: "Vielen Dank!")
thank_you_text        // Bestätigungstext
video_button_text     // Button-Text (Default: "Zum Videokurs")
video_course_url      // Video-URL (optional)
```

### Short-Link Format

Die Short-Codes sind:
- 6 Zeichen lang
- Alphanumerisch (a-z, A-Z, 0-9)
- Case-sensitive
- Eindeutig

## 📊 Tracking

### Automatisches Tracking

Beide Seiten tracken automatisch:

```php
// Freebie-View
UPDATE freebies SET freebie_clicks = freebie_clicks + 1 WHERE id = ?

// Thank-You-Page
UPDATE freebies SET thank_you_clicks = thank_you_clicks + 1 WHERE id = ?
```

### Statistik-Anzeige

Im Admin-Dashboard:
- 👁️ **Freebie-Klicks**: Anzahl der Aufrufe der Freebie-Seite
- ✓ **Danke-Klicks**: Anzahl der Aufrufe der Danke-Seite

## 🎯 API-Endpunkte

### Link kürzen

**POST** `/api/shorten-link.php`

Request:
```json
{
  "freebie_id": 123,
  "type": "freebie"  // oder "thankyou"
}
```

Response:
```json
{
  "success": true,
  "short_link": "/f/aB3xY9",
  "full_url": "https://domain.de/f/aB3xY9",
  "short_code": "aB3xY9"
}
```

## 🔒 Sicherheit

- Alle API-Endpunkte erfordern Admin-Login
- Short-Codes werden auf Eindeutigkeit geprüft
- SQL-Injection-Schutz durch Prepared Statements
- XSS-Schutz durch htmlspecialchars()

## 🐛 Troubleshooting

### Links funktionieren nicht

1. Prüfe .htaccess Dateien:
   ```bash
   # /freebie/.htaccess muss existieren
   # /f/.htaccess muss existieren
   ```

2. Prüfe Apache mod_rewrite:
   ```bash
   sudo a2enmod rewrite
   sudo service apache2 restart
   ```

3. Prüfe Dateirechte:
   ```bash
   chmod 644 /freebie/.htaccess
   chmod 644 /f/.htaccess
   ```

### Tracking funktioniert nicht

1. Prüfe Datenbank-Spalten:
   ```sql
   DESCRIBE freebies;
   ```
   
2. Stelle sicher, dass die Spalten existieren:
   - `freebie_clicks`
   - `thank_you_clicks`
   - `public_link`
   - `thank_you_link`

### Short-Links führen zu 404

1. Prüfe `/f/index.php` existiert
2. Prüfe `/f/.htaccess` Konfiguration
3. Teste manuell: `https://domain.de/f/index.php`

## 📝 Best Practices

1. **Links immer testen** nach Erstellung
2. **Short-Links nutzen** für Marketing-Materialien
3. **Tracking regelmäßig prüfen** im Dashboard
4. **Danke-Seite personalisieren** für bessere Conversion
5. **Video-URL setzen** für höheres Engagement

## 🆕 Neue Features in Planung

- [ ] QR-Code-Generierung für Links
- [ ] Erweiterte Analytics (Zeitverläufe, Geräte)
- [ ] A/B-Testing für verschiedene Designs
- [ ] E-Mail-Integration für Danke-Seite
- [ ] Custom-Domains für Short-Links

## 💡 Tipps

### Marketing
- Nutze Short-Links in Social Media Posts
- Verwende beschreibende Danke-Seiten-Texte
- Setze Video-URLs für höhere Engagement-Raten

### Design
- Halte Freebie-Seiten einfach und fokussiert
- Nutze starke CTAs
- Teste verschiedene Layouts

### Tracking
- Überwache Conversion-Rates (Freebie → Danke-Seite)
- Analysiere welche Templates am besten performen
- Optimiere basierend auf Daten

## 🤝 Support

Bei Fragen oder Problemen:
1. Prüfe diese Dokumentation
2. Schaue in die Log-Dateien
3. Teste die einzelnen Komponenten isoliert

## 📄 Lizenz

© 2025 - Alle Rechte vorbehalten
