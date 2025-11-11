<?php
/**
 * Freebies Diagnose - Warum werden keine Freebies angezeigt?
 */

session_start();
require_once __DIR__ . '/../config/database.php';

// Check login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    die("❌ Nicht eingeloggt als Customer");
}

$pdo = getDBConnection();
$customer_id = $_SESSION['user_id'];

echo "<h2>🔍 Freebies Diagnose für Customer ID: $customer_id</h2>";
echo "<hr><br>";

// 1. Admin Templates prüfen
echo "<h3>📚 Admin Templates (freebies Tabelle)</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
    $count = $stmt->fetchColumn();
    echo "Anzahl Templates vom Admin: <strong>$count</strong><br><br>";
    
    if ($count > 0) {
        echo "✅ Templates vorhanden!<br><br>";
        
        // Details anzeigen
        $stmt = $pdo->query("
            SELECT id, name, headline, layout, created_at, niche 
            FROM freebies 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Headline</th><th>Layout</th><th>Nische</th><th>Erstellt</th></tr>";
        foreach ($templates as $t) {
            echo "<tr>";
            echo "<td>" . $t['id'] . "</td>";
            echo "<td>" . htmlspecialchars($t['name'] ?? 'Kein Name') . "</td>";
            echo "<td>" . htmlspecialchars($t['headline'] ?? 'Keine Headline') . "</td>";
            echo "<td>" . htmlspecialchars($t['layout'] ?? 'Standard') . "</td>";
            echo "<td>" . htmlspecialchars($t['niche'] ?? 'Keine') . "</td>";
            echo "<td>" . $t['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "⚠️ <strong>KEINE Templates vorhanden!</strong><br>";
        echo "→ Der Admin muss erst Templates erstellen<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "<br><br>";
}

// 2. Customer Freebies prüfen (Template-basiert)
echo "<hr><h3>🎨 Vom Kunden angepasste Templates</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customer_freebies 
        WHERE customer_id = ? AND (freebie_type = 'template' OR freebie_type IS NULL)
    ");
    $stmt->execute([$customer_id]);
    $count = $stmt->fetchColumn();
    
    echo "Anzahl angepasste Templates: <strong>$count</strong><br><br>";
    
    if ($count > 0) {
        echo "✅ Customer hat Templates angepasst!<br><br>";
        
        $stmt = $pdo->prepare("
            SELECT cf.id, cf.template_id, cf.headline, cf.unique_id, cf.created_at,
                   f.name as template_name
            FROM customer_freebies cf
            LEFT JOIN freebies f ON cf.template_id = f.id
            WHERE cf.customer_id = ? AND (cf.freebie_type = 'template' OR cf.freebie_type IS NULL)
            ORDER BY cf.created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Template</th><th>Headline</th><th>Unique ID</th><th>Erstellt</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['template_name'] ?? 'Template ' . $item['template_id']) . "</td>";
            echo "<td>" . htmlspecialchars($item['headline'] ?? 'Keine') . "</td>";
            echo "<td>" . htmlspecialchars($item['unique_id'] ?? 'Keine') . "</td>";
            echo "<td>" . $item['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "ℹ️ Customer hat noch keine Templates angepasst<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "<br><br>";
}

// 3. Eigene Custom Freebies prüfen
echo "<hr><h3>✨ Eigene Custom Freebies</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM customer_freebies 
        WHERE customer_id = ? AND freebie_type = 'custom'
    ");
    $stmt->execute([$customer_id]);
    $count = $stmt->fetchColumn();
    
    echo "Anzahl eigene Freebies: <strong>$count</strong><br><br>";
    
    if ($count > 0) {
        echo "✅ Customer hat eigene Freebies erstellt!<br><br>";
        
        $stmt = $pdo->prepare("
            SELECT id, headline, subheadline, unique_id, layout, created_at
            FROM customer_freebies
            WHERE customer_id = ? AND freebie_type = 'custom'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$customer_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Headline</th><th>Subheadline</th><th>Layout</th><th>Unique ID</th><th>Erstellt</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['headline'] ?? 'Keine') . "</td>";
            echo "<td>" . htmlspecialchars($item['subheadline'] ?? 'Keine') . "</td>";
            echo "<td>" . htmlspecialchars($item['layout'] ?? 'Standard') . "</td>";
            echo "<td>" . htmlspecialchars($item['unique_id'] ?? 'Keine') . "</td>";
            echo "<td>" . $item['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    } else {
        echo "ℹ️ Customer hat noch keine eigenen Freebies erstellt<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "<br><br>";
}

// 4. Freebie Limit prüfen
echo "<hr><h3>📊 Freebie Limit</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT freebie_limit, product_name 
        FROM customer_freebie_limits 
        WHERE customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $limitData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($limitData) {
        echo "Paket: <strong>" . htmlspecialchars($limitData['product_name']) . "</strong><br>";
        echo "Limit: <strong>" . $limitData['freebie_limit'] . "</strong> eigene Freebies erlaubt<br><br>";
        
        // Aktuelle Nutzung
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM customer_freebies 
            WHERE customer_id = ? AND freebie_type = 'custom'
        ");
        $stmt->execute([$customer_id]);
        $used = $stmt->fetchColumn();
        
        echo "Verwendet: <strong>$used</strong> / <strong>" . $limitData['freebie_limit'] . "</strong><br>";
        echo "Verfügbar: <strong>" . ($limitData['freebie_limit'] - $used) . "</strong><br><br>";
        
        if ($used >= $limitData['freebie_limit']) {
            echo "⚠️ <strong>Limit erreicht!</strong> - Kunde kann keine weiteren Freebies erstellen<br><br>";
        } else {
            echo "✅ Kunde kann noch " . ($limitData['freebie_limit'] - $used) . " Freebie(s) erstellen<br><br>";
        }
    } else {
        echo "⚠️ <strong>Kein Limit gefunden!</strong> - Bitte Limit in customer_freebie_limits eintragen<br><br>";
    }
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "<br><br>";
}

// 5. Zusammenfassung
echo "<hr><h3>📋 Zusammenfassung</h3>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
    $adminTemplates = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
    $stmt->execute([$customer_id]);
    $customFreebies = $stmt->fetchColumn();
    
    if ($adminTemplates == 0) {
        echo "❌ <strong>Problem:</strong> Keine Admin-Templates vorhanden!<br>";
        echo "→ <strong>Lösung:</strong> Admin muss Templates erstellen unter <a href='/admin/dashboard.php?page=freebies'>Admin → Freebies</a><br><br>";
    } else {
        echo "✅ Admin-Templates vorhanden: $adminTemplates<br>";
    }
    
    if ($customFreebies == 0) {
        echo "ℹ️ Kunde hat noch keine eigenen Freebies erstellt<br>";
        echo "→ Kunde kann unter <a href='/customer/dashboard.php?page=freebies'>Freebies</a> welche erstellen<br><br>";
    } else {
        echo "✅ Eigene Freebies vorhanden: $customFreebies<br><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "<br><br>";
}

echo "<hr><br>";
echo "<a href='/customer/dashboard.php?page=freebies'>→ Zurück zu Freebies</a> | ";
echo "<a href='/admin/dashboard.php?page=freebies'>→ Admin Freebies</a>";
?>