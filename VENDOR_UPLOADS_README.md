# Vendor-Produktbilder Upload System

## Übersicht
Vendors können jetzt Produktbilder direkt hochladen statt URLs anzugeben.

## Änderungen im System

### 1. Template-Editor (`customer/sections/vendor/templates.php`)
**Entfernt:**
- ❌ Preis (€) Feld
- ❌ Digistore24 Produkt-ID Feld
- ❌ Umsatz-Anzeige in Template-Karten
- ❌ Verkäufe-Statistik
- ❌ Preis-Anzeige

**Hinzugefügt:**
- ✅ Drag & Drop Bild-Upload
- ✅ Bildvorschau im Editor
- ✅ Bild-Upload zum Server
- ✅ Downloads-Statistik
- ✅ Claims-Statistik
- ✅ Produktbild-Anzeige in Template-Karten

**Design-Verbesserungen:**
- ✅ Modal ohne doppelte Scrollbalken
- ✅ Optimiertes Layout mit Produktbild
- ✅ Responsive Design für mobile Geräte

### 2. Statistik-Seite (`customer/sections/vendor/statistics.php`)
**Entfernt:**
- ❌ Revenue-Karte
- ❌ Gesamt-Revenue Anzeige
- ❌ Revenue in Template-Tabelle
- ❌ Preis in Imports-Tabelle

**Hinzugefügt:**
- ✅ Durchschnittliche Downloads pro Template
- ✅ Download-fokussierte Statistiken
- ✅ Verbesserte Fehleranzeige

### 3. API-Endpunkte

#### Neuer Endpunkt: `/api/vendor/upload-image.php`
**Funktion:** Produktbilder hochladen
**Method:** POST (multipart/form-data)
**Parameter:**
- `image` (File): Bilddatei

**Validierung:**
- Erlaubte Formate: JPG, JPEG, PNG, WebP, GIF
- Maximale Größe: 5MB
- Automatische Namensgebung: `vendor_{vendor_id}_{uniqid}.{ext}`

**Response:**
```json
{
  "success": true,
  "url": "/uploads/vendor-products/vendor_123_abc123.jpg",
  "filename": "vendor_123_abc123.jpg"
}
```

#### Aktualisierte Endpunkte:
- `/api/vendor/templates/list.php` - Entfernt `sales_count`, `revenue`
- `/api/vendor/stats/overview.php` - Entfernt `total_revenue`

## Server-Setup

### 1. Upload-Verzeichnis erstellen
Das Upload-Verzeichnis wird automatisch von der API erstellt, falls es nicht existiert:
```
htdocs/app.mehr-infos-jetzt.de/uploads/vendor-products/
```

Manuelle Erstellung (falls nötig):
```bash
cd /home/u123456789/domains/mehr-infos-jetzt.de/public_html/htdocs/app.mehr-infos-jetzt.de
mkdir -p uploads/vendor-products
chmod 755 uploads/vendor-products
```

### 2. .htaccess für Vendor-Uploads (optional)
Erstelle `uploads/vendor-products/.htaccess`:
```apache
# Vendor Product Images .htaccess
# Erlaubt Zugriff auf Bilder

<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Verhindere Directory Listing
Options -Indexes

# Verhindere Zugriff auf andere Dateien
<FilesMatch "^(?!.*\.(jpg|jpeg|png|gif|webp)$).*$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

## Verwendung

### Für Vendors:

1. **Template erstellen/bearbeiten:**
   - Gehe zu Dashboard → Vendor-Bereich → Templates
   - Klicke "Neues Template" oder bearbeite ein bestehendes
   - Klicke in den Upload-Bereich oder ziehe ein Bild hinein
   - Das Bild wird automatisch hochgeladen und gespeichert

2. **Statistiken ansehen:**
   - Gehe zu Dashboard → Vendor-Bereich → Statistiken
   - Sieh Downloads und Claims deiner Templates
   - Analysiere die Performance über Zeit

### Template-Übersicht:
Jede Template-Karte zeigt:
- Produktbild (falls vorhanden)
- Template-Name und Kategorie
- Status (Veröffentlicht/Entwurf)
- Download-Anzahl
- Claims-Anzahl
- Aktionen (Bearbeiten, Veröffentlichen, Löschen)

## Datenbankfelder

Die folgenden Felder in `vendor_reward_templates` sind **nicht mehr relevant** für kostenlose Belohnungen:
- `marketplace_price` (kann 0 bleiben)
- `digistore_product_id` (kann NULL bleiben)
- `total_revenue` (wird nicht mehr verwendet)
- `sales_count` (wird nicht mehr verwendet)

**Wichtige Felder:**
- `product_mockup_url` - URL zum hochgeladenen Produktbild
- `times_imported` - Anzahl der Downloads
- `times_claimed` - Anzahl der Claims
- `is_published` - Veröffentlichungsstatus

## Fehlerbehandlung

### Upload-Fehler:
- **"Keine Datei hochgeladen"** - Stelle sicher, dass eine Datei ausgewählt wurde
- **"Ungültiger Dateityp"** - Nur Bildformate sind erlaubt
- **"Datei zu groß"** - Maximale Größe ist 5MB
- **"Fehler beim Speichern"** - Prüfe Schreibrechte des Verzeichnisses

### Statistik-Fehler:
- **"Nicht authentifiziert"** - Session abgelaufen, neu anmelden
- **"Kein Vendor"** - User ist nicht als Vendor berechtigt
- **"Datenbankfehler"** - Prüfe API-Logs

## Testing

### Template-Editor testen:
1. Erstelle neues Template
2. Lade Produktbild hoch
3. Fülle alle Pflichtfelder aus
4. Speichere Template
5. Prüfe ob Bild in Template-Übersicht erscheint

### Statistiken testen:
1. Erstelle mindestens 1 Template
2. Lasse einen anderen User das Template importieren
3. Prüfe ob Download in Statistiken erscheint
4. Prüfe Timeline-Chart

## Produktiv-Checkliste

- [x] Upload-API erstellt und getestet
- [x] Template-Editor ohne Preis/Digistore
- [x] Bild-Upload Funktionalität implementiert
- [x] Statistik-Seite ohne Revenue
- [x] Alle APIs angepasst
- [ ] Upload-Verzeichnis auf Server erstellt (wird automatisch erstellt)
- [ ] .htaccess für Uploads konfiguriert (optional)
- [ ] Deployment durchgeführt
- [ ] Frontend-Tests durchgeführt
- [ ] Statistiken verifiziert

## Support

Bei Problemen:
1. Prüfe Browser-Konsole auf JavaScript-Fehler
2. Prüfe Server-Logs für PHP-Fehler
3. Verifiziere Verzeichnisrechte
4. Teste API-Endpunkte direkt
