<?php
/**
 * Customer Freebies Migration Fix
 * Entfernt den Foreign Key und bereinigt die Tabelle
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-customer-freebies.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Freebies Fix</title>
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
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔧 Customer Freebies Fix</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Bereinigt die Tabellenstruktur</p>';

try {
    // Get current columns
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $existing_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    echo '<div class="step"><strong>Schritt 1:</strong> Suche Foreign Key Constraints...</div>';
    
    // Find all foreign keys
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'customer_freebies' 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ");
    $foreign_keys = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($foreign_keys) > 0) {
        echo '<div class="status info">';
        echo 'ℹ️ Gefunden: ' . implode(', ', $foreign_keys);
        echo '</div>';
        
        // Drop all foreign keys
        foreach ($foreign_keys as $fk_name) {
            try {
                $pdo->exec("ALTER TABLE customer_freebies DROP FOREIGN KEY `$fk_name`");
                echo '<div class="status success">✅ Foreign Key "$fk_name" entfernt</div>';
            } catch (PDOException $e) {
                echo '<div class="status error">❌ Fehler beim Entfernen von "$fk_name": ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    } else {
        echo '<div class="status success">✅ Keine Foreign Keys gefunden</div>';
    }
    
    echo '<div class="step"><strong>Schritt 2:</strong> Entferne unnötige Spalten...</div>';
    
    // Remove unnecessary columns
    $columns_to_remove = ['name', 'status', 'course_id'];
    $removed = 0;
    
    foreach ($columns_to_remove as $col) {
        if (in_array($col, $existing_columns)) {
            try {
                $pdo->exec("ALTER TABLE customer_freebies DROP COLUMN `$col`");
                echo '<div class="status success">✅ Spalte "$col" entfernt</div>';
                $removed++;
            } catch (PDOException $e) {
                echo '<div class="status error">❌ Fehler bei "$col": ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
    
    if ($removed === 0) {
        echo '<div class="status success">✅ Alle unnötigen Spalten bereits entfernt</div>';
    }
    
    echo '<div class="step"><strong>Schritt 3:</strong> Überprüfe fehlende Spalten...</div>';
    
    // Refresh column list
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $existing_columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    // Required columns
    $required_columns = [
        'id', 'customer_id', 'template_id', 'headline', 'subheadline', 
        'preheadline', 'bullet_points', 'cta_text', 'layout', 
        'background_color', 'primary_color', 'raw_code', 'unique_id', 
        'url_slug', 'mockup_image_url', 'freebie_clicks', 'thank_you_clicks',
        'created_at', 'updated_at'
    ];
    
    $missing = array_diff($required_columns, $existing_columns);
    
    if (empty($missing)) {
        echo '<div class="status success">✅ Alle benötigten Spalten vorhanden</div>';
    } else {
        echo '<div class="status error">';
        echo '⚠️ Fehlende Spalten: ' . implode(', ', $missing);
        echo '</div>';
    }
    
    echo '<div class="step" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
    echo '<strong style="color: #065f46; font-size: 18px;">🎉 Migration abgeschlossen!</strong>';
    echo '</div>';
    
    echo '<div class="status info">';
    echo '<span style="font-size: 24px;">✓</span>';
    echo '<div>';
    echo '<strong>Die Tabelle ist jetzt einsatzbereit!</strong><br>';
    echo 'Alle Spalten entsprechen der neuen Struktur und Foreign Keys wurden entfernt.';
    echo '</div>';
    echo '</div>';
    
    echo '<a href="/check-customer-freebies.php" class="button">Struktur prüfen</a> ';
    echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div><strong>Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div></div>';
}

echo '</div></body></html>';
?>
