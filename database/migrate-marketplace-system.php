<?php
/**
 * Marktplatz-System Migration
 * Erstellt die notwendigen Tabellen für das Freebie-Marktplatz-Feature
 * 
 * FEATURES:
 * - Kunden können ihre Freebies auf dem Marktplatz anbieten
 * - Digistore24-Integration für Verkäufe
 * - Automatisches Kopieren von gekauften Freebies
 * - Nischen-Kategorisierung
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>";
    echo "<html><head><meta charset='UTF-8'>";
    echo "<title>Marktplatz Migration</title>";
    echo "<style>";
    echo "body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }";
    echo ".success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; border-radius: 4px; }";
    echo ".error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 4px; }";
    echo ".info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; border-radius: 4px; }";
    echo ".warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 4px; }";
    echo "pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }";
    echo "</style></head><body>";
    echo "<h1>🏪 Marktplatz-System Migration</h1>";
    
    // 1. Marketplace Freebies Tabelle erstellen
    echo "<h2>1️⃣ Erstelle marketplace_freebies Tabelle</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'marketplace_freebies'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='warning'>⚠️ Tabelle 'marketplace_freebies' existiert bereits. Überspringe...</div>";
    } else {
        $sql = "CREATE TABLE marketplace_freebies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            freebie_id INT NOT NULL,
            
            -- Marktplatz-spezifische Daten
            digistore24_link VARCHAR(500),
            description TEXT,
            course_info TEXT,
            lessons_count INT DEFAULT 0,
            course_duration VARCHAR(100),
            price DECIMAL(10,2),
            
            -- Status
            is_active BOOLEAN DEFAULT 1,
            is_approved BOOLEAN DEFAULT 0,
            
            -- Nische (für Filterung)
            niche VARCHAR(50) DEFAULT 'sonstiges',
            
            -- Verkaufs-Statistiken
            views_count INT DEFAULT 0,
            sales_count INT DEFAULT 0,
            
            -- Zeitstempel
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Foreign Keys
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (freebie_id) REFERENCES customer_freebies(id) ON DELETE CASCADE,
            
            -- Einzigartigkeit: Ein Freebie kann nur einmal im Marktplatz sein
            UNIQUE KEY unique_freebie (freebie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ Tabelle 'marketplace_freebies' erfolgreich erstellt!</div>";
    }
    
    // 2. Marketplace Purchases Tabelle (für Tracking)
    echo "<h2>2️⃣ Erstelle marketplace_purchases Tabelle</h2>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'marketplace_purchases'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='warning'>⚠️ Tabelle 'marketplace_purchases' existiert bereits. Überspringe...</div>";
    } else {
        $sql = "CREATE TABLE marketplace_purchases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            
            -- Käufer und Verkäufer
            buyer_id INT NOT NULL,
            seller_id INT NOT NULL,
            
            -- Produkt
            marketplace_freebie_id INT NOT NULL,
            original_freebie_id INT NOT NULL,
            copied_freebie_id INT,
            
            -- Digistore24 Daten
            digistore_order_id VARCHAR(100),
            digistore_product_id VARCHAR(100),
            purchase_price DECIMAL(10,2),
            
            -- Status
            status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            
            -- Zeitstempel
            purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            
            -- Foreign Keys
            FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (marketplace_freebie_id) REFERENCES marketplace_freebies(id) ON DELETE CASCADE,
            
            INDEX idx_buyer (buyer_id),
            INDEX idx_seller (seller_id),
            INDEX idx_order (digistore_order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ Tabelle 'marketplace_purchases' erfolgreich erstellt!</div>";
    }
    
    // 3. Prüfe ob customer_freebies Tabelle marketplace_original_id Spalte braucht
    echo "<h2>3️⃣ Erweitere customer_freebies Tabelle</h2>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'marketplace_original_id'");
    if ($stmt->rowCount() > 0) {
        echo "<div class='warning'>⚠️ Spalte 'marketplace_original_id' existiert bereits in customer_freebies</div>";
    } else {
        $sql = "ALTER TABLE customer_freebies 
                ADD COLUMN marketplace_original_id INT NULL AFTER freebie_id,
                ADD COLUMN marketplace_seller_id INT NULL AFTER marketplace_original_id,
                ADD COLUMN is_marketplace_copy BOOLEAN DEFAULT 0 AFTER marketplace_seller_id";
        
        $pdo->exec($sql);
        echo "<div class='success'>✅ Spalten 'marketplace_original_id', 'marketplace_seller_id', 'is_marketplace_copy' zu customer_freebies hinzugefügt!</div>";
    }
    
    // 4. Zusammenfassung
    echo "<h2>📊 Migrations-Zusammenfassung</h2>";
    echo "<div class='info'>";
    echo "<h3>Erfolgreich erstellte Strukturen:</h3>";
    echo "<ul>";
    echo "<li>✅ <strong>marketplace_freebies</strong> - Speichert Marktplatz-Angebote</li>";
    echo "<li>✅ <strong>marketplace_purchases</strong> - Tracking von Käufen</li>";
    echo "<li>✅ <strong>customer_freebies</strong> - Erweitert um Marktplatz-Felder</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🎯 Nächste Schritte</h2>";
    echo "<div class='info'>";
    echo "<ol>";
    echo "<li>✅ Migration erfolgreich - Diese Datei kann jetzt gelöscht werden</li>";
    echo "<li>⏭️ Menüeintrag 'Marktplatz' zum Dashboard hinzufügen</li>";
    echo "<li>⏭️ Marktplatz-Verwaltungsseite erstellen</li>";
    echo "<li>⏭️ Öffentliche Marktplatz-Seite erstellen</li>";
    echo "<li>⏭️ Webhook für automatisches Kopieren erweitern</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<h2>🗄️ Datenbank-Schema</h2>";
    echo "<pre>";
    echo "marketplace_freebies:\n";
    echo "├─ id (PK)\n";
    echo "├─ customer_id (FK → users)\n";
    echo "├─ freebie_id (FK → customer_freebies)\n";
    echo "├─ digistore24_link\n";
    echo "├─ description\n";
    echo "├─ course_info\n";
    echo "├─ lessons_count\n";
    echo "├─ course_duration\n";
    echo "├─ price\n";
    echo "├─ is_active\n";
    echo "├─ is_approved\n";
    echo "├─ niche\n";
    echo "├─ views_count\n";
    echo "└─ sales_count\n";
    echo "\n";
    echo "marketplace_purchases:\n";
    echo "├─ id (PK)\n";
    echo "├─ buyer_id (FK → users)\n";
    echo "├─ seller_id (FK → users)\n";
    echo "├─ marketplace_freebie_id (FK)\n";
    echo "├─ original_freebie_id\n";
    echo "├─ copied_freebie_id\n";
    echo "├─ digistore_order_id\n";
    echo "├─ purchase_price\n";
    echo "└─ status\n";
    echo "</pre>";
    
    echo "<div class='success'><h2>✅ Migration erfolgreich abgeschlossen!</h2></div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Fehler bei der Migration!</h3>";
    echo "<p><strong>Nachricht:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>