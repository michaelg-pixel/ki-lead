<?php
/**
 * Migration: reward_template_imports Tabelle erstellen
 * Datum: 2025-11-20
 * Zweck: Tracking von importierten Vendor-Belohnungen
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== Migration: reward_template_imports Tabelle ===\n\n";
    
    // 1. Prüfe ob Tabelle bereits existiert
    echo "1. Prüfe ob Tabelle existiert...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_template_imports'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "   ✓ Tabelle 'reward_template_imports' existiert bereits\n";
        
        // Zeige Struktur
        echo "\n2. Aktuelle Struktur:\n";
        $stmt = $pdo->query("DESCRIBE reward_template_imports");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "   - {$col['Field']} ({$col['Type']})\n";
        }
        
        echo "\n✓ Migration nicht erforderlich - Tabelle existiert bereits\n";
        exit(0);
    }
    
    echo "   → Tabelle existiert nicht, wird erstellt...\n\n";
    
    // 2. Erstelle Tabelle
    echo "2. Erstelle Tabelle 'reward_template_imports'...\n";
    
    $sql = "
    CREATE TABLE reward_template_imports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        template_id INT NOT NULL COMMENT 'ID aus vendor_reward_templates',
        customer_id INT NOT NULL COMMENT 'User der importiert hat',
        reward_definition_id INT NULL COMMENT 'Erstellte reward_definition',
        import_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        import_source VARCHAR(50) DEFAULT 'marketplace' COMMENT 'Quelle des Imports',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_template (template_id),
        INDEX idx_customer (customer_id),
        INDEX idx_reward_def (reward_definition_id),
        UNIQUE KEY unique_import (template_id, customer_id) COMMENT 'Ein Template kann nur einmal pro User importiert werden'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Tracking von importierten Vendor-Belohnungen';
    ";
    
    $pdo->exec($sql);
    echo "   ✓ Tabelle erfolgreich erstellt\n\n";
    
    // 3. Zeige finale Struktur
    echo "3. Finale Struktur:\n";
    $stmt = $pdo->query("DESCRIBE reward_template_imports");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']})\n";
    }
    
    // 4. Zeige Indizes
    echo "\n4. Indizes:\n";
    $stmt = $pdo->query("SHOW INDEX FROM reward_template_imports");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $shown = [];
    foreach ($indexes as $idx) {
        $keyName = $idx['Key_name'];
        if (!in_array($keyName, $shown)) {
            echo "   - {$keyName}\n";
            $shown[] = $keyName;
        }
    }
    
    echo "\n✅ Migration erfolgreich abgeschlossen!\n";
    echo "\nDie Tabelle 'reward_template_imports' wurde erstellt.\n";
    echo "Jetzt können Vendor-Belohnungen importiert werden.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Fehler bei der Migration:\n";
    echo "   {$e->getMessage()}\n\n";
    echo "SQL State: " . ($e->errorInfo[0] ?? 'N/A') . "\n";
    echo "Error Code: " . $e->getCode() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Allgemeiner Fehler:\n";
    echo "   {$e->getMessage()}\n\n";
    exit(1);
}
