<?php
/**
 * Migration: Fügt freebie_id Spalte zur reward_definitions Tabelle hinzu
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/migrations/add_freebie_id_to_rewards.php
 */

require_once __DIR__ . '/../config/database.php';
session_start();

// Sicherheitscheck - nur für eingeloggte User
if (!isset($_SESSION['user_id'])) {
    die('❌ Nicht autorisiert. Bitte erst einloggen.');
}

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration: freebie_id zu reward_definitions</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px 20px;
            margin: 20px 0;
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
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Migration: freebie_id zu reward_definitions</h1>
        <p>Diese Migration fügt die <code>freebie_id</code> Spalte zur <code>reward_definitions</code> Tabelle hinzu.</p>
";

try {
    $pdo = getDBConnection();
    
    echo "<div class='step'>";
    echo "<strong>Schritt 1:</strong> Prüfe ob Tabelle reward_definitions existiert...<br>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_definitions'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='step error'>❌ Fehler: Tabelle reward_definitions existiert nicht!</div>";
        echo "</div></div></body></html>";
        exit;
    }
    echo "✅ Tabelle gefunden<br>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<strong>Schritt 2:</strong> Prüfe ob freebie_id Spalte bereits existiert...<br>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_definitions LIKE 'freebie_id'");
    $column_exists = $stmt->rowCount() > 0;
    
    if ($column_exists) {
        echo "<div class='step info'>ℹ️ Spalte <code>freebie_id</code> existiert bereits!</div>";
    } else {
        echo "Spalte existiert noch nicht. Wird hinzugefügt...<br>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<strong>Schritt 3:</strong> Füge freebie_id Spalte hinzu...<br>";
        
        $pdo->exec("
            ALTER TABLE reward_definitions 
            ADD COLUMN freebie_id INT(11) NULL DEFAULT NULL 
            AFTER user_id,
            ADD INDEX idx_freebie_id (freebie_id)
        ");
        
        echo "✅ Spalte erfolgreich hinzugefügt<br>";
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<strong>Schritt 4:</strong> Füge Kommentar hinzu...<br>";
        
        $pdo->exec("
            ALTER TABLE reward_definitions 
            MODIFY COLUMN freebie_id INT(11) NULL DEFAULT NULL 
            COMMENT 'Optional: Wenn gesetzt, gilt die Belohnung nur für dieses Freebie. NULL = gilt für alle Freebies'
        ");
        
        echo "✅ Kommentar hinzugefügt<br>";
        echo "</div>";
    }
    
    // Zeige aktuelle Spalten-Struktur
    echo "<div class='step success'>";
    echo "<h3>✅ Migration erfolgreich abgeschlossen!</h3>";
    echo "<p>Die Spalte <code>freebie_id</code> ist jetzt verfügbar.</p>";
    echo "</div>";
    
    echo "<div class='step info'>";
    echo "<h4>📋 Aktuelle Spalten-Struktur:</h4>";
    $stmt = $pdo->query("DESCRIBE reward_definitions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #e9ecef;'>";
    echo "<th style='padding: 8px; text-align: left; border: 1px solid #dee2e6;'>Spalte</th>";
    echo "<th style='padding: 8px; text-align: left; border: 1px solid #dee2e6;'>Typ</th>";
    echo "<th style='padding: 8px; text-align: left; border: 1px solid #dee2e6;'>Null</th>";
    echo "<th style='padding: 8px; text-align: left; border: 1px solid #dee2e6;'>Standard</th>";
    echo "</tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'><code>{$col['Field']}</code></td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$col['Type']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>{$col['Null']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #dee2e6;'>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    echo "<div class='step info'>";
    echo "<h4>📝 So funktioniert's jetzt:</h4>";
    echo "<ul>";
    echo "<li><strong>freebie_id = NULL:</strong> Belohnung gilt für ALLE Freebies</li>";
    echo "<li><strong>freebie_id = 7:</strong> Belohnung gilt NUR für Freebie mit ID 7</li>";
    echo "</ul>";
    echo "<p>Wenn du jetzt Belohnungen erstellst, kannst du optional ein Freebie zuordnen!</p>";
    echo "</div>";
    
    echo "<a href='/customer/dashboard.php?page=belohnungsstufen' class='btn'>← Zurück zu Belohnungsstufen</a>";
    
} catch (PDOException $e) {
    echo "<div class='step error'>";
    echo "<h3>❌ Fehler bei der Migration</h3>";
    echo "<p><strong>Fehlermeldung:</strong></p>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code>";
    echo "</div>";
    error_log("Migration Error: " . $e->getMessage());
}

echo "
    </div>
</body>
</html>";
?>
