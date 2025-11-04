#!/bin/bash

# Quick Fix: Freebie-Belohnungen Migration
# Führt die notwendige Datenbank-Migration aus

echo "======================================"
echo "  Freebie-Belohnungen Migration"
echo "======================================"
echo ""

# Prüfe ob MySQL verfügbar ist
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL nicht gefunden!"
    echo "Bitte nutze die Browser-basierte Migration:"
    echo "https://app.mehr-infos-jetzt.de/database/run-migrations.php"
    exit 1
fi

echo "✅ MySQL gefunden"
echo ""

# Datenbank-Konfiguration
read -p "Datenbank-Name: " DB_NAME
read -p "Datenbank-User: " DB_USER
read -sp "Datenbank-Passwort: " DB_PASS
echo ""
echo ""

# Migration-Datei
MIGRATION_FILE="database/migrations/2025-11-04_add_freebie_id_to_reward_definitions.sql"

# Prüfe ob Datei existiert
if [ ! -f "$MIGRATION_FILE" ]; then
    echo "❌ Migration-Datei nicht gefunden: $MIGRATION_FILE"
    echo "Bitte stelle sicher, dass du im Root-Verzeichnis des Projekts bist."
    exit 1
fi

echo "✅ Migration-Datei gefunden"
echo ""

# Backup erstellen
echo "📦 Erstelle Backup..."
BACKUP_FILE="backup/reward_definitions_$(date +%Y%m%d_%H%M%S).sql"
mkdir -p backup

mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT * FROM reward_definitions INTO OUTFILE '/tmp/reward_definitions_backup.sql';" 2>/dev/null || true

echo "✅ Backup erstellt (falls Tabelle existiert)"
echo ""

# Migration ausführen
echo "🚀 Führe Migration aus..."
if mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$MIGRATION_FILE"; then
    echo ""
    echo "✅ Migration erfolgreich abgeschlossen!"
    echo ""
    
    # Verifizierung
    echo "🔍 Verifiziere Änderungen..."
    COLUMN_EXISTS=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'reward_definitions' 
        AND COLUMN_NAME = 'freebie_id'
        AND TABLE_SCHEMA = '$DB_NAME'
    ")
    
    if [ "$COLUMN_EXISTS" -eq "1" ]; then
        echo "✅ Spalte 'freebie_id' erfolgreich hinzugefügt"
        
        # Prüfe Foreign Key
        FK_EXISTS=$(mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'reward_definitions' 
            AND CONSTRAINT_NAME = 'fk_reward_def_freebie'
            AND TABLE_SCHEMA = '$DB_NAME'
        ")
        
        if [ "$FK_EXISTS" -eq "1" ]; then
            echo "✅ Foreign Key erfolgreich erstellt"
        else
            echo "⚠️  Foreign Key konnte nicht erstellt werden (eventuell bereits vorhanden)"
        fi
        
        # Statistiken
        echo ""
        echo "📊 Statistiken:"
        mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -se "
            SELECT 
                COUNT(*) as total_rewards,
                COUNT(freebie_id) as rewards_with_freebie,
                COUNT(*) - COUNT(freebie_id) as rewards_without_freebie
            FROM reward_definitions
        " | column -t
        
        echo ""
        echo "======================================"
        echo "  ✅ Migration abgeschlossen!"
        echo "======================================"
        echo ""
        echo "Nächste Schritte:"
        echo "1. Öffne: https://app.mehr-infos-jetzt.de/customer/dashboard.php?page=empfehlungsprogramm"
        echo "2. Wähle ein Freebie"
        echo "3. Klicke auf 'Belohnungen einrichten'"
        echo "4. Erstelle eine Belohnungsstufe"
        echo ""
        echo "Dokumentation: FREEBIE_REWARDS_COMPLETE.md"
        echo ""
        
    else
        echo "❌ Spalte 'freebie_id' wurde NICHT hinzugefügt"
        echo "Bitte prüfe die Logs und versuche es manuell."
    fi
    
else
    echo ""
    echo "❌ Migration fehlgeschlagen!"
    echo ""
    echo "Bitte nutze die manuelle Migration:"
    echo "1. Öffne phpMyAdmin"
    echo "2. Wähle Datenbank: $DB_NAME"
    echo "3. Gehe zu 'SQL'"
    echo "4. Kopiere Inhalt von: $MIGRATION_FILE"
    echo "5. Führe aus"
    echo ""
    exit 1
fi
