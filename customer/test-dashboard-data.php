<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die('Bitte einloggen');
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];
$customer_name = $_SESSION['name'] ?? 'Unbekannt';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Test</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #fff; padding: 20px; }
        .box { background: #16213e; padding: 20px; margin: 10px 0; border-radius: 10px; }
        h2 { color: #667eea; }
        .success { color: #28a745; font-size: 24px; font-weight: bold; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <h1>🔍 Dashboard Daten Test</h1>
    
    <div class="box">
        <h2>Eingeloggt als:</h2>
        <p>Name: <strong><?php echo htmlspecialchars($customer_name); ?></strong></p>
        <p>Customer ID (user_id): <strong><?php echo $customer_id; ?></strong></p>
    </div>
    
    <div class="box">
        <h2>🎁 Freebies Zählung:</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            $count = $stmt->fetchColumn();
            
            echo "<p class='success'>✅ Du hast $count Freebies!</p>";
            
            // Details anzeigen
            $stmt = $pdo->prepare("SELECT id, title FROM customer_freebies WHERE user_id = ? LIMIT 10");
            $stmt->execute([$customer_id]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($freebies)) {
                echo "<p><strong>Deine Freebies:</strong></p><ul>";
                foreach ($freebies as $f) {
                    echo "<li>ID: {$f['id']} - " . htmlspecialchars($f['title']) . "</li>";
                }
                echo "</ul>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>🎓 Kurse Zählung:</h2>
        <?php
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE user_id = ?");
            $stmt->execute([$customer_id]);
            $count = $stmt->fetchColumn();
            
            echo "<p class='success'>✅ Du hast Zugriff auf $count Kurse!</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>📊 SQL Query Test:</h2>
        <p>Teste die exakte Query aus overview.php:</p>
        <?php
        try {
            // EXAKTE Query aus overview.php
            $stmt_freebies = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE user_id = ?");
            $stmt_freebies->execute([$customer_id]);
            $freebies_unlocked = $stmt_freebies->fetchColumn();
            
            echo "<p class='success'>Ergebnis: $freebies_unlocked Freebies</p>";
            echo "<p>Query: <code>SELECT COUNT(*) FROM customer_freebies WHERE user_id = $customer_id</code></p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Query Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="box">
        <h2>🔧 Was sagt die Datenbank?</h2>
        <?php
        try {
            // Direkt alle Einträge prüfen
            $stmt = $pdo->query("SELECT user_id, COUNT(*) as count FROM customer_freebies GROUP BY user_id");
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Freebies pro User:</strong></p>";
            echo "<table style='color: white; width: 100%;'>";
            echo "<tr><th>User ID</th><th>Anzahl</th></tr>";
            foreach ($results as $row) {
                $highlight = $row['user_id'] == $customer_id ? 'style="background: #28a745;"' : '';
                echo "<tr $highlight><td>{$row['user_id']}</td><td>{$row['count']}</td></tr>";
            }
            echo "</table>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <p><a href="/customer/dashboard.php?page=overview" style="color: #667eea; font-size: 18px;">← Zurück zum Dashboard</a></p>
</body>
</html>
