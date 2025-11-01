#!/bin/bash

# Kundengesteuerte Freebie-Erstellung - Setup Skript
# Dieses Skript richtet das komplette System automatisch ein

echo "🎁 Kundengesteuerte Freebie-Erstellung - Setup"
echo "=============================================="
echo ""

# Farben für Output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Funktion für Erfolgsmeldungen
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Funktion für Warnungen
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Funktion für Fehler
error() {
    echo -e "${RED}❌ $1${NC}"
    exit 1
}

# Schritt 1: Datenbank-Konfiguration prüfen
echo "Schritt 1: Datenbank-Konfiguration prüfen..."
if [ ! -f "../config/database.php" ]; then
    error "Datenbank-Konfiguration nicht gefunden!"
fi
success "Datenbank-Konfiguration gefunden"
echo ""

# Schritt 2: SQL-Setup ausführen
echo "Schritt 2: Datenbank-Tabellen erstellen..."
echo "Bitte gib deine Datenbank-Zugangsdaten ein:"
read -p "Datenbank-Host [localhost]: " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Datenbank-Name: " DB_NAME
if [ -z "$DB_NAME" ]; then
    error "Datenbank-Name ist erforderlich!"
fi

read -p "Datenbank-Benutzer: " DB_USER
if [ -z "$DB_USER" ]; then
    error "Datenbank-Benutzer ist erforderlich!"
fi

read -sp "Datenbank-Passwort: " DB_PASS
echo ""

if [ -z "$DB_PASS" ]; then
    error "Datenbank-Passwort ist erforderlich!"
fi

echo ""
echo "Führe SQL-Setup aus..."

if command -v mysql &> /dev/null; then
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < setup-customer-freebie-limits.sql
    if [ $? -eq 0 ]; then
        success "Datenbank-Tabellen erfolgreich erstellt"
    else
        error "Fehler beim Erstellen der Tabellen"
    fi
else
    warning "MySQL CLI nicht gefunden. Bitte führe 'setup-customer-freebie-limits.sql' manuell aus."
fi
echo ""

# Schritt 3: Verzeichnis-Berechtigungen prüfen
echo "Schritt 3: Verzeichnis-Berechtigungen prüfen..."

DIRS=("../customer" "../api" "../webhook" "../admin")
for dir in "${DIRS[@]}"; do
    if [ -d "$dir" ]; then
        success "Verzeichnis $dir existiert"
    else
        warning "Verzeichnis $dir nicht gefunden"
    fi
done
echo ""

# Schritt 4: PHP-Extensions prüfen
echo "Schritt 4: PHP-Extensions prüfen..."

REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "json" "session")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "$ext"; then
        success "PHP Extension '$ext' ist installiert"
    else
        warning "PHP Extension '$ext' fehlt möglicherweise"
    fi
done
echo ""

# Schritt 5: Webhook-URL generieren
echo "Schritt 5: Webhook-Konfiguration..."
read -p "Deine Domain (z.B. app.mehr-infos-jetzt.de): " DOMAIN
if [ -z "$DOMAIN" ]; then
    warning "Domain nicht angegeben"
else
    echo ""
    echo "==================================="
    echo "WEBHOOK-URL für Digistore24:"
    echo "https://${DOMAIN}/webhook/digistore24.php"
    echo "==================================="
    echo ""
    success "Webhook-URL generiert"
fi
echo ""

# Schritt 6: Standard-Produkt-Konfigurationen
echo "Schritt 6: Standard-Produkt-Konfigurationen einrichten..."
echo ""
echo "Möchtest du Standard-Produkt-Konfigurationen erstellen? (j/n)"
read -p "> " CREATE_DEFAULTS

if [ "$CREATE_DEFAULTS" = "j" ] || [ "$CREATE_DEFAULTS" = "J" ]; then
    echo "Erstelle Standard-Konfigurationen..."
    
    # SQL für Standard-Produkte
    DEFAULT_SQL="
    INSERT INTO product_freebie_config (product_id, product_name, freebie_limit, is_active) VALUES
    ('STARTER_001', 'Starter Paket', 5, 1),
    ('PROFESSIONAL_002', 'Professional Paket', 10, 1),
    ('ENTERPRISE_003', 'Enterprise Paket', 25, 1),
    ('UNLIMITED_004', 'Unlimited Paket', 999, 1)
    ON DUPLICATE KEY UPDATE 
        product_name = VALUES(product_name),
        freebie_limit = VALUES(freebie_limit);
    "
    
    if command -v mysql &> /dev/null; then
        echo "$DEFAULT_SQL" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
        if [ $? -eq 0 ]; then
            success "Standard-Konfigurationen erstellt"
        else
            warning "Fehler beim Erstellen der Standard-Konfigurationen"
        fi
    else
        warning "Bitte erstelle die Produkt-Konfigurationen manuell über das Admin-Panel"
    fi
fi
echo ""

# Schritt 7: Datei-Berechtigungen setzen
echo "Schritt 7: Datei-Berechtigungen setzen..."
if [ -f "../webhook/digistore24.php" ]; then
    chmod 644 ../webhook/digistore24.php
    success "Webhook-Berechtigungen gesetzt"
fi

if [ -d "../api" ]; then
    chmod 755 ../api
    chmod 644 ../api/*.php 2>/dev/null
    success "API-Berechtigungen gesetzt"
fi
echo ""

# Schritt 8: Test-Modus Check
echo "Schritt 8: Konfiguration überprüfen..."
echo ""
echo "Bitte überprüfe folgende Punkte manuell:"
echo ""
echo "✅ 1. Datenbank-Tabellen erstellt (customer_freebie_limits, product_freebie_config)"
echo "✅ 2. Webhook-URL in Digistore24 eingetragen"
echo "✅ 3. Admin-Panel erreichbar unter: https://${DOMAIN}/admin/freebie-limits.php"
echo "✅ 4. Customer-Dashboard erreichbar unter: https://${DOMAIN}/customer/dashboard.php?page=freebies"
echo ""

# Abschluss
echo ""
echo "=============================================="
echo "🎉 Setup abgeschlossen!"
echo "=============================================="
echo ""
echo "Nächste Schritte:"
echo ""
echo "1. Öffne das Admin-Panel:"
echo "   https://${DOMAIN}/admin/freebie-limits.php"
echo ""
echo "2. Passe die Produkt-Konfigurationen an deine Digistore24-Produkte an"
echo ""
echo "3. Trage die Webhook-URL in Digistore24 ein:"
echo "   https://${DOMAIN}/webhook/digistore24.php"
echo ""
echo "4. Teste das System mit einem Test-Kauf"
echo ""
echo "5. Lies die Dokumentation:"
echo "   CUSTOMER_FREEBIES_README.md"
echo ""
success "Alles bereit! Viel Erfolg! 🚀"
echo ""
