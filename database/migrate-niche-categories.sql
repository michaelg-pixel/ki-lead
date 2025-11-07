-- ====================================================
-- Nischen-Kategorie Migration
-- Direkt in phpMyAdmin oder MySQL ausführen
-- ====================================================

-- Schritt 1: Prüfen ob Spalte in freebies existiert
-- Falls ja, wird ein Fehler angezeigt - das ist OK!
ALTER TABLE freebies 
ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER name;

-- Schritt 2: Prüfen ob Spalte in customer_freebies existiert
-- Falls ja, wird ein Fehler angezeigt - das ist OK!
ALTER TABLE customer_freebies 
ADD COLUMN niche VARCHAR(50) DEFAULT 'sonstiges' AFTER customer_id;

-- Schritt 3: Standard-Werte für bestehende Einträge setzen
UPDATE freebies 
SET niche = 'sonstiges' 
WHERE niche IS NULL OR niche = '';

UPDATE customer_freebies 
SET niche = 'sonstiges' 
WHERE niche IS NULL OR niche = '';

-- Schritt 4: Prüfen ob alles geklappt hat
SELECT 'freebies', COUNT(*) as total, 
       SUM(CASE WHEN niche = 'sonstiges' THEN 1 ELSE 0 END) as sonstiges,
       SUM(CASE WHEN niche != 'sonstiges' THEN 1 ELSE 0 END) as andere
FROM freebies
UNION ALL
SELECT 'customer_freebies', COUNT(*) as total, 
       SUM(CASE WHEN niche = 'sonstiges' THEN 1 ELSE 0 END) as sonstiges,
       SUM(CASE WHEN niche != 'sonstiges' THEN 1 ELSE 0 END) as andere
FROM customer_freebies;

-- ====================================================
-- Wenn alles erfolgreich war, solltest du Folgendes sehen:
-- - Zwei Zeilen mit Statistiken
-- - Keine Fehler (außer "Duplicate column" wenn bereits vorhanden)
-- ====================================================