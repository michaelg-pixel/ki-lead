<?php
/**
 * Database Debug Script - FIXED
 * Zeigt alle relevanten Daten für das Dashboard
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Bitte zuerst einloggen: <a href="/public/login.php">Login</a>');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Unbekannt';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Debug - Dashboard Daten</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a2e;
            color: #e0e0e0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #16213e;
            padding: 30px;
            border-radius: 10px;
        }
        h1 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
        }
        .info-box {
            background: #0f0f1e;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: #0f0f1e;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #1a1a3e;
        }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            opacity: 0.9;
        }
        pre {
            background: #0f0f1e;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Debug - Dashboard Daten</h1>
        
        <div class="info-box">
            <strong>Eingeloggt als:</strong> <?php echo htmlspecialchars($customer_name); ?><br>
            <strong>Customer ID:</strong> <?php echo $customer_id; ?><br>
            <strong>Rolle:</strong> <?php echo $_SESSION['role']; ?>
        </div>

        <p>
            <a href="/customer/dashboard.php?page=overview" class="btn">← Zurück zum Dashboard</a>
            <a href="javascript:location.reload()" class="btn">🔄 Neu laden</a>
        </p>

        <?php
        // ===== CUSTOMER_FREEBIES STRUKTUR PRÜFEN =====
        echo "<h2>🎁 Customer Freebies - Tabellen-Struktur</h2>";
        try {
            $stmt = $pdo->query("DESCRIBE customer_freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Alle Spaltennamen sammeln
            $column_names = array_column($columns, 'Field');
            echo "<div class='info-box'>";
            echo "<strong>Verfügbare Spalten:</strong> " . implode(', ', $column_names);
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler: " . $e->getMessage() . "</div>";
        }

        // ===== CUSTOMER_FREEBIES DATEN =====
        echo "<h2>📊 Deine Freebies (Rohdaten)</h2>";
        try {
            $stmt = $pdo->query("SELECT * FROM customer_freebies LIMIT 10");
            $all_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($all_data)) {
                echo "<div class='info-box warning'>⚠️ Tabelle ist leer</div>";
            } else {
                echo "<h3>Erste 10 Einträge aus customer_freebies:</h3>";
                echo "<table>";
                
                // Header dynamisch generieren
                $first_row = $all_data[0];
                echo "<tr>";
                foreach (array_keys($first_row) as $column) {
                    echo "<th>$column</th>";
                }
                echo "</tr>";
                
                // Daten ausgeben
                foreach ($all_data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler: " . $e->getMessage() . "</div>";
        }

        // ===== FILTERN NACH CUSTOMER ID =====
        echo "<h2>🔍 Filter: Nur deine Freebies</h2>";
        try {
            // Prüfen welche Spalte für Customer ID verwendet wird
            $stmt = $pdo->query("DESCRIBE customer_freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $column_names = array_column($columns, 'Field');
            
            $customer_column = null;
            if (in_array('customer_id', $column_names)) {
                $customer_column = 'customer_id';
            } elseif (in_array('user_id', $column_names)) {
                $customer_column = 'user_id';
            }
            
            if ($customer_column) {
                $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE $customer_column = ?");
                $stmt->execute([$customer_id]);
                $my_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<div class='info-box success'>";
                echo "<strong>✅ Gefunden:</strong> " . count($my_freebies) . " Freebies für Customer ID $customer_id";
                echo "</div>";
                
                if (!empty($my_freebies)) {
                    echo "<table>";
                    $first_row = $my_freebies[0];
                    echo "<tr>";
                    foreach (array_keys($first_row) as $column) {
                        echo "<th>$column</th>";
                    }
                    echo "</tr>";
                    
                    foreach ($my_freebies as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<div class='info-box error'>❌ Keine customer_id oder user_id Spalte gefunden!</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler: " . $e->getMessage() . "</div>";
        }

        // ===== COURSE_ACCESS STRUKTUR =====
        echo "<h2>🎓 Course Access - Tabellen-Struktur</h2>";
        try {
            $stmt = $pdo->query("DESCRIBE course_access");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
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
            
        } catch (PDOException $e) {
            echo "<div class='info-box warning'>⚠️ Tabelle course_access existiert nicht: " . $e->getMessage() . "</div>";
        }

        // ===== TRACKING TABELLE =====
        echo "<h2>📊 Customer Tracking - Tabellen-Struktur</h2>";
        try {
            $stmt = $pdo->query("DESCRIBE customer_tracking");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='info-box success'>✅ Tabelle customer_tracking existiert!</div>";
            
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
            
            // Anzahl Tracking-Einträge
            $stmt = $pdo->query("SELECT COUNT(*) FROM customer_tracking");
            $count = $stmt->fetchColumn();
            echo "<div class='info-box'>Tracking-Einträge gesamt: <strong>$count</strong></div>";
            
        } catch (PDOException $e) {
            echo "<div class='info-box warning'>⚠️ Tabelle customer_tracking existiert nicht: " . $e->getMessage() . "</div>";
        }

        // ===== ZUSAMMENFASSUNG & LÖSUNG =====
        echo "<h2>💡 Diagnose & Lösung</h2>";
        
        try {
            // Prüfen welche Spalte für Customer ID in customer_freebies existiert
            $stmt = $pdo->query("DESCRIBE customer_freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $column_names = array_column($columns, 'Field');
            
            echo "<div class='info-box'>";
            echo "<h3>Status:</h3>";
            
            // 1. Customer ID Spalte prüfen
            if (in_array('customer_id', $column_names)) {
                echo "✅ Spalte 'customer_id' existiert<br>";
                $customer_column = 'customer_id';
            } elseif (in_array('user_id', $column_names)) {
                echo "✅ Spalte 'user_id' existiert (wird als customer_id verwendet)<br>";
                $customer_column = 'user_id';
            } else {
                echo "❌ Keine customer_id oder user_id Spalte gefunden!<br>";
                $customer_column = null;
            }
            
            // 2. Freebies zählen
            if ($customer_column) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE $customer_column = ?");
                $stmt->execute([$customer_id]);
                $freebie_count = $stmt->fetchColumn();
                
                echo "📊 Deine Freebies: <strong class='success'>$freebie_count</strong><br>";
                
                if ($freebie_count > 0) {
                    echo "<br><strong class='success'>🎉 Perfekt! Du hast $freebie_count Freebies!</strong><br>";
                    echo "Diese sollten im Dashboard angezeigt werden.";
                } else {
                    echo "<br><strong class='warning'>⚠️ Du hast noch keine Freebies erstellt.</strong><br>";
                    echo "Gehe zu 'Freebies' im Dashboard und erstelle dein erstes Freebie!";
                }
            }
            
            echo "</div>";
            
            // SQL FIX für overview.php generieren
            if ($customer_column && $customer_column !== 'customer_id') {
                echo "<div class='info-box error'>";
                echo "<h3>🔧 Problem gefunden!</h3>";
                echo "Die Tabelle verwendet '<strong>$customer_column</strong>' statt 'customer_id'.<br>";
                echo "Die overview.php muss angepasst werden!<br><br>";
                echo "<strong>SQL Query sollte sein:</strong><br>";
                echo "<code>SELECT COUNT(*) FROM customer_freebies WHERE $customer_column = ?</code>";
                echo "</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler: " . $e->getMessage() . "</div>";
        }
        ?>

        <p style="margin-top: 30px;">
            <a href="/customer/dashboard.php?page=overview" class="btn">← Zurück zum Dashboard</a>
        </p>
    </div>
</body>
</html>
