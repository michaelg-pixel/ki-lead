#!/bin/bash

# Font Download Script für KI Leadsystem
# Dieses Skript lädt alle benötigten Fonts herunter und speichert sie lokal

echo "🎨 Starte Font-Download für KI Leadsystem..."
echo ""

# Erstelle Fonts-Verzeichnis
mkdir -p /home/u123456789/domains/app.mehr-infos-jetzt.de/public_html/assets/fonts
cd /home/u123456789/domains/app.mehr-infos-jetzt.de/public_html/assets/fonts

# Funktion zum Herunterladen
download_font() {
    local url=$1
    local filename=$2
    echo "⬇️  Downloading $filename..."
    wget -q "$url" -O "$filename" && echo "   ✅ $filename" || echo "   ❌ Failed: $filename"
}

# Inter (Variable Font)
echo "📦 Inter Font..."
download_font "https://fonts.bunny.net/inter/files/inter-latin-300-normal.woff2" "inter-300.woff2"
download_font "https://fonts.bunny.net/inter/files/inter-latin-400-normal.woff2" "inter-400.woff2"
download_font "https://fonts.bunny.net/inter/files/inter-latin-500-normal.woff2" "inter-500.woff2"
download_font "https://fonts.bunny.net/inter/files/inter-latin-600-normal.woff2" "inter-600.woff2"
download_font "https://fonts.bunny.net/inter/files/inter-latin-700-normal.woff2" "inter-700.woff2"
download_font "https://fonts.bunny.net/inter/files/inter-latin-800-normal.woff2" "inter-800.woff2"

# Poppins
echo ""
echo "📦 Poppins Font..."
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-300-normal.woff2" "poppins-300.woff2"
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-400-normal.woff2" "poppins-400.woff2"
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-500-normal.woff2" "poppins-500.woff2"
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-600-normal.woff2" "poppins-600.woff2"
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-700-normal.woff2" "poppins-700.woff2"
download_font "https://fonts.bunny.net/poppins/files/poppins-latin-800-normal.woff2" "poppins-800.woff2"

# Roboto
echo ""
echo "📦 Roboto Font..."
download_font "https://fonts.bunny.net/roboto/files/roboto-latin-300-normal.woff2" "roboto-300.woff2"
download_font "https://fonts.bunny.net/roboto/files/roboto-latin-400-normal.woff2" "roboto-400.woff2"
download_font "https://fonts.bunny.net/roboto/files/roboto-latin-500-normal.woff2" "roboto-500.woff2"
download_font "https://fonts.bunny.net/roboto/files/roboto-latin-700-normal.woff2" "roboto-700.woff2"
download_font "https://fonts.bunny.net/roboto/files/roboto-latin-900-normal.woff2" "roboto-900.woff2"

# Montserrat
echo ""
echo "📦 Montserrat Font..."
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-300-normal.woff2" "montserrat-300.woff2"
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-400-normal.woff2" "montserrat-400.woff2"
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-500-normal.woff2" "montserrat-500.woff2"
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-600-normal.woff2" "montserrat-600.woff2"
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-700-normal.woff2" "montserrat-700.woff2"
download_font "https://fonts.bunny.net/montserrat/files/montserrat-latin-800-normal.woff2" "montserrat-800.woff2"

# Playfair Display
echo ""
echo "📦 Playfair Display Font..."
download_font "https://fonts.bunny.net/playfair-display/files/playfair-display-latin-400-normal.woff2" "playfair-400.woff2"
download_font "https://fonts.bunny.net/playfair-display/files/playfair-display-latin-500-normal.woff2" "playfair-500.woff2"
download_font "https://fonts.bunny.net/playfair-display/files/playfair-display-latin-600-normal.woff2" "playfair-600.woff2"
download_font "https://fonts.bunny.net/playfair-display/files/playfair-display-latin-700-normal.woff2" "playfair-700.woff2"
download_font "https://fonts.bunny.net/playfair-display/files/playfair-display-latin-800-normal.woff2" "playfair-800.woff2"

# Open Sans
echo ""
echo "📦 Open Sans Font..."
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-300-normal.woff2" "opensans-300.woff2"
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-400-normal.woff2" "opensans-400.woff2"
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-500-normal.woff2" "opensans-500.woff2"
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-600-normal.woff2" "opensans-600.woff2"
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-700-normal.woff2" "opensans-700.woff2"
download_font "https://fonts.bunny.net/open-sans/files/open-sans-latin-800-normal.woff2" "opensans-800.woff2"

# Lato
echo ""
echo "📦 Lato Font..."
download_font "https://fonts.bunny.net/lato/files/lato-latin-300-normal.woff2" "lato-300.woff2"
download_font "https://fonts.bunny.net/lato/files/lato-latin-400-normal.woff2" "lato-400.woff2"
download_font "https://fonts.bunny.net/lato/files/lato-latin-700-normal.woff2" "lato-700.woff2"
download_font "https://fonts.bunny.net/lato/files/lato-latin-900-normal.woff2" "lato-900.woff2"

echo ""
echo "✅ Font-Download abgeschlossen!"
echo ""
echo "📊 Heruntergeladene Dateien:"
ls -lh *.woff2 | wc -l | xargs echo "   Anzahl:"
du -sh . | cut -f1 | xargs echo "   Größe gesamt:"
echo ""
echo "🎉 Alle Fonts sind jetzt auf deinem Server verfügbar!"
echo "💡 Vergiss nicht, die fonts.css zu aktualisieren, um lokale Pfade zu verwenden."
