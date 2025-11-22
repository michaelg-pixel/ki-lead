<?php
/**
 * Migration: av_contract_acceptances Tabelle erstellen/erweitern
 * 
 * Erstellt die Tabelle für AV-Vertrags-Zustimmungen falls nicht vorhanden
 * Wird benötigt für Mailgun-Consent Tracking
 */

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🔧 Starte Migration für av_contract_acceptances...\n\n";
    
    // Prüfe ob Tabelle existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'av_contract_acceptances'");
    $table_exists = $stmt->rowCount() > 0;
    
    if (!$table_exists) {
        echo "📋 Tabelle av_contract_acceptances existiert nicht - erstelle neu...\n";
        
        $sql = "
        CREATE TABLE av_contract_acceptances (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            accepted_at DATETIME NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            av_contract_version VARCHAR(50) NOT NULL,
            acceptance_type VARCHAR(50) NOT NULL DEFAULT 'registration',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_acceptance_type (acceptance_type),
            INDEX idx_accepted_at (accepted_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($sql);
        echo "✅ Tabelle av_contract_acceptances erfolgreich erstellt!\n\n";
    } else {
        echo "✅ Tabelle av_contract_acceptances existiert bereits\n";
        
        // Prüfe ob acceptance_type Spalte existiert
        $stmt = $pdo->query("SHOW COLUMNS FROM av_contract_acceptances LIKE 'acceptance_type'");
        if ($stmt->rowCount() === 0) {
            echo "📋 Spalte acceptance_type fehlt - füge hinzu...\n";
            $pdo->exec("
                ALTER TABLE av_contract_acceptances 
                ADD COLUMN acceptance_type VARCHAR(50) NOT NULL DEFAULT 'registration' AFTER av_contract_version
            ");
            $pdo->exec("
                ALTER TABLE av_contract_acceptances 
                ADD INDEX idx_acceptance_type (acceptance_type)
            ");
            echo "✅ Spalte acceptance_type hinzugefügt!\n";
        } else {
            echo "✅ Spalte acceptance_type existiert bereits\n";
        }
    }
    
    // Zeige Statistiken
    echo "\n📊 Aktuelle Statistiken:\n";
    $stmt = $pdo->query("
        SELECT acceptance_type, COUNT(*) as count 
        FROM av_contract_acceptances 
        GROUP BY acceptance_type
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($stats)) {
        echo "   Noch keine Einträge vorhanden\n";
    } else {
        foreach ($stats as $stat) {
            echo "   - {$stat['acceptance_type']}: {$stat['count']} Einträge\n";
        }
    }
    
    echo "\n✅ Migration erfolgreich abgeschlossen!\n";
    
} catch (PDOException $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}