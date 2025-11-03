<?php
/**
 * TEST-DATEN für Referral-System
 * Erstellt Demo-Daten für User ID 1
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
    <title>Test-Daten erstellen</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold mb-4">🧪 Test-Daten erstellen</h1>

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
        
        // Aktiviere Referral für User 1
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>1️⃣ Aktiviere Referral für User 1...</strong><br><br>';
        
        $pdo->exec("
            UPDATE users 
            SET 
                referral_enabled = 1,
                company_name = 'Test Firma GmbH',
                company_email = 'test@mehr-infos-jetzt.de',
                company_imprint_html = '<p>Test Firma GmbH<br>Teststraße 123<br>12345 Teststadt<br>E-Mail: test@mehr-infos-jetzt.de</p>'
            WHERE id = 1
            LIMIT 1
        ");
        
        echo '<span class="text-green-600">✓</span> User 1 aktiviert<br>';
        echo '</div>';
        
        // Erstelle Test-Klicks
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>2️⃣ Erstelle Test-Klicks...</strong><br><br>';
        
        $test_clicks = [
            ['TEST123', '192.168.1.1', 'Chrome'],
            ['TEST123', '192.168.1.2', 'Firefox'],
            ['TEST456', '192.168.1.3', 'Safari']
        ];
        
        foreach ($test_clicks as $click) {
            try {
                $pdo->exec("
                    INSERT INTO referral_clicks (user_id, ref_code, ip_address_hash, user_agent, fingerprint, created_at)
                    VALUES (
                        1, 
                        '{$click[0]}', 
                        SHA2('{$click[1]}', 256), 
                        'Mozilla/5.0 {$click[2]}', 
                        MD5(CONCAT('{$click[1]}', '{$click[2]}')), 
                        NOW()
                    )
                ");
                echo '<span class="text-green-600">✓</span> Klick: ' . $click[0] . ' von ' . $click[1] . '<br>';
            } catch (PDOException $e) {
                echo '<span class="text-yellow-600">⊘</span> ' . $e->getMessage() . '<br>';
            }
        }
        
        echo '</div>';
        
        // Erstelle Test-Conversions
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>3️⃣ Erstelle Test-Conversions...</strong><br><br>';
        
        $test_conversions = [
            ['TEST123', '192.168.1.1', 'Chrome', 'thankyou'],
            ['TEST456', '192.168.1.3', 'Safari', 'pixel']
        ];
        
        foreach ($test_conversions as $conv) {
            try {
                $pdo->exec("
                    INSERT INTO referral_conversions (user_id, ref_code, ip_address_hash, user_agent, fingerprint, source, suspicious, created_at)
                    VALUES (
                        1,
                        '{$conv[0]}',
                        SHA2('{$conv[1]}', 256),
                        'Mozilla/5.0 {$conv[2]}',
                        MD5(CONCAT('{$conv[1]}', '{$conv[2]}')),
                        '{$conv[3]}',
                        0,
                        NOW()
                    )
                ");
                echo '<span class="text-green-600">✓</span> Conversion: ' . $conv[0] . ' via ' . $conv[3] . '<br>';
            } catch (PDOException $e) {
                echo '<span class="text-yellow-600">⊘</span> ' . $e->getMessage() . '<br>';
            }
        }
        
        echo '</div>';
        
        // Erstelle Test-Leads
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>4️⃣ Erstelle Test-Leads...</strong><br><br>';
        
        $test_leads = [
            ['TEST123', 'max@example.com', true],
            ['TEST123', 'anna@example.com', false],
            ['TEST456', 'peter@example.com', true]
        ];
        
        foreach ($test_leads as $lead) {
            try {
                $email_hash = hash('sha256', $lead[1]);
                $token = bin2hex(random_bytes(32));
                $confirmed = $lead[2] ? 1 : 0;
                $confirmed_at = $lead[2] ? 'NOW()' : 'NULL';
                
                $pdo->exec("
                    INSERT INTO referral_leads (user_id, ref_code, email, email_hash, confirmed, confirmation_token, confirmed_at, created_at)
                    VALUES (
                        1,
                        '{$lead[0]}',
                        '{$lead[1]}',
                        '$email_hash',
                        $confirmed,
                        '$token',
                        $confirmed_at,
                        NOW()
                    )
                ");
                $status = $lead[2] ? 'bestätigt' : 'ausstehend';
                echo '<span class="text-green-600">✓</span> Lead: ' . $lead[1] . ' (' . $status . ')<br>';
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo '<span class="text-yellow-600">⊘</span> ' . $lead[1] . ' bereits vorhanden<br>';
                } else {
                    echo '<span class="text-yellow-600">⊘</span> ' . $e->getMessage() . '<br>';
                }
            }
        }
        
        echo '</div>';
        
        // Update Stats
        echo '<div class="p-4 bg-blue-50 rounded-lg">';
        echo '<strong>5️⃣ Aktualisiere Statistiken...</strong><br><br>';
        
        // Berechne Stats
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM referral_clicks WHERE user_id = 1");
        $clicks = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->query("SELECT COUNT(DISTINCT fingerprint) as cnt FROM referral_clicks WHERE user_id = 1");
        $unique_clicks = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM referral_conversions WHERE user_id = 1");
        $conversions = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM referral_conversions WHERE user_id = 1 AND suspicious = 1");
        $suspicious = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM referral_leads WHERE user_id = 1");
        $leads = $stmt->fetch()['cnt'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM referral_leads WHERE user_id = 1 AND confirmed = 1");
        $confirmed_leads = $stmt->fetch()['cnt'];
        
        $conversion_rate = $unique_clicks > 0 ? round(($conversions / $unique_clicks) * 100, 2) : 0;
        
        $pdo->exec("
            UPDATE referral_stats 
            SET 
                total_clicks = $clicks,
                unique_clicks = $unique_clicks,
                total_conversions = $conversions,
                suspicious_conversions = $suspicious,
                total_leads = $leads,
                confirmed_leads = $confirmed_leads,
                conversion_rate = $conversion_rate,
                last_click_at = NOW(),
                last_conversion_at = NOW(),
                updated_at = NOW()
            WHERE user_id = 1
        ");
        
        echo '<span class="text-green-600">✓</span> Statistiken aktualisiert<br>';
        echo '<div class="ml-4 mt-2 bg-white p-3 rounded">';
        echo "• Gesamt Klicks: $clicks<br>";
        echo "• Unique Klicks: $unique_clicks<br>";
        echo "• Conversions: $conversions<br>";
        echo "• Leads gesamt: $leads<br>";
        echo "• Leads bestätigt: $confirmed_leads<br>";
        echo "• Conversion Rate: $conversion_rate%<br>";
        echo '</div>';
        
        echo '</div>';
        
        // Hole Referral-Code
        $stmt = $pdo->query("SELECT referral_code FROM users WHERE id = 1");
        $ref_code = $stmt->fetch()['referral_code'];
        
        echo '<div class="p-4 bg-green-50 border-2 border-green-500 rounded-lg">';
        echo '<strong class="text-green-800 text-xl">🎉 TEST-DATEN ERSTELLT!</strong><br><br>';
        echo '<div class="text-green-700">';
        echo "✅ User 1 aktiviert<br>";
        echo "✅ 3 Test-Klicks erstellt<br>";
        echo "✅ 2 Test-Conversions erstellt<br>";
        echo "✅ 3 Test-Leads erstellt<br>";
        echo "✅ Statistiken aktualisiert<br>";
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mt-6 p-4 bg-blue-50 rounded-lg">';
        echo '<strong>🔗 Test-Links:</strong><br><br>';
        echo '<div class="space-y-2">';
        echo '<div class="bg-white p-3 rounded">';
        echo '<strong>Referral-Link:</strong><br>';
        echo '<code class="text-sm">https://app.mehr-infos-jetzt.de/freebie.php?ref=' . $ref_code . '</code>';
        echo '</div>';
        echo '<div class="bg-white p-3 rounded">';
        echo '<strong>Admin Dashboard:</strong><br>';
        echo '<a href="admin/sections/referral-overview.php" class="text-blue-600 underline">→ Referral-Übersicht öffnen</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">';
        echo '<strong class="text-yellow-800">⚠️ WICHTIG:</strong><br>';
        echo '<p class="text-yellow-700 mt-2">Die PHP-Dateien im /api/referral/ Ordner müssen noch von customer_id auf user_id angepasst werden!</p>';
        echo '</div>';
        
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
        <div class="p-4 bg-blue-50 rounded-lg">
            <strong>Was wird erstellt:</strong>
            <ul class="list-disc ml-6 mt-2 text-gray-700">
                <li>Aktiviert Referral für User 1</li>
                <li>3 Test-Klicks (verschiedene IPs und Codes)</li>
                <li>2 Test-Conversions</li>
                <li>3 Test-Leads (2 bestätigt, 1 ausstehend)</li>
                <li>Aktualisiert alle Statistiken</li>
            </ul>
        </div>
        
        <a href="?run=1" class="inline-block px-8 py-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-xl font-semibold">
            🚀 Test-Daten erstellen
        </a>
    </div>
    <?php
}
?>

        </div>
    </div>
</body>
</html>
