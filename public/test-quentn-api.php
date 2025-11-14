<?php
/**
 * Debug-Seite für Quentn API Test
 * Zeigt ob die API funktioniert
 */

require_once '../config/database.php';
require_once '../config/quentn_config.php';
require_once '../includes/quentn_api.php';

header('Content-Type: text/html; charset=utf-8');

// Test-E-Mail
$testEmail = 'cybercop33@web.de';

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quentn API Debug</title>
    <style>
        body {
            font-family: monospace;
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            line-height: 1.8;
        }
        .success { color: #00ff00; }
        .error { color: #ff4444; }
        .warning { color: #ffaa00; }
        .info { color: #00aaff; }
        h1 { color: #00aaff; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Quentn API Debug Test</h1>

<pre>
<?php

echo "<span class='info'>1. Prüfe Konfiguration...</span>\n";
echo "   API Base URL: " . QUENTN_API_BASE_URL . "\n";
echo "   API Key: " . substr(QUENTN_API_KEY, 0, 10) . "..." . substr(QUENTN_API_KEY, -5) . "\n\n";

echo "<span class='info'>2. Prüfe ob User existiert...</span>\n";
try {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<span class='success'>   ✓ User gefunden!</span>\n";
        echo "   ID: {$user['id']}\n";
        echo "   Name: {$user['name']}\n";
        echo "   E-Mail: {$user['email']}\n\n";
    } else {
        echo "<span class='error'>   ✗ User nicht gefunden!</span>\n";
        echo "   E-Mail: $testEmail\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "<span class='error'>   ✗ DB-Fehler: " . $e->getMessage() . "</span>\n\n";
    exit;
}

echo "<span class='info'>3. Teste Quentn API direkt...</span>\n";

// Direkter API-Test
$testData = [
    'email' => $testEmail,
    'first_name' => $user['name'],
    'tags' => ['password-reset-test'],
    'skip_double_opt_in' => true
];

$ch = curl_init(QUENTN_API_BASE_URL . 'contacts');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . QUENTN_API_KEY
    ],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: $httpCode\n";
if ($curlError) {
    echo "<span class='error'>   cURL Fehler: $curlError</span>\n";
}
echo "   Response: " . substr($response, 0, 500) . "\n\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "<span class='success'>✅ API-Verbindung funktioniert!</span>\n\n";
} else {
    echo "<span class='error'>❌ API-Fehler! HTTP Code: $httpCode</span>\n\n";
    echo "<span class='warning'>Mögliche Probleme:</span>\n";
    echo "   - Domain noch nicht bei Quentn verifiziert\n";
    echo "   - API Key falsch oder abgelaufen\n";
    echo "   - Keine Berechtigung zum E-Mail-Versand\n\n";
}

echo "<span class='info'>4. Teste E-Mail-Versand-Funktion...</span>\n";

$resetLink = "https://app.mehr-infos-jetzt.de/public/password-reset.php?token=TEST123456";
$result = sendPasswordResetEmail($testEmail, $user['name'], $resetLink);

if ($result['success']) {
    echo "<span class='success'>   ✓ {$result['message']}</span>\n\n";
    echo "<span class='success'>🎉 E-Mail sollte jetzt versendet sein!</span>\n";
    echo "<span class='warning'>⚠️  Prüfe auch deinen SPAM-Ordner!</span>\n";
} else {
    echo "<span class='error'>   ✗ {$result['message']}</span>\n\n";
}

?>
</pre>

<p style="margin-top: 30px; padding: 20px; background: #2d2d2d; border-radius: 8px;">
    <strong style="color: #00aaff;">Nächste Schritte:</strong><br>
    <span style="color: #ccc;">
    1. Falls HTTP Code 401/403: API Key prüfen<br>
    2. Falls HTTP Code 400: Daten-Format prüfen<br>
    3. Falls API OK aber keine E-Mail: Domain bei Quentn verifizieren<br>
    4. <a href="password-reset-request.php" style="color: #00ff00;">Zurück zur Reset-Anfrage</a>
    </span>
</p>

</body>
</html>
