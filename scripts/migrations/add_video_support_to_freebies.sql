-- Video-Felder zur customer_freebies Tabelle hinzufügen
-- Datum: 2025-11-02
-- Beschreibung: Ermöglicht Kunden Videos (YouTube, Vimeo, etc.) statt Mockup-Bilder zu verwenden

ALTER TABLE customer_freebies 
ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) DEFAULT NULL COMMENT 'YouTube/Vimeo Video URL' AFTER mockup_image_url,
ADD COLUMN IF NOT EXISTS video_format ENUM('16:9', '9:16') DEFAULT '16:9' COMMENT 'Video-Seitenverhältnis' AFTER video_url;
