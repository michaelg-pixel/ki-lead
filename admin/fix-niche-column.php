<?php
/**
 * Fix: Setze Default-Wert für niche Spalte
 */

require_once '../config/database.php';

try {
    echo "<h2>🔧 Behebe niche Spalten-Problem...</h2>";
    
    // Prüfe ob niche Spalte existiert
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'niche'");
    $has_niche = $stmt->rowCount() > 0;
    
    if ($has_niche) {
        echo "<p>✅ niche Spalte gefunden - setze Default-Wert...</p>";
        
        // Setze Default-Wert für niche
        $pdo->exec("ALTER TABLE courses MODIFY COLUMN niche VARCHAR(100) DEFAULT 'other'");
        
        echo "<p style='color: green;'>✅ Default-Wert 'other' für niche gesetzt</p>";
        
        // Update bestehende NULL-Werte
        $pdo->exec("UPDATE courses SET niche = 'other' WHERE niche IS NULL OR niche = ''");
        echo "<p style='color: green;'>✅ Bestehende NULL-Werte aktualisiert</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ️ niche Spalte existiert nicht - wird übersprungen</p>";
    }
    
    // Zeige aktuelle Struktur der niche Spalte
    echo "<br><h3>📋 Aktuelle niche Spalten-Info:</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM courses LIKE 'niche'");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<pre style='background: #1a1532; padding: 15px; border-radius: 8px;'>";
        echo "Feld: " . htmlspecialchars($row['Field']) . "\n";
        echo "Typ: " . htmlspecialchars($row['Type']) . "\n";
        echo "Null: " . htmlspecialchars($row['Null']) . "\n";
        echo "Default: " . htmlspecialchars($row['Default'] ?? 'NULL') . "\n";
        echo "</pre>";
    }
    
    echo "<br><h3>✅ Fertig! Jetzt kannst du Kurse erstellen.</h3>";
    echo "<p><a href='dashboard.php?page=templates' style='background: #a855f7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; display: inline-block;'>→ Zur Kursverwaltung</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Fehler:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 800px;
        margin: 50px auto;
        padding: 20px;
        background: #0a0a16;
        color: #e5e7eb;
    }
    h2, h3 {
        color: #a855f7;
    }
    pre {
        background: #1a1532;
        padding: 15px;
        border-radius: 8px;
        overflow-x: auto;
    }
</style>