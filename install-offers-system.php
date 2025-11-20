<?php
/**
 * Installation Script: Offers-System
 * Erstellt die Datenbanktabelle für Angebots-Laufschriften
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html lang='de'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Offers-System Installation</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 {
                color: #667eea;
                margin-bottom: 10px;
            }
            .subtitle {
                color: #666;
                margin-bottom: 30px;
            }
            .status {
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            .error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            .warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                color: #856404;
            }
            .info {
                background: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
            .icon {
                font-size: 20px;
            }
            .button {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 12px 30px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                margin-top: 20px;
                transition: transform 0.2s;
            }
            .button:hover {
                transform: translateY(-2px);
            }
            code {
                background: #f4f4f4;
                padding: 2px 6px;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🎯 Offers-System Installation</h1>
            <p class='subtitle'>Installiert die Angebots-Laufschrift Funktion</p>";
    
    // Prüfen ob Tabelle bereits existiert
    $check_table = $pdo->query("SHOW TABLES LIKE 'offers'")->fetchColumn();
    
    if ($check_table) {
        echo "<div class='status warning'>
                <span class='icon'>⚠️</span>
                <span>Tabelle 'offers' existiert bereits!</span>
              </div>";
        
        $count = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
        echo "<div class='status info'>
                <span class='icon'>ℹ️</span>
                <span>Aktuell {$count} Angebot(e) in der Datenbank</span>
              </div>";
    } else {
        // Tabelle erstellen
        $sql = "CREATE TABLE IF NOT EXISTS offers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            button_text VARCHAR(100) NOT NULL DEFAULT 'Jetzt ansehen',
            button_link VARCHAR(500) NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        echo "<div class='status success'>
                <span class='icon'>✅</span>
                <span>Tabelle 'offers' erfolgreich erstellt!</span>
              </div>";
        
        // Standard-Angebot einfügen
        $stmt = $pdo->prepare("INSERT INTO offers (title, description, button_text, button_link, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'Neu: KI Avatar Business Masterclass',
            'Lerne, wie du mit KI-Avataren automatisierte Geschäfte aufbaust. Jetzt 50% Rabatt für Mitglieder!',
            'Jetzt starten',
            'https://mehr-infos-jetzt.de',
            1
        ]);
        
        echo "<div class='status success'>
                <span class='icon'>✅</span>
                <span>Standard-Angebot wurde hinzugefügt!</span>
              </div>";
    }
    
    echo "
            <div class='status info'>
                <span class='icon'>📋</span>
                <div>
                    <strong>Nächste Schritte:</strong><br>
                    1. Gehe zu <code>Admin Dashboard → Angebote</code><br>
                    2. Erstelle oder bearbeite Angebote<br>
                    3. Die Laufschrift erscheint im Customer Dashboard
                </div>
            </div>
            
            <a href='/admin/dashboard.php?page=offers' class='button'>
                🎯 Zu den Angeboten
            </a>
            
            <a href='/admin/dashboard.php' class='button' style='background: #6c757d; margin-left: 10px;'>
                📊 Zum Dashboard
            </a>
        </div>
    </body>
    </html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
    <html lang='de'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Installations-Fehler</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 16px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
            h1 { color: #dc3545; }
            .error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
            }
            code {
                background: #f4f4f4;
                padding: 10px;
                border-radius: 4px;
                display: block;
                margin-top: 10px;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>❌ Installations-Fehler</h1>
            <div class='error'>
                <strong>Fehler beim Erstellen der Tabelle:</strong>
                <code>" . htmlspecialchars($e->getMessage()) . "</code>
            </div>
            <p style='margin-top: 20px; color: #666;'>
                Bitte überprüfe die Datenbankverbindung und die Berechtigungen.
            </p>
        </div>
    </body>
    </html>";
}
?>
