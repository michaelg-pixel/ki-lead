#!/bin/bash
# ULTIMATE FIX: Video Tabs Scrollbar entfernen
# Dieses Script fixt das Problem direkt auf dem Server

FILE="/home/mehr-infos-jetzt-app/htdocs/app.mehr-infos-jetzt.de/customer/course-view.php"

echo "🔧 Video-Tabs Scrollbar Fix"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ ! -f "$FILE" ]; then
    echo "❌ Datei nicht gefunden: $FILE"
    exit 1
fi

# Backup erstellen
cp "$FILE" "${FILE}.backup_$(date +%Y%m%d_%H%M%S)"
echo "✓ Backup erstellt"

# FIX: Alle Scrollbar-Properties entfernen
sed -i 's/overflow-x: auto;.*$/overflow: hidden;/g' "$FILE"
sed -i '/overflow-y: hidden;/d' "$FILE"
sed -i 's/flex-wrap: nowrap;.*$/flex-wrap: wrap;/g' "$FILE"
sed -i '/-webkit-overflow-scrolling:/d' "$FILE"
sed -i '/scrollbar-width:/d' "$FILE"
sed -i '/min-height: 80px;/d' "$FILE"

# Entferne ::-webkit-scrollbar Block
sed -i '/\/\* Scrollbar komplett verstecken \*\//,/display: none;.*Chrome/d' "$FILE"

echo "✓ CSS gefixt"
echo ""
echo "🎉 FERTIG!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "Jetzt: Strg+Shift+R drücken oder privates Fenster öffnen!"
echo ""