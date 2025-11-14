<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug: Checking Quentn Setup</h1>";
echo "<pre>";

// 1. Check if quentn_config exists
echo "1. Checking quentn_config.php...\n";
if (file_exists('../config/quentn_config.php')) {
    echo "✅ File exists\n";
    require_once '../config/quentn_config.php';
    echo "✅ Loaded successfully\n";
    echo "   API_BASE_URL: " . QUENTN_API_BASE_URL . "\n";
    echo "   API_KEY: " . substr(QUENTN_API_KEY, 0, 10) . "...\n";
} else {
    echo "❌ File not found\n";
}

echo "\n";

// 2. Check if quentn_helpers exists
echo "2. Checking quentn_helpers.php...\n";
if (file_exists('../includes/quentn_helpers.php')) {
    echo "✅ File exists\n";
    require_once '../includes/quentn_helpers.php';
    echo "✅ Loaded successfully\n";
    
    // Check if functions exist
    if (function_exists('quentnGetAllTags')) {
        echo "✅ Function quentnGetAllTags exists\n";
    }
    if (function_exists('quentnCreateContact')) {
        echo "✅ Function quentnCreateContact exists\n";
    }
} else {
    echo "❌ File not found\n";
}

echo "\n";

// 3. Test simple API call
echo "3. Testing simple API call...\n";
try {
    $ch = curl_init(QUENTN_API_BASE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . QUENTN_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($httpCode == 200 || $httpCode == 405) {
        echo "✅ API is reachable\n";
    } else {
        echo "❌ API returned: $httpCode\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// 4. Try to get tags
echo "4. Testing quentnGetAllTags()...\n";
if (function_exists('quentnGetAllTags')) {
    try {
        $tags = quentnGetAllTags();
        if (is_array($tags)) {
            echo "✅ Got " . count($tags) . " tags\n";
            if (count($tags) > 0) {
                echo "First tag: " . json_encode($tags[0]) . "\n";
            }
        } else {
            echo "❌ Did not get array, got: " . gettype($tags) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";
echo "<hr>";
echo "<a href='/public/test-quentn-final.php'>Back to test page</a>";
