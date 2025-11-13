<?php
/**
 * Fix für beide Probleme:
 * 1. customer_freebies Marktplatz-Spalten
 * 2. vendor_reward_templates Enhancement-Spalten
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Complete Fix</title>";
    echo "<style>body{font-family:Arial;max-width:900px;margin:50px auto;padding:20px}";
    echo ".success{background:#d4edda;border-left:4px solid #28a745;padding:15px;margin:10px 0;border-radius:4px}";
    echo ".error{background:#f8d7da;border-left:4px solid #dc3545;padding:15px;margin:10px 0;border-radius:4px}";
    echo ".warning{background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:10px 0;border-radius:4px}";
    echo ".info{background:#d1ecf1;border-left:4px solid #17a2b8;padding:15px;margin:10px 0;border-radius:4px}";
    echo "h2{color:#667eea;margin-top:30px}</style></head><body>";
    echo "<h1>🔧 Complete Database Fix</h1>";
    
    // ===== TEIL 1: customer_freebies Marktplatz-Spalten =====
    echo "<h2>📦 Teil 1: customer_freebies Marktplatz-Spalten</h2>";
    
    // Zeige aktuelle Struktur
    echo "<div class='info'><strong>Aktuelle Struktur von customer_freebies:</strong><br>";
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
        echo "- " . htmlspecialchars($row['Field']) . "<br>";
    }
    echo "</div>";
    
    // Finde die letzte Spalte für AFTER
    $last_column = end($existing_columns);
    
    $marketplace_columns = [
        ['name' => 'marketplace_original_id', 'def' => 'INT NULL', 'desc' => 'ID des Original-Freebies im Marktplatz'],
        ['name' => 'marketplace_seller_id', 'def' => 'INT NULL', 'desc' => 'ID des Verkäufers'],
        ['name' => 'is_marketplace_copy', 'def' => 'BOOLEAN DEFAULT 0', 'desc' => 'Ist dies eine Kopie aus dem Marktplatz?']
    ];
    
    $added_mp = 0;
    $skipped_mp = 0;
    
    foreach ($marketplace_columns as $col) {
        echo "<div style='margin:15px 0;padding:10px;background:#f8f9fa;border-radius:4px'>";
        echo "<strong>📋 {$col['name']}</strong><br><small>{$col['desc']}</small><br>";
        
        if (in_array($col['name'], $existing_columns)) {
            echo "<span style='color:#856404'>⚠️ Existiert bereits</span>";
            $skipped_mp++;
        } else {
            try {
                $sql = "ALTER TABLE customer_freebies ADD COLUMN {$col['name']} {$col['def']}";
                $pdo->exec($sql);
                echo "<span style='color:#28a745'>✅ Erfolgreich hinzugefügt</span>";
                $added_mp++;
            } catch (PDOException $e) {
                echo "<span style='color:#dc3545'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
        }
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<strong>customer_freebies:</strong> $added_mp hinzugefügt, $skipped_mp übersprungen";
    echo "</div>";
    
    // ===== TEIL 2: vendor_reward_templates Enhancement-Spalten =====
    echo "<h2>💎 Teil 2: vendor_reward_templates Enhancement-Spalten</h2>";
    
    // Prüfe ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'vendor_reward_templates'");
    if ($stmt->rowCount() === 0) {
        echo "<div class='warning'>⚠️ Tabelle vendor_reward_templates existiert nicht. Überspringe...</div>";
    } else {
        // Zeige aktuelle Struktur
        echo "<div class='info'><strong>Aktuelle Struktur von vendor_reward_templates:</strong><br>";
        $stmt = $pdo->query("SHOW COLUMNS FROM vendor_reward_templates");
        $vendor_columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $vendor_columns[] = $row['Field'];
        }
        echo "Gesamt: " . count($vendor_columns) . " Spalten</div>";
        
        $enhancement_columns = [
            ['name' => 'marketplace_price', 'def' => 'DECIMAL(10,2) DEFAULT 0.00', 'after' => 'suggested_referrals_required', 'desc' => 'Preis im Marktplatz'],
            ['name' => 'product_mockup_url', 'def' => 'VARCHAR(500) NULL', 'after' => 'preview_image', 'desc' => 'URL zum Mockup-Bild'],
            ['name' => 'course_duration', 'def' => 'VARCHAR(100) NULL', 'after' => 'reward_instructions', 'desc' => 'Dauer des Kurses'],
            ['name' => 'original_product_link', 'def' => 'VARCHAR(500) NULL', 'after' => 'course_duration', 'desc' => 'Link zum Original-Produkt']
        ];
        
        $added_vt = 0;
        $skipped_vt = 0;
        
        foreach ($enhancement_columns as $col) {
            echo "<div style='margin:15px 0;padding:10px;background:#f8f9fa;border-radius:4px'>";
            echo "<strong>📋 {$col['name']}</strong><br><small>{$col['desc']}</small><br>";
            
            if (in_array($col['name'], $vendor_columns)) {
                echo "<span style='color:#856404'>⚠️ Existiert bereits</span>";
                $skipped_vt++;
            } else {
                try {
                    // Prüfe ob AFTER-Spalte existiert
                    if (in_array($col['after'], $vendor_columns)) {
                        $sql = "ALTER TABLE vendor_reward_templates ADD COLUMN {$col['name']} {$col['def']} AFTER {$col['after']}";
                    } else {
                        // Fallback: ohne AFTER
                        $sql = "ALTER TABLE vendor_reward_templates ADD COLUMN {$col['name']} {$col['def']}";
                    }
                    $pdo->exec($sql);
                    echo "<span style='color:#28a745'>✅ Erfolgreich hinzugefügt</span>";
                    $added_vt++;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        echo "<span style='color:#856404'>⚠️ Existiert bereits</span>";
                        $skipped_vt++;
                    } else {
                        echo "<span style='color:#dc3545'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</span>";
                    }
                }
            }
            echo "</div>";
        }
        
        echo "<div class='success'>";
        echo "<strong>vendor_reward_templates:</strong> $added_vt hinzugefügt, $skipped_vt übersprungen";
        echo "</div>";
    }
    
    // ===== ZUSAMMENFASSUNG =====
    echo "<h2>📊 Gesamt-Zusammenfassung</h2>";
    echo "<div class='success'>";
    echo "<h3>✅ Migration abgeschlossen!</h3>";
    echo "<ul>";
    echo "<li><strong>customer_freebies:</strong> $added_mp neue Spalten</li>";
    echo "<li><strong>vendor_reward_templates:</strong> $added_vt neue Spalten</li>";
    echo "</ul>";
    
    $total = $added_mp + $added_vt;
    if ($total > 0) {
        echo "<p style='color:#28a745;font-weight:bold'>Insgesamt $total Spalten erfolgreich hinzugefügt! 🎉</p>";
    } else {
        echo "<p style='color:#856404'>Alle Spalten existierten bereits. Keine Änderungen nötig.</p>";
    }
    
    echo "<p><strong>Nächste Schritte:</strong></p>";
    echo "<ol>";
    echo "<li>✅ Datenbank ist jetzt vollständig</li>";
    echo "<li>Lösche diese Datei (complete-fix.php)</li>";
    echo "<li>Teste den Vendor-Bereich</li>";
    echo "<li>Teste die Marktplatz-Funktionen</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Kritischer Fehler</h3>";
    echo "<p><strong>Nachricht:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . htmlspecialchars($e->getCode()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>