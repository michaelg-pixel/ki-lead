<?php
/**
 * Debug-Seite für E-Mail-Versand Test
 * Zeigt ob PHP mail() funktioniert
 */

require_once '../config/database.php';
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
    <title>E-Mail Versand Debug</title>
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

<h1>🔍 E-Mail Versand Debug Test</h1>

<pre>
<?php

echo "<span class='info'>ℹ️  Hinweis: E-Mail-Versand nutzt jetzt PHP mail() statt Quentn API</span>\n";
echo "<span class='info'>   Einfacher und zuverlässiger für Transaktions-E-Mails</span>\n\n";

echo "<span class='info'>1. Prüfe ob User existiert...</span>\n";
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

echo "<span class='info'>2. Teste PHP mail() Funktion...</span>\n";

// Prüfe ob mail() verfügbar ist
if (!function_exists('mail')) {
    echo "<span class='error'>   ✗ PHP mail() Funktion nicht verfügbar!</span>\n";
    echo "<span class='warning'>   → Hostinger sollte mail() unterstützen</span>\n";
    echo "<span class='warning'>   → Prüfe PHP-Konfiguration</span>\n\n";
    exit;
} else {
    echo "<span class='success'>   ✓ PHP mail() Funktion verfügbar</span>\n\n";
}

echo "<span class='info'>3. Sende Test-E-Mail...</span>\n";

$resetLink = "https://app.mehr-infos-jetzt.de/public/password-reset.php?token=TEST" . time();
$result = sendPasswordResetEmail($testEmail, $user['name'], $resetLink);

if ($result['success']) {
    echo "<span class='success'>   ✓ {$result['message']}</span>\n\n";
    echo "<span class='success'>🎉 E-Mail wurde versendet!</span>\n\n";
    echo "<span class='warning'>⚠️  WICHTIG: Prüfe auch deinen SPAM-Ordner!</span>\n";
    echo "<span class='info'>   E-Mails von PHP mail() landen oft im Spam.</span>\n\n";
    echo "<span class='info'>Reset-Link im Test:</span>\n";
    echo "   $resetLink\n\n";
} else {
    echo "<span class='error'>   ✗ {$result['message']}</span>\n\n";
    echo "<span class='warning'>Mögliche Probleme:</span>\n";
    echo "   - Mail-Server auf Hostinger nicht korrekt konfiguriert\n";
    echo "   - SPF/DKIM Records für mehr-infos-jetzt.de fehlen\n";
    echo "   - PHP mail() durch Hoster deaktiviert\n\n";
}

?>
</pre>

<p style="margin-top: 30px; padding: 20px; background: #2d2d2d; border-radius: 8px;">
    <strong style="color: #00aaff;">Spam-Schutz Tipp:</strong><br>
    <span style="color: #ccc;">
    Um zu verhindern, dass E-Mails im Spam landen:<br>
    1. SPF Record für mehr-infos-jetzt.de setzen<br>
    2. DKIM aktivieren<br>
    3. Oder SMTP mit einem E-Mail-Service nutzen (z.B. SendGrid, Mailgun)<br>
    <br>
    <a href="password-reset-request.php" style="color: #00ff00;">Zurück zur Reset-Anfrage</a>
    </span>
</p>

</body>
</html>
