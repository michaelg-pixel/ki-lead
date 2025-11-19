<?php
// 🔧 Produkt-ID von 613818 auf 639493 ändern
// EINMALIG AUSFÜHREN UND DANN LÖSCHEN!

require_once '../config/database.php';

try {
    // Altes Marktplatz-Freebie mit 613818 finden
    $stmt = $pdo->query("SELECT * FROM marketplace_freebies WHERE digistore_product_id = '613818'");
    $old_freebie = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($old_freebie) {
        echo "✅ ALTES MARKTPLATZ-FREEBIE GEFUNDEN:<br>";
        echo "ID: {$old_freebie['id']}<br>";
        echo "Template ID: {$old_freebie['template_id']}<br>";
        echo "Alte Produkt-ID: {$old_freebie['digistore_product_id']}<br><br>";
        
        // Produkt-ID ändern
        $stmt = $pdo->prepare("UPDATE marketplace_freebies SET digistore_product_id = '639493' WHERE id = ?");
        $stmt->execute([$old_freebie['id']]);
        
        echo "✅ PRODUKT-ID GEÄNDERT!<br>";
        echo "Neue Produkt-ID: 639493<br><br>";
        
        // Verifizieren
        $stmt = $pdo->query("SELECT * FROM marketplace_freebies WHERE id = {$old_freebie['id']}");
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ VERIFIZIERT:<br>";
        echo "ID: {$updated['id']}<br>";
        echo "Template ID: {$updated['template_id']}<br>";
        echo "Produkt-ID: {$updated['digistore_product_id']}<br><br>";
        
        echo "<hr>";
        echo "🎉 <strong>FERTIG! Produkt-ID wurde erfolgreich geändert!</strong><br>";
        echo "⚠️ <strong>WICHTIG: Lösche diese Datei jetzt!</strong>";
        
    } else {
        echo "❌ KEIN MARKTPLATZ-FREEBIE MIT PRODUKT-ID 613818 GEFUNDEN!<br>";
        echo "Suche nach allen Marktplatz-Freebies...<br><br>";
        
        $stmt = $pdo->query("SELECT * FROM marketplace_freebies");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($all)) {
            echo "❌ KEINE MARKTPLATZ-FREEBIES GEFUNDEN!";
        } else {
            echo "📋 ALLE MARKTPLATZ-FREEBIES:<br>";
            foreach ($all as $f) {
                echo "ID: {$f['id']} | Template: {$f['template_id']} | Produkt-ID: {$f['digistore_product_id']}<br>";
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ FEHLER: " . $e->getMessage();
}
?>
