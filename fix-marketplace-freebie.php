<?php
/**
 * FIX MARKETPLACE FREEBIE - Verschiebt Freebie + Videokurs zum richtigen User
 * KORRIGIERTE VERSION - Berücksichtigt customer_freebie_courses Struktur
 */

require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

$freebieId = 53;
$correctCustomerId = 17; // Micha Test2 (12@abnehmen-fitness.com)
$wrongCustomerId = 8;

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
    
    // SCHRITT 2: User 8 prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: User {$freebie['customer_id']} prüfen</h2>";
    
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
        echo "<p class='info'>ℹ️ User {$freebie['customer_id']} existiert nicht (wurde gelöscht)</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 3: Videokurs-Struktur prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Videokurs-Daten prüfen</h2>";
    
    // customer_freebie_courses prüfen
    $stmt = $pdo->query("SHOW TABLES LIKE 'customer_freebie_courses'");
    $courseTableExists = $stmt->fetch() !== false;
    
    if ($courseTableExists) {
        echo "<p class='success'>✓ Tabelle customer_freebie_courses existiert</p>";
        
        // Module des Freebies laden
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   (SELECT COUNT(*) FROM customer_freebie_lessons l WHERE l.module_id = m.id) as lesson_count
            FROM customer_freebie_modules m 
            WHERE m.customer_freebie_id = ?
            ORDER BY m.module_order
        ");
        $stmt->execute([$freebieId]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($modules) {
            echo "<p class='info'>🎓 " . count($modules) . " Modul(e) gefunden:</p>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Order</th><th>Lektionen</th></tr>";
            foreach ($modules as $module) {
                echo "<tr>";
                echo "<td>{$module['id']}</td>";
                echo "<td>" . htmlspecialchars($module['module_name']) . "</td>";
                echo "<td>{$module['module_order']}</td>";
                echo "<td>{$module['lesson_count']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Lektionen zählen
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM customer_freebie_lessons l
                JOIN customer_freebie_modules m ON l.module_id = m.id
                WHERE m.customer_freebie_id = ?
            ");
            $stmt->execute([$freebieId]);
            $lessonCount = $stmt->fetchColumn();
            echo "<p class='info'>📚 Gesamt: $lessonCount Lektion(en)</p>";
            
        } else {
            echo "<p class='warning'>⚠️ Keine Module gefunden für Freebie $freebieId</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Tabelle customer_freebie_courses existiert nicht!</p>";
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
        echo "<h2>SCHRITT 5: FIX DURCHFÜHREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // Nur das Freebie verschieben - der Videokurs ist bereits über customer_freebie_id verknüpft!
            if ($freebie['customer_id'] != $correctCustomerId) {
                $stmt = $pdo->prepare("UPDATE customer_freebies SET customer_id = ? WHERE id = ?");
                $stmt->execute([$correctCustomerId, $freebieId]);
                echo "<p class='success'>✓ Freebie $freebieId: customer_id von {$freebie['customer_id']} auf $correctCustomerId geändert</p>";
            } else {
                echo "<p class='info'>ℹ️ Freebie hat bereits die richtige customer_id</p>";
            }
            
            // Videokurs wird automatisch mitverschoben, da er über customer_freebie_id verknüpft ist
            if ($modules) {
                echo "<p class='success'>✓ Videokurs (Module & Lektionen) bleiben automatisch mit Freebie verknüpft</p>";
                echo "<p class='info'>ℹ️ Keine Änderungen an customer_freebie_modules/lessons nötig</p>";
            }
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH! Alle Änderungen wurden gespeichert!</p>";
            echo "<p><a href='/customer/dashboard.php?page=freebies' style='display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px;'>→ Zu Meine Freebies</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNGS-BUTTON
        echo "<div class='box'>";
        echo "<h2>BEREIT ZUM REPARIEREN?</h2>";
        echo "<p><strong>Das wird passieren:</strong></p>";
        echo "<ul>";
        if ($freebie['customer_id'] != $correctCustomerId) {
            echo "<li>✅ Freebie $freebieId: customer_id {$freebie['customer_id']} → $correctCustomerId</li>";
            if ($modules) {
                echo "<li>✅ Videokurs mit " . count($modules) . " Modul(en) bleibt automatisch verknüpft</li>";
            }
        } else {
            echo "<li>ℹ️ Freebie hat bereits richtige customer_id</li>";
        }
        echo "</ul>";
        
        echo "<p><a href='?confirm=yes' style='display: inline-block; background: #10b981; color: white; padding: 16px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 20px;'>🔧 JETZT REPARIEREN</a></p>";
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