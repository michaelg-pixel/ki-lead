<?php
/**
 * Migration: E-Mail Optin Popup Feature
 * Fügt Felder für Popup-Anzeige, Animation und Custom Message hinzu
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🚀 Migration: E-Mail Optin Popup Feature wird installiert...\n\n";
    
    // 1. Felder zur customer_freebies Tabelle hinzufügen
    echo "📋 Füge Felder zur customer_freebies Tabelle hinzu...\n";
    
    $alterQueries = [
        "ALTER TABLE customer_freebies 
         ADD COLUMN IF NOT EXISTS optin_display_mode ENUM('direct', 'popup') DEFAULT 'direct' 
         COMMENT 'Anzeige-Modus für E-Mail Optin'",
        
        "ALTER TABLE customer_freebies 
         ADD COLUMN IF NOT EXISTS popup_message TEXT NULL 
         COMMENT 'Benutzerdefinierte Nachricht im Popup'",
        
        "ALTER TABLE customer_freebies 
         ADD COLUMN IF NOT EXISTS cta_animation VARCHAR(50) DEFAULT 'none' 
         COMMENT 'Animation für CTA-Button (none, pulse, shake, bounce, glow)'"
    ];
    
    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✅ Feld hinzugefügt\n";
        } catch (PDOException $e) {
            // Ignoriere Fehler wenn Spalte bereits existiert
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
            echo "  ℹ️  Feld existiert bereits\n";
        }
    }
    
    // 2. Auch zur freebies Tabelle für Templates hinzufügen
    echo "\n📋 Füge Felder zur freebies Tabelle hinzu...\n";
    
    $alterTemplateQueries = [
        "ALTER TABLE freebies 
         ADD COLUMN IF NOT EXISTS optin_display_mode ENUM('direct', 'popup') DEFAULT 'direct'",
        
        "ALTER TABLE freebies 
         ADD COLUMN IF NOT EXISTS popup_message TEXT NULL",
        
        "ALTER TABLE freebies 
         ADD COLUMN IF NOT EXISTS cta_animation VARCHAR(50) DEFAULT 'none'"
    ];
    
    foreach ($alterTemplateQueries as $query) {
        try {
            $pdo->exec($query);
            echo "  ✅ Feld hinzugefügt\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                throw $e;
            }
            echo "  ℹ️  Feld existiert bereits\n";
        }
    }
    
    // 3. Default-Werte setzen für bestehende Einträge
    echo "\n🔧 Setze Default-Werte für bestehende Einträge...\n";
    
    $pdo->exec("
        UPDATE customer_freebies 
        SET optin_display_mode = 'direct',
            popup_message = 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
            cta_animation = 'none'
        WHERE optin_display_mode IS NULL
    ");
    echo "  ✅ customer_freebies aktualisiert\n";
    
    $pdo->exec("
        UPDATE freebies 
        SET optin_display_mode = 'direct',
            popup_message = 'Trage dich jetzt unverbindlich ein und erhalte sofortigen Zugang!',
            cta_animation = 'none'
        WHERE optin_display_mode IS NULL
    ");
    echo "  ✅ freebies aktualisiert\n";
    
    echo "\n✨ Migration erfolgreich abgeschlossen!\n";
    echo "\n📝 Neue Funktionen:\n";
    echo "  • E-Mail Optin kann als Popup angezeigt werden\n";
    echo "  • CTA-Button kann animiert werden\n";
    echo "  • Custom Popup-Nachricht einstellbar\n";
    echo "  • Komplett responsive\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler bei der Migration: " . $e->getMessage() . "\n";
    exit(1);
}
