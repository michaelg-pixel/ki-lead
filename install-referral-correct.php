<?php
/**
 * KORREKTES MIGRATIONS-SKRIPT
 * Arbeitet mit der USERS Tabelle (nicht customers)
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
    <title>Referral Migration (Korrigiert)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4">🔧 Referral Migration (Korrigiert für USERS)</h1>

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
        
        // 1. Erweitere USERS Tabelle
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>1️⃣ Erweitere users Tabelle...</strong><br><br>';
        
        $user_columns = [
            'referral_enabled' => "ALTER TABLE users ADD COLUMN referral_enabled BOOLEAN DEFAULT FALSE",
            'company_name' => "ALTER TABLE users ADD COLUMN company_name VARCHAR(255) DEFAULT NULL",
            'company_email' => "ALTER TABLE users ADD COLUMN company_email VARCHAR(255) DEFAULT NULL",
            'company_imprint_html' => "ALTER TABLE users ADD COLUMN company_imprint_html TEXT DEFAULT NULL",
            'referral_code' => "ALTER TABLE users ADD COLUMN referral_code VARCHAR(50) UNIQUE DEFAULT NULL"
        ];
        
        foreach ($user_columns as $col => $sql) {
            try {
                $pdo->exec($sql);
                echo '<span class="text-green-600">✓</span> ' . $col . ' hinzugefügt<br>';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo '<span class="text-yellow-600">⊘</span> ' . $col . ' bereits vorhanden<br>';
                } else {
                    echo '<span class="text-red-600">✗</span> ' . $col . ': ' . $e->getMessage() . '<br>';
                }
            }
        }
        
        // Generiere Referral-Codes
        try {
            $pdo->exec("
                UPDATE users 
                SET referral_code = CONCAT('REF', LPAD(id, 6, '0'), SUBSTRING(MD5(CONCAT(id, email, UNIX_TIMESTAMP())), 1, 6))
                WHERE referral_code IS NULL
            ");
            echo '<span class="text-green-600">✓</span> Referral-Codes generiert<br>';
        } catch (PDOException $e) {
            echo '<span class="text-yellow-600">⊘</span> Referral-Codes: ' . $e->getMessage() . '<br>';
        }
        
        echo '</div>';
        
        // 2. Erstelle Referral-Tabellen
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>2️⃣ Erstelle Referral-Tabellen...</strong><br><br>';
        
        $tables = [
            'referral_clicks' => "
                CREATE TABLE IF NOT EXISTS referral_clicks (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    ref_code VARCHAR(50) NOT NULL,
                    ip_address_hash VARCHAR(64) NOT NULL,
                    user_agent TEXT,
                    fingerprint VARCHAR(64) NOT NULL,
                    session_id VARCHAR(64) DEFAULT NULL,
                    referer TEXT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_ref_code (ref_code),
                    INDEX idx_fingerprint (fingerprint),
                    INDEX idx_created (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'referral_conversions' => "
                CREATE TABLE IF NOT EXISTS referral_conversions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    ref_code VARCHAR(50) NOT NULL,
                    ip_address_hash VARCHAR(64) NOT NULL,
                    user_agent TEXT,
                    fingerprint VARCHAR(64) NOT NULL,
                    source ENUM('thankyou', 'pixel', 'api') DEFAULT 'thankyou',
                    suspicious BOOLEAN DEFAULT FALSE,
                    time_to_convert INT DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user (user_id),
                    INDEX idx_ref_code (ref_code),
                    INDEX idx_suspicious (suspicious)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ",
            
            'referral_leads' => "
                CREATE TABLE IF NOT EXISTS referral_leads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    ref_code VARCHAR(50) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    email_hash VARCHAR(64) NOT NULL,
                    confirmed BOOLEAN DEFAULT FALSE,
                    reward_notified BOOLEAN DEFAULT FALSE,
                    confirmation_token VARCHAR(64) DEFAULT NULL,
                    ip_address_hash VARCHAR(64) DEFAULT NULL,
                    gdpr_consent BOOLEAN DEFAULT TRUE,
                    gdpr_consent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    confirmed_at DATETIME DEFAULT NULL,
                    notified_at DATETIME DEFAULT NULL,
                    INDEX idx_user (user_id),
                    INDEX idx_ref_code (ref_code),
                    INDEX idx_email_hash (email_hash),
                    UNIQUE KEY unique_user_email (user_id, email_hash)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
        ];
        
        foreach ($tables as $name => $sql) {
            try {
                $pdo->exec($sql);
                echo '<span class="text-green-600">✓</span> ' . $name . ' erstellt<br>';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo '<span class="text-yellow-600">⊘</span> ' . $name . ' bereits vorhanden<br>';
                } else {
                    echo '<span class="text-red-600">✗</span> ' . $name . ': ' . $e->getMessage() . '<br>';
                }
            }
        }
        
        echo '</div>';
        
        // 3. Update existierende Tabellen zu user_id
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>3️⃣ Update existierende Tabellen...</strong><br><br>';
        
        // Prüfe ob referral_stats customer_id hat und benenne um
        try {
            $stmt = $pdo->query("DESCRIBE referral_stats");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $has_customer_id = false;
            $has_user_id = false;
            
            foreach ($cols as $col) {
                if ($col['Field'] === 'customer_id') $has_customer_id = true;
                if ($col['Field'] === 'user_id') $has_user_id = true;
            }
            
            if ($has_customer_id && !$has_user_id) {
                $pdo->exec("ALTER TABLE referral_stats CHANGE customer_id user_id INT NOT NULL");
                echo '<span class="text-green-600">✓</span> referral_stats: customer_id → user_id<br>';
            } else {
                echo '<span class="text-yellow-600">⊘</span> referral_stats: bereits korrekt<br>';
            }
            
        } catch (PDOException $e) {
            echo '<span class="text-red-600">✗</span> referral_stats: ' . $e->getMessage() . '<br>';
        }
        
        // Gleiches für referral_rewards
        try {
            $stmt = $pdo->query("DESCRIBE referral_rewards");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $has_customer_id = false;
            $has_user_id = false;
            
            foreach ($cols as $col) {
                if ($col['Field'] === 'customer_id') $has_customer_id = true;
                if ($col['Field'] === 'user_id') $has_user_id = true;
            }
            
            if ($has_customer_id && !$has_user_id) {
                $pdo->exec("ALTER TABLE referral_rewards CHANGE customer_id user_id INT NOT NULL");
                echo '<span class="text-green-600">✓</span> referral_rewards: customer_id → user_id<br>';
            } else {
                echo '<span class="text-yellow-600">⊘</span> referral_rewards: bereits korrekt<br>';
            }
            
        } catch (PDOException $e) {
            echo '<span class="text-red-600">✗</span> referral_rewards: ' . $e->getMessage() . '<br>';
        }
        
        // Gleiches für referral_fraud_log
        try {
            $stmt = $pdo->query("DESCRIBE referral_fraud_log");
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $has_customer_id = false;
            $has_user_id = false;
            
            foreach ($cols as $col) {
                if ($col['Field'] === 'customer_id') $has_customer_id = true;
                if ($col['Field'] === 'user_id') $has_user_id = true;
            }
            
            if ($has_customer_id && !$has_user_id) {
                $pdo->exec("ALTER TABLE referral_fraud_log CHANGE customer_id user_id INT NOT NULL");
                echo '<span class="text-green-600">✓</span> referral_fraud_log: customer_id → user_id<br>';
            } else {
                echo '<span class="text-yellow-600">⊘</span> referral_fraud_log: bereits korrekt<br>';
            }
            
        } catch (PDOException $e) {
            echo '<span class="text-red-600">✗</span> referral_fraud_log: ' . $e->getMessage() . '<br>';
        }
        
        echo '</div>';
        
        // 4. Initialisiere Daten
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>4️⃣ Initialisiere Daten...</strong><br><br>';
        
        try {
            $pdo->exec("
                INSERT INTO referral_stats (user_id)
                SELECT id FROM users
                WHERE id NOT IN (SELECT user_id FROM referral_stats)
            ");
            echo '<span class="text-green-600">✓</span> referral_stats initialisiert<br>';
        } catch (PDOException $e) {
            echo '<span class="text-yellow-600">⊘</span> referral_stats: ' . $e->getMessage() . '<br>';
        }
        
        try {
            $pdo->exec("
                INSERT INTO referral_rewards (user_id)
                SELECT id FROM users
                WHERE id NOT IN (SELECT user_id FROM referral_rewards)
            ");
            echo '<span class="text-green-600">✓</span> referral_rewards initialisiert<br>';
        } catch (PDOException $e) {
            echo '<span class="text-yellow-600">⊘</span> referral_rewards: ' . $e->getMessage() . '<br>';
        }
        
        echo '</div>';
        
        // 5. Finale Prüfung
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>5️⃣ Finale Prüfung...</strong><br><br>';
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'referral_%'");
        $tables = [];
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
            echo '✓ ' . $table . ' (' . $count . ' Einträge)<br>';
        }
        
        echo '</div>';
        
        if (count($tables) >= 6) {
            echo '<div class="p-4 bg-green-50 border-2 border-green-500 rounded-lg">';
            echo '<strong class="text-green-800 text-xl">🎉 INSTALLATION ERFOLGREICH!</strong><br><br>';
            echo '<div class="text-green-700">';
            echo '✅ Alle 6 Referral-Tabellen erstellt<br>';
            echo '✅ users Tabelle erweitert<br>';
            echo '✅ Daten initialisiert<br>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="mt-6 p-4 bg-blue-50 rounded-lg">';
            echo '<strong>📋 Nächste Schritte:</strong><br><br>';
            echo '1. <strong>Test-Daten erstellen:</strong><br>';
            echo '   <a href="create-test-data-users.php" class="text-blue-600 underline">→ Test-Daten für User ID 1</a><br><br>';
            echo '2. <strong>Aufräumen:</strong><br>';
            echo '   Lösche alle Installer-Dateien (*-only.php, debug-*.php, fix-*.php, diagnose-*.php)<br><br>';
            echo '3. <strong>Wichtig:</strong> Das System nutzt jetzt USER_ID statt CUSTOMER_ID!<br>';
            echo '   Die PHP-Dateien müssen entsprechend angepasst werden.';
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
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <strong class="text-green-800">✅ Korrigierte Version!</strong>
            <p class="text-green-700 mt-2">Dieses Skript arbeitet mit der USERS Tabelle (nicht customers).</p>
        </div>
        
        <div class="p-4 bg-blue-50 rounded-lg">
            <strong>Was wird gemacht:</strong>
            <ul class="list-disc ml-6 mt-2 text-gray-700">
                <li>users Tabelle um Referral-Felder erweitern</li>
                <li>6 Referral-Tabellen mit user_id erstellen</li>
                <li>Existierende Tabellen von customer_id auf user_id umstellen</li>
                <li>Daten für alle Users initialisieren</li>
            </ul>
        </div>
        
        <a href="?run=1" class="inline-block px-8 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xl font-semibold">
            🚀 Korrekte Installation starten
        </a>
    </div>
    <?php
}
?>

        </div>
    </div>
</body>
</html>
