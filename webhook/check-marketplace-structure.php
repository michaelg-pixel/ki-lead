<?php
// 🔍 MARKETPLACE_FREEBIES TABELLEN-STRUKTUR PRÜFEN
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
require_once $config_path;

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Marketplace Freebies Struktur</title></head><body>";
echo "<h1>🔍 MARKETPLACE_FREEBIES STRUKTUR</h1>";
echo "<hr>";

// Struktur prüfen
echo "<h2>1️⃣ TABELLENSTRUKTUR</h2>";
$stmt = $pdo->query("DESCRIBE marketplace_freebies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    echo "<tr>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// Alle Einträge anzeigen
echo "<h2>2️⃣ ALLE MARKETPLACE_FREEBIES</h2>";
$stmt = $pdo->query("SELECT * FROM marketplace_freebies");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($all)) {
    echo "❌ Keine Einträge gefunden!<br>";
} else {
    echo "✅ Einträge gefunden: " . count($all) . "<br><br>";
    
    foreach ($all as $row) {
        echo "<div style='border: 2px solid #0066cc; padding: 15px; margin: 10px 0;'>";
        foreach ($row as $key => $value) {
            $highlight = (stripos($key, 'product') !== false) ? 'background: yellow;' : '';
            echo "<div style='$highlight'><strong>$key:</strong> $value</div>";
        }
        echo "</div>";
    }
}

echo "<hr>";

// customer_freebies mit marketplace_enabled prüfen
echo "<h2>3️⃣ CUSTOMER_FREEBIES MIT MARKETPLACE_ENABLED=1</h2>";
$stmt = $pdo->query("
    SELECT id, customer_id, headline, digistore_product_id, marketplace_enabled, marketplace_price 
    FROM customer_freebies 
    WHERE marketplace_enabled = 1
");
$mp_customer_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mp_customer_freebies)) {
    echo "❌ Keine Marktplatz-Freebies in customer_freebies gefunden!<br>";
} else {
    echo "✅ Marktplatz-Freebies gefunden: " . count($mp_customer_freebies) . "<br><br>";
    
    foreach ($mp_customer_freebies as $row) {
        echo "<div style='border: 2px solid green; padding: 15px; margin: 10px 0;'>";
        echo "<strong>ID:</strong> {$row['id']}<br>";
        echo "<strong>Customer ID:</strong> {$row['customer_id']}<br>";
        echo "<strong>Headline:</strong> {$row['headline']}<br>";
        echo "<strong>Digistore Produkt-ID:</strong> <span style='font-size: 18px; color: red; font-weight: bold;'>{$row['digistore_product_id']}</span><br>";
        echo "<strong>Marketplace Price:</strong> {$row['marketplace_price']}<br>";
        echo "</div>";
    }
}

echo "</body></html>";
?>
