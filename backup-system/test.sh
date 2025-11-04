#!/bin/bash

###############################################################################
# Backup System - Test Script
# Manuelles Testen aller Backup-Funktionen
###############################################################################

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN=$(which php)

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              🧪 Backup System - Test Suite                    ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Test 1: PHP-Verfügbarkeit
echo "[Test 1] PHP-Verfügbarkeit prüfen..."
if [ -z "$PHP_BIN" ]; then
    echo "❌ PHP nicht gefunden!"
    exit 1
fi
echo "✅ PHP gefunden: $PHP_BIN"
echo ""

# Test 2: Verzeichnisse prüfen
echo "[Test 2] Verzeichnisse prüfen..."
for dir in "$SCRIPT_DIR/backups/database" "$SCRIPT_DIR/backups/files" "$SCRIPT_DIR/backups/logs"; do
    if [ ! -d "$dir" ]; then
        echo "❌ Verzeichnis fehlt: $dir"
        exit 1
    fi
    
    if [ ! -w "$dir" ]; then
        echo "❌ Keine Schreibrechte: $dir"
        exit 1
    fi
done
echo "✅ Alle Verzeichnisse OK"
echo ""

# Test 3: Config-Datei prüfen
echo "[Test 3] Konfiguration prüfen..."
if [ ! -f "$SCRIPT_DIR/config.php" ]; then
    echo "❌ config.php nicht gefunden!"
    exit 1
fi

# Prüfen ob Standard-Passwort noch gesetzt ist
if grep -q "DeinSicheresPasswort123!" "$SCRIPT_DIR/config.php"; then
    echo "⚠️  WARNUNG: Standard-Passwort ist noch gesetzt!"
    echo "   Bitte ändere das Passwort in config.php"
fi
echo "✅ Konfiguration vorhanden"
echo ""

# Test 4: Datenbank-Verbindung prüfen
echo "[Test 4] Datenbank-Verbindung testen..."
$PHP_BIN -r "
require_once '$SCRIPT_DIR/config.php';
\$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (\$mysqli->connect_error) {
    echo '❌ Datenbankverbindung fehlgeschlagen: ' . \$mysqli->connect_error . PHP_EOL;
    exit(1);
}
echo '✅ Datenbankverbindung OK' . PHP_EOL;
\$mysqli->close();
"
echo ""

# Test 5: Datenbank-Backup erstellen
echo "[Test 5] Test-Datenbank-Backup erstellen..."
echo "   (Dies kann einige Sekunden dauern...)"
$PHP_BIN "$SCRIPT_DIR/engine.php" database

if [ $? -eq 0 ]; then
    echo "✅ Datenbank-Backup erfolgreich"
    
    # Neuestes Backup anzeigen
    LATEST_DB=$(ls -t "$SCRIPT_DIR/backups/database" | head -1)
    if [ ! -z "$LATEST_DB" ]; then
        SIZE=$(du -h "$SCRIPT_DIR/backups/database/$LATEST_DB" | cut -f1)
        echo "   📦 Erstellt: $LATEST_DB ($SIZE)"
    fi
else
    echo "❌ Datenbank-Backup fehlgeschlagen!"
    echo "   Prüfe die Logs: $SCRIPT_DIR/backups/logs/"
    exit 1
fi
echo ""

# Test 6: Datei-Backup erstellen (optional, da zeitintensiv)
echo "[Test 6] Test-Datei-Backup erstellen (optional)..."
read -p "   Datei-Backup erstellen? Dies kann mehrere Minuten dauern. (j/n): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[JjYy]$ ]]; then
    echo "   (Dies kann mehrere Minuten dauern...)"
    $PHP_BIN "$SCRIPT_DIR/engine.php" files
    
    if [ $? -eq 0 ]; then
        echo "✅ Datei-Backup erfolgreich"
        
        LATEST_FILES=$(ls -t "$SCRIPT_DIR/backups/files" | head -1)
        if [ ! -z "$LATEST_FILES" ]; then
            SIZE=$(du -h "$SCRIPT_DIR/backups/files/$LATEST_FILES" | cut -f1)
            echo "   📦 Erstellt: $LATEST_FILES ($SIZE)"
        fi
    else
        echo "❌ Datei-Backup fehlgeschlagen!"
        exit 1
    fi
else
    echo "⏭️  Datei-Backup übersprungen"
fi
echo ""

# Test 7: Logs prüfen
echo "[Test 7] Logs überprüfen..."
LOG_FILE="$SCRIPT_DIR/backups/logs/backup_$(date +%Y-%m-%d).log"

if [ -f "$LOG_FILE" ]; then
    echo "✅ Log-Datei vorhanden: $LOG_FILE"
    echo ""
    echo "Letzte Zeilen aus dem Log:"
    echo "─────────────────────────────────────────"
    tail -n 10 "$LOG_FILE"
    echo "─────────────────────────────────────────"
else
    echo "⚠️  Keine Log-Datei für heute gefunden"
fi
echo ""

# Test 8: Admin-Interface prüfen
echo "[Test 8] Admin-Interface prüfen..."
if [ -f "$SCRIPT_DIR/admin.php" ]; then
    echo "✅ Admin-Interface vorhanden"
    echo "   URL: https://deine-domain.de/backup-system/admin.php"
else
    echo "❌ admin.php nicht gefunden!"
    exit 1
fi
echo ""

# Test 9: Cronjobs prüfen
echo "[Test 9] Cronjobs überprüfen..."
CRON_COUNT=$(crontab -l 2>/dev/null | grep -c "backup-system/engine.php")

if [ $CRON_COUNT -gt 0 ]; then
    echo "✅ $CRON_COUNT Backup-Cronjob(s) gefunden"
    echo ""
    echo "Aktive Cronjobs:"
    echo "─────────────────────────────────────────"
    crontab -l | grep "backup-system/engine.php"
    echo "─────────────────────────────────────────"
else
    echo "⚠️  Keine Backup-Cronjobs gefunden"
    echo "   Installiere sie mit: ./install-cronjobs.sh"
fi
echo ""

# Zusammenfassung
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ Test abgeschlossen!                      ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Statistiken anzeigen
echo "📊 Backup-Statistiken:"
DB_COUNT=$(ls -1 "$SCRIPT_DIR/backups/database" 2>/dev/null | wc -l)
FILES_COUNT=$(ls -1 "$SCRIPT_DIR/backups/files" 2>/dev/null | wc -l)
DB_SIZE=$(du -sh "$SCRIPT_DIR/backups/database" 2>/dev/null | cut -f1)
FILES_SIZE=$(du -sh "$SCRIPT_DIR/backups/files" 2>/dev/null | cut -f1)

echo "   • Datenbank-Backups: $DB_COUNT ($DB_SIZE)"
echo "   • Datei-Backups: $FILES_COUNT ($FILES_SIZE)"
echo ""

echo "🎉 Alle Tests erfolgreich!"
echo ""
echo "Nächste Schritte:"
echo "   1. Admin-Interface aufrufen"
echo "   2. Cronjobs installieren (falls noch nicht geschehen)"
echo "   3. Externe Speicherorte konfigurieren (optional)"
echo ""
