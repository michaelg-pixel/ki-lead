<?php
/**
 * Mailgun Empfehlungsprogramm - Standalone Migration
 * Erweitert bestehendes System (KEIN Parallel-System!)
 */

// DATENBANK-CREDENTIALS (aus config/database.php)
$db_host = 'localhost';
$db_name = 'lumisaas';
$db_user = 'lumisaas52';
$db_pass = 'I1zx1XdL1hrWd75yu57e';

echo "🚀 Mailgun System-Integration - Datenbank-Migration\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user, $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Datenbankverbindung hergestellt (DB: $db_name)\n\n";
    
    // 1. reward_emails_sent - Tracking versendeter Belohnungs-Emails
    echo "📧 Erstelle reward_emails_sent Tabelle...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS reward_emails_sent (
        id INT PRIMARY KEY AUTO_INCREMENT,
        lead_id INT NOT NULL,
        reward_id INT NOT NULL,
        mailgun_id VARCHAR(255) NULL COMMENT 'Mailgun Message-ID',
        sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        opened_at DATETIME NULL COMMENT 'Wann Email geöffnet wurde',
        clicked_at DATETIME NULL COMMENT 'Wann Link geklickt wurde',
        INDEX idx_lead (lead_id),
        INDEX idx_reward (reward_id),
        INDEX idx_mailgun (mailgun_id),
        UNIQUE KEY unique_reward (lead_id, reward_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracking versendeter Belohnungs-Emails'");
    echo "   ✅ reward_emails_sent erstellt\n\n";
    
    // 2. email_verifications - Email-Verifizierungs-Tokens (optional)
    echo "🔐 Erstelle email_verifications Tabelle...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT PRIMARY KEY AUTO_INCREMENT,
        lead_id INT NOT NULL,
        token VARCHAR(64) UNIQUE NOT NULL,
        verified_at DATETIME NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_lead (lead_id),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Email-Verifizierungs-Tokens'");
    echo "   ✅ email_verifications erstellt\n\n";
    
    // 3. mailgun_events - Mailgun Event-Logs (Öffnungen, Klicks)
    echo "📊 Erstelle mailgun_events Tabelle...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS mailgun_events (
        id INT PRIMARY KEY AUTO_INCREMENT,
        message_id VARCHAR(255) NOT NULL,
        event_type VARCHAR(50) NOT NULL COMMENT 'opened, clicked, delivered, etc.',
        recipient VARCHAR(255) NOT NULL,
        event_data JSON NULL,
        received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_message (message_id),
        INDEX idx_type (event_type),
        INDEX idx_recipient (recipient)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Mailgun Event-Logs'");
    echo "   ✅ mailgun_events erstellt\n\n";
    
    // 4. Erweitere lead_users (DSGVO, IP-Tracking)
    echo "👤 Erweitere lead_users Tabelle...\n";
    $stmt = $pdo->query("DESCRIBE lead_users");
    $existing = [];
    while ($row = $stmt->fetch()) $existing[] = $row['Field'];
    
    $cols = [
        'ip_address' => "VARCHAR(45) NULL COMMENT 'IP für Betrugsschutz'",
        'user_agent' => "TEXT NULL COMMENT 'Browser Info'",
        'status' => "VARCHAR(50) DEFAULT 'active' COMMENT 'active, blocked, deleted'",
        'dsgvo_consent_at' => "DATETIME NULL COMMENT 'DSGVO-Zustimmung'",
        'unsubscribed_at' => "DATETIME NULL COMMENT 'Email-Abmeldung'",
        'flag_reason' => "VARCHAR(255) NULL COMMENT 'Grund für Blockierung'"
    ];
    
    foreach ($cols as $col => $def) {
        if (!in_array($col, $existing)) {
            $pdo->exec("ALTER TABLE lead_users ADD COLUMN $col $def");
            echo "   ✅ Spalte $col hinzugefügt\n";
        } else {
            echo "   ⏭️  Spalte $col existiert bereits\n";
        }
    }
    
    // 5. Erweitere users (AVV-Zustimmung, Impressum)
    echo "\n🏢 Erweitere users Tabelle...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $existing = [];
    while ($row = $stmt->fetch()) $existing[] = $row['Field'];
    
    $cols = [
        'avv_accepted_at' => "DATETIME NULL COMMENT 'Mailgun AVV-Zustimmung'",
        'email_verification_enabled' => "BOOLEAN DEFAULT FALSE COMMENT 'Email-Verifizierung aktiviert'",
        'company_imprint_html' => "TEXT NULL COMMENT 'HTML-Impressum für Emails'"
    ];
    
    foreach ($cols as $col => $def) {
        if (!in_array($col, $existing)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
            echo "   ✅ Spalte $col hinzugefügt\n";
        } else {
            echo "   ⏭️  Spalte $col existiert bereits\n";
        }
    }
    
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "🎉 MIGRATION ERFOLGREICH ABGESCHLOSSEN!\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    echo "📋 Erstellte Tabellen:\n";
    echo "   • reward_emails_sent     - Tracking versendeter Belohnungs-Emails\n";
    echo "   • email_verifications    - Email-Verifizierungs-Tokens (optional)\n";
    echo "   • mailgun_events         - Mailgun Event-Logs (Öffnungen, Klicks)\n\n";
    
    echo "🔧 Erweiterte Tabellen:\n";
    echo "   • lead_users             - IP-Tracking, Status, DSGVO-Felder\n";
    echo "   • users                  - AVV-Zustimmung, Impressum-Feld\n\n";
    
    echo "⏭️  Nächste Schritte:\n";
    echo "   1. Reward-Trigger in lead_register.php einfügen (siehe Doku)\n";
    echo "   2. Test-Email versenden: php mailgun/test_mailgun.php\n";
    echo "   3. Live-Test mit echten Empfehlungen\n\n";
    
} catch (PDOException $e) {
    echo "\n❌ FEHLER: " . $e->getMessage() . "\n";
    echo "\n💡 Prüfe:\n";
    echo "   • Sind die DB-Credentials korrekt?\n";
    echo "   • Hat der User die nötigen Rechte?\n";
    echo "   • Existiert die Datenbank '$db_name'?\n\n";
    exit(1);
}
