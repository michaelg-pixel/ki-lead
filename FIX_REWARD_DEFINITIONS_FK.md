# Fix: Reward Definitions Foreign Key Fehler

## Problem

Beim Speichern von Belohnungsstufen tritt folgender Fehler auf:

```
Datenbankfehler: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'cf.freebie_id' in 'on clause'
```

## Ursache

Die Tabelle `reward_definitions` hat einen Foreign Key `freebie_id`, der auf die falsche Tabelle verweist:

- **IST-Zustand:** `freebie_id` → `freebies(id)` ❌
- **SOLL-Zustand:** `freebie_id` → `customer_freebies(id)` ✅

### Warum ist das ein Problem?

1. In der Anwendung wird `customer_freebie_id` verwendet (aus `customer_freebies` Tabelle)
2. Der Foreign Key verweist aber auf `freebies(id)` 
3. Beim Speichern entsteht ein Konflikt, weil die IDs nicht übereinstimmen
4. Die Validierung in `save.php` prüft gegen `customer_freebies`, aber der FK gegen `freebies`

## Lösung

Es gibt 3 Möglichkeiten, den Fix anzuwenden:

### Option 1: PHP-Script (Empfohlen)

```bash
cd /pfad/zum/projekt
php fix_reward_definitions_fk.php
```

Das Script:
- Prüft den aktuellen Zustand
- Entfernt den fehlerhaften Foreign Key
- Erstellt den korrekten Foreign Key
- Verifiziert die Änderungen
- Prüft die Daten-Integrität

### Option 2: SQL-Script direkt

```bash
mysql -u username -p database_name < fix_reward_definitions_fk.sql
```

Oder über phpMyAdmin:
1. Öffne phpMyAdmin
2. Wähle die Datenbank aus
3. Gehe zu "SQL"
4. Kopiere den Inhalt von `fix_reward_definitions_fk.sql`
5. Führe aus

### Option 3: Manuelle SQL-Befehle

```sql
-- 1. Alten FK entfernen
ALTER TABLE reward_definitions 
DROP FOREIGN KEY fk_reward_def_freebie;

-- 2. Neuen FK erstellen
ALTER TABLE reward_definitions 
ADD CONSTRAINT fk_reward_def_customer_freebie
FOREIGN KEY (freebie_id) 
REFERENCES customer_freebies(id) 
ON DELETE SET NULL
ON UPDATE CASCADE;
```

## Verifizierung

Nach dem Fix solltest du prüfen:

```sql
-- Prüfe den Foreign Key
SELECT 
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions'
    AND COLUMN_NAME = 'freebie_id';
```

Erwartetes Ergebnis:
- `CONSTRAINT_NAME`: `fk_reward_def_customer_freebie`
- `REFERENCED_TABLE_NAME`: `customer_freebies`
- `REFERENCED_COLUMN_NAME`: `id`

## Testen

1. Gehe zu: `https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=belohnungsstufen&freebie_id=6`
2. Erstelle eine neue Belohnungsstufe
3. Klicke auf "Speichern"
4. Der Fehler sollte nicht mehr auftreten ✅

## Betroffene Dateien

### Geändert:
- `database/migrations/2025-11-04_add_freebie_id_to_reward_definitions.sql`
  - Foreign Key verweist nun auf `customer_freebies(id)`

### Neu erstellt:
- `fix_reward_definitions_fk.php` - Automatisches Fix-Script
- `fix_reward_definitions_fk.sql` - SQL Fix-Script
- `FIX_REWARD_DEFINITIONS_FK.md` - Diese Dokumentation

## Keine Änderungen erforderlich

Die folgenden Dateien funktionieren bereits korrekt und benötigen KEINE Änderung:
- `api/rewards/save.php` ✅
- `api/rewards/list.php` ✅
- `customer/sections/belohnungsstufen.php` ✅
- `customer/sections/empfehlungsprogramm.php` ✅

## Wichtig

- ✅ Der Fix ist **idempotent** (kann mehrmals ausgeführt werden)
- ✅ **Keine Datenverluste** - bestehende Belohnungen bleiben erhalten
- ✅ `ON DELETE SET NULL` - bei Löschung eines Freebies wird nur die Referenz auf NULL gesetzt
- ✅ Funktioniert mit bestehenden Belohnungen ohne `freebie_id` (allgemeine Belohnungen)

## Sicherheit

Der Fix erzeugt **keine** Konflikte mit:
- Bestehenden Belohnungsstufen
- Allgemeinen Belohnungen (ohne Freebie-Zuordnung)
- Anderen Funktionen des Systems
- Foreign Keys in anderen Tabellen

## Support

Bei Problemen:
1. Prüfe die Fehlermeldung
2. Führe das PHP-Script mit `php -f fix_reward_definitions_fk.php` aus
3. Kontrolliere die Output-Meldungen
4. Bei weiterhin Problemen: DB-Backup erstellen und Entwickler kontaktieren
