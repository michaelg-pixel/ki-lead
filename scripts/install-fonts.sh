#!/bin/bash

# Quick-Start: Fonts auf Server installieren
# Führe dieses Skript auf deinem Hostinger-Server aus

echo "🎨 KI Leadsystem - Font Installation"
echo "===================================="
echo ""
echo "Dieses Skript führt dich durch die Font-Installation."
echo ""

# Pfad ermitteln
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "📍 Projekt-Verzeichnis: $PROJECT_ROOT"
echo ""

# Prüfe ob fonts-Verzeichnis existiert
if [ ! -d "$PROJECT_ROOT/assets/fonts" ]; then
    echo "❌ Fehler: assets/fonts Verzeichnis nicht gefunden!"
    echo "   Stelle sicher, dass du im richtigen Verzeichnis bist."
    exit 1
fi

# Prüfe ob Download-Skript existiert
if [ ! -f "$PROJECT_ROOT/scripts/download-fonts.sh" ]; then
    echo "❌ Fehler: download-fonts.sh nicht gefunden!"
    exit 1
fi

echo "✅ Alle Voraussetzungen erfüllt!"
echo ""
echo "Starte Font-Download..."
echo ""

# Download-Skript ausführbar machen
chmod +x "$PROJECT_ROOT/scripts/download-fonts.sh"

# Download-Skript ausführen
"$PROJECT_ROOT/scripts/download-fonts.sh"

# Prüfe Ergebnis
FONT_COUNT=$(ls -1 "$PROJECT_ROOT/assets/fonts/"*.woff2 2>/dev/null | wc -l)

echo ""
echo "================================"
echo "📊 Installation abgeschlossen!"
echo "================================"
echo ""
echo "Gefundene Font-Dateien: $FONT_COUNT"
echo ""

if [ "$FONT_COUNT" -ge 40 ]; then
    echo "✅ ERFOLG! Alle Fonts wurden erfolgreich installiert."
    echo ""
    echo "🎉 Nächste Schritte:"
    echo "   1. Öffne: https://app.mehr-infos-jetzt.de/admin/freebie-edit.php"
    echo "   2. Teste die Schriftarten in der Live-Vorschau"
    echo "   3. Überprüfe in Browser DevTools → Network ob Fonts geladen werden"
    echo ""
    echo "💡 Tipp: Leere den Browser-Cache (Strg+Shift+R)"
else
    echo "⚠️  WARNUNG: Nur $FONT_COUNT Font-Dateien gefunden (erwartet: ~44)"
    echo ""
    echo "Mögliche Lösungen:"
    echo "   1. Führe das Skript erneut aus"
    echo "   2. Siehe manuelle Installation: docs/FONT-INSTALLATION.md"
    echo "   3. Prüfe Netzwerkverbindung auf dem Server"
fi

echo ""
echo "📚 Weitere Hilfe: docs/FONT-INSTALLATION.md"
