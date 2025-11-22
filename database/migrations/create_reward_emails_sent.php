<?php
/**
 * Einfache Mailgun Migration - reward_emails_sent Tabelle
 * Direkt ausführbar ohne Browser-Interface
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    // Prüfen ob Tabelle bereits existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'reward_emails_sent'");
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabelle 'reward_emails_sent' existiert bereits!\n";
        echo "\nTabellen-Struktur:\n";
        $stmt = $pdo->query("DESCRIBE reward_emails_sent");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
        exit(0);
    }
    
    // Tabelle erstellen
    echo "🚀 Erstelle Tabelle 'reward_emails_sent'...\n";
    
    $pdo->exec("
        CREATE TABLE reward_emails_sent (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lead_id INT NOT NULL,
            reward_id INT NOT NULL,
            mailgun_id VARCHAR(255) NULL COMMENT 'Mailgun Message-ID',
            email_type VARCHAR(50) DEFAULT 'reward_unlocked',
            sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            opened_at DATETIME NULL,
            clicked_at DATETIME NULL,
            failed_at DATETIME NULL,
            error_message TEXT NULL,
            INDEX idx_lead (lead_id),
            INDEX idx_reward (reward_id),
            INDEX idx_mailgun_id (mailgun_id),
            INDEX idx_email_type (email_type),
            INDEX idx_sent_at (sent_at),
            UNIQUE KEY unique_reward (lead_id, reward_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✅ Tabelle erfolgreich erstellt!\n\n";
    
    // Verifizierung
    echo "📋 Tabellen-Struktur:\n";
    $stmt = $pdo->query("DESCRIBE reward_emails_sent");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    
    echo "\n✨ Migration abgeschlossen!\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}
