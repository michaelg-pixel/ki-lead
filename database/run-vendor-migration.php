<?php
/**
 * Direct Migration Script - Vendor Template Enhancements
 * Fügt fehlende Spalten zur vendor_reward_templates Tabelle hinzu
 */

// Direkt ausführbar - keine Authentifizierung erforderlich
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Template Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            color: #856404;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .step-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎨 Vendor Template Migration</h1>
        
        <?php
        try {
            $pdo = getDBConnection();
            $success_count = 0;
            $skip_count = 0;
            $error_count = 0;
            
            echo '<div class="info">';
            echo '<strong>Migration gestartet...</strong><br>';
            echo 'Füge fehlende Spalten zur vendor_reward_templates Tabelle hinzu.';
            echo '</div>';
            
            // Liste der hinzuzufügenden Spalten
            $columns = [
                [
                    'name' => 'marketplace_price',
                    'definition' => 'DECIMAL(10,2) DEFAULT 0.00',
                    'after' => 'suggested_referrals_required',
                    'description' => 'Preis für Marketplace'
                ],
                [
                    'name' => 'product_mockup_url',
                    'definition' => 'VARCHAR(500) NULL',
                    'after' => 'preview_image',
                    'description' => 'URL zum Mockup-Bild des Produkts'
                ],
                [
                    'name' => 'course_duration',
                    'definition' => 'VARCHAR(100) NULL',
                    'after' => 'reward_instructions',
                    'description' => 'Dauer des Videokurses'
                ],
                [
                    'name' => 'original_product_link',
                    'definition' => 'VARCHAR(500) NULL',
                    'after' => 'course_duration',
                    'description' => 'Link zum Original-Produkt'
                ]
            ];
            
            // Prüfe jede Spalte und füge sie hinzu falls nötig
            foreach ($columns as $column) {
                echo '<div class="step">';
                echo '<div class="step-title">📋 ' . htmlspecialchars($column['name']) . '</div>';
                echo '<small>' . htmlspecialchars($column['description']) . '</small><br><br>';
                
                // Prüfe ob Spalte existiert
                $check_sql = "SHOW COLUMNS FROM vendor_reward_templates LIKE ?";
                $stmt = $pdo->prepare($check_sql);
                $stmt->execute([$column['name']]);
                
                if ($stmt->rowCount() > 0) {
                    echo '<span style="color: #856404;">⚠️ Spalte existiert bereits - übersprungen</span>';
                    $skip_count++;
                } else {
                    // Spalte hinzufügen
                    try {
                        $alter_sql = "ALTER TABLE vendor_reward_templates 
                                     ADD COLUMN {$column['name']} {$column['definition']} 
                                     AFTER {$column['after']}";
                        
                        $pdo->exec($alter_sql);
                        
                        echo '<span style="color: #28a745;">✅ Erfolgreich hinzugefügt</span>';
                        $success_count++;
                        
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                            echo '<span style="color: #856404;">⚠️ Spalte existiert bereits - übersprungen</span>';
                            $skip_count++;
                        } else {
                            echo '<span style="color: #dc3545;">❌ Fehler: ' . htmlspecialchars($e->getMessage()) . '</span>';
                            $error_count++;
                        }
                    }
                }
                
                echo '</div>';
            }
            
            // Zusammenfassung
            echo '<div class="success">';
            echo '<h3>📊 Migrations-Zusammenfassung</h3>';
            echo '<ul>';
            echo '<li><strong>' . $success_count . '</strong> Spalten erfolgreich hinzugefügt</li>';
            echo '<li><strong>' . $skip_count . '</strong> Spalten bereits vorhanden (übersprungen)</li>';
            if ($error_count > 0) {
                echo '<li><strong>' . $error_count . '</strong> Fehler aufgetreten</li>';
            }
            echo '</ul>';
            echo '</div>';
            
            if ($error_count === 0) {
                echo '<div class="success">';
                echo '<h3>✅ Migration erfolgreich abgeschlossen!</h3>';
                echo '<p><strong>Nächste Schritte:</strong></p>';
                echo '<ol>';
                echo '<li>Gehe zum Vendor-Bereich im Dashboard</li>';
                echo '<li>Erstelle oder bearbeite ein Template</li>';
                echo '<li>Die neuen Felder sollten nun im Formular erscheinen</li>';
                echo '</ol>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h3>⚠️ Migration mit Fehlern abgeschlossen</h3>';
                echo '<p>Einige Spalten konnten nicht hinzugefügt werden. Prüfe die Fehler oben.</p>';
                echo '</div>';
            }
            
            // Zeige aktuelle Tabellenstruktur
            echo '<div class="info">';
            echo '<h3>📋 Aktuelle Tabellenstruktur (vendor_reward_templates)</h3>';
            echo '<small>Neue Spalten sind fett markiert</small><br><br>';
            
            $columns_sql = "SHOW COLUMNS FROM vendor_reward_templates";
            $stmt = $pdo->query($columns_sql);
            $all_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $new_columns = ['marketplace_price', 'product_mockup_url', 'course_duration', 'original_product_link'];
            
            echo '<ul style="font-family: monospace; font-size: 12px;">';
            foreach ($all_columns as $col) {
                $is_new = in_array($col['Field'], $new_columns);
                $style = $is_new ? 'font-weight: bold; color: #28a745;' : '';
                echo '<li style="' . $style . '">' . htmlspecialchars($col['Field']) . ' - ' . htmlspecialchars($col['Type']) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<h3>❌ Kritischer Fehler</h3>';
            echo '<p><strong>Nachricht:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p><strong>Code:</strong> ' . $e->getCode() . '</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
            <small>Diese Datei kann nach erfolgreicher Migration gelöscht werden.</small>
        </div>
    </div>
</body>
</html>