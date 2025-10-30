#!/bin/bash

echo "🚀 Starte manuelles Deployment..."
echo ""

# Farben für Output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Zum Projekt-Verzeichnis wechseln
cd /home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de

echo "📂 Aktuelles Verzeichnis: $(pwd)"
echo ""

# Backup erstellen
echo "${YELLOW}📦 Erstelle Backup...${NC}"
BACKUP_DIR="/home/mehr-infos-jetzt-app/backups"
mkdir -p $BACKUP_DIR
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
tar -czf "$BACKUP_DIR/backup_$TIMESTAMP.tar.gz" . 2>/dev/null
echo "${GREEN}✅ Backup erstellt: backup_$TIMESTAMP.tar.gz${NC}"
echo ""

# Git Status prüfen
echo "${YELLOW}🔍 Prüfe Git Status...${NC}"
git status
echo ""

# Neuesten Code holen
echo "${YELLOW}⬇️ Hole neuesten Code von GitHub...${NC}"
git fetch origin main
echo ""

# Lokale Änderungen verwerfen und auf neuesten Stand bringen
echo "${YELLOW}🔄 Aktualisiere auf neuesten Stand...${NC}"
git reset --hard origin/main
echo ""

# Verzeichnisse erstellen falls nicht vorhanden
echo "${YELLOW}📁 Erstelle fehlende Verzeichnisse...${NC}"
mkdir -p customer/sections
mkdir -p admin/sections
mkdir -p uploads
mkdir -p logs
echo "${GREEN}✅ Verzeichnisse erstellt${NC}"
echo ""

# Berechtigungen setzen
echo "${YELLOW}🔐 Setze Dateiberechtigungen...${NC}"
chmod -R 755 customer/
chmod -R 755 admin/
chmod -R 755 config/
chmod -R 755 includes/
chmod -R 777 uploads/
chmod -R 777 logs/
echo "${GREEN}✅ Berechtigungen gesetzt${NC}"
echo ""

# Prüfe ob wichtige Dateien vorhanden sind
echo "${YELLOW}📋 Prüfe wichtige Dateien...${NC}"
echo ""

if [ -f "customer/dashboard.php" ]; then
    echo "${GREEN}✅ customer/dashboard.php${NC}"
else
    echo "${RED}❌ customer/dashboard.php FEHLT!${NC}"
fi

if [ -f "customer/sections/einstellungen.php" ]; then
    echo "${GREEN}✅ customer/sections/einstellungen.php${NC}"
else
    echo "${RED}❌ customer/sections/einstellungen.php FEHLT!${NC}"
fi

if [ -f "customer/sections/kurse.php" ]; then
    echo "${GREEN}✅ customer/sections/kurse.php${NC}"
else
    echo "${RED}❌ customer/sections/kurse.php FEHLT!${NC}"
fi

if [ -f "customer/sections/fortschritt.php" ]; then
    echo "${GREEN}✅ customer/sections/fortschritt.php${NC}"
else
    echo "${RED}❌ customer/sections/fortschritt.php FEHLT!${NC}"
fi

if [ -f "config/settings.php" ]; then
    echo "${GREEN}✅ config/settings.php${NC}"
else
    echo "${RED}❌ config/settings.php FEHLT!${NC}"
fi

echo ""

# Zeige alle Dateien im sections Ordner
echo "${YELLOW}📂 Dateien in customer/sections/:${NC}"
ls -lah customer/sections/
echo ""

# Zeige letzten Commit
echo "${YELLOW}📝 Letzter Commit:${NC}"
git log -1 --oneline
echo ""

echo "${GREEN}✅ Deployment abgeschlossen!${NC}"
echo ""
echo "🌐 Teste jetzt: https://app.mehr-infos-jetzt.de/customer/dashboard.php"
echo ""
