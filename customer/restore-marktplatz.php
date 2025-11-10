<?php
/**
 * NOTFALL-WIEDERHERSTELLUNG: Marktplatz.php
 * Stellt die komplette marktplatz.php Datei wieder her
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/customer/restore-marktplatz.php
 * Nach Ausführung LÖSCHEN!
 */

$target_file = __DIR__ . '/sections/marktplatz.php';

// Backup erstellen
$backup_file = $target_file . '.backup.' . date('Y-m-d-H-i-s');
if (file_exists($target_file)) {
    copy($target_file, $backup_file);
    echo "<h3>Backup erstellt: " . basename($backup_file) . "</h3>";
}

// GitHub Raw URL der letzten funktionierenden Version
$github_url = 'https://raw.githubusercontent.com/michaelg-pixel/ki-lead/b93354ad2b5ec631fa9507459137843b32ff438a/customer/sections/marktplatz.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px;}";
echo ".success{background:#d4edda;border-left:4px solid #28a745;padding:15px;margin:10px 0;}";
echo ".error{background:#f8d7da;border-left:4px solid #dc3545;padding:15px;margin:10px 0;}";
echo "</style></head><body>";

echo "<h1>🔧 Marktplatz.php Wiederherstellung</h1>";

// Versuche von GitHub zu laden
echo "<p>Lade funktionierende Version von GitHub...</p>";

$content = @file_get_contents($github_url);

if ($content === false) {
    echo "<div class='error'><h3>❌ Fehler beim Laden von GitHub</h3>";
    echo "<p>Konnte Datei nicht von GitHub laden. Verwende manuelle Wiederherstellung...</p></div>";
    
    // Manuelle Wiederherstellung mit minimaler Funktionalität
    $content = '<?php
// Marktplatz Section - MINIMAL VERSION
global $pdo;
if (!isset($pdo)) {
    require_once "../config/database.php";
    $pdo = getDBConnection();
}
$customer_id = $_SESSION["user_id"] ?? 0;

// Eigene Freebies laden
$stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ? AND freebie_type = \"custom\" ORDER BY created_at DESC");
$stmt->execute([$customer_id]);
$my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div style="padding:32px;">
    <h1>🏪 Marktplatz (Wiederherstellungsmodus)</h1>
    <p>Die Marktplatz-Funktion wurde temporär wiederhergestellt.</p>
    <p style="color:red;"><strong>Bitte kontaktiere den Support für die vollständige Version!</strong></p>
    
    <h2>Deine Freebies (<?php echo count($my_freebies); ?>)</h2>
    <?php foreach ($my_freebies as $freebie): ?>
        <div style="padding:20px;margin:10px 0;background:#f5f5f5;border-radius:8px;">
            <h3><?php echo htmlspecialchars($freebie["headline"]); ?></h3>
            <p>ID: <?php echo $freebie["id"]; ?></p>
        </div>
    <?php endforeach; ?>
</div>';
}

// Sicherstellen, dass freebie_type = 'custom' in der Query ist
$content = preg_replace(
    '/WHERE customer_id = \? \s+AND freebie_type IN \([^)]+\)\s+AND copied_from_freebie_id IS NULL/i',
    "WHERE customer_id = ? \nAND freebie_type = 'custom'",
    $content
);

// Datei schreiben
if (file_put_contents($target_file, $content) !== false) {
    echo "<div class='success'>";
    echo "<h3>✅ Datei erfolgreich wiederhergestellt!</h3>";
    echo "<p><strong>Größe:</strong> " . number_format(strlen($content)) . " Bytes</p>";
    echo "<p><strong>Query korrigiert:</strong> Nur freebie_type = 'custom'</p>";
    echo "</div>";
    
    echo "<div class='success'>";
    echo "<h3>🎯 Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li><a href='/customer/dashboard.php?page=marktplatz'>Teste den Marktplatz</a></li>";
    echo "<li>Lösche diese Datei: <code>customer/restore-marktplatz.php</code></li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>❌ Fehler beim Schreiben der Datei</h3>";
    echo "<p>Konnte Datei nicht schreiben. Prüfe die Berechtigungen.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
