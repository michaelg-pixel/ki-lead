<?php
/**
 * Einmalige Marktplatz-Migration
 * Führt die MySQL-kompatible Migration direkt aus
 * 
 * AUFRUF: https://app.mehr-infos-jetzt.de/database/add-marketplace-fields-direct.php
 * Nach erfolgreicher Ausführung LÖSCHEN!
 */

require_once __DIR__ . '/../config/database.php';

// HTML Header
echo "<!DOCTYPE html>";
echo "<html lang='de'><head><meta charset='UTF-8'>";
echo "<title>Marktplatz Migration</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; background: #f5f5f5; }";
echo ".success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; border-radius: 4px; color: #155724; }";
echo ".error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 4px; color: #721c24; }";
echo ".info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 10px 0; border-radius: 4px; color: #0c5460; }";
echo ".warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 4px; color: #856404; }";
echo "pre { background: #fff; padding: 15px; border-radius: 4px; border: 1px solid #ddd; overflow-x: auto; }";
echo "h1 { color: #667eea; }";
echo "h2 { color: #333; margin-top: 30px; }";
echo "</style></head><body>";

echo "<h1>🏪 Marktplatz-Felder Migration</h1>";

try {
    $pdo = getDBConnection();
    
    // Liste der hinzuzufügenden Spalten
    $columns = [
        ['name' => 'marketplace_enabled', 'definition' => 'BOOLEAN DEFAULT FALSE', 'desc' => 'Marktplatz-Aktivierung'],
        ['name' => 'marketplace_price', 'definition' => 'DECIMAL(10,2) DEFAULT NULL', 'desc' => 'Preis'],
        ['name' => 'digistore_product_id', 'definition' => 'VARCHAR(255) DEFAULT NULL', 'desc' => 'DigiStore24 Produkt-ID'],
        ['name' => 'marketplace_description', 'definition' => 'TEXT DEFAULT NULL', 'desc' => 'Marktplatz-Beschreibung'],
        ['name' => 'course_lessons_count', 'definition' => 'INT DEFAULT NULL', 'desc' => 'Anzahl Lektionen'],
        ['name' => 'course_duration', 'definition' => 'VARCHAR(100) DEFAULT NULL', 'desc' => 'Kursdauer'],
        ['name' => 'marketplace_sales_count', 'definition' => 'INT DEFAULT 0', 'desc' => 'Verkaufszähler'],
        ['name' => 'marketplace_updated_at', 'definition' => 'TIMESTAMP NULL DEFAULT NULL', 'desc' => 'Letzte Aktualisierung'],
        ['name' => 'original_creator_id', 'definition' => 'INT DEFAULT NULL', 'desc' => 'Original-Ersteller ID'],
        ['name' => 'copied_from_freebie_id', 'definition' => 'INT DEFAULT NULL', 'desc' => 'Original-Freebie ID']
    ];
    
    echo "<div class='info'>";
    echo "<strong>📋 Prüfe " . count($columns) . " Spalten...</strong>";
    echo "</div>";
    
    $addedCount = 0;
    $skippedCount = 0;
    $errorCount = 0;
    
    foreach ($columns as $column) {
        echo "<h2>🔍 Spalte: {$column['name']}</h2>";
        echo "<p><strong>Beschreibung:</strong> {$column['desc']}</p>";
        
        // Prüfe ob Spalte existiert
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'customer_freebies'
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$column['name']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo "<div class='warning'>";
            echo "⏭️ <strong>Übersprungen</strong> - Spalte existiert bereits";
            echo "</div>";
            $skippedCount++;
        } else {
            try {
                $sql = "ALTER TABLE customer_freebies ADD COLUMN {$column['name']} {$column['definition']}";
                echo "<pre>SQL: $sql</pre>";
                
                $pdo->exec($sql);
                
                echo "<div class='success'>";
                echo "✅ <strong>Erfolgreich hinzugefügt!</strong>";
                echo "</div>";
                $addedCount++;
            } catch (PDOException $e) {
                echo "<div class='error'>";
                echo "❌ <strong>Fehler:</strong> " . htmlspecialchars($e->getMessage());
                echo "</div>";
                $errorCount++;
            }
        }
    }
    
    // Zusammenfassung
    echo "<hr style='margin: 40px 0;'>";
    echo "<h2>📊 Migrations-Zusammenfassung</h2>";
    
    if ($errorCount === 0 && $addedCount > 0) {
        echo "<div class='success'>";
        echo "<h3>✅ Migration erfolgreich abgeschlossen!</h3>";
        echo "<ul>";
        echo "<li>✅ Hinzugefügt: <strong>$addedCount</strong> Spalte(n)</li>";
        echo "<li>⏭️ Übersprungen: <strong>$skippedCount</strong> Spalte(n)</li>";
        echo "<li>❌ Fehler: <strong>$errorCount</strong></li>";
        echo "</ul>";
        echo "</div>";
    } else if ($errorCount === 0 && $addedCount === 0) {
        echo "<div class='warning'>";
        echo "<h3>⚠️ Keine Änderungen vorgenommen</h3>";
        echo "<p>Alle Spalten existieren bereits. Das Marktplatz-System ist bereits vollständig eingerichtet!</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>⚠️ Migration mit Fehlern abgeschlossen</h3>";
        echo "<ul>";
        echo "<li>✅ Hinzugefügt: <strong>$addedCount</strong> Spalte(n)</li>";
        echo "<li>⏭️ Übersprungen: <strong>$skippedCount</strong> Spalte(n)</li>";
        echo "<li>❌ Fehler: <strong>$errorCount</strong></li>";
        echo "</ul>";
        echo "</div>";
    }
    
    if ($addedCount > 0 || ($addedCount === 0 && $skippedCount === count($columns))) {
        echo "<div class='success'>";
        echo "<h3>🎉 Marktplatz-System ist einsatzbereit!</h3>";
        echo "<h4>Nächste Schritte:</h4>";
        echo "<ol>";
        echo "<li>Gehe zu <strong>Dashboard → Marktplatz</strong></li>";
        echo "<li>Wähle ein Freebie aus der Liste</li>";
        echo "<li>Klicke auf <strong>⚙️ Marktplatz-Einstellungen</strong></li>";
        echo "<li>Fülle alle Felder aus (Preis, DigiStore24 Produkt-ID, etc.)</li>";
        echo "<li>Aktiviere <strong>\"🟢 Im Marktplatz anzeigen\"</strong></li>";
        echo "<li>Speichere die Einstellungen</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<h4>⚠️ WICHTIG: Diese Datei jetzt löschen!</h4>";
        echo "<p>Aus Sicherheitsgründen solltest du diese Migrations-Datei nach erfolgreicher Ausführung löschen:</p>";
        echo "<pre>database/add-marketplace-fields-direct.php</pre>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Kritischer Fehler!</h3>";
    echo "<p><strong>Nachricht:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
}

echo "</body></html>";
