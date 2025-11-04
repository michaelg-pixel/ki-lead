# Fix: Empfehlungsprogramm Freebie-Anzeige

**Datum:** 04.11.2025  
**Problem:** Neue Freebies wurden nicht im Empfehlungsprogramm angezeigt  
**Status:** ✅ Behoben

## Problem-Beschreibung

Wenn ein Kunde im Dashboard ein neues Freebie freigeschaltet hat, wurde dieses nicht im Empfehlungsprogramm-Bereich angezeigt. Nur "alte" Freebies waren sichtbar.

### Ursache

Die Empfehlungsprogramm-Sektion (`customer/sections/empfehlungsprogramm.php`) lud nur Freebies aus der `freebies` Tabelle, wo `user_id = $customer_id`. Dies zeigte nur:
- ❌ Eigene vom Kunden erstellte Freebies
- ❌ NICHT: Template-Freebies, die der Kunde freigeschaltet/angepasst hat

### Lösung

Die SQL-Abfrage wurde erweitert, um Freebies aus BEIDEN Quellen zu laden:

1. **Eigene Custom Freebies** aus `customer_freebies` wo `freebie_type = 'custom'`
2. **Freigeschaltete Template-Freebies** aus `customer_freebies` mit JOIN zu `freebies`

## Durchgeführte Änderungen

### 1. Empfehlungsprogramm-Sektion aktualisiert
**Datei:** `customer/sections/empfehlungsprogramm.php`

**Vorher:**
```php
$stmt_freebies = $pdo->prepare("
    SELECT DISTINCT
        f.id,
        f.unique_id,
        f.name as title,
        ...
    FROM freebies f
    WHERE f.user_id = ?
");
```

**Nachher:**
```php
// 1. Eigene Freebies
$stmt_custom = $pdo->prepare("
    SELECT ... FROM customer_freebies cf
    WHERE cf.customer_id = ? AND cf.freebie_type = 'custom'
");

// 2. Freigeschaltete Template-Freebies  
$stmt_templates = $pdo->prepare("
    SELECT ... FROM customer_freebies cf
    INNER JOIN freebies f ON cf.template_id = f.id
    WHERE cf.customer_id = ? AND cf.template_id IS NOT NULL
");

// Kombinieren
$freebies = array_merge($custom_freebies, $template_freebies);
```

**Vorteile:**
- ✅ Zeigt ALLE für den Kunden verfügbaren Freebies
- ✅ Unterscheidet zwischen eigenen und Template-Freebies (Badge)
- ✅ Verwendet korrekte `unique_id` aus `customer_freebies`

---

## Entdeckte Datenbank-Konflikte

### Konflikt 1: Fehlende `freebie_id` Spalte

**Problem:**
- Die API-Dateien (`api/rewards/save.php`, `api/rewards/list.php`) erwarten eine `freebie_id` Spalte in `reward_definitions`
- Die ursprüngliche Migration `006_reward_definitions.sql` hatte diese Spalte NICHT
- Dies führte zu SQL-Fehlern beim Speichern von Belohnungen

**Lösung:**
Neue Migration erstellt: `2025-11-04_add_freebie_id_to_reward_definitions.sql`

**Features der Migration:**
- ✅ Idempotent (kann mehrmals ausgeführt werden ohne Fehler)
- ✅ Prüft ob Spalte bereits existiert
- ✅ Fügt Foreign Key mit CASCADE hinzu
- ✅ Erstellt Performance-Indizes
- ✅ NULL-able (existierende Daten bleiben gültig)

---

## Migration ausführen

### Option 1: Via Browser
```
https://app.mehr-infos-jetzt.de/database/run-migrations.php
```

### Option 2: Via MySQL/phpMyAdmin
1. Öffne phpMyAdmin
2. Wähle deine Datenbank
3. Führe die SQL-Datei aus:
   ```
   database/migrations/2025-11-04_add_freebie_id_to_reward_definitions.sql
   ```

### Option 3: Via Kommandozeile
```bash
mysql -u username -p database_name < database/migrations/2025-11-04_add_freebie_id_to_reward_definitions.sql
```

---

## Verifizierung

### 1. Empfehlungsprogramm testen

1. Als Kunde einloggen
2. Zu Dashboard → Freebies gehen
3. Ein Template-Freebie "nutzen" und speichern
4. Zu Dashboard → Empfehlungsprogramm gehen
5. ✅ Das neue Freebie sollte nun in der Auswahl sichtbar sein

### 2. Belohnungsstufen testen

1. Im Empfehlungsprogramm ein Freebie auswählen
2. Auf "Belohnungen konfigurieren" klicken
3. Neue Belohnungsstufe erstellen
4. ✅ Sollte ohne SQL-Fehler gespeichert werden
5. ✅ Belohnung sollte mit dem Freebie verknüpft sein

### 3. Datenbank prüfen

```sql
-- Prüfe ob freebie_id Spalte existiert
DESCRIBE reward_definitions;

-- Prüfe bestehende Daten
SELECT 
    COUNT(*) as total_rewards,
    COUNT(freebie_id) as rewards_with_freebie,
    COUNT(*) - COUNT(freebie_id) as rewards_without_freebie
FROM reward_definitions;

-- Prüfe Foreign Keys
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'reward_definitions'
AND CONSTRAINT_NAME = 'fk_reward_def_freebie';
```

---

## Potenzielle weitere Konflikte (bereits geprüft)

### ✅ customer_freebies Struktur
- Tabelle existiert und hat alle nötigen Spalten
- `template_id` für Verknüpfung zu Templates
- `freebie_type` zur Unterscheidung ('custom' vs 'template')
- `unique_id` für öffentliche Links

### ✅ freebies Tabelle
- Enthält Admin-erstellte Templates
- `user_id` verweist auf den Ersteller
- Keine Konflikte mit `customer_freebies`

### ✅ reward_definitions Struktur
- Nach Migration vollständig kompatibel mit APIs
- Foreign Key Constraints sichern Datenintegrität
- Indizes für gute Performance

---

## Zusammenfassung

| Bereich | Status | Aktion erforderlich |
|---------|--------|---------------------|
| Empfehlungsprogramm-Anzeige | ✅ Behoben | Nein |
| `reward_definitions.freebie_id` | ⚠️ Migration nötig | **Ja - Migration ausführen** |
| API-Kompatibilität | ✅ Kompatibel | Nein |
| Datenbank-Indizes | ✅ Optimiert | Nein |
| Foreign Keys | ✅ Gesichert | Nein |

---

## Nächste Schritte

1. **Migration ausführen** (siehe oben)
2. **Verifizierung durchführen** (siehe Checkliste)
3. **Bestehende Belohnungen überprüfen**
   - Alte Belohnungen ohne `freebie_id` bleiben funktional
   - Optional: Nachträglich Freebies zuweisen

## Support & Fragen

Bei Fragen oder Problemen:
1. Prüfe die Error-Logs: `/var/log/apache2/error.log`
2. Prüfe Browser-Console auf JavaScript-Fehler
3. Verifiziere Datenbank-Struktur (siehe SQL oben)
