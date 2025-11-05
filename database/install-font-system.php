<?php
/**
 * FONT-SYSTEM MIGRATION - Einfache Browser-Ausführung
 * 
 * Rufe diese Datei einfach im Browser auf:
 * https://app.mehr-infos-jetzt.de/database/install-font-system.php
 */

// Direkte Datenbankverbindung
$host = 'localhost';
$database = 'lumisaas';
$username = 'lumisaas52';
$password = 'I1zx1XdL1hrWd75yu57e';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<!DOCTYPE html>
    <html lang='de'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Font-System Migration</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
            }
            .container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #1a1a2e;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
            }
            .step {
                background: #f8f9fa;
                border-left: 4px solid #667eea;
                padding: 15px;
                margin: 15px 0;
                border-radius: 4px;
            }
            .success {
                background: #d4edda;
                border-left-color: #28a745;
                color: #155724;
            }
            .error {
                background: #f8d7da;
                border-left-color: #dc3545;
                color: #721c24;
            }
            .info {
                background: #d1ecf1;
                border-left-color: #17a2b8;
                color: #0c5460;
            }
            .code {
                background: #2d2d2d;
                color: #f8f8f2;
                padding: 10px;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                overflow-x: auto;
            }
            .icon {
                font-size: 24px;
                margin-right: 10px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background: #667eea;
                color: white;
            }
            .btn {
                display: inline-block;
                padding: 12px 24px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                transition: transform 0.2s;
            }
            .btn:hover {
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎨 Font-System Migration</h1>
            <p class='subtitle'>Datenbank-Felder für Custom Freebie Fonts werden angelegt...</p>";
    
    // Schritt 1: Prüfe ob Felder bereits existieren
    echo "<div class='step'><span class='icon'>🔍</span><strong>Schritt 1:</strong> Überprüfe vorhandene Felder...</div>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'font_%'");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($existing_columns) >= 3) {
        echo "<div class='step success'><span class='icon'>✅</span>Font-Felder existieren bereits! Migration nicht nötig.</div>";
        
        echo "<table>
            <tr><th>Feld</th><th>Typ</th><th>Standard</th></tr>";
        
        $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field LIKE 'font_%'");
        while ($col = $stmt->fetch()) {
            echo "<tr>
                <td><strong>{$col['Field']}</strong></td>
                <td>{$col['Type']}</td>
                <td>{$col['Default']}</td>
            </tr>";
        }
        echo "</table>";
        
        echo "<a href='/customer/custom-freebie-editor.php?id=10' class='btn'>Zum Editor →</a>";
        echo "</div></body></html>";
        exit;
    }
    
    // Schritt 2: Füge Felder hinzu
    echo "<div class='step'><span class='icon'>🔧</span><strong>Schritt 2:</strong> Erstelle Font-Felder...</div>";
    
    $migrations = [
        "ALTER TABLE customer_freebies ADD COLUMN font_heading VARCHAR(100) DEFAULT 'Inter' AFTER cta_animation",
        "ALTER TABLE customer_freebies ADD COLUMN font_body VARCHAR(100) DEFAULT 'Inter' AFTER font_heading",
        "ALTER TABLE customer_freebies ADD COLUMN font_size ENUM('small', 'medium', 'large') DEFAULT 'medium' AFTER font_body"
    ];
    
    $success_count = 0;
    
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            $success_count++;
            
            // Feldname extrahieren
            preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
            $field_name = $matches[1] ?? 'unbekannt';
            
            echo "<div class='step success'><span class='icon'>✅</span>Feld <strong>{$field_name}</strong> erfolgreich angelegt</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<div class='step info'><span class='icon'>ℹ️</span>Feld existiert bereits - überspringe</div>";
                $success_count++;
            } else {
                echo "<div class='step error'><span class='icon'>❌</span>Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    // Schritt 3: Erstelle Index
    echo "<div class='step'><span class='icon'>📊</span><strong>Schritt 3:</strong> Erstelle Performance-Index...</div>";
    
    try {
        $pdo->exec("CREATE INDEX idx_font_settings ON customer_freebies(font_heading, font_body, font_size)");
        echo "<div class='step success'><span class='icon'>✅</span>Index erfolgreich erstellt</div>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<div class='step info'><span class='icon'>ℹ️</span>Index existiert bereits</div>";
        } else {
            echo "<div class='step error'><span class='icon'>❌</span>Index-Fehler: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    
    // Schritt 4: Verifizierung
    echo "<div class='step'><span class='icon'>🔍</span><strong>Schritt 4:</strong> Verifiziere Installation...</div>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies WHERE Field LIKE 'font_%'");
    $final_columns = $stmt->fetchAll();
    
    if (count($final_columns) >= 3) {
        echo "<div class='step success'>
            <span class='icon'>🎉</span>
            <strong>Migration erfolgreich!</strong> Alle {$success_count} Font-Felder wurden angelegt.
        </div>";
        
        echo "<table>
            <tr><th>Feld</th><th>Typ</th><th>Standard</th></tr>";
        
        foreach ($final_columns as $col) {
            echo "<tr>
                <td><strong>{$col['Field']}</strong></td>
                <td>{$col['Type']}</td>
                <td>{$col['Default']}</td>
            </tr>";
        }
        echo "</table>";
        
        echo "<div class='step info'>
            <span class='icon'>💡</span>
            <strong>Was jetzt?</strong><br><br>
            <ol style='margin-left: 20px;'>
                <li>Gehe zum Custom Freebie Editor</li>
                <li>Scrolle zu 'Schriftarten & Größe'</li>
                <li>Wähle aus 10 Webfonts und 10 Google Fonts</li>
                <li>Ändere die Schriftgröße (Klein/Mittel/Groß)</li>
                <li>Sieh dir die Live-Preview an</li>
                <li>Speichern - fertig! 🎨</li>
            </ol>
        </div>";
        
        echo "<a href='/customer/custom-freebie-editor.php?id=10' class='btn'>🚀 Zum Editor starten →</a>";
        
    } else {
        echo "<div class='step error'>
            <span class='icon'>❌</span>
            <strong>Fehler bei der Verifizierung!</strong> Nur " . count($final_columns) . " von 3 Feldern gefunden.
        </div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='step error'>
        <span class='icon'>❌</span>
        <strong>Datenbankfehler:</strong><br><br>
        <div class='code'>" . htmlspecialchars($e->getMessage()) . "</div>
    </div>";
}

echo "</div></body></html>";
?>