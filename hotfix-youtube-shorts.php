<?php
/**
 * HOTFIX: YouTube Shorts Support in freebie/index.php hinzufügen
 */

$file = __DIR__ . '/freebie/index.php';

echo "🔧 HOTFIX: YouTube Shorts Support\n";
echo str_repeat("=", 60) . "\n\n";

if (!file_exists($file)) {
    die("❌ Datei nicht gefunden: $file\n");
}

$content = file_get_contents($file);

// Alte RegEx (ohne Shorts)
$oldPattern = "if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";

// Neue RegEx (mit Shorts)
$newPattern = "if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";

if (strpos($content, $oldPattern) !== false) {
    echo "✓ Alte Pattern gefunden\n";
    $content = str_replace($oldPattern, $newPattern, $content);
    file_put_contents($file, $content);
    echo "✅ YouTube Shorts Support hinzugefügt!\n\n";
    echo "📝 Geändert:\n";
    echo "   VORHER: youtube.com/watch?v= | youtu.be/\n";
    echo "   NACHHER: youtube.com/watch?v= | youtube.com/shorts/ | youtu.be/\n\n";
} else if (strpos($content, $newPattern) !== false) {
    echo "ℹ️  YouTube Shorts Support bereits vorhanden!\n\n";
} else {
    echo "❌ Pattern nicht gefunden - manuelle Prüfung erforderlich\n\n";
}

echo str_repeat("=", 60) . "\n";
echo "✅ HOTFIX ABGESCHLOSSEN\n";
