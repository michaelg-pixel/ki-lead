#!/bin/bash

###############################################################################
# Backup System - Quick Install
# Schnelle Installation und Konfiguration
###############################################################################

clear
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                  🔐 Backup System Installation                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$SCRIPT_DIR/config.php"

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Schritt 1: Berechtigungen prüfen
echo -e "${BLUE}[1/5]${NC} Prüfe Berechtigungen..."

if [ ! -w "$SCRIPT_DIR" ]; then
    echo -e "${RED}❌ Keine Schreibrechte für Backup-Verzeichnis!${NC}"
    echo "Bitte führe aus: chmod 755 $SCRIPT_DIR"
    exit 1
fi

echo -e "${GREEN}✅ Berechtigungen OK${NC}"
echo ""

# Schritt 2: Verzeichnisse erstellen
echo -e "${BLUE}[2/5]${NC} Erstelle Backup-Verzeichnisse..."

mkdir -p "$SCRIPT_DIR/backups/database"
mkdir -p "$SCRIPT_DIR/backups/files"
mkdir -p "$SCRIPT_DIR/backups/logs"

chmod 755 "$SCRIPT_DIR/backups"
chmod 777 "$SCRIPT_DIR/backups/database"
chmod 777 "$SCRIPT_DIR/backups/files"
chmod 777 "$SCRIPT_DIR/backups/logs"

echo -e "${GREEN}✅ Verzeichnisse erstellt${NC}"
echo ""

# Schritt 3: Zugangsdaten konfigurieren
echo -e "${BLUE}[3/5]${NC} Konfiguration..."
echo ""

read -p "Admin-Benutzername [admin]: " ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}

while true; do
    read -s -p "Admin-Passwort (mind. 8 Zeichen): " ADMIN_PASS
    echo ""
    
    if [ ${#ADMIN_PASS} -lt 8 ]; then
        echo -e "${RED}❌ Passwort zu kurz! Mindestens 8 Zeichen.${NC}"
        continue
    fi
    
    read -s -p "Passwort bestätigen: " ADMIN_PASS_CONFIRM
    echo ""
    
    if [ "$ADMIN_PASS" != "$ADMIN_PASS_CONFIRM" ]; then
        echo -e "${RED}❌ Passwörter stimmen nicht überein!${NC}"
        continue
    fi
    
    break
done

# Passwort-Hash generieren
PASS_HASH=$(php -r "echo password_hash('$ADMIN_PASS', PASSWORD_DEFAULT);")

# Config-Datei aktualisieren
sed -i "s/define('BACKUP_ADMIN_USER', 'admin');/define('BACKUP_ADMIN_USER', '$ADMIN_USER');/" "$CONFIG_FILE"
sed -i "s/define('BACKUP_ADMIN_PASS', password_hash('DeinSicheresPasswort123!', PASSWORD_DEFAULT));/define('BACKUP_ADMIN_PASS', '$PASS_HASH');/" "$CONFIG_FILE"

echo -e "${GREEN}✅ Zugangsdaten konfiguriert${NC}"
echo ""

# Schritt 4: E-Mail-Benachrichtigungen (optional)
echo -e "${BLUE}[4/5]${NC} E-Mail-Benachrichtigungen (optional)..."
echo ""

read -p "E-Mail für Fehler-Benachrichtigungen [leer lassen für keine]: " NOTIFY_EMAIL

if [ ! -z "$NOTIFY_EMAIL" ]; then
    sed -i "s/define('BACKUP_NOTIFY_EMAIL', '');/define('BACKUP_NOTIFY_EMAIL', '$NOTIFY_EMAIL');/" "$CONFIG_FILE"
    echo -e "${GREEN}✅ E-Mail-Benachrichtigungen aktiviert: $NOTIFY_EMAIL${NC}"
else
    echo -e "${YELLOW}ℹ️  Keine E-Mail-Benachrichtigungen${NC}"
fi
echo ""

# Schritt 5: Cronjobs installieren
echo -e "${BLUE}[5/5]${NC} Cronjobs installieren..."
echo ""

read -p "Möchtest du die Cronjobs jetzt installieren? (j/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[JjYy]$ ]]; then
    bash "$SCRIPT_DIR/install-cronjobs.sh"
else
    echo -e "${YELLOW}ℹ️  Du kannst die Cronjobs später mit './install-cronjobs.sh' installieren${NC}"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                 ✅ Installation abgeschlossen!                 ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}🎉 Backup-System erfolgreich installiert!${NC}"
echo ""
echo "📋 Nächste Schritte:"
echo ""
echo "1. Admin-Interface aufrufen:"
echo -e "   ${BLUE}https://deine-domain.de/backup-system/admin.php${NC}"
echo ""
echo "2. Login-Daten:"
echo -e "   Benutzer: ${GREEN}$ADMIN_USER${NC}"
echo -e "   Passwort: ${GREEN}***${NC} (das eben eingegebene)"
echo ""
echo "3. Test-Backup erstellen:"
echo -e "   ${YELLOW}php $SCRIPT_DIR/engine.php database${NC}"
echo ""
echo "4. Cronjobs überprüfen (falls installiert):"
echo -e "   ${YELLOW}crontab -l${NC}"
echo ""
echo "📖 Dokumentation:"
echo "   $SCRIPT_DIR/README.md"
echo ""
echo -e "${GREEN}Viel Erfolg! 🚀${NC}"
echo ""
