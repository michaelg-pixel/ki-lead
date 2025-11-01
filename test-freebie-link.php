<?php
/**
 * Debug-Skript zum Testen von Freebie-Links
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 Freebie Link Debug</h1>";

// Test unique_id
$test_unique_id = '08385ca983cb6dfdffca575e84e22e93';

echo "<h2>Testing unique_id: " . htmlspecialchars($test_unique_id) . "</h2>";

try {
    // Suche in customer_freebies
    echo "<h3>1. Suche in customer_freebies:</h3>";
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE unique_id = ?");
    $stmt->execute([$test_unique_id]);
    $customer_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer_freebie) {
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ Gefunden in customer_freebies!<br>";
        echo "<pre>" . print_r($customer_freebie, true) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "❌ NICHT gefunden in customer_freebies<br>";
        echo "</div>";
    }
    
    // Suche in freebies (Templates)
    echo "<h3>2. Suche in freebies (Templates):</h3>";
    $stmt = $pdo->prepare("SELECT * FROM freebies WHERE unique_id = ? OR url_slug = ?");
    $stmt->execute([$test_unique_id, $test_unique_id]);
    $template_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($template_freebie) {
        echo "<div style='background: #d1fae5; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "✅ Gefunden in freebies (Template)!<br>";
        echo "<pre>" . print_r($template_freebie, true) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
        echo "❌ NICHT gefunden in freebies (Templates)<br>";
        echo "</div>";
    }
    
    // Zeige alle customer_freebies
    echo "<h3>3. Alle customer_freebies:</h3>";
    $stmt = $pdo->query("SELECT id, customer_id, template_id, unique_id, headline, created_at FROM customer_freebies ORDER BY created_at DESC LIMIT 10");
    $all_customer_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_customer_freebies) {
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Customer ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Template ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Unique ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Headline</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Erstellt</th>";
        echo "</tr>";
        
        foreach ($all_customer_freebies as $cf) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $cf['id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $cf['customer_id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $cf['template_id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 11px;'>" . $cf['unique_id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($cf['headline']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $cf['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Zeige alle freebies (Templates)
    echo "<h3>4. Alle freebies (Templates):</h3>";
    $stmt = $pdo->query("SELECT id, name, unique_id, url_slug, created_at FROM freebies ORDER BY created_at DESC");
    $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($all_freebies) {
        echo "<table style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f3f4f6;'>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Name</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Unique ID</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>URL Slug</th>";
        echo "<th style='padding: 10px; border: 1px solid #ddd;'>Erstellt</th>";
        echo "</tr>";
        
        foreach ($all_freebies as $f) {
            echo "<tr>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $f['id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . htmlspecialchars($f['name']) . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd; font-family: monospace; font-size: 11px;'>" . $f['unique_id'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $f['url_slug'] . "</td>";
            echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $f['created_at'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #fee2e2; padding: 20px; border-radius: 8px; margin: 10px 0;'>";
    echo "❌ Datenbankfehler: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h3>💡 Hinweise:</h3>";
echo "<ul>";
echo "<li>Die unique_id sollte entweder in <code>customer_freebies</code> (kundenspezifisch) oder in <code>freebies</code> (Template) existieren</li>";
echo "<li>Wenn die unique_id nirgendwo gefunden wird, führt das zum Fehler</li>";
echo "<li>Prüfe ob der Kunde das Template bereits gespeichert hat</li>";
echo "</ul>";
?>