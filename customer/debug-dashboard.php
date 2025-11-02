<?php
/**
 * Database Debug Script
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
        // ===== TABELLEN PRÜFEN =====
        echo "<h2>📋 Vorhandene Tabellen</h2>";
        try {
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<div class='info-box'>";
            echo "<strong>Gefundene Tabellen (" . count($tables) . "):</strong><br>";
            foreach ($tables as $table) {
                $highlight = in_array($table, ['customer_freebies', 'customer_tracking', 'customers', 'courses', 'course_access']) ? 'success' : '';
                echo "<span class='$highlight'>• $table</span><br>";
            }
            echo "</div>";
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler: " . $e->getMessage() . "</div>";
        }

        // ===== CUSTOMER_FREEBIES PRÜFEN =====
        echo "<h2>🎁 Customer Freebies</h2>";
        try {
            // Struktur prüfen
            $stmt = $pdo->query("DESCRIBE customer_freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Tabellen-Struktur:</h3>";
            echo "<table>";
            echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Daten für aktuellen Kunden
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Deine Freebies (" . count($freebies) . "):</h3>";
            if (empty($freebies)) {
                echo "<div class='info-box warning'>⚠️ Keine Freebies gefunden für Customer ID: $customer_id</div>";
            } else {
                echo "<table>";
                echo "<tr><th>ID</th><th>Freebie ID</th><th>Clicks</th><th>Erstellt am</th></tr>";
                foreach ($freebies as $freebie) {
                    echo "<tr>";
                    echo "<td>{$freebie['id']}</td>";
                    echo "<td>{$freebie['freebie_id']}</td>";
                    echo "<td>" . ($freebie['clicks'] ?? 0) . "</td>";
                    echo "<td>" . ($freebie['created_at'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Alle Freebies in der Tabelle
            $stmt = $pdo->query("SELECT customer_id, COUNT(*) as count FROM customer_freebies GROUP BY customer_id");
            $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($all_freebies)) {
                echo "<h3>Alle Kunden mit Freebies:</h3>";
                echo "<table>";
                echo "<tr><th>Customer ID</th><th>Anzahl Freebies</th></tr>";
                foreach ($all_freebies as $row) {
                    $highlight = $row['customer_id'] == $customer_id ? 'success' : '';
                    echo "<tr class='$highlight'>";
                    echo "<td>{$row['customer_id']}</td>";
                    echo "<td>{$row['count']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler beim Abrufen von customer_freebies: " . $e->getMessage() . "</div>";
        }

        // ===== COURSES / COURSE_ACCESS =====
        echo "<h2>🎓 Kurse</h2>";
        try {
            // Kurszugriff prüfen
            $stmt = $pdo->prepare("
                SELECT c.id, c.title, ca.has_access 
                FROM courses c
                LEFT JOIN course_access ca ON c.id = ca.course_id AND ca.customer_id = ?
                WHERE c.is_active = 1
            ");
            $stmt->execute([$customer_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($courses)) {
                echo "<div class='info-box warning'>⚠️ Keine Kurse gefunden</div>";
            } else {
                echo "<table>";
                echo "<tr><th>Kurs ID</th><th>Titel</th><th>Zugriff</th></tr>";
                foreach ($courses as $course) {
                    $access = $course['has_access'] ? '<span class="success">✅ Ja</span>' : '<span class="error">❌ Nein</span>';
                    echo "<tr>";
                    echo "<td>{$course['id']}</td>";
                    echo "<td>" . htmlspecialchars($course['title']) . "</td>";
                    echo "<td>$access</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<div class='info-box error'>❌ Fehler beim Abrufen von Kursen: " . $e->getMessage() . "</div>";
        }

        // ===== TRACKING DATEN =====
        echo "<h2>📊 Tracking Daten</h2>";
        try {
            // Gesamte Tracking-Einträge
            $stmt = $pdo->prepare("
                SELECT type, COUNT(*) as count 
                FROM customer_tracking 
                WHERE customer_id = ?
                GROUP BY type
            ");
            $stmt->execute([$customer_id]);
            $tracking_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($tracking_stats)) {
                echo "<div class='info-box warning'>⚠️ Noch keine Tracking-Daten vorhanden</div>";
            } else {
                echo "<table>";
                echo "<tr><th>Typ</th><th>Anzahl</th></tr>";
                foreach ($tracking_stats as $stat) {
                    echo "<tr>";
                    echo "<td>{$stat['type']}</td>";
                    echo "<td>{$stat['count']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Letzte 10 Tracking-Events
            $stmt = $pdo->prepare("
                SELECT type, page, element, created_at 
                FROM customer_tracking 
                WHERE customer_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$customer_id]);
            $recent_tracking = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($recent_tracking)) {
                echo "<h3>Letzte 10 Aktivitäten:</h3>";
                echo "<table>";
                echo "<tr><th>Typ</th><th>Seite</th><th>Element</th><th>Zeit</th></tr>";
                foreach ($recent_tracking as $track) {
                    echo "<tr>";
                    echo "<td>{$track['type']}</td>";
                    echo "<td>" . ($track['page'] ?? '-') . "</td>";
                    echo "<td>" . ($track['element'] ?? '-') . "</td>";
                    echo "<td>" . date('d.m.Y H:i:s', strtotime($track['created_at'])) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<div class='info-box warning'>⚠️ customer_tracking Tabelle existiert noch nicht oder ist leer</div>";
        }

        // ===== SQL QUERIES ZUM TESTEN =====
        echo "<h2>🔧 Test-Queries</h2>";
        echo "<div class='info-box'>";
        echo "<p><strong>Wenn du 3 Freebies haben solltest, kannst du diese SQL-Queries manuell ausführen:</strong></p>";
        echo "<pre>";
        echo "-- Beispiel: 3 Test-Freebies für dich erstellen\n";
        echo "INSERT INTO customer_freebies (customer_id, freebie_id, clicks, created_at) VALUES\n";
        echo "($customer_id, 1, 0, NOW()),\n";
        echo "($customer_id, 2, 5, NOW()),\n";
        echo "($customer_id, 3, 12, NOW());\n\n";
        echo "-- Oder prüfe, ob Daten vorhanden sind:\n";
        echo "SELECT * FROM customer_freebies WHERE customer_id = $customer_id;";
        echo "</pre>";
        echo "</div>";

        // ===== ZUSAMMENFASSUNG =====
        echo "<h2>📊 Zusammenfassung</h2>";
        try {
            $stmt_freebies = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
            $stmt_freebies->execute([$customer_id]);
            $freebies_count = $stmt_freebies->fetchColumn();
            
            $stmt_courses = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE customer_id = ? AND has_access = 1");
            $stmt_courses->execute([$customer_id]);
            $courses_count = $stmt_courses->fetchColumn();
            
            $stmt_tracking = $pdo->prepare("SELECT COUNT(*) FROM customer_tracking WHERE customer_id = ?");
            $stmt_tracking->execute([$customer_id]);
            $tracking_count = $stmt_tracking->fetchColumn();
            
            echo "<div class='info-box'>";
            echo "<strong>🎁 Freigeschaltete Freebies:</strong> <span class='success'>$freebies_count</span><br>";
            echo "<strong>🎓 Kurse mit Zugriff:</strong> <span class='success'>$courses_count</span><br>";
            echo "<strong>📊 Tracking-Einträge:</strong> <span class='success'>$tracking_count</span><br>";
            echo "</div>";
            
            if ($freebies_count == 0) {
                echo "<div class='info-box warning'>";
                echo "<strong>⚠️ Problem identifiziert:</strong><br>";
                echo "Du hast noch keine Einträge in der <code>customer_freebies</code> Tabelle.<br>";
                echo "Die Freebies müssen erst freigeschaltet/erstellt werden.<br><br>";
                echo "<strong>Lösung:</strong> Gehe zu 'Freebies' im Dashboard und erstelle deine ersten Freebies!";
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
