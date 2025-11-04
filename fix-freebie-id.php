<?php
/**
 * Fix freebie_id Problem in customer_freebies
 * Diagnose und Lösung für den SQL-Fehler: Field 'freebie_id' doesn't have a default value
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-freebie-id.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix freebie_id Problem</title>
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
        .button-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 13px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">';

echo '<h1>🔧 Fix freebie_id Problem</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Diagnose und Lösung für: Field \'freebie_id\' doesn\'t have a default value</p>';

try {
    // Schritt 1: Tabellen-Struktur prüfen
    echo '<div class="step"><strong>Schritt 1:</strong> Prüfe Tabellen-Struktur...</div>';
    
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    $has_freebie_id = in_array('freebie_id', $column_names);
    
    if ($has_freebie_id) {
        echo '<div class="status error">';
        echo '<span style="font-size: 24px;">❌</span>';
        echo '<div>';
        echo '<strong>Problem gefunden!</strong><br>';
        echo 'Die Spalte "freebie_id" existiert in der Tabelle, ist aber NICHT im Code definiert.';
        echo '</div>';
        echo '</div>';
        
        // Zeige Details zur freebie_id Spalte
        $freebie_id_col = array_filter($columns, function($col) {
            return $col['Field'] === 'freebie_id';
        });
        $freebie_id_col = reset($freebie_id_col);
        
        echo '<div class="info">';
        echo '<strong>Details zur freebie_id Spalte:</strong><br>';
        echo '<table style="margin: 10px 0;">';
        echo '<tr><th>Eigenschaft</th><th>Wert</th></tr>';
        echo '<tr><td>Typ</td><td>' . htmlspecialchars($freebie_id_col['Type']) . '</td></tr>';
        echo '<tr><td>Null erlaubt</td><td>' . htmlspecialchars($freebie_id_col['Null']) . '</td></tr>';
        echo '<tr><td>Key</td><td>' . ($freebie_id_col['Key'] ?: '-') . '</td></tr>';
        echo '<tr><td>Default</td><td>' . ($freebie_id_col['Default'] !== null ? htmlspecialchars($freebie_id_col['Default']) : '<strong style="color: #ef4444;">NULL (PROBLEM!)</strong>') . '</td></tr>';
        echo '<tr><td>Extra</td><td>' . ($freebie_id_col['Extra'] ?: '-') . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
    } else {
        echo '<div class="status success">';
        echo '✅ Tabelle hat KEIN freebie_id Feld - alles OK!';
        echo '</div>';
    }
    
    // Schritt 2: Prüfe ob freebie_id irgendwo im Code verwendet wird
    echo '<div class="step"><strong>Schritt 2:</strong> Prüfe Code-Verwendung...</div>';
    
    $files_to_check = [
        'customer/freebie-editor.php',
        'customer/freebies.php',
        'customer/my-freebies.php',
        'admin/dashboard.php',
        'admin/freebie-edit.php'
    ];
    
    $freebie_id_used = false;
    $usage_locations = [];
    
    foreach ($files_to_check as $file) {
        $file_path = __DIR__ . '/' . $file;
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            if (stripos($content, 'freebie_id') !== false) {
                $freebie_id_used = true;
                $usage_locations[] = $file;
            }
        }
    }
    
    if ($freebie_id_used) {
        echo '<div class="status warning">';
        echo '<span style="font-size: 24px;">⚠️</span>';
        echo '<div>';
        echo '<strong>Achtung:</strong> freebie_id wird in folgenden Dateien verwendet:<br>';
        echo '<ul style="margin: 10px 0; padding-left: 20px;">';
        foreach ($usage_locations as $location) {
            echo '<li>' . htmlspecialchars($location) . '</li>';
        }
        echo '</ul>';
        echo 'Vor dem Löschen der Spalte müssen diese Dateien angepasst werden!';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="status success">';
        echo '✅ freebie_id wird NICHT im Code verwendet - kann sicher entfernt werden!';
        echo '</div>';
    }
    
    // Schritt 3: Prüfe auf Daten-Abhängigkeiten
    echo '<div class="step"><strong>Schritt 3:</strong> Prüfe Daten-Abhängigkeiten...</div>';
    
    if ($has_freebie_id) {
        // Prüfe ob Foreign Key existiert
        $stmt = $pdo->query("
            SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'customer_freebies'
            AND COLUMN_NAME = 'freebie_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreign_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($foreign_keys) > 0) {
            echo '<div class="status warning">';
            echo '<span style="font-size: 24px;">⚠️</span>';
            echo '<div>';
            echo '<strong>Foreign Key gefunden:</strong><br>';
            foreach ($foreign_keys as $fk) {
                echo 'Constraint: ' . htmlspecialchars($fk['CONSTRAINT_NAME']) . '<br>';
                echo 'Referenz: ' . htmlspecialchars($fk['REFERENCED_TABLE_NAME']) . '.' . htmlspecialchars($fk['REFERENCED_COLUMN_NAME']) . '<br>';
            }
            echo 'Dieser muss zuerst entfernt werden!';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="status success">';
            echo '✅ Keine Foreign Keys auf freebie_id - kann direkt entfernt werden!';
            echo '</div>';
        }
        
        // Prüfe ob Daten in freebie_id existieren
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM customer_freebies WHERE freebie_id IS NOT NULL");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            echo '<div class="status warning">';
            echo '<span style="font-size: 24px;">⚠️</span>';
            echo '<div>';
            echo '<strong>Achtung:</strong> ' . $result['count'] . ' Einträge haben einen Wert in freebie_id.<br>';
            echo 'Diese Daten gehen verloren, wenn die Spalte gelöscht wird!';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="status success">';
            echo '✅ Keine Daten in freebie_id - kann sicher entfernt werden!';
            echo '</div>';
        }
    }
    
    // Lösungsvorschläge
    if ($has_freebie_id) {
        echo '<h2 style="margin-top: 30px;">💡 Lösungsvorschläge</h2>';
        
        echo '<div class="info">';
        echo '<strong>Empfohlene Lösung: Spalte entfernen</strong><br>';
        echo 'Da freebie_id nicht im Code verwendet wird, sollte die Spalte entfernt werden.';
        echo '</div>';
        
        if (!isset($_POST['remove_freebie_id'])) {
            echo '<form method="POST">';
            echo '<button type="submit" name="remove_freebie_id" class="button button-danger">';
            echo '🗑️ freebie_id Spalte jetzt entfernen';
            echo '</button>';
            echo '</form>';
            
            echo '<div class="warning" style="margin-top: 20px;">';
            echo '<strong>Alternativer Ansatz:</strong> Standardwert setzen<br>';
            echo 'Falls du die Spalte behalten möchtest, kannst du auch einen Standardwert (z.B. 0 oder NULL) setzen.';
            echo '</div>';
        }
    }
    
    // Ausführung der Lösung
    if (isset($_POST['remove_freebie_id']) && $has_freebie_id) {
        echo '<div class="step" style="border-left-color: #ef4444;">';
        echo '<strong>Ausführung:</strong> Entferne freebie_id Spalte...';
        echo '</div>';
        
        try {
            // Prüfe und entferne Foreign Keys
            $stmt = $pdo->query("
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'customer_freebies'
                AND COLUMN_NAME = 'freebie_id'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $fks = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fks as $fk_name) {
                $pdo->exec("ALTER TABLE customer_freebies DROP FOREIGN KEY `$fk_name`");
                echo '<div class="status success">✅ Foreign Key "$fk_name" entfernt</div>';
            }
            
            // Entferne die Spalte
            $pdo->exec("ALTER TABLE customer_freebies DROP COLUMN freebie_id");
            
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">🎉</span>';
            echo '<div>';
            echo '<strong>Erfolgreich!</strong><br>';
            echo 'Die Spalte "freebie_id" wurde entfernt. Der Fehler sollte jetzt behoben sein.';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
            echo '<a href="/check-customer-freebies.php" class="button">Struktur prüfen</a>';
            
        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<span style="font-size: 24px;">❌</span>';
            echo '<div>';
            echo '<strong>Fehler beim Entfernen:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '</div>';
        }
    }
    
    // Zusammenfassung
    if (!$has_freebie_id) {
        echo '<div class="step" style="border-left-color: #10b981; background: #d1fae5; margin-top: 30px;">';
        echo '<strong style="color: #065f46; font-size: 18px;">✅ Alles in Ordnung!</strong><br>';
        echo 'Die Tabelle ist korrekt konfiguriert.';
        echo '</div>';
        
        echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
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