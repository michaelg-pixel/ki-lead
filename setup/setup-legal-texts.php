<?php
/**
 * Setup-Script für Rechtstexte-System (Legal Texts)
 * Erstellt die notwendige Datenbank-Tabelle für Impressum und Datenschutzerklärung
 * 
 * Aufruf: /setup/setup-legal-texts.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Legal Texts Setup</title>
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
        }
        .btn:hover {
            background: #6D28D9;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Legal Texts Setup</h1>";

    // 1. Erstelle legal_texts Tabelle
    echo "<h3>1. Erstelle legal_texts Tabelle...</h3>";
    
    $sql_legal_texts = "
    CREATE TABLE IF NOT EXISTS `legal_texts` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `customer_id` INT(11) NOT NULL,
        `impressum` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        `datenschutz` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_customer` (`customer_id`),
        KEY `idx_customer_id` (`customer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_legal_texts);
    echo "<div class='success'>✅ Tabelle 'legal_texts' erfolgreich erstellt/überprüft!</div>";

    // 2. Füge customer_id zu freebies hinzu (falls nicht vorhanden)
    echo "<h3>2. Erweitere freebies Tabelle (optional)...</h3>";
    
    try {
        // Prüfe ob customer_id Spalte existiert
        $stmt = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'customer_id'");
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            $sql_alter = "ALTER TABLE `freebies` 
                         ADD COLUMN `customer_id` INT(11) DEFAULT NULL AFTER `id`,
                         ADD KEY `idx_customer_id` (`customer_id`)";
            $pdo->exec($sql_alter);
            echo "<div class='success'>✅ Spalte 'customer_id' zu 'freebies' hinzugefügt!</div>";
        } else {
            echo "<div class='info'>ℹ️ Spalte 'customer_id' existiert bereits in 'freebies'</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='info'>ℹ️ Freebies-Tabelle konnte nicht erweitert werden (möglicherweise bereits vorhanden): " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    // 3. Zusammenfassung
    echo "<h3>3. Setup abgeschlossen!</h3>";
    echo "<div class='success'>
        <strong>✅ Legal Texts System erfolgreich eingerichtet!</strong><br><br>
        
        <strong>Was wurde eingerichtet:</strong><br>
        ✓ Tabelle 'legal_texts' für Impressum und Datenschutzerklärung<br>
        ✓ Verknüpfung mit Kunden über customer_id<br>
        ✓ Automatische Timestamps für Änderungsverfolgung<br><br>
        
        <strong>Nächste Schritte:</strong><br>
        1. Gehe zum <a href='/customer/dashboard.php' style='color: #7C3AED;'>Kunden-Dashboard</a><br>
        2. Klicke auf 'Rechtstexte' im Menü (3. Position)<br>
        3. Füge dein Impressum und deine Datenschutzerklärung ein<br>
        4. Die Texte werden automatisch auf allen Freebie-Seiten verlinkt!<br><br>
        
        <strong>Hinweis:</strong><br>
        Nutze die e-recht24 Generatoren auf der Rechtstexte-Seite für rechtssichere Texte!
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
    <title>Setup Fehler</title>
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
        <h1>❌ Setup Fehler</h1>
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