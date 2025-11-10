<?php
// Test des Marketplace API Endpoints
// Aufruf: https://app.mehr-infos-jetzt.de/test-marketplace-api.php

session_start();

// Fake Login für Test
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 4;
    $_SESSION['role'] = 'customer';
}

echo "<h2>🧪 Marketplace API Test</h2>";

// 1. Prüfe ob API-Datei existiert
$api_file = __DIR__ . '/customer/api/marketplace-update.php';
echo "<h3>1. Datei-Check</h3>";
echo "API-Datei: " . $api_file . "<br>";
echo "Existiert: " . (file_exists($api_file) ? '✅ JA' : '❌ NEIN') . "<br>";

if (file_exists($api_file)) {
    echo "Größe: " . filesize($api_file) . " Bytes<br>";
}

// 2. Test POST Request
echo "<h3>2. Test POST Request</h3>";

$test_data = [
    'freebie_id' => 7,
    'enabled' => 1,
    'price' => 66.00,
    'digistore_id' => 613818,
    'description' => 'Test Beschreibung'
];

echo "<strong>Test-Daten:</strong><br>";
echo "<pre>" . print_r($test_data, true) . "</pre>";

// Simuliere POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $test_data;

echo "<strong>Response:</strong><br>";
echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";

// Capture output
ob_start();

try {
    include $api_file;
    $response = ob_get_clean();
    echo htmlspecialchars($response);
    
    // Prüfe ob gültiges JSON
    $json = json_decode($response);
    if ($json === null) {
        echo "\n\n❌ FEHLER: Keine gültige JSON-Response!";
        echo "\nJSON Error: " . json_last_error_msg();
    } else {
        echo "\n\n✅ Gültige JSON-Response!";
        echo "\nParsed: " . print_r($json, true);
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Exception: " . $e->getMessage();
}

echo "</pre>";

// 3. Browser-Test mit Fetch
echo "<h3>3. Browser Fetch Test</h3>";
echo "<button onclick='testFetch()' style='padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;'>🚀 Fetch API testen</button>";
echo "<pre id='fetchResult' style='background: #f5f5f5; padding: 10px; border-radius: 5px; margin-top: 10px; min-height: 100px;'></pre>";

?>

<script>
async function testFetch() {
    const resultDiv = document.getElementById('fetchResult');
    resultDiv.textContent = '🔄 Teste API...\n';
    
    try {
        const response = await fetch('/customer/api/marketplace-update.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                freebie_id: 7,
                enabled: 1,
                price: 66.00,
                digistore_id: 613818,
                description: 'Test Beschreibung via Fetch'
            })
        });
        
        resultDiv.textContent += `Status: ${response.status}\n`;
        resultDiv.textContent += `Content-Type: ${response.headers.get('content-type')}\n\n`;
        
        const text = await response.text();
        resultDiv.textContent += `Response Text:\n${text}\n\n`;
        
        try {
            const json = JSON.parse(text);
            resultDiv.textContent += `✅ Gültiges JSON:\n${JSON.stringify(json, null, 2)}`;
        } catch (e) {
            resultDiv.textContent += `❌ JSON Parse Error: ${e.message}`;
        }
        
    } catch (error) {
        resultDiv.textContent += `❌ Fetch Error: ${error.message}`;
    }
}
</script>
