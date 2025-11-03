<?php
/**
 * Setup Script: Empfehlungsprogramm-Tabellen erstellen
 * Rufen Sie diese Datei auf: https://app.mehr-infos-jetzt.de/setup-referral-system.php
 */

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "<h2>🚀 Empfehlungsprogramm-Setup</h2>";
    echo "<hr><br>";
    
    // 1. User-Tabelle erweitern
    echo "<strong>1. Erweitere users-Tabelle...</strong><br>";
    
    $columns = [
        'referral_enabled' => "ALTER TABLE users ADD COLUMN referral_enabled TINYINT(1) DEFAULT 0 AFTER is_active",
        'ref_code' => "ALTER TABLE users ADD COLUMN ref_code VARCHAR(20) NULL AFTER referral_enabled",
        'company_name' => "ALTER TABLE users ADD COLUMN company_name VARCHAR(255) NULL AFTER ref_code",
        'company_email' => "ALTER TABLE users ADD COLUMN company_email VARCHAR(255) NULL AFTER company_name",
        'company_imprint_html' => "ALTER TABLE users ADD COLUMN company_imprint_html TEXT NULL AFTER company_email"
    ];
    
    foreach ($columns as $col => $sql) {
        try {
            // Prüfe ob Spalte existiert
            $check = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'")->fetch();
            if (!$check) {
                $pdo->exec($sql);
                echo "✅ Spalte '$col' hinzugefügt<br>";
            } else {
                echo "ℹ️ Spalte '$col' existiert bereits<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ Fehler bei '$col': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br>";
    
    // 2. Referral-Klicks Tabelle
    echo "<strong>2. Erstelle referral_clicks Tabelle...</strong><br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS referral_clicks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ref_code VARCHAR(20) NOT NULL,
        fingerprint VARCHAR(64) NOT NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        referrer TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_ref_code (ref_code),
        INDEX idx_fingerprint (fingerprint),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabelle 'referral_clicks' erstellt<br><br>";
    
    // 3. Referral-Conversions Tabelle
    echo "<strong>3. Erstelle referral_conversions Tabelle...</strong><br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS referral_conversions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ref_code VARCHAR(20) NOT NULL,
        fingerprint VARCHAR(64) NOT NULL,
        click_id INT NULL,
        source VARCHAR(50) DEFAULT 'pixel',
        time_to_convert INT NULL,
        suspicious TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_ref_code (ref_code),
        INDEX idx_click (click_id),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabelle 'referral_conversions' erstellt<br><br>";
    
    // 4. Referral-Leads Tabelle
    echo "<strong>4. Erstelle referral_leads Tabelle...</strong><br>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS referral_leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        ref_code VARCHAR(20) NOT NULL,
        email VARCHAR(255) NOT NULL,
        fingerprint VARCHAR(64) NULL,
        confirmed TINYINT(1) DEFAULT 0,
        confirm_token VARCHAR(64) NULL,
        reward_notified TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        confirmed_at TIMESTAMP NULL,
        INDEX idx_user (user_id),
        INDEX idx_ref_code (ref_code),
        INDEX idx_email (email),
        INDEX idx_token (confirm_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✅ Tabelle 'referral_leads' erstellt<br><br>";
    
    // 5. Ref-Codes für existierende User generieren
    echo "<strong>5. Generiere Referral-Codes...</strong><br>";
    $users = $pdo->query("SELECT id, email FROM users WHERE role = 'customer' AND (ref_code IS NULL OR ref_code = '')")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $refCode = strtoupper(substr(md5($user['email'] . time()), 0, 8));
        $pdo->prepare("UPDATE users SET ref_code = ? WHERE id = ?")->execute([$refCode, $user['id']]);
    }
    
    echo "✅ " . count($users) . " Referral-Codes generiert<br><br>";
    
    echo "<hr>";
    echo "<h3>🎉 Setup erfolgreich!</h3>";
    echo "<p>Das Empfehlungsprogramm ist jetzt einsatzbereit.</p>";
    echo "<br><a href='/admin/dashboard.php?page=referrals' style='padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Zur Empfehlungs-Übersicht</a>";
    
} catch (Exception $e) {
    echo "<h3>❌ Fehler beim Setup</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>