<?php
/**
 * Referral-System Test & Diagnose
 * Prüft ob alle Komponenten funktionieren
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Farben für CLI-Output
function colorize($text, $color = 'green') {
    $colors = [
        'green' => "\033[0;32m",
        'red' => "\033[0;31m",
        'yellow' => "\033[1;33m",
        'blue' => "\033[0;34m",
        'reset' => "\033[0m"
    ];
    return $colors[$color] . $text . $colors['reset'];
}

function test_result($name, $success, $details = '') {
    $icon = $success ? '✓' : '✗';
    $color = $success ? 'green' : 'red';
    echo colorize("$icon $name", $color);
    if ($details) {
        echo " - " . $details;
    }
    echo "\n";
    return $success;
}

echo "============================================\n";
echo "🔍 REFERRAL-SYSTEM DIAGNOSE\n";
echo "============================================\n\n";

$all_passed = true;

// TEST 1: Datenbank-Verbindung
echo colorize("📋 TEST 1: DATENBANK-VERBINDUNG\n", 'blue');
try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    test_result("Datenbank-Verbindung", true, "MySQL verbunden");
} catch (Exception $e) {
    test_result("Datenbank-Verbindung", false, $e->getMessage());
    $all_passed = false;
    die("\n❌ Kann nicht fortfahren ohne Datenbank-Verbindung\n");
}

// TEST 2: Tabellen-Existenz
echo "\n" . colorize("📊 TEST 2: DATENBANK-TABELLEN\n", 'blue');
$required_tables = [
    'referral_clicks',
    'referral_conversions',
    'referral_leads',
    'referral_stats',
    'referral_rewards',
    'referral_fraud_log'
];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        test_result("Tabelle: $table", $exists);
        if (!$exists) $all_passed = false;
    } catch (Exception $e) {
        test_result("Tabelle: $table", false, $e->getMessage());
        $all_passed = false;
    }
}

// TEST 3: Customer-Tabelle Erweiterung
echo "\n" . colorize("👥 TEST 3: CUSTOMERS-TABELLE ERWEITERUNG\n", 'blue');
$required_columns = [
    'referral_enabled',
    'company_name',
    'company_email',
    'company_imprint_html',
    'referral_code'
];

foreach ($required_columns as $column) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM customers LIKE '$column'");
        $exists = $stmt->rowCount() > 0;
        test_result("Spalte: $column", $exists);
        if (!$exists) $all_passed = false;
    } catch (Exception $e) {
        test_result("Spalte: $column", false, $e->getMessage());
        $all_passed = false;
    }
}

// TEST 4: API-Endpoints
echo "\n" . colorize("🔌 TEST 4: API-ENDPOINTS\n", 'blue');
$api_endpoints = [
    'track-click.php',
    'track-conversion.php',
    'track.php',
    'register-lead.php',
    'toggle.php',
    'update-company.php',
    'get-stats.php',
    'get-customer-details.php',
    'get-fraud-log.php',
    'export-stats.php'
];

foreach ($api_endpoints as $endpoint) {
    $path = __DIR__ . "/../api/referral/$endpoint";
    $exists = file_exists($path);
    test_result("API: $endpoint", $exists, $exists ? filesize($path) . " bytes" : "Datei nicht gefunden");
    if (!$exists) $all_passed = false;
}

// TEST 5: Customer mit Referral-Programm
echo "\n" . colorize("🎯 TEST 5: AKTIVE REFERRAL-PROGRAMME\n", 'blue');
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE referral_enabled = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];
    test_result("Aktive Programme", $count > 0, "$count Customer haben Programm aktiviert");
    
    if ($count == 0) {
        echo colorize("   💡 Tipp: Aktiviere das Programm für mindestens einen Customer\n", 'yellow');
    }
} catch (Exception $e) {
    test_result("Aktive Programme", false, $e->getMessage());
}

// TEST 6: Statistik-Daten
echo "\n" . colorize("📈 TEST 6: STATISTIK-DATEN\n", 'blue');
try {
    // Klicks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_clicks");
    $clicks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test_result("Klicks", true, "$clicks Klicks erfasst");
    
    // Conversions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_conversions");
    $conversions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test_result("Conversions", true, "$conversions Conversions erfasst");
    
    // Leads
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_leads");
    $leads = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test_result("Leads", true, "$leads Leads registriert");
    
    // Stats
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM referral_stats WHERE total_clicks > 0 OR total_conversions > 0");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    test_result("Statistiken", true, "$stats Customer mit Aktivität");
    
    if ($clicks == 0 && $conversions == 0 && $leads == 0) {
        echo colorize("\n   💡 Tipp: Noch keine Tracking-Daten vorhanden.\n", 'yellow');
        echo colorize("   Teste mit: https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123\n", 'yellow');
    }
} catch (Exception $e) {
    test_result("Statistik-Daten", false, $e->getMessage());
}

// TEST 7: Cron-Job
echo "\n" . colorize("⏰ TEST 7: CRON-JOB\n", 'blue');
$cron_output = shell_exec('crontab -l 2>/dev/null | grep send-reward-emails.php');
$cron_exists = !empty(trim($cron_output));
test_result("Cron-Job eingerichtet", $cron_exists, $cron_exists ? "Läuft täglich um 10:00" : "Nicht gefunden");
if (!$cron_exists) {
    echo colorize("   💡 Führe aus: bash scripts/setup-referral-system.sh\n", 'yellow');
    $all_passed = false;
}

// TEST 8: Logs-Ordner
echo "\n" . colorize("📝 TEST 8: LOGS & BERECHTIGUNGEN\n", 'blue');
$log_path = '/home/lumisaas/logs';
$log_exists = is_dir($log_path);
test_result("Logs-Ordner", $log_exists, $log_exists ? "Pfad: $log_path" : "Nicht gefunden");
if ($log_exists) {
    $writable = is_writable($log_path);
    test_result("Logs beschreibbar", $writable);
    if (!$writable) $all_passed = false;
}

// TEST 9: Beispiel-Customer
echo "\n" . colorize("👤 TEST 9: CUSTOMER-KONFIGURATION\n", 'blue');
try {
    $stmt = $pdo->query("
        SELECT 
            id,
            email,
            referral_enabled,
            referral_code,
            company_name,
            company_email
        FROM customers 
        WHERE referral_enabled = 1 
        LIMIT 1
    ");
    
    if ($customer = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo colorize("   Beispiel-Customer gefunden:\n", 'green');
        echo "   ID: {$customer['id']}\n";
        echo "   E-Mail: {$customer['email']}\n";
        echo "   Referral-Code: {$customer['referral_code']}\n";
        echo "   Firma: " . ($customer['company_name'] ?: '(nicht hinterlegt)') . "\n";
        echo "   Firmen-Mail: " . ($customer['company_email'] ?: '(nicht hinterlegt)') . "\n";
        
        if (empty($customer['company_name']) || empty($customer['company_email'])) {
            echo colorize("   ⚠ Firmendaten sollten im Dashboard hinterlegt werden\n", 'yellow');
        }
        
        // Zeige Test-Link
        echo colorize("\n   🔗 Test-Link:\n", 'blue');
        echo "   https://app.mehr-infos-jetzt.de/freebie.php?customer={$customer['id']}&ref={$customer['referral_code']}\n";
    } else {
        echo colorize("   ⚠ Kein Customer mit aktivem Programm gefunden\n", 'yellow');
        echo colorize("   💡 Aktiviere das Programm im Customer-Dashboard\n", 'yellow');
    }
} catch (Exception $e) {
    test_result("Customer-Konfiguration", false, $e->getMessage());
}

// TEST 10: Frontend-Dateien
echo "\n" . colorize("🖥️  TEST 10: FRONTEND-KOMPONENTEN\n", 'blue');
$frontend_files = [
    'freebie/index.php' => 'Freebie-Seite (mit Tracking)',
    'public/thankyou.php' => 'Danke-Seite (mit Formular)',
    'customer/sections/empfehlungsprogramm.php' => 'Customer-Dashboard',
    'admin/sections/referral-overview.php' => 'Admin-Monitoring',
    'admin/sections/referral-monitoring-extended.php' => 'Erweiterte Analytics'
];

foreach ($frontend_files as $file => $description) {
    $path = __DIR__ . "/../$file";
    $exists = file_exists($path);
    test_result($description, $exists, $exists ? filesize($path) . " bytes" : "Nicht gefunden");
    if (!$exists) $all_passed = false;
}

// ZUSAMMENFASSUNG
echo "\n============================================\n";
if ($all_passed) {
    echo colorize("✅ ALLE TESTS BESTANDEN!\n", 'green');
    echo "Das Referral-System ist vollständig eingerichtet.\n";
} else {
    echo colorize("⚠️  EINIGE TESTS FEHLGESCHLAGEN\n", 'yellow');
    echo "Bitte behebe die oben genannten Probleme.\n";
}
echo "============================================\n\n";

// EMPFEHLUNGEN
echo colorize("📋 NÄCHSTE SCHRITTE:\n", 'blue');
echo "\n1. Setup-Skript ausführen (falls noch nicht geschehen):\n";
echo "   bash scripts/setup-referral-system.sh\n";
echo "\n2. Browser öffnen und testen:\n";
echo "   • Admin: https://app.mehr-infos-jetzt.de/admin/dashboard.php?section=referral-overview\n";
echo "   • Customer: https://app.mehr-infos-jetzt.de/customer/dashboard.php\n";
echo "\n3. Programm aktivieren:\n";
echo "   • Im Customer-Dashboard → Empfehlungsprogramm → Toggle aktivieren\n";
echo "\n4. Test-Link aufrufen:\n";
echo "   • https://app.mehr-infos-jetzt.de/freebie.php?customer=1&ref=TEST123\n";
echo "   • Browser-Console öffnen (F12) und nach \"Referral\" suchen\n";
echo "\n5. Dashboard prüfen:\n";
echo "   • Sollte nun Klicks und Conversions anzeigen\n";
echo "\n============================================\n";

// Exit-Code
exit($all_passed ? 0 : 1);
