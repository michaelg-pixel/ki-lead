-- Migration: Font-Einstellungen zu customer_freebies Tabelle hinzufügen
-- Datum: 2025-01-04
-- Beschreibung: Fügt Spalten für Schriftart und -größe hinzu

-- Prüfen ob Spalten bereits existieren und nur hinzufügen wenn nicht vorhanden
SET @preparedStatement = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE customer_freebies 
         ADD COLUMN preheadline_font VARCHAR(100) DEFAULT "Poppins" AFTER bullet_points,
         ADD COLUMN preheadline_size INT DEFAULT 14 AFTER preheadline_font,
         ADD COLUMN headline_font VARCHAR(100) DEFAULT "Poppins" AFTER preheadline_size,
         ADD COLUMN headline_size INT DEFAULT 48 AFTER headline_font,
         ADD COLUMN subheadline_font VARCHAR(100) DEFAULT "Poppins" AFTER headline_size,
         ADD COLUMN subheadline_size INT DEFAULT 20 AFTER subheadline_font,
         ADD COLUMN bulletpoints_font VARCHAR(100) DEFAULT "Poppins" AFTER subheadline_size,
         ADD COLUMN bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font;',
        'SELECT "Spalten existieren bereits" AS info;'
    ) AS statement
    FROM information_schema.columns 
    WHERE table_schema = DATABASE()
    AND table_name = 'customer_freebies' 
    AND column_name = 'preheadline_font'
);

PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SELECT 'Migration erfolgreich: Font-Spalten zu customer_freebies hinzugefügt' AS status;
