<?php
/**
 * Test-Datei für Customer API
 * Zum Debuggen der API-Endpunkte
 */

session_start();

echo "<h1>Customer API Debug Test</h1>";
echo "<style>body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; padding: 20px; } 
      .success { color: #22c55e; } .error { color: #ef4444; } 
      pre { background: #0a0a1e; padding: 15px; border-radius: 8px; border: 1px solid #a855f7; }
      h2 { color: #c084fc; }</style>";

echo "<h2>1. Session Check</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Name: " . ($_SESSION['name'] ?? 'NOT SET') . "\n";
echo "</pre>";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<p class='error'>❌ Nicht als Admin eingeloggt!</p>";
    echo "<p>Bitte erst im Admin-Dashboard einloggen: <a href='/admin/dashboard.php' style='color: #a855f7;'>Zum Dashboard</a></p>";
    exit;
}

echo "<p class='success'>✅ Admin-Session aktiv</p>";

echo "<h2>2. Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Datenbank-Verbindung erfolgreich</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Datenbank-Fehler: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>3. Users Table Structure</h2>";
try {
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    echo "Vorhandene Spalten:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Available Tables</h2>";
try {
    $tables = ['user_freebies', 'freebies', 'user_activity_log', 'freebie_analytics'];
    echo "<pre>";
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
        $status = count($result) > 0 ? '✅' : '❌';
        echo "$status $table\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Sample Customer Query</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "<p class='success'>✅ Kunde gefunden (ID: {$customer['id']})</p>";
        echo "<pre>" . print_r($customer, true) . "</pre>";
        
        echo "<h2>6. Test API Call</h2>";
        echo "<p>Test mit User ID: {$customer['id']}</p>";
        echo "<p><a href='/api/customer-get.php?user_id={$customer['id']}' style='color: #a855f7;' target='_blank'>API-Endpunkt testen</a></p>";
    } else {
        echo "<p class='error'>❌ Keine Kunden in der Datenbank gefunden</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
}

echo "<hr style='border-color: #a855f7; margin: 30px 0;'>";
echo "<p><a href='/admin/dashboard.php?page=users' style='color: #a855f7;'>← Zurück zur Kundenverwaltung</a></p>";
