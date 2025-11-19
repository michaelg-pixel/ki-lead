#!/bin/bash

# Reward Auto-Delivery Cronjob Setup
# Installiert automatischen Cronjob für Belohnungsauslieferung

echo "================================================"
echo "Reward Auto-Delivery Cronjob Setup"
echo "================================================"
echo ""

# Pfad ermitteln
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
CRON_SCRIPT="$PROJECT_ROOT/api/rewards/auto-deliver-cron.php"
LOG_DIR="$PROJECT_ROOT/logs"
LOG_FILE="$LOG_DIR/reward-delivery.log"

echo "📁 Project Root: $PROJECT_ROOT"
echo "📄 Cronjob Script: $CRON_SCRIPT"
echo ""

# Prüfe ob Script existiert
if [ ! -f "$CRON_SCRIPT" ]; then
    echo "❌ FEHLER: Cronjob-Script nicht gefunden!"
    echo "   Erwarteter Pfad: $CRON_SCRIPT"
    exit 1
fi

# Log-Verzeichnis erstellen
if [ ! -d "$LOG_DIR" ]; then
    echo "📁 Erstelle Log-Verzeichnis..."
    mkdir -p "$LOG_DIR"
    chmod 755 "$LOG_DIR"
fi

# PHP-Pfad ermitteln
PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    PHP_PATH="/usr/bin/php"
    echo "⚠️  PHP-Pfad nicht gefunden, verwende Standard: $PHP_PATH"
else
    echo "✅ PHP gefunden: $PHP_PATH"
fi

# Cronjob-Zeile erstellen
# Läuft alle 10 Minuten
CRON_JOB="*/10 * * * * $PHP_PATH $CRON_SCRIPT >> $LOG_FILE 2>&1"

echo ""
echo "================================================"
echo "Cronjob Konfiguration:"
echo "================================================"
echo "Intervall: Alle 10 Minuten"
echo "Command:   $PHP_PATH $CRON_SCRIPT"
echo "Log:       $LOG_FILE"
echo ""

# Prüfe ob Cronjob bereits existiert
EXISTING_CRON=$(crontab -l 2>/dev/null | grep -F "$CRON_SCRIPT")

if [ -n "$EXISTING_CRON" ]; then
    echo "⚠️  Cronjob existiert bereits:"
    echo "   $EXISTING_CRON"
    echo ""
    read -p "Möchtest du ihn aktualisieren? (y/n): " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "❌ Abgebrochen"
        exit 0
    fi
    
    # Alten Cronjob entfernen
    (crontab -l 2>/dev/null | grep -v -F "$CRON_SCRIPT") | crontab -
    echo "🗑️  Alter Cronjob entfernt"
fi

# Cronjob hinzufügen
echo "➕ Füge neuen Cronjob hinzu..."
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ ================================================"
    echo "✅ Cronjob erfolgreich installiert!"
    echo "✅ ================================================"
    echo ""
    echo "📊 Aktive Cronjobs:"
    crontab -l | grep -F "$CRON_SCRIPT"
    echo ""
    echo "📋 Nächste Schritte:"
    echo "   1. Warte 10 Minuten oder führe manuell aus:"
    echo "      $PHP_PATH $CRON_SCRIPT"
    echo ""
    echo "   2. Überprüfe Logs:"
    echo "      tail -f $LOG_FILE"
    echo ""
    echo "   3. Teste manuell mit:"
    echo "      php $PROJECT_ROOT/api/rewards/test-auto-delivery.php"
    echo ""
else
    echo ""
    echo "❌ ================================================"
    echo "❌ Fehler beim Installieren des Cronjobs!"
    echo "❌ ================================================"
    echo ""
    echo "Manuelle Installation:"
    echo "1. Öffne Crontab: crontab -e"
    echo "2. Füge folgende Zeile hinzu:"
    echo "   $CRON_JOB"
    echo ""
    exit 1
fi
