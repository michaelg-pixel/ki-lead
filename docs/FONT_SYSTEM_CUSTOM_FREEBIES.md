# 🎨 Font-System für Custom Freebie Editor

## Übersicht
Das Font-System ermöglicht es Kunden, die Schriftarten ihrer Custom Freebies individuell anzupassen - sowohl im Editor als auch in der öffentlichen Ansicht.

## Features
- ✅ **10 Websichere Fonts** (100% DSGVO-konform, keine externen Server)
- ✅ **10 Google Fonts** (hochwertige Premium-Schriften)
- ✅ **3 Schriftgrößen** (Klein, Mittel, Groß)
- ✅ **Separate Einstellungen** für Überschriften und Fließtext
- ✅ **Live-Preview** im Editor
- ✅ **Automatische Anwendung** in der öffentlichen Ansicht

## Installation

### 1. Datenbank-Migration ausführen
```bash
php database/run-font-system-migration.php
```

Oder direkt per Browser:
```
https://deine-domain.de/database/run-font-system-migration.php
```

### 2. Überprüfung
Nach der Migration sollten folgende Felder in der `customer_freebies` Tabelle existieren:
- `font_heading` (VARCHAR 100) - Schriftart für Überschriften
- `font_body` (VARCHAR 100) - Schriftart für Fließtext
- `font_size` (ENUM) - Schriftgröße (small, medium, large)

## Verfügbare Schriftarten

### Websichere Fonts (DSGVO-konform)
1. System UI - Systemschrift des Betriebssystems
2. Arial - Klassische Sans-Serif
3. Helvetica - Elegant und modern
4. Verdana - Gut lesbar
5. Trebuchet MS - Humanistische Sans-Serif
6. Georgia - Elegante Serif
7. Times New Roman - Klassische Serif
8. Courier New - Monospace
9. Tahoma - Kompakte Sans-Serif
10. Comic Sans MS - Verspielt und locker

### Google Fonts (Premium-Qualität)
1. Inter - Modern und vielseitig
2. Roboto - Googles Hausschrift
3. Open Sans - Freundlich und offen
4. Montserrat - Urban und modern
5. Poppins - Geometrisch und rund
6. Lato - Warm und ernst
7. Oswald - Verdichtet und kraftvoll
8. Raleway - Elegant und dünn
9. Playfair Display - Klassisch und elegant (Serif)
10. Merriweather - Lesbar und klassisch (Serif)

## Verwendung im Editor

### 1. Öffne den Custom Freebie Editor
```
/customer/custom-freebie-editor.php?id=FREEBIE_ID
```

### 2. Scrolle zum Abschnitt "Schriftarten & Größe"

### 3. Wähle deine Schriftarten
- **Überschrift-Schriftart**: Wird für Headline, Preheadline und alle H-Tags verwendet
- **Text-Schriftart**: Wird für Subheadline, Bulletpoints und Fließtext verwendet

### 4. Wähle die Schriftgröße
- **Klein**: Kompakt, für viel Text
- **Mittel**: Standard, ausgewogen (empfohlen)
- **Groß**: Auffällig, für kurze Texte

### 5. Live-Preview
Änderungen werden sofort in der Vorschau rechts angezeigt!

## Technische Details

### Font-Größen-Mapping

#### Klein (small)
- Headline: 32px
- Subheadline: 16px
- Body/Bullets: 14px
- Preheadline: 11px

#### Mittel (medium) - Standard
- Headline: 40px
- Subheadline: 20px
- Body/Bullets: 16px
- Preheadline: 13px

#### Groß (large)
- Headline: 48px
- Subheadline: 24px
- Body/Bullets: 18px
- Preheadline: 15px

### Datenbankstruktur
```sql
ALTER TABLE customer_freebies 
ADD COLUMN font_heading VARCHAR(100) DEFAULT 'Inter',
ADD COLUMN font_body VARCHAR(100) DEFAULT 'Inter',
ADD COLUMN font_size ENUM('small', 'medium', 'large') DEFAULT 'medium';
```

### Font-Stack-Implementierung
```php
// Webfonts (lokal, keine externen Requests)
$webfonts = [
    'System UI' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, ...',
    'Arial' => 'Arial, "Helvetica Neue", Helvetica, sans-serif',
    // ...
];

// Google Fonts (werden dynamisch von Google CDN geladen)
$google_fonts = [
    'Inter' => '"Inter", sans-serif',
    'Roboto' => '"Roboto", sans-serif',
    // ...
];
```

## Öffentliche Ansicht

### Automatische Font-Anwendung
Die Fonts werden automatisch in der öffentlichen Freebie-Ansicht angewendet:

```
https://deine-domain.de/freebie/?id=UNIQUE_ID
```

### Dynamisches Laden der Google Fonts
Wenn Google Fonts verwendet werden, werden sie automatisch vom Google CDN geladen:

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
```

### CSS-Anwendung
```css
body {
    font-family: 'Inter', sans-serif;
    font-size: 16px;
}

h1, h2, h3, .headline {
    font-family: 'Montserrat', sans-serif;
    font-size: 40px;
}
```

## Datenschutz

### Websichere Fonts
- ✅ **100% DSGVO-konform**
- ✅ Keine externen Server
- ✅ Keine IP-Adressen werden übertragen
- ✅ Keine Cookies

### Google Fonts
- ⚠️ Externe Requests an Google
- ⚠️ IP-Adresse wird übertragen
- ✅ Google hat Datenschutzerklärung angepasst
- ℹ️ Kunden sollten in Datenschutzerklärung darauf hinweisen

**Empfehlung**: Verwende Websichere Fonts für maximale Datenschutz-Konformität!

## Troubleshooting

### Problem: Fonts werden nicht angezeigt
**Lösung 1**: Cache leeren
```bash
# Browser-Cache leeren (Strg + Shift + R)
# Server-Cache leeren falls vorhanden
```

**Lösung 2**: Migration erneut ausführen
```bash
php database/run-font-system-migration.php
```

### Problem: Google Fonts laden nicht
**Lösung**: Prüfe ob Google Fonts CDN erreichbar ist
```bash
curl -I https://fonts.googleapis.com/css2?family=Inter
```

### Problem: Fonts im Editor aber nicht in öffentlicher Ansicht
**Lösung**: Prüfe ob `freebie/index.php` und `freebie/templates/layout1.php` aktuell sind

## Best Practices

### 1. Font-Kombination
- **Überschrift**: Auffällige, markante Schrift (z.B. Montserrat, Oswald)
- **Text**: Gut lesbare, neutrale Schrift (z.B. Inter, Open Sans)

### 2. Schriftgröße
- **Viel Text**: Klein wählen
- **Wenig Text**: Groß wählen für mehr Impact
- **Standard**: Mittel für ausgewogenes Design

### 3. Datenschutz
- Für B2B und Enterprise: Websichere Fonts bevorzugen
- Für Marketing und Design: Google Fonts für Premium-Qualität

## Support

Bei Problemen oder Fragen:
1. Prüfe diese Dokumentation
2. Schaue in die Browser-Konsole (F12)
3. Überprüfe die Datenbank-Felder
4. Kontaktiere den Support

## Changelog

### v1.0.0 (2025-11-05)
- ✨ Initiales Font-System
- ✨ 10 Websichere Fonts
- ✨ 10 Google Fonts
- ✨ 3 Schriftgrößen
- ✨ Live-Preview im Editor
- ✨ Automatische Anwendung in öffentlicher Ansicht
- ✨ DSGVO-konforme Option (Websafe Fonts)

## Lizenz
Proprietär - Teil des Ki-Lead Systems