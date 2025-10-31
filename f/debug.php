<?php
/**
 * DEBUG: Link-Redirect Tester
 * URL: /f/debug.php
 */

// KEINE INCLUDES! Nur pure PHP

echo "<!DOCTYPE html>";
echo "<html lang='de'><head><meta charset='UTF-8'>";
echo "<title>Short-Link Debug</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f0f0f0;max-width:900px;margin:0 auto;}";
echo ".box{background:white;padding:20px;margin:10px 0;border-radius:8px;border-left:4px solid #667eea;}";
echo ".success{border-left-color:#10b981;}.error{border-left-color:#ef4444;}";
echo "pre{background:#1f2937;color:#10b981;padding:15px;border-radius:4px;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>🔍 Short-Link Debug</h1>";

// Step 1: Request Info
echo "<div class='box'>";
echo "<h2>1️⃣ Request Information</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'N/A') . "\n";
echo "QUERY_STRING: " . ($_SERVER['QUERY_STRING'] ?? 'N/A') . "\n";
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Parsed Path: " . $path . "\n";
echo "Basename: " . basename($path) . "\n";
echo "</pre>";
echo "</div>";

// Step 2: Database Test
echo "<div class='box'>";
echo "<h2>2️⃣ Database Connection</h2>";
try {
    require_once __DIR__ . '/../config/database.php';
    if (isset($pdo) && $pdo) {
        echo "<p style='color:green;'>✅ Database connected</p>";
        
        // Test Query
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM freebies WHERE short_link IS NOT NULL");
        $result = $stmt->fetch();
        echo "<p>📊 Short-Links in DB: " . $result['cnt'] . "</p>";
        
        // Show all short links
        echo "<h3>Vorhandene Short-Links:</h3>";
        echo "<pre>";
        $stmt = $pdo->query("SELECT id, name, short_link, thank_you_short_link FROM freebies WHERE short_link IS NOT NULL OR thank_you_short_link IS NOT NULL");
        while ($row = $stmt->fetch()) {
            echo "ID: {$row['id']} | Name: {$row['name']}\n";
            echo "  Freebie Short: {$row['short_link']}\n";
            echo "  ThankYou Short: {$row['thank_you_short_link']}\n\n";
        }
        echo "</pre>";
        
    } else {
        echo "<p style='color:red;'>❌ No database connection</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 3: Test Short-Link Lookup
echo "<div class='box'>";
echo "<h2>3️⃣ Short-Link Lookup Test</h2>";
$test_code = 'fj4b9e'; // Der Code aus deinem Link
$test_link = '/f/' . $test_code;

echo "<p>Testing: <code>{$test_link}</code></p>";

try {
    $stmt = $pdo->prepare("SELECT id, name, public_link, short_link FROM freebies WHERE short_link = ?");
    $stmt->execute([$test_link]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "<div class='success' style='padding:15px;margin-top:10px;'>";
        echo "<p style='color:green;font-weight:bold;'>✅ Short-Link gefunden!</p>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
        echo "<p><strong>Redirect zu:</strong> <a href='{$result['public_link']}'>{$result['public_link']}</a></p>";
        echo "</div>";
    } else {
        echo "<div class='error' style='padding:15px;margin-top:10px;'>";
        echo "<p style='color:red;font-weight:bold;'>❌ Kein Ergebnis!</p>";
        echo "<p>Der Short-Link <code>{$test_link}</code> wurde in der Datenbank nicht gefunden.</p>";
        echo "</div>";
        
        // Search for similar
        echo "<h4>Ähnliche Suche:</h4>";
        echo "<pre>";
        $stmt = $pdo->query("SELECT id, name, short_link FROM freebies WHERE short_link LIKE '%{$test_code}%' OR short_link LIKE '%f/{$test_code}%'");
        while ($row = $stmt->fetch()) {
            print_r($row);
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Query Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 4: .htaccess Test
echo "<div class='box'>";
echo "<h2>4️⃣ .htaccess Check</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<p>✅ .htaccess exists</p>";
    echo "<pre>";
    echo htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess'));
    echo "</pre>";
} else {
    echo "<p>❌ No .htaccess found</p>";
}
echo "</div>";

// Step 5: Session Check
echo "<div class='box'>";
echo "<h2>5️⃣ Session Check</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color:orange;'>⚠️ Session is active (might be from includes)</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p style='color:green;'>✅ No session active</p>";
}
echo "</div>";

echo "</body></html>";
