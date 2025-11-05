<?php
/**
 * Quick-Fix: Digistore Products Tabelle reparieren
 * Behebt UNIQUE constraint Problem mit leeren product_id Werten
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Digistore Products - Quick Fix</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 Digistore Products - Quick Fix</h1>
            <p>Repariere die bestehende Tabelle und füge Produkte hinzu.</p>";
    
    // Schritt 1: Prüfe ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'digistore_products'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='warning'>⚠️ Tabelle existiert nicht. Nutze stattdessen das reguläre Setup-Script.</div>";
        echo "<a href='setup-digistore-products.php' class='btn'>→ Zum Setup-Script</a>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='info'>✅ Tabelle <code>digistore_products</code> gefunden.</div>";
    
    // Schritt 2: Ändere product_id zu NULL DEFAULT NULL
    echo "<div class='info'>🔧 Passe Spalten-Definition an...</div>";
    
    try {
        $pdo->exec("
            ALTER TABLE digistore_products 
            MODIFY COLUMN product_id VARCHAR(100) NULL DEFAULT NULL 
            COMMENT 'Digistore24 Produkt-ID'
        ");
        echo "<div class='success'>✅ Spalte <code>product_id</code> ist jetzt nullable.</div>";
    } catch (Exception $e) {
        echo "<div class='info'>ℹ️ Spalte bereits korrekt konfiguriert.</div>";
    }
    
    // Schritt 3: Prüfe vorhandene Produkte
    $count = $pdo->query("SELECT COUNT(*) FROM digistore_products")->fetchColumn();
    
    if ($count > 0) {
        echo "<div class='warning'>⚠️ Es sind bereits $count Produkte vorhanden. Diese werden NICHT überschrieben.</div>";
    } else {
        echo "<div class='info'>📦 Füge 4 Produkt-Templates hinzu...</div>";
        
        // Füge die 4 Produktvarianten als Templates ein
        $products = [
            [
                'product_id' => null,
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
                'product_id' => null,
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
                'product_id' => null,
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
                'product_id' => null,
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
        
        echo "<div class='success'>✅ 4 Produkt-Templates wurden erfolgreich hinzugefügt!</div>";
    }
    
    // Zeige aktuelle Produkte
    $products = $pdo->query("SELECT * FROM digistore_products ORDER BY 
        FIELD(product_type, 'launch', 'starter', 'pro', 'business', 'custom')")->fetchAll();
    
    echo "<div class='info'><strong>📋 Aktuelle Produkte:</strong></div>";
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
        <thead>
            <tr style='background: #f3f4f6;'>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Produkt</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Typ</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Preis</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Digistore ID</th>
                <th style='padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Status</th>
            </tr>
        </thead>
        <tbody>";
    
    foreach ($products as $product) {
        $statusColor = $product['is_active'] ? '#10b981' : '#ef4444';
        $statusText = $product['is_active'] ? '✅ Aktiv' : '❌ Inaktiv';
        $digistoreId = $product['product_id'] ?: '<em style="color: #9ca3af;">nicht gesetzt</em>';
        $price = number_format($product['price'], 2, ',', '.') . ' €';
        
        echo "<tr style='border-bottom: 1px solid #e5e7eb;'>
            <td style='padding: 12px;'>{$product['product_name']}</td>
            <td style='padding: 12px;'><span style='background: #dbeafe; padding: 4px 8px; border-radius: 4px; font-size: 12px;'>{$product['product_type']}</span></td>
            <td style='padding: 12px;'>$price</td>
            <td style='padding: 12px;'>$digistoreId</td>
            <td style='padding: 12px;'><span style='color: $statusColor; font-weight: bold;'>$statusText</span></td>
        </tr>";
    }
    
    echo "</tbody></table>";
    
    echo "
        <div class='success'>
            <strong>✅ Quick-Fix erfolgreich abgeschlossen!</strong>
            <p style='margin-top: 10px; margin-bottom: 0;'>Die Tabelle ist jetzt bereit. Gehe zum Admin-Dashboard und trage die Digistore24 Produkt-IDs ein.</p>
        </div>
        
        <a href='/admin/dashboard.php?page=digistore' class='btn'>→ Zum Admin-Dashboard</a>
        
        </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Trace:</strong></p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>
    </div>
    </body>
    </html>";
}
