-- Migration: Bullet Icon Style Spalte hinzufügen
-- Datum: 2025-11-05
-- Beschreibung: Fügt das Feld bullet_icon_style zur customer_freebies Tabelle hinzu

-- Spalte hinzufügen (nur wenn sie noch nicht existiert)
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS bullet_icon_style VARCHAR(20) DEFAULT 'standard' 
COMMENT 'Bullet point style: standard (checkmarks) oder custom (eigene Icons/Emojis)';

-- Index hinzufügen für bessere Performance
CREATE INDEX IF NOT EXISTS idx_bullet_icon_style ON customer_freebies(bullet_icon_style);

-- Erfolgs-Meldung
SELECT 'Migration erfolgreich: bullet_icon_style Spalte hinzugefügt' AS Status;
