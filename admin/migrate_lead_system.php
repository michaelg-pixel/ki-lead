<?php
/**
 * Datenbank-Migration: Lead-System Erweiterungen
 * - Fügt fehlende Felder zu lead_users hinzu
 * - Fügt Webhook-Felder zu users hinzu
 * - Erstellt referral_claimed_rewards Tabelle falls nicht vorhanden
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDBConnection();

echo "<!DOCTYPE html>
<html lang='de'>
<head>
    <meta charset='UTF-8'>
    <title>Datenbank Migration</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .log { background: white; padding: 20px; border-radius: 8px; max-width: 800px; margin: 0 auto; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        h1 { color: #1a1a1a; }
        pre { background: #f9f9f9; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
<div class='log'>
<h1>🔄 Datenbank Migration</h1>";

$migrations = [];

/**
 * Helper: Prüft ob eine Spalte existiert
 */
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// ===== MIGRATION 1: lead_users erweitern =====
try {
    echo "<p class='info'><strong>Migration 1:</strong> lead_users Tabelle erweitern...</p>";
    
    // created_at hinzufügen (WICHTIG!)
    if (!columnExists($pdo, 'lead_users', 'created_at')) {
        $pdo->exec("ALTER TABLE lead_users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✓ created_at Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ created_at existiert bereits</p>";
    }
    
    // freebie_id hinzufügen
    if (!columnExists($pdo, 'lead_users', 'freebie_id')) {
        $pdo->exec("ALTER TABLE lead_users ADD COLUMN freebie_id INT NULL AFTER user_id");
        $pdo->exec("ALTER TABLE lead_users ADD INDEX idx_freebie (freebie_id)");
        echo "<p class='success'>✓ freebie_id Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ freebie_id existiert bereits</p>";
    }
    
    // referrer_id hinzufügen
    if (!columnExists($pdo, 'lead_users', 'referrer_id')) {
        $pdo->exec("ALTER TABLE lead_users ADD COLUMN referrer_id INT NULL AFTER referral_code");
        $pdo->exec("ALTER TABLE lead_users ADD INDEX idx_referrer (referrer_id)");
        echo "<p class='success'>✓ referrer_id Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ referrer_id existiert bereits</p>";
    }
    
    // status hinzufügen
    if (!columnExists($pdo, 'lead_users', 'status')) {
        $pdo->exec("ALTER TABLE lead_users ADD COLUMN status VARCHAR(50) DEFAULT 'active' AFTER referrer_id");
        $pdo->exec("ALTER TABLE lead_users ADD INDEX idx_status (status)");
        echo "<p class='success'>✓ status Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ status existiert bereits</p>";
    }
    
    $migrations[] = "lead_users erweitert";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Fehler bei lead_users Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===== MIGRATION 2: users Webhook-Felder =====
try {
    echo "<p class='info'><strong>Migration 2:</strong> users Tabelle für Webhooks erweitern...</p>";
    
    // autoresponder_webhook_url hinzufügen
    if (!columnExists($pdo, 'users', 'autoresponder_webhook_url')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN autoresponder_webhook_url TEXT NULL");
        echo "<p class='success'>✓ autoresponder_webhook_url Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ autoresponder_webhook_url existiert bereits</p>";
    }
    
    // autoresponder_api_key hinzufügen
    if (!columnExists($pdo, 'users', 'autoresponder_api_key')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN autoresponder_api_key VARCHAR(255) NULL");
        echo "<p class='success'>✓ autoresponder_api_key Spalte hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ autoresponder_api_key existiert bereits</p>";
    }
    
    $migrations[] = "users Webhook-Felder hinzugefügt";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Fehler bei users Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===== MIGRATION 3: referral_claimed_rewards Tabelle =====
try {
    echo "<p class='info'><strong>Migration 3:</strong> referral_claimed_rewards Tabelle erstellen...</p>";
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS referral_claimed_rewards (
            id INT PRIMARY KEY AUTO_INCREMENT,
            lead_id INT NOT NULL,
            reward_id INT NOT NULL,
            reward_name VARCHAR(255) NOT NULL,
            claimed_at DATETIME NOT NULL,
            INDEX idx_lead (lead_id),
            INDEX idx_reward (reward_id),
            UNIQUE KEY unique_claim (lead_id, reward_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='success'>✓ referral_claimed_rewards Tabelle erstellt/aktualisiert</p>";
    
    $migrations[] = "referral_claimed_rewards Tabelle erstellt";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Fehler bei referral_claimed_rewards Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===== MIGRATION 4: lead_referrals erweitern =====
try {
    echo "<p class='info'><strong>Migration 4:</strong> lead_referrals Tabelle prüfen...</p>";
    
    // freebie_id hinzufügen falls nicht vorhanden
    if (!columnExists($pdo, 'lead_referrals', 'freebie_id')) {
        $pdo->exec("ALTER TABLE lead_referrals ADD COLUMN freebie_id INT NULL AFTER referred_name");
        $pdo->exec("ALTER TABLE lead_referrals ADD INDEX idx_freebie (freebie_id)");
        echo "<p class='success'>✓ freebie_id in lead_referrals hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ freebie_id existiert bereits</p>";
    }
    
    // invited_at prüfen
    if (!columnExists($pdo, 'lead_referrals', 'invited_at')) {
        $pdo->exec("ALTER TABLE lead_referrals ADD COLUMN invited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✓ invited_at in lead_referrals hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ invited_at existiert bereits</p>";
    }
    
    $migrations[] = "lead_referrals erweitert";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Fehler bei lead_referrals Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===== MIGRATION 5: lead_login_tokens erweitern =====
try {
    echo "<p class='info'><strong>Migration 5:</strong> lead_login_tokens erweitern...</p>";
    
    if (!columnExists($pdo, 'lead_login_tokens', 'referral_code')) {
        $pdo->exec("ALTER TABLE lead_login_tokens ADD COLUMN referral_code VARCHAR(50) NULL AFTER freebie_id");
        echo "<p class='success'>✓ referral_code in lead_login_tokens hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ referral_code existiert bereits</p>";
    }
    
    // created_at prüfen
    if (!columnExists($pdo, 'lead_login_tokens', 'created_at')) {
        $pdo->exec("ALTER TABLE lead_login_tokens ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        echo "<p class='success'>✓ created_at in lead_login_tokens hinzugefügt</p>";
    } else {
        echo "<p class='info'>→ created_at existiert bereits</p>";
    }
    
    $migrations[] = "lead_login_tokens erweitert";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Fehler bei lead_login_tokens Migration: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ===== ZUSAMMENFASSUNG =====
echo "<hr>";
echo "<h2>✅ Migration abgeschlossen</h2>";
echo "<p><strong>Durchgeführte Migrationen:</strong></p>";
echo "<ul>";
foreach ($migrations as $migration) {
    echo "<li>" . htmlspecialchars($migration) . "</li>";
}
echo "</ul>";

echo "<p style='margin-top: 30px;'><a href='/lead_register.php?freebie=7&customer=4' style='background: #8B5CF6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; display: inline-block;'>Jetzt Lead registrieren testen</a></p>";

echo "</div>
</body>
</html>";
?>
