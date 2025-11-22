-- Migration: Erweitere acceptance_type ENUM um 'mailgun_consent'
-- Datum: 2025-11-22
-- Grund: Separates Tracking für Mailgun-Zustimmung im Empfehlungsprogramm

ALTER TABLE `av_contract_acceptances` 
MODIFY COLUMN `acceptance_type` 
ENUM('registration','update','renewal','mailgun_consent') 
NOT NULL DEFAULT 'registration'
COMMENT 'Typ der Zustimmung: registration, update, renewal, mailgun_consent';
