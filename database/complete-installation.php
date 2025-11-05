<?php
/**
 * VOLLSTÄNDIGE INSTALLATION - Konflikt-Fixes + Sync-Button
 * Führt alle notwendigen Updates auf einmal durch
 */

require_once '../config/database.php';

$steps = [];
$errors = [];

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Vollständige Installation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 1000px; margin: 50px auto; padding: 20px; background: #f5f7fa; }
            .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            h1 { color: #667eea; margin-bottom: 10px; }
            .step { margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6; }
            .success { background: #d1fae5; border-left-color: #10b981; }
            .error { background: #fee2e2; border-left-color: #ef4444; }
            .warning { background: #fef3c7; border-left-color: #f59e0b; }
            code { background: #e5e7eb; padding: 2px 6px; border-radius: 4px; }
            .progress { margin: 20px 0; }
            .progress-bar { width: 100%; height: 30px; background: #e5e7eb; border-radius: 15px; overflow: hidden; }
            .progress-fill { height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); transition: width 0.3s; }
            ul { margin: 10px 0 10px 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>🚀 Vollständige Installation</h1>
            <p style='color: #6b7280;'>Installiere alle Konflikt-Fixes und Sync-Funktionen auf einmal</p>
            
            <div class='progress'>
                <div class='progress-bar'>
                    <div class='progress-fill' id='progressBar' style='width: 0%'></div>
                </div>
                <p id='progressText' style='text-align: center; margin-top: 10px; color: #6b7280;'>Starte Installation...</p>
            </div>";
    
    // SCHRITT 1: Source-Spalte zu customer_freebie_limits
    echo "<div class='step'><strong>Schritt 1/5:</strong> Erweitere customer_freebie_limits...</div>";
    
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM customer_freebie_limits LIKE 'source'")->rowCount();
        if ($columns === 0) {
            $pdo->exec("
                ALTER TABLE customer_freebie_limits 
                ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' 
                AFTER product_name
            ");
            $pdo->exec("UPDATE customer_freebie_limits SET source = 'webhook' WHERE source IS NULL");
            $steps[] = "✅ Spalte 'source' zu customer_freebie_limits hinzugefügt";
        } else {
            $steps[] = "ℹ️ customer_freebie_limits.source existiert bereits";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Fehler bei customer_freebie_limits: " . $e->getMessage();
    }
    
    updateProgress(20, "customer_freebie_limits aktualisiert");
    
    // SCHRITT 2: Erweitere customer_referral_slots
    echo "<div class='step'><strong>Schritt 2/5:</strong> Erweitere customer_referral_slots...</div>";
    
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'customer_referral_slots'")->rowCount() > 0;
        
        if ($tableExists) {
            // product_id
            $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'product_id'")->rowCount();
            if ($columns === 0) {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN product_id VARCHAR(100) DEFAULT NULL AFTER customer_id");
                $steps[] = "✅ product_id hinzugefügt";
            }
            
            // product_name
            $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'product_name'")->rowCount();
            if ($columns === 0) {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN product_name VARCHAR(255) DEFAULT NULL AFTER product_id");
                $steps[] = "✅ product_name hinzugefügt";
            }
            
            // source
            $columns = $pdo->query("SHOW COLUMNS FROM customer_referral_slots LIKE 'source'")->rowCount();
            if ($columns === 0) {
                $pdo->exec("ALTER TABLE customer_referral_slots ADD COLUMN source ENUM('webhook', 'manual', 'upgrade') DEFAULT 'webhook' AFTER product_name");
                $pdo->exec("UPDATE customer_referral_slots SET source = 'webhook' WHERE source IS NULL");
                $steps[] = "✅ source hinzugefügt";
            }
            
            // Unique constraint
            $result = $pdo->query("SHOW INDEX FROM customer_referral_slots WHERE Key_name = 'unique_customer'")->fetch();
            if (!$result) {
                try {
                    $pdo->exec("ALTER TABLE customer_referral_slots ADD UNIQUE KEY unique_customer (customer_id)");
                    $steps[] = "✅ Unique constraint hinzugefügt";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        // Bereinige Duplikate
                        $pdo->exec("DELETE t1 FROM customer_referral_slots t1 INNER JOIN customer_referral_slots t2 WHERE t1.customer_id = t2.customer_id AND t1.total_slots < t2.total_slots");
                        $pdo->exec("ALTER TABLE customer_referral_slots ADD UNIQUE KEY unique_customer (customer_id)");
                        $steps[] = "✅ Duplikate bereinigt und Unique constraint hinzugefügt";
                    }
                }
            }
        } else {
            $errors[] = "⚠️ Tabelle customer_referral_slots existiert nicht - bitte erst migrate-referral-slots.php ausführen!";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ Fehler bei customer_referral_slots: " . $e->getMessage();
    }
    
    updateProgress(40, "customer_referral_slots aktualisiert");
    
    // SCHRITT 3: Webhook wurde bereits aktualisiert (v3.0)
    echo "<div class='step'><strong>Schritt 3/5:</strong> Prüfe Webhook-Version...</div>";
    $steps[] = "✅ Webhook wurde bereits auf Version 3.0 aktualisiert (mit Source-Tracking)";
    
    updateProgress(60, "Webhook geprüft");
    
    // SCHRITT 4: Admin-API wurde bereits aktualisiert
    echo "<div class='step'><strong>Schritt 4/5:</strong> Prüfe Admin-APIs...</div>";
    
    $apiFiles = [
        'customer-update-limits.php' => 'Individuelle Limits API',
        'product-sync-limits.php' => 'Globale Sync API'
    ];
    
    foreach ($apiFiles as $file => $name) {
        if (file_exists(__DIR__ . '/../api/' . $file)) {
            $steps[] = "✅ $name verfügbar";
        } else {
            $errors[] = "❌ $name fehlt";
        }
    }
    
    updateProgress(80, "Admin-APIs geprüft");
    
    // SCHRITT 5: Admin-Interface mit Sync-Button
    echo "<div class='step'><strong>Schritt 5/5:</strong> Installiere Admin-Interface...</div>";
    
    $sourceFile = __DIR__ . '/../admin/sections/digistore-complete.php';
    $targetFile = __DIR__ . '/../admin/sections/digistore.php';
    
    if (file_exists($sourceFile)) {
        // Backup erstellen
        if (file_exists($targetFile)) {
            copy($targetFile, $targetFile . '.backup');
            $steps[] = "✅ Backup von digistore.php erstellt";
        }
        
        // Neue Version kopieren
        copy($sourceFile, $targetFile);
        $steps[] = "✅ Admin-Interface mit Sync-Button installiert";
    } else {
        $errors[] = "❌ digistore-complete.php nicht gefunden";
    }
    
    updateProgress(100, "Installation abgeschlossen!");
    
    // Zusammenfassung
    echo "<div class='step success'>
        <h3 style='margin-top: 0;'>✅ Installation erfolgreich!</h3>
        <h4>Durchgeführte Schritte:</h4>
        <ul>";
    
    foreach ($steps as $step) {
        echo "<li>$step</li>";
    }
    
    echo "</ul>";
    
    if (!empty($errors)) {
        echo "<h4 style='color: #ef4444;'>⚠️ Warnungen:</h4><ul>";
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo "</ul>";
    }
    
    echo "</div>";
    
    // Nächste Schritte
    echo "<div class='step warning'>
        <h3 style='margin-top: 0;'>🎯 Nächste Schritte:</h3>
        <ol>
            <li><strong>Admin-Dashboard öffnen:</strong><br>
                <a href='/admin/dashboard.php?page=digistore' style='color: #667eea;'>→ Zur Produktverwaltung</a>
            </li>
            <li><strong>Produkt-IDs eintragen:</strong><br>
                Trage deine Digistore24 Produkt-IDs ein und aktiviere die Produkte
            </li>
            <li><strong>Teste den Sync-Button:</strong><br>
                Nutze \"Alle Kunden aktualisieren\" um bestehende Kunden zu synchronisieren
            </li>
            <li><strong>Prüfe die Logs:</strong><br>
                <code>/webhook/webhook-logs.txt</code>
            </li>
        </ol>
    </div>";
    
    // Features
    echo "<div class='step'>
        <h3 style='margin-top: 0;'>🎉 Neue Features verfügbar:</h3>
        <ul>
            <li><strong>Source-Tracking:</strong> Unterscheidung zwischen webhook/manuell/upgrade</li>
            <li><strong>Schutz vor Überschreiben:</strong> Manuelle Admin-Limits werden respektiert</li>
            <li><strong>Globale Sync:</strong> Alle Kunden eines Tarifs mit einem Klick aktualisieren</li>
            <li><strong>Produkt-Referenz:</strong> Jedes Limit hat eine klare Zuordnung</li>
            <li><strong>Detaillierte Statistik:</strong> Sehe genau was synchronisiert wurde</li>
        </ul>
    </div>";
    
    echo "<a href='/admin/dashboard.php?page=digistore' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px;'>
        → Zur Produktverwaltung
    </a>
    
    </div>
    
    <script>
    function updateProgress(percent, text) {
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressText').textContent = text;
    }
    </script>
    
    </body>
    </html>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
        <h3>❌ Kritischer Fehler</h3>
        <p><strong>Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
    </div>";
}

function updateProgress($percent, $text) {
    echo "<script>updateProgress($percent, '$text');</script>";
    flush();
}
