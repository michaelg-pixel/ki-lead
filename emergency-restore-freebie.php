<?php
/**
 * EMERGENCY RESTORE: Stelle freebie/index.php wieder her
 * Die Datei wurde versehentlich beschädigt und muss wiederhergestellt werden
 */

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🚨 EMERGENCY RESTORE - freebie/index.php\n";
echo str_repeat("=", 80) . "\n\n";

$backupUrl = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/15646ec0d77df275a656873004917d512a748397/freebie/index.php';

echo "📥 Lade funktionierende Version vom Commit 15646ec...\n";

$content = @file_get_contents($backupUrl);

if ($content === false) {
    echo "❌ FEHLER: Konnte Backup nicht laden!\n";
    echo "   Versuche manuelles Wiederherstellen...\n\n";
    
    // Zeige Git-Command an
    echo "🔧 MANUELLE WIEDERHERSTELLUNG:\n";
    echo "   cd /path/to/repo\n";
    echo "   git checkout 15646ec -- freebie/index.php\n";
    echo "   git add freebie/index.php\n";
    echo "   git commit -m \"Restore: freebie/index.php nach Beschädigung\"\n\n";
    exit(1);
}

echo "✓ Backup geladen (" . number_format(strlen($content)) . " bytes)\n\n";

// Füge YouTube Shorts Support hinzu
echo "🔧 Füge YouTube Shorts Support hinzu...\n";

$oldPattern = "if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";
$newPattern = "if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";

$content = str_replace($oldPattern, $newPattern, $content);

echo "✓ YouTube Shorts Support hinzugefügt\n\n";

// Speichere Datei
$targetFile = __DIR__ . '/freebie/index.php';
file_put_contents($targetFile, $content);

echo "✅ Datei wiederhergestellt: $targetFile\n";
echo "   Größe: " . number_format(strlen($content)) . " bytes\n\n";

echo str_repeat("=", 80) . "\n";
echo "✅ WIEDERHERSTELLUNG ERFOLGREICH\n";
echo "</pre>";
