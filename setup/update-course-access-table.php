<?php
/**
 * Datenbank-Update für Kurs-Zugriffstabelle
 * Fügt fehlende Spalten zur course_access Tabelle hinzu
 */

require_once '../config/database.php';

echo "<h1>Datenbank-Update: course_access Tabelle</h1>";
echo "<style>
    body { font-family: system-ui; max-width: 800px; margin: 50px auto; padding: 20px; background: #0a0a16; color: #e5e7eb; }
    h1 { color: #a855f7; }
    .success { color: #4ade80; margin: 10px 0; }
    .error { color: #fb7185; margin: 10px 0; }
    .info { color: #60a5fa; margin: 10px 0; }
    code { background: rgba(168, 85, 247, 0.1); padding: 2px 8px; border-radius: 4px; }
</style>";

try {
    $pdo = getDBConnection();
    
    // Prüfen ob granted_at Spalte existiert
    echo "<h2>1. Prüfe granted_at Spalte...</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM course_access LIKE 'granted_at'");
    $column = $stmt->fetch();
    
    if (!$column) {
        echo "<div class='info'>⚠️ Spalte <code>granted_at</code> fehlt, wird hinzugefügt...</div>";
        
        $pdo->exec("
            ALTER TABLE course_access 
            ADD COLUMN granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER access_source
        ");
        
        echo "<div class='success'>✅ Spalte <code>granted_at</code> erfolgreich hinzugefügt!</div>";
    } else {
        echo "<div class='success'>✅ Spalte <code>granted_at</code> existiert bereits</div>";
    }
    
    // Prüfen ob expires_at Spalte existiert
    echo "<h2>2. Prüfe expires_at Spalte...</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM course_access LIKE 'expires_at'");
    $column = $stmt->fetch();
    
    if (!$column) {
        echo "<div class='info'>⚠️ Spalte <code>expires_at</code> fehlt, wird hinzugefügt...</div>";
        
        $pdo->exec("
            ALTER TABLE course_access 
            ADD COLUMN expires_at TIMESTAMP NULL AFTER granted_at
        ");
        
        echo "<div class='success'>✅ Spalte <code>expires_at</code> erfolgreich hinzugefügt!</div>";
    } else {
        echo "<div class='success'>✅ Spalte <code>expires_at</code> existiert bereits</div>";
    }
    
    // Tabellen-Struktur anzeigen
    echo "<h2>3. Aktuelle Tabellen-Struktur:</h2>";
    $stmt = $pdo->query("DESCRIBE course_access");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
    echo "<thead><tr style='background: rgba(168, 85, 247, 0.2);'>";
    echo "<th style='padding: 10px; text-align: left; border: 1px solid rgba(168, 85, 247, 0.3);'>Spalte</th>";
    echo "<th style='padding: 10px; text-align: left; border: 1px solid rgba(168, 85, 247, 0.3);'>Typ</th>";
    echo "<th style='padding: 10px; text-align: left; border: 1px solid rgba(168, 85, 247, 0.3);'>Null</th>";
    echo "<th style='padding: 10px; text-align: left; border: 1px solid rgba(168, 85, 247, 0.3);'>Default</th>";
    echo "</tr></thead><tbody>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid rgba(168, 85, 247, 0.2);'><code>{$col['Field']}</code></td>";
        echo "<td style='padding: 8px; border: 1px solid rgba(168, 85, 247, 0.2);'>{$col['Type']}</td>";
        echo "<td style='padding: 8px; border: 1px solid rgba(168, 85, 247, 0.2);'>{$col['Null']}</td>";
        echo "<td style='padding: 8px; border: 1px solid rgba(168, 85, 247, 0.2);'>" . ($col['Default'] ?: '-') . "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    
    // Beispiel-Daten anzeigen
    echo "<h2>4. Beispiel-Einträge:</h2>";
    $stmt = $pdo->query("SELECT * FROM course_access LIMIT 5");
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($entries) > 0) {
        echo "<table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>";
        echo "<thead><tr style='background: rgba(168, 85, 247, 0.2);'>";
        foreach (array_keys($entries[0]) as $key) {
            echo "<th style='padding: 10px; text-align: left; border: 1px solid rgba(168, 85, 247, 0.3);'>$key</th>";
        }
        echo "</tr></thead><tbody>";
        
        foreach ($entries as $entry) {
            echo "<tr>";
            foreach ($entry as $value) {
                echo "<td style='padding: 8px; border: 1px solid rgba(168, 85, 247, 0.2);'>" . htmlspecialchars($value ?: '-') . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='info'>ℹ️ Noch keine Einträge in der Tabelle</div>";
    }
    
    echo "<h2>✅ Update erfolgreich abgeschlossen!</h2>";
    echo "<div class='success'>";
    echo "<h3>✓ Alle Spalten sind vorhanden</h3>";
    echo "<p>Die <code>course_access</code> Tabelle ist jetzt bereit für:</p>";
    echo "<ul>";
    echo "<li>✅ Automatische Freischaltung via Digistore24</li>";
    echo "<li>✅ Zeitstempel für Zugang-Gewährung</li>";
    echo "<li>✅ Ablaufdatum für zeitlich begrenzte Kurse</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔗 Nächste Schritte:</h3>";
    echo "<ol>";
    echo "<li>Gehe zu <a href='/admin/dashboard.php?page=templates' style='color: #a855f7;'>Kursverwaltung</a></li>";
    echo "<li>Hinterlege bei jedem kostenpflichtigen Kurs die <strong>Digistore24 Produkt-ID</strong></li>";
    echo "<li>In Digistore24: IPN-URL auf <code>https://app.ki-leadsystem.com/webhook/digistore24.php</code> setzen</li>";
    echo "<li>Testkauf durchführen und prüfen ob automatische Freischaltung funktioniert</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Fehler: " . $e->getMessage() . "</div>";
}
?>