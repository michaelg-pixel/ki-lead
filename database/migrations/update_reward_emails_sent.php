<?php
/**
 * Update reward_emails_sent Tabelle
 * Fügt fehlende Spalten hinzu
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "🔧 Erweitere Tabelle 'reward_emails_sent'...\n\n";
    
    // Prüfe welche Spalten fehlen
    $stmt = $pdo->query("SHOW COLUMNS FROM reward_emails_sent");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    // email_type hinzufügen
    if (!in_array('email_type', $existingColumns)) {
        echo "➕ Füge Spalte 'email_type' hinzu...\n";
        $pdo->exec("ALTER TABLE reward_emails_sent ADD COLUMN email_type VARCHAR(50) DEFAULT 'reward_unlocked' AFTER mailgun_id");
    } else {
        echo "✅ Spalte 'email_type' existiert bereits\n";
    }
    
    // failed_at hinzufügen
    if (!in_array('failed_at', $existingColumns)) {
        echo "➕ Füge Spalte 'failed_at' hinzu...\n";
        $pdo->exec("ALTER TABLE reward_emails_sent ADD COLUMN failed_at DATETIME NULL AFTER clicked_at");
    } else {
        echo "✅ Spalte 'failed_at' existiert bereits\n";
    }
    
    // error_message hinzufügen
    if (!in_array('error_message', $existingColumns)) {
        echo "➕ Füge Spalte 'error_message' hinzu...\n";
        $pdo->exec("ALTER TABLE reward_emails_sent ADD COLUMN error_message TEXT NULL AFTER failed_at");
    } else {
        echo "✅ Spalte 'error_message' existiert bereits\n";
    }
    
    // Indizes hinzufügen (falls noch nicht vorhanden)
    echo "\n🔍 Erstelle Indizes...\n";
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD INDEX idx_lead (lead_id)");
        echo "✅ Index 'idx_lead' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Index 'idx_lead' existiert bereits\n";
        } else {
            throw $e;
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD INDEX idx_reward (reward_id)");
        echo "✅ Index 'idx_reward' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Index 'idx_reward' existiert bereits\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD INDEX idx_mailgun_id (mailgun_id)");
        echo "✅ Index 'idx_mailgun_id' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Index 'idx_mailgun_id' existiert bereits\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD INDEX idx_email_type (email_type)");
        echo "✅ Index 'idx_email_type' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Index 'idx_email_type' existiert bereits\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD INDEX idx_sent_at (sent_at)");
        echo "✅ Index 'idx_sent_at' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Index 'idx_sent_at' existiert bereits\n";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE reward_emails_sent ADD UNIQUE KEY unique_reward (lead_id, reward_id)");
        echo "✅ Unique Index 'unique_reward' erstellt\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✅ Unique Index 'unique_reward' existiert bereits\n";
        }
    }
    
    echo "\n📋 Aktualisierte Tabellen-Struktur:\n";
    $stmt = $pdo->query("DESCRIBE reward_emails_sent");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $null = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['Default'] ? " DEFAULT '{$col['Default']}'" : '';
        echo "- {$col['Field']} ({$col['Type']}) {$null}{$default}\n";
    }
    
    echo "\n✨ Update abgeschlossen!\n";
    echo "🎉 Die Tabelle ist jetzt vollständig für Mailgun Email-Tracking!\n";
    
} catch (PDOException $e) {
    echo "❌ Fehler: " . $e->getMessage() . "\n";
    exit(1);
}
