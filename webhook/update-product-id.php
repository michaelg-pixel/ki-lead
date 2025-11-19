<?php
/**
 * UPDATE Marktplatz-Freebie Produkt-ID
 * Von: 613818 → 639493
 */

require_once '../config/database.php';

echo "=== UPDATE MARKTPLATZ PRODUKT-ID ===\n\n";

try {
    $pdo = getDBConnection();
    
    // 1. Aktuelles Marktplatz-Freebie finden
    $stmt = $pdo->prepare("
        SELECT id, headline, digistore_product_id, marketplace_enabled 
        FROM customer_freebies 
        WHERE digistore_product_id = '613818' AND marketplace_enabled = 1
    ");
    $stmt->execute();
    $freebie = $stmt->fetch();
    
    if ($freebie) {
        echo "✅ Marktplatz-Freebie gefunden:\n";
        echo "   ID: " . $freebie['id'] . "\n";
        echo "   Headline: " . $freebie['headline'] . "\n";
        echo "   Alte Produkt-ID: " . $freebie['digistore_product_id'] . "\n\n";
        
        // 2. Produkt-ID ändern
        $stmt = $pdo->prepare("
            UPDATE customer_freebies 
            SET digistore_product_id = '639493'
            WHERE id = ?
        ");
        $stmt->execute([$freebie['id']]);
        
        echo "✅ Produkt-ID geändert!\n";
        echo "   Neue Produkt-ID: 639493\n\n";
        
        // 3. Verifizieren
        $stmt = $pdo->prepare("
            SELECT id, headline, digistore_product_id 
            FROM customer_freebies 
            WHERE id = ?
        ");
        $stmt->execute([$freebie['id']]);
        $updated = $stmt->fetch();
        
        echo "═══════════════════════════════════════\n";
        echo "🎉 UPDATE ERFOLGREICH!\n";
        echo "═══════════════════════════════════════\n\n";
        
        echo "📊 NEUE KONFIGURATION:\n";
        echo "   Freebie ID: " . $updated['id'] . "\n";
        echo "   Headline: " . $updated['headline'] . "\n";
        echo "   Produkt-ID: " . $updated['digistore_product_id'] . "\n\n";
        
        echo "🎯 AB JETZT:\n";
        echo "   Alle Käufe von Produkt 639493 (Optin Pilot)\n";
        echo "   werden automatisch dieses Freebie kopieren!\n\n";
        
    } else {
        echo "❌ FEHLER: Kein Marktplatz-Freebie mit Produkt-ID 613818 gefunden!\n\n";
        
        // Suche nach anderen Marktplatz-Freebies
        $stmt = $pdo->prepare("
            SELECT id, headline, digistore_product_id, marketplace_enabled 
            FROM customer_freebies 
            WHERE marketplace_enabled = 1
        ");
        $stmt->execute();
        $all = $stmt->fetchAll();
        
        if ($all) {
            echo "Gefundene Marktplatz-Freebies:\n";
            foreach ($all as $f) {
                echo "   ID: " . $f['id'] . " | Produkt-ID: " . $f['digistore_product_id'] . " | " . $f['headline'] . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
