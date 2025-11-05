<?php
/**
 * Digistore24 Produktverwaltung - Datenbank Setup
 * 
 * Erstellt/Erweitert die Tabelle für die zentrale Webhook-Verwaltung
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Digistore24 Produkt-Setup</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🛒 Digistore24 Produktverwaltung Setup</h1>
            <p>Richte die Datenbank-Struktur für die zentrale Webhook-Verwaltung ein.</p>";
    
    // Prüfen ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'digistore_products'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Tabelle <code>digistore_products</code> existiert bereits. Prüfe Struktur...</div>";
        
        // Prüfe ob alle Spalten vorhanden sind
        $columns = $pdo->query("SHOW COLUMNS FROM digistore_products")->fetchAll(PDO::FETCH_COLUMN);
        $requiredColumns = [
            'id', 'product_id', 'product_name', 'product_type', 'price', 
            'billing_type', 'own_freebies_limit', 'ready_freebies_count', 
            'referral_program_slots', 'is_active', 'created_at', 'updated_at'
        ];
        
        $missingColumns = array_diff($requiredColumns, $columns);
        
        if (empty($missingColumns)) {
            echo "<div class='success'>✅ Alle erforderlichen Spalten sind vorhanden!</div>";
        } else {
            echo "<div class='info'>📝 Fehlende Spalten werden hinzugefügt: " . implode(', ', $missingColumns) . "</div>";
            
            // Füge fehlende Spalten hinzu (falls alte Struktur vorhanden)
            if (in_array('ready_freebies_count', $missingColumns)) {
                $pdo->exec("ALTER TABLE digistore_products ADD COLUMN ready_freebies_count INT DEFAULT 0 AFTER own_freebies_limit");
            }
            if (in_array('referral_program_slots', $missingColumns)) {
                $pdo->exec("ALTER TABLE digistore_products ADD COLUMN referral_program_slots INT DEFAULT 0 AFTER ready_freebies_count");
            }
            
            echo "<div class='success'>✅ Tabellen-Struktur wurde aktualisiert!</div>";
        }
        
    } else {
        echo "<div class='step'><strong>Schritt 1:</strong> Erstelle Tabelle <code>digistore_products</code>...</div>";
        
        // Erstelle neue Tabelle
        $pdo->exec("
            CREATE TABLE digistore_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'Digistore24 Produkt-ID',
                product_name VARCHAR(255) NOT NULL COMMENT 'Name des Produkts',
                product_type ENUM('launch', 'starter', 'pro', 'business', 'custom') NOT NULL DEFAULT 'custom',
                price DECIMAL(10,2) NOT NULL COMMENT 'Preis in Euro',
                billing_type ENUM('one_time', 'monthly', 'yearly') NOT NULL DEFAULT 'monthly',
                own_freebies_limit INT NOT NULL DEFAULT 0 COMMENT 'Anzahl eigener Freebies',
                ready_freebies_count INT NOT NULL DEFAULT 0 COMMENT 'Anzahl fertiger Freebies (Launch)',
                referral_program_slots INT NOT NULL DEFAULT 0 COMMENT 'Empfehlungsprogramm Slots',
                is_active TINYINT(1) DEFAULT 1 COMMENT 'Produkt aktiv?',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_product_id (product_id),
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Digistore24 Produktkonfiguration für Webhook-Steuerung'
        ");
        
        echo "<div class='success'>✅ Tabelle <code>digistore_products</code> erfolgreich erstellt!</div>";
    }
    
    // Prüfe ob Beispieldaten vorhanden sind
    $count = $pdo->query("SELECT COUNT(*) FROM digistore_products")->fetchColumn();
    
    if ($count == 0) {
        echo "<div class='step'><strong>Schritt 2:</strong> Füge Produkt-Templates hinzu...</div>";
        
        // Füge die 4 Produktvarianten als Templates ein (ohne Produkt-ID)
        $products = [
            [
                'product_id' => '',
                'product_name' => 'Launch Angebot',
                'product_type' => 'launch',
                'price' => 497.00,
                'billing_type' => 'one_time',
                'own_freebies_limit' => 4,
                'ready_freebies_count' => 4,
                'referral_program_slots' => 1,
                'is_active' => 0
            ],
            [
                'product_id' => '',
                'product_name' => 'Starter Abo',
                'product_type' => 'starter',
                'price' => 49.00,
                'billing_type' => 'monthly',
                'own_freebies_limit' => 4,
                'ready_freebies_count' => 0,
                'referral_program_slots' => 1,
                'is_active' => 0
            ],
            [
                'product_id' => '',
                'product_name' => 'Pro Abo',
                'product_type' => 'pro',
                'price' => 99.00,
                'billing_type' => 'monthly',
                'own_freebies_limit' => 8,
                'ready_freebies_count' => 0,
                'referral_program_slots' => 3,
                'is_active' => 0
            ],
            [
                'product_id' => '',
                'product_name' => 'Business Abo',
                'product_type' => 'business',
                'price' => 199.00,
                'billing_type' => 'monthly',
                'own_freebies_limit' => 20,
                'ready_freebies_count' => 0,
                'referral_program_slots' => 10,
                'is_active' => 0
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO digistore_products (
                product_id, product_name, product_type, price, billing_type,
                own_freebies_limit, ready_freebies_count, referral_program_slots, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($products as $product) {
            $stmt->execute([
                $product['product_id'],
                $product['product_name'],
                $product['product_type'],
                $product['price'],
                $product['billing_type'],
                $product['own_freebies_limit'],
                $product['ready_freebies_count'],
                $product['referral_program_slots'],
                $product['is_active']
            ]);
        }
        
        echo "<div class='success'>✅ 4 Produkt-Templates wurden hinzugefügt!</div>";
        echo "<div class='info'>ℹ️ <strong>Wichtig:</strong> Die Templates sind zunächst inaktiv. Trage die Digistore24 Produkt-IDs im Admin-Dashboard ein und aktiviere sie.</div>";
    } else {
        echo "<div class='info'>ℹ️ <strong>Info:</strong> Es sind bereits $count Produkte in der Datenbank vorhanden.</div>";
    }
    
    // Übersicht der Produkte
    echo "<div class='step'><strong>Aktuelle Produkte:</strong></div>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
        <thead>
            <tr style='background: #f3f4f6;'>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Produkt</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Typ</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Digistore ID</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Eigene Freebies</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Empf.-Slots</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Status</th>
            </tr>
        </thead>
        <tbody>";
    
    $products = $pdo->query("SELECT * FROM digistore_products ORDER BY 
        FIELD(product_type, 'launch', 'starter', 'pro', 'business', 'custom')")->fetchAll();
    
    foreach ($products as $product) {
        $statusColor = $product['is_active'] ? '#10b981' : '#ef4444';
        $statusText = $product['is_active'] ? '✅ Aktiv' : '❌ Inaktiv';
        $digistoreId = $product['product_id'] ?: '<em style="color: #9ca3af;">nicht gesetzt</em>';
        
        echo "<tr style='border-bottom: 1px solid #e5e7eb;'>
            <td style='padding: 12px;'>{$product['product_name']}</td>
            <td style='padding: 12px;'><span style='background: #dbeafe; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>{$product['product_type']}</span></td>
            <td style='padding: 12px;'>$digistoreId</td>
            <td style='padding: 12px;'>{$product['own_freebies_limit']}</td>
            <td style='padding: 12px;'>{$product['referral_program_slots']}</td>
            <td style='padding: 12px;'><span style='color: $statusColor; font-weight: bold;'>$statusText</span></td>
        </tr>";
    }
    
    echo "</tbody></table>";
    
    echo "
        <div class='success'>
            <strong>✅ Setup erfolgreich abgeschlossen!</strong>
            <p style='margin-top: 10px; margin-bottom: 0;'>Nächste Schritte:</p>
            <ol style='margin: 10px 0 0 20px;'>
                <li>Gehe zum <strong>Admin-Dashboard → Digistore24</strong></li>
                <li>Trage die Digistore24 Produkt-IDs ein</li>
                <li>Aktiviere die Produkte</li>
                <li>Der Webhook unter <code>/webhook/digistore24.php</code> ist bereit!</li>
            </ol>
        </div>
        
        <a href='/admin/dashboard.php?page=digistore' class='btn'>→ Zum Admin-Dashboard</a>
        
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler beim Setup</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Trace:</strong></p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}
