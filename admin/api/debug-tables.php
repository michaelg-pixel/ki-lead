<?php
/**
 * DEBUG-SCRIPT: Zeigt alle Tabellen und deren Inhalte für Kurs ID 5
 */

session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('❌ Zugriff verweigert');
}

$pdo = getDBConnection();
$course_id = 5;

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Tabellen Debug</title>
    <style>
        body { 
            background: #0f0f1e;
            color: #e0e0e0; 
            font-family: monospace;
            padding: 20px;
        }
        .box {
            background: rgba(26, 26, 46, 0.9);
            border: 1px solid rgba(168, 85, 247, 0.3);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 8px;
        }
        h2 { color: #a855f7; }
        table { 
            width: 100%; 
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td { 
            padding: 8px; 
            text-align: left; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        th { color: #c084fc; }
        .count { 
            font-size: 2rem; 
            color: #22c55e;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🔍 Tabellen Debug - Kurs ID <?= $course_id ?></h1>

    <?php
    // Tabellen zu prüfen
    $tables = [
        'modules' => 'course_id',
        'course_modules' => 'course_id',
        'lessons' => 'module_id',
        'course_lessons' => 'module_id',
        'lesson_videos' => 'lesson_id'
    ];

    foreach ($tables as $table => $filter_column) {
        echo "<div class='box'>";
        echo "<h2>📋 Tabelle: {$table}</h2>";
        
        try {
            if ($table === 'modules' || $table === 'course_modules') {
                // Module filtern nach course_id
                $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$filter_column} = ?");
                $stmt->execute([$course_id]);
            } elseif ($table === 'lessons' || $table === 'course_lessons') {
                // Lektionen: Erst Module holen, dann Lektionen
                $module_table = ($table === 'lessons') ? 'modules' : 'course_modules';
                $stmt = $pdo->prepare("SELECT id FROM {$module_table} WHERE course_id = ?");
                $stmt->execute([$course_id]);
                $module_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($module_ids)) {
                    $placeholders = implode(',', array_fill(0, count($module_ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$filter_column} IN ({$placeholders})");
                    $stmt->execute($module_ids);
                } else {
                    $stmt = $pdo->query("SELECT * FROM {$table} WHERE 1=0");
                }
            } else {
                // lesson_videos: Alle anzeigen
                $stmt = $pdo->query("SELECT * FROM {$table}");
            }
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($rows);
            
            echo "<div class='count'>{$count} Einträge</div>";
            
            if ($count > 0) {
                echo "<table>";
                echo "<tr>";
                foreach (array_keys($rows[0]) as $column) {
                    echo "<th>{$column}</th>";
                }
                echo "</tr>";
                
                foreach ($rows as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        $display = is_null($value) ? '<i>NULL</i>' : htmlspecialchars(substr($value, 0, 100));
                        echo "<td>{$display}</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            }
        } catch (PDOException $e) {
            echo "<p style='color: #f87171;'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
    }
    ?>

    <div class="box">
        <h2>🎯 Nächster Schritt</h2>
        <p>Basierend auf den Ergebnissen oben können wir entscheiden, welche Tabellen verwendet werden sollen.</p>
    </div>
</body>
</html>
