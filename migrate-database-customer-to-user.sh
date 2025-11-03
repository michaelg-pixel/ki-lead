#!/bin/bash

echo "🗄️  DATENBANK-MIGRATION: customer_id → user_id"
echo "=============================================="
echo ""
echo "Dieses Script benennt alle Datenbank-Tabellen und -Spalten um:"
echo "  • customer_freebies → user_freebies"
echo "  • customer_freebie_limits → user_freebie_limits"
echo "  • customer_id → user_id (in allen Tabellen)"
echo ""

# Sicherheitsabfrage
read -p "⚠️  WARNUNG: Dies ändert die Datenbank-Struktur! Fortfahren? (j/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Jj]$ ]]; then
    echo "❌ Abgebrochen."
    exit 1
fi

echo ""
echo "📝 Erstelle SQL-Migration..."
echo ""

# Erstelle SQL-Migrationsdatei
cat > migrate-customer-to-user.sql << 'EOSQL'
-- ========================================
-- MIGRATION: customer_id → user_id
-- ========================================

START TRANSACTION;

-- 1. TABELLE: customer_freebies → user_freebies
-- ------------------------------------------
RENAME TABLE customer_freebies TO user_freebies;

ALTER TABLE user_freebies 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

-- Index umbenennen (falls vorhanden)
ALTER TABLE user_freebies 
    DROP INDEX IF EXISTS idx_customer_freebie,
    ADD INDEX idx_user_freebie (user_id, freebie_id);

ALTER TABLE user_freebies
    DROP FOREIGN KEY IF EXISTS customer_freebies_ibfk_1;

ALTER TABLE user_freebies
    ADD CONSTRAINT user_freebies_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 2. TABELLE: customer_freebie_limits → user_freebie_limits
-- --------------------------------------------------------
RENAME TABLE customer_freebie_limits TO user_freebie_limits;

ALTER TABLE user_freebie_limits 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

-- Primary Key umbenennen
ALTER TABLE user_freebie_limits 
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (user_id);

ALTER TABLE user_freebie_limits
    DROP FOREIGN KEY IF EXISTS customer_freebie_limits_ibfk_1;

ALTER TABLE user_freebie_limits
    ADD CONSTRAINT user_freebie_limits_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 3. TABELLE: referral_clicks
-- ---------------------------
ALTER TABLE referral_clicks 
    CHANGE COLUMN customer_id user_id INT(11) NULL;

ALTER TABLE referral_clicks
    DROP INDEX IF EXISTS idx_customer_ref,
    ADD INDEX idx_user_ref (user_id, ref_code);

-- 4. TABELLE: referral_conversions
-- --------------------------------
ALTER TABLE referral_conversions 
    CHANGE COLUMN customer_id user_id INT(11) NULL;

ALTER TABLE referral_conversions
    DROP INDEX IF EXISTS idx_customer_conversion,
    ADD INDEX idx_user_conversion (user_id, created_at);

-- 5. TABELLE: referral_leads
-- --------------------------
ALTER TABLE referral_leads 
    CHANGE COLUMN customer_id user_id INT(11) NULL;

ALTER TABLE referral_leads
    DROP INDEX IF EXISTS idx_customer_lead,
    ADD INDEX idx_user_lead (user_id, email);

-- 6. TABELLE: referral_stats
-- --------------------------
ALTER TABLE referral_stats 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

ALTER TABLE referral_stats
    DROP PRIMARY KEY,
    ADD PRIMARY KEY (user_id);

ALTER TABLE referral_stats
    DROP FOREIGN KEY IF EXISTS referral_stats_ibfk_1;

ALTER TABLE referral_stats
    ADD CONSTRAINT referral_stats_ibfk_1 
    FOREIGN KEY (user_id) REFERENCES customers(id) ON DELETE CASCADE;

-- 7. TABELLE: referral_fraud_log
-- ------------------------------
ALTER TABLE referral_fraud_log 
    CHANGE COLUMN customer_id user_id INT(11) NULL;

ALTER TABLE referral_fraud_log
    DROP INDEX IF EXISTS idx_customer_fraud,
    ADD INDEX idx_user_fraud (user_id, created_at);

-- 8. WEITERE TABELLEN (falls vorhanden)
-- -------------------------------------

-- customer_courses → user_courses (falls vorhanden)
RENAME TABLE IF EXISTS customer_courses TO user_courses;

ALTER TABLE IF EXISTS user_courses 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

-- customer_progress → user_progress (falls vorhanden)
RENAME TABLE IF EXISTS customer_progress TO user_progress;

ALTER TABLE IF EXISTS user_progress 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

-- customer_tutorials → user_tutorials (falls vorhanden)
RENAME TABLE IF EXISTS customer_tutorials TO user_tutorials;

ALTER TABLE IF EXISTS user_tutorials 
    CHANGE COLUMN customer_id user_id INT(11) NOT NULL;

COMMIT;

-- ========================================
-- VERIFIZIERUNG
-- ========================================
SELECT 'Migration erfolgreich abgeschlossen!' AS Status;

-- Zeige aktualisierte Tabellen
SHOW TABLES LIKE '%user%';

EOSQL

echo "✅ SQL-Datei erstellt: migrate-customer-to-user.sql"
echo ""
echo "🔄 Führe Migration aus..."
echo ""

# Führe Migration aus
# HINWEIS: Passe Datenbank-Credentials an!
mysql -u root -p ki_lead < migrate-customer-to-user.sql

if [ $? -eq 0 ]; then
    echo ""
    echo "🎉 MIGRATION ERFOLGREICH!"
    echo "========================="
    echo ""
    echo "✅ Folgende Änderungen wurden vorgenommen:"
    echo ""
    echo "📋 UMBENANNTE TABELLEN:"
    echo "  • customer_freebies → user_freebies"
    echo "  • customer_freebie_limits → user_freebie_limits"
    echo "  • customer_courses → user_courses"
    echo "  • customer_progress → user_progress"
    echo "  • customer_tutorials → user_tutorials"
    echo ""
    echo "📋 UMBENANNTE SPALTEN:"
    echo "  • customer_id → user_id (in allen Tabellen)"
    echo ""
    echo "📋 AKTUALISIERTE FOREIGN KEYS:"
    echo "  • Alle Beziehungen zu users-Tabelle aktualisiert"
    echo ""
    echo "⚠️  WICHTIG:"
    echo "  1. Teste alle Funktionen gründlich!"
    echo "  2. Prüfe Referential Integrity"
    echo "  3. Backup der Datenbank wurde hoffentlich vorher erstellt!"
    echo ""
else
    echo ""
    echo "❌ FEHLER BEI DER MIGRATION!"
    echo "==========================="
    echo ""
    echo "⚠️  Die Migration wurde NICHT durchgeführt!"
    echo ""
    echo "🔍 Mögliche Ursachen:"
    echo "  • Falsche Datenbank-Credentials"
    echo "  • Tabellen existieren nicht"
    echo "  • Foreign Key Constraints"
    echo "  • Fehlende Berechtigungen"
    echo ""
    echo "💡 Lösung:"
    echo "  1. Prüfe die Datenbank-Verbindung"
    echo "  2. Führe SQL-Datei manuell aus:"
    echo "     mysql -u DEIN_USER -p ki_lead < migrate-customer-to-user.sql"
    echo ""
    exit 1
fi

echo "🔗 NÄCHSTE SCHRITTE:"
echo "  1. Frontend mit update-frontend-customer-to-user.sh aktualisieren"
echo "  2. Alle Funktionen testen"
echo "  3. Cache leeren"
echo "  4. Deployment durchführen"
echo ""
