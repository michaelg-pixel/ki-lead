<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Alle Course-Tabellen</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#0f0f1e;color:#fff;font-size:12px;}";
echo ".box{background:#1a1a2e;padding:15px;margin:15px 0;border-radius:8px;border:1px solid #667eea;}";
echo "h3{color:#667eea;margin:10px 0;}pre{background:#000;padding:10px;border-radius:5px;overflow-x:auto;font-size:11px;}";
echo "table{width:100%;border-collapse:collapse;margin:10px 0;font-size:11px;}";
echo "th,td{padding:5px;text-align:left;border-bottom:1px solid #333;}th{color:#667eea;}</style></head><body>";

echo "<h1>🔍 ALLE COURSE-TABELLEN</h1>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $courseTables = [];
    foreach ($allTables as $table) {
        if (stripos($table, 'course') !== false || stripos($table, 'freebie') !== false) {
            $courseTables[] = $table;
        }
    }
    
    echo "<p>Gefunden: " . count($courseTables) . " Tabellen</p>";
    
    foreach ($courseTables as $table) {
        echo "<div class='box'>";
        echo "<h3>📋 $table</h3>";
        
        // Struktur
        $stmt = $pdo->query("DESCRIBE $table");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table><tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
        foreach ($cols as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td></tr>";
        }
        echo "</table>";
        
        // Anzahl Einträge
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<p>Einträge: $count</p>";
        
        // Wenn Einträge vorhanden, erste 2 zeigen
        if ($count > 0 && $count < 1000) {
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 2");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($samples) {
                echo "<p>Beispiel-Daten:</p><pre>" . print_r($samples, true) . "</pre>";
            }
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:#ff4444'>Fehler: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>