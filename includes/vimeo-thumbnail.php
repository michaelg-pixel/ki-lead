<?php
/**
 * Vimeo Thumbnail Helper
 * Holt das Thumbnail für ein Vimeo-Video
 */

function getVimeoThumbnail($videoId) {
    $url = "https://vimeo.com/api/oembed.json?url=https://vimeo.com/" . $videoId;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['thumbnail_url'])) {
            // Vimeo Thumbnails haben unterschiedliche Größen
            // Wir wollen die größte verfügbare
            $thumbnail = $data['thumbnail_url'];
            // Ersetze die Größe mit der größten verfügbaren
            $thumbnail = preg_replace('/_\d+x\d+/', '_1280x720', $thumbnail);
            return $thumbnail;
        }
    }
    
    // Fallback: Generisches Vimeo-Thumbnail-URL-Format
    return "https://i.vimeocdn.com/video/{$videoId}_1280x720.jpg";
}

// Test
if (isset($_GET['test'])) {
    $videoId = $_GET['test'];
    echo "<h2>Vimeo Thumbnail Test</h2>";
    echo "<p>Video ID: $videoId</p>";
    $thumb = getVimeoThumbnail($videoId);
    echo "<p>Thumbnail URL: $thumb</p>";
    echo "<img src='$thumb' style='max-width:500px;border:2px solid #ccc;'>";
}
