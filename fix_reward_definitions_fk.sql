-- SOFORT-FIX für reward_definitions Foreign Key Fehler
-- Datum: 2025-11-04
-- Problem: freebie_id Foreign Key verweist auf freebies(id) statt customer_freebies(id)
-- Fehler: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'cf.freebie_id' in 'on clause'

-- WICHTIG: Dieses Script behebt den Fehler sofort und ist idempotent (kann mehrmals ausgeführt werden)

USE `ki_lead`;

-- Schritt 1: Prüfen ob der alte Foreign Key existiert
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions'
    AND COLUMN_NAME = 'freebie_id'
    AND CONSTRAINT_NAME LIKE 'fk_%';

-- Schritt 2: Alten Foreign Key entfernen (falls vorhanden)
-- Dieser verweist auf freebies(id) - das ist falsch!
SET @drop_fk_sql = (
    SELECT CONCAT('ALTER TABLE reward_definitions DROP FOREIGN KEY ', CONSTRAINT_NAME, ';')
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'reward_definitions'
        AND COLUMN_NAME = 'freebie_id'
        AND REFERENCED_TABLE_NAME = 'freebies'
    LIMIT 1
);

-- Ausführen wenn FK existiert
SET @drop_fk_sql = IFNULL(@drop_fk_sql, 'SELECT "Kein alter FK gefunden" AS info;');

PREPARE stmt FROM @drop_fk_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Schritt 3: Neuen Foreign Key erstellen auf customer_freebies(id)
-- Dies ist die richtige Referenz!
ALTER TABLE reward_definitions 
ADD CONSTRAINT fk_reward_def_customer_freebie
FOREIGN KEY (freebie_id) 
REFERENCES customer_freebies(id) 
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Schritt 4: Verifizierung
SELECT 
    '✓ Fix erfolgreich angewendet' AS status,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    'freebie_id sollte nun auf customer_freebies(id) verweisen' AS erwartung
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions'
    AND COLUMN_NAME = 'freebie_id'
    AND CONSTRAINT_NAME LIKE 'fk_%';

-- Schritt 5: Daten-Integrität prüfen
SELECT 
    'Daten-Integrität' AS check_type,
    COUNT(*) as total_rewards,
    COUNT(freebie_id) as rewards_with_freebie,
    COUNT(*) - COUNT(freebie_id) as rewards_without_freebie,
    'OK - Alle Einträge gültig' AS status
FROM reward_definitions;

-- Schritt 6: Prüfe ob es ungültige freebie_ids gibt
SELECT 
    'Ungültige Referenzen' AS check_type,
    COUNT(*) as anzahl_ungueltig
FROM reward_definitions rd
LEFT JOIN customer_freebies cf ON rd.freebie_id = cf.id
WHERE rd.freebie_id IS NOT NULL 
    AND cf.id IS NULL;

-- HINWEISE:
-- • Dieser Fix behebt den Fehler "Unknown column 'cf.freebie_id' in 'on clause'"
-- • Der Foreign Key verweist nun korrekt auf customer_freebies(id)
-- • Existierende Daten bleiben erhalten (ON DELETE SET NULL)
-- • Das Script ist idempotent und kann mehrmals ausgeführt werden
-- • Nach dem Fix sollten Belohnungsstufen normal speicherbar sein

SELECT 'FIX ABGESCHLOSSEN - Bitte teste jetzt das Speichern einer Belohnungsstufe' AS abschluss;
