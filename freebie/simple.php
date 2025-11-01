<?php
/**
 * ULTRA MINIMALER TEST - Direkte Credentials
 * Rufe auf: /freebie/simple.php?id=08385ca983cb6dfdffca575e84e22e93
 */

// DIREKTE CREDENTIALS (aus config/database.php)
$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

$id = $_GET['id'] ?? 'KEINE ID';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Simple Test</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f3f4f6;}";
echo ".box{background:white;padding:30px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:800px;margin:0 auto;}</style>";
echo "</head><body><div class='box'>";

echo "<h1>🔍 Simple Freebie Test</h1>";
echo "<p><strong>ID aus URL:</strong> " . htmlspecialchars($id) . "</p>";

try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
    
    echo "<p><strong>DB-Verbindung:</strong> ✅ Erfolgreich</p>";
    echo "<p><strong>Database:</strong> " . htmlspecialchars($database) . "</p>";
    
    if ($id !== 'KEINE ID') {
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
            
            echo "<hr>";
            echo "<h3>🔗 Test die Links:</h3>";
            echo "<ol>";
            echo "<li><a href='/freebie/index.php?id=" . htmlspecialchars($id) . "' target='_blank'>index.php mit Parameter</a></li>";
            echo "<li><a href='/freebie/debug.php?id=" . htmlspecialchars($id) . "' target='_blank'>debug.php mit Parameter</a></li>";
            echo "<li><a href='/freebie/" . htmlspecialchars($id) . "' target='_blank'>Clean URL (wird weitergeleitet)</a></li>";
            echo "</ol>";
            
            echo "<p><small>Klicke auf die Links oben. Wenn Clean URL zum Dashboard leitet, dann ist es ein .htaccess oder Auth-Problem.</small></p>";
        } else {
            echo "<div style='background: #fee2e2; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
            echo "<h2>❌ Freebie NICHT gefunden</h2>";
            echo "<p>Die unique_id <code>" . htmlspecialchars($id) . "</code> existiert nicht in customer_freebies</p>";
            echo "</div>";
        }
    } else {
        echo "<p><em>Füge <code>?id=08385ca983cb6dfdffca575e84e22e93</code> zur URL hinzu</em></p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
    echo "<h2>❌ Fehler</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>