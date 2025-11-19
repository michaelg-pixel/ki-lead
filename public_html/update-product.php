<?php
// 🔧 PRODUKT-ID UPDATE
require_once 'config/database.php';

$action = $_GET['action'] ?? 'view';

if ($action === 'update') {
    try {
        // Produkt-ID ändern
        $stmt = $pdo->prepare("UPDATE marketplace_freebies SET digistore_product_id = '639493' WHERE digistore_product_id = '613818'");
        $stmt->execute();
        $affected = $stmt->rowCount();
        
        echo "✅ UPDATE ERFOLGREICH!<br>";
        echo "Geänderte Zeilen: $affected<br><br>";
        
        // Verifizieren
        $stmt = $pdo->query("SELECT * FROM marketplace_freebies");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 ALLE MARKTPLATZ-FREEBIES:<br>";
        foreach ($all as $f) {
            echo "ID: {$f['id']} | Template: {$f['template_id']} | Produkt-ID: {$f['digistore_product_id']}<br>";
        }
        
        echo "<br><a href='?action=view'>Zurück</a>";
        
    } catch (PDOException $e) {
        echo "❌ FEHLER: " . $e->getMessage();
    }
} else {
    // Aktuelle Daten anzeigen
    try {
        $stmt = $pdo->query("SELECT * FROM marketplace_freebies");
        $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>📋 MARKTPLATZ-FREEBIES</h2>";
        
        if (empty($all)) {
            echo "❌ KEINE MARKTPLATZ-FREEBIES GEFUNDEN!";
        } else {
            foreach ($all as $f) {
                $color = ($f['digistore_product_id'] == '613818') ? 'red' : 'green';
                echo "<div style='margin: 10px; padding: 10px; border: 2px solid $color;'>";
                echo "ID: {$f['id']}<br>";
                echo "Template ID: {$f['template_id']}<br>";
                echo "<strong>Produkt-ID: {$f['digistore_product_id']}</strong><br>";
                echo "</div>";
            }
            
            echo "<br><br>";
            echo "<a href='?action=update' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>";
            echo "🔧 PRODUKT-ID VON 613818 AUF 639493 ÄNDERN";
            echo "</a>";
        }
        
    } catch (PDOException $e) {
        echo "❌ FEHLER: " . $e->getMessage();
    }
}
?>
