-- Font-Felder zur customer_freebies Tabelle hinzufügen
-- Diese Migration erweitert die customer_freebies Tabelle um Schrifteinstellungen

ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER raw_code,
ADD COLUMN IF NOT EXISTS preheadline_size INT DEFAULT 14 AFTER preheadline_font,
ADD COLUMN IF NOT EXISTS headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size,
ADD COLUMN IF NOT EXISTS headline_size INT DEFAULT 48 AFTER headline_font,
ADD COLUMN IF NOT EXISTS subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size,
ADD COLUMN IF NOT EXISTS subheadline_size INT DEFAULT 20 AFTER subheadline_font,
ADD COLUMN IF NOT EXISTS bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size,
ADD COLUMN IF NOT EXISTS bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font;

-- Bestehende customer_freebies mit Font-Einstellungen aus ihren Templates aktualisieren
UPDATE customer_freebies cf
INNER JOIN freebies f ON cf.template_id = f.id
SET 
    cf.preheadline_font = COALESCE(f.preheadline_font, 'Poppins'),
    cf.preheadline_size = COALESCE(f.preheadline_size, 14),
    cf.headline_font = COALESCE(f.headline_font, 'Poppins'),
    cf.headline_size = COALESCE(f.headline_size, 48),
    cf.subheadline_font = COALESCE(f.subheadline_font, 'Poppins'),
    cf.subheadline_size = COALESCE(f.subheadline_size, 20),
    cf.bulletpoints_font = COALESCE(f.bulletpoints_font, 'Poppins'),
    cf.bulletpoints_size = COALESCE(f.bulletpoints_size, 16)
WHERE cf.template_id IS NOT NULL;
