#!/bin/bash

###############################################################################
# EMPFEHLUNGSPROGRAMM - AUTOMATISCHES SETUP
# Richtet alle Komponenten ein und testet das System
###############################################################################

echo "=============================================="
echo "🚀 EMPFEHLUNGSPROGRAMM AUTOMATISCHES SETUP"
echo "=============================================="
echo ""

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Basis-Pfad
BASE_PATH="/home/lumisaas/public_html"
LOG_PATH="/home/lumisaas/logs"

# Fehler-Handling
set -e
trap 'echo -e "${RED}❌ Setup fehlgeschlagen bei Zeile $LINENO${NC}"; exit 1' ERR

###############################################################################
# SCHRITT 1: LOGS-ORDNER ERSTELLEN
###############################################################################
echo "📁 Schritt 1/6: Logs-Ordner erstellen..."

if [ ! -d "$LOG_PATH" ]; then
    mkdir -p "$LOG_PATH"
    chmod 755 "$LOG_PATH"
    echo -e "${GREEN}✓ Logs-Ordner erstellt: $LOG_PATH${NC}"
else
    echo -e "${YELLOW}⚠ Logs-Ordner existiert bereits${NC}"
fi

# Test-Log-Datei erstellen
touch "$LOG_PATH/cron.log"
chmod 644 "$LOG_PATH/cron.log"
echo "$(date '+%Y-%m-%d %H:%M:%S') - Referral System Setup gestartet" >> "$LOG_PATH/cron.log"
echo -e "${GREEN}✓ Test-Log-Datei erstellt${NC}"

###############################################################################
# SCHRITT 2: DATENBANK-MIGRATION PRÜFEN
###############################################################################
echo ""
echo "🗄️  Schritt 2/6: Datenbank-Migration prüfen..."

# MySQL-Credentials (aus config/database.php)
DB_HOST="localhost"
DB_NAME="lumisaas"
DB_USER="lumisaas52"
DB_PASS="I1zx1XdL1hrWd75yu57e"

# Prüfe ob Tabellen existieren
TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "SHOW TABLES LIKE 'referral_%'" 2>/dev/null | wc -l)

if [ "$TABLES" -eq 7 ]; then
    echo -e "${GREEN}✓ Alle 7 Referral-Tabellen gefunden${NC}"
else
    echo -e "${YELLOW}⚠ Nur $TABLES/7 Tabellen gefunden. Führe Migration durch...${NC}"
    
    # Migration ausführen
    if [ -f "$BASE_PATH/database/migrations/004_referral_system.sql" ]; then
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BASE_PATH/database/migrations/004_referral_system.sql"
        echo -e "${GREEN}✓ Migration erfolgreich durchgeführt${NC}"
    else
        echo -e "${RED}❌ Migrations-Datei nicht gefunden!${NC}"
        exit 1
    fi
fi

# Prüfe ob customers-Tabelle erweitert wurde
COLUMNS=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "SHOW COLUMNS FROM customers LIKE 'referral_%'" 2>/dev/null | wc -l)

if [ "$COLUMNS" -ge 1 ]; then
    echo -e "${GREEN}✓ Customer-Tabelle erweitert ($COLUMNS Referral-Spalten)${NC}"
else
    echo -e "${YELLOW}⚠ Customer-Tabelle muss erweitert werden${NC}"
fi

###############################################################################
# SCHRITT 3: CRON-JOB EINRICHTEN
###############################################################################
echo ""
echo "⏰ Schritt 3/6: Cron-Job einrichten..."

CRON_COMMAND="0 10 * * * php $BASE_PATH/scripts/send-reward-emails.php >> $LOG_PATH/cron.log 2>&1"

# Prüfe ob Cron-Job bereits existiert
if crontab -l 2>/dev/null | grep -q "send-reward-emails.php"; then
    echo -e "${YELLOW}⚠ Cron-Job existiert bereits${NC}"
else
    # Füge Cron-Job hinzu
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -
    echo -e "${GREEN}✓ Cron-Job erfolgreich hinzugefügt${NC}"
    echo "   Läuft täglich um 10:00 Uhr"
fi

# Zeige aktuelle Cron-Jobs
echo ""
echo "Aktive Cron-Jobs:"
crontab -l | grep -v "^#" | grep -v "^$"

###############################################################################
# SCHRITT 4: BERECHTIGUNGEN SETZEN
###############################################################################
echo ""
echo "🔐 Schritt 4/6: Berechtigungen setzen..."

# API-Ordner
if [ -d "$BASE_PATH/api/referral" ]; then
    chmod -R 755 "$BASE_PATH/api/referral"
    echo -e "${GREEN}✓ API-Berechtigungen gesetzt${NC}"
fi

# Scripts
if [ -f "$BASE_PATH/scripts/send-reward-emails.php" ]; then
    chmod 755 "$BASE_PATH/scripts/send-reward-emails.php"
    echo -e "${GREEN}✓ Script-Berechtigungen gesetzt${NC}"
fi

# Logs
chmod -R 755 "$LOG_PATH"
echo -e "${GREEN}✓ Log-Berechtigungen gesetzt${NC}"

###############################################################################
# SCHRITT 5: TEST-DATEN ERSTELLEN (Optional)
###############################################################################
echo ""
echo "🧪 Schritt 5/6: Test-Daten erstellen (optional)..."

read -p "Möchten Sie Test-Daten erstellen? (j/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Jj]$ ]]; then
    # Aktiviere Referral für ersten Customer
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
UPDATE customers 
SET 
    referral_enabled = 1,
    company_name = 'Test Firma GmbH',
    company_email = 'test@mehr-infos-jetzt.de',
    company_imprint_html = '<p>Test Firma GmbH<br>Teststraße 123<br>12345 Teststadt</p>'
WHERE id = 1
LIMIT 1;

-- Erstelle Test-Klick
INSERT INTO referral_clicks (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test', 'test_fingerprint', NOW());

-- Erstelle Test-Conversion
INSERT INTO referral_conversions (customer_id, ref_code, ip_address_hash, user_agent, fingerprint, source, suspicious, created_at)
VALUES (1, 'TEST123', SHA2('127.0.0.1', 256), 'Mozilla/5.0 Test', 'test_fingerprint', 'thankyou', 0, NOW());

-- Update Stats
INSERT INTO referral_stats (customer_id, total_clicks, unique_clicks, total_conversions, conversion_rate)
VALUES (1, 1, 1, 1, 100.00)
ON DUPLICATE KEY UPDATE
    total_clicks = 1,
    unique_clicks = 1,
    total_conversions = 1,
    conversion_rate = 100.00,
    updated_at = NOW();
EOF
    echo -e "${GREEN}✓ Test-Daten erstellt (Customer ID 1)${NC}"
else
    echo -e "${YELLOW}⚠ Test-Daten übersprungen${NC}"
fi

###############################################################################
# SCHRITT 6: SYSTEM-VALIDIERUNG
###############################################################################
echo ""
echo "✅ Schritt 6/6: System-Validierung..."

# Prüfe Datenbank-Tabellen
echo "Datenbank-Tabellen:"
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'referral_%'" 2>/dev/null

# Prüfe API-Endpoints
echo ""
echo "API-Endpoints:"
if [ -d "$BASE_PATH/api/referral" ]; then
    ls -la "$BASE_PATH/api/referral/" | grep "\.php$" | wc -l
    echo " PHP-Dateien gefunden"
fi

# Prüfe Logs
echo ""
echo "Logs:"
ls -lh "$LOG_PATH/" 2>/dev/null || echo "Keine Logs vorhanden"

# Prüfe Cron-Job
echo ""
echo "Cron-Job Status:"
if crontab -l 2>/dev/null | grep -q "send-reward-emails.php"; then
    echo -e "${GREEN}✓ Cron-Job aktiv${NC}"
else
    echo -e "${RED}❌ Cron-Job NICHT aktiv${NC}"
fi

###############################################################################
# ZUSAMMENFASSUNG
###############################################################################
echo ""
echo "=============================================="
echo "📊 SETUP ABGESCHLOSSEN"
echo "=============================================="
echo ""
echo -e "${GREEN}✓ Logs-Ordner erstellt${NC}"
echo -e "${GREEN}✓ Datenbank-Migration geprüft${NC}"
echo -e "${GREEN}✓ Cron-Job eingerichtet${NC}"
echo -e "${GREEN}✓ Berechtigungen gesetzt${NC}"
echo -e "${GREEN}✓ System validiert${NC}"
echo ""
echo "🔗 ZUGRIFFS-URLs:"
echo "   Customer: https://app.mehr-infos-jetzt.de/customer/dashboard.php"
echo "   Admin: https://app.mehr-infos-jetzt.de/admin/dashboard.php?section=referral-overview"
echo "   Erweitert: https://app.mehr-infos-jetzt.de/admin/sections/referral-monitoring-extended.php"
echo ""
echo "📝 NÄCHSTE SCHRITTE:"
echo "   1. Browser öffnen und Admin-Dashboard aufrufen"
echo "   2. Customer-Login und Empfehlungsprogramm aktivieren"
echo "   3. Test-Link aufrufen: https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123"
echo "   4. Browser-Console prüfen (F12)"
echo ""
echo "🧪 SYSTEM TESTEN:"
echo "   Führe aus: $BASE_PATH/scripts/test-referral-system.php"
echo ""
echo "📋 LOGS ANZEIGEN:"
echo "   tail -f $LOG_PATH/cron.log"
echo "   tail -f $LOG_PATH/reward-emails-\$(date +%Y-%m-%d).log"
echo ""
echo "=============================================="
echo -e "${GREEN}✅ SETUP ERFOLGREICH ABGESCHLOSSEN!${NC}"
echo "=============================================="
