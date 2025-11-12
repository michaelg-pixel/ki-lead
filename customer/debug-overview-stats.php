<?php
/**
 * DEBUG SCRIPT - Overview Statistics
 * Zeigt alle relevanten Datenbank-Queries und deren Ergebnisse
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('❌ Nicht eingeloggt!');
}

require_once __DIR__ . '/../config/database.php';

$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Unbekannt';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Overview Stats</title>
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
        .success {
            color: #00ff00;
        }
        .error {
            color: #ff0000;
        }
        .warning {
            color: #ffaa00;
        }
        .info {
            color: #00aaff;
        }
        h2 {
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
            margin-top: 0;
        }
        pre {
            background: #000;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
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
    </style>
</head>
<body>
    <h1>🔍 DEBUG - Overview Statistics</h1>
    
    <div class="section">
        <h2>Session Info</h2>
        <p><strong>User ID:</strong> <?php echo $customer_id; ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($customer_name); ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'nicht gesetzt'; ?></p>
    </div>

    <div class="section">
        <h2>1️⃣ Freigeschaltete Freebies</h2>
        <?php
        try {
            echo "<p class='info'>Query:</p>";
            echo "<pre>SELECT COUNT(*) 
FROM customer_freebies cf
INNER JOIN freebies f ON cf.freebie_id = f.id
WHERE cf.customer_id = $customer_id</pre>";
            
            $stmt_freebies = $pdo->prepare("
                SELECT COUNT(*) 
                FROM customer_freebies cf
                INNER JOIN freebies f ON cf.freebie_id = f.id
                WHERE cf.customer_id = ?
            ");
            $stmt_freebies->execute([$customer_id]);
            $freebies_unlocked = $stmt_freebies->fetchColumn();
            
            echo "<p class='success'>✅ Erfolgreich!</p>";
            echo "<p><strong>Ergebnis:</strong> $freebies_unlocked Freebies</p>";
            
            // Details anzeigen
            $stmt_details = $pdo->prepare("
                SELECT f.id, f.title, cf.unlocked_at
                FROM customer_freebies cf
                INNER JOIN freebies f ON cf.freebie_id = f.id
                WHERE cf.customer_id = ?
                ORDER BY cf.unlocked_at DESC
            ");
            $stmt_details->execute([$customer_id]);
            $freebie_details = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($freebie_details)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Freigeschaltet am</th></tr>";
                foreach ($freebie_details as $freebie) {
                    echo "<tr>";
                    echo "<td>" . $freebie['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($freebie['title']) . "</td>";
                    echo "<td>" . $freebie['unlocked_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ Keine Freebies gefunden!</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>2️⃣ Videokurse</h2>
        <?php
        try {
            echo "<p class='info'>Query:</p>";
            echo "<pre>SELECT COUNT(*) FROM course_access WHERE user_id = $customer_id</pre>";
            
            $stmt_courses = $pdo->prepare("
                SELECT COUNT(*) FROM course_access 
                WHERE user_id = ?
            ");
            $stmt_courses->execute([$customer_id]);
            $courses_count = $stmt_courses->fetchColumn();
            
            echo "<p class='success'>✅ Erfolgreich!</p>";
            echo "<p><strong>Ergebnis:</strong> $courses_count Kurse</p>";
            
            // Details anzeigen
            $stmt_course_details = $pdo->prepare("
                SELECT c.id, c.title, ca.granted_at
                FROM course_access ca
                INNER JOIN courses c ON ca.course_id = c.id
                WHERE ca.user_id = ?
                ORDER BY ca.granted_at DESC
            ");
            $stmt_course_details->execute([$customer_id]);
            $course_details = $stmt_course_details->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($course_details)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Zugriff seit</th></tr>";
                foreach ($course_details as $course) {
                    echo "<tr>";
                    echo "<td>" . $course['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($course['title']) . "</td>";
                    echo "<td>" . $course['granted_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ Keine Kurse gefunden!</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3️⃣ Klicks (freebie_click_analytics)</h2>
        <?php
        try {
            echo "<p class='info'>Query:</p>";
            echo "<pre>SELECT COALESCE(SUM(click_count), 0) 
FROM freebie_click_analytics 
WHERE customer_id = $customer_id
AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)</pre>";
            
            $stmt_clicks = $pdo->prepare("
                SELECT COALESCE(SUM(click_count), 0) 
                FROM freebie_click_analytics 
                WHERE customer_id = ?
                AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $stmt_clicks->execute([$customer_id]);
            $total_clicks = $stmt_clicks->fetchColumn();
            
            echo "<p class='success'>✅ Erfolgreich!</p>";
            echo "<p><strong>Ergebnis:</strong> $total_clicks Klicks (letzte 30 Tage)</p>";
            
            // Details anzeigen
            $stmt_click_details = $pdo->prepare("
                SELECT click_date, freebie_id, click_count
                FROM freebie_click_analytics 
                WHERE customer_id = ?
                AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY click_date DESC
                LIMIT 10
            ");
            $stmt_click_details->execute([$customer_id]);
            $click_details = $stmt_click_details->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($click_details)) {
                echo "<table>";
                echo "<tr><th>Datum</th><th>Freebie ID</th><th>Klicks</th></tr>";
                foreach ($click_details as $click) {
                    echo "<tr>";
                    echo "<td>" . $click['click_date'] . "</td>";
                    echo "<td>" . $click['freebie_id'] . "</td>";
                    echo "<td>" . $click['click_count'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='warning'>⚠️ Keine Klick-Daten gefunden!</p>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler (versuche Fallback): " . $e->getMessage() . "</p>";
            
            // Fallback: customer_tracking
            try {
                echo "<p class='info'>Fallback Query:</p>";
                echo "<pre>SELECT COUNT(*) FROM customer_tracking 
WHERE user_id = $customer_id 
AND type = 'click'
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)</pre>";
                
                $stmt_clicks_fallback = $pdo->prepare("
                    SELECT COUNT(*) FROM customer_tracking 
                    WHERE user_id = ? 
                    AND type = 'click'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $stmt_clicks_fallback->execute([$customer_id]);
                $total_clicks = $stmt_clicks_fallback->fetchColumn();
                
                echo "<p class='success'>✅ Fallback erfolgreich!</p>";
                echo "<p><strong>Ergebnis:</strong> $total_clicks Klicks</p>";
            } catch (PDOException $e2) {
                echo "<p class='error'>❌ Auch Fallback fehlgeschlagen: " . $e2->getMessage() . "</p>";
            }
        }
        ?>
    </div>

    <div class="section">
        <h2>4️⃣ Seitenaufrufe (customer_tracking)</h2>
        <?php
        try {
            echo "<p class='info'>Query:</p>";
            echo "<pre>SELECT COUNT(*) FROM customer_tracking 
WHERE user_id = $customer_id 
AND type = 'page_view'
AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)</pre>";
            
            $stmt_page_views = $pdo->prepare("
                SELECT COUNT(*) FROM customer_tracking 
                WHERE user_id = ? 
                AND type = 'page_view'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt_page_views->execute([$customer_id]);
            $total_page_views = $stmt_page_views->fetchColumn();
            
            echo "<p class='success'>✅ Erfolgreich!</p>";
            echo "<p><strong>Ergebnis:</strong> $total_page_views Seitenaufrufe</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5️⃣ Tabellen-Existenz prüfen</h2>
        <?php
        $tables_to_check = [
            'customer_freebies',
            'freebies',
            'course_access',
            'courses',
            'freebie_click_analytics',
            'customer_tracking'
        ];
        
        echo "<table>";
        echo "<tr><th>Tabelle</th><th>Status</th><th>Anzahl Einträge</th></tr>";
        
        foreach ($tables_to_check as $table) {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                
                if ($exists) {
                    $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                    $count = $count_stmt->fetchColumn();
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td class='success'>✅ Existiert</td>";
                    echo "<td>$count Einträge</td>";
                    echo "</tr>";
                } else {
                    echo "<tr>";
                    echo "<td>$table</td>";
                    echo "<td class='error'>❌ Nicht gefunden</td>";
                    echo "<td>-</td>";
                    echo "</tr>";
                }
            } catch (PDOException $e) {
                echo "<tr>";
                echo "<td>$table</td>";
                echo "<td class='error'>❌ Fehler</td>";
                echo "<td>" . $e->getMessage() . "</td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        ?>
    </div>

    <div class="section">
        <h2>6️⃣ Zusammenfassung</h2>
        <?php
        $summary = [];
        
        // Freebies
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies cf INNER JOIN freebies f ON cf.freebie_id = f.id WHERE cf.customer_id = ?");
            $stmt->execute([$customer_id]);
            $summary['freebies'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $summary['freebies'] = 'ERROR: ' . $e->getMessage();
        }
        
        // Courses
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            $summary['courses'] = $stmt->fetchColumn();
        } catch (Exception $e) {
            $summary['courses'] = 'ERROR: ' . $e->getMessage();
        }
        
        echo "<pre>";
        print_r($summary);
        echo "</pre>";
        ?>
        
        <p><a href="dashboard.php" style="color: #00aaff;">← Zurück zum Dashboard</a></p>
    </div>
</body>
</html>