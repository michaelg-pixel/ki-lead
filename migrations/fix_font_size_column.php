<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🔧 Ändere font_size Spalte zu TEXT...\n";
    
    // Spalte zu TEXT ändern
    $pdo->exec("ALTER TABLE customer_freebies MODIFY COLUMN font_size TEXT");
    
    echo "✅ Spalte erfolgreich geändert!\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}
