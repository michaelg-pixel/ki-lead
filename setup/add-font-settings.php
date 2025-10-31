<?php
/**
 * EINMALIG AUSFÜHREN: Font-Settings zu Freebies hinzufügen
 * URL: /setup/add-font-settings.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Font-Settings Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 { color: #333; }
        .success { color: #22c55e; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .info { color: #3b82f6; }
        pre {
            background: #f9fafb;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔤 Font-Settings für Freebies</h1>
        <p>Dieses Script fügt Schriftarten und Schriftgrößen-Felder zur Freebies-Tabelle hinzu.</p>
    </div>

    <div class="box">
        <h2>Ausführung...</h2>
        <?php
        try {
            // SQL-Statements ausführen
            $statements = [
                // Preheadline Font & Size
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER body_font",
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS preheadline_size INT DEFAULT 14 AFTER preheadline_font",
                
                // Headline Font & Size
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size",
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS headline_size INT DEFAULT 48 AFTER headline_font",
                
                // Subheadline Font & Size
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size",
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS subheadline_size INT DEFAULT 20 AFTER subheadline_font",
                
                // Bulletpoints Font & Size
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size",
                "ALTER TABLE freebies ADD COLUMN IF NOT EXISTS bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font",
            ];

            foreach ($statements as $sql) {
                $pdo->exec($sql);
                echo "<p class='success'>✅ " . htmlspecialchars(substr($sql, 0, 80)) . "...</p>";
            }

            // Bestehende Einträge aktualisieren
            $updateSql = "UPDATE freebies 
                SET 
                    preheadline_font = COALESCE(preheadline_font, 'Poppins'),
                    preheadline_size = COALESCE(preheadline_size, 14),
                    headline_font = COALESCE(headline_font, 'Poppins'),
                    headline_size = COALESCE(headline_size, 48),
                    subheadline_font = COALESCE(subheadline_font, 'Poppins'),
                    subheadline_size = COALESCE(subheadline_size, 20),
                    bulletpoints_font = COALESCE(bulletpoints_font, 'Poppins'),
                    bulletpoints_size = COALESCE(bulletpoints_size, 16)";
            
            $pdo->exec($updateSql);
            echo "<p class='success'>✅ Bestehende Einträge wurden aktualisiert</p>";

            // Tabellen-Info anzeigen
            echo "<h3 class='info'>📊 Tabellen-Status:</h3>";
            $stmt = $pdo->query("DESCRIBE freebies");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<pre>";
            $fontColumns = array_filter($columns, function($col) {
                return strpos($col['Field'], 'font') !== false || strpos($col['Field'], 'size') !== false;
            });
            
            echo "Font-Spalten in der Freebies-Tabelle:\n\n";
            foreach ($fontColumns as $col) {
                echo sprintf("%-25s %-20s %s\n", 
                    $col['Field'], 
                    $col['Type'], 
                    $col['Default'] ? "Default: {$col['Default']}" : ''
                );
            }
            echo "</pre>";

            echo "<h2 class='success'>✅ Setup erfolgreich abgeschlossen!</h2>";
            echo "<p>Du kannst jetzt im Freebie-Editor Schriftarten und -größen auswählen.</p>";

        } catch (PDOException $e) {
            echo "<p class='error'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="box">
        <h3>⚠️ Hinweis</h3>
        <p>Dieses Script sollte nur <strong>einmal</strong> ausgeführt werden.</p>
        <p>Nach erfolgreicher Ausführung kannst du diese Datei löschen oder umbenennen.</p>
    </div>
</body>
</html>
