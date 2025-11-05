<?php
/**
 * COMPLETE RESTORE: Stelle vollständige freebie/index.php wieder her
 */

$targetFile = __DIR__ . '/freebie/index.php';

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🔧 COMPLETE RESTORE - freebie/index.php\n";
echo str_repeat("=", 80) . "\n\n";

// Versuche von verschiedenen Quellen zu laden
$sources = [
    'GitHub Raw' => 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/15646ec0d77df275a656873004917d512a748397/freebie/index.php',
    'GitHub API' => 'https://api.github.com/repos/michaelg-pixel/ki-lead/contents/freebie/index.php?ref=15646ec0d77df275a656873004917d512a748397'
];

$content = null;

foreach ($sources as $name => $url) {
    echo "📥 Versuche von $name zu laden...\n";
    
    $headers = ['User-Agent: PHP'];
    $context = stream_context_create(['http' => ['header' => implode("\r\n", $headers)]]);
    $data = @file_get_contents($url, false, $context);
    
    if ($data !== false && strlen($data) > 1000) {
        if ($name === 'GitHub API') {
            $json = json_decode($data, true);
            if (isset($json['content'])) {
                $content = base64_decode($json['content']);
            }
        } else {
            $content = $data;
        }
        
        if ($content && strlen($content) > 10000) {
            echo "✅ Erfolgreich geladen von $name (" . number_format(strlen($content)) . " bytes)\n\n";
            break;
        }
    }
    
    echo "❌ Fehlgeschlagen\n";
}

if (!$content) {
    echo "\n❌ KRITISCHER FEHLER: Konnte Backup nicht laden!\n\n";
    echo "🛠️ MANUELLE WIEDERHERSTELLUNG ERFORDERLICH:\n\n";
    echo "Option 1 - Via Git:\n";
    echo "  cd /path/to/repo\n";
    echo "  git checkout 15646ec -- freebie/index.php\n";
    echo "  git add freebie/index.php\n";
    echo "  git commit -m 'Restore freebie/index.php'\n";
    echo "  git push\n\n";
    echo "Option 2 - Manuell:\n";
    echo "  1. Gehe zu: https://github.com/michaelg-pixel/ki-lead/blob/15646ec/freebie/index.php\n";
    echo "  2. Kopiere den kompletten Code\n";
    echo "  3. Füge ihn in /freebie/index.php ein\n\n";
    exit(1);
}

// YouTube Shorts Support hinzufügen
echo "🔧 Füge YouTube Shorts Support hinzu...\n";

$oldPattern = "if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";
$newPattern = "if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', \$url, \$matches)) {";

if (strpos($content, $oldPattern) !== false) {
    $content = str_replace($oldPattern, $newPattern, $content);
    echo "✅ YouTube Shorts Support hinzugefügt\n\n";
} else {
    echo "⚠️ Pattern nicht gefunden - eventuell bereits vorhanden\n\n";
}

// Backup der aktuellen kaputten Datei
if (file_exists($targetFile)) {
    $backupFile = $targetFile . '.broken.' . date('Y-m-d_H-i-s');
    copy($targetFile, $backupFile);
    echo "📦 Backup der kaputten Datei erstellt: $backupFile\n\n";
}

// Schreibe wiederhergestellte Datei
file_put_contents($targetFile, $content);

echo "✅ DATEI WIEDERHERGESTELLT!\n";
echo "   Ziel: $targetFile\n";
echo "   Größe: " . number_format(strlen($content)) . " bytes\n";
echo "   Zeilen: " . substr_count($content, "\n") . "\n\n";

echo str_repeat("=", 80) . "\n";
echo "🎉 WIEDERHERSTELLUNG ERFOLGREICH!\n\n";
echo "📋 NÄCHSTE SCHRITTE:\n";
echo "1. Teste: https://app.mehr-infos-jetzt.de/freebie/index.php?id=04828493b017248c0db10bb82d48754e\n";
echo "2. Das Video sollte jetzt angezeigt werden\n";
echo "3. YouTube Shorts werden unterstützt (wenn embed-fähig)\n";

echo "</pre>";
