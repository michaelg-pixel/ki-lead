-- Migration: Freebie-Integration für Reward Tiers
-- Datum: 2025-11-03
-- Beschreibung: Fügt freebie_id Spalte zur reward_tiers Tabelle hinzu

-- Schritt 1: Spalte hinzufügen
ALTER TABLE reward_tiers 
ADD COLUMN freebie_id INT NULL COMMENT 'Verknüpfung zum Freebie (optional)';

-- Schritt 2: Foreign Key hinzufügen (falls freebies Tabelle existiert)
ALTER TABLE reward_tiers 
ADD CONSTRAINT fk_reward_tiers_freebie
FOREIGN KEY (freebie_id) 
REFERENCES freebies(id) 
ON DELETE SET NULL
ON UPDATE CASCADE;

-- Schritt 3: Index für bessere Performance
CREATE INDEX idx_reward_tiers_freebie ON reward_tiers(freebie_id);

-- Schritt 4: Index für user_id + freebie_id Kombination
CREATE INDEX idx_reward_tiers_user_freebie ON reward_tiers(user_id, freebie_id);

-- Schritt 5: Bestehende Daten prüfen
SELECT COUNT(*) as total_rewards FROM reward_tiers;
SELECT COUNT(*) as rewards_with_freebie FROM reward_tiers WHERE freebie_id IS NOT NULL;

-- Optional: Beispiel-Daten einfügen (für Testing)
-- INSERT INTO reward_tiers (
--     user_id, 
--     freebie_id,
--     tier_level, 
--     tier_name, 
--     required_referrals,
--     reward_type,
--     reward_title,
--     is_active
-- ) VALUES (
--     1,  -- Ersetze mit echter user_id
--     1,  -- Ersetze mit echter freebie_id
--     1,
--     'Bronze',
--     3,
--     'ebook',
--     'Gratis E-Book',
--     1
-- );

-- Hinweise:
-- 1. Backup der Datenbank vor Ausführung erstellen
-- 2. Migration kann auf bestehenden Daten ausgeführt werden
-- 3. freebie_id ist NULL-able, existierende Rewards bleiben gültig
-- 4. Nach Migration sollten APIs getestet werden
