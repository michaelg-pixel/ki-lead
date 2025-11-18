<?php
/**
 * Check: Welche Freebies haben Digistore-Produkt-IDs?
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Marketplace Produkt-IDs Check</title>
    <style>
        body {
            font-family: monospace;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: left;
        }
        th {
            background: rgba(102, 126, 234, 0.2);
        }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body>
    <h1>🔍 Marketplace Produkt-IDs Check</h1>

<?php
try {
    $pdo = getDBConnection();
    
    echo '<div class="section">';
    echo '<h2>1️⃣ Alle Marketplace-Freebies mit Digistore-Produkt-IDs</h2>';
    
    $stmt = $pdo->query("
        SELECT 
            cf.id,
            cf.customer_id,
            cf.headline,
            cf.marketplace_enabled,
            cf.marketplace_price,
            cf.digistore_product_id,
            u.name as seller_name,
            u.email as seller_email
        FROM customer_freebies cf
        LEFT JOIN users u ON cf.customer_id = u.id
        WHERE cf.marketplace_enabled = 1
        ORDER BY cf.created_at DESC
    ");
    
    $freebies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($freebies) > 0) {
        echo '<p class="success">✅ ' . count($freebies) . ' Marketplace-Freebies gefunden</p>';
        echo '<table>';
        echo '<tr><th>ID</th><th>Headline</th><th>Preis</th><th>Digistore Produkt-ID</th><th>Verkäufer</th></tr>';
        
        foreach ($freebies as $f) {
            $productIdStatus = empty($f['digistore_product_id']) ? '<span class="error">❌ KEINE</span>' : '<span class="success">✓ ' . htmlspecialchars($f['digistore_product_id']) . '</span>';
            
            echo '<tr>';
            echo '<td>' . $f['id'] . '</td>';
            echo '<td>' . htmlspecialchars($f['headline']) . '</td>';
            echo '<td>' . ($f['marketplace_price'] ?? '0.00') . ' €</td>';
            echo '<td>' . $productIdStatus . '</td>';
            echo '<td>' . htmlspecialchars($f['seller_name'] ?? 'N/A') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        // Spezifisch Freebie 613818 prüfen
        echo '<h3>Spezial-Check: Freebie 613818</h3>';
        $stmt = $pdo->prepare("
            SELECT 
                id,
                headline,
                marketplace_enabled,
                marketplace_price,
                digistore_product_id,
                customer_id
            FROM customer_freebies 
            WHERE id = 613818
        ");
        $stmt->execute();
        $freebie613818 = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($freebie613818) {
            echo '<table>';
            echo '<tr><th>Feld</th><th>Wert</th></tr>';
            foreach ($freebie613818 as $key => $value) {
                $displayValue = $value ?? '(NULL)';
                if ($key === 'digistore_product_id' && empty($value)) {
                    $displayValue = '<span class="error">⚠️ NICHT GESETZT!</span>';
                }
                echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . $displayValue . '</td></tr>';
            }
            echo '</table>';
            
            if (empty($freebie613818['digistore_product_id'])) {
                echo '<div style="background: rgba(239, 68, 68, 0.2); padding: 15px; margin: 10px 0; border-left: 4px solid #ef4444;">';
                echo '<h3 class="error">🔴 PROBLEM GEFUNDEN!</h3>';
                echo '<p>Dieses Freebie hat KEINE Digistore-Produkt-ID!</p>';
                echo '<p><strong>Lösung:</strong> Der Verkäufer muss erst eine Digistore24-Produkt-ID eintragen, bevor Käufe funktionieren.</p>';
                echo '</div>';
            } else {
                echo '<p class="success">✅ Digistore-Produkt-ID ist gesetzt: ' . htmlspecialchars($freebie613818['digistore_product_id']) . '</p>';
            }
        } else {
            echo '<p class="error">❌ Freebie 613818 nicht gefunden!</p>';
        }
        
    } else {
        echo '<p class="error">❌ Keine Marketplace-Freebies gefunden</p>';
    }
    
    echo '</div>';
    
    // Webhook-Konfiguration prüfen
    echo '<div class="section">';
    echo '<h2>2️⃣ Webhook-Konfiguration</h2>';
    echo '<p><strong>Aktuelle Webhook-URL sollte sein:</strong></p>';
    echo '<pre style="background: #000; padding: 10px; border-radius: 4px;">https://app.mehr-infos-jetzt.de/webhook/digistore24-v4.php</pre>';
    
    echo '<h3>Test: Ist die Webhook-Datei erreichbar?</h3>';
    $webhookFile = __DIR__ . '/digistore24-v4.php';
    if (file_exists($webhookFile)) {
        echo '<p class="success">✅ Webhook-Datei existiert</p>';
        echo '<p>Dateigröße: ' . filesize($webhookFile) . ' bytes</p>';
        echo '<p>Letztes Update: ' . date('Y-m-d H:i:s', filemtime($webhookFile)) . '</p>';
    } else {
        echo '<p class="error">❌ Webhook-Datei nicht gefunden!</p>';
    }
    
    echo '</div>';
    
    // Zusammenfassung
    echo '<div class="section">';
    echo '<h2>📊 Diagnose-Ergebnis</h2>';
    
    $issues = [];
    $hasProductIds = false;
    
    foreach ($freebies as $f) {
        if (!empty($f['digistore_product_id'])) {
            $hasProductIds = true;
            break;
        }
    }
    
    if (!$hasProductIds) {
        echo '<div style="background: rgba(239, 68, 68, 0.2); padding: 20px; border-radius: 8px; border-left: 4px solid #ef4444;">';
        echo '<h3 class="error">🔴 HAUPTPROBLEM</h3>';
        echo '<p><strong>KEIN Marketplace-Freebie hat eine Digistore-Produkt-ID!</strong></p>';
        echo '<p>Deshalb können keine Käufe über Digistore24 verarbeitet werden.</p>';
        
        echo '<h4>Lösung:</h4>';
        echo '<ol>';
        echo '<li>Verkäufer muss in seinem Freebie-Editor die Digistore24-Produkt-ID eintragen</li>';
        echo '<li>Oder: Verkäufer muss erst ein Digistore24-Produkt erstellen</li>';
        echo '<li>Dann: Diese Produkt-ID in den Marketplace-Einstellungen des Freebies hinterlegen</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<p class="success">✅ Es gibt Freebies mit Digistore-Produkt-IDs</p>';
        echo '<p>Wenn ein Kauf nicht funktioniert hat, prüfe:</p>';
        echo '<ul>';
        echo '<li>Wurde die richtige Produkt-ID gekauft?</li>';
        echo '<li>Ist die Webhook-URL in Digistore24 richtig eingetragen?</li>';
        echo '<li>Gibt es Fehler in den Webhook-Logs?</li>';
        echo '</ul>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<p class="error">ERROR: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

<div class="section">
    <h2>🔧 Nächste Schritte</h2>
    <ol>
        <li>Prüfe ob Freebie 613818 eine Digistore-Produkt-ID hat</li>
        <li>Falls nein: Verkäufer muss diese erst einrichten</li>
        <li>Falls ja: Prüfe die Webhook-URL in Digistore24</li>
        <li><a href="check-purchase-location.php" style="color: #667eea;">Zurück zur Kauf-Übersicht</a></li>
    </ol>
</div>

</body>
</html>