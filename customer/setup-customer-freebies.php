<?php
// Customer Freebies Tabelle Setup
require_once '../config/database.php';

try {
    // Prüfen ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    $exists = $stmt->rowCount() > 0;
    
    if (!$exists) {
        echo "Erstelle customer_freebies Tabelle...\n";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS customer_freebies (
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
        
        echo "✅ Tabelle customer_freebies erfolgreich erstellt!\n";
    } else {
        echo "✓ Tabelle customer_freebies existiert bereits\n";
        
        // Prüfe und füge fehlende Spalten hinzu
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
        
        foreach ($columns_to_check as $column => $sql) {
            $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '$column'");
            if ($stmt->rowCount() === 0) {
                try {
                    $pdo->exec($sql);
                    echo "✅ Spalte '$column' hinzugefügt\n";
                } catch (PDOException $e) {
                    echo "⚠️ Fehler beim Hinzufügen von '$column': " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n=== Setup abgeschlossen ===\n";
    echo "Die Kunden können jetzt:\n";
    echo "1. Freebie-Templates auswählen\n";
    echo "2. Templates anpassen und bearbeiten\n";
    echo "3. Eigene Versionen speichern\n";
    echo "4. Live-Vorschau nutzen\n";
    echo "5. E-Mail-Optin Code einbinden\n";
    echo "6. Verschiedene Layouts verwenden\n";
    
} catch (PDOException $e) {
    die("Datenbankfehler: " . $e->getMessage());
}
?>
