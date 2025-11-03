-- Migration: Rename customer_id to user_id in legal_texts table
-- Date: 2025-11-03
-- Purpose: Align with system-wide migration from customer_id to user_id

-- Check if the table exists
SELECT 'Checking legal_texts table...' AS status;

-- Rename the column from customer_id to user_id
ALTER TABLE `legal_texts` 
CHANGE COLUMN `customer_id` `user_id` INT(11) NOT NULL;

-- Update the unique constraint
ALTER TABLE `legal_texts` 
DROP INDEX `unique_customer`,
ADD UNIQUE KEY `unique_user` (`user_id`);

-- Update the index
ALTER TABLE `legal_texts`
DROP INDEX `idx_customer_id`,
ADD KEY `idx_user_id` (`user_id`);

-- Success message
SELECT '✅ legal_texts table successfully migrated from customer_id to user_id!' AS status;
