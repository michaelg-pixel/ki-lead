<?php
/**
 * Direkter Test der Freebie-Logik
 * Rufe auf: /test-freebie-direct.php?id=08385ca983cb6dfdffca575e84e22e93
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Freebie Direct Test</title></head><body>";
echo "<h1>🧪 Direkter Freebie Test</h1>";

// Identifier aus URL holen
$identifier = $_GET['id'] ?? null;

if (!$identifier) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ Keine ID in der URL gefunden!<br>";
    echo "Verwende: /test-freebie-direct.php?id=YOUR_UNIQUE_ID";
    echo "</div>";
    exit;
}

echo "<h2>Testing ID: " . htmlspecialchars($identifier) . "</h2>";

// Database connection
require_once __DIR__ . '/config/database.php';

// Check database connection
if (!isset($pdo) || !$pdo) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ Datenbankverbindung fehlgeschlagen";
    echo "</div>";
    exit;
}

echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "✅ Datenbankverbindung OK";
echo "</div>";

// Find the freebie - first check customer_freebies, then template freebies
$customer_id = null;
try {
    echo "<h3>1. Suche in customer_freebies:</h3>";
    
    $stmt = $pdo->prepare("SELECT cf.*, u.id as customer_id FROM customer_freebies cf LEFT JOIN users u ON cf.customer_id = u.id WHERE cf.unique_id = ? LIMIT 1");
    $stmt->execute([$identifier]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($freebie) {
        $customer_id = $freebie['customer_id'] ?? null;
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ Gefunden in customer_freebies!<br>";
        echo "<strong>ID:</strong> " . $freebie['id'] . "<br>";
        echo "<strong>Customer ID:</strong> " . $customer_id . "<br>";
        echo "<strong>Template ID:</strong> " . $freebie['template_id'] . "<br>";
        echo "<strong>Headline:</strong> " . htmlspecialchars($freebie['headline']) . "<br>";
        echo "<strong>Layout:</strong> " . $freebie['layout'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #fef3c7; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "⚠️ Nicht in customer_freebies gefunden, prüfe Templates...";
        echo "</div>";
        
        echo "<h3>2. Suche in freebies (Templates):</h3>";
        $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freebie) {
            echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
            echo "✅ Gefunden in freebies (Template)!<br>";
            echo "<strong>ID:</strong> " . $freebie['id'] . "<br>";
            echo "<strong>Name:</strong> " . htmlspecialchars($freebie['name']) . "<br>";
            echo "</div>";
        } else {
            echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
            echo "❌ Freebie nicht gefunden!<br>";
            echo "Die unique_id existiert weder in customer_freebies noch in freebies.";
            echo "</div>";
            exit;
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ Datenbankfehler: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    exit;
}

// Wenn wir hier sind, wurde das Freebie gefunden
echo "<div style='background: #dbeafe; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
echo "<h3>✅ Freebie erfolgreich gefunden!</h3>";
echo "<p>Die Freebie-Seite sollte funktionieren.</p>";
echo "<p><strong>Customer ID für Rechtstexte:</strong> " . ($customer_id ?? 'Nicht verfügbar (Template)') . "</p>";
echo "</div>";

// Test: Simuliere die Footer-Links
$impressum_link = $customer_id ? "/impressum.php?customer=" . $customer_id : "/impressum.php";
$datenschutz_link = $customer_id ? "/datenschutz.php?customer=" . $customer_id : "/datenschutz.php";

echo "<h3>Footer-Links (die generiert würden):</h3>";
echo "<ul>";
echo "<li><strong>Impressum:</strong> <a href='" . htmlspecialchars($impressum_link) . "'>" . htmlspecialchars($impressum_link) . "</a></li>";
echo "<li><strong>Datenschutz:</strong> <a href='" . htmlspecialchars($datenschutz_link) . "'>" . htmlspecialchars($datenschutz_link) . "</a></li>";
echo "</ul>";

// Teste den echten Link
$real_link = "/freebie/" . $identifier;
echo "<hr>";
echo "<h3>🔗 Teste den echten Link:</h3>";
echo "<p><a href='" . htmlspecialchars($real_link) . "' target='_blank' style='background: #667eea; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;'>Öffne Freebie-Seite</a></p>";

echo "</body></html>";
?>