#!/bin/bash
# Cleanup-Script: Entfernt alle Setup-Dateien nach erfolgreicher Installation

echo "🧹 Cleanup: Entferne Setup-Dateien..."

# Liste der zu löschenden Dateien
FILES=(
    "setup/setup-checklist-system.php"
    "setup/check-db-structure.php"
)

DELETED=0
FAILED=0

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        rm "$file"
        if [ $? -eq 0 ]; then
            echo "✅ Gelöscht: $file"
            ((DELETED++))
        else
            echo "❌ Fehler beim Löschen: $file"
            ((FAILED++))
        fi
    else
        echo "⏭️  Nicht gefunden: $file"
    fi
done

echo ""
echo "📊 Zusammenfassung:"
echo "   ✅ Gelöscht: $DELETED"
echo "   ❌ Fehler: $FAILED"

if [ $FAILED -eq 0 ]; then
    echo ""
    echo "🎉 Cleanup erfolgreich abgeschlossen!"
else
    echo ""
    echo "⚠️  Einige Dateien konnten nicht gelöscht werden."
fi
