<?php
/**
 * Test-Datei für Customer API - ERWEITERTE DEBUG-VERSION
 * Zeigt alle Fehler und Probleme an
 */

// Fehleranzeige aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Customer API Debug Test (Extended)</h1>";
echo "<style>body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; padding: 20px; } 
      .success { color: #22c55e; } .error { color: #ef4444; } .warning { color: #f59e0b; }
      pre { background: #0a0a1e; padding: 15px; border-radius: 8px; border: 1px solid #a855f7; overflow-x: auto; }
      h2 { color: #c084fc; margin-top: 30px; }
      .box { background: #0a0a1e; padding: 15px; border-radius: 8px; border: 1px solid #a855f7; margin: 10px 0; }
      </style>";

echo "<h2>1. Session Check</h2>";
echo "<div class='box'><pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Name: " . ($_SESSION['name'] ?? 'NOT SET') . "\n";
echo "</pre></div>";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<p class='error'>❌ Nicht als Admin eingeloggt!</p>";
    echo "<p>Bitte erst im Admin-Dashboard einloggen: <a href='/admin/dashboard.php' style='color: #a855f7;'>Zum Dashboard</a></p>";
    exit;
}

echo "<p class='success'>✅ Admin-Session aktiv</p>";

echo "<h2>2. PHP Configuration</h2>";
echo "<div class='box'><pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Error Reporting: " . error_reporting() . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "</pre></div>";

echo "<h2>3. File Check</h2>";
echo "<div class='box'><pre>";
$files = [
    '/config/database.php',
    '/includes/auth.php',
    '/api/customer-get.php',
    '/api/customer-update.php'
];
foreach ($files as $file) {
    $fullPath = __DIR__ . $file;
    $exists = file_exists($fullPath);
    $status = $exists ? '✅' : '❌';
    echo "$status $file\n";
}
echo "</pre></div>";

echo "<h2>4. Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p class='success'>✅ Database config geladen</p>";
    
    $pdo = Database::getInstance()->getConnection();
    echo "<p class='success'>✅ Datenbank-Verbindung erfolgreich</p>";
    
    // Test Query
    $result = $pdo->query("SELECT DATABASE() as db_name")->fetch();
    echo "<div class='box'><pre>Datenbank: " . $result['db_name'] . "</pre></div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Datenbank-Fehler:</p>";
    echo "<div class='box'><pre>" . htmlspecialchars($e->getMessage()) . "\n\n";
    echo "Stack Trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
    exit;
}

echo "<h2>5. Users Table Structure</h2>";
try {
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='box'><pre>Vorhandene Spalten:\n";
    foreach ($columns as $col) {
        echo sprintf("  %-20s %-20s %s\n", 
            $col['Field'], 
            $col['Type'],
            $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    echo "</pre></div>";
    
    // Prüfe wichtige Spalten
    $requiredColumns = ['id', 'name', 'email', 'password', 'role', 'is_active', 'created_at'];
    $optionalColumns = ['raw_code', 'company_name', 'company_email', 'referral_enabled', 'referral_code'];
    
    $existingColumns = array_column($columns, 'Field');
    
    echo "<h3>Spalten-Status:</h3>";
    echo "<div class='box'><pre>";
    echo "Erforderliche Spalten:\n";
    foreach ($requiredColumns as $col) {
        $status = in_array($col, $existingColumns) ? '✅' : '❌';
        echo "$status $col\n";
    }
    echo "\nOptionale Spalten:\n";
    foreach ($optionalColumns as $col) {
        $status = in_array($col, $existingColumns) ? '✅' : '⚠️';
        echo "$status $col\n";
    }
    echo "</pre></div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler beim Laden der Tabellenstruktur:</p>";
    echo "<div class='box'><pre>" . htmlspecialchars($e->getMessage()) . "</pre></div>";
}

echo "<h2>6. Available Tables</h2>";
try {
    $tables = ['user_freebies', 'freebies', 'user_activity_log', 'freebie_analytics'];
    echo "<div class='box'><pre>";
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
        $status = count($result) > 0 ? '✅' : '⚠️';
        echo "$status $table";
        if (count($result) === 0) {
            echo " (optional - wird nicht benötigt)";
        }
        echo "\n";
    }
    echo "</pre></div>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>7. Customer Count</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='box'><pre>Anzahl Kunden: " . $count['total'] . "</pre></div>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>8. Sample Customer Query</h2>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role, is_active, created_at FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($customer) {
        echo "<p class='success'>✅ Kunde gefunden (ID: {$customer['id']})</p>";
        echo "<div class='box'><pre>" . print_r($customer, true) . "</pre></div>";
        
        $testUserId = $customer['id'];
        
        echo "<h2>9. Direct API Test</h2>";
        echo "<p>Test mit User ID: $testUserId</p>";
        
        // Direkter Test der API
        $_GET['user_id'] = $testUserId;
        
        echo "<h3>a) Include API File Test:</h3>";
        echo "<div class='box'>";
        ob_start();
        try {
            include __DIR__ . '/api/customer-get.php';
            $apiOutput = ob_get_clean();
            
            // Versuche JSON zu parsen
            $jsonData = json_decode($apiOutput, true);
            if ($jsonData) {
                echo "<p class='success'>✅ API gibt gültiges JSON zurück</p>";
                echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='error'>❌ API gibt kein gültiges JSON zurück:</p>";
                echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";
            }
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p class='error'>❌ API-Fehler:</p>";
            echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
        echo "</div>";
        
        echo "<h3>b) Browser Test Links:</h3>";
        echo "<div class='box'>";
        echo "<p><a href='/api/customer-get.php?user_id=$testUserId' style='color: #a855f7;' target='_blank'>🔗 API-Endpunkt im Browser öffnen</a></p>";
        echo "<p><small>Sollte ein JSON-Objekt mit Kundendaten zurückgeben</small></p>";
        echo "</div>";
        
    } else {
        echo "<p class='warning'>⚠️ Keine Kunden in der Datenbank gefunden</p>";
        echo "<p>Bitte erst einen Testkunden anlegen im Dashboard</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Fehler beim Laden des Testkunden:</p>";
    echo "<div class='box'><pre>" . htmlspecialchars($e->getMessage()) . "</pre></div>";
}

echo "<hr style='border-color: #a855f7; margin: 30px 0;'>";
echo "<h2>10. Empfohlene Nächste Schritte</h2>";
echo "<div class='box'>";
echo "<ol style='line-height: 1.8;'>";
echo "<li>Prüfe alle ✅ Marks oben - alle wichtigen Checks sollten grün sein</li>";
echo "<li>Öffne den Browser Test Link in Abschnitt 9</li>";
echo "<li>Wenn API-Fehler erscheinen, sende mir den kompletten Output dieser Seite</li>";
echo "<li><a href='/admin/dashboard.php?page=users' style='color: #a855f7;'>← Zurück zur Kundenverwaltung</a></li>";
echo "</ol>";
echo "</div>";
