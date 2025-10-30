<?php
/**
 * Freebies-Tabelle Erstellen
 * Erstellt die notwendige Datenbank-Tabelle für das Freebie-System
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freebies Tabelle Erstellen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        .success { 
            background: #d1fae5; 
            border-left: 4px solid #10b981; 
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .error { 
            background: #fee2e2; 
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info { 
            background: #e0e7ff; 
            border-left: 4px solid #6366f1;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        h1 { color: #1f2937; margin-top: 0; }
        h2 { color: #374151; }
        code {
            background: #f3f4f6;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #8B5CF6, #6D28D9);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .sql-box {
            background: #1f2937;
            color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 13px;
            line-height: 1.5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎁 Freebies-Tabelle Erstellen</h1>
        
        <?php
        // Check if database.php exists
        $config_path = __DIR__ . '/../config/database.php';
        
        if (!file_exists($config_path)) {
            echo "<div class='error'>";
            echo "<strong>❌ Fehler:</strong> config/database.php nicht gefunden!<br>";
            echo "Pfad: <code>$config_path</code>";
            echo "</div>";
            echo "<p><a href='test-database.php'>→ Zurück zum DB-Test</a></p>";
            die();
        }
        
        // Load database connection
        try {
            require_once $config_path;
            
            if (!isset($pdo)) {
                throw new Exception("PDO-Verbindung nicht verfügbar!");
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ Datenbankverbindung fehlgeschlagen:</strong><br>";
            echo $e->getMessage();
            echo "</div>";
            echo "<p><a href='test-database.php'>→ Zurück zum DB-Test</a></p>";
            die();
        }
        
        // Check if table already exists
        $table_exists = false;
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
            $table_exists = $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo "<div class='error'>Fehler beim Prüfen: " . $e->getMessage() . "</div>";
        }
        
        if ($table_exists) {
            echo "<div class='info'>";
            echo "<strong>ℹ️ Die Tabelle 'freebies' existiert bereits!</strong><br><br>";
            
            // Show table info
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM freebies");
                $count = $stmt->fetchColumn();
                echo "Anzahl Einträge: <strong>$count</strong><br><br>";
                
                // Show structure
                $stmt = $pdo->query("DESCRIBE freebies");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<strong>Tabellenstruktur:</strong>";
                echo "<table>";
                echo "<tr><th>Spalte</th><th>Typ</th><th>Null</th><th>Key</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td><code>{$col['Field']}</code></td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>{$col['Key']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
            } catch (PDOException $e) {
                echo "Fehler: " . $e->getMessage();
            }
            
            echo "</div>";
            
            echo "<p style='margin-top: 30px;'>";
            echo "<a href='freebie-templates.php' class='btn'>→ Zu den Templates</a> ";
            echo "<a href='test-database.php' style='margin-left: 10px;'>← Zurück zum Test</a>";
            echo "</p>";
            
        } else {
            // Table doesn't exist - offer to create it
            
            if (isset($_POST['create_table'])) {
                // CREATE TABLE
                echo "<div class='info'>";
                echo "<h2>🔨 Erstelle Tabelle...</h2>";
                
                $sql = "CREATE TABLE `freebies` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) NOT NULL,
                  `description` text DEFAULT NULL,
                  `template_type` varchar(50) NOT NULL,
                  `design_config` longtext DEFAULT NULL,
                  `customizable_fields` longtext DEFAULT NULL,
                  `is_active` tinyint(1) DEFAULT 1,
                  `created_at` timestamp NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                try {
                    $pdo->exec($sql);
                    
                    echo "<div class='success'>";
                    echo "<h3>✅ Erfolgreich!</h3>";
                    echo "<p>Die Tabelle 'freebies' wurde erfolgreich erstellt!</p>";
                    echo "<p><strong>Das Freebie-System ist jetzt einsatzbereit!</strong></p>";
                    echo "</div>";
                    
                    echo "<p style='margin-top: 30px;'>";
                    echo "<a href='freebie-templates.php' class='btn'>→ Zu den Templates</a> ";
                    echo "<a href='freebie-create.php' class='btn' style='margin-left: 10px; background: linear-gradient(135deg, #10b981, #059669);'>→ Erstes Template erstellen</a>";
                    echo "</p>";
                    
                } catch (PDOException $e) {
                    echo "<div class='error'>";
                    echo "<h3>❌ Fehler beim Erstellen</h3>";
                    echo "<p><strong>Fehlermeldung:</strong><br>" . $e->getMessage() . "</p>";
                    echo "</div>";
                    
                    echo "<p><a href='test-database.php'>→ Zurück zum DB-Test</a></p>";
                }
                
                echo "</div>";
                
            } else {
                // Show form to create table
                echo "<div class='error'>";
                echo "<strong>❌ Die Tabelle 'freebies' existiert nicht!</strong><br>";
                echo "Diese Tabelle wird benötigt, um Templates zu speichern.";
                echo "</div>";
                
                echo "<h2>📋 SQL-Befehl</h2>";
                echo "<p>Folgende Tabelle wird erstellt:</p>";
                
                echo "<div class='sql-box'>";
                echo "CREATE TABLE `freebies` (\n";
                echo "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
                echo "  `name` varchar(255) NOT NULL,\n";
                echo "  `description` text DEFAULT NULL,\n";
                echo "  `template_type` varchar(50) NOT NULL,\n";
                echo "  `design_config` longtext DEFAULT NULL,\n";
                echo "  `customizable_fields` longtext DEFAULT NULL,\n";
                echo "  `is_active` tinyint(1) DEFAULT 1,\n";
                echo "  `created_at` timestamp NULL DEFAULT current_timestamp(),\n";
                echo "  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),\n";
                echo "  PRIMARY KEY (`id`)\n";
                echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                echo "</div>";
                
                echo "<h2>🚀 Tabelle erstellen</h2>";
                echo "<form method='POST'>";
                echo "<p><strong>Klicke auf den Button um die Tabelle automatisch zu erstellen:</strong></p>";
                echo "<button type='submit' name='create_table' class='btn' style='border: none; cursor: pointer; font-size: 16px;'>";
                echo "✨ Tabelle jetzt erstellen";
                echo "</button>";
                echo "</form>";
                
                echo "<div class='info' style='margin-top: 30px;'>";
                echo "<h3>ℹ️ Alternative: Manuelle Erstellung</h3>";
                echo "<p>Du kannst die Tabelle auch manuell in phpMyAdmin erstellen:</p>";
                echo "<ol>";
                echo "<li>Öffne phpMyAdmin in CloudPanel</li>";
                echo "<li>Wähle deine Datenbank aus</li>";
                echo "<li>Klicke auf 'SQL'</li>";
                echo "<li>Kopiere den obigen SQL-Befehl</li>";
                echo "<li>Klicke auf 'Ausführen'</li>";
                echo "</ol>";
                echo "</div>";
            }
        }
        ?>
        
    </div>
</body>
</html>