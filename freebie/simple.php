<?php
/**
 * ULTRA MINIMALER TEST - Nutzt die echte config
 * Rufe auf: /freebie/simple.php?id=08385ca983cb6dfdffca575e84e22e93
 */

// Nutze die echte Datenbank-Config
require_once __DIR__ . '/../config/database.php';

$id = $_GET['id'] ?? 'KEINE ID';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Simple Test</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f3f4f6;}";
echo ".box{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:800px;margin:0 auto;}</style>";
echo "</head><body><div class='box'>";

echo "<h1>✅ PHP funktioniert!</h1>";
echo "<p><strong>ID aus URL:</strong> " . htmlspecialchars($id) . "</p>";
echo "<p><strong>DB-Verbindung:</strong> " . (isset($pdo) && $pdo ? '✅ OK' : '❌ Fehler') . "</p>";

if ($id !== 'KEINE ID' && isset($pdo) && $pdo) {
    try {
        // Suche in customer_freebies
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE unique_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<div style='background: #d1fae5; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
            echo "<h2>✅ Freebie GEFUNDEN!</h2>";
            echo "<p><strong>ID:</strong> " . $result['id'] . "</p>";
            echo "<p><strong>Customer ID:</strong> " . $result['customer_id'] . "</p>";
            echo "<p><strong>Template ID:</strong> " . $result['template_id'] . "</p>";
            echo "<p><strong>Headline:</strong> " . htmlspecialchars($result['headline']) . "</p>";
            echo "<p><strong>Layout:</strong> " . $result['layout'] . "</p>";
            echo "</div>";
            
            echo "<h3>🔗 Test-Links:</h3>";
            echo "<ul>";
            echo "<li><a href='/freebie/index.php?id=" . htmlspecialchars($id) . "'>index.php mit Parameter</a></li>";
            echo "<li><a href='/freebie/" . htmlspecialchars($id) . "'>Clean URL</a></li>";
            echo "</ul>";
        } else {
            echo "<div style='background: #fee2e2; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
            echo "<h2>❌ Freebie NICHT gefunden</h2>";
            echo "<p>Die unique_id existiert nicht in customer_freebies</p>";
            echo "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='background: #fee2e2; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
        echo "<h2>❌ Fehler</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

echo "</div></body></html>";
?>