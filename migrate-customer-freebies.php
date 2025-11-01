<?php
/**
 * Customer Freebies Migration
 * Korrigiert die Tabellenstruktur auf die neue Version
 * Aufruf: https://app.mehr-infos-jetzt.de/migrate-customer-freebies.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Freebies Migration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #1a1a2e; margin-bottom: 10px; }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .info {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
        }
        .step {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid #667eea;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        pre {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 6px;
            font-size: 12px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔄 Customer Freebies Migration</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Aktualisiert die Tabellenstruktur auf die neue Version</p>';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebies'");
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Tabelle customer_freebies nicht gefunden!');
    }
    
    echo '<div class="status success">✅ Tabelle gefunden</div>';
    
    // Get current columns
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $existing_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    echo '<div class="step"><strong>Schritt 1:</strong> Analysiere aktuelle Struktur...</div>';
    echo '<pre>Vorhandene Spalten: ' . implode(', ', $existing_columns) . '</pre>';
    
    // Backup old data if exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer_freebies");
    $data_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($data_count > 0) {
        echo '<div class="status warning">';
        echo "⚠️ <strong>Achtung:</strong> Es existieren $data_count Datensätze. Diese werden migriert.";
        echo '</div>';
    }
    
    echo '<div class="step"><strong>Schritt 2:</strong> Starte Migration...</div>';
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Rename/Add columns
        $migrations = [
            // Umbenennen von custom_* zu standard Namen
            "ALTER TABLE customer_freebies CHANGE custom_headline headline VARCHAR(255)" => 
                in_array('custom_headline', $existing_columns),
            
            "ALTER TABLE customer_freebies CHANGE custom_subheadline subheadline VARCHAR(500)" => 
                in_array('custom_subheadline', $existing_columns),
            
            "ALTER TABLE customer_freebies CHANGE custom_cta_button_text cta_text VARCHAR(255)" => 
                in_array('custom_cta_button_text', $existing_columns),
            
            "ALTER TABLE customer_freebies CHANGE custom_mockup_url mockup_image_url VARCHAR(500)" => 
                in_array('custom_mockup_url', $existing_columns),
            
            "ALTER TABLE customer_freebies CHANGE unique_url unique_id VARCHAR(100)" => 
                in_array('unique_url', $existing_columns),
            
            // Neue Spalten hinzufügen
            "ALTER TABLE customer_freebies ADD COLUMN preheadline VARCHAR(255) AFTER subheadline" => 
                !in_array('preheadline', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN bullet_points TEXT AFTER preheadline" => 
                !in_array('bullet_points', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN layout VARCHAR(50) DEFAULT 'hybrid' AFTER cta_text" => 
                !in_array('layout', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN background_color VARCHAR(20) DEFAULT '#FFFFFF' AFTER layout" => 
                !in_array('background_color', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN primary_color VARCHAR(20) DEFAULT '#8B5CF6' AFTER background_color" => 
                !in_array('primary_color', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN raw_code TEXT AFTER primary_color" => 
                !in_array('raw_code', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN url_slug VARCHAR(255) AFTER unique_id" => 
                !in_array('url_slug', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN freebie_clicks INT DEFAULT 0" => 
                !in_array('freebie_clicks', $existing_columns),
            
            "ALTER TABLE customer_freebies ADD COLUMN thank_you_clicks INT DEFAULT 0" => 
                !in_array('thank_you_clicks', $existing_columns),
            
            // Unnötige Spalten entfernen
            "ALTER TABLE customer_freebies DROP COLUMN name" => 
                in_array('name', $existing_columns),
            
            "ALTER TABLE customer_freebies DROP COLUMN status" => 
                in_array('status', $existing_columns),
            
            "ALTER TABLE customer_freebies DROP COLUMN course_id" => 
                in_array('course_id', $existing_columns),
        ];
        
        $executed = 0;
        $skipped = 0;
        
        foreach ($migrations as $sql => $should_execute) {
            if ($should_execute) {
                try {
                    $pdo->exec($sql);
                    echo '<div class="status success">✅ ' . htmlspecialchars(substr($sql, 0, 80)) . '...</div>';
                    $executed++;
                } catch (PDOException $e) {
                    echo '<div class="status error">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    throw $e;
                }
            } else {
                $skipped++;
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo '<div class="status success">';
        echo "<strong>✅ Migration erfolgreich!</strong><br>";
        echo "$executed Änderungen durchgeführt, $skipped übersprungen";
        echo '</div>';
        
        echo '<div class="step" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
        echo '<strong style="color: #065f46; font-size: 18px;">🎉 Tabelle erfolgreich migriert!</strong>';
        echo '</div>';
        
        echo '<div class="status info">';
        echo '<span style="font-size: 24px;">✓</span>';
        echo '<div>';
        echo '<strong>Die Tabelle ist jetzt bereit!</strong><br>';
        echo 'Alle Spalten entsprechen der neuen Struktur.';
        echo '</div>';
        echo '</div>';
        
        echo '<a href="/check-customer-freebies.php" class="button">Struktur überprüfen</a> ';
        echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div><strong>Migration fehlgeschlagen:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div></div>';
    
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

echo '</div></body></html>';
?>
