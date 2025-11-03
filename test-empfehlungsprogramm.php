<?php
/**
 * Test Empfehlungsprogramm - Direkter Test
 * URL: https://app.mehr-infos-jetzt.de/test-empfehlungsprogramm.php
 */

session_start();

// Login simulieren
if (!isset($_SESSION['user_id'])) {
    if (isset($_POST['user_id'])) {
        $_SESSION['user_id'] = (int)$_POST['user_id'];
        $_SESSION['role'] = 'customer';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
        <style>
            body { font-family: sans-serif; padding: 2rem; background: #f0f0f0; }
            form { background: white; padding: 2rem; border-radius: 0.5rem; max-width: 400px; margin: 0 auto; }
            input { width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 2px solid #ddd; border-radius: 0.25rem; }
            button { width: 100%; padding: 0.75rem; background: #667eea; color: white; border: none; border-radius: 0.25rem; font-weight: bold; }
        </style>
    </head>
    <body>
        <form method="POST">
            <h2>Bitte User ID eingeben:</h2>
            <input type="number" name="user_id" placeholder="User ID (z.B. 1)" required autofocus>
            <button type="submit">Anmelden</button>
        </form>
    </body>
    </html>
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
    <title>Test - Empfehlungsprogramm</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #1a1a1a;
            color: #e0e0e0;
            padding: 2rem;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 { color: #667eea; }
        .box {
            background: #2a2a2a;
            border: 2px solid #667eea;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        pre {
            background: #0a0a0a;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            border-left: 3px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #444;
        }
        th {
            background: #333;
            color: #667eea;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 0.5rem;
            font-weight: 600;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test: Empfehlungsprogramm</h1>
        <p>Eingeloggt als User ID: <strong><?php echo $customer_id; ?></strong></p>
        
        <?php
        try {
            $pdo = getDBConnection();
            echo "<div class='box success'>✅ Datenbankverbindung OK</div>";
            
            // Test 1: User-Daten
            echo "<div class='box'>";
            echo "<h2>1️⃣ User-Daten</h2>";
            $stmt = $pdo->prepare("SELECT id, name, email, referral_enabled, ref_code FROM users WHERE id = ?");
            $stmt->execute([$customer_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "<div class='success'>✅ User gefunden</div>";
                echo "<table>";
                echo "<tr><th>Feld</th><th>Wert</th></tr>";
                foreach ($user as $key => $value) {
                    echo "<tr><td>$key</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ User nicht gefunden!</div>";
            }
            echo "</div>";
            
            // Test 2: Alle Freebies in DB
            echo "<div class='box'>";
            echo "<h2>2️⃣ Alle Freebies in der Datenbank</h2>";
            $stmt = $pdo->query("SELECT id, name, user_id, created_at FROM freebies ORDER BY id DESC");
            $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Gesamt: <strong>" . count($all_freebies) . "</strong> Freebies</p>";
            
            if (!empty($all_freebies)) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Name</th><th>User ID</th><th>Erstellt</th></tr>";
                foreach ($all_freebies as $f) {
                    $is_mine = ($f['user_id'] == $customer_id);
                    $style = $is_mine ? 'style="background: #1a4d2e;"' : '';
                    echo "<tr $style>";
                    echo "<td>{$f['id']}</td>";
                    echo "<td>" . htmlspecialchars($f['name']) . "</td>";
                    echo "<td>" . ($f['user_id'] ?: '<span class="error">NULL</span>') . ($is_mine ? ' <span class="success">← DEINE</span>' : '') . "</td>";
                    echo "<td>{$f['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='warning'>⚠️ Keine Freebies in der Datenbank!</div>";
            }
            echo "</div>";
            
            // Test 3: Freebies-Query wie in empfehlungsprogramm.php
            echo "<div class='box'>";
            echo "<h2>3️⃣ Freebies-Query (wie in empfehlungsprogramm.php)</h2>";
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT
                    f.id,
                    f.name as title,
                    f.description,
                    f.mockup_image_url as image_path,
                    CASE 
                        WHEN f.user_id = ? THEN 'own'
                        ELSE 'unlocked'
                    END as freebie_type
                FROM freebies f
                LEFT JOIN customer_freebies cf ON f.id = cf.freebie_id AND cf.customer_id = ?
                WHERE (
                    f.user_id = ?  -- Eigene Freebies
                    OR cf.is_unlocked = 1  -- Freigeschaltete Freebies
                )
                ORDER BY f.user_id = ? DESC, f.created_at DESC
            ");
            $stmt->execute([$customer_id, $customer_id, $customer_id, $customer_id]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Gefunden: <strong>" . count($freebies) . "</strong> Freebies</p>";
            
            if (!empty($freebies)) {
                echo "<div class='success'>✅ Query erfolgreich! Diese Freebies sollten auf der Seite erscheinen:</div>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Titel</th><th>Typ</th><th>Beschreibung</th></tr>";
                foreach ($freebies as $f) {
                    echo "<tr>";
                    echo "<td>{$f['id']}</td>";
                    echo "<td>" . htmlspecialchars($f['title']) . "</td>";
                    echo "<td>{$f['freebie_type']}</td>";
                    echo "<td>" . htmlspecialchars(substr($f['description'] ?? '', 0, 50)) . "...</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<div class='error'>❌ Keine Freebies gefunden!</div>";
                echo "<p class='warning'>Gründe:</p>";
                echo "<ul>";
                echo "<li>Kein Freebie hat user_id = $customer_id</li>";
                echo "<li>Keine Freebies wurden freigeschaltet (customer_freebies.is_unlocked = 1)</li>";
                echo "</ul>";
            }
            
            echo "<h3>Query zum Debuggen:</h3>";
            echo "<pre>" . htmlspecialchars("
SELECT DISTINCT
    f.id,
    f.name as title,
    f.description,
    f.mockup_image_url as image_path,
    CASE 
        WHEN f.user_id = $customer_id THEN 'own'
        ELSE 'unlocked'
    END as freebie_type
FROM freebies f
LEFT JOIN customer_freebies cf ON f.id = cf.freebie_id AND cf.customer_id = $customer_id
WHERE (
    f.user_id = $customer_id  -- Eigene Freebies
    OR cf.is_unlocked = 1  -- Freigeschaltete Freebies
)
ORDER BY f.user_id = $customer_id DESC, f.created_at DESC
") . "</pre>";
            
            echo "</div>";
            
            // Test 4: customer_freebies Tabelle
            echo "<div class='box'>";
            echo "<h2>4️⃣ Customer Freebies (Freischaltungen)</h2>";
            
            try {
                $stmt = $pdo->prepare("
                    SELECT cf.*, f.name as freebie_name
                    FROM customer_freebies cf
                    LEFT JOIN freebies f ON cf.freebie_id = f.id
                    WHERE cf.customer_id = ?
                ");
                $stmt->execute([$customer_id]);
                $unlocked = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p>Freigeschaltet: <strong>" . count($unlocked) . "</strong></p>";
                
                if (!empty($unlocked)) {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Freebie ID</th><th>Name</th><th>Freigeschaltet?</th></tr>";
                    foreach ($unlocked as $u) {
                        echo "<tr>";
                        echo "<td>{$u['id']}</td>";
                        echo "<td>{$u['freebie_id']}</td>";
                        echo "<td>" . htmlspecialchars($u['freebie_name']) . "</td>";
                        echo "<td>" . ($u['is_unlocked'] ? '<span class="success">✅ Ja</span>' : '<span class="error">❌ Nein</span>') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<div class='warning'>⚠️ Keine freigeschalteten Freebies</div>";
                }
            } catch (PDOException $e) {
                echo "<div class='error'>❌ Tabelle existiert nicht oder Fehler: " . $e->getMessage() . "</div>";
            }
            
            echo "</div>";
            
            // Lösung
            echo "<div class='box'>";
            echo "<h2>🎯 Lösung</h2>";
            
            if (empty($freebies)) {
                echo "<div class='warning'>";
                echo "<h3>⚠️ Keine Freebies sichtbar</h3>";
                echo "<p><strong>Sofortlösung:</strong></p>";
                echo "<ol>";
                echo "<li>Verwende das Quick-Fix Tool um Freebies zuzuweisen</li>";
                echo "<li>Oder erstelle ein neues Freebie auf der Freebies-Seite</li>";
                echo "</ol>";
                echo "</div>";
            } else {
                echo "<div class='success'>";
                echo "<h3>✅ Freebies gefunden!</h3>";
                echo "<p>Diese sollten auf der Empfehlungsprogramm-Seite sichtbar sein.</p>";
                echo "<p><strong>Wenn Sie sie nicht sehen:</strong></p>";
                echo "<ul>";
                echo "<li>Browser-Cache leeren (Strg + Shift + R oder Strg + F5)</li>";
                echo "<li>Inkognito-Fenster testen</li>";
                echo "</ul>";
                echo "</div>";
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div class='box error'>";
            echo "<h2>❌ Datenbankfehler</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <!-- Navigation -->
        <div style="margin-top: 2rem;">
            <a href="/customer/dashboard.php?page=empfehlungsprogramm" class="btn">
                → Zum Empfehlungsprogramm
            </a>
            <a href="/fix-freebies-quick.php" class="btn">
                🔧 Quick Fix Tool
            </a>
            <a href="?logout=1" class="btn" style="background: #ef4444;">
                Logout
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['logout'])): unset($_SESSION['user_id']); header('Location: ' . $_SERVER['PHP_SELF']); endif; ?>
</body>
</html>