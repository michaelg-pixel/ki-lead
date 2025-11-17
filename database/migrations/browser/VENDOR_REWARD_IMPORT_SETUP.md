# Vendor Reward Import System - Fix & Setup

## 🔧 Problem
Beim Importieren von Vendor-Templates kam der Fehler "Datenbankfehler". Dies lag an fehlenden Spalten in der `reward_definitions` Tabelle.

## ✅ Lösung

### Schritt 1: Migration ausführen
Rufe diese URL in deinem Browser auf:
```
https://app.mehr-infos-jetzt.de/database/migrations/browser/migrate-vendor-reward-import.html
```

**Wichtig:** Du musst als Admin eingeloggt sein!

### Schritt 2: Migration starten
Klicke auf den Button "🚀 Migration starten"

Die Migration fügt folgende Spalten hinzu:
- ✅ `freebie_id` - Zuordnung zu spezifischem Freebie (optional)
- ✅ `imported_from_template_id` - Tracking des Original-Templates
- ✅ `is_imported` - Markierung für importierte Templates
- ✅ `reward_delivery_type` - Auslieferungsart (manual, download, email, redirect)

Außerdem wird die Tabelle `reward_template_imports` erstellt für das Import-Tracking.

### Schritt 3: Testen
1. Gehe zu: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=marktplatz-browse
2. Wechsle zum Tab "🎁 Belohnungen"
3. Klicke auf ein Template
4. Klicke auf "📥 Jetzt importieren"

Das Template sollte jetzt erfolgreich importiert werden!

### Schritt 4: Importierte Belohnungen ansehen
Die importierten Belohnungen findest du unter:
```
https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=belohnungsstufen
```

## 🎯 Features nach Migration

### 1. Import in Belohnungssystem
- Templates können direkt in dein Empfehlungsprogramm importiert werden
- Wahlweise für ein bestimmtes Freebie oder als allgemeine Belohnung
- Alle Daten vom Vendor werden übernommen (Beschreibung, Farbe, Icon, etc.)

### 2. Tracking
- System trackt, welche Templates du bereits importiert hast
- Vendor sieht, wie oft sein Template importiert wurde
- Doppelte Imports werden verhindert

### 3. Integration
- Importierte Belohnungen erscheinen automatisch in deinen Belohnungsstufen
- Du kannst sie nach dem Import noch anpassen
- Alle bestehenden Funktionen bleiben erhalten

## 🛠️ Technische Details

### Neue Spalten in `reward_definitions`:
```sql
freebie_id INT                      -- Optional: Zuordnung zu Freebie
imported_from_template_id INT       -- Link zum Original-Template
is_imported BOOLEAN                 -- Markierung als Import
reward_delivery_type VARCHAR(50)    -- Auslieferungsart
```

### Neue Tabelle `reward_template_imports`:
```sql
CREATE TABLE reward_template_imports (
    id INT PRIMARY KEY,
    template_id INT,                -- Welches Template
    customer_id INT,                -- Wer hat importiert
    reward_definition_id INT,       -- Wohin importiert
    import_date DATETIME,           -- Wann importiert
    import_source VARCHAR(50)       -- Von wo (marketplace)
)
```

## 🚨 Fehlerbehebung

### Fehler: "Nicht authentifiziert"
➡️ Du musst als Admin eingeloggt sein

### Fehler: "Spalte bereits vorhanden"
➡️ Das ist OK! Die Migration kann mehrfach ausgeführt werden

### Fehler: "Foreign Key constraint fails"
➡️ Die Tabelle `vendor_reward_templates` muss existieren
➡️ Stelle sicher, dass die Vendor-Migration bereits ausgeführt wurde

### Migration prüfen
Nach der Migration kannst du folgendes SQL ausführen, um zu prüfen:
```sql
DESCRIBE reward_definitions;
DESCRIBE reward_template_imports;
```

## 📊 Workflow

1. **Vendor erstellt Template** → `/customer/dashboard.php?page=vendor-bereich&tab=templates`
2. **Vendor veröffentlicht Template** → Template erscheint im Marktplatz
3. **User browsed Marktplatz** → `/customer/dashboard.php?page=marktplatz-browse` (Tab: Belohnungen)
4. **User importiert Template** → Popup mit allen Details, dann "Importieren"
5. **System erstellt Belohnung** → In `reward_definitions` mit `is_imported=1`
6. **User sieht Belohnung** → `/customer/dashboard.php?page=belohnungsstufen`
7. **User kann anpassen** → Alle Felder können nach Import bearbeitet werden

## ✨ Vorteile

- ✅ **Zeitsparend**: Templates müssen nicht manuell nachgebaut werden
- ✅ **Professionell**: Nutze getestete Templates von anderen Vendors
- ✅ **Flexibel**: Nach Import frei anpassbar
- ✅ **Tracking**: Volle Transparenz über Imports
- ✅ **Freebie-spezifisch**: Optional pro Freebie oder global

## 📝 Hinweise

- Die Migration ist **sicher** und kann mehrfach ausgeführt werden
- Bestehende Daten werden **nicht überschrieben**
- Die Migration benötigt **keine manuellen SQL-Befehle**
- Alles läuft über das Browser-Interface

---

**Status:** ✅ Bereit für Produktion
**Getestet:** ✅ Migration-Script, Import-API, Frontend-Integration
**Dokumentation:** ✅ Vollständig
