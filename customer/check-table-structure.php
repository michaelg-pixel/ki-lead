<?php
/**
 * CHECK TABLE STRUCTURE
 * Zeigt die exakte Struktur aller relevanten Tabellen
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('❌ Nicht eingeloggt!');
}

require_once __DIR__ . '/../config/database.php';

$customer_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabellen-Struktur Check</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.6;
        }
        .section {
            background: #0f0f1e;
            border: 2px solid #00ff00;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h2 {
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #2a2a4e;
            color: #00aaff;
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Tabellen-Struktur Check</h1>
    
    <div class="section">
        <h2>Customer Info</h2>
        <p><strong>Customer ID:</strong> <?php echo $customer_id; ?></p>
    </div>

    <?php
    $tables_to_check = [
        'customer_freebies',
        'freebies',
        'course_access',
        'courses',
        'freebie_click_analytics',
        'customer_tracking'
    ];

    foreach ($tables_to_check as $table) {
        echo "<div class='section'>";
        echo "<h2>📋 Tabelle: $table</h2>";
        
        try {
            // Prüfe ob Tabelle existiert
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            $exists = $stmt->rowCount() > 0;
            
            if (!$exists) {
                echo "<p class='error'>❌ Tabelle existiert NICHT!</p>";
                echo "</div>";
                continue;
            }
            
            echo "<p class='success'>✅ Tabelle existiert!</p>";
            
            // Zeige Struktur
            $stmt = $pdo->query("DESCRIBE `$table`");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Spalten:</h3>";
            echo "<table>";
            echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($structure as $column) {
                echo "<tr>";
                echo "<td><strong>" . $column['Field'] . "</strong></td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . ($column['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Zeige Anzahl Einträge
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "<p><strong>Anzahl Einträge:</strong> $count</p>";
            
            // Zeige erste 3 Einträge
            if ($count > 0) {
                $sample_stmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Beispiel-Daten (erste 3 Zeilen):</h3>";
                echo "<pre>" . print_r($samples, true) . "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    ?>
    
    <div class="section">
        <h2>🔧 Empfohlene Fixes</h2>
        
        <?php
        // Prüfe customer_freebies Struktur
        try {
            $stmt = $pdo->query("DESCRIBE customer_freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h3>customer_freebies Spalten:</h3>";
            echo "<ul>";
            foreach ($columns as $col) {
                echo "<li>$col</li>";
            }
            echo "</ul>";
            
            // Finde die richtige Spalte für Freebie-ID
            if (in_array('template_id', $columns)) {
                echo "<p class='warning'>⚠️ Spalte heißt 'template_id' statt 'freebie_id'</p>";
                echo "<p>Fix: Query anpassen auf 'cf.template_id'</p>";
            } elseif (in_array('freebie_id', $columns)) {
                echo "<p class='success'>✅ Spalte 'freebie_id' existiert</p>";
            } else {
                echo "<p class='error'>❌ Keine passende ID-Spalte gefunden!</p>";
                echo "<p>Verfügbare Spalten: " . implode(', ', $columns) . "</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>Fehler beim Prüfen: " . $e->getMessage() . "</p>";
        }
        ?>
        
        <h3>customer_tracking Tabelle fehlt?</h3>
        <?php
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'customer_tracking'");
            if ($stmt->rowCount() == 0) {
                echo "<p class='error'>❌ Tabelle 'customer_tracking' existiert nicht!</p>";
                echo "<p class='warning'>Das ist OK - Tracking ist optional. Ohne diese Tabelle werden nur Freebies und Kurse angezeigt.</p>";
            }
        } catch (PDOException $e) {
            // ignore
        }
        ?>
    </div>
    
    <p><a href="dashboard.php" style="color: #00aaff;">← Zurück zum Dashboard</a></p>
</body>
</html>