<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

// Customer ID setzen
$customer_id = $_SESSION['user_id'] ?? 1; // Fallback zu ID 1 für Debug

echo "<h2>🔍 Freebies Debug</h2>";
echo "<p><strong>Customer ID:</strong> $customer_id</p>";

// 1. Prüfen ob Freebies-Tabelle existiert
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
    $tableExists = $stmt->fetch();
    echo "<p>✅ Freebies Tabelle existiert: " . ($tableExists ? "JA" : "NEIN") . "</p>";
} catch (PDOException $e) {
    echo "<p>❌ Fehler beim Prüfen der Tabelle: " . $e->getMessage() . "</p>";
}

// 2. Anzahl Freebies Templates zählen
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
    $count = $stmt->fetchColumn();
    echo "<p><strong>Anzahl Freebie Templates:</strong> $count</p>";
} catch (PDOException $e) {
    echo "<p>❌ Fehler: " . $e->getMessage() . "</p>";
}

// 3. Freebies Templates auflisten
try {
    $stmt = $pdo->query("SELECT id, name, headline, layout, created_at FROM freebies ORDER BY created_at DESC LIMIT 10");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Freebie Templates:</h3>";
    if (count($templates) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Headline</th><th>Layout</th><th>Erstellt</th></tr>";
        foreach ($templates as $t) {
            echo "<tr>";
            echo "<td>{$t['id']}</td>";
            echo "<td>{$t['name']}</td>";
            echo "<td>{$t['headline']}</td>";
            echo "<td>{$t['layout']}</td>";
            echo "<td>{$t['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ Keine Freebie Templates gefunden!</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Fehler: " . $e->getMessage() . "</p>";
}

// 4. Customer Freebies prüfen
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $customerCount = $stmt->fetchColumn();
    echo "<p><strong>Anzahl Customer Freebies für User $customer_id:</strong> $customerCount</p>";
} catch (PDOException $e) {
    echo "<p>❌ Fehler: " . $e->getMessage() . "</p>";
}

// 5. Customer Freebies auflisten
try {
    $stmt = $pdo->prepare("SELECT id, headline, freebie_type, template_id, created_at FROM customer_freebies WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$customer_id]);
    $customerFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📋 Customer Freebies:</h3>";
    if (count($customerFreebies) > 0) {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Headline</th><th>Type</th><th>Template ID</th><th>Erstellt</th></tr>";
        foreach ($customerFreebies as $cf) {
            echo "<tr>";
            echo "<td>{$cf['id']}</td>";
            echo "<td>" . ($cf['headline'] ?? 'N/A') . "</td>";
            echo "<td>" . ($cf['freebie_type'] ?? 'N/A') . "</td>";
            echo "<td>" . ($cf['template_id'] ?? 'N/A') . "</td>";
            echo "<td>{$cf['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ Keine Customer Freebies gefunden!</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Fehler: " . $e->getMessage() . "</p>";
}

// 6. Limits prüfen
try {
    $stmt = $pdo->prepare("SELECT freebie_limit, product_name FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $limits = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($limits) {
        echo "<p><strong>Freebie Limit:</strong> {$limits['freebie_limit']}</p>";
        echo "<p><strong>Produkt:</strong> {$limits['product_name']}</p>";
    } else {
        echo "<p>⚠️ Keine Limits für diesen Kunden gefunden!</p>";
    }
} catch (PDOException $e) {
    echo "<p>❌ Fehler: " . $e->getMessage() . "</p>";
}
?>
