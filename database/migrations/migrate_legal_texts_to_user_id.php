<?php
/**
 * Migration: legal_texts customer_id → user_id
 * 
 * Dieses Script migriert die legal_texts Tabelle von customer_id zu user_id
 * Aufruf: https://app.mehr-infos-jetzt.de/database/migrations/migrate_legal_texts_to_user_id.php
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Legal Texts Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #7C3AED;
            margin-bottom: 30px;
        }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #7C3AED;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #6D28D9;
        }
        .step {
            margin-bottom: 25px;
            padding-left: 30px;
            position: relative;
        }
        .step::before {
            content: '✓';
            position: absolute;
            left: 0;
            top: 0;
            width: 24px;
            height: 24px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        .step-pending::before {
            content: '○';
            background: #6c757d;
        }
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            border-left: 4px solid #7C3AED;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Legal Texts Migration: customer_id → user_id</h1>";

    // Schritt 1: Prüfe ob Tabelle existiert
    echo "<div class='step'>
            <h3>Schritt 1: Prüfe Tabelle 'legal_texts'</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'legal_texts'");
    if (!$stmt->fetch()) {
        echo "<div class='error'>❌ Tabelle 'legal_texts' existiert nicht!</div>";
        echo "<p>Bitte führe zuerst das Setup-Script aus:</p>";
        echo "<a href='/setup/setup-legal-texts.php' class='btn'>Setup ausführen</a>";
        echo "</div></div></body></html>";
        exit;
    }
    echo "<div class='success'>✅ Tabelle 'legal_texts' gefunden</div>";
    echo "</div>";

    // Schritt 2: Prüfe aktuelle Spalten
    echo "<div class='step'>
            <h3>Schritt 2: Prüfe Spalten-Struktur</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM legal_texts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_customer_id = false;
    $has_user_id = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'customer_id') {
            $has_customer_id = true;
        }
        if ($column['Field'] === 'user_id') {
            $has_user_id = true;
        }
    }
    
    if ($has_user_id && !$has_customer_id) {
        echo "<div class='info'>ℹ️ Migration bereits durchgeführt - Tabelle verwendet bereits 'user_id'</div>";
        echo "</div>";
        
        echo "<div class='success'>
                <strong>✅ Keine Migration notwendig!</strong><br><br>
                Die Tabelle 'legal_texts' verwendet bereits die Spalte 'user_id'.
              </div>";
        
        echo "<a href='/customer/legal-texts.php' class='btn'>Zu den Rechtstexten</a>";
        echo "</div></body></html>";
        exit;
    }
    
    if ($has_customer_id) {
        echo "<div class='warning'>⚠️ Spalte 'customer_id' gefunden - Migration notwendig</div>";
    }
    
    if ($has_user_id) {
        echo "<div class='error'>❌ Spalte 'user_id' existiert bereits - manuelles Eingreifen erforderlich</div>";
        echo "</div></div></body></html>";
        exit;
    }
    
    echo "</div>";

    // Schritt 3: Zähle Einträge
    echo "<div class='step'>
            <h3>Schritt 3: Zähle vorhandene Rechtstexte</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM legal_texts");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $count = $result['count'];
    
    echo "<div class='info'>📊 Gefundene Rechtstexte: <strong>{$count}</strong></div>";
    echo "</div>";

    // Schritt 4: Migration durchführen
    echo "<div class='step'>
            <h3>Schritt 4: Migration durchführen</h3>";
    
    $pdo->beginTransaction();
    
    try {
        // 4.1: Spalte umbenennen
        echo "<p>→ Benenne Spalte 'customer_id' zu 'user_id' um...</p>";
        $pdo->exec("ALTER TABLE `legal_texts` CHANGE COLUMN `customer_id` `user_id` INT(11) NOT NULL");
        echo "<div class='success'>✅ Spalte umbenannt</div>";
        
        // 4.2: Unique Constraint aktualisieren
        echo "<p>→ Aktualisiere Unique Constraint...</p>";
        try {
            $pdo->exec("ALTER TABLE `legal_texts` DROP INDEX `unique_customer`");
            echo "✓ Alter Constraint entfernt<br>";
        } catch (PDOException $e) {
            echo "ℹ️ Alter Constraint existiert nicht<br>";
        }
        $pdo->exec("ALTER TABLE `legal_texts` ADD UNIQUE KEY `unique_user` (`user_id`)");
        echo "<div class='success'>✅ Unique Constraint aktualisiert</div>";
        
        // 4.3: Index aktualisieren
        echo "<p>→ Aktualisiere Index...</p>";
        try {
            $pdo->exec("ALTER TABLE `legal_texts` DROP INDEX `idx_customer_id`");
            echo "✓ Alter Index entfernt<br>";
        } catch (PDOException $e) {
            echo "ℹ️ Alter Index existiert nicht<br>";
        }
        $pdo->exec("ALTER TABLE `legal_texts` ADD KEY `idx_user_id` (`user_id`)");
        echo "<div class='success'>✅ Index aktualisiert</div>";
        
        $pdo->commit();
        echo "</div>";
        
        // Schritt 5: Bestätigung
        echo "<div class='step'>
                <h3>Schritt 5: Migration abgeschlossen!</h3>
                <div class='success'>
                    <strong>🎉 Migration erfolgreich!</strong><br><br>
                    Die Tabelle 'legal_texts' wurde erfolgreich von 'customer_id' zu 'user_id' migriert.<br><br>
                    <strong>Änderungen:</strong><br>
                    ✓ Spalte 'customer_id' → 'user_id' umbenannt<br>
                    ✓ Unique Constraint aktualisiert<br>
                    ✓ Index aktualisiert<br>
                    ✓ {$count} Rechtstexte bleiben erhalten<br><br>
                    <strong>Nächste Schritte:</strong><br>
                    1. Gehe zu den Rechtstexten und prüfe ob alles funktioniert<br>
                    2. Die Links zu Impressum und Datenschutz sind bereits aktualisiert<br>
                </div>
              </div>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<div class='error'>
                ❌ Migration fehlgeschlagen!<br><br>
                <strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "
              </div>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }

    // Aktuelle Struktur anzeigen
    echo "<div class='info'>
            <strong>📋 Aktuelle Tabellenstruktur:</strong><br>
            <pre>";
    $stmt = $pdo->query("DESCRIBE legal_texts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo str_pad("Spalte", 20) . str_pad("Typ", 20) . str_pad("Null", 10) . "\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 20) . 
             str_pad($column['Null'], 10) . "\n";
    }
    echo "</pre>
          </div>";
    
    echo "<a href='/customer/legal-texts.php' class='btn'>📄 Zu den Rechtstexten</a>
          <a href='/customer/dashboard.php' class='btn' style='background: #10B981;'>🏠 Zum Dashboard</a>";
    
    echo "</div>
</body>
</html>";

} catch (PDOException $e) {
    echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Migration Fehler</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>❌ Migration Fehler</h1>
        <div class='error'>
            <strong>Datenbankfehler:</strong><br>
            " . htmlspecialchars($e->getMessage()) . "
        </div>
        <p>Bitte überprüfe die Datenbank-Verbindung und versuche es erneut.</p>
    </div>
</body>
</html>";
}
?>
