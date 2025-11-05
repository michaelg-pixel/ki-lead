<?php
/**
 * Debug: Zeige aktuell verwendete Video-URL
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}

$identifier = '04828493b017248c0db10bb82d48754e';

$stmt = $pdo->prepare("SELECT id, video_url, video_format, headline FROM customer_freebies WHERE unique_id = ?");
$stmt->execute([$identifier]);
$freebie = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🎬 VIDEO DEBUG FÜR FREEBIE\n";
echo str_repeat("=", 80) . "\n\n";

if ($freebie) {
    echo "📝 FREEBIE INFO:\n";
    echo "   ID: {$freebie['id']}\n";
    echo "   Headline: {$freebie['headline']}\n\n";
    
    echo "🎥 VIDEO-DATEN:\n";
    echo "   video_url: " . ($freebie['video_url'] ?: 'LEER') . "\n";
    echo "   video_format: " . ($freebie['video_format'] ?: 'widescreen') . "\n\n";
    
    if (!empty($freebie['video_url'])) {
        $videoUrl = $freebie['video_url'];
        
        // Konvertiere zu Embed URL
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
            $videoId = $matches[1];
            $embedUrl = "https://www.youtube.com/embed/$videoId";
            
            echo "✅ YouTube Video erkannt!\n";
            echo "   Video ID: $videoId\n";
            echo "   Embed URL: $embedUrl\n\n";
            
            echo "🧪 TESTE VIDEO:\n\n";
            
            // Test: Öffne Video direkt
            echo "1. Öffne Video direkt auf YouTube:\n";
            echo "   https://www.youtube.com/watch?v=$videoId\n";
            echo "   → Funktioniert das Video?\n\n";
            
            echo "2. Öffne Embed-URL direkt:\n";
            echo "   $embedUrl\n";
            echo "   → Wird das Video angezeigt oder Fehler?\n\n";
            
            // Live Test
            echo "📺 LIVE EMBED TEST:\n\n";
            echo "</pre>";
            
            echo "<div style='max-width:560px;margin:20px auto;background:white;padding:20px;'>";
            echo "<h3 style='color:black;'>Test: Widescreen Embed</h3>";
            echo "<div style='position:relative;padding-bottom:56.25%;background:#000;'>";
            echo "<iframe src='$embedUrl' style='position:absolute;top:0;left:0;width:100%;height:100%;' frameborder='0' allowfullscreen></iframe>";
            echo "</div>";
            echo "</div>";
            
            echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
            
            echo "\n💡 TROUBLESHOOTING:\n\n";
            echo "Wenn Fehler 153 angezeigt wird:\n";
            echo "• Das Video ist nicht für Embedding freigegeben\n";
            echo "• Gehe zu YouTube Studio → Video → Erweiterte Einstellungen\n";
            echo "• Aktiviere: 'Einbetten zulassen'\n\n";
            
        } else {
            echo "❌ Video-URL konnte nicht geparst werden!\n";
            echo "   Erwartetes Format: youtube.com/watch?v=XXX oder youtu.be/XXX\n";
        }
    } else {
        echo "⚠️ KEINE VIDEO-URL GESETZT!\n";
    }
    
} else {
    echo "❌ Freebie nicht gefunden!\n";
}

echo "</pre>";
