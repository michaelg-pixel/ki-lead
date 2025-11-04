-- Migration: Mockup-Feld zur tutorials-Tabelle hinzufügen
-- Datum: 2025-11-04
-- Beschreibung: Ermöglicht das Hochladen von Mockup-Bildern für Tutorial-Videos

ALTER TABLE tutorials 
ADD COLUMN mockup_image VARCHAR(500) NULL AFTER thumbnail_url;

-- Index für Performance
CREATE INDEX idx_mockup ON tutorials(mockup_image);
