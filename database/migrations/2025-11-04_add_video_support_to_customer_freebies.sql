-- Migration: Video-Support für Customer Freebies
-- Datum: 2025-11-04
-- Beschreibung: Fügt Video-URL und Video-Format Felder zur customer_freebies Tabelle hinzu

ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) NULL AFTER mockup_image_url,
ADD COLUMN IF NOT EXISTS video_format ENUM('portrait', 'widescreen') DEFAULT 'widescreen' AFTER video_url;

-- Index für bessere Performance bei Video-Abfragen
CREATE INDEX IF NOT EXISTS idx_customer_freebies_video ON customer_freebies(video_url);
