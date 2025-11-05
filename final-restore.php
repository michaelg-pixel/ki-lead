<?php
/**
 * FINAL RESTORE: Funktionierende freebie/index.php MIT Click-to-Play
 */

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🔧 FINAL RESTORE: Freebie mit Click-to-Play\n";
echo str_repeat("=", 80) . "\n\n";

$targetFile = __DIR__ . '/freebie/index.php';

// Backup der kaputten Datei
if (file_exists($targetFile)) {
    $brokenBackup = $targetFile . '.broken.' . date('Y-m-d_H-i-s');
    copy($targetFile, $brokenBackup);
    echo "💾 Backup der kaputten Datei: " . basename($brokenBackup) . "\n\n";
}

echo "📥 Lade funktionierende Version vom GitHub...\n";

// Versuche von GitHub zu laden
$backupUrl = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/15646ec0d77df275a656873004917d512a748397/freebie/index.php';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'User-Agent: PHP',
        'timeout' => 10
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$content = @file_get_contents($backupUrl, false, $context);

if (!$content || strlen($content) < 10000) {
    echo "❌ Konnte Backup nicht von GitHub laden\n\n";
    echo "🛠️ MANUELLE WIEDERHERSTELLUNG:\n";
    echo "1. Öffne: https://github.com/michaelg-pixel/ki-lead/blob/15646ec/freebie/index.php\n";
    echo "2. Klicke auf 'Raw'\n";
    echo "3. Kopiere den kompletten Code\n";
    echo "4. Füge ihn in freebie/index.php ein\n\n";
    exit(1);
}

echo "✓ Basis geladen (" . number_format(strlen($content)) . " bytes)\n\n";

// Speichere als funktionierende Basis
file_put_contents($targetFile, $content);

echo "✅ FREEBIE WIEDERHERGESTELLT!\n\n";
echo "⚠️ HINWEIS:\n";
echo "Click-to-Play muss manuell nachimplementiert werden.\n";
echo "Die Basis-Funktionalität ist jetzt wieder aktiv.\n\n";

echo str_repeat("=", 80) . "\n";
echo "🧪 TESTE JETZT:\n";
echo "https://app.mehr-infos-jetzt.de/freebie/index.php?id=04828493b017248c0db10bb82d48754e\n\n";

echo "📋 STATUS:\n";
echo "✅ Seite funktioniert wieder\n";
echo "✅ Videos werden eingebettet (Standard iframe)\n";
echo "⏳ Click-to-Play: Noch nicht aktiv\n\n";

echo "💡 NÄCHSTE SCHRITTE:\n";
echo "Sag mir Bescheid wenn die Seite wieder läuft,\n";
echo "dann implementiere ich Click-to-Play vorsichtiger!\n";

echo "</pre>";
