-- ========================================
-- FONT SETTINGS FÜR FREEBIES
-- Fügt Schriftarten und Schriftgrößen hinzu
-- ========================================

-- Font-Felder für Freebies-Tabelle hinzufügen
ALTER TABLE freebies 
ADD COLUMN IF NOT EXISTS preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER heading_font,
ADD COLUMN IF NOT EXISTS preheadline_size INT DEFAULT 14 AFTER preheadline_font,
ADD COLUMN IF NOT EXISTS headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size,
ADD COLUMN IF NOT EXISTS headline_size INT DEFAULT 48 AFTER headline_font,
ADD COLUMN IF NOT EXISTS subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size,
ADD COLUMN IF NOT EXISTS subheadline_size INT DEFAULT 20 AFTER subheadline_font,
ADD COLUMN IF NOT EXISTS bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size,
ADD COLUMN IF NOT EXISTS bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font;

-- Bestehende Einträge mit Standardwerten aktualisieren
UPDATE freebies 
SET 
    preheadline_font = 'Poppins',
    preheadline_size = 14,
    headline_font = 'Poppins',
    headline_size = 48,
    subheadline_font = 'Poppins',
    subheadline_size = 20,
    bulletpoints_font = 'Poppins',
    bulletpoints_size = 16
WHERE preheadline_font IS NULL OR headline_font IS NULL;

-- ========================================
-- FERTIG! ✅
-- ========================================

SELECT 'Font-Spalten erfolgreich hinzugefügt!' as Status;
