<?php
/**
 * Update: customer_referral_slots Tabelle
 * Fügt fehlende Spalten für Source-Tracking hinzu
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Referral Slots - Spalten Update</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 15px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
            .btn { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🔧 Referral Slots Tabelle - Spalten Update</h1>
            <p>Fügt die fehlenden Spalten für Source-Tracking hinzu.</p>";
    
    // Prüfen ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'customer_referral_slots'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='warning'>⚠️ <strong>Warnung:</strong> Tabelle <code>customer_referral_slots</code> existiert nicht!</div>";
        echo "<p>Bitte führe zuerst das Migrations-Script aus:</p>";
        echo "<a href='migrate-referral-slots.php' class='btn'>→ Zur Migration</a>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='success'>✅ Tabelle <code>customer_referral_slots</code> gefunden.</div>";
    
    // Aktuelle Spalten abrufen
    $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots")->fetchAll(PDO::FETCH_COLUMN);
    
    $updates = 0;
    
    // product_id Spalte hinzufügen
    if (!in_array('product_id', $columns)) {
        echo "<div class='step'><strong>Schritt 1:</strong> Füge Spalte <code>product_id</code> hinzu...</div>";
        
        $pdo->exec("
            ALTER TABLE customer_referral_slots 
            ADD COLUMN product_id VARCHAR(100) NULL DEFAULT NULL 
            COMMENT 'Digistore24 Produkt-ID' 
            AFTER used_slots
        ");
        
        echo "<div class='success'>✅ Spalte <code>product_id</code> wurde hinzugefügt!</div>";
        $updates++;
    } else {
        echo "<div class='info'>ℹ️ Spalte <code>product_id</code> existiert bereits.</div>";
    }
    
    // product_name Spalte hinzufügen
    if (!in_array('product_name', $columns)) {
        echo "<div class='step'><strong>Schritt 2:</strong> Füge Spalte <code>product_name</code> hinzu...</div>";
        
        $pdo->exec("
            ALTER TABLE customer_referral_slots 
            ADD COLUMN product_name VARCHAR(255) NULL DEFAULT NULL 
            COMMENT 'Name des Produkts/Tarifs' 
            AFTER product_id
        ");
        
        echo "<div class='success'>✅ Spalte <code>product_name</code> wurde hinzugefügt!</div>";
        $updates++;
    } else {
        echo "<div class='info'>ℹ️ Spalte <code>product_name</code> existiert bereits.</div>";
    }
    
    // source Spalte hinzufügen
    if (!in_array('source', $columns)) {
        echo "<div class='step'><strong>Schritt 3:</strong> Füge Spalte <code>source</code> hinzu...</div>";
        
        $pdo->exec("
            ALTER TABLE customer_referral_slots 
            ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' 
            COMMENT 'Quelle der Limits' 
            AFTER product_name
        ");
        
        echo "<div class='success'>✅ Spalte <code>source</code> wurde hinzugefügt!</div>";
        $updates++;
    } else {
        echo "<div class='info'>ℹ️ Spalte <code>source</code> existiert bereits.</div>";
    }
    
    // Finale Tabellenstruktur anzeigen
    echo "<div class='step'>
        <h3>📊 Finale Tabellenstruktur:</h3>
        <table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>
            <thead>
                <tr style='background: #f3f4f6;'>
                    <th style='padding: 8px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Spalte</th>
                    <th style='padding: 8px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Typ</th>
                    <th style='padding: 8px; text-align: left; border-bottom: 2px solid #e5e7eb;'>Beschreibung</th>
                </tr>
            </thead>
            <tbody>";
    
    $finalColumns = $pdo->query("SHOW FULL COLUMNS FROM customer_referral_slots")->fetchAll();
    foreach ($finalColumns as $col) {
        $isNew = !in_array($col['Field'], $columns) ? ' style="background: #d1fae5;"' : '';
        echo "<tr$isNew>
            <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'><code>{$col['Field']}</code></td>
            <td style='padding: 8px; border-bottom: 1px solid #e5e7eb;'>{$col['Type']}</td>
            <td style='padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 12px; color: #666;'>{$col['Comment']}</td>
        </tr>";
    }
    
    echo "</tbody></table></div>";
    
    if ($updates > 0) {
        echo "<div class='success'>
            <strong>✅ Update erfolgreich abgeschlossen!</strong>
            <p style='margin: 10px 0 0 0;'>
                $updates Spalte(n) wurden hinzugefügt. Die Limits-Verwaltung funktioniert jetzt vollständig!
            </p>
        </div>";
    } else {
        echo "<div class='info'>
            <strong>ℹ️ Keine Updates erforderlich</strong>
            <p style='margin: 10px 0 0 0;'>
                Alle erforderlichen Spalten sind bereits vorhanden.
            </p>
        </div>";
    }
    
    echo "
    <div style='text-align: center; margin-top: 30px;'>
        <a href='/admin/dashboard.php?page=users' class='btn'>→ Zur Kundenverwaltung</a>
    </div>
    
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler beim Update</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p><strong>Trace:</strong></p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>
    </div>
    </body>
    </html>";
}
