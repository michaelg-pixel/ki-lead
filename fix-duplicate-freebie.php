<?php
/**
 * Fix Duplicate Entry Problem in customer_freebies
 * Diagnose und Lösung für: Duplicate entry for key 'unique_customer_freebie'
 * 
 * Aufruf: https://app.mehr-infos-jetzt.de/fix-duplicate-freebie.php
 */

require_once __DIR__ . '/config/database.php';

echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Duplicate Freebie Problem</title>
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
            max-width: 1000px;
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

echo '<h1>🔧 Fix Duplicate Freebie Problem</h1>';
echo '<p style="color: #666; margin-bottom: 30px;">Diagnose und Lösung für: Duplicate entry for key \'unique_customer_freebie\'</p>';

// Get user info from session if available
session_start();
$current_user_id = $_SESSION['user_id'] ?? null;

try {
    // Schritt 1: Prüfe UNIQUE Constraints
    echo '<div class="step"><strong>Schritt 1:</strong> Prüfe UNIQUE Constraints...</div>';
    
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'customer_freebies'
        AND CONSTRAINT_NAME LIKE '%unique%'
        ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION
    ");
    $unique_constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($unique_constraints) > 0) {
        echo '<div class="info">';
        echo '<strong>Gefundene UNIQUE Constraints:</strong><br>';
        echo '<table>';
        echo '<tr><th>Constraint Name</th><th>Spalte</th></tr>';
        
        $constraint_groups = [];
        foreach ($unique_constraints as $constraint) {
            $name = $constraint['CONSTRAINT_NAME'];
            if (!isset($constraint_groups[$name])) {
                $constraint_groups[$name] = [];
            }
            $constraint_groups[$name][] = $constraint['COLUMN_NAME'];
        }
        
        foreach ($constraint_groups as $name => $columns) {
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($name) . '</code></td>';
            echo '<td>' . implode(', ', array_map('htmlspecialchars', $columns)) . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="status warning">⚠️ Keine UNIQUE Constraints gefunden</div>';
    }
    
    // Schritt 2: Zeige bestehende Einträge für den aktuellen User
    if ($current_user_id) {
        echo '<div class="step"><strong>Schritt 2:</strong> Prüfe deine bestehenden Freebies...</div>';
        
        $stmt = $pdo->prepare("
            SELECT cf.*, f.name as template_name
            FROM customer_freebies cf
            LEFT JOIN freebies f ON cf.template_id = f.id
            WHERE cf.customer_id = ?
            ORDER BY cf.template_id, cf.created_at DESC
        ");
        $stmt->execute([$current_user_id]);
        $user_freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($user_freebies) > 0) {
            echo '<div class="info">';
            echo '<strong>Deine Freebies (' . count($user_freebies) . '):</strong><br>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Template ID</th><th>Template Name</th><th>Headline</th><th>Erstellt</th></tr>';
            
            foreach ($user_freebies as $freebie) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($freebie['id']) . '</td>';
                echo '<td>' . htmlspecialchars($freebie['template_id']) . '</td>';
                echo '<td>' . htmlspecialchars($freebie['template_name'] ?? 'Unbekannt') . '</td>';
                echo '<td>' . htmlspecialchars(substr($freebie['headline'], 0, 40)) . '...</td>';
                echo '<td>' . htmlspecialchars($freebie['created_at']) . '</td>';
                echo '</tr>';
            }
            
            echo '</table>';
            echo '</div>';
            
            // Prüfe auf Duplikate
            $template_counts = [];
            foreach ($user_freebies as $freebie) {
                $tid = $freebie['template_id'];
                if (!isset($template_counts[$tid])) {
                    $template_counts[$tid] = 0;
                }
                $template_counts[$tid]++;
            }
            
            $duplicates = array_filter($template_counts, function($count) { return $count > 1; });
            
            if (count($duplicates) > 0) {
                echo '<div class="status error">';
                echo '<span style="font-size: 24px;">⚠️</span>';
                echo '<div>';
                echo '<strong>Duplikate gefunden!</strong><br>';
                echo 'Du hast mehrere Einträge für dieselbe Template ID:<br>';
                foreach ($duplicates as $template_id => $count) {
                    echo '- Template ID ' . $template_id . ': ' . $count . ' Einträge<br>';
                }
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="status success">✅ Keine Duplikate gefunden</div>';
            }
            
        } else {
            echo '<div class="status info">ℹ️ Du hast noch keine Freebies erstellt</div>';
        }
    }
    
    // Schritt 3: Prüfe auf fehlende freebie_type Spalte
    echo '<div class="step"><strong>Schritt 3:</strong> Prüfe Tabellen-Struktur...</div>';
    
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $column_names = array_column($columns, 'Field');
    
    $has_freebie_type = in_array('freebie_type', $column_names);
    
    if (!$has_freebie_type) {
        echo '<div class="status error">';
        echo '<span style="font-size: 24px;">❌</span>';
        echo '<div>';
        echo '<strong>Problem gefunden!</strong><br>';
        echo 'Die Spalte "freebie_type" fehlt in der Tabelle. Der INSERT-Befehl im Code versucht diese Spalte zu befüllen, aber sie existiert nicht!';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="status success">✅ Spalte "freebie_type" existiert</div>';
    }
    
    // Lösungsvorschläge
    echo '<h2 style="margin-top: 30px;">💡 Lösungen</h2>';
    
    if (!$has_freebie_type) {
        echo '<div class="info">';
        echo '<strong>Lösung 1: Spalte "freebie_type" hinzufügen</strong><br>';
        echo 'Der Code erwartet diese Spalte. Sie sollte hinzugefügt werden.';
        echo '</div>';
        
        if (!isset($_POST['add_freebie_type'])) {
            echo '<form method="POST">';
            echo '<button type="submit" name="add_freebie_type" class="button">';
            echo '➕ freebie_type Spalte hinzufügen';
            echo '</button>';
            echo '</form>';
            
            echo '<pre>ALTER TABLE customer_freebies 
ADD COLUMN freebie_type VARCHAR(50) DEFAULT \'template\' AFTER mockup_image_url;</pre>';
        }
    }
    
    // Lösung 2: Duplikate bereinigen
    if ($current_user_id && isset($duplicates) && count($duplicates) > 0) {
        echo '<div class="warning">';
        echo '<strong>Lösung 2: Duplikate bereinigen</strong><br>';
        echo 'Behalte nur den neuesten Eintrag pro Template.';
        echo '</div>';
        
        if (!isset($_POST['remove_duplicates'])) {
            echo '<form method="POST">';
            echo '<button type="submit" name="remove_duplicates" class="button button-danger">';
            echo '🗑️ Duplikate entfernen (neuesten behalten)';
            echo '</button>';
            echo '</form>';
        }
    }
    
    // Ausführung: freebie_type hinzufügen
    if (isset($_POST['add_freebie_type'])) {
        echo '<div class="step" style="border-left-color: #667eea;">';
        echo '<strong>Ausführung:</strong> Füge freebie_type Spalte hinzu...';
        echo '</div>';
        
        try {
            $pdo->exec("
                ALTER TABLE customer_freebies 
                ADD COLUMN freebie_type VARCHAR(50) DEFAULT 'template' AFTER mockup_image_url
            ");
            
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">🎉</span>';
            echo '<div>';
            echo '<strong>Erfolgreich!</strong><br>';
            echo 'Die Spalte "freebie_type" wurde hinzugefügt.';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo '<div class="status success">';
                echo '✅ Spalte "freebie_type" existiert bereits!';
                echo '</div>';
            } else {
                echo '<div class="status error">';
                echo '<span style="font-size: 24px;">❌</span>';
                echo '<div>';
                echo '<strong>Fehler:</strong><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
                echo '</div>';
            }
        }
    }
    
    // Ausführung: Duplikate entfernen
    if (isset($_POST['remove_duplicates']) && $current_user_id) {
        echo '<div class="step" style="border-left-color: #ef4444;">';
        echo '<strong>Ausführung:</strong> Entferne Duplikate...';
        echo '</div>';
        
        try {
            // Für jede Template ID, behalte nur den neuesten
            foreach ($duplicates as $template_id => $count) {
                // Hole alle IDs für diese Template ID, sortiert nach created_at DESC
                $stmt = $pdo->prepare("
                    SELECT id FROM customer_freebies
                    WHERE customer_id = ? AND template_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$current_user_id, $template_id]);
                $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Behalte den ersten (neuesten), lösche alle anderen
                $keep_id = array_shift($ids);
                
                if (count($ids) > 0) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("DELETE FROM customer_freebies WHERE id IN ($placeholders)");
                    $stmt->execute($ids);
                    
                    echo '<div class="status success">';
                    echo '✅ Template ID ' . $template_id . ': ' . count($ids) . ' Duplikat(e) entfernt, ID ' . $keep_id . ' behalten';
                    echo '</div>';
                }
            }
            
            echo '<div class="status success">';
            echo '<span style="font-size: 24px;">🎉</span>';
            echo '<div>';
            echo '<strong>Fertig!</strong><br>';
            echo 'Alle Duplikate wurden entfernt.';
            echo '</div>';
            echo '</div>';
            
            echo '<a href="/customer/freebie-editor.php?template_id=17" class="button">Freebie Editor testen</a>';
            
        } catch (PDOException $e) {
            echo '<div class="status error">';
            echo '<span style="font-size: 24px;">❌</span>';
            echo '<div>';
            echo '<strong>Fehler:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '</div>';
        }
    }
    
    // Zusammenfassung
    echo '<div class="step" style="margin-top: 30px; border-left-color: #3b82f6;">';
    echo '<strong>Zusammenfassung:</strong><br>';
    echo '<ul style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">';
    echo '<li>UNIQUE Constraint verhindert mehrfache Einträge pro Customer+Template</li>';
    echo '<li>Code prüft ob Eintrag existiert und macht UPDATE statt INSERT</li>';
    echo '<li>Falls "freebie_type" Spalte fehlt, kann INSERT fehlschlagen</li>';
    echo '<li>Duplikate sollten bereinigt werden</li>';
    echo '</ul>';
    echo '</div>';
    
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