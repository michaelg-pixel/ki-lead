-- Migration: Freebie-ID zu reward_definitions hinzufügen
-- Datum: 2025-11-04
-- Beschreibung: Fügt freebie_id Spalte zur reward_definitions Tabelle hinzu
--              um Belohnungen mit spezifischen Freebies zu verknüpfen

-- Prüfen ob Spalte bereits existiert
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions' 
    AND COLUMN_NAME = 'freebie_id'
);

-- Nur hinzufügen wenn sie noch nicht existiert
SET @sql = IF(@column_exists = 0,
    'ALTER TABLE reward_definitions 
     ADD COLUMN freebie_id INT NULL COMMENT "Verknüpfung zum Freebie (optional)" 
     AFTER user_id',
    'SELECT "Column freebie_id already exists" AS result'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Foreign Key hinzufügen (nur wenn Spalte neu erstellt wurde)
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions' 
    AND CONSTRAINT_NAME = 'fk_reward_def_freebie'
);

SET @sql_fk = IF(@fk_exists = 0 AND @column_exists = 0,
    'ALTER TABLE reward_definitions 
     ADD CONSTRAINT fk_reward_def_freebie
     FOREIGN KEY (freebie_id) 
     REFERENCES freebies(id) 
     ON DELETE SET NULL
     ON UPDATE CASCADE',
    'SELECT "Foreign key already exists or not needed" AS result'
);

PREPARE stmt_fk FROM @sql_fk;
EXECUTE stmt_fk;
DEALLOCATE PREPARE stmt_fk;

-- Index für bessere Performance (nur wenn noch nicht existiert)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions' 
    AND INDEX_NAME = 'idx_reward_def_freebie'
);

SET @sql_idx = IF(@idx_exists = 0 AND @column_exists = 0,
    'CREATE INDEX idx_reward_def_freebie ON reward_definitions(freebie_id)',
    'SELECT "Index already exists or not needed" AS result'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- Kombinations-Index für user_id + freebie_id (nur wenn noch nicht existiert)
SET @idx_combo_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reward_definitions' 
    AND INDEX_NAME = 'idx_reward_def_user_freebie'
);

SET @sql_idx_combo = IF(@idx_combo_exists = 0 AND @column_exists = 0,
    'CREATE INDEX idx_reward_def_user_freebie ON reward_definitions(user_id, freebie_id)',
    'SELECT "Combination index already exists or not needed" AS result'
);

PREPARE stmt_idx_combo FROM @sql_idx_combo;
EXECUTE stmt_idx_combo;
DEALLOCATE PREPARE stmt_idx_combo;

-- Status anzeigen
SELECT 
    'reward_definitions' AS table_name,
    @column_exists AS column_existed_before,
    @fk_exists AS foreign_key_existed,
    @idx_exists AS index_existed,
    @idx_combo_exists AS combo_index_existed,
    'Migration completed' AS status;

-- Daten prüfen
SELECT 
    COUNT(*) as total_rewards,
    COUNT(freebie_id) as rewards_with_freebie,
    COUNT(*) - COUNT(freebie_id) as rewards_without_freebie
FROM reward_definitions;

-- HINWEISE:
-- 1. Diese Migration ist idempotent - kann mehrmals ausgeführt werden
-- 2. freebie_id ist NULL-able, existierende Rewards bleiben gültig
-- 3. Nach Migration können Belohnungen optional mit Freebies verknüpft werden
-- 4. Foreign Key CASCADE: Bei Löschen eines Freebies wird freebie_id auf NULL gesetzt
