<?php
/**
 * Prüfe erlaubte ENUM-Werte für freebie_type
 */

require_once '../config/database.php';

echo "<h1>📋 ENUM-Werte Check</h1><pre>";

try {
    $pdo = getDBConnection();
    
    // ENUM-Werte für freebie_type ermitteln
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field = 'freebie_type'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "=== freebie_type Spalten-Info ===\n";
    echo "Type: " . $row['Type'] . "\n\n";
    
    // ENUM-Werte extrahieren
    preg_match("/^enum\(\'(.*)\'\)$/", $row['Type'], $matches);
    $enumValues = explode("','", $matches[1]);
    
    echo "=== ERLAUBTE WERTE ===\n";
    foreach ($enumValues as $value) {
        echo "- '$value'\n";
    }
    
    echo "\n=== AKTUELL VERWENDETE WERTE ===\n";
    $stmt = $pdo->query("SELECT DISTINCT freebie_type, COUNT(*) as count FROM customer_freebies GROUP BY freebie_type");
    $used = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($used as $u) {
        echo "- '" . $u['freebie_type'] . "' (" . $u['count'] . " Freebies)\n";
    }
    
    echo "\n=== EMPFEHLUNG ===\n";
    if (in_array('marketplace', $enumValues)) {
        echo "✅ Verwende 'marketplace' für Marktplatz-Käufe\n";
    } elseif (in_array('template', $enumValues)) {
        echo "✅ Verwende 'template' als Fallback\n";
    } else {
        echo "⚠️ Verwende ersten verfügbaren Wert: '" . $enumValues[0] . "'\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

echo "</pre>";
?>
