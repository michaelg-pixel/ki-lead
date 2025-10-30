<?php
/**
 * Datenbank-Verbindungs-Test
 * Teste ob die database.php korrekt funktioniert
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB-Verbindung Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { border-left: 4px solid #10b981; }
        .error { border-left: 4px solid #ef4444; }
        .warning { border-left: 4px solid #f59e0b; }
        .info { border-left: 4px solid #3b82f6; }
        h1 { color: #1f2937; }
        h2 { color: #374151; margin-top: 0; }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 14px;
        }
        .credentials {
            background: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <h1>🔌 Datenbank-Verbindungs-Test</h1>
    
    <?php
    echo "<div class='box info'>";
    echo "<h2>📍 Schritt 1: Config-Datei prüfen</h2>";
    
    $config_path = __DIR__ . '/../config/database.php';
    
    if (file_exists($config_path)) {
        echo "✅ <strong>Config-Datei gefunden:</strong><br>";
        echo "<code>$config_path</code><br><br>";
        
        // Datei-Infos
        $size = filesize($config_path);
        $perms = substr(sprintf('%o', fileperms($config_path)), -4);
        echo "Größe: $size Bytes | Rechte: $perms<br>";
    } else {
        echo "❌ <strong>Config-Datei NICHT gefunden!</strong><br>";
        echo "Erwartet: <code>$config_path</code><br>";
        echo "</div>";
        die();
    }
    echo "</div>";
    
    echo "<div class='box warning'>";
    echo "<h2>🔐 Schritt 2: DB-Zugangsdaten prüfen</h2>";
    echo "<p>Öffne <code>$config_path</code> und stelle sicher, dass folgende Werte korrekt sind:</p>";
    echo "<div class='credentials'>";
    echo "<strong>In CloudPanel findest du die richtigen Daten:</strong><br>";
    echo "1. CloudPanel öffnen<br>";
    echo "2. Databases → app.mehr-infos-jetzt.de<br>";
    echo "3. Klicke auf <strong>'Show Credentials'</strong><br>";
    echo "4. Kopiere: Database Name, Username, Password<br>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='box info'>";
    echo "<h2>🔄 Schritt 3: Verbindung herstellen</h2>";
    echo "<p>Versuche database.php zu laden...</p>";
    
    try {
        require_once $config_path;
        
        if (isset($pdo)) {
            echo "✅ <strong>PDO-Objekt wurde erstellt!</strong><br><br>";
        } else {
            echo "❌ <strong>PDO-Variable ist nicht gesetzt!</strong><br>";
            echo "Die database.php wurde geladen, aber \$pdo existiert nicht.<br>";
            echo "</div>";
            die();
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Fehler beim Laden:</strong><br>";
        echo $e->getMessage();
        echo "</div>";
        die();
    }
    echo "</div>";
    
    echo "<div class='box success'>";
    echo "<h2>✅ Schritt 4: Verbindung testen</h2>";
    
    try {
        // Test 1: Server-Version
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "✅ <strong>MySQL Version:</strong> $version<br><br>";
        
        // Test 2: Aktuelle Datenbank
        $database = $pdo->query('SELECT DATABASE()')->fetchColumn();
        echo "✅ <strong>Aktuelle Datenbank:</strong> $database<br><br>";
        
        // Test 3: Charset
        $charset = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch();
        echo "✅ <strong>Zeichensatz:</strong> {$charset['Value']}<br><br>";
        
        // Test 4: Tabellen auflisten
        echo "<strong>📋 Verfügbare Tabellen:</strong><br>";
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "<em style='color: #f59e0b;'>⚠️ Keine Tabellen vorhanden!</em><br>";
        } else {
            echo "<ul>";
            foreach ($tables as $table) {
                // Check if freebies table exists
                if ($table === 'freebies') {
                    echo "<li><strong style='color: #10b981;'>$table</strong> ✅</li>";
                } else {
                    echo "<li>$table</li>";
                }
            }
            echo "</ul>";
        }
        
        // Test 5: Freebies Tabelle prüfen
        echo "<br><strong>🎁 Freebies-Tabelle:</strong><br>";
        $freebies_exists = in_array('freebies', $tables);
        
        if ($freebies_exists) {
            echo "✅ Tabelle existiert!<br>";
            
            $count = $pdo->query('SELECT COUNT(*) FROM freebies')->fetchColumn();
            echo "Anzahl Einträge: <strong>$count</strong><br>";
            
            if ($count > 0) {
                echo "<br><em>Templates gefunden! Das System sollte funktionieren.</em>";
            } else {
                echo "<br><em>Keine Templates vorhanden. Erstelle dein erstes Template!</em>";
            }
        } else {
            echo "<span style='color: #ef4444;'>❌ Tabelle 'freebies' nicht gefunden!</span><br>";
            echo "<br><strong>Die Tabelle muss erstellt werden:</strong><br>";
            echo "<a href='create-freebies-table.php' style='display: inline-block; background: #8B5CF6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; margin-top: 10px;'>Tabelle jetzt erstellen</a>";
        }
        
        echo "</div>";
        
        // Success Summary
        echo "<div class='box success'>";
        echo "<h2>🎉 ERFOLGREICH!</h2>";
        echo "<p><strong>Die Datenbankverbindung funktioniert einwandfrei!</strong></p>";
        
        if ($freebies_exists) {
            echo "<p>Du kannst jetzt das Freebie-System verwenden:</p>";
            echo "<a href='freebie-templates.php' style='display: inline-block; background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-right: 10px;'>→ Zu den Templates</a>";
            echo "<a href='freebie-create.php' style='display: inline-block; background: #8B5CF6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>→ Template erstellen</a>";
        } else {
            echo "<p><strong>Nächster Schritt:</strong> Erstelle die 'freebies' Tabelle!</p>";
        }
        
        echo "</div>";
        
    } catch (PDOException $e) {
        echo "</div>";
        echo "<div class='box error'>";
        echo "<h2>❌ Verbindungsfehler</h2>";
        echo "<strong>Fehler:</strong> " . $e->getMessage() . "<br><br>";
        echo "<strong>Mögliche Ursachen:</strong><ul>";
        echo "<li>Falsche Datenbank-Zugangsdaten</li>";
        echo "<li>MySQL-Server nicht erreichbar</li>";
        echo "<li>Datenbank existiert nicht</li>";
        echo "<li>Benutzer hat keine Rechte</li>";
        echo "</ul>";
        echo "</div>";
    }
    ?>
    
    <div class='box info'>
        <h2>🔧 Weitere Tests</h2>
        <p>
            <a href="debug-freebie.php">→ Vollständiger Debug</a> | 
            <a href="dashboard.php">→ Zum Dashboard</a>
        </p>
    </div>

</body>
</html>