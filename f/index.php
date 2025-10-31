<?php
/**
 * Link-Redirect für gekürzte URLs
 * Format: /f/XXXXXX
 */

require_once __DIR__ . '/../config/database.php';

// Short-Code aus URL holen
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$short_code = basename($path);

if (empty($short_code) || strlen($short_code) < 6) {
    header('Location: /');
    exit;
}

// In Datenbank nach Link suchen
$short_link = '/f/' . $short_code;

// Zuerst nach Freebie-Link suchen
$stmt = $pdo->prepare("SELECT id, public_link FROM freebies WHERE short_link = ?");
$stmt->execute([$short_link]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    // Redirect zu Freebie
    header('Location: ' . $result['public_link']);
    exit;
}

// Dann nach Thank-You-Link suchen
$stmt = $pdo->prepare("SELECT id, thank_you_link FROM freebies WHERE thank_you_short_link = ?");
$stmt->execute([$short_link]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    // Redirect zu Thank-You-Seite
    header('Location: ' . $result['thank_you_link']);
    exit;
}

// Link nicht gefunden
header('HTTP/1.0 404 Not Found');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link nicht gefunden</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .container {
            max-width: 500px;
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 24px;
        }
        h1 {
            font-size: 36px;
            margin-bottom: 16px;
        }
        p {
            font-size: 18px;
            opacity: 0.9;
            margin-bottom: 32px;
        }
        a {
            display: inline-block;
            padding: 14px 32px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        a:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">🔍</div>
        <h1>Link nicht gefunden</h1>
        <p>Dieser Link existiert nicht oder ist nicht mehr gültig.</p>
        <a href="/">Zur Startseite</a>
    </div>
</body>
</html>
