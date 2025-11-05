<?php
/**
 * Migration: Empfehlungsprogramm Slots Tabelle
 * Erstellt die Tabelle für Referral-Slots falls nicht vorhanden
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Empfehlungsprogramm-Slots Migration</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🚀 Empfehlungsprogramm-Slots Migration</h1>";
    
    // Prüfen ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'customer_referral_slots'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='step'><strong>Schritt 1:</strong> Erstelle Tabelle <code>customer_referral_slots</code>...</div>";
        
        $pdo->exec("
            CREATE TABLE customer_referral_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                total_slots INT NOT NULL DEFAULT 1 COMMENT 'Gesamtanzahl verfügbarer Slots',
                used_slots INT NOT NULL DEFAULT 0 COMMENT 'Anzahl bereits genutzter Slots',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_customer (customer_id),
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_customer_id (customer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Empfehlungsprogramm-Slots pro Kunde'
        ");
        
        echo "<div class='success'>✅ Tabelle <code>customer_referral_slots</code> erfolgreich erstellt!</div>";
    } else {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Tabelle <code>customer_referral_slots</code> existiert bereits.</div>";
    }
    
    // Prüfen ob customer_freebies Tabelle existiert (für fertige Freebies)
    $freebiesTableExists = $pdo->query("SHOW TABLES LIKE 'customer_freebies'")->rowCount() > 0;
    
    if (!$freebiesTableExists) {
        echo "<div class='step'><strong>Schritt 2:</strong> Erstelle Tabelle <code>customer_freebies</code>...</div>";
        
        $pdo->exec("
            CREATE TABLE customer_freebies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_id INT NOT NULL,
                freebie_id INT NOT NULL,
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_assignment (customer_id, freebie_id),
                FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (freebie_id) REFERENCES freebies(id) ON DELETE CASCADE,
                INDEX idx_customer_id (customer_id),
                INDEX idx_freebie_id (freebie_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Zuordnung von fertigen Freebies zu Kunden'
        ");
        
        echo "<div class='success'>✅ Tabelle <code>customer_freebies</code> erfolgreich erstellt!</div>";
    } else {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Tabelle <code>customer_freebies</code> existiert bereits.</div>";
    }
    
    // Prüfe ob is_template Spalte in freebies existiert
    $columns = $pdo->query("SHOW COLUMNS FROM freebies LIKE 'is_template'")->rowCount();
    
    if ($columns === 0) {
        echo "<div class='step'><strong>Schritt 3:</strong> Füge <code>is_template</code> Spalte zur <code>freebies</code> Tabelle hinzu...</div>";
        
        $pdo->exec("
            ALTER TABLE freebies 
            ADD COLUMN is_template TINYINT(1) DEFAULT 0 COMMENT 'Ist ein fertiges Template?' AFTER is_active
        ");
        
        echo "<div class='success'>✅ Spalte <code>is_template</code> wurde hinzugefügt!</div>";
        echo "<div class='info'>ℹ️ <strong>Tipp:</strong> Markiere jetzt Freebies als Templates mit:<br>
        <code>UPDATE freebies SET is_template = 1 WHERE id IN (1,2,3,4);</code></div>";
    } else {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Spalte <code>is_template</code> existiert bereits.</div>";
    }
    
    // Übersicht
    echo "<div class='step'>
        <h3>📊 Tabellen-Übersicht:</h3>
        <ul>
            <li>✅ <code>digistore_products</code> - Produktverwaltung</li>
            <li>✅ <code>customer_referral_slots</code> - Empfehlungs-Slots</li>
            <li>✅ <code>customer_freebies</code> - Zuordnung fertiger Freebies</li>
            <li>✅ <code>freebies.is_template</code> - Template-Markierung</li>
        </ul>
    </div>";
    
    echo "<div class='success'>
        <strong>✅ Migration erfolgreich abgeschlossen!</strong>
        <p style='margin: 10px 0 0 0;'>Das Digistore24 Webhook-System ist jetzt vollständig eingerichtet.</p>
    </div>
    
    <p style='margin-top: 30px; text-align: center;'>
        <a href='/admin/dashboard.php?page=digistore' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
            → Zur Digistore24 Verwaltung
        </a>
    </p>
    
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler bei der Migration</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}
