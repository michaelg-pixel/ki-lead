<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Bitte einloggen');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ALLE Tabellen zeigen</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #fff; padding: 20px; }
        .box { background: #16213e; padding: 20px; margin: 10px 0; border-radius: 10px; }
        h2 { color: #667eea; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #667eea; }
    </style>
</head>
<body>
    <h1>🔍 ALLE Datenbank-Tabellen & Strukturen</h1>
    
    <div class="box">
        <h2>Customer ID: <?php echo $customer_id; ?></h2>
    </div>
    
    <?php
    // ALLE Tabellen auflisten
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div class='box'>";
    echo "<h2>📋 Gefundene Tabellen (" . count($tables) . "):</h2>";
    echo "<p>" . implode(', ', $tables) . "</p>";
    echo "</div>";
    
    // Jede Tabelle mit "freebie" im Namen detailliert anzeigen
    foreach ($tables as $table) {
        if (stripos($table, 'freebie') !== false || stripos($table, 'customer') !== false) {
            echo "<div class='box'>";
            echo "<h2>📊 Tabelle: $table</h2>";
            
            // Struktur
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Struktur:</h3>";
            echo "<table>";
            echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Anzahl Einträge
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<p><strong>Anzahl Einträge:</strong> $count</p>";
            } catch (Exception $e) {
                echo "<p>Fehler beim Zählen: " . $e->getMessage() . "</p>";
            }
            
            // Erste 3 Zeilen anzeigen
            try {
                $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($rows)) {
                    echo "<h3>Erste 3 Einträge:</h3>";
                    echo "<table>";
                    
                    // Header
                    echo "<tr>";
                    foreach (array_keys($rows[0]) as $column) {
                        echo "<th>$column</th>";
                    }
                    echo "</tr>";
                    
                    // Daten
                    foreach ($rows as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            $display = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                            echo "<td>" . htmlspecialchars($display ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } catch (Exception $e) {
                echo "<p>Fehler beim Lesen: " . $e->getMessage() . "</p>";
            }
            
            echo "</div>";
        }
    }
    ?>
    
    <div class="box">
        <h2>🔍 Welche Tabelle verbindet Kunden mit Freebies?</h2>
        <p>Suche nach Tabellen die sowohl customer/user_id als auch freebie_id haben...</p>
        <?php
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
                
                $has_user = in_array('user_id', $columns) || in_array('customer_id', $columns);
                $has_freebie = in_array('freebie_id', $columns);
                
                if ($has_user && $has_freebie) {
                    echo "<p style='color: #28a745; font-size: 18px;'>✅ <strong>$table</strong> - Das ist die Zuordnungstabelle!</p>";
                    
                    // Spalten anzeigen
                    echo "<p>Spalten: " . implode(', ', $columns) . "</p>";
                }
            } catch (Exception $e) {
                // Tabelle überspringen
            }
        }
        ?>
    </div>
    
    <p><a href="/customer/dashboard.php?page=overview" style="color: #667eea; font-size: 18px;">← Zurück</a></p>
</body>
</html>
