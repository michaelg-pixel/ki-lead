<?php
/**
 * DEBUG: Datenbank-Struktur prüfen
 * Zeigt welche Tabellen und Spalten tatsächlich existieren
 */

session_start();

// Login-Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /public/login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getDBConnection();

$customer_id = $_SESSION['user_id'];

echo "<!DOCTYPE html><html><head><title>DB Struktur Debug</title>";
echo "<style>
body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
h2 { color: #ff0; border-bottom: 2px solid #ff0; padding: 10px 0; }
pre { background: #000; padding: 15px; border-radius: 5px; overflow-x: auto; }
.success { color: #0f0; }
.error { color: #f00; }
.info { color: #0ff; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { padding: 10px; text-align: left; border: 1px solid #333; }
th { background: #333; color: #ff0; }
tr:nth-child(even) { background: #222; }
</style></head><body>";

echo "<h1>🔍 Datenbank Struktur Debug</h1>";
echo "<p class='info'>User ID aus Session: <strong>$customer_id</strong></p>";
echo "<hr>";

// 1. Prüfe welche relevanten Tabellen existieren
echo "<h2>📊 Existierende Tabellen</h2>";
$tables_to_check = [
    'customer_freebies',
    'user_freebies',
    'freebies',
    'customer_freebie_limits',
    'user_freebie_limits',
    'course_access',
    'freebie_click_analytics',
    'customer_tracking'
];

$existing_tables = [];
echo "<table><tr><th>Tabelle</th><th>Status</th><th>Anzahl Einträge</th></tr>";
foreach ($tables_to_check as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        echo "<tr><td>$table</td><td class='success'>✅ Existiert</td><td>$count</td></tr>";
        $existing_tables[] = $table;
    } catch (PDOException $e) {
        echo "<tr><td>$table</td><td class='error'>❌ Existiert nicht</td><td>-</td></tr>";
    }
}
echo "</table>";

// 2. Prüfe Spaltenstruktur der wichtigsten Tabellen
echo "<h2>🔧 Spaltenstruktur</h2>";

foreach (['customer_freebies', 'user_freebies'] as $table) {
    if (in_array($table, $existing_tables)) {
        echo "<h3>Tabelle: $table</h3>";
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table><tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                $has_user_id = ($col['Field'] === 'user_id') ? 'class="success"' : '';
                $has_customer_id = ($col['Field'] === 'customer_id') ? 'class="info"' : '';
                $style = $has_user_id ?: $has_customer_id;
                echo "<tr $style>";
                echo "<td><strong>{$col['Field']}</strong></td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } catch (PDOException $e) {
            echo "<p class='error'>Fehler beim Lesen der Struktur: " . $e->getMessage() . "</p>";
        }
    }
}

// 3. Prüfe tatsächliche Daten mit BEIDEN möglichen Spaltennamen
echo "<h2>📈 Statistiken für User ID: $customer_id</h2>";

// 3.1 Freebies Count
echo "<h3>Freigeschaltete Freebies</h3>";

// Versuche mit customer_id
$freebie_count_customer = 0;
if (in_array('customer_freebies', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $freebie_count_customer = $stmt->fetchColumn();
        echo "<p class='info'>customer_freebies mit customer_id: <strong>$freebie_count_customer</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler (customer_id): " . $e->getMessage() . "</p>";
    }
}

// Versuche mit user_id in customer_freebies
if (in_array('customer_freebies', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer_freebies WHERE user_id = ?");
        $stmt->execute([$customer_id]);
        $freebie_count_user = $stmt->fetchColumn();
        echo "<p class='success'>customer_freebies mit user_id: <strong>$freebie_count_user</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler (user_id): " . $e->getMessage() . "</p>";
    }
}

// Versuche mit user_freebies
if (in_array('user_freebies', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_freebies WHERE user_id = ?");
        $stmt->execute([$customer_id]);
        $freebie_count = $stmt->fetchColumn();
        echo "<p class='success'>user_freebies mit user_id: <strong>$freebie_count</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler: " . $e->getMessage() . "</p>";
    }
}

// 3.2 Join mit freebies
echo "<h3>Freebies mit JOIN (wie in overview.php)</h3>";

// Original Query aus overview.php
if (in_array('customer_freebies', $existing_tables) && in_array('freebies', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM customer_freebies cf
            INNER JOIN freebies f ON cf.template_id = f.id
            WHERE cf.user_id = ?
        ");
        $stmt->execute([$customer_id]);
        $join_count = $stmt->fetchColumn();
        echo "<p class='success'>JOIN mit user_id: <strong>$join_count</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler JOIN (user_id): " . $e->getMessage() . "</p>";
    }
    
    // Mit customer_id versuchen
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM customer_freebies cf
            INNER JOIN freebies f ON cf.template_id = f.id
            WHERE cf.customer_id = ?
        ");
        $stmt->execute([$customer_id]);
        $join_count_customer = $stmt->fetchColumn();
        echo "<p class='info'>JOIN mit customer_id: <strong>$join_count_customer</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler JOIN (customer_id): " . $e->getMessage() . "</p>";
    }
}

// 3.3 Kurse
echo "<h3>Kurse</h3>";
if (in_array('course_access', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_access WHERE user_id = ?");
        $stmt->execute([$customer_id]);
        $course_count = $stmt->fetchColumn();
        echo "<p class='success'>course_access mit user_id: <strong>$course_count</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler: " . $e->getMessage() . "</p>";
    }
}

// 3.4 Klicks
echo "<h3>Klicks (letzte 30 Tage)</h3>";
if (in_array('freebie_click_analytics', $existing_tables)) {
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(click_count), 0) 
            FROM freebie_click_analytics 
            WHERE user_id = ?
            AND click_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$customer_id]);
        $clicks = $stmt->fetchColumn();
        echo "<p class='success'>Klicks: <strong>$clicks</strong></p>";
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler: " . $e->getMessage() . "</p>";
    }
}

// 4. Zeige aktuelle Freebies des Users
echo "<h2>📋 Deine Freebies</h2>";
if (in_array('customer_freebies', $existing_tables)) {
    // Versuche zuerst mit customer_id
    try {
        $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE customer_id = ? ORDER BY id DESC LIMIT 5");
        $stmt->execute([$customer_id]);
        $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($freebies)) {
            // Versuche mit user_id
            $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE user_id = ? ORDER BY id DESC LIMIT 5");
            $stmt->execute([$customer_id]);
            $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (!empty($freebies)) {
            echo "<p class='success'>Gefundene Freebies: " . count($freebies) . "</p>";
            echo "<table><tr><th>ID</th><th>Headline</th><th>Layout</th><th>Type</th><th>Template ID</th><th>Created</th></tr>";
            foreach ($freebies as $freebie) {
                echo "<tr>";
                echo "<td>{$freebie['id']}</td>";
                echo "<td>{$freebie['headline']}</td>";
                echo "<td>{$freebie['layout']}</td>";
                echo "<td>{$freebie['freebie_type']}</td>";
                echo "<td>{$freebie['template_id']}</td>";
                echo "<td>{$freebie['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>Keine Freebies gefunden!</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>Fehler: " . $e->getMessage() . "</p>";
    }
}

// 5. Empfehlung
echo "<h2>💡 Empfehlung</h2>";
echo "<div style='background: #333; padding: 20px; border-radius: 10px; border-left: 5px solid #ff0;'>";
echo "<p><strong>Basierend auf der Analyse:</strong></p>";

if (in_array('customer_freebies', $existing_tables) && !in_array('user_freebies', $existing_tables)) {
    echo "<p class='info'>✓ Die Tabelle heißt noch <code>customer_freebies</code> (nicht migriert zu user_freebies)</p>";
}

// Check ob customer_id oder user_id Spalte existiert
$has_customer_id_col = false;
$has_user_id_col = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'customer_id'");
    $has_customer_id_col = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'user_id'");
    $has_user_id_col = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // Ignorieren
}

if ($has_customer_id_col && !$has_user_id_col) {
    echo "<p class='error'>⚠️ Die Spalte heißt noch <code>customer_id</code> (nicht migriert zu user_id)</p>";
    echo "<p><strong>Lösung:</strong> overview.php muss <code>customer_id</code> statt <code>user_id</code> verwenden!</p>";
} elseif ($has_user_id_col && !$has_customer_id_col) {
    echo "<p class='success'>✓ Die Spalte heißt korrekt <code>user_id</code></p>";
    echo "<p><strong>Problem:</strong> Möglicherweise sind keine Freebies zugeordnet oder andere Fehler</p>";
} elseif ($has_customer_id_col && $has_user_id_col) {
    echo "<p class='info'>⚠️ BEIDE Spalten existieren! Das sollte nicht sein.</p>";
}

echo "</div>";

echo "<hr>";
echo "<p><a href='dashboard.php?page=overview' style='color: #0ff;'>← Zurück zum Dashboard</a></p>";
echo "</body></html>";
?>
