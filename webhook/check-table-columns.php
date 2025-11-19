<?php
// 🔍 TABELLEN-STRUKTUR PRÜFEN
header('Content-Type: text/html; charset=utf-8');

$config_path = dirname(__DIR__) . '/config/database.php';
require_once $config_path;

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Tabellen Check</title></head><body>";
echo "<h1>🔍 TABELLEN-STRUKTUR CHECK</h1>";
echo "<hr>";

// customer_freebies Struktur prüfen
echo "<h2>1️⃣ customer_freebies Struktur</h2>";
$stmt = $pdo->query("DESCRIBE customer_freebies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($columns as $col) {
    $highlight = (stripos($col['Field'], 'user') !== false || stripos($col['Field'], 'customer') !== false) ? 'background: yellow;' : '';
    echo "<tr style='$highlight'>";
    echo "<td><strong>{$col['Field']}</strong></td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Key']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "<td>{$col['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

// Prüfen, welche Spalte existiert
$has_user_id = false;
$has_customer_id = false;

foreach ($columns as $col) {
    if ($col['Field'] === 'user_id') $has_user_id = true;
    if ($col['Field'] === 'customer_id') $has_customer_id = true;
}

echo "<br><div style='padding: 15px; border: 2px solid " . ($has_user_id ? "green" : "red") . ";'>";
echo "<strong>Status:</strong><br>";
echo "user_id Spalte: " . ($has_user_id ? "✅ VORHANDEN" : "❌ FEHLT") . "<br>";
echo "customer_id Spalte: " . ($has_customer_id ? "✅ VORHANDEN" : "❌ FEHLT") . "<br>";
echo "</div>";

echo "<hr>";

// marketplace_freebies Struktur prüfen
echo "<h2>2️⃣ marketplace_freebies Struktur</h2>";
$stmt = $pdo->query("DESCRIBE marketplace_freebies");
$mp_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($mp_columns as $col) {
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

// users Tabelle prüfen
echo "<h2>3️⃣ users Struktur</h2>";
$stmt = $pdo->query("DESCRIBE users");
$user_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; margin: 10px;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
foreach ($user_columns as $col) {
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

// DIAGNOSE
echo "<h2>4️⃣ DIAGNOSE</h2>";
echo "<div style='background: " . ($has_user_id ? "#d4edda" : "#f8d7da") . "; border: 2px solid " . ($has_user_id ? "#28a745" : "#dc3545") . "; padding: 20px;'>";

if ($has_user_id && !$has_customer_id) {
    echo "<h3>✅ KORREKT MIGRIERT!</h3>";
    echo "Die Tabelle customer_freebies verwendet user_id.<br>";
    echo "Der Webhook-Code sollte funktionieren.";
} elseif (!$has_user_id && $has_customer_id) {
    echo "<h3>❌ MIGRATION NICHT DURCHGEFÜHRT!</h3>";
    echo "Die Tabelle customer_freebies verwendet noch customer_id.<br>";
    echo "Der Webhook-Code erwartet aber user_id!<br><br>";
    echo "<strong>LÖSUNG:</strong><br>";
    echo "1. Migration durchführen: customer_id → user_id<br>";
    echo "2. ODER: Webhook-Code anpassen, um customer_id zu verwenden";
} elseif ($has_user_id && $has_customer_id) {
    echo "<h3>⚠️ BEIDE SPALTEN VORHANDEN!</h3>";
    echo "Die Tabelle hat sowohl user_id als auch customer_id.<br>";
    echo "Das ist ein Übergangszustand - eine Spalte sollte entfernt werden.";
} else {
    echo "<h3>❌ KEINE ID-SPALTE GEFUNDEN!</h3>";
    echo "Weder user_id noch customer_id existiert!<br>";
    echo "Die Tabellen-Struktur ist fehlerhaft.";
}

echo "</div>";

echo "</body></html>";
?>
