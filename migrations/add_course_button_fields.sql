-- Migration: Button-Felder für Kurse hinzufügen
-- Datum: 2025-11-11
-- Beschreibung: Fügt Felder für einen Call-to-Action Button hinzu

ALTER TABLE courses
ADD COLUMN button_text VARCHAR(100) DEFAULT NULL COMMENT 'Text des CTA-Buttons',
ADD COLUMN button_url VARCHAR(500) DEFAULT NULL COMMENT 'Link/URL des Buttons',
ADD COLUMN button_new_window TINYINT(1) DEFAULT 1 COMMENT 'Button in neuem Fenster öffnen (1=ja, 0=nein)';
