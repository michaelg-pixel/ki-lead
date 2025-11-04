-- Add font fields to customer_freebies table
-- This allows customers to inherit font settings from their template

ALTER TABLE customer_freebies
ADD COLUMN IF NOT EXISTS preheadline_font VARCHAR(100) DEFAULT NULL AFTER mockup_image_url,
ADD COLUMN IF NOT EXISTS preheadline_size INT DEFAULT NULL AFTER preheadline_font,
ADD COLUMN IF NOT EXISTS headline_font VARCHAR(100) DEFAULT NULL AFTER preheadline_size,
ADD COLUMN IF NOT EXISTS headline_size INT DEFAULT NULL AFTER headline_font,
ADD COLUMN IF NOT EXISTS subheadline_font VARCHAR(100) DEFAULT NULL AFTER headline_size,
ADD COLUMN IF NOT EXISTS subheadline_size INT DEFAULT NULL AFTER subheadline_font,
ADD COLUMN IF NOT EXISTS bulletpoints_font VARCHAR(100) DEFAULT NULL AFTER subheadline_size,
ADD COLUMN IF NOT EXISTS bulletpoints_size INT DEFAULT NULL AFTER bulletpoints_font;

-- Copy font settings from template to existing customer_freebies
UPDATE customer_freebies cf
INNER JOIN freebies f ON cf.template_id = f.id
SET 
    cf.preheadline_font = f.preheadline_font,
    cf.preheadline_size = f.preheadline_size,
    cf.headline_font = f.headline_font,
    cf.headline_size = f.headline_size,
    cf.subheadline_font = f.subheadline_font,
    cf.subheadline_size = f.subheadline_size,
    cf.bulletpoints_font = f.bulletpoints_font,
    cf.bulletpoints_size = f.bulletpoints_size
WHERE cf.template_id IS NOT NULL
  AND (cf.preheadline_font IS NULL OR cf.headline_font IS NULL);
