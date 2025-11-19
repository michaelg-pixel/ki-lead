<?php
/**
 * FIX MARKETPLACE FREEBIE - Verschiebt Freebie + Videokurs zum richtigen User
 * VERSION 3 - Findet die richtige Videokurs-Struktur automatisch
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$freebieId = 53;
$correctCustomerId = 17; // Micha Test2 (12@abnehmen-fitness.com)

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Fix Marketplace Freebie</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #0f0f1e; color: #fff; }
        .box { background: #1a1a2e; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #667eea; }
        h2 { color: #667eea; }
        .success { background: #10b981; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .error { background: #ff4444; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .info { background: #3b82f6; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; }
        .warning { background: #f59e0b; padding: 5px 10px; border-radius: 3px; display: inline-block; margin: 5px 0; color: #000; }
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; max-height: 300px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #667eea; }
        .btn { display: inline-block; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px; }
        .btn-primary { background: #10b981; color: white; }
        .btn-secondary { background: #667eea; color: white; }
    </style>
</head>
<body>
<h1>🔧 Fix Marketplace Freebie ID $freebieId</h1>";

try {
    // SCHRITT 1: Freebie prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 1: Freebie prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT * FROM customer_freebies WHERE id = ?");
    $stmt->execute([$freebieId]);
    $freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$freebie) {
        echo "<p class='error'>❌ Freebie $freebieId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='info'>📦 Freebie gefunden: " . htmlspecialchars($freebie['headline']) . "</p>";
    echo "<table>";
    echo "<tr><th>Feld</th><th>Wert</th></tr>";
    echo "<tr><td>id</td><td>{$freebie['id']}</td></tr>";
    echo "<tr><td>customer_id</td><td>{$freebie['customer_id']} " . ($freebie['customer_id'] == $correctCustomerId ? '<span class="success">✓ KORREKT</span>' : '<span class="error">❌ FALSCH (sollte 17 sein)</span>') . "</td></tr>";
    echo "<tr><td>template_id</td><td>" . ($freebie['template_id'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>copied_from_freebie_id</td><td>" . ($freebie['copied_from_freebie_id'] ?? 'NULL') . "</td></tr>";
    echo "<tr><td>has_course</td><td>" . ($freebie['has_course'] ?? '0') . "</td></tr>";
    echo "</table>";
    
    echo "</div>";
    
    // SCHRITT 2: User prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: Aktueller User {$freebie['customer_id']} prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT id, email, name, created_at FROM users WHERE id = ?");
    $stmt->execute([$freebie['customer_id']]);
    $wrongUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($wrongUser) {
        echo "<p class='info'>👤 User {$freebie['customer_id']} gefunden:</p>";
        echo "<table>";
        foreach ($wrongUser as $key => $value) {
            echo "<tr><th>$key</th><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>ℹ️ User {$freebie['customer_id']} existiert nicht</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 3: ALLE Videokurs-Tabellen durchsuchen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Videokurs-Struktur finden</h2>";
    
    $modules = [];
    $lessons = [];
    $courseTableFound = false;
    
    // Mögliche Tabellen-Kombinationen durchprobieren
    $tableCombinations = [
        ['modules' => 'customer_freebie_modules', 'lessons' => 'customer_freebie_lessons'],
        ['modules' => 'freebie_modules', 'lessons' => 'freebie_lessons'],
        ['modules' => 'course_modules', 'lessons' => 'course_lessons']
    ];
    
    foreach ($tableCombinations as $tables) {
        try {
            // Module-Tabelle prüfen
            $stmt = $pdo->query("SHOW TABLES LIKE '{$tables['modules']}'");
            $moduleTableExists = $stmt->fetch() !== false;
            
            if ($moduleTableExists) {
                // Struktur der Tabelle prüfen
                $stmt = $pdo->query("DESCRIBE {$tables['modules']}");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<p class='success'>✓ Tabelle {$tables['modules']} gefunden</p>";
                echo "<p class='info'>Spalten: " . implode(', ', $columns) . "</p>";
                
                // Richtige Spalte für Freebie-Verknüpfung finden
                $freebieColumn = null;
                if (in_array('customer_freebie_id', $columns)) {
                    $freebieColumn = 'customer_freebie_id';
                } elseif (in_array('freebie_id', $columns)) {
                    $freebieColumn = 'freebie_id';
                }
                
                if ($freebieColumn) {
                    // Module laden
                    $stmt = $pdo->prepare("SELECT * FROM {$tables['modules']} WHERE $freebieColumn = ? ORDER BY module_order");
                    $stmt->execute([$freebieId]);
                    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($modules) {
                        echo "<p class='success'>🎓 " . count($modules) . " Modul(e) gefunden in {$tables['modules']}</p>";
                        $courseTableFound = true;
                        
                        // Lektionen zählen
                        $stmt = $pdo->query("SHOW TABLES LIKE '{$tables['lessons']}'");
                        if ($stmt->fetch()) {
                            $stmt = $pdo->prepare("
                                SELECT COUNT(*) as total
                                FROM {$tables['lessons']} l
                                JOIN {$tables['modules']} m ON l.module_id = m.id
                                WHERE m.$freebieColumn = ?
                            ");
                            $stmt->execute([$freebieId]);
                            $lessonCount = $stmt->fetchColumn();
                            echo "<p class='info'>📚 $lessonCount Lektion(en) in {$tables['lessons']}</p>";
                        }
                        
                        // Zeige Module
                        echo "<table>";
                        echo "<tr><th>ID</th><th>Name</th><th>Order</th></tr>";
                        foreach ($modules as $module) {
                            echo "<tr>";
                            echo "<td>{$module['id']}</td>";
                            echo "<td>" . htmlspecialchars($module['module_name']) . "</td>";
                            echo "<td>{$module['module_order']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        
                        break; // Gefunden, raus aus der Schleife
                    }
                }
            }
        } catch (Exception $e) {
            // Tabelle existiert nicht, weiter zur nächsten
        }
    }
    
    if (!$courseTableFound) {
        echo "<p class='warning'>⚠️ Keine Videokurs-Tabellen gefunden oder keine Module für Freebie $freebieId</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: Ziel-User prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 4: Ziel-User $correctCustomerId prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT id, email, name, created_at FROM users WHERE id = ?");
    $stmt->execute([$correctCustomerId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($targetUser) {
        echo "<p class='success'>✓ Ziel-User gefunden:</p>";
        echo "<table>";
        foreach ($targetUser as $key => $value) {
            echo "<tr><th>$key</th><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ Ziel-User $correctCustomerId nicht gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "</div>";
    
    // SCHRITT 5: FIX DURCHFÜHREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 5: REPARATUR DURCHFÜHREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            if ($freebie['customer_id'] != $correctCustomerId) {
                $stmt = $pdo->prepare("UPDATE customer_freebies SET customer_id = ? WHERE id = ?");
                $stmt->execute([$correctCustomerId, $freebieId]);
                echo "<p class='success'>✓ Freebie $freebieId: customer_id von {$freebie['customer_id']} auf $correctCustomerId geändert</p>";
            } else {
                echo "<p class='info'>ℹ️ Freebie hat bereits die richtige customer_id</p>";
            }
            
            if ($courseTableFound && count($modules) > 0) {
                echo "<p class='success'>✓ Videokurs mit " . count($modules) . " Modul(en) bleibt automatisch verknüpft</p>";
            }
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH REPARIERT!</p>";
            echo "<p><a href='/customer/dashboard.php?page=freebies' class='btn btn-secondary'>→ Zu Meine Freebies</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNGS-BUTTON
        echo "<div class='box'>";
        echo "<h2>🚀 BEREIT ZUM REPARIEREN?</h2>";
        echo "<p><strong>Das wird passieren:</strong></p>";
        echo "<ul>";
        if ($freebie['customer_id'] != $correctCustomerId) {
            echo "<li>✅ Freebie $freebieId: customer_id {$freebie['customer_id']} → $correctCustomerId</li>";
            if ($courseTableFound && count($modules) > 0) {
                echo "<li>✅ Videokurs mit " . count($modules) . " Modul(en) bleibt verknüpft</li>";
            }
        } else {
            echo "<li>ℹ️ Freebie hat bereits richtige customer_id</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?confirm=yes' class='btn btn-primary'>🔧 JETZT REPARIEREN</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='box'>";
    echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?>