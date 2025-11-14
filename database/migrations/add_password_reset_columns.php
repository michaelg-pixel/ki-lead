<?php
/**
 * Datenbank-Migration: Passwort-Reset Spalten hinzufügen
 * 
 * Fügt folgende Spalten zur users Tabelle hinzu:
 * - password_reset_token: Token für Reset-Link
 * - password_reset_expires: Ablaufzeit des Tokens
 */

require_once __DIR__ . '/../config/database.php';

echo "🔄 Starte Passwort-Reset Migration...\n\n";

try {
    // Migration SQL
    $sql = "
        -- Passwort-Reset Token Spalte
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(64) NULL DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS password_reset_expires DATETIME NULL DEFAULT NULL;
        
        -- Index für schnelle Token-Suche
        ALTER TABLE users 
        ADD INDEX IF NOT EXISTS idx_password_reset_token (password_reset_token);
    ";
    
    // Migration ausführen
    $pdo->exec($sql);
    
    echo "✅ Migration erfolgreich!\n\n";
    echo "Hinzugefügte Spalten:\n";
    echo "  ✓ password_reset_token (VARCHAR 64)\n";
    echo "  ✓ password_reset_expires (DATETIME)\n";
    echo "  ✓ Index auf password_reset_token\n\n";
    
    // Spalten verifizieren
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasResetToken = false;
    $hasResetExpires = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'password_reset_token') $hasResetToken = true;
        if ($col['Field'] === 'password_reset_expires') $hasResetExpires = true;
    }
    
    if ($hasResetToken && $hasResetExpires) {
        echo "✅ Verifizierung: Alle Spalten korrekt angelegt!\n";
    } else {
        echo "⚠️  Warnung: Nicht alle Spalten gefunden!\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Fehler bei Migration: " . $e->getMessage() . "\n";
    exit(1);
}
