<?php
/**
 * FIX: Reward Definitions Foreign Key
 * 
 * Problem: freebie_id verweist auf freebies(id) statt customer_freebies(id)
 * Fehler: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'cf.freebie_id'
 * Lösung: Foreign Key neu erstellen mit korrekter Referenz
 * 
 * Aufruf: php fix_reward_definitions_fk.php
 */

require_once __DIR__ . '/config/database.php';

echo "===========================================\n";
echo "FIX: Reward Definitions Foreign Key\n";
echo "===========================================\n\n";

try {
    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "[1/6] Prüfe aktuellen Foreign Key...\n";
    
    // Schritt 1: Aktuellen FK prüfen
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'reward_definitions'
            AND COLUMN_NAME = 'freebie_id'
            AND CONSTRAINT_NAME LIKE 'fk_%'
    ");
    
    $existing_fk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_fk) {
        echo "   ✓ Gefunden: {$existing_fk['CONSTRAINT_NAME']}\n";
        echo "     Verweist auf: {$existing_fk['REFERENCED_TABLE_NAME']}({$existing_fk['REFERENCED_COLUMN_NAME']})\n";
        
        if ($existing_fk['REFERENCED_TABLE_NAME'] === 'freebies') {
            echo "   ⚠ PROBLEM: Foreign Key verweist auf falsche Tabelle!\n\n";
            
            echo "[2/6] Entferne fehlerhaften Foreign Key...\n";
            $pdo->exec("ALTER TABLE reward_definitions DROP FOREIGN KEY {$existing_fk['CONSTRAINT_NAME']}");
            echo "   ✓ Foreign Key entfernt\n\n";
        } else if ($existing_fk['REFERENCED_TABLE_NAME'] === 'customer_freebies') {
            echo "   ✓ Foreign Key ist bereits korrekt!\n";
            echo "   Nichts zu tun.\n\n";
            exit(0);
        }
    } else {
        echo "   ℹ Kein Foreign Key gefunden\n\n";
    }
    
    echo "[3/6] Erstelle korrekten Foreign Key...\n";
    $pdo->exec("
        ALTER TABLE reward_definitions 
        ADD CONSTRAINT fk_reward_def_customer_freebie
        FOREIGN KEY (freebie_id) 
        REFERENCES customer_freebies(id) 
        ON DELETE SET NULL
        ON UPDATE CASCADE
    ");
    echo "   ✓ Foreign Key erstellt\n";
    echo "     Referenz: customer_freebies(id)\n\n";
    
    echo "[4/6] Verifiziere neuen Foreign Key...\n";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'reward_definitions'
            AND COLUMN_NAME = 'freebie_id'
            AND CONSTRAINT_NAME = 'fk_reward_def_customer_freebie'
    ");
    
    $new_fk = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($new_fk && $new_fk['REFERENCED_TABLE_NAME'] === 'customer_freebies') {
        echo "   ✓ Foreign Key korrekt erstellt!\n";
        echo "     {$new_fk['CONSTRAINT_NAME']} -> {$new_fk['REFERENCED_TABLE_NAME']}({$new_fk['REFERENCED_COLUMN_NAME']})\n\n";
    } else {
        throw new Exception("Foreign Key konnte nicht verifiziert werden!");
    }
    
    echo "[5/6] Prüfe Daten-Integrität...\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            COUNT(freebie_id) as with_freebie,
            COUNT(*) - COUNT(freebie_id) as without_freebie
        FROM reward_definitions
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Gesamt Belohnungen: {$stats['total']}\n";
    echo "   Mit Freebie: {$stats['with_freebie']}\n";
    echo "   Ohne Freebie (allgemein): {$stats['without_freebie']}\n\n";
    
    echo "[6/6] Prüfe auf ungültige Referenzen...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as invalid_count
        FROM reward_definitions rd
        LEFT JOIN customer_freebies cf ON rd.freebie_id = cf.id
        WHERE rd.freebie_id IS NOT NULL 
            AND cf.id IS NULL
    ");
    $invalid = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invalid['invalid_count'] > 0) {
        echo "   ⚠ WARNUNG: {$invalid['invalid_count']} ungültige Referenzen gefunden!\n";
        echo "   Diese werden automatisch auf NULL gesetzt bei nächstem Zugriff.\n\n";
    } else {
        echo "   ✓ Alle Referenzen sind gültig\n\n";
    }
    
    echo "===========================================\n";
    echo "✅ FIX ERFOLGREICH ABGESCHLOSSEN\n";
    echo "===========================================\n\n";
    echo "Nächste Schritte:\n";
    echo "1. Teste das Speichern einer Belohnungsstufe\n";
    echo "2. Prüfe die Belohnungsstufen-Seite\n";
    echo "3. Bei Bedarf Cache leeren\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n\n";
    
    if ($e->getCode() == '42000' && strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "ℹ Der Foreign Key existiert bereits. Das ist in Ordnung.\n";
        echo "Teste das Speichern einer Belohnungsstufe.\n\n";
    }
    
    exit(1);
} catch (Exception $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n\n";
    exit(1);
}
