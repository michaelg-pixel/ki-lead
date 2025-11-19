<?php
/**
 * Diagnose: Prüft ob Digistore24-Kauf von test@web.de verarbeitet wurde
 */

require_once '../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Kauf-Diagnose</title>";
echo "<style>
body{font-family:Arial;padding:20px;background:#0f0f1e;color:#fff;font-size:14px;}
.box{background:#1a1a2e;padding:20px;margin:15px 0;border-radius:8px;border:2px solid #667eea;}
.success{border-color:#10b981;background:#10b98120;}
.error{border-color:#ef4444;background:#ef444420;}
.warning{border-color:#f59e0b;background:#f59e0b20;}
h1{color:#667eea;}
h3{color:#667eea;margin-top:0;}
pre{background:#000;padding:15px;border-radius:5px;overflow-x:auto;font-size:12px;max-height:400px;overflow-y:auto;}
table{width:100%;border-collapse:collapse;margin:10px 0;}
th,td{padding:8px;text-align:left;border-bottom:1px solid #333;}
th{color:#667eea;}
</style></head><body>";

echo "<h1>🔍 Diagnose: Digistore24-Kauf test@web.de</h1>";

try {
    $pdo = getDBConnection();
    $email = 'test@web.de';
    $productId = '613818';
    
    // 1. User prüfen
    echo "<div class='box'>";
    echo "<h3>1️⃣ User-Status</h3>";
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='success'>";
        echo "✅ User existiert!<br>";
        echo "<table>";
        echo "<tr><th>ID</th><td>{$user['id']}</td></tr>";
        echo "<tr><th>Name</th><td>{$user['name']}</td></tr>";
        echo "<tr><th>Email</th><td>{$user['email']}</td></tr>";
        echo "<tr><th>Role</th><td>{$user['role']}</td></tr>";
        echo "<tr><th>Active</th><td>" . ($user['is_active'] ? '✅ Ja' : '❌ Nein') . "</td></tr>";
        echo "<tr><th>Source</th><td>{$user['source']}</td></tr>";
        echo "<tr><th>Erstellt</th><td>{$user['created_at']}</td></tr>";
        echo "</table>";
        echo "</div>";
        
        $userId = $user['id'];
    } else {
        echo "<div class='error'>❌ User existiert NICHT! Webhook wurde nicht getriggert oder hat fehlgeschlagen.</div>";
        $userId = null;
    }
    echo "</div>";
    
    // 2. Marktplatz-Freebie prüfen
    echo "<div class='box'>";
    echo "<h3>2️⃣ Marktplatz-Freebie (Produkt-ID: $productId)</h3>";
    
    $stmt = $pdo->prepare("
        SELECT * FROM customer_freebies 
        WHERE digistore_product_id = ? 
        AND marketplace_enabled = 1
    ");
    $stmt->execute([$productId]);
    $sourceFreebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sourceFreebie) {
        echo "<div class='success'>";
        echo "✅ Marktplatz-Freebie gefunden!<br>";
        echo "<table>";
        echo "<tr><th>Freebie ID</th><td>{$sourceFreebie['id']}</td></tr>";
        echo "<tr><th>Verkäufer ID</th><td>{$sourceFreebie['customer_id']}</td></tr>";
        echo "<tr><th>Headline</th><td>{$sourceFreebie['headline']}</td></tr>";
        echo "<tr><th>Preis</th><td>{$sourceFreebie['marketplace_price']} €</td></tr>";
        echo "<tr><th>Verkäufe</th><td>{$sourceFreebie['marketplace_sales_count']}</td></tr>";
        echo "<tr><th>Hat Videokurs?</th><td>" . ($sourceFreebie['course_id'] ? "✅ Ja (ID: {$sourceFreebie['course_id']})" : "❌ Nein") . "</td></tr>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ Kein Marktplatz-Freebie mit dieser Produkt-ID gefunden!</div>";
    }
    echo "</div>";
    
    // 3. Kopiertes Freebie prüfen (falls User existiert)
    if ($userId) {
        echo "<div class='box'>";
        echo "<h3>3️⃣ Kopierte Freebies für User</h3>";
        
        $stmt = $pdo->prepare("
            SELECT * FROM customer_freebies 
            WHERE customer_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $userFreebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($userFreebies) {
            echo "<div class='success'>Gefunden: " . count($userFreebies) . " Freebies</div>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Headline</th><th>Kopiert von</th><th>Hat Videokurs?</th><th>Erstellt</th></tr>";
            foreach ($userFreebies as $f) {
                $hasVideo = "❓";
                if ($f['copied_from_freebie_id']) {
                    // Prüfe ob Videokurs kopiert wurde
                    $stmt2 = $pdo->prepare("SELECT id FROM freebie_courses WHERE freebie_id = ?");
                    $stmt2->execute([$f['id']]);
                    $hasVideo = $stmt2->fetch() ? "✅ Ja" : "❌ Nein";
                }
                
                echo "<tr>";
                echo "<td>{$f['id']}</td>";
                echo "<td>{$f['headline']}</td>";
                echo "<td>" . ($f['copied_from_freebie_id'] ?? 'Eigenes') . "</td>";
                echo "<td>$hasVideo</td>";
                echo "<td>{$f['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<div class='error'>❌ Keine Freebies für diesen User gefunden!</div>";
        }
        echo "</div>";
    }
    
    // 4. Webhook-Logs prüfen
    echo "<div class='box'>";
    echo "<h3>4️⃣ Webhook-Logs (Letzte 50 Zeilen)</h3>";
    
    $logFile = __DIR__ . '/webhook-logs.txt';
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        $lastLines = array_slice($lines, -50);
        
        // Suche nach test@web.de oder 613818
        $relevantLines = array_filter($lastLines, function($line) use ($email, $productId) {
            return stripos($line, $email) !== false || 
                   stripos($line, $productId) !== false ||
                   stripos($line, 'marketplace') !== false;
        });
        
        if ($relevantLines) {
            echo "<div class='warning'>⚠️ Relevante Log-Einträge gefunden:</div>";
            echo "<pre>" . implode("\n", $relevantLines) . "</pre>";
        } else {
            echo "<div class='error'>❌ Keine Einträge für $email oder Produkt-ID $productId in den Logs!</div>";
        }
        
        echo "<h4>Komplette Logs (letzte 50 Zeilen):</h4>";
        echo "<pre>" . implode("\n", $lastLines) . "</pre>";
    } else {
        echo "<div class='error'>❌ Webhook-Log-Datei nicht gefunden!</div>";
    }
    echo "</div>";
    
    // 5. Webhook-URL prüfen
    echo "<div class='box'>";
    echo "<h3>5️⃣ Webhook-Konfiguration</h3>";
    echo "<p><strong>Webhook-URL:</strong> https://app.mehr-infos-jetzt.de/webhook/digistore24.php</p>";
    echo "<p>Diese URL muss in Digistore24 unter 'Produkt-Einstellungen → IPN/Webhook' eingetragen sein.</p>";
    echo "</div>";
    
    // 6. Manueller Test-Kauf simulieren
    if (!$user && $sourceFreebie) {
        echo "<div class='box warning'>";
        echo "<h3>6️⃣ Manueller Fix</h3>";
        echo "<p>Der Webhook hat den Kauf nicht verarbeitet. Du kannst den Kauf manuell nachstellen:</p>";
        echo "<p><a href='simulate-purchase.php?email=$email&product_id=$productId' style='color:#667eea'>→ Kauf manuell simulieren</a></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
