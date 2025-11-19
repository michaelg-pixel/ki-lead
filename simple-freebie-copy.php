<?php
/**
 * SIMPLE FREEBIE COMPARE & COPY
 * Vergleicht Freebie 7 und 53 und kopiert fehlende Felder
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Freebie Compare & Copy</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; font-size: 13px; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; background: #0f0f1e; }
        .diff { background: #f59e0b; color: #000; font-weight: bold; }
        .same { background: #10b981; color: #000; }
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 10px 0 0; color: white; border: none; cursor: pointer; }
        .btn-primary { background: #10b981; }
        .btn-secondary { background: #667eea; }
        .success { background: #10b981; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #ff4444; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<h1>🔍 Freebie 7 vs 53 - Vergleich & Kopie</h1>";

try {
    // SCHRITT 1: Beide Freebies laden
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id IN (7, 53) ORDER BY id");
    $stmt->execute();
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($freebies) != 2) {
        echo "<p class='error'>❌ Konnte nicht beide Freebies laden!</p></body></html>";
        exit;
    }
    
    $freebie7 = $freebies[0];
    $freebie53 = $freebies[1];
    
    // SCHRITT 2: Vergleich
    echo "<div class='box'>";
    echo "<h2>📊 Feld-für-Feld Vergleich</h2>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Freebie 7 (Original)</th><th>Freebie 53 (Kopie)</th><th>Status</th></tr>";
    
    $importantFields = [];
    
    foreach ($freebie7 as $field => $value7) {
        $value53 = $freebie53[$field] ?? null;
        
        $status = '';
        $class = '';
        
        if ($value7 == $value53) {
            $status = '✓ Gleich';
            $class = 'same';
        } else {
            $status = '⚠️ Unterschied';
            $class = 'diff';
            
            // Wichtige Felder merken
            if (stripos($field, 'course') !== false || $field === 'has_course') {
                $importantFields[$field] = $value7;
            }
        }
        
        // Nur wichtige oder unterschiedliche Felder anzeigen
        if ($class === 'diff' || stripos($field, 'course') !== false || $field === 'has_course' || $field === 'headline') {
            echo "<tr class='$class'>";
            echo "<td><strong>$field</strong></td>";
            echo "<td>" . htmlspecialchars(substr($value7 ?? 'NULL', 0, 50)) . "</td>";
            echo "<td>" . htmlspecialchars(substr($value53 ?? 'NULL', 0, 50)) . "</td>";
            echo "<td>$status</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    echo "</div>";
    
    // SCHRITT 3: Wichtige Unterschiede
    if ($importantFields) {
        echo "<div class='box'>";
        echo "<h2>🎓 VIDEOKURS-RELEVANTE FELDER</h2>";
        echo "<p>Diese Felder sind bei Freebie 7 gesetzt, aber bei Freebie 53 fehlen sie:</p>";
        echo "<ul>";
        foreach ($importantFields as $field => $value) {
            echo "<li><strong>$field:</strong> " . htmlspecialchars($value) . "</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    // SCHRITT 4: KOPIER-AKTION
    if (isset($_POST['copy'])) {
        echo "<div class='box'>";
        echo "<h2>🚀 KOPIERE VIDEOKURS-FELDER</h2>";
        
        $pdo->beginTransaction();
        
        try {
            $updates = [];
            $params = [];
            
            foreach ($importantFields as $field => $value) {
                $updates[] = "$field = ?";
                $params[] = $value;
            }
            
            if ($updates) {
                $params[] = 53; // WHERE id = 53
                
                $sql = "UPDATE customer_freebies SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo "<p class='success'>✅ Erfolgreich " . count($importantFields) . " Feld(er) kopiert!</p>";
                echo "<ul>";
                foreach ($importantFields as $field => $value) {
                    echo "<li>$field = " . htmlspecialchars($value) . "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>ℹ️ Keine Felder zum Kopieren gefunden</p>";
            }
            
            $pdo->commit();
            
            echo "<p><a href='/customer/dashboard.php?page=freebies' class='btn btn-secondary'>→ Zu Meine Freebies</a></p>";
            echo "<p><small>Lade die Seite neu, um den Videokurs-Button zu sehen!</small></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        
    } else {
        // FORMULAR
        if ($importantFields) {
            echo "<div class='box'>";
            echo "<h2>✅ BEREIT ZUM KOPIEREN?</h2>";
            echo "<p>Folgende Videokurs-Felder werden von Freebie 7 zu Freebie 53 kopiert:</p>";
            echo "<ul>";
            foreach ($importantFields as $field => $value) {
                echo "<li><strong>$field:</strong> " . htmlspecialchars($value) . "</li>";
            }
            echo "</ul>";
            
            echo "<form method='POST'>";
            echo "<button type='submit' name='copy' class='btn btn-primary'>🎓 JETZT KOPIEREN</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<div class='box'>";
            echo "<p class='success'>✅ Alle Videokurs-Felder sind bereits identisch!</p>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='box'><p class='error'>❌ Fehler: " . $e->getMessage() . "</p></div>";
}

echo "</body></html>";
?>