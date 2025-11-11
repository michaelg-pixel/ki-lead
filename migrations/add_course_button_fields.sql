-- Migration: Button-Felder für Kurse hinzufügen
-- Datum: 2025-11-11
-- Beschreibung: Fügt Felder für einen Call-to-Action Button hinzu
-- HINWEIS: Manuelle Ausführung nur falls automatische Migration fehlschlägt

-- Prüfe ob Spalten existieren und füge sie hinzu
ALTER TABLE courses
ADD COLUMN button_text VARCHAR(100) DEFAULT NULL COMMENT 'Text des CTA-Buttons',
ADD COLUMN button_url VARCHAR(500) DEFAULT NULL COMMENT 'Link/URL des Buttons',
ADD COLUMN button_new_window TINYINT(1) DEFAULT 1 COMMENT 'Button in neuem Fenster öffnen (1=ja, 0=nein)';

-- Falls Spalten bereits existieren, wird ein Fehler angezeigt - das ist OK!
-- Die Migration kann nicht mehrfach ausgeführt werden.
