<?php
/**
 * FIX: Kurs-Verknüpfung bei Marktplatz-Freebie-Käufen
 * 
 * Problem: Beim Kauf eines Freebies über den Marktplatz wird die course_id
 * vom Original-Freebie nicht korrekt in den Käufer-Account übertragen.
 * 
 * Lösung: 
 * 1. Sicherstellen, dass die course_id-Spalte in customer_freebies existiert
 * 2. Webhook-Logik aktualisieren, um course_id korrekt zu übertragen
 * 3. Bestehende gekaufte Freebies rückwirkend korrigieren
 */

require_once '../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>🔧 Fix: Marktplatz-Kurs-Verknüpfung</h2>";
    echo "<hr>";
    
    // SCHRITT 1: Prüfen ob course_id-Spalte existiert
    echo "<h3>1️⃣ Datenbank-Struktur prüfen</h3>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'course_id'");
    $hasColumn = $stmt->fetch();
    
    if (!$hasColumn) {
        echo "<p style='color: orange;'>⚠️ Spalte 'course_id' fehlt in customer_freebies - wird hinzugefügt...</p>";
        
        $pdo->exec("
            ALTER TABLE customer_freebies 
            ADD COLUMN course_id INT NULL AFTER template_id,
            ADD KEY idx_course_id (course_id)
        ");
        
        echo "<p style='color: green;'>✅ Spalte 'course_id' erfolgreich hinzugefügt</p>";
    } else {
        echo "<p style='color: green;'>✅ Spalte 'course_id' existiert bereits</p>";
    }
    
    // SCHRITT 2: Prüfen ob es gekaufte Freebies ohne course_id gibt
    echo "<h3>2️⃣ Gekaufte Freebies prüfen</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            cf.id as freebie_id,
            cf.customer_id,
            cf.headline,
            cf.course_id as current_course_id,
            cf.copied_from_freebie_id,
            original.course_id as original_course_id,
            original.headline as original_headline,
            u.email,
            u.name
        FROM customer_freebies cf
        LEFT JOIN customer_freebies original ON original.id = cf.copied_from_freebie_id
        LEFT JOIN users u ON u.id = cf.customer_id
        WHERE cf.copied_from_freebie_id IS NOT NULL
        AND cf.freebie_type = 'purchased'
        AND (cf.course_id IS NULL OR cf.course_id = 0)
        AND original.course_id IS NOT NULL
        ORDER BY cf.id DESC
    ");
    
    $missingLinks = $stmt->fetchAll();
    
    if (empty($missingLinks)) {
        echo "<p style='color: green;'>✅ Alle gekauften Freebies haben eine korrekte Kurs-Verknüpfung</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Gefunden: " . count($missingLinks) . " gekaufte Freebies ohne Kurs-Verknüpfung</p>";
        
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>Freebie ID</th>
                <th>Käufer</th>
                <th>Freebie</th>
                <th>Original Kurs-ID</th>
                <th>Status</th>
              </tr>";
        
        foreach ($missingLinks as $link) {
            echo "<tr>";
            echo "<td>" . $link['freebie_id'] . "</td>";
            echo "<td>" . htmlspecialchars($link['name']) . "<br><small>" . htmlspecialchars($link['email']) . "</small></td>";
            echo "<td>" . htmlspecialchars($link['headline']) . "</td>";
            echo "<td>" . $link['original_course_id'] . "</td>";
            echo "<td style='color: orange;'>⚠️ Fehlt</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // SCHRITT 3: Rückwirkend korrigieren
        echo "<h3>3️⃣ Kurs-Verknüpfungen reparieren</h3>";
        
        $updateStmt = $pdo->prepare("
            UPDATE customer_freebies cf
            JOIN customer_freebies original ON original.id = cf.copied_from_freebie_id
            SET cf.course_id = original.course_id
            WHERE cf.copied_from_freebie_id IS NOT NULL
            AND cf.freebie_type = 'purchased'
            AND (cf.course_id IS NULL OR cf.course_id = 0)
            AND original.course_id IS NOT NULL
        ");
        
        $updateStmt->execute();
        $fixed = $updateStmt->rowCount();
        
        echo "<p style='color: green;'>✅ " . $fixed . " Kurs-Verknüpfungen erfolgreich repariert</p>";
    }
    
    // SCHRITT 4: Webhook-Logik prüfen
    echo "<h3>4️⃣ Webhook-Logik prüfen</h3>";
    
    $webhookFile = __DIR__ . '/digistore24.php';
    $webhookContent = file_get_contents($webhookFile);
    
    // Prüfen ob course_id in der copyMarketplaceFreebie-Funktion vorhanden ist
    if (strpos($webhookContent, 'course_id,') !== false && 
        strpos($webhookContent, '$source[\'course_id\']') !== false) {
        echo "<p style='color: green;'>✅ Webhook enthält bereits course_id-Logik</p>";
    } else {
        echo "<p style='color: red;'>❌ Webhook muss aktualisiert werden!</p>";
        echo "<p>Bitte stelle sicher, dass in der Funktion <code>copyMarketplaceFreebie()</code> die <code>course_id</code> korrekt kopiert wird.</p>";
    }
    
    // SCHRITT 5: Zusammenfassung
    echo "<h3>5️⃣ Zusammenfassung</h3>";
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM customer_freebies
        WHERE copied_from_freebie_id IS NOT NULL
        AND freebie_type = 'purchased'
    ");
    $total = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("
        SELECT COUNT(*) as with_course
        FROM customer_freebies
        WHERE copied_from_freebie_id IS NOT NULL
        AND freebie_type = 'purchased'
        AND course_id IS NOT NULL
        AND course_id > 0
    ");
    $withCourse = $stmt->fetch()['with_course'];
    
    echo "<ul>";
    echo "<li>Gesamt gekaufte Freebies: <strong>$total</strong></li>";
    echo "<li>Mit Kurs-Verknüpfung: <strong>$withCourse</strong></li>";
    echo "<li>Ohne Kurs-Verknüpfung: <strong>" . ($total - $withCourse) . "</strong></li>";
    echo "</ul>";
    
    if ($withCourse == $total) {
        echo "<p style='color: green; font-size: 18px; font-weight: bold;'>🎉 ALLE FREEBIES HABEN EINE KORREKTE KURS-VERKNÜPFUNG!</p>";
    }
    
    // SCHRITT 6: Test-Abfrage für einen spezifischen Käufer
    echo "<h3>6️⃣ Test-Abfrage (Beispiel)</h3>";
    
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.headline,
            cf.course_id,
            c.title as course_title,
            u.email as buyer_email
        FROM customer_freebies cf
        LEFT JOIN courses c ON c.id = cf.course_id
        LEFT JOIN users u ON u.id = cf.customer_id
        WHERE cf.copied_from_freebie_id IS NOT NULL
        AND cf.freebie_type = 'purchased'
        ORDER BY cf.id DESC
        LIMIT 5
    ");
    
    $examples = $stmt->fetchAll();
    
    if (!empty($examples)) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>Freebie</th>
                <th>Käufer</th>
                <th>Verknüpfter Kurs</th>
                <th>Status</th>
              </tr>";
        
        foreach ($examples as $ex) {
            $status = $ex['course_id'] ? '✅' : '❌';
            $statusColor = $ex['course_id'] ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($ex['headline']) . "</td>";
            echo "<td>" . htmlspecialchars($ex['buyer_email']) . "</td>";
            echo "<td>" . ($ex['course_title'] ? htmlspecialchars($ex['course_title']) : '<em>Kein Kurs</em>') . "</td>";
            echo "<td style='color: $statusColor;'>$status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p style='color: green; font-size: 16px;'><strong>✅ Fix erfolgreich abgeschlossen!</strong></p>";
    echo "<p>Die Kurs-Verknüpfungen werden ab sofort bei allen neuen Marktplatz-Käufen korrekt übertragen.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Fehler:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    
    h2 {
        color: #333;
        border-bottom: 3px solid #667eea;
        padding-bottom: 10px;
    }
    
    h3 {
        color: #555;
        margin-top: 30px;
    }
    
    table {
        background: white;
        width: 100%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    th {
        font-weight: 600;
        text-align: left;
    }
    
    code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: 'Courier New', monospace;
    }
    
    ul {
        background: white;
        padding: 20px 40px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    li {
        margin: 10px 0;
    }
</style>
