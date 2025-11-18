<?php
/**
 * Quick Check: Wo ist der neue Kauf gelandet?
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Kauf-Check</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: left;
        }
        th {
            background: rgba(102, 126, 234, 0.2);
        }
    </style>
</head>
<body>
    <h1>🔍 Wo ist der Kauf gelandet?</h1>

<?php
try {
    $pdo = getDBConnection();
    
    echo '<div class="section">';
    echo '<h2>1️⃣ Alle Accounts mit dieser Email</h2>';
    
    // USERS Tabelle (neues System)
    $stmt = $pdo->prepare("SELECT id, name, email, role, source, created_at FROM users WHERE email = '12@abnehmen-fitness.com'");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo '<h3 class="success">✅ USERS Tabelle (Neues System):</h3>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Source</th><th>Created</th></tr>';
        foreach ($users as $u) {
            echo '<tr>';
            echo '<td>' . $u['id'] . '</td>';
            echo '<td>' . htmlspecialchars($u['name']) . '</td>';
            echo '<td>' . htmlspecialchars($u['email']) . '</td>';
            echo '<td>' . $u['role'] . '</td>';
            echo '<td>' . $u['source'] . '</td>';
            echo '<td>' . $u['created_at'] . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // CUSTOMERS Tabelle (altes System)
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, source, created_at FROM customers WHERE email = '12@abnehmen-fitness.com'");
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($customers) > 0) {
            echo '<h3 class="error">⚠️ CUSTOMERS Tabelle (Altes System - DEPRECATED):</h3>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Source</th><th>Created</th></tr>';
            foreach ($customers as $c) {
                echo '<tr>';
                echo '<td>' . $c['id'] . '</td>';
                echo '<td>' . htmlspecialchars($c['name']) . '</td>';
                echo '<td>' . htmlspecialchars($c['email']) . '</td>';
                echo '<td>' . $c['source'] . '</td>';
                echo '<td>' . $c['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } catch (Exception $e) {
        echo '<p class="error">CUSTOMERS Tabelle existiert nicht (gut!)</p>';
    }
    
    echo '</div>';
    
    // Freebies in customer_freebies
    echo '<div class="section">';
    echo '<h2>2️⃣ Freebies für alle User-IDs</h2>';
    
    foreach ($users as $user) {
        $stmt = $pdo->prepare("
            SELECT id, headline, freebie_type, copied_from_freebie_id, created_at 
            FROM customer_freebies 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<h3>User ID ' . $user['id'] . ' (' . htmlspecialchars($user['name']) . '):</h3>';
        
        if (count($freebies) > 0) {
            echo '<p class="success">✅ ' . count($freebies) . ' Freebie(s) gefunden</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Headline</th><th>Type</th><th>Copied From</th><th>Created</th></tr>';
            foreach ($freebies as $f) {
                echo '<tr>';
                echo '<td>' . $f['id'] . '</td>';
                echo '<td>' . htmlspecialchars($f['headline'] ?: '(LEER)') . '</td>';
                echo '<td>' . $f['freebie_type'] . '</td>';
                echo '<td>' . ($f['copied_from_freebie_id'] ?: '-') . '</td>';
                echo '<td>' . $f['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">❌ Keine Freebies</p>';
        }
    }
    
    echo '</div>';
    
    // Webhook Logs
    echo '<div class="section">';
    echo '<h2>3️⃣ Letzte Webhook-Aktivität</h2>';
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $logLines = explode("\n", $logs);
        $recentLogs = array_slice($logLines, -100); // Letzte 100 Zeilen
        
        // Filter für 12@abnehmen-fitness.com
        $relevantLogs = array_filter($recentLogs, function($line) {
            return stripos($line, '12@abnehmen-fitness.com') !== false || 
                   stripos($line, 'marketplace') !== false;
        });
        
        if (count($relevantLogs) > 0) {
            echo '<p class="success">✅ ' . count($relevantLogs) . ' relevante Log-Einträge</p>';
            echo '<div style="background: #000; padding: 10px; border-radius: 4px; overflow-x: auto; max-height: 400px; overflow-y: auto;">';
            echo '<pre style="font-size: 12px; margin: 0;">';
            foreach ($relevantLogs as $line) {
                if (stripos($line, 'error') !== false) {
                    echo '<span style="color: #ef4444;">' . htmlspecialchars($line) . '</span>' . "\n";
                } elseif (stripos($line, 'success') !== false) {
                    echo '<span style="color: #10b981;">' . htmlspecialchars($line) . '</span>' . "\n";
                } else {
                    echo htmlspecialchars($line) . "\n";
                }
            }
            echo '</pre></div>';
        } else {
            echo '<p class="error">❌ Keine relevanten Webhook-Logs für diese Email</p>';
        }
    } else {
        echo '<p class="error">❌ Keine Webhook-Logs gefunden</p>';
    }
    
    echo '</div>';
    
    // Zusammenfassung
    echo '<div class="section">';
    echo '<h2>📊 Zusammenfassung</h2>';
    
    if (count($users) > 0) {
        $userId = $users[0]['id'];
        echo '<p class="success">✅ User existiert in USERS Tabelle (ID: ' . $userId . ')</p>';
        echo '<p><strong>Login:</strong> <a href="/public/login.php" style="color: #667eea;">https://app.mehr-infos-jetzt.de/public/login.php</a></p>';
        echo '<p><strong>Email:</strong> 12@abnehmen-fitness.com</p>';
        
        // Check ob Freebies da sind
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM customer_freebies WHERE customer_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo '<p class="success">✅ Hat ' . $count . ' Freebie(s) im System</p>';
            echo '<p><a href="diagnose-marketplace-purchase-v2.php?email=12@abnehmen-fitness.com&freebie_id=613818" style="color: #667eea;">Diagnose-Tool öffnen</a></p>';
        } else {
            echo '<p class="error">⚠️ Hat KEINE Freebies - Webhook hat nicht funktioniert?</p>';
        }
    }
    
    if (isset($customers) && count($customers) > 0) {
        echo '<p class="warning">⚠️ Alter CUSTOMERS Account existiert noch (sollte ignoriert werden)</p>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">ERROR: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

<div class="section">
    <h2>🔧 Nächste Schritte</h2>
    <ol>
        <li>Logge dich im RICHTIGEN System ein: <a href="/public/login.php" style="color: #667eea;">https://app.mehr-infos-jetzt.de/public/login.php</a></li>
        <li>Nutze die Email: <strong>12@abnehmen-fitness.com</strong></li>
        <li>Checke dort die Freebies</li>
    </ol>
</div>

</body>
</html>