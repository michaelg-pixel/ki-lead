<?php
/**
 * DIAGNOSE: Was ist in der Datenbank?
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'lumisaas');
define('DB_USER', 'lumisaas52');
define('DB_PASS', 'I1zx1XdL1hrWd75yu57e');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Datenbank Diagnose</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4">🔍 Datenbank Diagnose</h1>

<?php
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo '<div class="space-y-4">';
    
    // Aktuelle Datenbank
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>📊 Aktuelle Datenbank:</strong><br>';
    $stmt = $pdo->query("SELECT DATABASE()");
    $current_db = $stmt->fetch(PDO::FETCH_NUM)[0];
    echo 'Name: <strong>' . $current_db . '</strong><br>';
    echo '</div>';
    
    // Alle Tabellen
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>📋 Alle Tabellen in dieser Datenbank:</strong><br><br>';
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    if (count($tables) > 0) {
        echo '<div class="grid grid-cols-3 gap-2">';
        foreach ($tables as $table) {
            // Zähle Einträge
            try {
                $stmt_count = $pdo->query("SELECT COUNT(*) as cnt FROM `{$table[0]}`");
                $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['cnt'];
                echo '<div class="bg-white p-2 rounded text-sm">';
                echo '<strong>' . $table[0] . '</strong><br>';
                echo '<span class="text-gray-600">' . $count . ' Einträge</span>';
                echo '</div>';
            } catch (Exception $e) {
                echo '<div class="bg-white p-2 rounded text-sm">';
                echo '<strong>' . $table[0] . '</strong><br>';
                echo '<span class="text-red-600">Fehler</span>';
                echo '</div>';
            }
        }
        echo '</div>';
        echo '<br><strong>Gesamt: ' . count($tables) . ' Tabellen</strong>';
    } else {
        echo '<span class="text-red-600">❌ Keine Tabellen gefunden!</span>';
    }
    echo '</div>';
    
    // Suche nach customers-ähnlichen Tabellen
    echo '<div class="p-4 bg-yellow-50 rounded-lg">';
    echo '<strong>🔍 Suche nach "customer" Tabellen:</strong><br><br>';
    $stmt = $pdo->query("SHOW TABLES LIKE '%customer%'");
    $customer_tables = $stmt->fetchAll(PDO::FETCH_NUM);
    
    if (count($customer_tables) > 0) {
        foreach ($customer_tables as $table) {
            echo '✅ Gefunden: <strong>' . $table[0] . '</strong><br>';
            
            // Zeige Struktur
            try {
                $stmt_desc = $pdo->query("DESCRIBE `{$table[0]}`");
                $columns = $stmt_desc->fetchAll(PDO::FETCH_ASSOC);
                echo '<div class="ml-4 mt-2 text-xs bg-white p-2 rounded">';
                echo '<strong>Spalten:</strong><br>';
                foreach ($columns as $col) {
                    echo '• ' . $col['Field'] . ' (' . $col['Type'] . ')<br>';
                }
                echo '</div>';
            } catch (Exception $e) {
                echo '<span class="text-red-600">Fehler beim Auslesen der Struktur</span><br>';
            }
        }
    } else {
        echo '<span class="text-red-600">❌ Keine "customer" Tabellen gefunden!</span><br><br>';
        echo '<strong>Mögliche Ursachen:</strong><br>';
        echo '1. Falsche Datenbank ausgewählt<br>';
        echo '2. Anwendung wurde noch nie installiert<br>';
        echo '3. Tabellen wurden gelöscht<br>';
    }
    echo '</div>';
    
    // Referral Tabellen Status
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>🎯 Referral-Tabellen Status:</strong><br><br>';
    $referral_tables = [
        'referral_clicks',
        'referral_conversions',
        'referral_leads',
        'referral_stats',
        'referral_rewards',
        'referral_fraud_log'
    ];
    
    foreach ($referral_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $stmt_count = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $count = $stmt_count->fetch(PDO::FETCH_ASSOC)['cnt'];
            echo '<span class="text-green-600">✓</span> ' . $table . ' (' . $count . ' Einträge)<br>';
        } else {
            echo '<span class="text-red-600">✗</span> ' . $table . ' <em class="text-gray-500">(fehlt)</em><br>';
        }
    }
    echo '</div>';
    
    // Verfügbare Datenbanken
    echo '<div class="p-4 bg-blue-50 rounded-lg">';
    echo '<strong>🗄️ Verfügbare Datenbanken:</strong><br><br>';
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_NUM);
    
    foreach ($databases as $db) {
        $is_current = ($db[0] === $current_db);
        if ($is_current) {
            echo '<strong class="text-blue-600">→ ' . $db[0] . ' (aktuell)</strong><br>';
        } else {
            echo '  • ' . $db[0] . '<br>';
        }
    }
    echo '</div>';
    
    // Empfehlung
    echo '<div class="p-4 bg-purple-50 border-2 border-purple-500 rounded-lg">';
    echo '<strong class="text-purple-800 text-xl">💡 Nächste Schritte:</strong><br><br>';
    
    $has_customers = false;
    foreach ($tables as $table) {
        if (strtolower($table[0]) === 'customers') {
            $has_customers = true;
            break;
        }
    }
    
    if (!$has_customers) {
        echo '<div class="text-purple-700">';
        echo '<strong>Die customers Tabelle fehlt komplett!</strong><br><br>';
        echo '<strong>Option 1:</strong> Falsche Datenbank?<br>';
        echo '→ Prüfe ob eine andere Datenbank die richtige ist<br><br>';
        echo '<strong>Option 2:</strong> Komplett-Installation nötig?<br>';
        echo '→ Möglicherweise muss die GESAMTE Anwendung erst installiert werden<br><br>';
        echo '<strong>Option 3:</strong> Backup wiederherstellen?<br>';
        echo '→ Falls Tabellen gelöscht wurden<br>';
        echo '</div>';
    } else {
        echo '<div class="text-purple-700">';
        echo '✅ customers Tabelle gefunden!<br>';
        echo '→ Du kannst mit der Referral-Installation fortfahren';
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
    echo '<strong class="text-red-800">💥 FEHLER:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
}
?>

        </div>
    </div>
</body>
</html>
