<?php
/**
 * Minimaler Test im Freebie-Verzeichnis
 * Rufe auf: /freebie/test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Freebie Test</title></head><body>";
echo "<h1>🧪 Freebie Verzeichnis Test</h1>";

echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "✅ Diese Seite wird erfolgreich geladen!<br>";
echo "Das bedeutet: Das Freebie-Verzeichnis ist erreichbar.";
echo "</div>";

// Test: Kann auf die Datenbank zugreifen?
try {
    require_once __DIR__ . '/../config/database.php';
    
    if (isset($pdo) && $pdo) {
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ Datenbankverbindung funktioniert";
        echo "</div>";
        
        // Test Query
        $stmt = $pdo->query("SELECT COUNT(*) FROM customer_freebies");
        $count = $stmt->fetchColumn();
        
        echo "<div style='background: #dbeafe; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "📊 Anzahl Customer-Freebies in DB: " . $count;
        echo "</div>";
    } else {
        echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "❌ Datenbankverbindung fehlgeschlagen";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ Fehler: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>Test-Link zur index.php:</h3>";
echo "<p><a href='/freebie/index.php?id=08385ca983cb6dfdffca575e84e22e93'>Direkt zur index.php mit Parameter</a></p>";

echo "<hr>";
echo "<h3>Test der .htaccess Regeln:</h3>";
echo "<p><a href='/freebie/08385ca983cb6dfdffca575e84e22e93'>Clean URL Test</a></p>";

echo "</body></html>";
?>