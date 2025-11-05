<?php
/**
 * Migration: Video-Spalten zu customer_freebies und freebies hinzufügen
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🎥 Migration: Video-Support für Freebies\n";
echo str_repeat("=", 60) . "\n\n";

// Datenbank-Verbindung
require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Datenbankverbindung hergestellt\n\n";
    
    // 1. Video-Spalten zu customer_freebies hinzufügen
    echo "📝 Füge Video-Spalten zu 'customer_freebies' hinzu...\n";
    
    $sql = "
        ALTER TABLE customer_freebies 
        ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER mockup_image_url,
        ADD COLUMN video_format ENUM('widescreen', 'portrait') DEFAULT 'widescreen' AFTER video_url
    ";
    
    try {
        $pdo->exec($sql);
        echo "   ✓ video_url hinzugefügt\n";
        echo "   ✓ video_format hinzugefügt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ Spalten existieren bereits\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n";
    
    // 2. Video-Spalten zu freebies (Templates) hinzufügen
    echo "📝 Füge Video-Spalten zu 'freebies' (Templates) hinzu...\n";
    
    $sql = "
        ALTER TABLE freebies 
        ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER mockup_image_url,
        ADD COLUMN video_format ENUM('widescreen', 'portrait') DEFAULT 'widescreen' AFTER video_url
    ";
    
    try {
        $pdo->exec($sql);
        echo "   ✓ video_url hinzugefügt\n";
        echo "   ✓ video_format hinzugefügt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "   ℹ Spalten existieren bereits\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n";
    
    // 3. Prüfe die Struktur
    echo "🔍 Prüfe Tabellenstruktur...\n\n";
    
    $stmt = $pdo->query("DESCRIBE customer_freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('video_url', $columns) && in_array('video_format', $columns)) {
        echo "✅ customer_freebies: Video-Spalten vorhanden\n";
    } else {
        echo "❌ customer_freebies: Video-Spalten fehlen\n";
    }
    
    $stmt = $pdo->query("DESCRIBE freebies");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('video_url', $columns) && in_array('video_format', $columns)) {
        echo "✅ freebies: Video-Spalten vorhanden\n";
    } else {
        echo "❌ freebies: Video-Spalten fehlen\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ Migration erfolgreich abgeschlossen!\n\n";
    echo "🎬 Video-Support ist jetzt aktiv!\n";
    echo "   • YouTube-Videos werden unterstützt\n";
    echo "   • Vimeo-Videos werden unterstützt\n";
    echo "   • Hochformat (9:16) und Querformat (16:9)\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}
