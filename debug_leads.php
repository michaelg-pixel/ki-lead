<?php
require_once __DIR__ . '/config/database.php';

// Passwort-Schutz
if (!isset($_GET['pw']) || $_GET['pw'] !== 'test123') {
    die('Unauthorized');
}

$pdo = getDBConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Lead Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #fff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #333; padding: 10px; text-align: left; }
        td { border: 1px solid #444; padding: 8px; }
        tr:nth-child(even) { background: #222; }
    </style>
</head>
<body>
<h1>Lead Database</h1>
<?php
try {
    $stmt = $pdo->query("SELECT * FROM lead_users ORDER BY created_at DESC LIMIT 20");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($leads) > 0) {
        echo "<table><tr>";
        foreach (array_keys($leads[0]) as $col) {
            echo "<th>{$col}</th>";
        }
        echo "</tr>";
        
        foreach ($leads as $lead) {
            echo "<tr>";
            foreach ($lead as $val) {
                echo "<td>{$val}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Keine Leads gefunden</p>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
</body>
</html>
