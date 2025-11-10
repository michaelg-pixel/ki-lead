<?php
// Dashboard Error Diagnose
// Aufruf: https://app.mehr-infos-jetzt.de/diagnose-dashboard.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Dashboard Diagnose</h2>";

// 1. PHP Syntax Check
echo "<h3>1. PHP Syntax Check</h3>";
$files_to_check = [
    '/customer/dashboard.php',
    '/customer/sections/marktplatz.php',
    '/customer/sections/marktplatz-browse.php',
    '/customer/api/marketplace-update.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . $file;
    echo "<strong>$file</strong><br>";
    
    if (!file_exists($full_path)) {
        echo "❌ Datei nicht gefunden<br><br>";
        continue;
    }
    
    exec("php -l " . escapeshellarg($full_path) . " 2>&1", $output, $return_code);
    
    if ($return_code === 0) {
        echo "✅ Syntax OK<br>";
    } else {
        echo "❌ <span style='color: red;'>Syntax Fehler:</span><br>";
        echo "<pre style='background: #fee; padding: 10px; color: red;'>";
        echo implode("\n", $output);
        echo "</pre>";
    }
    echo "<br>";
}

// 2. Teste Dashboard Include
echo "<h3>2. Dashboard Include Test</h3>";

session_start();
$_SESSION['user_id'] = 4;
$_SESSION['role'] = 'customer';
$_SESSION['name'] = 'Test User';
$_SESSION['email'] = 'test@test.de';

echo "Session gesetzt: ✅<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br><br>";

echo "<strong>Versuche Dashboard zu laden:</strong><br>";
ob_start();
try {
    include __DIR__ . '/customer/dashboard.php';
    $output = ob_get_clean();
    echo "✅ Dashboard erfolgreich geladen!<br>";
    echo "Output-Länge: " . strlen($output) . " Bytes<br>";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ <span style='color: red;'>Fehler beim Laden:</span><br>";
    echo "<pre style='background: #fee; padding: 10px; color: red;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

// 3. PHP Error Log
echo "<h3>3. PHP Error Log (letzte 50 Zeilen)</h3>";
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 400px; overflow: auto;'>";
    $lines = file($error_log);
    $last_lines = array_slice($lines, -50);
    echo htmlspecialchars(implode('', $last_lines));
    echo "</pre>";
} else {
    echo "Kein Error Log gefunden oder nicht konfiguriert.<br>";
}
?>
