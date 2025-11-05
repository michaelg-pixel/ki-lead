<?php
/**
 * Debug: Video Embed URL testen
 */

$videoUrl = 'https://www.youtube.com/shorts/McAE7VWoqas';

echo "<pre style='background:#1a1a2e;color:#00ff00;padding:20px;font-family:monospace;'>";
echo "🎬 VIDEO EMBED URL DEBUG\n";
echo str_repeat("=", 80) . "\n\n";

echo "📝 Original URL:\n   $videoUrl\n\n";

// Test RegEx
if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $matches)) {
    echo "✅ RegEx Match gefunden!\n";
    echo "   Video ID: {$matches[1]}\n\n";
    
    $embedUrl = 'https://www.youtube.com/embed/' . $matches[1];
    
    echo "🔗 Embed URLs zum Testen:\n\n";
    echo "1. STANDARD:\n";
    echo "   $embedUrl\n\n";
    
    echo "2. MIT PARAMETERN:\n";
    echo "   {$embedUrl}?autoplay=0&rel=0\n\n";
    
    echo "3. ALTERNATIVE (nocookie):\n";
    echo "   https://www.youtube-nocookie.com/embed/{$matches[1]}\n\n";
    
    echo "📺 LIVE TEST:\n\n";
    
    // Test 1: Standard Embed
    echo "<div style='background:white;padding:20px;margin:20px 0;'>";
    echo "<h3 style='color:black;'>Test 1: Standard Embed</h3>";
    echo "<iframe width='315' height='560' src='$embedUrl' frameborder='0' allowfullscreen></iframe>";
    echo "</div>";
    
    // Test 2: Mit Parametern
    echo "<div style='background:white;padding:20px;margin:20px 0;'>";
    echo "<h3 style='color:black;'>Test 2: Mit Parametern</h3>";
    echo "<iframe width='315' height='560' src='{$embedUrl}?autoplay=0&rel=0&modestbranding=1' frameborder='0' allowfullscreen></iframe>";
    echo "</div>";
    
    // Test 3: YouTube nocookie
    $nocookieUrl = "https://www.youtube-nocookie.com/embed/{$matches[1]}";
    echo "<div style='background:white;padding:20px;margin:20px 0;'>";
    echo "<h3 style='color:black;'>Test 3: YouTube NoCooki</h3>";
    echo "<iframe width='315' height='560' src='$nocookieUrl' frameborder='0' allowfullscreen></iframe>";
    echo "</div>";
    
} else {
    echo "❌ RegEx Match fehlgeschlagen!\n";
}

echo "</pre>";
