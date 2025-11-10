<?php
// Debug Script für marktplatz.php
// Aufruf: https://app.mehr-infos-jetzt.de/debug-marktplatz.php

echo "<h2>🔍 Marktplatz Debug</h2>";

// 1. Datei existiert?
$file = __DIR__ . '/customer/marktplatz.php';
echo "<h3>1. Datei Check</h3>";
echo "Pfad: " . $file . "<br>";
echo "Existiert: " . (file_exists($file) ? '✅ JA' : '❌ NEIN') . "<br>";
echo "Größe: " . (file_exists($file) ? filesize($file) . ' Bytes' : 'N/A') . "<br>";
echo "Lesbar: " . (is_readable($file) ? '✅ JA' : '❌ NEIN') . "<br>";

// 2. Inhalt anzeigen (erste 500 Zeichen)
if (file_exists($file)) {
    echo "<h3>2. Datei-Inhalt (erste 500 Zeichen)</h3>";
    $content = file_get_contents($file);
    echo "<pre style='background: #f5f5f5; padding: 10px; overflow: auto;'>";
    echo htmlspecialchars(substr($content, 0, 500));
    echo "</pre>";
    
    // 3. PHP Syntax Check
    echo "<h3>3. PHP Syntax Check</h3>";
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_code);
    if ($return_code === 0) {
        echo "✅ Syntax OK<br>";
    } else {
        echo "❌ Syntax Fehler:<br>";
        echo "<pre style='background: #fee; padding: 10px; color: red;'>";
        echo implode("\n", $output);
        echo "</pre>";
    }
}

// 4. Dashboard integration Check
echo "<h3>4. Dashboard Integration</h3>";
$dashboard = __DIR__ . '/customer/dashboard.php';
if (file_exists($dashboard)) {
    echo "Dashboard existiert: ✅<br>";
    
    // Prüfe ob marktplatz.php included wird
    $dashboard_content = file_get_contents($dashboard);
    if (strpos($dashboard_content, 'marktplatz.php') !== false) {
        echo "marktplatz.php wird included: ✅<br>";
    } else {
        echo "marktplatz.php wird NICHT included: ⚠️<br>";
    }
} else {
    echo "Dashboard nicht gefunden: ❌<br>";
}

// 5. Datenbank Check
echo "<h3>5. Datenbank Check</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    
    // Prüfe ob customer_freebies Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    if ($stmt->rowCount() > 0) {
        echo "Tabelle customer_freebies: ✅<br>";
        
        // Prüfe Spalten
        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $required = ['marketplace_enabled', 'marketplace_price', 'digistore_product_id', 'marketplace_description', 'freebie_type'];
        foreach ($required as $col) {
            if (in_array($col, $columns)) {
                echo "Spalte $col: ✅<br>";
            } else {
                echo "Spalte $col: ❌ FEHLT<br>";
            }
        }
    } else {
        echo "Tabelle customer_freebies: ❌ NICHT GEFUNDEN<br>";
    }
} catch (Exception $e) {
    echo "❌ Datenbank Fehler: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>📝 Nächste Schritte:</h3>";
echo "<ol>";
echo "<li>Wenn Syntax OK: <a href='/customer/dashboard.php?page=marktplatz'>Marktplatz testen</a></li>";
echo "<li>Wenn Fehler: Fehler beheben und erneut deployen</li>";
echo "</ol>";
?>
