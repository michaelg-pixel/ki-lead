<?php
/**
 * TEST-DATEI: Öffentlicher Zugriff testen
 * Diese Datei sollte OHNE Login aufrufbar sein
 * URL: https://app.mehr-infos-jetzt.de/freebie/public-test.php
 */

// KEINE AUTH INCLUDES!
// Diese Datei muss öffentlich sein

echo "<!DOCTYPE html>";
echo "<html lang='de'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Public Access Test</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f0f0f0; }";
echo ".success { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; }";
echo "h1 { color: #059669; }";
echo ".info { background: white; padding: 15px; margin-top: 20px; border-radius: 8px; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='success'>";
echo "<h1>✅ Öffentlicher Zugriff funktioniert!</h1>";
echo "<p>Diese Seite ist erreichbar ohne Login.</p>";
echo "</div>";

echo "<div class='info'>";
echo "<h2>Server-Informationen:</h2>";
echo "<ul>";
echo "<li><strong>PHP Version:</strong> " . phpversion() . "</li>";
echo "<li><strong>Server:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "<li><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</li>";
echo "<li><strong>Script Filename:</strong> " . __FILE__ . "</li>";
echo "<li><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</li>";
echo "</ul>";
echo "</div>";

// Test Datenbankverbindung
echo "<div class='info'>";
echo "<h2>Datenbank-Test:</h2>";
try {
    require_once __DIR__ . '/../config/database.php';
    if (isset($pdo) && $pdo) {
        echo "<p style='color: green;'>✅ Datenbankverbindung erfolgreich</p>";
        
        // Teste Freebie-Abfrage
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM freebies");
        $result = $stmt->fetch();
        echo "<p>📊 Anzahl Freebies in DB: " . $result['count'] . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Keine Datenbankverbindung</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test .htaccess
echo "<div class='info'>";
echo "<h2>.htaccess Test:</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<p>✅ .htaccess existiert</p>";
    echo "<pre style='background: #f3f4f6; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
    echo htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess'));
    echo "</pre>";
} else {
    echo "<p>❌ Keine .htaccess gefunden</p>";
}
echo "</div>";

echo "</body>";
echo "</html>";
