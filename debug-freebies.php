<?php
/**
 * Debug Script für Customer Freebies
 * Aufruf: https://app.mehr-infos-jetzt.de/debug-freebies.php
 */

// Error Reporting VOLL aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Debug Customer Freebies</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
            line-height: 1.6;
        }
        .box {
            background: #2a2a3e;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .success { border-left-color: #22c55e; }
        .error { border-left-color: #ef4444; }
        .warning { border-left-color: #f59e0b; }
        h2 { color: #667eea; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #444; }
        th { color: #667eea; }
        code { background: #000; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>';

echo '<h1>🔍 Debug Customer Freebies System</h1>';

// SCHRITT 1: Datenbank-Verbindung
echo '<h2>1. Datenbank-Verbindung</h2>';
try {
    require_once __DIR__ . '/config/database.php';
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo '<div class="box success">✅ PDO-Verbindung erfolgreich</div>';
        
        $stmt = $pdo->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo '<div class="box">Datenbank: <code>' . htmlspecialchars($result['db']) . '</code></div>';
    } else {
        echo '<div class="box error">❌ PDO nicht verfügbar</div>';
    }
} catch (Exception $e) {
    echo '<div class="box error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// SCHRITT 2: Tabellen prüfen
echo '<h2>2. Tabellen Check</h2>';
$tables = [
    'users' => 'Benutzer-Tabelle',
    'customer_freebies' => 'Kunden Freebies',
    'customer_freebie_limits' => 'Freebie Limits',
    'product_freebie_config' => 'Produkt-Konfiguration',
    'freebies' => 'Admin Templates'
];

echo '<table>';
echo '<tr><th>Tabelle</th><th>Status</th><th>Einträge</th></tr>';

foreach ($tables as $table => $description) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            $count = $result['count'];
            echo '<tr>';
            echo '<td>' . htmlspecialchars($table) . '<br><small style="color:#888">' . htmlspecialchars($description) . '</small></td>';
            echo '<td style="color:#22c55e">✅ Existiert</td>';
            echo '<td>' . $count . '</td>';
            echo '</tr>';
        } else {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($table) . '</td>';
            echo '<td style="color:#ef4444">❌ Fehlt</td>';
            echo '<td>-</td>';
            echo '</tr>';
        }
    } catch (Exception $e) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($table) . '</td>';
        echo '<td style="color:#f59e0b">⚠️ Fehler</td>';
        echo '<td>' . htmlspecialchars($e->getMessage()) . '</td>';
        echo '</tr>';
    }
}
echo '</table>';

// SCHRITT 3: customer_freebies Struktur
echo '<h2>3. customer_freebies Struktur</h2>';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    if ($stmt->rowCount() > 0) {
        echo '<table>';
        echo '<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Default</th></tr>';
        
        $stmt = $pdo->query("DESCRIBE customer_freebies");
        while ($row = $stmt->fetch()) {
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($row['Field']) . '</code></td>';
            echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($row['Default'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="box error">❌ Tabelle customer_freebies existiert nicht</div>';
    }
} catch (Exception $e) {
    echo '<div class="box error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// SCHRITT 4: Session Check
echo '<h2>4. Session & User</h2>';
session_start();
if (isset($_SESSION['user_id'])) {
    echo '<div class="box success">✅ Session aktiv</div>';
    echo '<div class="box">User ID: <code>' . htmlspecialchars($_SESSION['user_id']) . '</code></div>';
    
    // User-Daten laden
    try {
        $stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo '<div class="box success">';
            echo '✅ User gefunden<br>';
            echo 'Name: ' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '<br>';
            echo 'Email: ' . htmlspecialchars($user['email']);
            echo '</div>';
        } else {
            echo '<div class="box error">❌ User nicht in DB gefunden</div>';
        }
    } catch (Exception $e) {
        echo '<div class="box error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="box warning">⚠️ Keine Session - nicht eingeloggt</div>';
}

// SCHRITT 5: Freebies laden simulieren
echo '<h2>5. Freebies laden (Simulation)</h2>';
try {
    $customer_id = $_SESSION['user_id'] ?? 1;
    
    // Limits prüfen
    $stmt = $pdo->prepare("SELECT freebie_limit, product_name FROM customer_freebie_limits WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $limitData = $stmt->fetch();
    
    if ($limitData) {
        echo '<div class="box success">';
        echo '✅ Limit gefunden<br>';
        echo 'Produkt: ' . htmlspecialchars($limitData['product_name']) . '<br>';
        echo 'Limit: ' . $limitData['freebie_limit'];
        echo '</div>';
    } else {
        echo '<div class="box warning">⚠️ Kein Limit gesetzt für User ' . $customer_id . '</div>';
    }
    
    // Custom Freebies zählen
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ? AND freebie_type = 'custom'");
    $stmt->execute([$customer_id]);
    $customCount = $stmt->fetchColumn();
    
    echo '<div class="box">Custom Freebies: ' . $customCount . '</div>';
    
    // Templates laden
    $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
    $templateCount = $stmt->fetchColumn();
    
    echo '<div class="box">Admin Templates: ' . $templateCount . '</div>';
    
} catch (Exception $e) {
    echo '<div class="box error">❌ Fehler beim Laden: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

// SCHRITT 6: PHP Errors
echo '<h2>6. PHP Fehler-Log (letzte Zeilen)</h2>';
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = file($error_log);
    $last_lines = array_slice($lines, -20);
    echo '<pre style="background:#000; padding:15px; border-radius:4px; overflow-x:auto;">';
    echo htmlspecialchars(implode('', $last_lines));
    echo '</pre>';
} else {
    echo '<div class="box warning">⚠️ Error Log nicht gefunden oder nicht konfiguriert</div>';
}

// SCHRITT 7: URLs testen
echo '<h2>7. URL Tests</h2>';
echo '<div class="box">';
echo '<a href="/customer/dashboard.php?page=freebies" target="_blank" style="color:#667eea">→ Dashboard Freebies öffnen</a><br>';
echo '<a href="/fix-customer-freebies-table.php" target="_blank" style="color:#667eea">→ Fix Script ausführen</a><br>';
echo '<a href="/customer/custom-freebie-editor.php" target="_blank" style="color:#667eea">→ Editor öffnen</a>';
echo '</div>';

echo '<div class="box" style="margin-top:40px; border-left-color:#667eea;">';
echo '<strong>Nächste Schritte:</strong><br>';
echo '1. Wenn Tabellen fehlen → <a href="/fix-customer-freebies-table.php" style="color:#667eea">Fix Script ausführen</a><br>';
echo '2. Wenn alles grün ist → <a href="/customer/dashboard.php?page=freebies" style="color:#667eea">Dashboard öffnen</a><br>';
echo '3. Bei weißem Bildschirm → PHP Error Log prüfen (siehe oben)';
echo '</div>';

echo '</body></html>';
?>
