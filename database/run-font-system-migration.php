<?php
/**
 * Font-System Migration Runner
 * Führt die Migration für das Font-System aus
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🎨 Starte Font-System Migration...\n\n";
    
    // Migration SQL laden
    $sql = file_get_contents(__DIR__ . '/migrations/2025-11-05_add_font_system_to_customer_freebies.sql');
    
    // SQL-Statements trennen und ausführen
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            echo "✅ Statement erfolgreich ausgeführt\n";
        } catch (PDOException $e) {
            // Wenn Spalte bereits existiert, ist das OK
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "ℹ️  Spalte existiert bereits - überspringe\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Überprüfen ob die Felder existieren
    echo "\n📊 Überprüfe Datenbank-Schema...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'font_%'");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($columns) >= 3) {
        echo "✅ Alle Font-Felder erfolgreich angelegt:\n";
        foreach ($columns as $col) {
            echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    } else {
        echo "⚠️  Warnung: Nicht alle Font-Felder gefunden!\n";
    }
    
    echo "\n🎉 Migration erfolgreich abgeschlossen!\n\n";
    echo "📝 Du kannst jetzt:\n";
    echo "   1. Im Custom Freebie Editor die Schriftarten anpassen\n";
    echo "   2. Zwischen 10 Webfonts und 10 Google Fonts wählen\n";
    echo "   3. Die Schriftgröße (Klein, Mittel, Groß) einstellen\n";
    echo "   4. Änderungen werden live im Editor und in der öffentlichen Ansicht angezeigt\n\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler bei der Migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>