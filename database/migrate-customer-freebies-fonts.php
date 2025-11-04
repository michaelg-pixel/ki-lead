<?php
/**
 * Migration: Font-Felder zu customer_freebies hinzufügen
 * 
 * Diese Migration fügt Schriftart- und Schriftgrößen-Felder 
 * zur customer_freebies Tabelle hinzu und aktualisiert bestehende
 * Daten mit den Font-Einstellungen aus den Templates.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🚀 Starte Font-Felder Migration für customer_freebies...\n\n";
    
    // Migration SQL laden
    $sql = file_get_contents(__DIR__ . '/migrations/2025-11-04_add_fonts_to_customer_freebies.sql');
    
    // Migration ausführen
    $pdo->exec($sql);
    
    echo "✅ Migration erfolgreich ausgeführt!\n\n";
    
    // Statistik anzeigen
    $stmt = $pdo->query("
        SELECT COUNT(*) as total,
               SUM(CASE WHEN headline_font IS NOT NULL THEN 1 ELSE 0 END) as with_fonts
        FROM customer_freebies
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Statistik:\n";
    echo "   - Gesamt Customer Freebies: " . $stats['total'] . "\n";
    echo "   - Mit Font-Einstellungen: " . $stats['with_fonts'] . "\n";
    
    echo "\n✨ Migration abgeschlossen!\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler bei der Migration: " . $e->getMessage() . "\n";
    exit(1);
}
