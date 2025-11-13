<?php
/**
 * Quick Fix: Vendor Template Spalten hinzufügen
 * Fügt die 4 fehlenden Spalten zur vendor_reward_templates Tabelle hinzu
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
    echo "<title>Vendor Template Fix</title>";
    echo "<style>body{font-family:Arial;max-width:800px;margin:50px auto;padding:20px}";
    echo ".success{background:#d4edda;border-left:4px solid #28a745;padding:15px;margin:10px 0;border-radius:4px}";
    echo ".error{background:#f8d7da;border-left:4px solid #dc3545;padding:15px;margin:10px 0;border-radius:4px}";
    echo ".warning{background:#fff3cd;border-left:4px solid #ffc107;padding:15px;margin:10px 0;border-radius:4px}";
    echo "</style></head><body>";
    echo "<h1>🔧 Vendor Template Quick Fix</h1>";
    
    $columns = [
        ['name' => 'marketplace_price', 'def' => 'DECIMAL(10,2) DEFAULT 0.00', 'after' => 'suggested_referrals_required'],
        ['name' => 'product_mockup_url', 'def' => 'VARCHAR(500) NULL', 'after' => 'preview_image'],
        ['name' => 'course_duration', 'def' => 'VARCHAR(100) NULL', 'after' => 'reward_instructions'],
        ['name' => 'original_product_link', 'def' => 'VARCHAR(500) NULL', 'after' => 'course_duration']
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($columns as $col) {
        echo "<div style='margin:15px 0;padding:10px;background:#f8f9fa;border-radius:4px'>";
        echo "<strong>📋 {$col['name']}</strong><br>";
        
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM vendor_reward_templates LIKE ?");
        $stmt->execute([$col['name']]);
        
        if ($stmt->rowCount() > 0) {
            echo "<span style='color:#856404'>⚠️ Existiert bereits</span>";
            $skipped++;
        } else {
            try {
                $sql = "ALTER TABLE vendor_reward_templates ADD COLUMN {$col['name']} {$col['def']} AFTER {$col['after']}";
                $pdo->exec($sql);
                echo "<span style='color:#28a745'>✅ Erfolgreich hinzugefügt</span>";
                $added++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<span style='color:#856404'>⚠️ Existiert bereits</span>";
                    $skipped++;
                } else {
                    echo "<span style='color:#dc3545'>❌ Fehler: " . htmlspecialchars($e->getMessage()) . "</span>";
                }
            }
        }
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Migration abgeschlossen!</h3>";
    echo "<ul>";
    echo "<li><strong>$added</strong> Spalten hinzugefügt</li>";
    echo "<li><strong>$skipped</strong> Spalten übersprungen (existierten bereits)</li>";
    echo "</ul>";
    echo "<p><strong>Nächste Schritte:</strong></p>";
    echo "<ol>";
    echo "<li>Lösche diese Datei (run-vendor-fix.php)</li>";
    echo "<li>Gehe zum Vendor-Bereich und teste die neuen Felder</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><h3>❌ Fehler</h3><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
}

echo "</body></html>";
?>