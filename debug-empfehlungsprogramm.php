<?php
/**
 * Debug-Tool für Empfehlungsprogramm
 * URL: https://app.mehr-infos-jetzt.de/debug-empfehlungsprogramm.php
 */

session_start();

// Login simulieren falls nicht angemeldet
if (!isset($_SESSION['user_id'])) {
    echo "<h1>Bitte User-ID eingeben zum Testen:</h1>";
    if (isset($_POST['user_id'])) {
        $_SESSION['user_id'] = (int)$_POST['user_id'];
        $_SESSION['role'] = 'customer';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
    <form method="POST">
        <input type="number" name="user_id" placeholder="User ID" required>
        <button type="submit">Login simulieren</button>
    </form>
    <?php
    exit;
}

require_once __DIR__ . '/config/database.php';

$customer_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Empfehlungsprogramm</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 2rem;
            line-height: 1.6;
        }
        h1, h2 { color: #00ffff; }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .warning { color: #ffaa00; }
        .box {
            background: #2a2a2a;
            border: 1px solid #00ff00;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0.5rem;
        }
        pre {
            background: #0a0a0a;
            padding: 1rem;
            overflow-x: auto;
            border-left: 3px solid #00ffff;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 1rem 0;
        }
        th, td {
            border: 1px solid #00ff00;
            padding: 0.5rem;
            text-align: left;
        }
        th {
            background: #2a2a2a;
            color: #00ffff;
        }
    </style>
</head>
<body>
    <h1>🔍 Debug: Empfehlungsprogramm</h1>
    <p>User ID: <strong><?php echo $customer_id; ?></strong></p>
    <hr>

    <?php
    try {
        $pdo = getDBConnection();
        echo "<div class='box success'>✅ Datenbankverbindung: OK</div>";
        
        // 1. User-Daten prüfen
        echo "<h2>1️⃣ User-Daten</h2>";
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$customer_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<div class='box success'>✅ User gefunden</div>";
            echo "<pre>" . print_r($user, true) . "</pre>";
        } else {
            echo "<div class='box error'>❌ User nicht gefunden!</div>";
        }
        
        // 2. Tabellen prüfen
        echo "<h2>2️⃣ Tabellen-Check</h2>";
        
        $tables = ['freebies', 'customer_freebies', 'reward_definitions'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<div class='box success'>✅ Tabelle '$table' existiert</div>";
                
                // Zeilen zählen
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                $count = $stmt->fetch()['count'];
                echo "<p>→ Anzahl Einträge: <strong>$count</strong></p>";
            } else {
                echo "<div class='box error'>❌ Tabelle '$table' existiert NICHT!</div>";
            }
        }
        
        // 3. Freebies abfragen - ORIGINAL QUERY
        echo "<h2>3️⃣ Freebies-Abfrage (Original Query)</h2>";
        
        try {
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    f.id,
                    f.title,
                    f.description,
                    f.image_path,
                    f.is_active,
                    CASE 
                        WHEN f.customer_id = ? THEN 'own'
                        ELSE 'unlocked'
                    END as freebie_type
                FROM freebies f
                LEFT JOIN customer_freebies cf ON f.id = cf.freebie_id AND cf.customer_id = ?
                WHERE f.is_active = 1
                AND (
                    f.customer_id = ?  -- Eigene Freebies
                    OR cf.is_unlocked = 1  -- Freigeschaltete Freebies
                )
                ORDER BY f.customer_id = ? DESC, f.created_at DESC
            ");
            $stmt->execute([$customer_id, $customer_id, $customer_id, $customer_id]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<div class='box success'>✅ Query erfolgreich</div>";
            echo "<p>Gefundene Freebies: <strong>" . count($freebies) . "</strong></p>";
            
            if (!empty($freebies)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Typ</th><th>Aktiv</th></tr>";
                foreach ($freebies as $f) {
                    echo "<tr>";
                    echo "<td>{$f['id']}</td>";
                    echo "<td>" . htmlspecialchars($f['title']) . "</td>";
                    echo "<td>{$f['freebie_type']}</td>";
                    echo "<td>{$f['is_active']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='box warning'>⚠️ Keine Freebies gefunden!</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='box error'>❌ Query Fehler: " . $e->getMessage() . "</div>";
        }
        
        // 4. Alternative Abfrage - Nur eigene Freebies
        echo "<h2>4️⃣ Alternative: Nur eigene Freebies</h2>";
        
        try {
            $stmt = $pdo->prepare("
                SELECT * FROM freebies 
                WHERE customer_id = ? 
                AND is_active = 1
                ORDER BY created_at DESC
            ");
            $stmt->execute([$customer_id]);
            $own_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Eigene Freebies: <strong>" . count($own_freebies) . "</strong></p>";
            
            if (!empty($own_freebies)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Erstellt</th></tr>";
                foreach ($own_freebies as $f) {
                    echo "<tr>";
                    echo "<td>{$f['id']}</td>";
                    echo "<td>" . htmlspecialchars($f['title']) . "</td>";
                    echo "<td>{$f['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='box error'>❌ Query Fehler: " . $e->getMessage() . "</div>";
        }
        
        // 5. Alle Freebies (Debug)
        echo "<h2>5️⃣ ALLE Freebies in der Datenbank</h2>";
        
        try {
            $stmt = $pdo->query("SELECT * FROM freebies ORDER BY created_at DESC");
            $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Gesamt in DB: <strong>" . count($all_freebies) . "</strong></p>";
            
            if (!empty($all_freebies)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Customer ID</th><th>Aktiv</th></tr>";
                foreach ($all_freebies as $f) {
                    $highlight = ($f['customer_id'] == $customer_id) ? 'style="background: #004400;"' : '';
                    echo "<tr $highlight>";
                    echo "<td>{$f['id']}</td>";
                    echo "<td>" . htmlspecialchars($f['title']) . "</td>";
                    echo "<td>{$f['customer_id']}</td>";
                    echo "<td>{$f['is_active']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<p class='warning'>⚠️ Grün markiert = Gehört diesem User</p>";
            } else {
                echo "<div class='box warning'>⚠️ KEINE Freebies in der Datenbank!</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='box error'>❌ Query Fehler: " . $e->getMessage() . "</div>";
        }
        
        // 6. Customer Freebies Tabelle prüfen
        echo "<h2>6️⃣ Customer Freebies (Freischaltungen)</h2>";
        
        try {
            $stmt = $pdo->prepare("
                SELECT cf.*, f.title 
                FROM customer_freebies cf
                LEFT JOIN freebies f ON cf.freebie_id = f.id
                WHERE cf.customer_id = ?
            ");
            $stmt->execute([$customer_id]);
            $unlocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Freigeschaltete Freebies: <strong>" . count($unlocked) . "</strong></p>";
            
            if (!empty($unlocked)) {
                echo "<table>";
                echo "<tr><th>Freebie ID</th><th>Titel</th><th>Freigeschaltet</th></tr>";
                foreach ($unlocked as $u) {
                    echo "<tr>";
                    echo "<td>{$u['freebie_id']}</td>";
                    echo "<td>" . htmlspecialchars($u['title']) . "</td>";
                    echo "<td>" . ($u['is_unlocked'] ? '✅' : '❌') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='box warning'>⚠️ Keine freigeschalteten Freebies</div>";
            }
            
        } catch (PDOException $e) {
            echo "<div class='box error'>❌ Tabelle customer_freebies existiert nicht oder Fehler: " . $e->getMessage() . "</div>";
        }
        
        // 7. Empfehlung
        echo "<h2>🎯 Diagnose & Lösung</h2>";
        
        if (empty($freebies) && empty($all_freebies)) {
            echo "<div class='box error'>";
            echo "<h3>❌ Problem: Keine Freebies in der Datenbank!</h3>";
            echo "<p><strong>Lösung:</strong></p>";
            echo "<ul>";
            echo "<li>1. Gehen Sie zu: <a href='/customer/dashboard.php?page=freebies' style='color: #00ffff;'>Freebies-Seite</a></li>";
            echo "<li>2. Erstellen Sie mindestens ein Freebie</li>";
            echo "<li>3. Kommen Sie zurück zum Empfehlungsprogramm</li>";
            echo "</ul>";
            echo "</div>";
        } elseif (empty($freebies) && !empty($all_freebies)) {
            echo "<div class='box warning'>";
            echo "<h3>⚠️ Problem: Freebies existieren, aber nicht für diesen User!</h3>";
            echo "<p><strong>Mögliche Ursachen:</strong></p>";
            echo "<ul>";
            echo "<li>Alle Freebies gehören anderen Users (customer_id stimmt nicht)</li>";
            echo "<li>Freebies sind inaktiv (is_active = 0)</li>";
            echo "<li>Keine Freebies wurden freigeschaltet</li>";
            echo "</ul>";
            echo "<p><strong>Lösung:</strong></p>";
            echo "<ul>";
            echo "<li>Erstellen Sie eigene Freebies auf der Freebies-Seite</li>";
            echo "<li>ODER: Admin muss Freebies für Sie freischalten</li>";
            echo "</ul>";
            echo "</div>";
        } else {
            echo "<div class='box success'>";
            echo "<h3>✅ Alles OK! Freebies sollten sichtbar sein.</h3>";
            echo "<p>Falls Sie sie im Frontend nicht sehen:</p>";
            echo "<ul>";
            echo "<li>1. Browser-Cache leeren (Strg + F5)</li>";
            echo "<li>2. Seite neu laden</li>";
            echo "<li>3. Prüfen Sie dass Sie auf der richtigen Seite sind: <a href='/customer/dashboard.php?page=empfehlungsprogramm' style='color: #00ffff;'>Empfehlungsprogramm</a></li>";
            echo "</ul>";
            echo "</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='box error'>❌ Datenbankfehler: " . $e->getMessage() . "</div>";
    }
    ?>
    
    <hr>
    <p><a href="/customer/dashboard.php?page=empfehlungsprogramm" style="color: #00ffff;">→ Zum Empfehlungsprogramm</a></p>
    <p><a href="/customer/dashboard.php?page=freebies" style="color: #00ffff;">→ Zu den Freebies</a></p>
    <p><a href="?logout=1" style="color: #ff0000;">Logout</a></p>
    
    <?php if (isset($_GET['logout'])): unset($_SESSION['user_id']); header('Location: ' . $_SERVER['PHP_SELF']); endif; ?>
</body>
</html>