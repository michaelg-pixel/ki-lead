<?php
/**
 * Fix UNIQUE Constraint in customer_freebies
 * Problem: Constraint ist nur auf customer_id, sollte aber (customer_id, template_id) sein
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-unique-constraint.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix UNIQUE Constraint</title>
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
            max-width: 900px;
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
        .warning {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            color: #92400e;
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
            margin: 10px 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔧 Fix UNIQUE Constraint Problem</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Der UNIQUE Constraint ist falsch konfiguriert</p>';

try {
    // Schritt 1: Aktuellen Constraint prüfen
    echo '<div class="step"><strong>Schritt 1:</strong> Prüfe aktuellen UNIQUE Constraint...</div>';
    
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'customer_freebies'
        AND CONSTRAINT_NAME = 'unique_customer_freebie'
        ORDER BY ORDINAL_POSITION
    ");
    $current_constraint = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($current_constraint) > 0) {
        $columns = array_column($current_constraint, 'COLUMN_NAME');
        
        echo '<div class="info">';
        echo '<strong>Aktueller Constraint:</strong><br>';
        echo 'Name: <code>unique_customer_freebie</code><br>';
        echo 'Spalten: <code>' . implode(', ', $columns) . '</code>';
        echo '</div>';
        
        // Prüfe ob korrekt
        $is_correct = (count($columns) === 2 && in_array('customer_id', $columns) && in_array('template_id', $columns));
        
        if ($is_correct) {
            echo '<div class="status success">';
            echo '✅ Constraint ist korrekt konfiguriert!';
            echo '</div>';
            
            echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
            
        } else {
            echo '<div class="status error">';
            echo '<span style="font-size: 24px;">❌</span>';
            echo '<div>';
            echo '<strong>Problem gefunden!</strong><br>';
            echo 'Der Constraint ist nur auf <code>' . implode(', ', $columns) . '</code><br>';
            echo 'Er sollte aber auf <code>(customer_id, template_id)</code> sein!';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="warning">';
            echo '<strong>Was bedeutet das?</strong><br>';
            echo 'Aktuell: Ein Kunde kann nur EIN Freebie haben (egal welches Template)<br>';
            echo 'Richtig: Ein Kunde kann EIN Freebie PRO Template haben';
            echo '</div>';
            
            // Lösung anbieten
            if (!isset($_POST['fix_constraint'])) {
                echo '<h2 style="margin-top: 30px;">💡 Lösung</h2>';
                
                echo '<div class="info">';
                echo '<strong>Der Constraint wird neu erstellt:</strong><br>';
                echo '<ol style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">';
                echo '<li>Alter Constraint wird entfernt</li>';
                echo '<li>Neuer Constraint auf (customer_id, template_id) wird erstellt</li>';
                echo '<li>Jeder Kunde kann dann ein Freebie pro Template haben</li>';
                echo '</ol>';
                echo '</div>';
                
                echo '<pre>';
                echo '-- 1. Alten Constraint entfernen' . "\n";
                echo 'ALTER TABLE customer_freebies' . "\n";
                echo 'DROP INDEX unique_customer_freebie;' . "\n\n";
                echo '-- 2. Neuen Constraint erstellen' . "\n";
                echo 'ALTER TABLE customer_freebies' . "\n";
                echo 'ADD UNIQUE KEY unique_customer_template (customer_id, template_id);';
                echo '</pre>';
                
                echo '<form method="POST">';
                echo '<button type="submit" name="fix_constraint" class="button">';
                echo '🔧 Constraint jetzt korrigieren';
                echo '</button>';
                echo '</form>';
            }
        }
        
    } else {
        echo '<div class="status warning">⚠️ Constraint "unique_customer_freebie" nicht gefunden</div>';
        
        // Prüfe ob unique_customer_template existiert
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'customer_freebies'
            AND CONSTRAINT_NAME = 'unique_customer_template'
        ");
        
        if ($stmt->rowCount() > 0) {
            echo '<div class="status success">';
            echo '✅ Constraint "unique_customer_template" existiert bereits - alles OK!';
            echo '</div>';
        } else {
            echo '<div class="status warning">';
            echo '⚠️ Kein UNIQUE Constraint gefunden - sollte erstellt werden';
            echo '</div>';
        }
    }
    
    // Ausführung
    if (isset($_POST['fix_constraint'])) {
        echo '<div class="step" style="border-left-color: #667eea;">';
        echo '<strong>Ausführung:</strong> Korrigiere UNIQUE Constraint...';
        echo '</div>';
        
        try {
            // 1. Alten Constraint entfernen
            $pdo->exec("ALTER TABLE customer_freebies DROP INDEX unique_customer_freebie");
            
            echo '<div class="status success">';
            echo '✅ Alter Constraint entfernt';
            echo '</div>';
            
            // 2. Neuen Constraint erstellen
            $pdo->exec("
                ALTER TABLE customer_freebies 
                ADD UNIQUE KEY unique_customer_template (customer_id, template_id)
            ");
            
            echo '<div class="status success">';
            echo '✅ Neuer Constraint erstellt';
            echo '</div>';
            
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">🎉</span>';
            echo '<div>';
            echo '<strong>Erfolgreich!</strong><br>';
            echo 'Der UNIQUE Constraint ist jetzt korrekt konfiguriert.<br>';
            echo 'Du kannst jetzt ein Freebie pro Template erstellen!';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
            echo '<a href="/customer/dashboard.php?page=freebies" class="button">Zu den Freebies</a>';
            
        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<span style="font-size: 24px;">❌</span>';
            echo '<div>';
            echo '<strong>Fehler:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '</div>';
            
            // Versuche trotzdem den neuen Constraint zu erstellen
            try {
                $pdo->exec("
                    ALTER TABLE customer_freebies 
                    ADD UNIQUE KEY unique_customer_template (customer_id, template_id)
                ");
                echo '<div class="status success">✅ Neuer Constraint wurde trotzdem erstellt</div>';
            } catch (PDOException $e2) {
                echo '<div class="status error">❌ Auch neuer Constraint fehlgeschlagen: ' . htmlspecialchars($e2->getMessage()) . '</div>';
            }
        }
    }
    
} catch (Exception $e) {
    echo '<div class="status error">';
    echo '<span style="font-size: 24px;">❌</span>';
    echo '<div>';
    echo '<strong>Fehler:</strong><br>';
    echo htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div>';
}

echo '</div></body></html>';
?>