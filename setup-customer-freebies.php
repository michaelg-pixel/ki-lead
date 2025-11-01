<?php
/**
 * Customer Freebies System Setup
 * Aufruf: https://app.mehr-infos-jetzt.de/setup-customer-freebies.php
 */

require_once __DIR__ . '/config/database.php';

// Sicherheitscheck - nur einmalig ausführbar
$setup_complete_file = __DIR__ . '/.customer_freebies_setup_complete';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Freebies Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1a1a2e;
            margin-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        pre {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        .step {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🎁 Customer Freebies System Setup</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Richtet das Freebie-Editor-System für Kunden ein</p>';

try {
    // Prüfen ob bereits durchgeführt
    if (file_exists($setup_complete_file)) {
        echo '<div class="status warning">';
        echo '<span style="font-size: 24px;">⚠️</span>';
        echo '<div>';
        echo '<strong>Setup bereits durchgeführt</strong><br>';
        echo 'Dieses Setup wurde bereits erfolgreich ausgeführt.';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="status info">';
        echo '<span style="font-size: 24px;">ℹ️</span>';
        echo '<div>';
        echo '<strong>Erneut ausführen?</strong><br>';
        echo 'Wenn du das Setup erneut ausführen möchtest, lösche die Datei: <code>.customer_freebies_setup_complete</code>';
        echo '</div>';
        echo '</div>';
        
        echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<div class="step">';
    echo '<strong>Schritt 1:</strong> Prüfe Datenbank-Verbindung...';
    echo '</div>';
    
    if (!isset($pdo)) {
        throw new Exception('Datenbankverbindung konnte nicht hergestellt werden');
    }
    
    echo '<div class="status success">';
    echo '✅ Datenbankverbindung erfolgreich';
    echo '</div>';
    
    // Prüfen ob Tabelle existiert
    echo '<div class="step">';
    echo '<strong>Schritt 2:</strong> Prüfe customer_freebies Tabelle...';
    echo '</div>';
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo '<div class="status info">';
        echo 'ℹ️ Tabelle existiert noch nicht - wird erstellt...';
        echo '</div>';
        
        $pdo->exec("
            CREATE TABLE customer_freebies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                template_id INT NOT NULL,
                headline VARCHAR(255) NOT NULL,
                subheadline VARCHAR(500),
                preheadline VARCHAR(255),
                bullet_points TEXT,
                cta_text VARCHAR(255) NOT NULL,
                layout VARCHAR(50) DEFAULT 'hybrid',
                background_color VARCHAR(20) DEFAULT '#FFFFFF',
                primary_color VARCHAR(20) DEFAULT '#8B5CF6',
                raw_code TEXT,
                unique_id VARCHAR(100) NOT NULL,
                url_slug VARCHAR(255),
                mockup_image_url VARCHAR(500),
                freebie_clicks INT DEFAULT 0,
                thank_you_clicks INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_customer (customer_id),
                INDEX idx_template (template_id),
                INDEX idx_unique (unique_id),
                UNIQUE KEY unique_customer_template (customer_id, template_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebies erfolgreich erstellt';
        echo '</div>';
    } else {
        echo '<div class="status success">';
        echo '✅ Tabelle customer_freebies existiert bereits';
        echo '</div>';
        
        // Prüfe und füge fehlende Spalten hinzu
        echo '<div class="step">';
        echo '<strong>Schritt 3:</strong> Prüfe Tabellen-Struktur...';
        echo '</div>';
        
        $columns_to_check = [
            'preheadline' => "ALTER TABLE customer_freebies ADD COLUMN preheadline VARCHAR(255) AFTER subheadline",
            'layout' => "ALTER TABLE customer_freebies ADD COLUMN layout VARCHAR(50) DEFAULT 'hybrid' AFTER cta_text",
            'background_color' => "ALTER TABLE customer_freebies ADD COLUMN background_color VARCHAR(20) DEFAULT '#FFFFFF' AFTER layout",
            'primary_color' => "ALTER TABLE customer_freebies ADD COLUMN primary_color VARCHAR(20) DEFAULT '#8B5CF6' AFTER background_color",
            'raw_code' => "ALTER TABLE customer_freebies ADD COLUMN raw_code TEXT AFTER primary_color",
            'url_slug' => "ALTER TABLE customer_freebies ADD COLUMN url_slug VARCHAR(255) AFTER unique_id",
            'mockup_image_url' => "ALTER TABLE customer_freebies ADD COLUMN mockup_image_url VARCHAR(500) AFTER url_slug",
            'freebie_clicks' => "ALTER TABLE customer_freebies ADD COLUMN freebie_clicks INT DEFAULT 0 AFTER mockup_image_url",
            'thank_you_clicks' => "ALTER TABLE customer_freebies ADD COLUMN thank_you_clicks INT DEFAULT 0 AFTER freebie_clicks"
        ];
        
        $updates_made = 0;
        foreach ($columns_to_check as $column => $sql) {
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '$column'");
            if ($stmt->rowCount() === 0) {
                try {
                    $pdo->exec($sql);
                    echo '<div class="status success">';
                    echo "✅ Spalte '$column' hinzugefügt";
                    echo '</div>';
                    $updates_made++;
                } catch (PDOException $e) {
                    echo '<div class="status error">';
                    echo "❌ Fehler beim Hinzufügen von '$column': " . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
            }
        }
        
        if ($updates_made === 0) {
            echo '<div class="status success">';
            echo '✅ Alle Spalten sind bereits vorhanden';
            echo '</div>';
        }
    }
    
    // Prüfe ob freebies Tabelle existiert
    echo '<div class="step">';
    echo '<strong>Schritt 4:</strong> Prüfe freebies Tabelle (Admin Templates)...';
    echo '</div>';
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'freebies'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM freebies");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo '<div class="status success">';
        echo "✅ Freebies Tabelle gefunden mit {$result['count']} Template(s)";
        echo '</div>';
    } else {
        echo '<div class="status warning">';
        echo '⚠️ Freebies Tabelle nicht gefunden - Admins müssen erst Templates erstellen';
        echo '</div>';
    }
    
    // Setup als abgeschlossen markieren
    file_put_contents($setup_complete_file, date('Y-m-d H:i:s'));
    
    echo '<div class="step" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
    echo '<strong style="color: #065f46; font-size: 18px;">🎉 Setup erfolgreich abgeschlossen!</strong>';
    echo '</div>';
    
    echo '<div class="status info">';
    echo '<span style="font-size: 24px;">ℹ️</span>';
    echo '<div>';
    echo '<strong>Was können Kunden jetzt tun?</strong><br>';
    echo '<ol style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">';
    echo '<li>Freebie-Templates in der Übersicht auswählen</li>';
    echo '<li>Templates im Editor anpassen (Texte, Farben, Layouts)</li>';
    echo '<li>E-Mail-Optin Code einbinden (Quentn, Klicktipp, etc.)</li>';
    echo '<li>Live-Vorschau nutzen</li>';
    echo '<li>Eigene Versionen speichern und teilen</li>';
    echo '</ol>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="status info">';
    echo '<span style="font-size: 24px;">📋</span>';
    echo '<div>';
    echo '<strong>Nächste Schritte:</strong><br>';
    echo '<ul style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">';
    echo '<li>Admin: Freebie-Templates erstellen unter <code>/admin/dashboard.php?page=freebies</code></li>';
    echo '<li>Kunde: Templates nutzen unter <code>/customer/dashboard.php?page=freebies</code></li>';
    echo '<li>Dieses Setup-Script kann gelöscht werden: <code>/setup-customer-freebies.php</code></li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    
    echo '<a href="/customer/dashboard.php?page=freebies" class="button">Jetzt Freebies ansehen</a>';
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div>';
    echo '<strong>Fehler beim Setup:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
    
    echo '<pre>Stack Trace:' . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</div></body></html>';
?>
