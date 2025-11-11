<?php
/**
 * Migration: Font-Größen Spalten zu customer_freebies hinzufügen
 */

// Absoluter Pfad zur database.php
require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDBConnection();
    
    echo "🔧 Füge Font-Größen Spalten hinzu...\n\n";
    
    // Check if columns exist
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'heading_font_size'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN heading_font_size INT DEFAULT 32 AFTER font_size");
        echo "✅ heading_font_size hinzugefügt\n";
    } else {
        echo "⏭️  heading_font_size existiert bereits\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM customer_freebies LIKE 'body_font_size'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE customer_freebies ADD COLUMN body_font_size INT DEFAULT 16 AFTER heading_font_size");
        echo "✅ body_font_size hinzugefügt\n";
    } else {
        echo "⏭️  body_font_size existiert bereits\n";
    }
    
    echo "\n✨ Migration erfolgreich abgeschlossen!\n";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "❌ Fehler: " . $e->getMessage() . "\n";
}

exit();
