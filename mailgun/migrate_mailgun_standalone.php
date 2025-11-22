<?php
/**
 * Mailgun Empfehlungsprogramm - Standalone Migration
 * Funktioniert ohne config/database.php
 */

// DATENBANK-CREDENTIALS HIER EINTRAGEN:
$db_host = 'localhost';
$db_name = 'kihcgcmy_ki_lead';
$db_user = 'kihcgcmy_ki_lead';
$db_pass = 'DEIN_DB_PASSWORT';  // <-- HIER EINTRAGEN!

echo "🚀 Mailgun Migration\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Datenbankverbindung hergestellt\n\n";
    
    // 1. reward_emails_sent
    $pdo->exec("CREATE TABLE IF NOT EXISTS reward_emails_sent (
        id INT PRIMARY KEY AUTO_INCREMENT,
        lead_id INT NOT NULL,
        reward_id INT NOT NULL,
        mailgun_id VARCHAR(255) NULL,
        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        opened_at DATETIME NULL,
        clicked_at DATETIME NULL,
        INDEX idx_lead (lead_id),
        INDEX idx_reward (reward_id),
        UNIQUE KEY unique_reward (lead_id, reward_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ reward_emails_sent erstellt\n";
    
    // 2. email_verifications
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        lead_id INT NOT NULL,
        token VARCHAR(64) UNIQUE NOT NULL,
        verified_at DATETIME NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_lead (lead_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ email_verifications erstellt\n";
    
    // 3. mailgun_events
    $pdo->exec("CREATE TABLE IF NOT EXISTS mailgun_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id VARCHAR(255) NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        recipient VARCHAR(255) NOT NULL,
        event_data JSON NULL,
        received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message (message_id),
        INDEX idx_type (event_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ mailgun_events erstellt\n\n";
    
    // 4. Erweitere lead_users
    $stmt = $pdo->query("DESCRIBE lead_users");
    $existing = [];
    while ($row = $stmt->fetch()) $existing[] = $row['Field'];
    
    $cols = [
        'ip_address' => "VARCHAR(45) NULL",
        'user_agent' => "TEXT NULL",
        'status' => "VARCHAR(50) DEFAULT 'active'",
        'dsgvo_consent_at' => "DATETIME NULL",
        'unsubscribed_at' => "DATETIME NULL",
        'flag_reason' => "VARCHAR(255) NULL"
    ];
    
    foreach ($cols as $col => $def) {
        if (!in_array($col, $existing)) {
            $pdo->exec("ALTER TABLE lead_users ADD COLUMN $col $def");
            echo "✅ lead_users.$col hinzugefügt\n";
        }
    }
    
    // 5. Erweitere users
    $stmt = $pdo->query("DESCRIBE users");
    $existing = [];
    while ($row = $stmt->fetch()) $existing[] = $row['Field'];
    
    $cols = [
        'avv_accepted_at' => "DATETIME NULL",
        'email_verification_enabled' => "BOOLEAN DEFAULT FALSE",
        'company_imprint_html' => "TEXT NULL"
    ];
    
    foreach ($cols as $col => $def) {
        if (!in_array($col, $existing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "✅ users.$col hinzugefügt\n";
        }
    }
    
    echo "\n🎉 Migration erfolgreich!\n";
    
} catch (PDOException $e) {
    echo "❌ FEHLER: " . $e->getMessage() . "\n";
    exit(1);
}
