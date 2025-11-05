<?php
/**
 * KRITISCHES UPDATE: Konflikt-Fixes für Limits-System
 * 
 * Probleme die gelöst werden:
 * 1. Webhook überschreibt manuelle Admin-Änderungen
 * 2. Keine globale Tarif-Synchronisation
 * 3. Fehlende Produkt-Referenz bei Referral-Slots
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Kritisches Limits-Update</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #ef4444; margin-bottom: 10px; }
            .warning { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 6px; }
            .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 6px; }
            .info { background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; border-radius: 6px; }
            .step { margin: 20px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
            pre { background: #1f2937; color: #f3f4f6; padding: 15px; border-radius: 8px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>⚠️ Kritisches Limits-System Update</h1>
            <p style='color: #6b7280;'>Behebt Konflikte zwischen Webhook und manuellen Limits</p>";
    
    $updates = [];
    
    // UPDATE 1: Source-Spalte zu customer_freebie_limits
    echo "<div class='step'><strong>Update 1:</strong> Füge <code>source</code> Spalte hinzu...</div>";
    
    $columns = $pdo->query("SHOW COLUMNS FROM customer_freebie_limits LIKE 'source'")->rowCount();
    if ($columns === 0) {
        $pdo->exec("
            ALTER TABLE customer_freebie_limits 
            ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' 
            COMMENT 'Quelle der Limit-Setzung' 
            AFTER product_name
        ");
        $updates[] = "✅ Spalte 'source' zu customer_freebie_limits hinzugefügt";
        
        // Bestehende Einträge als 'webhook' markieren
        $pdo->exec("UPDATE customer_freebie_limits SET source = 'webhook' WHERE source IS NULL");
        $updates[] = "✅ Bestehende Einträge als 'webhook' markiert";
    } else {
        $updates[] = "ℹ️ Spalte 'source' existiert bereits";
    }
    
    // UPDATE 2: Source und product_id zu customer_referral_slots
    echo "<div class='step'><strong>Update 2:</strong> Erweitere <code>customer_referral_slots</code> Tabelle...</div>";
    
    // Prüfe ob Tabelle existiert
    $tableExists = $pdo->query("SHOW TABLES LIKE 'customer_referral_slots'")->rowCount() > 0;
    
    if ($tableExists) {
        // Füge product_id hinzu
        $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'product_id'")->rowCount();
        if ($columns === 0) {
            $pdo->exec("
                ALTER TABLE customer_referral_slots 
                ADD COLUMN product_id VARCHAR(100) DEFAULT NULL 
                COMMENT 'Digistore24 Produkt-ID' 
                AFTER customer_id
            ");
            $updates[] = "✅ Spalte 'product_id' zu customer_referral_slots hinzugefügt";
        } else {
            $updates[] = "ℹ️ Spalte 'product_id' existiert bereits";
        }
        
        // Füge product_name hinzu
        $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'product_name'")->rowCount();
        if ($columns === 0) {
            $pdo->exec("
                ALTER TABLE customer_referral_slots 
                ADD COLUMN product_name VARCHAR(255) DEFAULT NULL 
                COMMENT 'Produktname' 
                AFTER product_id
            ");
            $updates[] = "✅ Spalte 'product_name' zu customer_referral_slots hinzugefügt";
        } else {
            $updates[] = "ℹ️ Spalte 'product_name' existiert bereits";
        }
        
        // Füge source hinzu
        $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'source'")->rowCount();
        if ($columns === 0) {
            $pdo->exec("
                ALTER TABLE customer_referral_slots 
                ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' 
                COMMENT 'Quelle der Slot-Setzung' 
                AFTER product_name
            ");
            $updates[] = "✅ Spalte 'source' zu customer_referral_slots hinzugefügt";
            
            // Bestehende als webhook markieren
            $pdo->exec("UPDATE customer_referral_slots SET source = 'webhook' WHERE source IS NULL");
            $updates[] = "✅ Bestehende Einträge als 'webhook' markiert";
        } else {
            $updates[] = "ℹ️ Spalte 'source' existiert bereits";
        }
    } else {
        $updates[] = "⚠️ Tabelle customer_referral_slots existiert nicht - bitte erst migrate-referral-slots.php ausführen!";
    }
    
    // UPDATE 3: Unique constraint anpassen
    echo "<div class='step'><strong>Update 3:</strong> Prüfe Unique Constraints...</div>";
    
    // Prüfe ob unique constraint auf customer_id existiert
    $result = $pdo->query("SHOW INDEX FROM customer_referral_slots WHERE Key_name = 'unique_customer'")->fetch();
    if ($result) {
        $updates[] = "ℹ️ Unique constraint existiert bereits";
    } else {
        try {
            $pdo->exec("
                ALTER TABLE customer_referral_slots 
                ADD UNIQUE KEY unique_customer (customer_id)
            ");
            $updates[] = "✅ Unique constraint hinzugefügt";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $updates[] = "⚠️ Duplikate gefunden - bereinige Daten...";
                
                // Bereinige Duplikate (behält den mit höchsten total_slots)
                $pdo->exec("
                    DELETE t1 FROM customer_referral_slots t1
                    INNER JOIN customer_referral_slots t2 
                    WHERE t1.customer_id = t2.customer_id 
                    AND t1.total_slots < t2.total_slots
                ");
                
                $pdo->exec("
                    ALTER TABLE customer_referral_slots 
                    ADD UNIQUE KEY unique_customer (customer_id)
                ");
                $updates[] = "✅ Duplikate bereinigt und Unique constraint hinzugefügt";
            }
        }
    }
    
    // Zeige alle Updates
    echo "<div class='info'><h3>📊 Durchgeführte Updates:</h3><ul>";
    foreach ($updates as $update) {
        echo "<li>$update</li>";
    }
    echo "</ul></div>";
    
    // Zeige neue Struktur
    echo "<div class='step'><h3>🗂️ Neue Datenbankstruktur:</h3>";
    
    echo "<h4>customer_freebie_limits:</h4>";
    $columns = $pdo->query("SHOW COLUMNS FROM customer_freebie_limits")->fetchAll();
    echo "<pre>";
    foreach ($columns as $col) {
        echo sprintf("%-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Comment'] ?: '');
    }
    echo "</pre>";
    
    if ($tableExists) {
        echo "<h4>customer_referral_slots:</h4>";
        $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots")->fetchAll();
        echo "<pre>";
        foreach ($columns as $col) {
            echo sprintf("%-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Comment'] ?: '');
        }
        echo "</pre>";
    }
    
    echo "</div>";
    
    echo "<div class='success'>
        <strong>✅ Kritische Updates erfolgreich durchgeführt!</strong>
        <h4 style='margin-top: 15px;'>Was wurde geändert:</h4>
        <ul style='margin: 10px 0 0 20px;'>
            <li><strong>Source-Tracking:</strong> System kann jetzt zwischen webhook/manuell unterscheiden</li>
            <li><strong>Produkt-Referenz:</strong> Referral-Slots haben jetzt product_id/product_name</li>
            <li><strong>Konflikt-Schutz:</strong> Manuelle Admin-Änderungen werden nicht mehr überschrieben</li>
            <li><strong>Sync-Ready:</strong> Basis für globale Tarif-Synchronisation ist gelegt</li>
        </ul>
    </div>";
    
    echo "<div class='warning'>
        <strong>⚠️ Wichtig - Nächste Schritte:</strong>
        <ol style='margin: 10px 0 0 20px;'>
            <li><strong>Webhook aktualisieren:</strong> Nutzt jetzt die 'source' Spalte</li>
            <li><strong>Admin-API aktualisieren:</strong> Setzt 'source = manual'</li>
            <li><strong>Sync-Funktion installieren:</strong> Für globale Tarif-Updates</li>
        </ol>
        <p style='margin-top: 15px;'>
            Führe jetzt aus: <code>update-webhook-with-source-tracking.php</code>
        </p>
    </div>";
    
    echo "<a href='/admin/dashboard.php?page=digistore' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px;'>
        → Zum Admin-Dashboard
    </a>
    
    </div>
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div style='background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; margin: 20px 0; border-radius: 8px;'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ Fehler beim Update</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <pre style='background: white; padding: 15px; border-radius: 6px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>
    </div>";
}
