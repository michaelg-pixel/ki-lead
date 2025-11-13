-- Vendor Template Enhancement Migration
-- Fügt fehlende Spalten zur vendor_reward_templates Tabelle hinzu
-- Datum: 2024

-- 1. marketplace_price Spalte hinzufügen
ALTER TABLE vendor_reward_templates 
ADD COLUMN marketplace_price DECIMAL(10,2) DEFAULT 0.00 
AFTER suggested_referrals_required;

-- 2. product_mockup_url Spalte hinzufügen
ALTER TABLE vendor_reward_templates 
ADD COLUMN product_mockup_url VARCHAR(500) NULL 
AFTER preview_image;

-- 3. course_duration Spalte hinzufügen
ALTER TABLE vendor_reward_templates 
ADD COLUMN course_duration VARCHAR(100) NULL 
AFTER reward_instructions;

-- 4. original_product_link Spalte hinzufügen
ALTER TABLE vendor_reward_templates 
ADD COLUMN original_product_link VARCHAR(500) NULL 
AFTER course_duration;

-- Fertig! Alle 4 neuen Spalten wurden hinzugefügt.