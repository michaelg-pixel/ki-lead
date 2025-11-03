#!/bin/bash

echo "🔍 SUCHE NACH FRONTEND-DATEIEN, DIE ANGEPASST WERDEN MÜSSEN..."
echo "================================================================"
echo ""

# Farben für bessere Lesbarkeit
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Zähler
total_files=0
total_matches=0

# Suchbegriffe
search_terms=(
    "customer_id"
    "customerId"
    "get-customer-details"
    "customerDetails"
    "customer:"
    "customer="
    "\.customer\."
    "session\['customer_id'\]"
    "SESSION\['customer_id'\]"
)

echo -e "${YELLOW}📋 Suchbegriffe:${NC}"
for term in "${search_terms[@]}"; do
    echo "  • $term"
done
echo ""
echo "================================================================"
echo ""

# Durchsuche verschiedene Verzeichnisse
directories=(
    "."
    "admin"
    "dashboard"
    "js"
    "assets"
    "includes"
)

for dir in "${directories[@]}"; do
    if [ -d "$dir" ]; then
        echo -e "${BLUE}📁 Durchsuche: $dir${NC}"
        echo "---"
        
        # Suche in PHP-Dateien
        if ls $dir/*.php 1> /dev/null 2>&1; then
            for file in $dir/*.php; do
                matches=0
                for term in "${search_terms[@]}"; do
                    if grep -q "$term" "$file" 2>/dev/null; then
                        if [ $matches -eq 0 ]; then
                            echo -e "${RED}❌ PHP: ${NC}$file"
                            total_files=$((total_files + 1))
                        fi
                        count=$(grep -c "$term" "$file" 2>/dev/null)
                        echo -e "   ${YELLOW}→${NC} '$term' gefunden (${count}x)"
                        matches=$((matches + count))
                        total_matches=$((total_matches + count))
                    fi
                done
                if [ $matches -gt 0 ]; then
                    echo ""
                fi
            done
        fi
        
        # Suche in JavaScript-Dateien
        if ls $dir/*.js 1> /dev/null 2>&1; then
            for file in $dir/*.js; do
                matches=0
                for term in "${search_terms[@]}"; do
                    if grep -q "$term" "$file" 2>/dev/null; then
                        if [ $matches -eq 0 ]; then
                            echo -e "${RED}❌ JS: ${NC}$file"
                            total_files=$((total_files + 1))
                        fi
                        count=$(grep -c "$term" "$file" 2>/dev/null)
                        echo -e "   ${YELLOW}→${NC} '$term' gefunden (${count}x)"
                        matches=$((matches + count))
                        total_matches=$((total_matches + count))
                    fi
                done
                if [ $matches -gt 0 ]; then
                    echo ""
                fi
            done
        fi
        
        # Suche in HTML-Dateien
        if ls $dir/*.html 1> /dev/null 2>&1; then
            for file in $dir/*.html; do
                matches=0
                for term in "${search_terms[@]}"; do
                    if grep -q "$term" "$file" 2>/dev/null; then
                        if [ $matches -eq 0 ]; then
                            echo -e "${RED}❌ HTML: ${NC}$file"
                            total_files=$((total_files + 1))
                        fi
                        count=$(grep -c "$term" "$file" 2>/dev/null)
                        echo -e "   ${YELLOW}→${NC} '$term' gefunden (${count}x)"
                        matches=$((matches + count))
                        total_matches=$((total_matches + count))
                    fi
                done
                if [ $matches -gt 0 ]; then
                    echo ""
                fi
            done
        fi
        
        echo ""
    fi
done

echo "================================================================"
echo -e "${GREEN}✅ SUCHE ABGESCHLOSSEN${NC}"
echo ""
echo -e "${YELLOW}📊 Statistik:${NC}"
echo "  • Betroffene Dateien: ${RED}$total_files${NC}"
echo "  • Gefundene Matches: ${RED}$total_matches${NC}"
echo ""

if [ $total_files -eq 0 ]; then
    echo -e "${GREEN}🎉 Keine Dateien gefunden - alles sauber!${NC}"
else
    echo -e "${YELLOW}⚠️  Bitte folgende Änderungen vornehmen:${NC}"
    echo ""
    echo "  1️⃣  customer_id → user_id"
    echo "  2️⃣  customerId → userId"
    echo "  3️⃣  get-customer-details.php → get-user-details.php"
    echo "  4️⃣  \$_SESSION['customer_id'] → \$_SESSION['user_id']"
    echo "  5️⃣  customer: → user:"
    echo "  6️⃣  customer= → user="
    echo ""
fi

echo "================================================================"
echo ""
echo -e "${BLUE}💡 Tipp: Nutze dieses Script, um gezielt Dateien zu finden!${NC}"
echo ""
