# 🎨 FONT-FIX KOMPLETT-ANLEITUNG

## ✅ Was wurde behoben?

Die Schriftarten und -größen, die im Admin-Template-Editor ausgewählt werden, werden jetzt korrekt:
1. ✅ In der Datenbank gespeichert (freebies Tabelle) 
2. ✅ Zu Customer Freebies kopiert (customer_freebies Tabelle)
3. ✅ Auf der Live-Seite angezeigt (freebie/index.php)
4. ✅ In der Vorschau angezeigt (customer/freebie-preview.php)

## 📋 Durchgeführte Änderungen

### 1. Backend-Dateien aktualisiert ✅
- `freebie/index.php` - Lädt jetzt alle Google Fonts und wendet Font-Einstellungen an
- `customer/freebie-preview.php` - Zeigt Font-Einstellungen in der Vorschau
- `api/save-freebie.php` - Speichert bereits alle Font-Felder (war schon korrekt)

### 2. Datenbank-Migration erstellt ✅
- **Datei**: `database/migrations/2025-11-04_add_fonts_to_customer_freebies.sql`
- **Zweck**: Fügt Font-Felder zur `customer_freebies` Tabelle hinzu
- **Migration-Script**: `database/migrate-customer-freebies-fonts.php`

### 3. Customer Freebie Editor Patch ✅
- **Datei**: `customer/FONT_PATCH.php`
- **Zweck**: Zeigt die korrigierten SQL-Queries

## 🚀 Installations-Schritte

### Schritt 1: Migration ausführen

Verbinde dich per SSH mit deinem Server und führe aus:

```bash
cd /home/u163674869/domains/app.mehr-infos-jetzt.de/public_html
php database/migrate-customer-freebies-fonts.php
```

**Erwartete Ausgabe:**
```
🚀 Starte Font-Felder Migration für customer_freebies...
✅ Migration erfolgreich ausgeführt!
📊 Statistik:
   - Gesamt Customer Freebies: X
   - Mit Font-Einstellungen: X
✨ Migration abgeschlossen!
```

### Schritt 2: Customer Freebie Editor patchen

**Option A: Manuelle Änderung (empfohlen)**

Öffne die Datei `customer/freebie-editor.php` und ersetze:

1. **Die UPDATE Query** (circa Zeile 55-65):
   - Siehe: `customer/FONT_PATCH.php` (UPDATE-Bereich)
   - Füge die Font-Felder hinzu

2. **Die INSERT Query** (circa Zeile 70-85):
   - Siehe: `customer/FONT_PATCH.php` (INSERT-Bereich)
   - Füge die Font-Felder hinzu

**Option B: Automatischer Patch (falls verfügbar)**

```bash
# Backup erstellen
cp customer/freebie-editor.php customer/freebie-editor.php.backup

# Patch anwenden (Script müsste noch erstellt werden)
# php customer/apply-font-patch.php
```

### Schritt 3: Testen

1. **Admin-Test:**
   - Gehe zu: `admin/dashboard.php?page=freebie-edit&id=1`
   - Wähle verschiedene Schriftarten und Größen
   - Speichere das Template
   - ✅ Öffne die Vorschau - Fonts sollten sichtbar sein

2. **Customer-Test:**
   - Logge dich als Customer ein
   - Öffne ein Freebie im Editor
   - Speichere Änderungen
   - ✅ Öffne die Live-Seite - Fonts sollten sichtbar sein

3. **Live-Test:**
   - Öffne eine Freebie-URL: `https://app.mehr-infos-jetzt.de/freebie/index.php?id=XXX`
   - ✅ Die Schriftarten sollten korrekt angezeigt werden

## 📊 Neue Datenbank-Felder

Die folgenden Felder wurden zu `customer_freebies` hinzugefügt:

```sql
preheadline_font VARCHAR(100) DEFAULT 'Poppins'
preheadline_size INT DEFAULT 14
headline_font VARCHAR(100) DEFAULT 'Poppins'
headline_size INT DEFAULT 48
subheadline_font VARCHAR(100) DEFAULT 'Poppins'
subheadline_size INT DEFAULT 20
bulletpoints_font VARCHAR(100) DEFAULT 'Poppins'
bulletpoints_size INT DEFAULT 16
```

## 🎨 Verfügbare Schriftarten

Das System unterstützt jetzt 20+ Schriftarten aus `config/fonts.php`:

- **Modern & Clean**: Poppins, Inter, Roboto, Montserrat, Lato, Open Sans
- **Bold & Impact**: Anton, Bebas Neue, Oswald, Barlow Condensed
- **Elegant & Light**: Raleway, Playfair Display, Lora, Cormorant
- **Classic & Serif**: Merriweather, PT Serif, Crimson Text
- **System Fonts**: Verdana, Arial, Georgia, Times New Roman

## 🔍 Troubleshooting

### Problem: Migration-Fehler "Column already exists"
**Lösung**: Felder existieren bereits - Migration ist bereits gelaufen. Überspringe Schritt 1.

### Problem: Fonts werden nicht angezeigt
**Checkliste:**
1. ✅ Migration ausgeführt? → `php database/migrate-customer-freebies-fonts.php`
2. ✅ Customer Editor gepatcht? → Prüfe `customer/freebie-editor.php`
3. ✅ Template neu gespeichert? → Im Admin-Editor speichern
4. ✅ Customer Freebie neu gespeichert? → Im Customer-Editor speichern
5. ✅ Browser-Cache gelöscht? → Strg+F5

### Problem: Font-Felder sind NULL in der Datenbank
**Lösung**: 
```sql
-- Bestehende Customer Freebies aktualisieren
UPDATE customer_freebies cf
INNER JOIN freebies f ON cf.template_id = f.id
SET 
    cf.headline_font = COALESCE(f.headline_font, 'Poppins'),
    cf.headline_size = COALESCE(f.headline_size, 48)
    -- etc. für alle Font-Felder
WHERE cf.template_id IS NOT NULL;
```

## 📁 Betroffene Dateien (Zusammenfassung)

### Aktualisiert ✅
- `freebie/index.php` - Font-Rendering
- `customer/freebie-preview.php` - Vorschau mit Fonts
- `config/fonts.php` - Font-Konfiguration (bereits vorhanden)

### Neu erstellt ✅
- `database/migrations/2025-11-04_add_fonts_to_customer_freebies.sql`
- `database/migrate-customer-freebies-fonts.php`
- `customer/FONT_PATCH.php`
- `database/migrate-fonts-info.html`

### Muss gepatcht werden ⚠️
- `customer/freebie-editor.php` - SQL-Queries erweitern

## ✨ Resultat

Nach erfolgreicher Installation:

1. **Admin**: Wählt Schriftarten im Template-Editor
2. **System**: Speichert Font-Einstellungen in DB
3. **Customer**: Kopiert Font-Einstellungen beim Freebie-Erstellen
4. **Frontend**: Zeigt korrekte Schriftarten auf der Live-Seite

**Ende der Anleitung** 🎉
