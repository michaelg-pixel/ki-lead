<?php
/**
 * TRACKING STATUS CHECKER
 * Prüft ob Tracking aktiv ist und zeigt aktuelle Werte an
 * SAFE: Macht keine DB-Änderungen, nur Lesezugriff
 */

session_start();

// Admin-Check (optional - entfernen falls nicht gewünscht)
if (!isset($_SESSION['user_id'])) {
    die('Bitte einloggen: <a href="/public/login.php">Login</a>');
}

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<html><head>";
    echo "<title>Tracking Status Check</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #fff; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #667eea; margin-bottom: 30px; }
        h2 { color: #764ba2; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #16213e; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #667eea; color: white; }
        .status-ok { color: #22c55e; font-weight: bold; }
        .status-warning { color: #f59e0b; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        .info-box { background: #16213e; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
        .alert { background: #fef3c7; color: #92400e; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d1fae5; color: #065f46; }
        code { background: #0f0f1e; padding: 2px 6px; border-radius: 4px; color: #fbbf24; }
    </style>";
    echo "</head><body>";
    echo "<div class='container'>";
    
    echo "<h1>🔍 Tracking Status Check</h1>";
    echo "<p><strong>Zeitpunkt:</strong> " . date('d.m.Y H:i:s') . "</p>";
    
    // ===== 1. ALLE KUNDEN MIT IHREN STATS =====
    echo "<h2>📊 Kunden-Übersicht (Alle Kunden)</h2>";
    
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name,
            c.email,
            -- Freebies (nur existierende)
            (SELECT COUNT(*) 
             FROM customer_freebies cf 
             INNER JOIN freebies f ON cf.freebie_id = f.id 
             WHERE cf.customer_id = c.id) as aktive_freebies,
            -- Kurse
            (SELECT COUNT(*) 
             FROM course_access ca 
             WHERE ca.user_id = c.id) as videokurse,
            -- Tracking-Events (30 Tage)
            (SELECT COUNT(*) 
             FROM customer_tracking ct 
             WHERE ct.user_id = c.id 
             AND ct.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as tracking_events,
            -- Seitenaufrufe (30 Tage)
            (SELECT COUNT(*) 
             FROM customer_tracking ct 
             WHERE ct.user_id = c.id 
             AND ct.type = 'page_view'
             AND ct.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as page_views,
            -- Klicks (30 Tage)
            (SELECT COALESCE(SUM(click_count), 0)
             FROM freebie_click_analytics fca
             WHERE fca.customer_id = c.id
             AND fca.click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as freebie_clicks,
            -- Letzter Login
            (SELECT MAX(created_at) 
             FROM customer_tracking ct 
             WHERE ct.user_id = c.id 
             AND ct.type = 'page_view') as letzter_login
        FROM customers c
        ORDER BY c.id
    ");
    
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($customers)) {
        echo "<div class='alert'>⚠️ Keine Kunden in der Datenbank gefunden!</div>";
    } else {
        echo "<table>";
        echo "<tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Aktive Freebies</th>
                <th>Videokurse</th>
                <th>Tracking Events</th>
                <th>Seitenaufrufe</th>
                <th>Freebie Klicks</th>
                <th>Letzter Login</th>
                <th>Status</th>
              </tr>";
        
        foreach ($customers as $customer) {
            $freebies_ok = $customer['aktive_freebies'] >= 1;
            $kurse_ok = $customer['videokurse'] > 0;
            $tracking_ok = $customer['tracking_events'] > 0;
            
            // Status bestimmen
            if ($freebies_ok && $kurse_ok && $tracking_ok) {
                $status = "<span class='status-ok'>✅ Alles OK</span>";
            } else {
                $warnings = [];
                if (!$freebies_ok) $warnings[] = "Keine Freebies";
                if (!$kurse_ok) $warnings[] = "Keine Kurse";
                if (!$tracking_ok) $warnings[] = "Kein Tracking";
                $status = "<span class='status-warning'>⚠️ " . implode(", ", $warnings) . "</span>";
            }
            
            $freebie_class = $freebies_ok ? 'status-ok' : 'status-error';
            $kurse_class = $kurse_ok ? 'status-ok' : 'status-error';
            $tracking_class = $tracking_ok ? 'status-ok' : 'status-warning';
            
            echo "<tr>";
            echo "<td>{$customer['id']}</td>";
            echo "<td>{$customer['name']}</td>";
            echo "<td>{$customer['email']}</td>";
            echo "<td class='$freebie_class'>{$customer['aktive_freebies']}</td>";
            echo "<td class='$kurse_class'>{$customer['videokurse']}</td>";
            echo "<td class='$tracking_class'>{$customer['tracking_events']}</td>";
            echo "<td>{$customer['page_views']}</td>";
            echo "<td>{$customer['freebie_clicks']}</td>";
            echo "<td>" . ($customer['letzter_login'] ? date('d.m.Y H:i', strtotime($customer['letzter_login'])) : 'Nie') . "</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // ===== 2. TRACKING SYSTEM CHECK =====
    echo "<h2>🎯 Tracking System Check</h2>";
    
    echo "<div class='info-box'>";
    
    // Tabelle existiert?
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_tracking'");
    $tracking_table_exists = $stmt->rowCount() > 0;
    
    if ($tracking_table_exists) {
        echo "<p class='status-ok'>✅ Tabelle <code>customer_tracking</code> existiert</p>";
        
        // Anzahl Tracking-Einträge
        $stmt = $pdo->query("SELECT COUNT(*) FROM customer_tracking");
        $total_tracking = $stmt->fetchColumn();
        echo "<p>📊 Gesamt Tracking-Einträge: <strong>$total_tracking</strong></p>";
        
        // Tracking nach Typ
        $stmt = $pdo->query("
            SELECT type, COUNT(*) as count 
            FROM customer_tracking 
            GROUP BY type
        ");
        $tracking_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($tracking_by_type)) {
            echo "<p><strong>Tracking nach Typ:</strong></p><ul>";
            foreach ($tracking_by_type as $type) {
                echo "<li>{$type['type']}: {$type['count']}</li>";
            }
            echo "</ul>";
        }
        
        // Letzte 5 Tracking-Events
        $stmt = $pdo->query("
            SELECT ct.*, c.name 
            FROM customer_tracking ct
            LEFT JOIN customers c ON ct.user_id = c.id
            ORDER BY ct.created_at DESC 
            LIMIT 5
        ");
        $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recent_events)) {
            echo "<p><strong>Letzte 5 Tracking-Events:</strong></p>";
            echo "<table style='width: 100%; font-size: 12px;'>";
            echo "<tr><th>Kunde</th><th>Typ</th><th>Seite</th><th>Zeitpunkt</th></tr>";
            foreach ($recent_events as $event) {
                echo "<tr>";
                echo "<td>{$event['name']}</td>";
                echo "<td>{$event['type']}</td>";
                echo "<td>{$event['page']}</td>";
                echo "<td>" . date('d.m.Y H:i:s', strtotime($event['created_at'])) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='status-warning'>⚠️ Noch keine Tracking-Events vorhanden</p>";
        }
        
    } else {
        echo "<p class='status-error'>❌ Tabelle <code>customer_tracking</code> existiert NICHT</p>";
        echo "<p>➡️ Migration ausführen: <code>database/migrations/002_customer_tracking.sql</code></p>";
    }
    
    echo "</div>";
    
    // ===== 3. FREEBIE CHECK =====
    echo "<h2>🎁 Freebie System Check</h2>";
    
    echo "<div class='info-box'>";
    
    // Freebies Tabelle
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
    $total_freebies = $stmt->fetchColumn();
    echo "<p>📦 Gesamt Freebies im System: <strong>$total_freebies</strong></p>";
    
    // Customer Freebies
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM customer_freebies cf
        INNER JOIN freebies f ON cf.freebie_id = f.id
    ");
    $total_customer_freebies = $stmt->fetchColumn();
    echo "<p>✅ Aktive Kunden-Freebies (existieren noch): <strong>$total_customer_freebies</strong></p>";
    
    // Freebie Klicks
    $stmt = $pdo->query("SELECT COALESCE(SUM(click_count), 0) FROM freebie_click_analytics");
    $total_clicks = $stmt->fetchColumn();
    echo "<p>🖱️ Gesamt Freebie-Klicks: <strong>$total_clicks</strong></p>";
    
    echo "</div>";
    
    // ===== 4. KURS CHECK =====
    echo "<h2>🎓 Videokurs System Check</h2>";
    
    echo "<div class='info-box'>";
    
    // Kurse Tabelle
    $stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1");
    $total_courses = $stmt->fetchColumn();
    echo "<p>📚 Aktive Kurse im System: <strong>$total_courses</strong></p>";
    
    // Kurs-Zugriffe
    $stmt = $pdo->query("SELECT COUNT(*) FROM course_access");
    $total_course_access = $stmt->fetchColumn();
    echo "<p>🎯 Gesamt Kurs-Zugriffe: <strong>$total_course_access</strong></p>";
    
    echo "</div>";
    
    // ===== 5. EMPFEHLUNGEN =====
    echo "<h2>💡 Empfehlungen</h2>";
    
    echo "<div class='info-box'>";
    
    $issues = [];
    
    // Check ob Kunden Freebies haben
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM customers c 
        WHERE NOT EXISTS (
            SELECT 1 
            FROM customer_freebies cf 
            INNER JOIN freebies f ON cf.freebie_id = f.id
            WHERE cf.customer_id = c.id
        )
    ");
    $customers_without_freebies = $stmt->fetchColumn();
    
    if ($customers_without_freebies > 0) {
        $issues[] = "⚠️ <strong>$customers_without_freebies</strong> Kunden haben keine Freebies zugewiesen";
    }
    
    // Check ob Kunden Kurse haben
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM customers c 
        WHERE NOT EXISTS (
            SELECT 1 
            FROM course_access ca 
            WHERE ca.user_id = c.id
        )
    ");
    $customers_without_courses = $stmt->fetchColumn();
    
    if ($customers_without_courses > 0) {
        $issues[] = "⚠️ <strong>$customers_without_courses</strong> Kunden haben keine Kurse zugewiesen";
    }
    
    // Check ob Tracking läuft
    if ($total_tracking == 0) {
        $issues[] = "⚠️ <strong>Kein Tracking aktiv!</strong> Kunden sollten Dashboard besuchen";
    }
    
    if (empty($issues)) {
        echo "<div class='alert success'><strong>✅ Alles sieht gut aus!</strong><br>";
        echo "• Tracking läuft<br>";
        echo "• Freebies sind zugewiesen<br>";
        echo "• Kurse sind zugewiesen</div>";
    } else {
        echo "<div class='alert'>";
        foreach ($issues as $issue) {
            echo "<p>$issue</p>";
        }
        echo "</div>";
        
        echo "<p><strong>Nächste Schritte:</strong></p>";
        echo "<ul>";
        if ($customers_without_freebies > 0) {
            echo "<li>Freebies zuweisen über: <code>/admin/dashboard.php</code> → Kunden verwalten</li>";
        }
        if ($customers_without_courses > 0) {
            echo "<li>Kurse zuweisen über: <code>/admin/dashboard.php</code> → Kurse verwalten</li>";
        }
        if ($total_tracking == 0) {
            echo "<li>Kunden sollten einmal das Dashboard besuchen, damit Tracking startet</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    
    // ===== 6. SYSTEM INFO =====
    echo "<h2>ℹ️ System Info</h2>";
    echo "<div class='info-box'>";
    echo "<p><strong>Datenbank:</strong> " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
    echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
    echo "<p><strong>Server Time:</strong> " . date('d.m.Y H:i:s') . "</p>";
    echo "</div>";
    
    echo "</div>"; // container
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<html><body style='background: #1a1a2e; color: #fff; font-family: Arial; padding: 20px;'>";
    echo "<h1 style='color: #ef4444;'>❌ Datenbankfehler</h1>";
    echo "<div style='background: #16213e; padding: 20px; border-radius: 10px; border-left: 4px solid #ef4444;'>";
    echo "<p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
    echo "</body></html>";
}
?>