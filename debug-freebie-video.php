<?php
/**
 * Debug: Freebie-Daten und Video-Support prüfen
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (PDOException $e) {
    die('❌ Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}

$identifier = $_GET['id'] ?? '04828493b017248c0db10bb82d48754e';

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🔍 FREEBIE DEBUG - ID: " . htmlspecialchars($identifier) . "\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Prüfe ob video_url und video_format Spalten existieren
echo "📋 SCHRITT 1: Prüfe Datenbank-Struktur\n";
echo str_repeat("-", 80) . "\n";

try {
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasVideoUrl = false;
    $hasVideoFormat = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'video_url') {
            $hasVideoUrl = true;
            echo "✅ customer_freebies.video_url existiert (Type: {$col['Type']})\n";
        }
        if ($col['Field'] === 'video_format') {
            $hasVideoFormat = true;
            echo "✅ customer_freebies.video_format existiert (Type: {$col['Type']})\n";
        }
    }
    
    if (!$hasVideoUrl) echo "❌ customer_freebies.video_url fehlt!\n";
    if (!$hasVideoFormat) echo "❌ customer_freebies.video_format fehlt!\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

echo "\n";

// 2. Lade Freebie-Daten
echo "📋 SCHRITT 2: Lade Freebie-Daten\n";
echo str_repeat("-", 80) . "\n";

try {
    $stmt = $pdo->prepare("
        SELECT 
            cf.*,
            u.id as customer_id,
            u.email as customer_email
        FROM customer_freebies cf 
        LEFT JOIN users u ON cf.customer_id = u.id 
        WHERE cf.unique_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        echo "✅ Freebie gefunden!\n\n";
        
        echo "🎬 VIDEO-DATEN:\n";
        echo "   video_url:    " . ($freebie['video_url'] ?? 'NULL') . "\n";
        echo "   video_format: " . ($freebie['video_format'] ?? 'NULL') . "\n\n";
        
        echo "🖼️ MOCKUP-DATEN:\n";
        echo "   mockup_image_url: " . ($freebie['mockup_image_url'] ?? 'NULL') . "\n\n";
        
        echo "📝 GRUNDDATEN:\n";
        echo "   ID:           " . $freebie['id'] . "\n";
        echo "   Customer ID:  " . $freebie['customer_id'] . "\n";
        echo "   Headline:     " . $freebie['headline'] . "\n";
        echo "   Layout:       " . $freebie['layout'] . "\n";
        echo "   Created:      " . $freebie['created_at'] . "\n\n";
        
        // Prüfe Video-URL Konvertierung
        if (!empty($freebie['video_url'])) {
            echo "🔄 VIDEO-URL KONVERTIERUNG:\n";
            $videoUrl = $freebie['video_url'];
            echo "   Original:     $videoUrl\n";
            
            // YouTube
            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
                $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
                echo "   Embed-URL:    $embedUrl\n";
                echo "   ✅ YouTube-Video erkannt\n";
            }
            // Vimeo
            elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $matches)) {
                $embedUrl = 'https://player.vimeo.com/video/' . $matches[1];
                echo "   Embed-URL:    $embedUrl\n";
                echo "   ✅ Vimeo-Video erkannt\n";
            }
            else {
                echo "   ❌ Video-URL konnte nicht konvertiert werden\n";
            }
        } else {
            echo "⚠️ Keine Video-URL gesetzt\n";
        }
        
    } else {
        echo "❌ Freebie nicht gefunden!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Fehler beim Laden: " . $e->getMessage() . "\n";
}

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "🏁 DEBUG ABGESCHLOSSEN\n";
echo "</pre>";
