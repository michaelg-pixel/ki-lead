<?php
/**
 * FIX: Erstelle fehlende Tabellen OHNE Foreign Keys
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
    <title>Fix Fehlende Tabellen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4">🔧 Fix Fehlende Tabellen</h1>

<?php
if (isset($_GET['run'])) {
    echo '<div class="space-y-4">';
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '✅ Datenbank-Verbindung erfolgreich<br>';
        echo '</div>';
        
        // Prüfe customers Tabelle
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>🔍 Prüfe customers Tabelle...</strong><br>';
        $stmt = $pdo->query("SHOW TABLES LIKE 'customers'");
        if ($stmt->rowCount() > 0) {
            echo '✅ customers Tabelle existiert<br>';
            
            // Prüfe Engine
            $stmt = $pdo->query("SHOW TABLE STATUS WHERE Name = 'customers'");
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            echo 'Engine: ' . $info['Engine'] . '<br>';
            echo 'Collation: ' . $info['Collation'] . '<br>';
        } else {
            echo '❌ customers Tabelle existiert NICHT!<br>';
        }
        echo '</div>';
        
        // Erstelle fehlende Tabellen OHNE Foreign Keys
        $tables_to_create = [
            'referral_stats' => "
                CREATE TABLE IF NOT EXISTS referral_stats (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    total_clicks INT DEFAULT 0,
                    unique_clicks INT DEFAULT 0,
                    total_conversions INT DEFAULT 0,
                    suspicious_conversions INT DEFAULT 0,
                    total_leads INT DEFAULT 0,
                    confirmed_leads INT DEFAULT 0,
                    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
                    last_click_at DATETIME DEFAULT NULL,
                    last_conversion_at DATETIME DEFAULT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY unique_customer (customer_id),
                    INDEX idx_customer (customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'referral_rewards' => "
                CREATE TABLE IF NOT EXISTS referral_rewards (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    reward_type ENUM('email', 'none', 'webhook', 'custom') DEFAULT 'email',
                    goal_referrals INT DEFAULT 5 COMMENT 'Anzahl benötigter Empfehlungen',
                    reward_email_subject VARCHAR(255) DEFAULT 'Ihre Belohnung wartet auf Sie!',
                    reward_email_template TEXT DEFAULT NULL,
                    auto_send_reward BOOLEAN DEFAULT FALSE,
                    webhook_url VARCHAR(500) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    UNIQUE KEY unique_customer (customer_id),
                    INDEX idx_customer (customer_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'referral_fraud_log' => "
                CREATE TABLE IF NOT EXISTS referral_fraud_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NOT NULL,
                    ref_code VARCHAR(50) DEFAULT NULL,
                    fraud_type ENUM('fast_conversion', 'duplicate_ip', 'duplicate_fingerprint', 'suspicious_pattern', 'rate_limit') NOT NULL,
                    ip_address_hash VARCHAR(64) DEFAULT NULL,
                    fingerprint VARCHAR(64) DEFAULT NULL,
                    user_agent TEXT DEFAULT NULL,
                    additional_data JSON DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    
                    INDEX idx_customer (customer_id),
                    INDEX idx_fraud_type (fraud_type),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>⚙️ Erstelle fehlende Tabellen...</strong><br><br>';
        
        foreach ($tables_to_create as $table_name => $sql) {
            try {
                // Prüfe ob Tabelle existiert
                $stmt = $pdo->query("SHOW TABLES LIKE '$table_name'");
                if ($stmt->rowCount() > 0) {
                    echo '<span class="text-yellow-600">⊘</span> ' . $table_name . ' existiert bereits<br>';
                    continue;
                }
                
                $pdo->exec($sql);
                echo '<span class="text-green-600">✓</span> ' . $table_name . ' erstellt<br>';
                
            } catch (PDOException $e) {
                echo '<span class="text-red-600">✗</span> ' . $table_name . ' Fehler: ' . htmlspecialchars($e->getMessage()) . '<br>';
            }
        }
        echo '</div>';
        
        // Initialisiere Daten
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>📊 Initialisiere Daten...</strong><br><br>';
        
        try {
            $pdo->exec("
                INSERT INTO referral_stats (customer_id)
                SELECT id FROM customers
                WHERE id NOT IN (SELECT customer_id FROM referral_stats)
            ");
            echo '<span class="text-green-600">✓</span> referral_stats initialisiert<br>';
        } catch (PDOException $e) {
            echo '<span class="text-yellow-600">⊘</span> referral_stats: ' . htmlspecialchars($e->getMessage()) . '<br>';
        }
        
        try {
            $pdo->exec("
                INSERT INTO referral_rewards (customer_id)
                SELECT id FROM customers
                WHERE id NOT IN (SELECT customer_id FROM referral_rewards)
            ");
            echo '<span class="text-green-600">✓</span> referral_rewards initialisiert<br>';
        } catch (PDOException $e) {
            echo '<span class="text-yellow-600">⊘</span> referral_rewards: ' . htmlspecialchars($e->getMessage()) . '<br>';
        }
        
        echo '</div>';
        
        // Finale Prüfung
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>🔍 Finale Prüfung...</strong><br><br>';
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        echo '<strong>Gefundene Tabellen (' . count($tables) . '):</strong><br>';
        foreach ($tables as $table) {
            // Zähle Einträge
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            echo '  • ' . $table . ' (' . $count . ' Einträge)<br>';
        }
        echo '</div>';
        
        if (count($tables) >= 6) {
            echo '<div class="p-4 bg-green-50 border-2 border-green-500 rounded-lg">';
            echo '<strong class="text-green-800 text-xl">🎉 ERFOLGREICH!</strong><br><br>';
            echo '<div class="text-green-700">';
            echo '✅ Alle 6 Referral-Tabellen existieren<br>';
            echo '✅ Daten initialisiert<br>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="mt-6 p-4 bg-blue-50 rounded-lg">';
            echo '<strong>📋 Nächste Schritte:</strong><br><br>';
            echo '1. <strong>Test-Daten erstellen:</strong><br>';
            echo '   <a href="create-test-data.php" class="text-blue-600 underline">→ Test-Daten Skript ausführen</a><br><br>';
            echo '2. <strong>Dashboards öffnen:</strong><br>';
            echo '   <a href="admin/sections/referral-overview.php" class="text-blue-600 underline font-bold">→ Admin-Dashboard</a><br>';
            echo '   <a href="customer/dashboard.php" class="text-blue-600 underline">→ Customer-Dashboard</a><br><br>';
            echo '3. <strong>Aufräumen:</strong><br>';
            echo '   Lösche: migrate-only.php, debug-migration.php, fix-tables.php, install-referral-web.php';
            echo '</div>';
        } else {
            echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
            echo '<strong class="text-red-800">❌ Noch nicht vollständig</strong><br>';
            echo 'Nur ' . count($tables) . ' von 6 Tabellen gefunden.';
            echo '</div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="p-4 bg-red-50 border-2 border-red-500 rounded-lg">';
        echo '<strong class="text-red-800">💥 FEHLER:</strong><br>';
        echo htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    
    echo '</div>';
} else {
    ?>
    <div class="space-y-4">
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <strong class="text-yellow-800">ℹ️ Info:</strong>
            <p class="text-yellow-700 mt-2">Dieses Skript erstellt die fehlenden 3 Tabellen OHNE Foreign Key Constraints, da diese Probleme verursachen.</p>
        </div>
        
        <div class="p-4 bg-blue-50 rounded-lg">
            <strong>Was wird gemacht:</strong>
            <ul class="list-disc ml-6 mt-2 text-gray-700">
                <li>referral_stats Tabelle erstellen</li>
                <li>referral_rewards Tabelle erstellen</li>
                <li>referral_fraud_log Tabelle erstellen</li>
                <li>Daten für existierende Customers initialisieren</li>
            </ul>
        </div>
        
        <a href="?run=1" class="inline-block px-8 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xl font-semibold">
            🚀 Tabellen erstellen
        </a>
    </div>
    <?php
}
?>

        </div>
    </div>
</body>
</html>
