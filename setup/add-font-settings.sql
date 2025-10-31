-- ========================================
-- FONT SETTINGS FÜR FREEBIES
-- Fügt Schriftarten und Schriftgrößen hinzu
-- MySQL kompatible Version
-- ========================================

-- Font-Felder für Freebies-Tabelle hinzufügen
-- HINWEIS: Dieses Script prüft NICHT ob Spalten bereits existieren
-- Verwende stattdessen das PHP-Setup: /setup/add-font-settings.php

ALTER TABLE freebies 
ADD COLUMN preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER body_font;

ALTER TABLE freebies 
ADD COLUMN preheadline_size INT DEFAULT 14 AFTER preheadline_font;

ALTER TABLE freebies 
ADD COLUMN headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size;

ALTER TABLE freebies 
ADD COLUMN headline_size INT DEFAULT 48 AFTER headline_font;

ALTER TABLE freebies 
ADD COLUMN subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size;

ALTER TABLE freebies 
ADD COLUMN subheadline_size INT DEFAULT 20 AFTER subheadline_font;

ALTER TABLE freebies 
ADD COLUMN bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size;

ALTER TABLE freebies 
ADD COLUMN bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font;

-- Bestehende Einträge mit Standardwerten aktualisieren
UPDATE freebies 
SET 
    preheadline_font = COALESCE(preheadline_font, 'Poppins'),
    preheadline_size = COALESCE(preheadline_size, 14),
    headline_font = COALESCE(headline_font, 'Poppins'),
    headline_size = COALESCE(headline_size, 48),
    subheadline_font = COALESCE(subheadline_font, 'Poppins'),
    subheadline_size = COALESCE(subheadline_size, 20),
    bulletpoints_font = COALESCE(bulletpoints_font, 'Poppins'),
    bulletpoints_size = COALESCE(bulletpoints_size, 16)
WHERE preheadline_font IS NULL 
   OR headline_font IS NULL 
   OR subheadline_font IS NULL 
   OR bulletpoints_font IS NULL;

-- ========================================
-- FERTIG! ✅
-- ========================================

SELECT 'Font-Spalten erfolgreich hinzugefügt!' as Status;
