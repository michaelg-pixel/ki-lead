<?php
/**
 * Database Table Structure Checker
 * Zeigt alle vorhandenen Tabellen und ihre Struktur
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>DB Structure Check</title>";
echo "<style>body{font-family:Arial;max-width:1200px;margin:20px auto;padding:20px;background:#f5f5f5}";
echo "table{width:100%;border-collapse:collapse;margin:20px 0;background:white}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left}";
echo "th{background:#667eea;color:white}";
echo ".box{background:white;padding:20px;border-radius:8px;margin:20px 0;box-shadow:0 2px 4px rgba(0,0,0,0.1)}</style>";
echo "</head><body>";

echo "<h1>🔍 Datenbank-Struktur Analyse</h1>";

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    echo "<div class='box' style='background:#d1fae5;border-left:4px solid #10b981'>";
    echo "✅ Datenbankverbindung erfolgreich";
    echo "</div>";
} catch (Exception $e) {
    die("<div class='box' style='background:#fee2e2;border-left:4px solid #ef4444'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</div></body></html>");
}

// Alle Tabellen auflisten
echo "<div class='box'>";
echo "<h2>📊 Alle Tabellen in der Datenbank</h2>";
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<table>";
echo "<tr><th>#</th><th>Tabellenname</th><th>Anzahl Zeilen</th></tr>";
foreach ($tables as $index => $table) {
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $count_stmt->fetchColumn();
    } catch (Exception $e) {
        $count = 'N/A';
    }
    echo "<tr><td>" . ($index + 1) . "</td><td><strong>$table</strong></td><td>$count</td></tr>";
}
echo "</table>";
echo "</div>";

// Nach User-Tabellen suchen
echo "<div class='box'>";
echo "<h2>👥 User/Customer Tabellen</h2>";
$user_tables = array_filter($tables, function($table) {
    return stripos($table, 'user') !== false || stripos($table, 'customer') !== false;
});

if (!empty($user_tables)) {
    echo "<p style='color:#10b981;font-weight:bold'>Gefundene User-Tabellen:</p>";
    echo "<ul>";
    foreach ($user_tables as $table) {
        echo "<li><strong>$table</strong>";
        
        // Struktur anzeigen
        echo "<table style='margin:10px 0;width:100%'>";
        echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Extra</th></tr>";
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color:#ef4444'>⚠️ Keine User/Customer Tabellen gefunden!</p>";
}
echo "</div>";

// Nach ID-Spalten in allen Tabellen suchen
echo "<div class='box'>";
echo "<h2>🔑 Tabellen mit 'id' Spalte (mögliche User-Tabellen)</h2>";
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $has_id = false;
        foreach ($columns as $col) {
            if ($col['Field'] === 'id' && $col['Key'] === 'PRI') {
                $has_id = true;
                break;
            }
        }
        
        if ($has_id) {
            echo "<h3>$table</h3>";
            echo "<table style='width:100%'>";
            echo "<tr><th>Spalte</th><th>Typ</th></tr>";
            foreach ($columns as $col) {
                echo "<tr><td>" . htmlspecialchars($col['Field']) . "</td><td>" . htmlspecialchars($col['Type']) . "</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        // Skip
    }
}
echo "</div>";

// Empfehlung
echo "<div class='box' style='background:#fef3c7;border-left:4px solid #f59e0b'>";
echo "<h2>💡 Empfehlung</h2>";
echo "<p>Basierend auf der Analyse:</p>";

if (in_array('customers', $tables)) {
    echo "<p style='color:#10b981;font-weight:bold'>✅ Tabelle 'customers' existiert!</p>";
    echo "<p>Die Migration sollte funktionieren. Versuche es nochmal oder prüfe die genaue Fehlermeldung.</p>";
} elseif (!empty($user_tables)) {
    echo "<p style='color:#f59e0b;font-weight:bold'>⚠️ Tabelle 'customers' existiert nicht, aber es gibt andere User-Tabellen:</p>";
    echo "<ul>";
    foreach ($user_tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    echo "<p>Die Migration muss angepasst werden, um die korrekte Tabelle zu referenzieren.</p>";
} else {
    echo "<p style='color:#ef4444;font-weight:bold'>❌ Keine User-Tabelle gefunden!</p>";
    echo "<p>Es muss zuerst eine User/Customer Tabelle erstellt werden.</p>";
}
echo "</div>";

echo "</body></html>";
?>