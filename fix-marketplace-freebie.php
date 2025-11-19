<?php
/**
 * FIX MARKETPLACE FREEBIE - Verschiebt Freebie + Kurs zum richtigen User
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
        pre { background: #000; padding: 10px; border-radius: 5px; overflow-x: auto; }
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
    echo "<p>Aktuelle customer_id: <strong>" . $freebie['customer_id'] . "</strong></p>";
    echo "<p>Ziel customer_id: <strong>$correctCustomerId</strong></p>";
    
    if ($freebie['customer_id'] == $correctCustomerId) {
        echo "<p class='success'>✓ Freebie hat bereits die richtige customer_id!</p>";
    } else {
        echo "<p class='error'>❌ Freebie hat falsche customer_id: " . $freebie['customer_id'] . "</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 2: User 8 prüfen
    echo "<div class='box'>";
    echo "<h2>SCHRITT 2: User $wrongCustomerId prüfen</h2>";
    
    $stmt = $pdo->prepare("SELECT id, email, name, created_at FROM users WHERE id = ?");
    $stmt->execute([$wrongCustomerId]);
    $user8 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user8) {
        echo "<p class='info'>👤 User $wrongCustomerId gefunden:</p>";
        echo "<pre>";
        print_r($user8);
        echo "</pre>";
    } else {
        echo "<p class='info'>ℹ️ User $wrongCustomerId existiert nicht (wurde gelöscht)</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 3: Kurs prüfen (mit dynamischer Spalten-Erkennung)
    echo "<div class='box'>";
    echo "<h2>SCHRITT 3: Zugehörigen Kurs finden</h2>";
    
    // Tabellenstruktur prüfen
    $stmt = $pdo->query("DESCRIBE courses");
    $courseColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p class='info'>📋 Vorhandene Spalten in courses: " . implode(', ', $courseColumns) . "</p>";
    
    // Die richtige ID-Spalte finden
    $userIdColumn = null;
    if (in_array('customer_id', $courseColumns)) {
        $userIdColumn = 'customer_id';
    } elseif (in_array('user_id', $courseColumns)) {
        $userIdColumn = 'user_id';
    }
    
    if (!$userIdColumn) {
        echo "<p class='error'>❌ Keine User-ID-Spalte in courses gefunden!</p>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<p class='success'>✓ User-ID-Spalte: <strong>$userIdColumn</strong></p>";
    
    // Kurs beim falschen User suchen
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE $userIdColumn = ?");
    $stmt->execute([$freebie['customer_id']]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($courses) {
        echo "<p class='info'>🎓 " . count($courses) . " Kurs(e) gefunden bei customer_id " . $freebie['customer_id'] . ":</p>";
        foreach ($courses as $course) {
            echo "<pre>";
            print_r($course);
            echo "</pre>";
        }
    } else {
        echo "<p class='info'>ℹ️ Kein Kurs gefunden für customer_id " . $freebie['customer_id'] . "</p>";
    }
    
    echo "</div>";
    
    // SCHRITT 4: FIX DURCHFÜHREN
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        echo "<div class='box'>";
        echo "<h2>SCHRITT 4: FIX DURCHFÜHREN</h2>";
        
        $pdo->beginTransaction();
        
        try {
            // 4.1: Freebie verschieben
            if ($freebie['customer_id'] != $correctCustomerId) {
                $stmt = $pdo->prepare("UPDATE customer_freebies SET customer_id = ? WHERE id = ?");
                $stmt->execute([$correctCustomerId, $freebieId]);
                echo "<p class='success'>✓ Freebie $freebieId: customer_id auf $correctCustomerId geändert</p>";
            }
            
            // 4.2: Kurse verschieben
            if ($courses) {
                foreach ($courses as $course) {
                    $stmt = $pdo->prepare("UPDATE courses SET $userIdColumn = ? WHERE id = ?");
                    $stmt->execute([$correctCustomerId, $course['id']]);
                    echo "<p class='success'>✓ Kurs " . $course['id'] . ": $userIdColumn auf $correctCustomerId geändert</p>";
                }
            }
            
            $pdo->commit();
            
            echo "<p class='success'>🎉 ERFOLGREICH! Alle Änderungen wurden gespeichert!</p>";
            echo "<p><a href='/customer/dashboard.php?page=freebies' style='display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; margin-top: 20px;'>→ Zu Meine Freebies</a></p>";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<p class='error'>❌ Fehler: " . $e->getMessage() . "</p>";
        }
        
        echo "</div>";
        
    } else {
        // BESTÄTIGUNGS-BUTTON
        echo "<div class='box'>";
        echo "<h2>BEREIT ZUM REPARIEREN?</h2>";
        echo "<p><strong>Das wird passiert:</strong></p>";
        echo "<ul>";
        if ($freebie['customer_id'] != $correctCustomerId) {
            echo "<li>✅ Freebie $freebieId: customer_id → $correctCustomerId</li>";
        } else {
            echo "<li>ℹ️ Freebie hat bereits richtige customer_id</li>";
        }
        if ($courses) {
            echo "<li>✅ " . count($courses) . " Kurs(e): $userIdColumn → $correctCustomerId</li>";
        } else {
            echo "<li>ℹ️ Keine Kurse zu verschieben</li>";
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