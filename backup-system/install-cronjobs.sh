#!/bin/bash

###############################################################################
# Backup System - Cronjob Installation
# Automatisches Setup der Backup-Cronjobs
###############################################################################

echo "🚀 Installiere Backup-System Cronjobs..."
echo ""

# Pfade
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN=$(which php)
ENGINE_SCRIPT="$SCRIPT_DIR/engine.php"

# Check ob PHP verfügbar ist
if [ -z "$PHP_BIN" ]; then
    echo "❌ PHP wurde nicht gefunden!"
    echo "Bitte installiere PHP oder gib den Pfad manuell an."
    exit 1
fi

echo "✅ PHP gefunden: $PHP_BIN"
echo "✅ Engine-Script: $ENGINE_SCRIPT"
echo ""

# Cronjob-Einträge
echo "📋 Folgende Cronjobs werden konfiguriert:"
echo ""
echo "1. Datenbank-Backup: Täglich um 02:00 Uhr"
echo "   0 2 * * * $PHP_BIN $ENGINE_SCRIPT database"
echo ""
echo "2. Datei-Backup: Wöchentlich Sonntags um 03:00 Uhr"
echo "   0 3 * * 0 $PHP_BIN $ENGINE_SCRIPT files"
echo ""
echo "3. Vollständiges Backup: Monatlich am 1. um 04:00 Uhr"
echo "   0 4 1 * * $PHP_BIN $ENGINE_SCRIPT full"
echo ""

# Benutzerbestätigung
read -p "Möchtest du diese Cronjobs installieren? (j/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
    echo "❌ Installation abgebrochen."
    exit 1
fi

# Temporäre Crontab-Datei
TEMP_CRON=$(mktemp)

# Aktuelle Crontab laden (falls vorhanden)
crontab -l > "$TEMP_CRON" 2>/dev/null || true

# Prüfen ob Backup-Cronjobs bereits existieren
if grep -q "backup-system/engine.php" "$TEMP_CRON"; then
    echo "⚠️  Backup-Cronjobs scheinen bereits zu existieren."
    read -p "Möchtest du sie überschreiben? (j/n): " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[JjYy]$ ]]; then
        # Alte Einträge entfernen
        sed -i '/backup-system\/engine.php/d' "$TEMP_CRON"
    else
        echo "❌ Installation abgebrochen."
        rm "$TEMP_CRON"
        exit 1
    fi
fi

# Neue Cronjob-Einträge hinzufügen
echo "" >> "$TEMP_CRON"
echo "# KI-Lead Backup System" >> "$TEMP_CRON"
echo "# Automatische Backups - Nicht manuell bearbeiten!" >> "$TEMP_CRON"
echo "0 2 * * * $PHP_BIN $ENGINE_SCRIPT database >> $SCRIPT_DIR/backups/logs/cron.log 2>&1" >> "$TEMP_CRON"
echo "0 3 * * 0 $PHP_BIN $ENGINE_SCRIPT files >> $SCRIPT_DIR/backups/logs/cron.log 2>&1" >> "$TEMP_CRON"
echo "0 4 1 * * $PHP_BIN $ENGINE_SCRIPT full >> $SCRIPT_DIR/backups/logs/cron.log 2>&1" >> "$TEMP_CRON"

# Crontab installieren
crontab "$TEMP_CRON"

# Aufräumen
rm "$TEMP_CRON"

echo ""
echo "✅ Cronjobs erfolgreich installiert!"
echo ""
echo "📅 Zeitplan:"
echo "   • Datenbank-Backup: Täglich um 02:00 Uhr"
echo "   • Datei-Backup: Wöchentlich Sonntags um 03:00 Uhr"
echo "   • Vollständiges Backup: Monatlich am 1. um 04:00 Uhr"
echo ""
echo "🔍 Du kannst deine Cronjobs mit 'crontab -l' anzeigen lassen."
echo ""
echo "🧪 Testlauf (optional):"
echo "   php $ENGINE_SCRIPT database"
echo ""
echo "🌐 Admin-Interface:"
echo "   https://deine-domain.de/backup-system/admin.php"
echo ""
