<?php
/**
 * ULTRA MINIMALER TEST - Kein Auth, keine .htaccess, nur PHP
 * Rufe auf: /freebie/simple.php?id=08385ca983cb6dfdffca575e84e22e93
 */

// ABSOLUT KEINE INCLUDES - nur direkter DB-Zugriff
$host = 'localhost';
$dbname = 'michaelg_ki_lead';
$username = 'michaelg_ki_lead';
$password = 'fG6!aM8#xR2$wN9@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $id = $_GET['id'] ?? 'KEINE ID';
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Simple Test</title></head><body>";
    echo "<h1>✅ PHP funktioniert!</h1>";
    echo "<p><strong>ID aus URL:</strong> " . htmlspecialchars($id) . "</p>";
    
    if ($id !== 'KEINE ID') {
        // Suche in customer_freebies
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE unique_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<div style='background: #d1fae5; padding: 20px; margin: 20px 0;'>";
            echo "<h2>✅ Freebie GEFUNDEN!</h2>";
            echo "<p><strong>Headline:</strong> " . htmlspecialchars($result['headline']) . "</p>";
            echo "<p><strong>Customer ID:</strong> " . $result['customer_id'] . "</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #fee2e2; padding: 20px; margin: 20px 0;'>";
            echo "<h2>❌ Freebie NICHT gefunden</h2>";
            echo "</div>";
        }
    }
    
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><body>";
    echo "<h1>❌ Fehler</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}
?>