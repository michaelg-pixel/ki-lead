#!/bin/bash

echo "🔄 AUTOMATISCHE FRONTEND-ANPASSUNG"
echo "===================================="
echo ""
echo "Dieses Script ersetzt automatisch alle customer_id Vorkommen"
echo "durch user_id in den Frontend-Dateien."
echo ""

# Sicherheitsabfrage
read -p "⚠️  WARNUNG: Dies ändert mehrere Dateien! Fortfahren? (j/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Jj]$ ]]; then
    echo "❌ Abgebrochen."
    exit 1
fi

echo ""
echo "🔍 SCHRITT 1: Dateien scannen..."
echo "--------------------------------"

# Zähler
total_files=0
total_changes=0

# Array für gefundene Dateien
declare -a found_files

# Suche in allen PHP-Dateien
for file in $(find . -name "*.php" -type f 2>/dev/null); do
    # Prüfe ob customer_id vorkommt
    if grep -q "customer_id\|customerId\|customer_freebies\|customer_freebie_limits" "$file" 2>/dev/null; then
        found_files+=("$file")
        echo "📄 Gefunden: $file"
        total_files=$((total_files + 1))
    fi
done

# Suche in JavaScript-Dateien
for file in $(find . -name "*.js" -type f 2>/dev/null); do
    if grep -q "customer_id\|customerId\|customerDetails" "$file" 2>/dev/null; then
        found_files+=("$file")
        echo "📄 Gefunden: $file"
        total_files=$((total_files + 1))
    fi
done

echo ""
echo "✅ $total_files Dateien gefunden"
echo ""

if [ $total_files -eq 0 ]; then
    echo "🎉 Keine Dateien zum Anpassen gefunden!"
    exit 0
fi

echo "🔄 SCHRITT 2: Ersetzungen durchführen..."
echo "----------------------------------------"
echo ""

for file in "${found_files[@]}"; do
    echo "📝 Bearbeite: $file"
    
    # Backup erstellen
    cp "$file" "$file.backup"
    
    # Zähle Ersetzungen
    changes=0
    
    # Ersetzungen durchführen
    # PHP Session Variable
    if sed -i "s/\$_SESSION\['customer_id'\]/\$_SESSION['user_id']/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # PHP Variable
    if sed -i "s/\\\$customer_id/\$userId/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # SQL Spalten
    if sed -i "s/customer_id/user_id/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # JavaScript Variable
    if sed -i "s/customerId/userId/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # API Endpoint
    if sed -i "s/get-customer-details/get-user-details/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # Tabellennamen
    if sed -i "s/customer_freebies/user_freebies/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    if sed -i "s/customer_freebie_limits/user_freebie_limits/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # URL Parameter
    if sed -i "s/customer=/user=/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    # Kommentare aktualisieren
    if sed -i "s/Customer ID/User ID/g" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    if sed -i "s/customer id/user id/gi" "$file" 2>/dev/null; then
        changes=$((changes + 1))
    fi
    
    echo "   ✅ $changes Ersetzungen"
    total_changes=$((total_changes + changes))
done

echo ""
echo "🎉 FERTIG!"
echo "=========="
echo ""
echo "📊 Statistik:"
echo "  • Bearbeitete Dateien: $total_files"
echo "  • Durchgeführte Änderungen: $total_changes"
echo ""
echo "💾 Backups erstellt:"
echo "  Alle Original-Dateien wurden als .backup gespeichert"
echo ""
echo "⚠️  WICHTIG:"
echo "  1. Teste die Änderungen gründlich!"
echo "  2. Bei Problemen: Backups wiederherstellen"
echo "  3. Danach Backups löschen: find . -name '*.backup' -delete"
echo ""
echo "🔗 Nächste Schritte:"
echo "  1. Datenbank-Tabellen umbenennen"
echo "  2. Datenbank-Spalten umbenennen"
echo "  3. Tests durchführen"
echo ""
