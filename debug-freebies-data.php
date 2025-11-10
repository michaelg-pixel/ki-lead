<?php
// Debug Datenbank Freebies
// Aufruf: https://app.mehr-infos-jetzt.de/debug-freebies-data.php

session_start();

echo "<h2>🔍 Freebies Datenbank Debug</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getDBConnection();
    
    // Customer ID
    $customer_id = $_SESSION['user_id'] ?? 0;
    echo "<h3>1. Session Info</h3>";
    echo "Customer ID: <strong>" . $customer_id . "</strong><br>";
    echo "Eingeloggt: " . (isset($_SESSION['user_id']) ? '✅ JA' : '❌ NEIN') . "<br>";
    
    if ($customer_id > 0) {
        // Alle Freebies für diesen User
        echo "<h3>2. Alle Freebies für User $customer_id</h3>";
        $stmt = $pdo->prepare("SELECT id, headline, freebie_type, created_at FROM customer_freebies WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customer_id]);
        $all_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($all_freebies)) {
            echo "❌ <strong>Keine Freebies gefunden!</strong><br>";
            echo "Du musst zuerst ein Freebie erstellen unter <a href='/customer/dashboard.php?page=freebies'>Freebies</a><br>";
        } else {
            echo "✅ Gesamt: " . count($all_freebies) . " Freebies<br><br>";
            
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Headline</th><th>Type</th><th>Erstellt</th></tr>";
            foreach ($all_freebies as $f) {
                echo "<tr>";
                echo "<td>" . $f['id'] . "</td>";
                echo "<td>" . htmlspecialchars($f['headline']) . "</td>";
                echo "<td><strong>" . ($f['freebie_type'] ?? 'NULL') . "</strong></td>";
                echo "<td>" . $f['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Custom Freebies
            echo "<h3>3. Custom Freebies (für Marktplatz)</h3>";
            $stmt = $pdo->prepare("SELECT id, headline FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
            $stmt->execute([$customer_id]);
            $custom_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($custom_freebies)) {
                echo "❌ <strong>Keine Custom Freebies gefunden!</strong><br>";
                echo "<br><strong>Problem identifiziert:</strong><br>";
                echo "Deine Freebies haben nicht den Type 'custom'.<br>";
                echo "Nur Freebies mit Type 'custom' können auf dem Marktplatz angeboten werden.<br>";
                echo "<br>";
                echo "<strong>Lösung:</strong><br>";
                echo "Erstelle ein neues Freebie unter <a href='/customer/dashboard.php?page=freebies'>Freebies erstellen</a><br>";
            } else {
                echo "✅ " . count($custom_freebies) . " Custom Freebies gefunden:<br>";
                foreach ($custom_freebies as $f) {
                    echo "- " . htmlspecialchars($f['headline']) . " (ID: " . $f['id'] . ")<br>";
                }
            }
        }
        
        // Type Statistik
        echo "<h3>4. Freebie Type Statistik</h3>";
        $stmt = $pdo->prepare("
            SELECT 
                freebie_type,
                COUNT(*) as count
            FROM customer_freebies 
            WHERE customer_id = ?
            GROUP BY freebie_type
        ");
        $stmt->execute([$customer_id]);
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($stats)) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
            echo "<tr><th>Type</th><th>Anzahl</th></tr>";
            foreach ($stats as $s) {
                echo "<tr>";
                echo "<td><strong>" . ($s['freebie_type'] ?? 'NULL') . "</strong></td>";
                echo "<td>" . $s['count'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<h3>❌ Nicht eingeloggt!</h3>";
        echo "Bitte zuerst einloggen: <a href='/customer/login.php'>Login</a>";
    }
    
} catch (Exception $e) {
    echo "❌ Fehler: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>📝 Zusammenfassung:</h3>";
echo "<p>Der Marktplatz zeigt <strong>NUR Freebies mit Type 'custom'</strong>.<br>";
echo "Template-Freebies werden NICHT angezeigt, da diese nicht verkauft werden können.</p>";
?>
