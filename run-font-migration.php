<?php
/**
 * Migration ausführen: Font-Spalten zu customer_freebies hinzufügen
 * Aufruf: https://app.mehr-infos-jetzt.de/run-font-migration.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>🔧 Migration: Font-Spalten zu customer_freebies</h2>";
    echo "<p>Status: Wird ausgeführt...</p>";
    
    // Prüfen ob Spalten bereits existieren
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_schema = DATABASE()
        AND table_name = 'customer_freebies' 
        AND column_name = 'preheadline_font'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "<p style='color: orange;'>✓ Font-Spalten existieren bereits in customer_freebies</p>";
    } else {
        // Spalten hinzufügen
        $pdo->exec("
            ALTER TABLE customer_freebies 
            ADD COLUMN preheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER bullet_points,
            ADD COLUMN preheadline_size INT DEFAULT 14 AFTER preheadline_font,
            ADD COLUMN headline_font VARCHAR(100) DEFAULT 'Poppins' AFTER preheadline_size,
            ADD COLUMN headline_size INT DEFAULT 48 AFTER headline_font,
            ADD COLUMN subheadline_font VARCHAR(100) DEFAULT 'Poppins' AFTER headline_size,
            ADD COLUMN subheadline_size INT DEFAULT 20 AFTER subheadline_font,
            ADD COLUMN bulletpoints_font VARCHAR(100) DEFAULT 'Poppins' AFTER subheadline_size,
            ADD COLUMN bulletpoints_size INT DEFAULT 16 AFTER bulletpoints_font
        ");
        
        echo "<p style='color: green;'>✓ Font-Spalten erfolgreich zu customer_freebies hinzugefügt</p>";
    }
    
    // Struktur anzeigen
    echo "<h3>Aktuelle Spaltenstruktur:</h3>";
    echo "<pre>";
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE '%font%' OR SHOW COLUMNS FROM customer_freebies LIKE '%size%'");
    
    // Alle Font-bezogenen Spalten anzeigen
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fontColumns = array_filter($columns, function($col) {
        return strpos($col['Field'], 'font') !== false || 
               strpos($col['Field'], 'size') !== false;
    });
    
    foreach ($fontColumns as $col) {
        echo $col['Field'] . " | " . $col['Type'] . " | Default: " . $col['Default'] . "\n";
    }
    echo "</pre>";
    
    echo "<h3 style='color: green;'>✅ Migration erfolgreich abgeschlossen!</h3>";
    echo "<p><a href='/admin/dashboard.php'>← Zurück zum Admin-Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Fehler:</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
