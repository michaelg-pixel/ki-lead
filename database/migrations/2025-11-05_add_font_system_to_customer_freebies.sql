-- Migration: Font-System für Custom Freebies
-- Datum: 2025-11-05
-- Beschreibung: Fügt font_heading, font_body und font_size Felder zur customer_freebies Tabelle hinzu

-- Prüfen ob die Felder bereits existieren und nur hinzufügen wenn nicht vorhanden
ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS font_heading VARCHAR(100) DEFAULT 'Inter' AFTER cta_animation,
ADD COLUMN IF NOT EXISTS font_body VARCHAR(100) DEFAULT 'Inter' AFTER font_heading,
ADD COLUMN IF NOT EXISTS font_size ENUM('small', 'medium', 'large') DEFAULT 'medium' AFTER font_body;

-- Index für bessere Performance bei Font-Abfragen
CREATE INDEX IF NOT EXISTS idx_font_settings ON customer_freebies(font_heading, font_body, font_size);